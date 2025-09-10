<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateEventoRequest;
use App\Models\Institucion;
use App\Models\Institucione;
use App\Models\Profesor;
use App\Models\Bitacora;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\View\View;
use App\Http\Requests\StoreEventoRequest;
use App\Models\Evento;
use App\Models\Seccione;
use App\Models\Subarea;
use App\Models\Horario;
use Illuminate\Http\Request;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Log;

class EventoController extends Controller
{

    public function index(Request $request)
    {
        $query = Evento::with([
            'bitacora',
            'usuario',
            'seccion',
            'subarea.especialidad',
            'horario.recinto.institucion'
        ]);

        

        // Aplicar filtro por estado si se proporciona
        if ($request->has('estado') && $request->estado !== '') {
            $query->where('estado', $request->estado);
        }

        // Aplicar filtro de búsqueda si se proporciona
        if ($request->has('busqueda') && $request->busqueda !== '') {
            $busqueda = $request->busqueda;
            $query->where(function ($q) use ($busqueda) {
                $q->whereHas('usuario', function ($userQuery) use ($busqueda) {
                    $userQuery->where('name', 'LIKE', "%{$busqueda}%");
                })
                    ->orWhereHas('horario.recinto', function ($recintoQuery) use ($busqueda) {
                        $recintoQuery->where('nombre', 'LIKE', "%{$busqueda}%");
                    })
                    ->orWhereHas('horario.recinto.institucion', function ($institucionQuery) use ($busqueda) {
                        $institucionQuery->where('nombre', 'LIKE', "%{$busqueda}%");
                    })
                    ->orWhere('observacion', 'LIKE', "%{$busqueda}%")
                    ->orWhere('prioridad', 'LIKE', "%{$busqueda}%");
            });
        }

        // Aplicar ordenamiento
        $orden = $request->get('orden', 'desc');
        if ($orden === 'asc') {
            $query->orderBy('created_at', 'asc');
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $eventos = $query->get();

        // Asegurar que todos los eventos tengan un estado válido
        foreach ($eventos as $evento) {
            if (empty($evento->estado) || is_null($evento->estado)) {
                $evento->estado = 'en_espera';
                $evento->save();
            }
        }

        if ($request->ajax()) {
            try {
                $view = view('Evento.partials.eventos-lista', compact('eventos'))->render();
                return response()->json([
                    'success' => true,
                    'html' => $view
                ]);
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al cargar los eventos',
                    'error' => $e->getMessage()
                ], 500);
            }
        }

        return view('Evento.index', compact('eventos'));
    }

    public function index_soporte(Request $request)
    {
        $query = Evento::with([
            'bitacora',
            'usuario',
            'seccion',
            'subarea.especialidad',
            'horario.recinto.institucion'
        ])->where('condicion', 1);

        // Aplicar filtro por recinto si se proporciona
        if ($request->has('recinto') && $request->recinto !== '') {
            $query->whereHas('horario.recinto', function ($recintoQuery) use ($request) {
                $recintoQuery->where('id', $request->recinto);
            });
        }

        // Aplicar filtro de búsqueda si se proporciona
        if ($request->has('busqueda') && $request->busqueda !== '') {
            $busqueda = $request->busqueda;
            $query->where(function ($q) use ($busqueda) {
                $q->whereHas('usuario', function ($userQuery) use ($busqueda) {
                    $userQuery->where('name', 'LIKE', "%{$busqueda}%");
                })
                    ->orWhereHas('horario.recinto', function ($recintoQuery) use ($busqueda) {
                        $recintoQuery->where('nombre', 'LIKE', "%{$busqueda}%");
                    })
                    ->orWhereHas('horario.recinto.institucion', function ($institucionQuery) use ($busqueda) {
                        $institucionQuery->where('nombre', 'LIKE', "%{$busqueda}%");
                    })
                    ->orWhere('observacion', 'LIKE', "%{$busqueda}%");
            });
        }

        // Aplicar ordenamiento
        if ($request->has('orden') && $request->orden === 'asc') {
            $query->orderBy('created_at', 'asc');
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $eventos = $query->get();

        // Asegurar que todos los eventos tengan un estado válido
        foreach ($eventos as $evento) {
            if (empty($evento->estado) || is_null($evento->estado)) {
                $evento->estado = 'en_espera';
                $evento->save();
            }
        }

        // Obtener todos los recintos para el dropdown (sin filtro de condición por ahora)
        $recintos = \App\Models\Recinto::orderBy('nombre')->get();

        // Debug: verificar si se están cargando los recintos
        \Log::info('Recintos cargados para soporte: ' . $recintos->count());

        if ($request->ajax()) {
            try {
                $view = view('Evento.partials.eventos-lista-soporte', compact('eventos'))->render();
                return response()->json([
                    'success' => true,
                    'html' => $view
                ]);
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al cargar los eventos',
                    'error' => $e->getMessage()
                ], 500);
            }
        }

        return view('Evento.index_soporte', compact('eventos', 'recintos'));
    }

    public function index_profesor(Request $request)
    {
        $eventos = Evento::with([
            'bitacora',
            'usuario',
            'seccion',
            'institucion',
            'subarea.especialidad',
            'horario.recinto.institucion'
        ])
            ->where('user_id', auth()->id()) // Solo eventos del usuario autenticado
            ->orderBy('created_at', 'desc')
            ->get();


        $bitacoras = Bitacora::all();

        $seccione = Seccione::all();
        $subareas = Subarea::all();

        // Filtrar solo los horarios del profesor logueado con relaciones
        $horarios = Horario::with(['recinto', 'subarea', 'seccion', 'leccion'])
            ->where('user_id', auth()->id())
            ->get();

        // Obtener todas las lecciones disponibles
        $lecciones = $horarios->flatMap(function ($horario) {
            return $horario->leccion->map(function ($leccion) use ($horario) {
                $leccion->horario_data = $horario;
                // Obtener la información de tiempo desde la tabla pivote
                $horarioLeccion = \DB::table('horario_leccion')
                    ->where('idHorario', $horario->id)
                    ->where('idLeccion', $leccion->id)
                    ->first();

                // Debug: Log the structure of horarioLeccion
                \Log::info('HorarioLeccion data:', [
                    'horario_id' => $horario->id,
                    'leccion_id' => $leccion->id,
                    'horario_leccion' => $horarioLeccion ? (array) $horarioLeccion : null
                ]);

                // Intentar diferentes nombres de columnas
                $leccion->hora_inicio = $horarioLeccion->horaInicio ??
                    $horarioLeccion->hora_inicio ??
                    $leccion->hora_inicio ?? null;

                $leccion->hora_fin = $horarioLeccion->horaFin ??
                    $horarioLeccion->hora_fin ??
                    $horarioLeccion->hora_final ??
                    $leccion->hora_final ?? null;

                return $leccion;
            });
        })->unique('id');

        // Obtener datos del horario seleccionado si existe
        $horarioSeleccionado = null;
        if ($request->filled('leccion')) {
            $horarioSeleccionado = $horarios->where('id', $request->get('leccion'))->first();
        }

        // Obtener la fecha del primer horario o horario seleccionado
        $fecha = $horarioSeleccionado ? $horarioSeleccionado->fecha : ($horarios->first() ? $horarios->first()->fecha : null);

        // Obtener datos dinámicos basados en la selección
        $seccion = $horarioSeleccionado && $horarioSeleccionado->seccion ? $horarioSeleccionado->seccion->nombre : '';
        $subarea = $horarioSeleccionado && $horarioSeleccionado->subarea ? $horarioSeleccionado->subarea->nombre : '';
        $recinto = $horarioSeleccionado && $horarioSeleccionado->recinto ? $horarioSeleccionado->recinto->nombre : '';

        // Obtener la bitácora asociada al recinto seleccionado
        $bitacoraId = $horarioSeleccionado && $horarioSeleccionado->recinto ?
            Bitacora::where('recinto_id', $horarioSeleccionado->recinto->id)->value('id') : null;


        if ($request->ajax()) {
            try {
                $html = view('Evento.index.soporte', compact('eventos'))->renderSections()['content'];
                return response()->json([
                    'success' => true,
                    'hasNewData' => true,
                    'html' => $html,
                    'timestamp' => $eventos->max('updated_at')
                ]);
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al cargar los eventos'
                ], 500);
            }
        }

        return view('Evento.index_profesor', compact(
            'eventos',
            'bitacoras',
            'seccione',
            'subareas',
            'horarios',
            'lecciones',
            'horarioSeleccionado',
            'fecha',
            'seccion',
            'subarea',
            'recinto',
            'bitacoraId'
        ));
    }

    //funcion de crear
    public function create(Request $request)
    {
        $eventos = Evento::with('bitacora', 'usuario', 'seccion', 'subarea', 'horario')->get();
        $bitacoras = Bitacora::all();
        $instituciones = Institucione::all();
        $seccione = Seccione::all();
        $subareas = Subarea::all();

        // Obtener todos los usuarios con rol profesor
        $profesores = User::whereHas('roles', function ($query) {
            $query->where('name', 'profesor');
        })->get();

        // Si se recibe id_bitacora, filtrar por el horario de esa bitácora
        $lecciones = collect();
        $horarioSeleccionado = null;
        $fecha = null;
        $seccion = '';
        $subarea = '';
        $recinto = '';
        $bitacoraId = null;
        $horarios = collect();

        if ($request->filled('id_bitacora')) {
            $bitacoraId = $request->get('id_bitacora');
            $bitacora = Bitacora::find($bitacoraId);
            if ($bitacora && $bitacora->id_recinto) {
                // Buscar el horario que tenga ese recinto
                $horarioSeleccionado = Horario::with(['recinto', 'subarea', 'seccion', 'leccion'])
                    ->where('idRecinto', $bitacora->id_recinto)
                    ->first();
                if ($horarioSeleccionado) {
                    $horarios = collect([$horarioSeleccionado]);
                    // Asignar horario_data a cada lección con información de tiempo
                    $lecciones = $horarioSeleccionado->leccion->map(function ($leccion) use ($horarioSeleccionado) {
                        $leccion->horario_data = $horarioSeleccionado;

                        // Obtener información de tiempo
                        $horarioLeccion = \DB::table('horario_leccion')
                            ->where('idHorario', $horarioSeleccionado->id)
                            ->where('idLeccion', $leccion->id)
                            ->first();

                        // Debug: Log the structure
                        \Log::info('HorarioLeccion create data:', [
                            'horario_id' => $horarioSeleccionado->id,
                            'leccion_id' => $leccion->id,
                            'horario_leccion' => $horarioLeccion ? (array) $horarioLeccion : null
                        ]);

                        // Intentar diferentes nombres de columnas
                        $leccion->hora_inicio = $horarioLeccion->horaInicio ??
                            $horarioLeccion->hora_inicio ??
                            $leccion->hora_inicio ?? null;

                        $leccion->hora_fin = $horarioLeccion->horaFin ??
                            $horarioLeccion->hora_fin ??
                            $horarioLeccion->hora_final ??
                            $leccion->hora_final ?? null;

                        return $leccion;
                    });
                    $fecha = $horarioSeleccionado->fecha;
                    $seccion = $horarioSeleccionado->seccion ? $horarioSeleccionado->seccion->nombre : '';
                    $subarea = $horarioSeleccionado->subarea ? $horarioSeleccionado->subarea->nombre : '';
                    $recinto = $horarioSeleccionado->recinto ? $horarioSeleccionado->recinto->nombre : '';
                }
            }
        }

        return view('Evento.create', compact(
            'eventos',
            'bitacoras',
            'profesores',
            'seccione',
            'subareas',
            'horarios',
            'fecha',
            'seccion',
            'subarea',
            'recinto',
            'horarioSeleccionado',
            'lecciones',
            'bitacoraId',
            'instituciones'
        ));
    }

    public function store(StoreEventoRequest $request)
    {
        // Recibimos el id de lección y el id de horario seleccionados
        $idLeccion = $request->id_leccion;
        $idHorario = $request->id_horario;
        $leccion = \App\Models\Leccion::find($idLeccion);
        $horario = Horario::with('recinto')->find($idHorario);
        if (!$leccion) {
            return back()->withErrors(['error' => 'La lección seleccionada no existe.']);
        }
        if (!$horario) {
            return back()->withErrors(['error' => 'El horario seleccionado no existe.']);
        }
        // Buscar el id de la fila pivote horario_leccion
        $idHorarioLeccion = \DB::table('horario_leccion')
            ->where('idHorario', $idHorario)
            ->where('idLeccion', $idLeccion)
            ->value('id');
        if (!$idHorarioLeccion) {
            return back()->withErrors(['error' => 'No existe relación entre el horario y la lección seleccionados.']);
        }
        DB::beginTransaction();
        try {
            $evento = new Evento();
            $bitacora = Bitacora::where('id_recinto', $horario->recinto->id)->first();
            if (!$bitacora) {
                throw new Exception('No se encontró una bitácora para el recinto de este horario.');
            }
            if ($bitacora->condicion == 0) {
                return back()->withErrors(['error' => 'La bitácora seleccionada no está activa.']);
            }
            $evento->id_bitacora = $bitacora->id;
            $evento->id_seccion = $request->id_seccion;
            $evento->id_subarea = $request->id_subarea;
            $evento->id_horario = $horario->id;
            $evento->id_institucion = $request->id_institucion;
            $evento->id_horario_leccion = $idHorarioLeccion;
            $evento->user_id = auth()->id();
            $evento->hora_envio = now()->format('H:i:s');
            $evento->fecha = now();
            $evento->observacion = $request->observacion;
            $evento->prioridad = $request->prioridad;
            $evento->confirmacion = false;
            $evento->condicion = 1;
            $evento->save();
            DB::commit();
            return redirect()->route('evento.index_profesor')
                ->with('success', 'Evento guardado correctamente.');
        } catch (Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Hubo un problema al guardar el evento. ' . $e->getMessage()]);
        }
    }

    public function cancelCreate()
    {
        // Método para manejar la cancelación del formulario
        return redirect()->back()->with('info', 'Registro de evento cancelado.');
    }

    //metodo editar
    public function edit(Evento $evento)
    {
        $bitacoras = Bitacora::all();
        $profesores = User::whereHas('roles', function ($query) {
            $query->where('name', 'profesor');
        })->get();

        $evento->load('bitacora', 'profesor');

        return view('Evento.edit', compact('evento', 'bitacoras', 'profesores'));
    }


    public function loadEventos(Request $request)
    {
        try {
            $eventos = Evento::with([
                'bitacora',
                'usuario',
                'seccion',
                'subarea.especialidad',
                'horario.recinto.institucion'
            ])
                ->orderBy('created_at', 'desc')
                ->get();

            // Get the latest update timestamp from all events
            $latestUpdate = $eventos->max('updated_at');
            $currentTimestamp = $request->query('timestamp');

            // Check if there are any changes
            $hasChanges = !$currentTimestamp || $latestUpdate > $currentTimestamp;

            if (!$hasChanges) {
                return response()->json([
                    'success' => true,
                    'hasNewData' => false
                ]);
            }

            $html = view('Evento.index', compact('eventos'))->renderSections()['content'];

            return response()->json([
                'success' => true,
                'hasNewData' => true,
                'html' => $html,
                'timestamp' => $latestUpdate
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar los eventos'
            ], 500);
        }
    }



    
    
    public function update(Request $request, $id)
    {
        try {
            Log::info('=== INICIO UPDATE EVENTO SOPORTE ===', [
                'evento_id' => $id,
                'request_data' => $request->all(),
                'is_json' => $request->isJson(),
                'content_type' => $request->header('Content-Type')
            ]);

            // Verificar si el evento existe
            $evento = Evento::find($id);
            
            if (!$evento) {
                Log::error('Evento no encontrado', ['id' => $id]);
                return response()->json([
                    'success' => false,
                    'message' => 'Evento no encontrado'
                ], 404);
            }

            // Validación específica para estado
            $rules = [];
            if ($request->has('estado')) {
                $rules['estado'] = 'required|in:en_espera,en_proceso,completado';
            }
            if ($request->has('prioridad')) {
                $rules['prioridad'] = 'in:alta,media,regular,baja';
            }
            if ($request->has('observacion')) {
                $rules['observacion'] = 'nullable|string|max:1000';
            }

            try {
                $validated = $request->validate($rules);
                Log::info('Validación exitosa:', $validated);
            } catch (\Illuminate\Validation\ValidationException $ve) {
                Log::error('Error de validación:', [
                    'errors' => $ve->errors(),
                    'input_data' => $request->all()
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Datos inválidos.',
                    'errors' => $ve->errors()
                ], 422);
            }

            // Actualizar los campos
            if (isset($validated['estado'])) {
                $evento->estado = $validated['estado'];
                Log::info('Estado actualizado a: ' . $validated['estado']);
            }

            if (isset($validated['prioridad'])) {
                $evento->prioridad = $validated['prioridad'];
                Log::info('Prioridad actualizada a: ' . $validated['prioridad']);
            }

            if (array_key_exists('observacion', $validated)) {
                $evento->observacion = $validated['observacion'];
                Log::info('Observación actualizada');
            }

            // Guardar cambios
            $saved = $evento->save();
            
            if (!$saved) {
                Log::error('Error al guardar evento');
                return response()->json([
                    'success' => false,
                    'message' => 'Error al guardar los cambios'
                ], 500);
            }

            Log::info('Evento actualizado exitosamente');

            return response()->json([
                'success' => true,
                'message' => 'Evento actualizado correctamente.',
                'data' => [
                    'id' => $evento->id,
                    'estado' => $evento->estado,
                    'prioridad' => $evento->prioridad,
                    'observacion' => $evento->observacion
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('=== ERROR EN UPDATE EVENTO ===', [
                'evento_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor'
            ], 500);
        }
    }

    public function destroy(string $id)
    {
        $message = '';
        $evento = Evento::find($id);
        if ($evento->condicion == 1) {
            Evento::where('id', $evento->id)
                ->update([
                    'condicion' => 0
                ]);
            $message = 'Evento eliminado';
        } else {
            Evento::where('id', $evento->id)
                ->update([
                    'condicion' => 1
                ]);
            $message = 'Evento restaurado';
        }
        return redirect()->route('evento.index_profesor')->with('success', $message);
    }

    // Add these two new methods
    public function loadEventosProfesor(Request $request)
    {
        try {
            $user = auth()->user();

            $eventos = Evento::with([
                'bitacora',
                'usuario',
                'seccion',
                'subarea.especialidad',
                'horario.recinto.institucion'
            ])
                ->where('user_id', $user->id)
                ->where('condicion', 1)
                ->orderBy('created_at', 'desc')
                ->get();

            $latestUpdate = $eventos->max('updated_at');
            $currentTimestamp = $request->query('timestamp');
            $hasChanges = !$currentTimestamp || $latestUpdate > $currentTimestamp;

            if (!$hasChanges) {
                return response()->json([
                    'success' => true,
                    'hasNewData' => false
                ]);
            }

            $html = view('Evento.index_profesor', compact('eventos'))->renderSections()['content'];

            return response()->json([
                'success' => true,
                'hasNewData' => true,
                'html' => $html,
                'timestamp' => $latestUpdate
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar los eventos'
            ], 500);
        }
    }

    public function loadEventosSoporte(Request $request)
    {
        try {
            $eventos = Evento::with([
                'bitacora',
                'usuario',
                'seccion',
                'subarea.especialidad',
                'horario.recinto.institucion'
            ])
                ->where('condicion', 1)
                ->orderBy('created_at', 'desc')
                ->get();

            $latestUpdate = $eventos->max('updated_at');
            $currentTimestamp = $request->query('timestamp');
            $hasChanges = !$currentTimestamp || $latestUpdate > $currentTimestamp;

            if (!$hasChanges) {
                return response()->json([
                    'success' => true,
                    'hasNewData' => false
                ]);
            }

            $html = view('Evento.index_soporte', compact('eventos'))->renderSections()['content'];

            return response()->json([
                'success' => true,
                'hasNewData' => true,
                'html' => $html,
                'timestamp' => $latestUpdate
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar los eventos'
            ], 500);
        }
    }

    public function getEventoDetails($id)
    {
        try {
            $evento = Evento::with([
                'usuario',
                'horario.recinto.institucion',
                'subarea.especialidad',
                'seccion'
            ])->findOrFail($id);

            return response()->json([
                'id' => $evento->id,
                'docente' => $evento->usuario->name ?? 'N/A',
                'institucion' => $evento->horario->recinto->institucion->nombre ?? '',
                'subarea' => $evento->subarea->nombre ?? '',
                'seccion' => $evento->seccion->nombre ?? '',
                'especialidad' => $evento->subarea->especialidad->nombre ?? '',
                'fecha' => \Carbon\Carbon::parse($evento->fecha)->format('d/m/Y'),
                'hora' => \Carbon\Carbon::parse($evento->hora_envio)->format('H:i'),
                'recinto' => $evento->horario->recinto->nombre ?? '',
                'prioridad' => ucfirst($evento->prioridad),
                'estado' => $evento->estado,
                'observaciones' => $evento->observacion ?? ''
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Evento no encontrado'
            ], 404);
        }
    }
}
