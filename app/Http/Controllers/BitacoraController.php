<?php

namespace App\Http\Controllers;
use App\Models\Bitacora;
use App\Models\Recinto;
use App\Models\Seccione;
use App\Models\Subarea;
use App\Models\Horario;
use App\Models\Evento;
use App\Models\Leccion;
use App\Models\User;
use App\Models\Profesor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BitacoraController extends Controller
{
    //Display a listing of the resource.*/
    public function index(Request $request)
    {
        // Verificar permiso para ver bitácoras
        if (!auth()->user()->can('view_bitacoras')) {
            abort(403, 'No tienes permisos para acceder a las bitácoras.');
        }

        $user = Auth::user();
        
        // Debug: verificar usuario y roles
        \Log::info('BitacoraController - Usuario: ' . $user->name);
        \Log::info('BitacoraController - Roles: ' . $user->roles->pluck('name')->join(', '));
        
        // Verificar si es administrador (probamos diferentes variaciones)
        if ($user->hasRole('Administrador') || $user->hasRole('administrador') || $user->hasRole('admin') || $user->hasRole('superadmin')) {
            \Log::info('BitacoraController - Usuario es Administrador');
            return $this->indexAdmin($request);
        }
        
        // Verificar si es profesor
        $profesor = Profesor::where('usuario_id', $user->id)->first();
        if ($profesor) {
            \Log::info('BitacoraController - Usuario es Profesor, ID: ' . $profesor->id);
            return $this->indexProfesor($request, $profesor);
        }
        
        // TEMPORAL: Si tiene cualquier rol, permitir acceso como admin para debug
        if ($user->roles->count() > 0) {
            \Log::info('BitacoraController - Permitiendo acceso temporal para debug');
            return $this->indexAdmin($request);
        }
        
        \Log::warning('BitacoraController - Usuario sin permisos');
        return redirect()->back()->with('error', 'No tienes permisos para acceder a esta sección.');
    }
    
    /**
     * Vista de bitácoras para administrador
     */
    private function indexAdmin(Request $request)
    {
        \Log::info('BitacoraController - Ejecutando indexAdmin');
        
        // Obtener todas las bitácoras para estadísticas
        $todasLasBitacoras = Bitacora::with(['recinto'])->get();
        
        // Inicializar query base para la lista filtrada
        $query = Bitacora::with(['recinto', 'evento' => function($q) { 
            $q->where('condicion', 1)->with('usuario'); 
        }]);
        
        // Aplicar filtro de búsqueda por recinto si existe
        if ($request->has('busquedaBitacora') && $request->busquedaBitacora) {
            $query->whereHas('recinto', function($q) use ($request) {
                $q->where('nombre', 'like', '%' . $request->busquedaBitacora . '%');
            });
        }
        
        // Aplicar filtro de estado (activas/inactivas)
        if ($request->has('inactivas') && $request->inactivas == '1') {
            $query->where('estado', 0); // Bitácoras inactivas
        } else {
            $query->where('estado', 1); // Bitácoras activas por defecto
        }
        
        $bitacoras = $query->orderBy('created_at', 'desc')->get();
        
        \Log::info('BitacoraController - Bitacoras encontradas: ' . $bitacoras->count());
        $dashboardRoute = route('Dashboard.indexAdmin');
        return view('admin.bitacora.index', compact('bitacoras', 'todasLasBitacoras', 'dashboardRoute'));
    }
    
        /**
     * Vista de bitácoras para profesor
     */
    private function indexProfesor(Request $request, $profesor)
    {
        \Log::info('BitacoraController - Ejecutando indexProfesor para profesor ID: ' . $profesor->id);
        
        try {
            // Obtener los IDs de recintos asignados al profesor a través de sus horarios
            $recintoIds = Horario::where('user_id', $profesor->usuario_id)
                ->whereNotNull('idRecinto')
                ->pluck('idRecinto')
                ->unique()
                ->toArray();
            
            \Log::info('BitacoraController - Recintos asignados al profesor: ' . implode(', ', $recintoIds));
            
            // Filtrar bitácoras solo de los recintos asignados al profesor
            if (!empty($recintoIds)) {
                // Obtener todas las bitácoras del profesor para estadísticas
                $todasLasBitacoras = Bitacora::with(['recinto'])
                    ->whereIn('id_recinto', $recintoIds)
                    ->get();
                
                $query = Bitacora::with(['recinto', 'evento' => function($q) { 
                    $q->where('condicion', 1)->with('usuario'); 
                }])
                    ->whereIn('id_recinto', $recintoIds);
                
                // Aplicar filtro de búsqueda por recinto si existe
                if ($request->has('busquedaBitacora') && $request->busquedaBitacora) {
                    $query->whereHas('recinto', function($q) use ($request) {
                        $q->where('nombre', 'like', '%' . $request->busquedaBitacora . '%');
                    });
                }
                
                // Aplicar filtro de estado (activas/inactivas)
                if ($request->has('inactivas') && $request->inactivas == '1') {
                    $query->where('estado', 0); // Bitácoras inactivas
                } else {
                    $query->where('estado', 1); // Bitácoras activas por defecto
                }
                
                $bitacoras = $query->orderBy('created_at', 'desc')->get();
            } else {
                // Si el profesor no tiene recintos asignados, mostrar mensaje vacío
                $bitacoras = collect();
                $todasLasBitacoras = collect();
                \Log::info('BitacoraController - Profesor no tiene recintos asignados');
            }
            
            \Log::info('BitacoraController - Bitacoras encontradas para profesor: ' . $bitacoras->count());
            
            return view('profesor.bitacora.index', compact('bitacoras', 'todasLasBitacoras', 'profesor'));
        } catch (\Exception $e) {
            \Log::error('BitacoraController - Error en indexProfesor: ' . $e->getMessage());
            
            // Si hay error, crear una colección vacía para evitar errores en la vista
            $bitacoras = collect();
            return view('profesor.bitacora.index', compact('bitacoras', 'profesor'));
        }
    }

    //Show the form for creating a new resource.*/
    public function create(){
        // Solo administradores pueden crear bitácoras manualmente
        if (!Auth::user()->hasRole('Administrador')) {
            return redirect()->back()->with('error', 'No tienes permisos para esta acción.');
        }
        
        $recintos = Recinto::where('condicion', 1)->get();
        $secciones = Seccione::where('condicion', 1)->get();
        $subareas = Subarea::where('condicion', 1)->get();
        $horarios = DB::table('horarios')->get();
        
        return view('admin.bitacora.create', compact('recintos', 'secciones', 'subareas', 'horarios'));
    }

    //Store a newly created resource in storage.*/
    public function store(Request $request){
        if (!Auth::user()->hasRole('Administrador')) {
            return redirect()->back()->with('error', 'No tienes permisos para esta acción.');
        }

        $request->validate([
            'id_recinto' => 'required|exists:recinto,id',
            'id_seccion' => 'required|exists:seccione,id',
            'id_subarea' => 'required|exists:subarea,id',
            'id_horario' => 'required|exists:horarios,id',
            'condicion' => 'required|in:0,1'
        ]);

        DB::table('bitacoras')->insert([
            'id_recinto' => $request->id_recinto,
            'id_seccion' => $request->id_seccion,
            'id_subarea' => $request->id_subarea,
            'id_horario' => $request->id_horario,
            'id_horario_leccion' => 1, // Valor por defecto
            'fecha' => now()->toDateString(),
            'hora_envio' => now()->toTimeString(),
            'condicion' => $request->condicion,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        return redirect()->route('bitacora.index')->with('success', 'Bitácora creada exitosamente.');
    }

    //Display the specified resource.*/
    public function show(string $id){
    
            $bitacora = DB::table('bitacoras')
                ->join('recinto', 'bitacoras.id_recinto', '=', 'recinto.id')
                ->join('seccione', 'bitacoras.id_seccion', '=', 'seccione.id')
                ->join('subarea', 'bitacoras.id_subarea', '=', 'subarea.id')
                ->join('horarios', 'bitacoras.id_horario', '=', 'horarios.id')
                ->leftJoin('llave', 'recinto.llave_id', '=', 'llave.id')
                ->where('bitacoras.id', $id)
                ->select(
                    'bitacoras.*',
                    'recinto.nombre as recinto_nombre',
                    'seccione.nombre as seccion_nombre',
                    'subarea.nombre as subarea_nombre',
                    'horarios.nombre as horario_nombre',
                    'llave.nombre as llave_nombre',
                    'llave.estado as llave_estado'
                )
                ->first();

            if (!$bitacora) {
                return redirect()->back()->with('error', 'Bitácora no encontrada.');
            }

            // Determinar la vista según el rol
            $view = Auth::user()->hasRole('Administrador') ? 'admin.bitacora.show' : 'profesor.bitacora.show';
            
            return view($view, compact('bitacora'));
    }

    
     
    //Show the form for editing the specified resource.*/
    public function edit(string $id){
        if (!Auth::user()->hasRole('Administrador')) {
            return redirect()->back()->with('error', 'No tienes permisos para esta acción.');
        }

        $bitacora = DB::table('bitacoras')->where('id', $id)->first();
        if (!$bitacora) {
            return redirect()->back()->with('error', 'Bitácora no encontrada.');
        }

        $recintos = Recinto::where('condicion', 1)->get();
        $secciones = Seccione::where('condicion', 1)->get();
        $subareas = Subarea::where('condicion', 1)->get();
        $horarios = DB::table('horarios')->get();

        return view('admin.bitacora.edit', compact('bitacora', 'recintos', 'secciones', 'subareas', 'horarios'));
    }

    //Update the specified resource in storage.*/
    public function update(Request $request, string $id){
        if (!Auth::user()->hasRole('Administrador')) {
            return redirect()->back()->with('error', 'No tienes permisos para esta acción.');
        }

        $request->validate([
            'id_recinto' => 'required|exists:recinto,id',
            'id_seccion' => 'required|exists:seccione,id',
            'id_subarea' => 'required|exists:subarea,id',
            'id_horario' => 'required|exists:horarios,id',
            'condicion' => 'required|in:0,1'
        ]);

        DB::table('bitacoras')->where('id', $id)->update([
            'id_recinto' => $request->id_recinto,
            'id_seccion' => $request->id_seccion,
            'id_subarea' => $request->id_subarea,
            'id_horario' => $request->id_horario,
            'condicion' => $request->condicion,
            'updated_at' => now()
        ]);

        return redirect()->route('bitacora.index')->with('success', 'Bitácora actualizada exitosamente.');
    }

    /**Remove the specified resource from storage.*/
    public function destroy(string $id){
        if (!Auth::user()->hasRole('Administrador')) {
            return redirect()->back()->with('error', 'No tienes permisos para esta acción.');
        }

        DB::table('bitacoras')->where('id', $id)->delete();
        
        return redirect()->route('bitacora.index')->with('success', 'Bitácora eliminada exitosamente.');
    }
}