<?php

namespace App\Http\Controllers;

use DB;
use Illuminate\Http\Request;
use App\Models\Seccione; 
use App\Models\Especialidade;
use App\Models\Institucione;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\StoreSeccionRequest;
use App\Http\Requests\UpdateSeccionRequest;
use Exception; 
use Illuminate\Support\Facades\Schema;

class SeccionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Seccione::with(['especialidades' => function($query) {
            $query->where('especialidad.condicion', 1)
                  ->where('especialidad_seccion.condicion', 1);
        }]);
        
        // Búsqueda por nombre de sección
        if ($request->filled('busquedaSeccion')) {
            $busqueda = $request->busquedaSeccion;
            $query->where('nombre', 'like', "%{$busqueda}%");
        }
        
        $secciones = $query->get();


        // Detectar esquema disponible
        $hasPivot = Schema::hasTable('especialidad_institucion');
        $hasIdInstitucionColumn = Schema::hasColumn('especialidad', 'id_institucion');

        // Obtener todas las especialidades activas para el dropdown (old repoblado)
        $especialidades = Especialidade::where('condicion', 1)
            ->select($hasIdInstitucionColumn ? ['id', 'nombre', 'id_institucion'] : ['id', 'nombre'])
            ->get();

        // Obtener instituciones para el select de creación
        $instituciones = Institucione::select('id','nombre')->get();

        // Catálogo de especialidades por institución (soporta M2M si existe pivote)
        if ($hasPivot) {
            $rows = DB::table('especialidad_institucion')
                ->join('especialidad', 'especialidad.id', '=', 'especialidad_institucion.especialidad_id')
                ->select('especialidad_institucion.institucion_id as institucion_id', 'especialidad.id as id', 'especialidad.nombre as nombre')
                ->where('especialidad.condicion', 1)
                ->get();

            $especialidadesPorInstitucion = $rows
                ->groupBy('institucion_id')
                ->map(function ($items) {
                    return $items->map(function ($r) {
                        return ['id' => $r->id, 'nombre' => $r->nombre];
                    })->values();
                });
        } elseif ($hasIdInstitucionColumn) {
            // Fallback 1:N por columna id_institucion
            $especialidadesPorInstitucion = $especialidades
                ->groupBy('id_institucion')
                ->map(function ($items) {
                    return $items->map(function ($e) {
                        return ['id' => $e->id, 'nombre' => $e->nombre];
                    })->values();
                });
        } else {
            // Sin pivote ni columna id_institucion: no se puede construir el mapa, devolver vacío
            $especialidadesPorInstitucion = collect();
        }

        return view('Seccion.index', compact('secciones', 'especialidades', 'instituciones', 'especialidadesPorInstitucion'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $especialidades = Especialidade::all();
        return view('Seccion.create', compact('especialidades'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSeccionRequest $request)
    {
        try{
            DB::beginTransaction();
            
            // Crear la sección
            $data = $request->validated();
            $seccion = Seccione::create([
                'nombre' => $data['nombre'],
                'id_institucion' => $data['id_institucion'] ?? null,
            ]);
            
            // Asociar especialidades si existen
            if ($request->has('especialidades') && is_array($request->especialidades)) {
                $especialidades = array_filter($request->especialidades); // Filtrar valores vacíos
                if (!empty($especialidades)) {
                    $seccion->especialidades()->attach($especialidades, [
                        'condicion' => 1,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }
            }
            
            DB::commit();
            return redirect()->route('seccion.index')->with('success', 'Sección creada correctamente.');
        }
        catch(Exception $e){
            DB::rollBack();
            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => 'Hubo un problema al guardar la sección: ' . $e->getMessage()])
                ->with('modal_crear', true);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Seccione $seccion)
    {
        $especialidades = Especialidade::all();
        return view('Seccion.edit', compact('seccion', 'especialidades'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSeccionRequest $request, Seccione $seccion)
    {
        try {
            DB::beginTransaction();
            
            // Actualizar la sección
            $seccion->update([
                'nombre' => $request->nombre,
                'id_institucion' => $request->id_institucion,
            ]);
            
            // Sincronizar especialidades (súper simple)
            $especialidades = $request->get('especialidades', []);
            $seccion->especialidades()->sync($especialidades);
            
            DB::commit();
            return redirect()->route('seccion.index')->with('success', 'Sección actualizada correctamente.');
        } catch (Exception $e) {
            DB::rollBack();
            return redirect()->back()->withInput()->with('error', 'Error al actualizar sección: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $message='';
        $seccion=Seccione::find($id);
        if($seccion->condicion==1){
            Seccione::where('id', $seccion->id)
            ->update([
                'condicion' => 0
            ]);
            $message = 'Sección eliminada correctamente.';
        }
        else{
            Seccione::where('id', $seccion->id)
            ->update([
                'condicion' => 1
            ]);
            $message = 'Sección restaurada correctamente.';
        }
        return redirect()->route('seccion.index')->with('success', $message);
    }

    /**
     * Get especialidades for a specific seccion (API endpoint)
     */
    public function getEspecialidades($seccionId)
    {
        try {
            $seccion = Seccione::with(['especialidades' => function($query) {
                $query->where('especialidad.condicion', 1)
                      ->where('especialidad_seccion.condicion', 1);
            }])->find($seccionId);

            if (!$seccion) {
                return response()->json([], 404);
            }

            $especialidades = $seccion->especialidades->map(function($especialidad) {
                return [
                    'id' => $especialidad->id,
                    'nombre' => $especialidad->nombre
                ];
            });

            return response()->json($especialidades);
        } catch (Exception $e) {
            return response()->json(['error' => 'Error al cargar especialidades'], 500);
        }
    }
}
