<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Horario;
use Carbon\Carbon;
use Illuminate\Validation\Validator;

class UpdateHorarioRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'idRecinto' => 'required|integer|exists:recinto,id',
            'idSubarea' => 'required|integer|exists:subarea,id',
            'idSeccion' => 'required|integer|exists:seccione,id',
            'user_id' => 'required|integer|exists:users,id',
            'tipoHorario' => 'required|string|in:fijo,temporal',
            'fecha' => [
                'nullable',
                'date',
                'required_if:tipoHorario,temporal'
            ],
            'dia' => [
                'nullable',
                'string',
                'required_if:tipoHorario,fijo',
                'in:Lunes,Martes,Miércoles,Jueves,Viernes,Sábado,Domingo'
            ],
            'lecciones' => 'required|array|min:1',
            'lecciones.*' => 'integer|exists:leccion,id',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Obtener el ID del horario que se está actualizando
            $horarioId = $this->route('horario')->id ?? null;

            // Validar que se seleccione al menos una lección
            if (!$this->has('lecciones') || empty($this->lecciones)) {
                $validator->errors()->add('lecciones', 'Debe seleccionar al menos una lección.');
                return;
            }

            // PRIORIDAD 1: Validar que la misma sección de la misma especialidad no tenga conflictos
            $this->validateSameSectionSpecialtyConflicts($validator, $horarioId);

            // PRIORIDAD 2: Validar conflictos entre horarios temporales y fijos
            $this->validateTemporalFixedConflicts($validator, $horarioId);

            // PRIORIDAD 3: Validar conflictos de horario: Un profesor no puede estar en múltiples recintos al mismo tiempo
            $this->validateTimeConflicts($validator, $horarioId);

            // PRIORIDAD 4: Validar conflictos en el mismo recinto: Dos profesores no pueden estar en el mismo recinto al mismo tiempo
            $this->validateSameRecintoConflicts($validator, $horarioId);

            // PRIORIDAD 5: Validar conflictos de secciones: Un profesor no puede tener dos secciones de la misma subárea al mismo tiempo
            $this->validateSameSubareaConflicts($validator, $horarioId);

            // PRIORIDAD 6: Validar que las lecciones seleccionadas sean consecutivas
        // Ordenar las lecciones por hora de inicio
        $leccionesSeleccionadas = \App\Models\Leccion::whereIn('id', $this->lecciones)
            ->get()
            ->sortBy(function($leccion) {
                $inicio = $this->normalizarHora($leccion->hora_inicio, $leccion->hora_inicio_periodo ?? null);
                $fin    = $this->normalizarHora($leccion->hora_final,  $leccion->hora_final_periodo ?? null);
                return [$inicio->timestamp, $fin->timestamp];
            })
            ->values();

        $this->validateConsecutiveLecciones($leccionesSeleccionadas, $validator);

            // PRIORIDAD 7: Validar duplicados / solapamientos dentro del MISMO recinto y mismo profesor (permitiendo bloques separados no solapados)
            $query = Horario::where('user_id', $this->user_id)
                ->where('idRecinto', $this->idRecinto)
                ->where('condicion', 1);

            if ($horarioId) { $query->where('id', '!=', $horarioId); }

            if ($this->tipoHorario === 'fijo') {
                $query->where('dia', $this->dia)->where('tipoHorario', 1);
            } else {
                $query->where('fecha', $this->fecha)->where('tipoHorario', 0);
            }

            $candidatos = $query->with('leccion')->get();
            if ($candidatos->isNotEmpty()) {
                $leccionesNuevas = \App\Models\Leccion::whereIn('id', $this->lecciones)->get();
                $idsNuevosOrdenados = collect($this->lecciones)->sort()->values()->all();

                $conflicto = null;
                foreach ($candidatos as $cand) {
                    $idsCand = $cand->leccion->pluck('id')->sort()->values()->all();
                    if ($idsCand === $idsNuevosOrdenados) {
                        $tipo = $this->tipoHorario === 'fijo' ? 'día' : 'fecha';
                        $valor = $this->tipoHorario === 'fijo' ? $this->dia : $this->fecha;
                        $validator->errors()->add('lecciones', "Horario duplicado: ya existe este mismo conjunto de lecciones en este recinto para el {$tipo} {$valor}.");
                        return;
                    }
                    foreach ($cand->leccion as $leExist) {
                        foreach ($leccionesNuevas as $leNueva) {
                            if ($this->hayConflictoHorario($leExist, $leNueva)) { $conflicto = ['existente'=>$leExist,'nueva'=>$leNueva]; break 3; }
                        }
                    }
                }
                if ($conflicto) {
                    $tipo = $this->tipoHorario === 'fijo' ? 'día' : 'fecha';
                    $valor = $this->tipoHorario === 'fijo' ? $this->dia : $this->fecha;
                    $validator->errors()->add('lecciones', "Conflicto interno: ya tienes otro horario en este recinto para el {$tipo} {$valor} en " . $this->formatearRango($conflicto['existente']) . " que se superpone con " . $this->formatearRango($conflicto['nueva']) . ".");
                    return;
                }
            }
        });
    }

    /**
     * Validar conflictos de tiempo para el mismo profesor
     */
    private function validateTimeConflicts($validator, $horarioId = null)
    {
        if (!$this->has('lecciones') || empty($this->lecciones)) {
            return;
        }

        // Obtener las lecciones seleccionadas con sus horarios
        $leccionesSeleccionadas = \App\Models\Leccion::whereIn('id', $this->lecciones)->get();

        // Buscar horarios existentes del mismo profesor
        $horariosExistentes = collect();

        if ($this->tipoHorario === 'fijo') {
            // Para horarios fijos, buscar otros horarios fijos del mismo día
            $query = Horario::where('user_id', $this->user_id)
                ->where('dia', $this->dia)
                ->where('tipoHorario', 1)
                ->where('condicion', 1);

            if ($horarioId) {
                $query->where('id', '!=', $horarioId);
            }

            $horariosExistentes = $query->with('leccion')->get();
        } else {
            // Para horarios temporales, buscar horarios fijos del día correspondiente y otros temporales de la misma fecha
            $diaSemana = date('l', strtotime($this->fecha));
            $diasEspanol = [
                'Monday' => 'Lunes',
                'Tuesday' => 'Martes', 
                'Wednesday' => 'Miércoles',
                'Thursday' => 'Jueves',
                'Friday' => 'Viernes',
                'Saturday' => 'Sábado',
                'Sunday' => 'Domingo'
            ];
            $diaEnEspanol = $diasEspanol[$diaSemana];

            // Horarios fijos del mismo día de la semana
            $queryFijos = Horario::where('user_id', $this->user_id)
                ->where('dia', $diaEnEspanol)
                ->where('tipoHorario', 1)
                ->where('condicion', 1);

            // Otros horarios temporales de la misma fecha
            $queryTemporales = Horario::where('user_id', $this->user_id)
                ->where('fecha', $this->fecha)
                ->where('tipoHorario', 0)
                ->where('condicion', 1);

            if ($horarioId) {
                $queryFijos->where('id', '!=', $horarioId);
                $queryTemporales->where('id', '!=', $horarioId);
            }

            $horariosFijos = $queryFijos->with('leccion')->get();
            $horariosTemporales = $queryTemporales->with('leccion')->get();

            $horariosExistentes = $horariosFijos->merge($horariosTemporales);
        }

        // Verificar conflictos de tiempo
        foreach ($horariosExistentes as $horarioExistente) {
            // Si es el mismo recinto, ya se validó arriba
            if ($horarioExistente->idRecinto == $this->idRecinto) {
                continue;
            }

            foreach ($horarioExistente->leccion as $leccionExistente) {
                foreach ($leccionesSeleccionadas as $leccionNueva) {
                    if ($this->hayConflictoHorario($leccionExistente, $leccionNueva)) {
                        $recintoExistente = $horarioExistente->recinto->nombre ?? 'Recinto desconocido';
                        $tipoExistente = $horarioExistente->tipoHorario == 1 ? 'fijo' : 'temporal';
                        $validator->errors()->add('lecciones', "Conflicto de horario: El profesor ya tiene clase en {$recintoExistente} ({$tipoExistente}) en " . $this->formatearRango($leccionExistente) . ", que coincide con " . $this->formatearRango($leccionNueva) . ".");
                        return; // Salir después del primer conflicto encontrado
                    }
                }
            }
        }
    }

    /**
     * Verificar si dos lecciones tienen conflicto de horario
     */
    private function hayConflictoHorario($leccion1, $leccion2)
    {
        $inicio1 = $this->normalizarHora($leccion1->hora_inicio, $leccion1->hora_inicio_periodo ?? null);
        $fin1    = $this->normalizarHora($leccion1->hora_final,  $leccion1->hora_final_periodo ?? null);
        $inicio2 = $this->normalizarHora($leccion2->hora_inicio, $leccion2->hora_inicio_periodo ?? null);
        $fin2    = $this->normalizarHora($leccion2->hora_final,  $leccion2->hora_final_periodo ?? null);

        if ($fin1->lessThanOrEqualTo($inicio1)) { $fin1 = $fin1->copy()->addDay(); }
        if ($fin2->lessThanOrEqualTo($inicio2)) { $fin2 = $fin2->copy()->addDay(); }

        return ($inicio1 < $fin2) && ($fin1 > $inicio2);
    }

    private function normalizarHora(string $hora, ?string $periodo): Carbon
    {
        $hora = trim($hora);
        if ($periodo) {
            $periodo = strtoupper(trim($periodo));
            try { return Carbon::createFromFormat('h:i A', $hora . ' ' . $periodo); } catch (\Exception $e) {}
            try { return Carbon::createFromFormat('h:i:s A', $hora . ' ' . $periodo); } catch (\Exception $e) {}
        }
        try { return Carbon::parse($hora . ($periodo ? (' ' . $periodo) : '')); } catch (\Exception $e) { return Carbon::parse('00:00'); }
    }

    private function formatearRango($leccion): string
    {
        $ini = trim($leccion->hora_inicio);
        $fin = trim($leccion->hora_final);
        if (property_exists($leccion, 'hora_inicio_periodo') && $leccion->hora_inicio_periodo) $ini .= ' ' . strtoupper($leccion->hora_inicio_periodo);
        if (property_exists($leccion, 'hora_final_periodo') && $leccion->hora_final_periodo) $fin .= ' ' . strtoupper($leccion->hora_final_periodo);
        return $ini . ' - ' . $fin;
    }

    /**
     * Validar conflictos en el mismo recinto: Dos profesores no pueden estar en el mismo recinto al mismo tiempo
     */
    private function validateSameRecintoConflicts($validator, $horarioId = null)
    {
        if (!$this->has('lecciones') || empty($this->lecciones)) {
            return;
        }

        // Obtener las lecciones seleccionadas con sus horarios
        $leccionesSeleccionadas = \App\Models\Leccion::whereIn('id', $this->lecciones)->get();

        // Buscar horarios de otros profesores en el mismo recinto
        $horariosEnRecinto = collect();

        if ($this->tipoHorario === 'fijo') {
            // Para horarios fijos, buscar otros horarios fijos del mismo día
            $query = Horario::where('idRecinto', $this->idRecinto)
                ->where('user_id', '!=', $this->user_id)
                ->where('dia', $this->dia)
                ->where('tipoHorario', 1)
                ->where('condicion', 1);

            if ($horarioId) {
                $query->where('id', '!=', $horarioId);
            }

            $horariosEnRecinto = $query->with(['leccion', 'profesor'])->get();
        } else {
            // Para horarios temporales, buscar horarios fijos del día correspondiente y otros temporales de la misma fecha
            $diaSemana = date('l', strtotime($this->fecha));
            $diasEspanol = [
                'Monday' => 'Lunes',
                'Tuesday' => 'Martes', 
                'Wednesday' => 'Miércoles',
                'Thursday' => 'Jueves',
                'Friday' => 'Viernes',
                'Saturday' => 'Sábado',
                'Sunday' => 'Domingo'
            ];
            $diaEnEspanol = $diasEspanol[$diaSemana];

            // Horarios fijos del mismo día de la semana en el mismo recinto
            $queryFijos = Horario::where('idRecinto', $this->idRecinto)
                ->where('user_id', '!=', $this->user_id)
                ->where('dia', $diaEnEspanol)
                ->where('tipoHorario', 1)
                ->where('condicion', 1);

            // Otros horarios temporales de la misma fecha en el mismo recinto
            $queryTemporales = Horario::where('idRecinto', $this->idRecinto)
                ->where('user_id', '!=', $this->user_id)
                ->where('fecha', $this->fecha)
                ->where('tipoHorario', 0)
                ->where('condicion', 1);

            if ($horarioId) {
                $queryFijos->where('id', '!=', $horarioId);
                $queryTemporales->where('id', '!=', $horarioId);
            }

            $horariosFijos = $queryFijos->with(['leccion', 'profesor'])->get();
            $horariosTemporales = $queryTemporales->with(['leccion', 'profesor'])->get();

            $horariosEnRecinto = $horariosFijos->merge($horariosTemporales);
        }

        // Verificar conflictos de tiempo en el mismo recinto
        foreach ($horariosEnRecinto as $horarioExistente) {
            foreach ($horarioExistente->leccion as $leccionExistente) {
                foreach ($leccionesSeleccionadas as $leccionNueva) {
                    if ($this->hayConflictoHorario($leccionExistente, $leccionNueva)) {
                        $profesorExistente = $horarioExistente->profesor->name ?? 'Profesor desconocido';
                        $tipoExistente = $horarioExistente->tipoHorario == 1 ? 'fijo' : 'temporal';
                        $validator->errors()->add('lecciones', "Conflicto en el recinto: El profesor {$profesorExistente} ya tiene clase ({$tipoExistente}) en este recinto en " . $this->formatearRango($leccionExistente) . ", que coincide con " . $this->formatearRango($leccionNueva) . ".");
                        return; // Salir después del primer conflicto encontrado
                    }
                }
            }
        }
    }

    /**
     * Validar conflictos de secciones: La misma sección con la misma subárea no puede estar con el mismo profesor en el mismo recinto en horarios que coincidan
     * Valida tanto horarios fijos como temporales de manera cruzada
     */
    private function validateSameSubareaConflicts($validator, $horarioId = null)
    {
        if (!$this->has('lecciones') || empty($this->lecciones)) {
            return;
        }

        // Obtener las lecciones seleccionadas con sus horarios
        $leccionesSeleccionadas = \App\Models\Leccion::whereIn('id', $this->lecciones)->get();

        // Buscar horarios del mismo profesor en la misma subárea, misma sección y mismo recinto
        $horariosExistentes = collect();

        if ($this->tipoHorario === 'fijo') {
            // Para horarios fijos, buscar otros horarios fijos del mismo día Y horarios temporales del mismo día de la semana
            $queryFijos = Horario::where('idSubarea', $this->idSubarea)
                ->where('idSeccion', $this->idSeccion)
                ->where('idRecinto', $this->idRecinto)
                ->where('user_id', $this->user_id)
                ->where('dia', $this->dia)
                ->where('tipoHorario', 1)
                ->where('condicion', 1);

            $queryTemporales = Horario::where('idSubarea', $this->idSubarea)
                ->where('idSeccion', $this->idSeccion)
                ->where('idRecinto', $this->idRecinto)
                ->where('user_id', $this->user_id)
                ->where('tipoHorario', 0)
                ->where('condicion', 1)
                ->whereRaw('DAYNAME(fecha) = ?', [$this->dia === 'Lunes' ? 'Monday' : 
                    ($this->dia === 'Martes' ? 'Tuesday' :
                    ($this->dia === 'Miércoles' ? 'Wednesday' :
                    ($this->dia === 'Jueves' ? 'Thursday' :
                    ($this->dia === 'Viernes' ? 'Friday' :
                    ($this->dia === 'Sábado' ? 'Saturday' : 'Sunday')))))]);

            if ($horarioId) {
                $queryFijos->where('id', '!=', $horarioId);
                $queryTemporales->where('id', '!=', $horarioId);
            }

            $horariosFijos = $queryFijos->with(['leccion', 'seccion', 'subarea', 'recinto'])->get();
            $horariosTemporales = $queryTemporales->with(['leccion', 'seccion', 'subarea', 'recinto'])->get();

            $horariosExistentes = $horariosFijos->merge($horariosTemporales);
        } else {
            // Para horarios temporales, buscar horarios fijos del día correspondiente y otros temporales de la misma fecha
            $diaSemana = date('l', strtotime($this->fecha));
            $diasEspanol = [
                'Monday' => 'Lunes',
                'Tuesday' => 'Martes', 
                'Wednesday' => 'Miércoles',
                'Thursday' => 'Jueves',
                'Friday' => 'Viernes',
                'Saturday' => 'Sábado',
                'Sunday' => 'Domingo'
            ];
            $diaEnEspanol = $diasEspanol[$diaSemana];

            $queryFijos = Horario::where('idSubarea', $this->idSubarea)
                ->where('idSeccion', $this->idSeccion)
                ->where('idRecinto', $this->idRecinto)
                ->where('user_id', $this->user_id)
                ->where('dia', $diaEnEspanol)
                ->where('tipoHorario', 1)
                ->where('condicion', 1);

            $queryTemporales = Horario::where('idSubarea', $this->idSubarea)
                ->where('idSeccion', $this->idSeccion)
                ->where('idRecinto', $this->idRecinto)
                ->where('user_id', $this->user_id)
                ->where('fecha', $this->fecha)
                ->where('tipoHorario', 0)
                ->where('condicion', 1);

            if ($horarioId) {
                $queryFijos->where('id', '!=', $horarioId);
                $queryTemporales->where('id', '!=', $horarioId);
            }

            $horariosFijos = $queryFijos->with(['leccion', 'seccion', 'subarea', 'recinto'])->get();
            $horariosTemporales = $queryTemporales->with(['leccion', 'seccion', 'subarea', 'recinto'])->get();

            $horariosExistentes = $horariosFijos->merge($horariosTemporales);
        }

        // Verificar conflictos de tiempo en la misma combinación
        foreach ($horariosExistentes as $horarioExistente) {
            foreach ($horarioExistente->leccion as $leccionExistente) {
                foreach ($leccionesSeleccionadas as $leccionNueva) {
                    if ($this->hayConflictoHorario($leccionExistente, $leccionNueva)) {
                        $seccionExistente = $horarioExistente->seccion->nombre ?? 'Sección desconocida';
                        $subareaExistente = $horarioExistente->subarea->nombre ?? 'Subárea desconocida';
                        $recintoExistente = $horarioExistente->recinto->nombre ?? 'Recinto desconocido';
                        $tipoExistente = $horarioExistente->tipoHorario == 1 ? 'fijo' : 'temporal';
                        $tipoNuevo = $this->tipoHorario === 'fijo' ? 'fijo' : 'temporal';
                        
                        $validator->errors()->add('lecciones', 
                            "Ya tienes asignada la sección {$seccionExistente} de {$subareaExistente} en el recinto {$recintoExistente} ({$tipoExistente}) en " . $this->formatearRango($leccionExistente) . ", que coincide con la lección {$tipoNuevo} " . $this->formatearRango($leccionNueva) . "."
                        );
                        return; // Salir después del primer conflicto encontrado
                    }
                }
            }
        }
    }

    /**
     * Validar que las lecciones seleccionadas sean consecutivas en tiempo
     */
    function validateConsecutiveLecciones($lecciones, Validator $validator)
    {
        for ($i = 0; $i < count($lecciones) - 1; $i++) {
            $leccionActual    = $lecciones[$i];
            $leccionSiguiente = $lecciones[$i + 1];
            $horaFinalActual = $this->normalizarHora((string)$leccionActual->hora_final, $leccionActual->hora_final_periodo ?? null);
            $horaInicioSig   = $this->normalizarHora((string)$leccionSiguiente->hora_inicio, $leccionSiguiente->hora_inicio_periodo ?? null);

            if ($horaInicioSig->lessThan($horaFinalActual) && $horaFinalActual->diffInHours($horaInicioSig) > 6) {
                $horaInicioSig = $horaInicioSig->copy()->addDay();
            }

            $diferencia = $horaFinalActual->diffInMinutes($horaInicioSig, false);

            if ($diferencia < 0 || $diferencia > 20) {
                $validator->errors()->add('lecciones',
                    "Las lecciones deben ser consecutivas (máximo 20 minutos). Bloque {$leccionActual->hora_inicio} " . ($leccionActual->hora_inicio_periodo ?? '') . " - {$leccionActual->hora_final} " . ($leccionActual->hora_final_periodo ?? '') .
                    " no es consecutivo con {$leccionSiguiente->hora_inicio} " . ($leccionSiguiente->hora_inicio_periodo ?? '') . " - {$leccionSiguiente->hora_final} " . ($leccionSiguiente->hora_final_periodo ?? '') . "."
                );
                return;
            }
        }
    }

    /**
     * Validar que la misma sección de la misma especialidad no tenga conflictos:
     * 1. Múltiples profesores en horarios coincidentes
     * 2. Mismo profesor con horarios fijos y temporales del mismo día para la misma sección
     */
    private function validateSameSectionSpecialtyConflicts($validator, $horarioId = null)
    {
        if (!$this->has('lecciones') || empty($this->lecciones)) {
            return;
        }

        // Obtener las lecciones seleccionadas con sus horarios
        $leccionesSeleccionadas = \App\Models\Leccion::whereIn('id', $this->lecciones)->get();

        // Buscar TODOS los horarios existentes para la misma sección y especialidad
        $horariosExistentes = collect();

        if ($this->tipoHorario === 'fijo') {
            // Para horarios fijos, buscar otros horarios fijos del mismo día
            $query = Horario::where('idSubarea', $this->idSubarea)
                ->where('idSeccion', $this->idSeccion)
                ->where('dia', $this->dia)
                ->where('tipoHorario', 1)
                ->where('condicion', 1);

            if ($horarioId) {
                $query->where('id', '!=', $horarioId);
            }

            $horariosExistentes = $query->with(['leccion', 'profesor', 'seccion', 'subarea'])->get();
        } else {
            // Para horarios temporales, buscar horarios fijos del día correspondiente y otros temporales de la misma fecha
            $diaSemana = date('l', strtotime($this->fecha));
            $diasEspanol = [
                'Monday' => 'Lunes',
                'Tuesday' => 'Martes', 
                'Wednesday' => 'Miércoles',
                'Thursday' => 'Jueves',
                'Friday' => 'Viernes',
                'Saturday' => 'Sábado',
                'Sunday' => 'Domingo'
            ];
            $diaEnEspanol = $diasEspanol[$diaSemana];

            // Horarios fijos del mismo día de la semana con la misma sección y subárea
            $queryFijos = Horario::where('idSubarea', $this->idSubarea)
                ->where('idSeccion', $this->idSeccion)
                ->where('dia', $diaEnEspanol)
                ->where('tipoHorario', 1)
                ->where('condicion', 1);

            // Otros horarios temporales de la misma fecha con la misma sección y subárea
            $queryTemporales = Horario::where('idSubarea', $this->idSubarea)
                ->where('idSeccion', $this->idSeccion)
                ->where('fecha', $this->fecha)
                ->where('tipoHorario', 0)
                ->where('condicion', 1);

            if ($horarioId) {
                $queryFijos->where('id', '!=', $horarioId);
                $queryTemporales->where('id', '!=', $horarioId);
            }

            $horariosFijos = $queryFijos->with(['leccion', 'profesor', 'seccion', 'subarea'])->get();
            $horariosTemporales = $queryTemporales->with(['leccion', 'profesor', 'seccion', 'subarea'])->get();

            $horariosExistentes = $horariosFijos->merge($horariosTemporales);
        }

        // Verificar conflictos para cada horario existente
        foreach ($horariosExistentes as $horarioExistente) {
            $esMismoProfesor = $horarioExistente->user_id == $this->user_id;
            $tipoExistente = $horarioExistente->tipoHorario == 1 ? 'fijo' : 'temporal';
            $tipoNuevo = $this->tipoHorario === 'fijo' ? 'fijo' : 'temporal';

            // CASO 1: Mismo profesor, misma sección, mismo día, pero diferente tipo (fijo vs temporal)
            if ($esMismoProfesor && $tipoExistente !== $tipoNuevo) {
                $seccionExistente = $horarioExistente->seccion->nombre ?? 'Sección desconocida';
                $subareaExistente = $horarioExistente->subarea->nombre ?? 'Subárea desconocida';
                
                $validator->errors()->add('general', 
                    "No puedes asignar la misma sección {$seccionExistente} de {$subareaExistente} en un horario {$tipoNuevo} cuando ya tienes un horario {$tipoExistente} para esta sección y especialidad el mismo día."
                );
                return;
            }

            // CASO 2: Verificar conflictos de horarios (tanto para mismo profesor como para diferentes)
            foreach ($horarioExistente->leccion as $leccionExistente) {
                foreach ($leccionesSeleccionadas as $leccionNueva) {
                    if ($this->hayConflictoHorario($leccionExistente, $leccionNueva)) {
                        $profesorExistente = $horarioExistente->profesor->name ?? 'Profesor desconocido';
                        $seccionExistente = $horarioExistente->seccion->nombre ?? 'Sección desconocida';
                        $subareaExistente = $horarioExistente->subarea->nombre ?? 'Subárea desconocida';
                        
                        if ($esMismoProfesor) {
                            // Mismo profesor con conflicto de horarios
                            $validator->errors()->add('lecciones', 
                                "Ya tienes asignada la sección {$seccionExistente} de {$subareaExistente} ({$tipoExistente}) en " . $this->formatearRango($leccionExistente) . ", que coincide con " . $this->formatearRango($leccionNueva) . "."
                            );
                        } else {
                            // Diferentes profesores con conflicto de horarios
                            $validator->errors()->add('lecciones', 
                                "Conflicto de sección: La sección {$seccionExistente} de {$subareaExistente} ya está asignada al profesor {$profesorExistente} ({$tipoExistente}) en " . $this->formatearRango($leccionExistente) . ", que coincide con " . $this->formatearRango($leccionNueva) . "."
                            );
                        }
                        return; // Salir después del primer conflicto encontrado
                    }
                }
            }
        }
    }

    /**
     * Validar conflictos entre horarios temporales y fijos
     * Funciona para ambos tipos: fijos vs temporales y temporales vs fijos
     */
    private function validateTemporalFixedConflicts($validator, $horarioId = null)
    {
        if (!$this->has('lecciones') || empty($this->lecciones)) {
            return;
        }

        // Obtener las lecciones seleccionadas
        $leccionesSeleccionadas = \App\Models\Leccion::whereIn('id', $this->lecciones)->get();

        $horariosFijos = collect();
        $horariosTemporales = collect();

        if ($this->tipoHorario === 'fijo') {
            // Si estamos editando un horario fijo, buscar horarios temporales que coincidan con este día de la semana
            $query = Horario::where('user_id', $this->user_id)
                ->where('tipoHorario', 0)
                ->where('condicion', 1)
                ->whereRaw('DAYNAME(fecha) = ?', [$this->dia === 'Lunes' ? 'Monday' : 
                    ($this->dia === 'Martes' ? 'Tuesday' :
                    ($this->dia === 'Miércoles' ? 'Wednesday' :
                    ($this->dia === 'Jueves' ? 'Thursday' :
                    ($this->dia === 'Viernes' ? 'Friday' :
                    ($this->dia === 'Sábado' ? 'Saturday' : 'Sunday')))))]);

            if ($horarioId) {
                $query->where('id', '!=', $horarioId);
            }

            $horariosTemporales = $query->with(['leccion', 'recinto'])->get();
        } else {
            // Si estamos editando un horario temporal, buscar horarios fijos del día correspondiente
            $diaSemana = date('l', strtotime($this->fecha));
            $diasEspanol = [
                'Monday' => 'Lunes',
                'Tuesday' => 'Martes', 
                'Wednesday' => 'Miércoles',
                'Thursday' => 'Jueves',
                'Friday' => 'Viernes',
                'Saturday' => 'Sábado',
                'Sunday' => 'Domingo'
            ];
            $diaEnEspanol = $diasEspanol[$diaSemana];

            // Buscar horarios fijos del mismo profesor en el mismo día de la semana
            $queryFijos = Horario::where('user_id', $this->user_id)
                ->where('dia', $diaEnEspanol)
                ->where('tipoHorario', 1)
                ->where('condicion', 1);

            // Buscar otros horarios temporales del mismo profesor en la misma fecha
            $queryTemporales = Horario::where('user_id', $this->user_id)
                ->where('fecha', $this->fecha)
                ->where('tipoHorario', 0)
                ->where('condicion', 1);

            // Excluir el horario actual si estamos editando
            if ($horarioId) {
                $queryFijos->where('id', '!=', $horarioId);
                $queryTemporales->where('id', '!=', $horarioId);
            }

            $horariosFijos = $queryFijos->with(['leccion', 'recinto'])->get();
            $horariosTemporales = $queryTemporales->with(['leccion', 'recinto'])->get();
        }

        $todosLosHorarios = $horariosFijos->merge($horariosTemporales);

        // Verificar conflictos con ambos tipos de horarios
        foreach ($todosLosHorarios as $horarioExistente) {
            foreach ($horarioExistente->leccion as $leccionExistente) {
                foreach ($leccionesSeleccionadas as $leccionNueva) {
                    if ($this->hayConflictoHorario($leccionExistente, $leccionNueva)) {
                        $recintoExistente = $horarioExistente->recinto->nombre ?? 'Recinto desconocido';
                        $tipoExistente = $horarioExistente->tipoHorario == 1 ? 'fijo' : 'temporal';
                        $tipoNuevo = $this->tipoHorario === 'fijo' ? 'fijo' : 'temporal';
                        
                        $validator->errors()->add('lecciones', 
                            "Conflicto de horario: Ya tienes un horario {$tipoExistente} en {$recintoExistente} en " . $this->formatearRango($leccionExistente) . ", que coincide con la lección {$tipoNuevo} " . $this->formatearRango($leccionNueva) . "."
                        );
                        return; // Salir después del primer conflicto encontrado
                    }
                }
            }
        }
    }

    public function messages(): array
    {
        return [
            'idRecinto.required' => 'El recinto es obligatorio.',
            'idRecinto.integer' => 'El recinto debe ser un valor válido.',
            'idRecinto.exists' => 'El recinto seleccionado no es válido.',
            
            'idSubarea.required' => 'La subárea es obligatoria.',
            'idSubarea.integer' => 'La subárea debe ser un valor válido.',
            'idSubarea.exists' => 'La subárea seleccionada no es válida.',
            
            'idSeccion.required' => 'La sección es obligatoria.',
            'idSeccion.integer' => 'La sección debe ser un valor válido.',
            'idSeccion.exists' => 'La sección seleccionada no es válida.',
            
            'user_id.required' => 'El profesor es obligatorio.',
            'user_id.integer' => 'El profesor debe ser un valor válido.',
            'user_id.exists' => 'El profesor seleccionado no es válido.',
            
            'tipoHorario.required' => 'El tipo de horario es obligatorio.',
            'tipoHorario.string' => 'El tipo de horario debe ser un texto válido.',
            'tipoHorario.in' => 'El tipo de horario debe ser fijo o temporal.',
            
            'fecha.date' => 'La fecha debe tener un formato válido.',
            'fecha.required_if' => 'La fecha es obligatoria para horarios temporales.',
            
            'dia.string' => 'El día debe ser un texto válido.',
            'dia.required_if' => 'El día es obligatorio para horarios fijos.',
            'dia.in' => 'El día seleccionado no es válido.',
            
            'lecciones.required' => 'Debe seleccionar al menos una lección.',
            'lecciones.array' => 'Las lecciones deben ser un listado válido.',
            'lecciones.min' => 'Debe seleccionar al menos una lección.',
            'lecciones.*.integer' => 'Las lecciones seleccionadas deben ser válidas.',
            'lecciones.*.exists' => 'Una o más lecciones seleccionadas no son válidas.',
        ];
    }
}