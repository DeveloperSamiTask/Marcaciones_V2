<?php

namespace App\Http\Controllers;

use App\Http\Requests\EmpresaRequest;
use App\Models\Empresa;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class EmpresaController extends Controller
{
    public function index(Request $request)
    {
        $empresas = Empresa::where('estado', $request->archivado ? false : true)->orderBy('razonsocial')->get();
        return Inertia::render('empresas/index', [
            'empresas' => $empresas
        ]);
    }

    public function create()
    {
        return Inertia::render('empresas/create');
    }

    public function store(EmpresaRequest $request)
    {
        $data = $request->validated();
        try {
            DB::transaction( function () use ($data){
                Empresa::create($data);
            });
            return to_route('empresas.index')->withSuccess(['message' => 'Empresa creada exitosamente!']);
        } catch (Exception $e) {
            return back()->withErrors(['message' => $e->getMessage()]);
        }
    }

    public function edit(Empresa $empresa)
    {
        return Inertia::render('empresas/create', [
            'empresa' => $empresa
        ]);
    }

    public function update(EmpresaRequest $request, Empresa $empresa)
    {
        $data = $request->validated();
        try {
            DB::transaction( function () use ($data, $empresa){
                $empresa->update($data);
            });
            return to_route('empresas.index')->withSuccess(['message' => 'Empresa actualizada exitosamente!']);
        } catch (Exception $e) {
            return back()->withErrors(['message' => $e->getMessage()]);
        }
    }

    public function destroy(Empresa $empresa)
    {
        try {
            DB::transaction( function () use ($empresa){
                $empresa->update(['estado' => 0]); // cambiamos de estadoa 0, [1 => activo , 0 => inactivo]
            });
            return to_route('empresas.index')->withSuccess(['message' => 'Empresa eliminada exitosamente!']);
        } catch (Exception $e) {
            return back()->withErrors(['message' => $e->getMessage()]);
        }
    }
}
