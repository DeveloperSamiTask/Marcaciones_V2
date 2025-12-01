<?php

namespace App\Http\Controllers;

use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Models\Empleado;
use App\Models\Empresa;
use App\Models\Role;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\AsistenciaDetalle;
use App\Models\Horario;
use Carbon\Carbon;


class UsuarioController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'estado' => 'nullable|boolean',
        ]);

        $estado = isset($filters['estado']) ? boolval($filters['estado']) : true; // Default to true (activos)

        $usuarios = User::with(['empleado', 'rol'])
            ->when(! is_null($estado), function ($query) use ($estado) {
                return $query->where('estado', $estado);
            })
            ->orderBy('id')
            ->get();

        return Inertia::render('usuarios/index', [
            'usuarios' => $usuarios,
            'filters' => ['estado' => $estado],
            'csrf_token' => csrf_token(),
        ]);
    }

    public function create(): Response
    {
        $empleados = Empleado::whereNull('fecha_cese')->orderBy('apellidos')->get();
        $roles = Role::where('estado', 1)->get();
        $empresas = Empresa::where('estado', 1)->get();
        return Inertia::render('usuarios/create', [
            'empleados' => $empleados,
            'roles' => $roles,
            'empresas' => $empresas
        ]);
    }

    public function store(StoreUserRequest $request)
    {
        $data = $request->validated();

        try {
            DB::transaction(function () use ($data) {
                $user = User::create([
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'password' => $data['password'],
                    'rol_id'  => $data['rol_id'],
                    'empleado_id' => $data['empleado_id'],
                ]);

                // Si es supervisor
                if ($data['rol_id'] == 5) {

                    // Empresas asignadas
                    if (!empty($data['empresas_asignadas'])) {
                        $user->empresasAsignadas()->sync($data['empresas_asignadas']);
                    }

                    // Empleados a cargo con empresa_id en pivot
                    if (!empty($data['empleados_a_cargo'])) {

                        $pivotData = [];

                        foreach ($data['empleados_a_cargo'] as $item) {
                            $pivotData[$item['empleado_id']] = [
                                'empresa_id' => $item['empresa_id']
                            ];
                        }

                        $user->empleadosACargo()->sync($pivotData);
                    }
                }
            });

            return to_route('usuarios.index')->withSuccess([
                'message' => 'Usuario creado exitosamente!'
            ]);
        } catch (Exception $e) {
            return back()
                ->withErrors(['message' => $e->getMessage()])
                ->withInput();
        }
    }


public function edit(User $usuario)
{
    // Obtener empleados con su empresa_id
    $empleados = Empleado::with('empresa:id,razonsocial')
        ->whereNull('fecha_cese')
        ->orderBy('apellidos')
        ->get()
        ->map(function ($empleado) {
            return [
                'id' => $empleado->id,
                'nombres' => $empleado->nombres,
                'apellidos' => $empleado->apellidos,
                'empresa_id' => $empleado->empresa_id,
                'empresa' => $empleado->empresa ? $empleado->empresa->razonsocial : null,
            ];
        });

    $roles = Role::where('estado', 1)->get();
    $empresas = Empresa::where('estado', 1)->get();

    if ($usuario->rol_id == 5) {
        // CARGAR relaciones con pivot
        $usuario->load(['empleadosACargo' => function($query) {
            $query->withPivot('empresa_id');
        }, 'empresasAsignadas']);

        // Estructura correcta para empleados_a_cargo
        $usuario->empleados_a_cargo = $usuario->empleadosACargo->map(function ($empleado) {
            return [
                'empleado_id' => $empleado->id,
                'empresa_id' => $empleado->pivot->empresa_id ?? null,
            ];
        })->filter(function($item) {
            // Filtrar solo los que tienen empresa_id válido
            return !is_null($item['empresa_id']);
        })->values()->all();

        // Solo IDs de empresas
        $usuario->empresas_asignadas = $usuario->empresasAsignadas->pluck('id')->all();

    } else {
        $usuario->empleados_a_cargo = [];
        $usuario->empresas_asignadas = [];
    }

    // Limpiar relaciones cargadas
    unset($usuario->empleadosACargo);
    unset($usuario->empresasAsignadas);

    return Inertia::render('usuarios/create', [
        'usuario' => $usuario,
        'empleados' => $empleados,
        'roles' => $roles,
        'empresas' => $empresas,
    ]);
}

    public function update(UpdateUserRequest $request, User $usuario)
    {
        $data = $request->validated();

        try {
            DB::transaction(function () use ($data, $usuario) {

                // Actualizar campos básicos
                $usuario->name = $data['name'];
                $usuario->email = $data['email'];
                $usuario->rol_id = $data['rol_id'];
                $usuario->empleado_id = $data['empleado_id'];

                if (!empty($data['password'])) {
                    $usuario->password = $data['password'];
                }

                $usuario->save();

                // Si es supervisor (rol_id = 5)
                if ($data['rol_id'] == 5) {

                    // Empresas asignadas
                    $usuario->empresasAsignadas()->sync($data['empresas_asignadas'] ?? []);

                    // Empleados a cargo con empresa_id en pivot
                    if (!empty($data['empleados_a_cargo'])) {

                        $pivotData = [];

                        foreach ($data['empleados_a_cargo'] as $item) {
                            $pivotData[$item['empleado_id']] = [
                                'empresa_id' => $item['empresa_id']
                            ];
                        }

                        $usuario->empleadosACargo()->sync($pivotData);
                    } else {
                        // Si no se enviaron empleados, limpiar pivot
                        $usuario->empleadosACargo()->detach();
                    }
                } else {
                    // Si dejó de ser supervisor → limpiar todo
                    $usuario->empresasAsignadas()->detach();
                    $usuario->empleadosACargo()->detach();
                }
            });

            return to_route('usuarios.index')->withSuccess([
                'message' => 'Usuario actualizado exitosamente!'
            ]);
        } catch (Exception $e) {
            return back()
                ->withErrors(['message' => $e->getMessage()])
                ->withInput();
        }
    }


    public function destroy(string $id)
    {
        //
    }
}
