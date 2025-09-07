<?php

namespace App\Http\Controllers;

use App\Models\Especialidade;
use App\Http\Requests\StoreEspecialidadRequest;
use App\Http\Requests\UpdateEspecialidadRequest;
use Illuminate\Support\Facades\DB;
use App\Models\Institucione;
use Illuminate\Http\Request;
use Exception;


class EspecialidadController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
    $query = Especialidade::with('instituciones');

        // Filtrar por activos/inactivos
        if ($request->query('inactivos')) {
            $query->where('condicion', 0);
        } elseif ($request->query('activos')) {
            $query->where('condicion', 1);
        } else {
            $query->where('condicion', 1);
        }

        // Búsqueda por nombre de especialidad o institución
        if ($request->filled('busquedaEspecialidad')) {
            $busqueda = $request->busquedaEspecialidad;
            $query->where(function($q) use ($busqueda) {
                $q->where('nombre', 'like', "%{$busqueda}%")
                  ->orWhereHas('instituciones', function($q2) use ($busqueda) {
                      $q2->where('nombre', 'like', "%{$busqueda}%");
                  });
            });
        }

    $especialidades = $query->get();
        $instituciones = Institucione::where('condicion', 1)->get();

        return view('Especialidad.index', compact('especialidades', 'instituciones'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreEspecialidadRequest $request)
    {
        try {
            DB::beginTransaction();
            
            // Crear la especialidad (solo campos del modelo)
            $especialidad = Especialidade::create($request->only(['nombre']));

            // Asociar instituciones si existen
            if ($request->has('instituciones') && is_array($request->instituciones)) {
                $institucionesIds = array_filter($request->instituciones); // Filtrar valores vacíos
                if (!empty($institucionesIds)) {
                    $pivotData = [];
                    foreach ($institucionesIds as $instId) {
                        $pivotData[$instId] = [
                            'condicion' => 1,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                    $especialidad->instituciones()->attach($pivotData);
                }
            }

            DB::commit();

            return redirect()->route('especialidad.index')
                ->with('success', 'Especialidad creada correctamente.');

        } catch (Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Hubo un problema al guardar la especialidad.'])
                ->withInput();
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
    public function edit(string $id)
    {
        $especialidad = Especialidade::findOrFail($id);
    $especialidades = Especialidade::with('instituciones')->get();
        $instituciones = Institucione::where('condicion', 1)->get();

        return view('Especialidad.index', compact('especialidades', 'instituciones', 'especialidad'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateEspecialidadRequest $request, Especialidade $especialidad)
    {
        try {
            DB::beginTransaction();

            // Actualizar la especialidad
            $especialidad->update($request->validated());

            // Sincronizar instituciones
            if ($request->has('instituciones') && is_array($request->instituciones)) {
                $instituciones = array_filter($request->instituciones); // Filtrar valores vacíos

                if (!empty($instituciones)) {
                    // Preparar datos para la tabla pivot con condición = 1
                    $pivotData = [];
                    foreach ($instituciones as $institucionId) {
                        $pivotData[$institucionId] = [
                            'condicion' => 1,
                            'created_at' => now(),
                            'updated_at' => now()
                        ];
                    }

                    // Sincronizar instituciones (elimina las anteriores y agrega las nuevas)
                    $especialidad->instituciones()->sync($pivotData);
                }
            } else {
                // Si no hay instituciones seleccionadas, desconectar todas
                $especialidad->instituciones()->sync([]);
            }
            
            DB::commit();
            return redirect()->route('especialidad.index')->with('success', 'Especialidad actualizada correctamente.');
        } catch (Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => 'Hubo un problema al actualizar la especialidad: ' . $e->getMessage()])
                ->with('modal_editar_id', $especialidad->id);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
     {
        $message = '';
        $especialidad = Especialidade::find($id);
        if ($especialidad->condicion == 1)
        {
            Especialidade::where('id',$especialidad->id)
            ->update(['condicion' => 0]);
            $message = 'Especialidad eliminada';
        } else {
            Especialidade::where('id',$especialidad->id)
            ->update(['condicion' => 1]);
            $message = 'Especialidad restaurada';
        }
        return redirect()->route('especialidad.index')->with('success', $message);
    }

    /**
     * Get all especialidades (API endpoint)
     */
    public function getAll()
    {
        try {
            $especialidades = Especialidade::where('condicion', 1)
                ->select('id', 'nombre')
                ->orderBy('nombre')
                ->get();

            return response()->json($especialidades);
        } catch (Exception $e) {
            return response()->json(['error' => 'Error al cargar especialidades'], 500);
        }
    }

    /**
     * Get especialidades by institucion (API endpoint)
     */
    public function getByInstitucion($institucionId)
    {
        try {
            // Verificar si existe tabla pivot
            $hasPivot = \Schema::hasTable('especialidad_institucion');
            $hasIdInstitucionColumn = \Schema::hasColumn('especialidad', 'id_institucion');

            if ($hasPivot) {
                // Usar tabla pivot M2M
                $especialidades = DB::table('especialidad_institucion')
                    ->join('especialidad', 'especialidad.id', '=', 'especialidad_institucion.especialidad_id')
                    ->where('especialidad_institucion.institucion_id', $institucionId)
                    ->where('especialidad.condicion', 1)
                    ->select('especialidad.id', 'especialidad.nombre')
                    ->get();
            } elseif ($hasIdInstitucionColumn) {
                // Usar columna id_institucion 1:N
                $especialidades = Especialidade::where('id_institucion', $institucionId)
                    ->where('condicion', 1)
                    ->select('id', 'nombre')
                    ->get();
            } else {
                // Sin relación definida, devolver vacío
                $especialidades = collect();
            }

            return response()->json($especialidades);
        } catch (Exception $e) {
            return response()->json(['error' => 'Error al cargar especialidades'], 500);
        }
    }
}
