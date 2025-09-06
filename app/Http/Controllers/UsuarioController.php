<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Institucione;
use App\Models\Especialidade;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class UsuarioController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $inactivos = $request->query('inactivos');
        $query = User::with(['roles', 'instituciones', 'especialidades']);
        
        if ($inactivos) {
            $query->where('condicion', false);
        } else {
            $query->where('condicion', true);
        }

        // Aplicar filtro de búsqueda si existe
        if ($request->filled('busquedaUsuario')) {
            $busqueda = $request->get('busquedaUsuario');
            $query->where(function($q) use ($busqueda) {
                $q->where('name', 'LIKE', '%' . $busqueda . '%')
                  ->orWhere('email', 'LIKE', '%' . $busqueda . '%');
            });
        }

        $usuarios = $query->get();
        $roles = Role::all();
        $instituciones = Institucione::all();
        $especialidades = Especialidade::all();

        return view('Usuario.index', compact('usuarios', 'roles', 'instituciones', 'especialidades'));
    }

    public function create()
    {
        $roles = Role::all();
        return view('Usuario.index', compact('roles'));
    }

    /**
     * Get especialidades by instituciones
     */
    public function getEspecialidadesByInstituciones(Request $request)
    {
        $institucionesIds = $request->input('instituciones', []);
        
        if (empty($institucionesIds)) {
            return response()->json([]);
        }
        
        // Buscar especialidades que pertenezcan a las instituciones seleccionadas
        // usando la relación many-to-many a través de la tabla pivot
        $especialidades = Especialidade::whereHas('instituciones', function($query) use ($institucionesIds) {
            $query->whereIn('institucione.id', $institucionesIds)
                  ->where('especialidad_institucion.condicion', true);
        })
        ->where('condicion', true)
        ->orderBy('nombre')
        ->get(['id', 'nombre']);
        
        return response()->json($especialidades);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'cedula' => 'required|unique:users,cedula',
            'email' => 'required|email|unique:users,email',
            'role' => 'required|exists:roles,name',
            'instituciones' => 'nullable|array',
            'instituciones.*' => 'exists:institucione,id',
            'especialidades' => 'nullable|array',
            'especialidades.*' => 'exists:especialidad,id',
        ], [
            'cedula.unique' => 'La cédula ya está registrada.',
            'email.unique' => 'El correo ya está registrado.',
        ]);
        
        try {
            DB::beginTransaction();

            // Crear el usuario con una contraseña temporal (será reemplazada por el reset)
            $usuario = User::create([
                'name' => $request->name,
                'cedula' => $request->cedula,
                'email' => $request->email,
                'password' => Hash::make(Str::random(32)), // Contraseña temporal aleatoria
            ]);

            // Asignar el rol
            $usuario->assignRole($request->role);

            // Asignar instituciones si es profesor
            if ($request->role === 'profesor' && $request->has('instituciones')) {
                $usuario->instituciones()->sync($request->instituciones);
            }

            // Asignar especialidades si es profesor
            if ($request->role === 'profesor' && $request->has('especialidades')) {
                $usuario->especialidades()->sync($request->especialidades);
            }

            // Enviar email de reset password automáticamente
            $status = Password::sendResetLink(['email' => $usuario->email]);

            DB::commit();

            if ($status == Password::RESET_LINK_SENT) {
                return redirect()->route('usuario.index')->with('success', 'Usuario creado exitosamente. Se ha enviado un correo para configurar la contraseña.');
            } else {
                return redirect()->route('usuario.index')->with('warning', 'Usuario creado, pero no se pudo enviar el correo de configuración.');
            }
            
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('usuario.index')->with('error', 'Error al crear el usuario: ' . $e->getMessage());
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $usuario)
    {
        $request->validate([
            'name' => 'required',
            'cedula' => 'required|unique:users,cedula,' . $usuario->id,
            'email' => 'required|email|unique:users,email,' . $usuario->id,
            'role' => 'required|exists:roles,name',
            'instituciones' => 'nullable|array',
            'instituciones.*' => 'exists:institucione,id',
            'especialidades' => 'nullable|array',
            'especialidades.*' => 'exists:especialidad,id',
        ], [
            'cedula.unique' => 'La cédula ya está registrada.',
            'email.unique' => 'El correo ya está registrado.',
        ]);

        try {
            DB::beginTransaction();
            
            $requestData = [
                'name' => $request->name,
                'cedula' => $request->cedula,
                'email' => $request->email,
            ];

            //Comprueba la contraseña y aplica el hash
            if (empty($request->password)) {
                // No actualizar contraseña
            } else {
                $requestData['password'] = Hash::make($request->password);
            }
            
            $usuario->update($requestData);
            
            //actualiza el rol
            $usuario->syncRoles($request->role);

            // Actualizar instituciones si es profesor
            if ($request->role === 'profesor' && $request->has('instituciones')) {
                $usuario->instituciones()->sync($request->instituciones);
            } else {
                // Si no es profesor, quitar todas las instituciones
                $usuario->instituciones()->detach();
            }

            // Actualizar especialidades si es profesor
            if ($request->role === 'profesor' && $request->has('especialidades')) {
                $usuario->especialidades()->sync($request->especialidades);
            } else {
                // Si no es profesor, quitar todas las especialidades
                $usuario->especialidades()->detach();
            }

            DB::commit();
            return redirect()->route('usuario.index')->with('success', 'Usuario actualizado exitosamente.');
        }
        catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('usuario.index')->with('error', 'Error al actualizar el usuario: ' . $e->getMessage());
        }
    }

    /**
     * Reenviar email de configuración de contraseña
     */
    public function resendPasswordSetup(User $usuario)
    {
        try {
            // Verificar que el usuario esté activo
            if (!$usuario->condicion) {
                return redirect()->route('usuario.index')->with('error', 'No se puede enviar configuración a usuarios inactivos.');
            }

            // Enviar email de reset password
            $status = Password::sendResetLink(['email' => $usuario->email]);

            if ($status == Password::RESET_LINK_SENT) {
                return redirect()->route('usuario.index')->with('success', 'Correo de configuración enviado exitosamente.');
            } else {
                return redirect()->route('usuario.index')->with('error', 'No se pudo enviar el correo de configuración.');
            }
            
        } catch (\Exception $e) {
            return redirect()->route('usuario.index')->with('error', 'Error al enviar el correo: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $usuario)
    {
        // Proteger al superadmin inborrable
        if ($usuario->name === 'Super Administrador') {
            return redirect()->route('usuario.index')->with('error', 'No puedes eliminar ni inactivar el usuario principal del sistema.');
        }

        // Cambia el estado de condicion (activo/inactivo)
        $usuario->condicion = !$usuario->condicion;
        $usuario->save();

        $mensaje = $usuario->condicion
            ? 'Usuario activado exitosamente.'
            : 'Usuario inactivado exitosamente.';

        return redirect()->route('usuario.index')->with('success', $mensaje);
    }
}