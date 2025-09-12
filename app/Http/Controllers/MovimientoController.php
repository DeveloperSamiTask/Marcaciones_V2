<?php

namespace App\Http\Controllers;

use App\Models\Movimiento;
use App\Models\Empleado;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Exception;
use Carbon\Carbon;

class MovimientoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */


    public function toggleEstadoAPI(Request $request)
    {
        try {

            $request->validate([
                'empleado_id' => 'required|exists:empleados,id',
                'motivo' => 'required|string|min:3',
                'tipo_movimiento' => 'required|in:cese,reactivacion',
                'fecha_cambio' => 'required|date'
            ]);

            $empleado = Empleado::findOrFail($request->empleado_id);

            $registro_cese = Carbon::parse($empleado->fecha_cese);
            $registro_activacion = Carbon::parse($empleado->fecha_ingreso);

            if ($request->tipo_movimiento === 'cese') {
                $empleado->fecha_cese = Carbon::parse($request->fecha_cambio);
            } elseif ($request->tipo_movimiento === 'reactivacion') {

                $empleado->fecha_ingreso = Carbon::parse($request->fecha_cambio);
                $empleado->fecha_cese = null;
            }

            $empleado->save();

            // Registrar movimiento
            $movimiento = Movimiento::create([
                'nombres' => $empleado->nombres,
                'dni' => $empleado->dni,
                'fecha_movimiento' => now()->format('d-m-Y'),
                'motivo' => $request->motivo,
                'tipo_movimiento' => $request->tipo_movimiento,
                'empleados_id' => $empleado->id,

                'ultima_fecha_cese' =>  Carbon::parse($registro_cese),
                'ultima_fecha_activacion' => Carbon::parse($registro_activacion),

                'fecha_cese_actual' =>  Carbon::parse($empleado->fecha_cese),
                'fecha_activacion_actual' => Carbon::parse($empleado->fecha_ingreso),
            ]);

            return response()->json([
                'message' => "Empleado {$request->tipo_movimiento} exitosamente.",
                'empleado_id' => $empleado->nombres,
                'empleado_f.cese' => optional($empleado->fecha_cese)->format('d-m-Y'),
                'empleado_f.activacion' => optional($empleado->fecha_ingreso)->format('d-m-Y'),
                'fecha_movimiento' => optional($request->fecha_cambio)->format('d-m-Y'),
                'movimiento' => $movimiento,
            ], 200);
        } catch (Exception $e) {
            Log::error('Error al cambiar estado del empleado: ' . $e->getMessage());

            return response()->json([
                'error' => 'Ocurrió un error al procesar la solicitud.',
                'detalle' => $e->getMessage(),
            ], 500);
        }
    }

    public function toggleEstadoInertia(Request $request)
    {
        try {
            $request->validate([
                'empleado_id' => 'required|exists:empleados,id',
                'motivo' => 'required|string|min:3',
                'tipo_movimiento' => 'required|in:cese,reactivacion',
                'fecha_cambio' => 'required|date'
            ]);

            $empleado = Empleado::findOrFail($request->empleado_id);

            $registro_cese = Carbon::parse($empleado->fecha_cese);
            $registro_activacion = Carbon::parse($empleado->fecha_ingreso);

            if ($request->tipo_movimiento === 'cese') {
                
                $empleado->fecha_cese = Carbon::parse($request->fecha_cambio);

            } elseif ($request->tipo_movimiento === 'reactivacion') {

                $empleado->fecha_ingreso = Carbon::parse($request->fecha_cambio);
                $empleado->fecha_cese = null;
            }

            $empleado->save();

            // Registrar movimiento
            Movimiento::create([
                'nombres' => $empleado->nombres,
                'dni' => $empleado->dni,
                'fecha_movimiento' => now()->format('d-m-Y'),
                'motivo' => $request->motivo,
                'tipo_movimiento' => $request->tipo_movimiento,
                'empleados_id' => $empleado->id,

                'ultima_fecha_cese' =>  $registro_cese,
                'ultima_fecha_activacion' => $registro_activacion,

                'fecha_cese_actual' =>  $empleado->fecha_cese,
                'fecha_activacion_actual' => $empleado->fecha_ingreso,
            ]);

            // Redirigir con mensaje flash para Inertia
            return Redirect::route('empleados.index')->withSuccess('success', "Empleado {$request->tipo_movimiento} exitosamente.");
        } catch (Exception $e) {
            Log::error('Error al cambiar estado del empleado: ' . $e->getMessage());

            return Redirect::back()->with('error', 'Ocurrió un error al procesar la solicitud.');
        }
    }







    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Movimiento $movimiento)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Movimiento $movimiento)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Movimiento $movimiento)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Movimiento $movimiento)
    {
        //
    }
}
