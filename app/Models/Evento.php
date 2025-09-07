<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Evento extends Model
{
    use HasFactory;
    protected $table = 'evento';
    protected $fillable = [
        'id_bitacora',
        'id_seccion',
        'id_subarea',
        'id_horario',
        'id_institucion',
        'id_horario_leccion',
        'user_id',
        'fecha',
        'observacion',
        'prioridad',
        'estado',
        'confirmacion'
    ];

    protected $attributes = [
        'estado' => 'en_espera',
        'prioridad' => 'media'
    ];

    public function bitacora()
    {
        return $this->belongsTo(Bitacora::class, 'id_bitacora');
    }

    public function seccion()
    {

        return $this->belongsTo(Seccione::class, 'id_seccion');

    }

    public function subarea()
    {

        return $this->belongsTo(Subarea::class, 'id_subarea');

    }

    public function horarioLeccion()
    {
        return $this->belongsTo(HorarioLeccion::class, 'id_horario_leccion');
    }

    public function horario()
    {
        return $this->belongsTo(Horario::class, 'id_horario');
    }

    public function leccion()
    {
        return $this->belongsToMany(Leccion::class, 'horario_leccion', 'idHorario', 'idLeccion');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function institucion()
    {
        return $this->belongsTo(Institucione::class, 'id_institucion');
    }

    // Método para obtener solo profesores
    public function profesor()
    {
        return $this->belongsTo(User::class, 'user_id')->whereHas('roles', function ($query) {
            $query->where('name', 'profesor');
        });
    }

}
