<?php

namespace App\Http\Controllers;

use App\Exports\EmpleadoExport;
use App\Http\Requests\EmpleadoRequest;
use App\Models\Area;
use App\Models\Empleado;
use App\Models\Empresa;
use App\Models\Jornada;
use App\Models\User;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;

class EmpleadoController extends Controller
{
    public function index(Request $request)
    {
        if (!$request->has('cesado')) {
            return redirect()->route('empleados.index', ['cesado' => 0]);
        }

        $filters = $request->validate([
            'cesado' => 'nullable|boolean',
        ]);

        $cesado = isset($filters['cesado']) ? boolval($filters['cesado']) : null;

        $empleados = Empleado::with(['empresa', 'area', 'jefe', 'jornada'])
            ->when(!is_null($cesado), function ($query) use ($cesado) {
                return $cesado
                    ? $query->whereNotNull('fecha_cese')
                    : $query->whereNull('fecha_cese');
            })
            ->orderBy('apellidos')
            ->get();

        return Inertia::render('empleados/index', [
            'empleados' => $empleados,
            'filters' => $filters,
            'csrf_token' => csrf_token(),
        ]);
    }


    public function mostrarEmpleadoModal($id)
    {
        $empleado = Empleado::findOrFail($id);

        return Inertia::render('empleados/delete', [
            'empleado' => $empleado,
        ]);
    }



    public function create(): Response
    {
        $empresas = Empresa::where('estado', true)->orderBy('razonsocial')->get(['id', 'razonsocial']);
        $jornadas = Jornada::get(['id', 'nombre']);
        $encargados = User::with('empleado')->where('estado', true)->get()->sortBy(fn($encargado) => $encargado->empleado->apellidos)->values();
        $areas = Area::get(['id', 'nombre', 'empresa_id']);

        return Inertia::render('empleados/create', [
            'empresas' => $empresas,
            'jornadas' => $jornadas,
            'encargados' => $encargados,
            'areas' => $areas,
        ]);
    }

    public function store(EmpleadoRequest $request): RedirectResponse
    {
        $data = $request->validated();
        try {
            DB::transaction(function () use ($data) {
                Empleado::create($data);
            });
            return to_route('empleados.index')->withSuccess(['message' => 'Empleado creada exitosamente!']);
        } catch (Exception $e) {
            return back()->withErrors(['message' => $e->getMessage()])->withInput();
        }
    }

    public function edit(Empleado $empleado): Response
    {
        $empresas = Empresa::where('estado', true)->orderBy('razonsocial')->get(['id', 'razonsocial']);
        $jornadas = Jornada::get(['id', 'nombre']);
        $encargados = User::with('empleado')->where('estado', true)->get()->sortBy(fn($encargado) => $encargado->empleado->apellidos)->values();
        $areas = Area::get(['id', 'nombre', 'empresa_id']);

        return Inertia::render('empleados/create', [
            'empleado' => $empleado,
            'empresas' => $empresas,
            'jornadas' => $jornadas,
            'encargados' => $encargados,
            'areas' => $areas,
        ]);
    }

    public function update(EmpleadoRequest $request, Empleado $empleado): RedirectResponse
    {
        $data = $request->validated();
        try {
            DB::transaction(function () use ($data, $empleado) {
                $empleado->update($data);
            });
            return to_route('empleados.index')->withSuccess(['message' => 'Empleado actualizada exitosamente!']);
        } catch (Exception $e) {
            return back()->withErrors(['message' => $e->getMessage()])->withInput();
        }
    }

    public function destroy(Request $request, Empleado $empleado)
    {
        $data = $request->validate([
            'fecha_cese' => 'required|date',
        ]);
        try {
            DB::transaction(function () use ($data, $empleado) {
                $empleado->update(['fecha_cese' => $data['fecha_cese']]);
                User::where('empleado_id', $empleado->id)->delete();
            });
            return to_route('empleados.index')->withSuccess(['message' => 'Empleado cesado correctamente!']);
        } catch (Exception $e) {
            return back()->withErrors(['message' => $e->getMessage()])->withInput();
        }
    }

    public function download(Request $request)
    {
        $data = $request->validate([
            'empleados' => 'required',
        ]);

        return Excel::download(new EmpleadoExport($data), 'empleados.xlsx');
    }
}
