<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Horario extends Model
{
    protected $table = 'horarios';
    
    protected $fillable = ['idRecinto', 
                        'idSubarea',
                        'idSeccion',
                        'user_id',
                        'tipoHorario', 
                        'fecha',
                        'dia',
                        'condicion'];
    

    protected $casts = [
        'tipoHorario' => 'boolean',
        'fecha' => 'date',
        'condicion' => 'integer'
    ];
    
    
    public function recinto()
    {
        return $this->belongsTo(Recinto::class, 'idRecinto');
    }

    public function subarea()
    {
        return $this->belongsTo(Subarea::class, 'idSubarea');
    }

    public function seccion()
    {
        return $this->belongsTo(Seccione::class, 'idSeccion');
    }

    public function leccion()
    {
        return $this->belongsToMany(Leccion::class, 'horario_leccion', 'idHorario', 'idLeccion')
            ->withPivot('condicion')
            ->withTimestamps();
    }

    public function profesor()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function profesorUsuario()
    {
         return $this->belongsTo(User::class, 'user_id')->where('rol', 'profesor');
    }

    public function getHoraEntradaAttribute()
    {
        if ($this->leccion->isEmpty()) return 'N/A';

        $primera = $this->leccion
            ->sortBy(fn($l) => $this->normalizarHora($l->hora_inicio, $l->hora_inicio_periodo ?? null)->timestamp)
            ->first();

        return $this->formatearHora($primera->hora_inicio, $primera->hora_inicio_periodo ?? null);
    }

    public function getHoraSalidaAttribute()
    {
        if ($this->leccion->isEmpty()) return 'N/A';

        $ultima = $this->leccion
            ->sortBy(fn($l) => $this->normalizarHora($l->hora_final, $l->hora_final_periodo ?? null)->timestamp)
            ->last();

        return $this->formatearHora($ultima->hora_final, $ultima->hora_final_periodo ?? null);
    }
    
    private function normalizarHora(string $hora, ?string $periodo): Carbon
    {
        $hora = trim($hora);
        if ($periodo) {
            $periodo = strtoupper(trim($periodo));
            foreach (['h:i A','h:i:s A'] as $fmt) {
                try { return Carbon::createFromFormat($fmt, $hora.' '.$periodo); } catch (\Exception $e) {}
            }
        }
        try { return Carbon::parse($hora . ($periodo ? (' '.$periodo):'')); } catch (\Exception $e) { return Carbon::parse('00:00'); }
    }

    private function formatearHora(string $hora, ?string $periodo): string
    {
        return $periodo ? (trim($hora).' '.strtoupper($periodo)) : $hora;
    }

    // Método para filtrar lecciones nocturnas
    private function leccionesNocturnas()
    {
        return $this->leccion->filter(function($leccion) {
            $horaInicio = $leccion->hora_inicio;
            $periodo = $leccion->hora_inicio_periodo ?? 'AM';
            
            // Solo lecciones técnicas pueden ser nocturnas
            if (strtolower($leccion->tipoLeccion) !== 'tecnica') {
                return false;
            }
            
            if ($periodo == 'PM') {
                list($hora, $minuto) = explode(':', $horaInicio);
                $hora = (int)$hora;
                $minuto = (int)$minuto;
                
                // Nocturno: 5:50 PM en adelante
                if ($hora >= 6 || ($hora == 5 && $minuto >= 50)) {
                    return true;
                }
            }
            
            return false;
        });
    }

    // Hora de entrada de lecciones nocturnas
    public function getHoraEntradaNocturnaAttribute()
    {
        $leccionesNocturnas = $this->leccionesNocturnas();
        
        if ($leccionesNocturnas->isEmpty()) {
            return 'N/A';
        }
        
        $primeraLeccion = $leccionesNocturnas->sortBy(function($leccion) {
            $hora = $leccion->hora_inicio;
            $periodo = $leccion->hora_inicio_periodo ?? 'AM';
            $time = explode(':', $hora);
            $hour = (int)$time[0];
            $minute = (int)$time[1];
            
            // Convertir a formato de 24 horas para ordenamiento
            if ($periodo == 'PM') {
                if ($hour == 12) {
                    return sprintf('12:%02d', $minute);
                } else {
                    return sprintf('%02d:%02d', $hour + 12, $minute);
                }
            } else {
                return sprintf('%02d:%02d', $hour, $minute);
            }
        })->first();
        
        return $primeraLeccion->hora_inicio . ' ' . ($primeraLeccion->hora_inicio_periodo ?? 'PM');
    }

    // Hora de salida de lecciones nocturnas
    public function getHoraSalidaNocturnaAttribute()
    {
        $leccionesNocturnas = $this->leccionesNocturnas();
        
        if ($leccionesNocturnas->isEmpty()) {
            return 'N/A';
        }
        
        $ultimaLeccion = $leccionesNocturnas->sortByDesc(function($leccion) {
            $hora = $leccion->hora_final;
            $periodo = $leccion->hora_final_periodo ?? 'AM';
            $time = explode(':', $hora);
            $hour = (int)$time[0];
            $minute = (int)$time[1];
            
            // Convertir a formato de 24 horas para ordenamiento
            if ($periodo == 'PM') {
                if ($hour == 12) {
                    return sprintf('12:%02d', $minute);
                } else {
                    return sprintf('%02d:%02d', $hour + 12, $minute);
                }
            } else {
                return sprintf('%02d:%02d', $hour, $minute);
            }
        })->first();
        
        return $ultimaLeccion->hora_final . ' ' . ($ultimaLeccion->hora_final_periodo ?? 'PM');
    }

    // Mtodo adicional: verificar si el horario es nocturno
    public function getEsNocturnoAttribute()
    {
        return !$this->leccionesNocturnas()->isEmpty();
    }

    // Método adicional: verificar si el horario es diurno
    public function getEsDiurnoAttribute()
    {
        $leccionesDiurnas = $this->leccion->filter(function($leccion) {
            // Las académicas son siempre diurnas
            if (strtolower($leccion->tipoLeccion) == 'academica') {
                return true;
            }
            
            // Para técnicas, verificar la hora
            $horaInicio = $leccion->hora_inicio;
            $periodo = $leccion->hora_inicio_periodo ?? 'AM';
            
            if ($periodo == 'AM') {
                return true;
            } else { // PM
                list($hora, $minuto) = explode(':', $horaInicio);
                $hora = (int)$hora;
                $minuto = (int)$minuto;
                
                // Diurno: antes de 5:50 PM
                if ($hora == 12 || ($hora >= 1 && $hora <= 4) || ($hora == 4 && $minuto <= 20)) {
                    return true;
                }
            }
            
            return false;
        });
        
        return !$leccionesDiurnas->isEmpty();
    }
}