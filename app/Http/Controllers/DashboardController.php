<?php

namespace App\Http\Controllers;

use App\Models\Empleado;
use App\Models\Suspension;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        DB::statement("SET lc_time_names = 'es_ES'");
        $fechaInicio = Carbon::now()->subMonth()->day(29)->startOfDay();
        $fechaFin = Carbon::now()->day(28)->endOfDay();

        $suspensiones = Suspension::query()
        ->where('estado', 0) // suspensiones pendientes x mes
        ->when($request->user()->rol_id == 4, fn($q) => $q->whereHas('empleado', fn($query) => $query->where('jefe_id', $request->user()->empleado_id)))
        ->whereYear('fecha', now()->year)
        ->whereNot('tipo', 'falta injustificada')
        ->selectRaw('MONTHNAME(fecha) as mes, MONTH(fecha) as numero, COUNT(*) as total')
        ->groupBy(['mes', 'numero'])
        ->orderBy('numero')
        ->get();

        $faltasInjustificadas = Suspension::query()
        ->where('estado', 0) // faltas injustificadas x mes
        ->when($request->user()->rol_id == 4, fn($q) => $q->whereHas('empleado', fn($query) => $query->where('jefe_id', $request->user()->empleado_id)))
        ->whereYear('fecha', now()->year)
        ->where('tipo', 'falta injustificada')
        ->selectRaw('MONTHNAME(fecha) as mes, MONTH(fecha) as numero, COUNT(*) as total')
        ->groupBy(['mes', 'numero'])
        ->orderBy('numero')
        ->get();

        $empleados = Empleado::with(['area', 'empresa', 'horarios' => function($q) use ($fechaInicio, $fechaFin){
            $q->whereBetween('fecha', [$fechaInicio, $fechaFin]);
        }, 'marcaciones' => function ($q) use ($fechaInicio, $fechaFin){
            $q->whereBetween('fecha', [$fechaInicio, $fechaFin]);
        }])
        ->whereHas('empresa', fn($q) => $q->where('estado', 1))
        ->whereNull('fecha_cese')
        ->where('jornada_id', 2)
        ->when($request->user()->rol_id == 4, fn($q) => $q->where('empresa_id', $request->user()->empleado->empresa_id))
        ->get()
        ->map(function ($empleado) use ($fechaInicio, $fechaFin){
            $fechas = CarbonPeriod::create($fechaInicio, $fechaFin);
            $horas = 0;
            collect($fechas)->map(function ($fecha) use ($empleado, &$horas) {
                $horario = $empleado->horarios->firstWhere('fecha', $fecha);
                $marcacion = $empleado->marcaciones->firstWhere('fecha', $fecha);
                if ($horario && $marcacion && $marcacion->ingreso && $marcacion->salida) {
                    $partTime = $empleado->jornada_id == 2 && !$marcacion->ingreso_refri; // se valida si se trata de partime y no tomo su refrigerio
                    $horasTrabajadas = max(0, $horario->ingreso->diffInMinutes($horario->salida, false));

                    $tardanza = max(0, $horario->ingreso->diffInMinutes($marcacion->ingreso, false)); // si es negativo devuelve 0
                    $horas += $horasTrabajadas - $tardanza - ($partTime ? 0 : 60); // no se descuenta la hora de refrigerio si es parttime y no tomo refrigerio
                }
            });

            $empleado->horas_trabajadas = $horas;
            return $empleado;
        });

        return Inertia::render('dashboard', [
            'suspensiones' => $suspensiones,
            'faltasInjustificadas' => $faltasInjustificadas,
            'compensas' => [],
            'empleados' => $empleados,
        ]);
    }
}
