<?php

namespace App\Http\Controllers;

use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Models\Empleado;
use App\Models\Role;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class UsuarioController extends Controller
{
    public function index(Request $request): Response
    {
        $usuarios = User::with(['empleado', 'rol'])
            ->where('estado', $request->archivado ? false : true)
            ->get();

        return Inertia::render('usuarios/index', [
            'usuarios' => $usuarios
        ]);
    }

    public function create(): Response
    {
        $empleados = Empleado::whereNull('fecha_cese')->orderBy('apellidos')->get();
        $roles = Role::where('estado', 1)->get();
        return Inertia::render('usuarios/create', [
            'empleados' => $empleados,
            'roles' => $roles,
        ]);

    }

    public function store(StoreUserRequest $request)
    {
        $data = $request->validated();
        try{
            DB::transaction(function () use ($data) {
                User::create($data);
            });
            return to_route('usuarios.index')->withSuccess(['message' => 'Usuario creado exitosamente!']);
        } catch (Exception $e) {
            return back()->withErrors([ 'message' => $e->getMessage()])->withInput();
        }

    }

    public function edit(User $usuario)
    {
        $empleados = Empleado::whereNull('fecha_cese')->orderBy('apellidos')->get();
        $roles = Role::where('estado', 1)->get();
        return Inertia::render('usuarios/create', [
            'usuario' => $usuario,
            'empleados' => $empleados,
            'roles' => $roles,
        ]);

    }

    public function update(UpdateUserRequest $request, User $usuario)
    {
        $data = $request->validated();
        try{
            DB::transaction(function () use ($data, $usuario) {
                $usuario->name = $data['name'];
                $usuario->email = $data['email'];
                $usuario->rol_id = $data['rol_id'];
                $usuario->empleado_id = $data['empleado_id'];
                if($data['password']){
                    $usuario->password = $data['password'];
                }
                $usuario->save();
            });
            return to_route('usuarios.index')->withSuccess(['message' => 'Usuario actualizado exitosamente!']);
        } catch (Exception $e) {
            return back()->withErrors([ 'message' => $e->getMessage()])->withInput();
        }

    }

    public function destroy(string $id)
    {
        //
    }
}
