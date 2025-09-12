<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Empleado;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AreaController extends Controller
{
    public function index(Request $request): Response
    {
        $areas = Area::with('empleado')
            ->where('estado', $request->archivado ? false : true)
            ->orderBy('nombre')
            ->get();

        session(['areas_url' => $request->fullUrl()]);

        return Inertia::render('areas/index', [
            'areas' => $areas
        ]);
    }

    public function create()
    {
        $encargados = User::with('empleado')->where('estado', true)->get()->sortBy(fn($encargado) => $encargado->empleado->apellidos)->values();
        return Inertia::render('areas/create', [
            'encargados' => $encargados
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre' => 'required|min:3|string',
            'empleado_id' => 'required|exists:empleados,id',
        ]);
        try{
            DB::transaction(function () use ($data) {
                $empleado = Empleado::find($data['empleado_id']);
                $data['empresa_id'] = $empleado->empresa_id;
                Area::create($data);
            });
            return to_route('areas.index')->withSuccess(['message' => 'Area creada exitosamente!']);
        } catch (Exception $e) {
            return back()->withErrors([ 'message' => $e->getMessage()])->withInput();
        }
    }

    public function edit(Area $area)
    {
        $encargados = User::with('empleado')->where('estado', true)->get()->sortBy(fn($encargado) => $encargado->empleado->apellidos)->values();
        return Inertia::render('areas/create', [
            'area' => $area,
            'encargados' => $encargados,
        ]);
    }

    public function update(Request $request, Area $area)
    {
        $data = $request->validate([
            'nombre' => 'required|min:3|string',
            'empleado_id' => 'required|exists:empleados,id',
        ]);
        try {
            DB::transaction(function () use ($data, $area) {
                $area->update($data);
            });
            return redirect()->to(session('areas_url', route('areas.index')))->withSuccess(['message' => 'Area actualizada exitosamente!']);
        } catch (Exception $e) {
            return back()->withInput()->withErrors([ 'message' => $e->getMessage()]);
        }
    }

    public function destroy(Area $area)
    {
        try {
            DB::transaction(function () use ($area) {
                $area->update(['estado' => 0]);
            });
            return redirect()->to(session('areas_url', route('areas.index')))->withSuccess(['message' => 'Area eliminada exitosamente!']);
        } catch (Exception $e) {
            return back()->withInput()->withErrors([ 'message' => $e->getMessage()]);
        }
    }
}
