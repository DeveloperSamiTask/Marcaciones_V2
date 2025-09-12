<?php

namespace App\Http\Controllers;

use App\Exports\AmonestacionExport;
use App\Exports\CompensaExport;
use App\Exports\HorasExtraExport;
use App\Exports\TareoExport;
use App\Exports\TareoStarsoftExport;
use App\Models\Area;
use App\Models\Empleado;
use App\Models\Empresa;
use App\Models\Feriado;
use App\Models\Horario;
use App\Models\Jornada;
use App\Models\Permiso;
use App\Models\Suspension;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;

class ReporteController extends Controller
{
    public function tareoIndex(Request $request): Response
    {
        $filters = $request->validate([
            'empresa' => 'nullable|integer|exists:empresas,id',
            'jornada' => 'nullable|integer|exists:jornadas,id',
            'area' => 'nullable|integer|exists:areas,id',
            'fechaInicio' => 'nullable|date',
            'fechaFin' => 'nullable|date|after_or_equal:fechaInicio',
        ]);

        $inicio = Carbon::parse($filters['fechaInicio'] ?? '');
        $fin = Carbon::parse($filters['fechaFin'] ?? '');
        $empresas = Empresa::where('estado', 1)->get(['id', 'razonsocial']);
        $areas = Area::where('estado', 1)->where('empresa_id', $request->empresa)->get(['id', 'nombre']);
        $jornadas = Jornada::get(['id', 'nombre']);
        // $feriadoIds = Feriado::whereBetween('fecha', [$request->fechaInicio, $request->fechaFin])->pluck('id');

        $empleados = Empleado::query()
            ->when($request->user()->rol_id == 4, fn($q) => $q->where('jefe_id', $request->user()->empleado_id))
            ->select('empleados.id', 'dni', 'nombres', 'apellidos', 'area_id', 'horas', 'jornada_id', 'empresa_id', 'fecha_ingreso')
            ->with(['area:id,nombre', 'horarios' => function ($q) use ($request) {
                $q->whereBetween('fecha', [$request->fechaInicio, $request->fechaFin]);
            }, 'marcaciones' => function ($q) use ($request) {
                $q->whereBetween('fecha', [$request->fechaInicio, $request->fechaFin]);
            }])
            ->where('empresa_id', $request->empresa)
            ->when($request->area, fn($query) => $query->where('area_id', $request->area))
            ->where('jornada_id', $request->jornada)
            ->when($request->fechaFin, function ($query) use ($request) {
                $query->whereDate('fecha_ingreso', '<=', $request->fechaFin);
            })
            ->whereNull('fecha_cese')
            ->orderBy('apellidos')
            ->get();

        // Obtener feriados pendientes para cada empleado
        $feriadosPendientes = Horario::whereIn('empleado_id', $empleados->pluck('id'))
            ->with(['empleado', 'feriados'])
            ->where('estado', 'L')
            ->whereBetween('fecha', [$request->fechaInicio, $request->fechaFin])
            ->get()
            ->groupBy('empleado_id')
            ->map(function ($horarios) {
                $empleadoId = $horarios->first()->empleado_id;
                $fechasHorarios = $horarios->pluck('fecha');

                return Feriado::whereIn('fecha', $fechasHorarios)
                    ->whereDoesntHave('horarios', fn($q) => $q->where('empleado_id', $empleadoId))
                    ->count();
        });

        $lista = $empleados->map(function ($empleado) use ($feriadosPendientes, $inicio, $fin) {
            $fechaCorte = $empleado->fecha_ingreso > $inicio ? $empleado->fecha_ingreso : $inicio;
            // $empleadoHorarios = $empleado->horarios ?? collect();
            // $empleadoMarcaciones = $empleado->marcaciones ?? collect();
            $empleadoHorarios = $empleado->horarios->filter(fn($h) => $h->fecha >= $fechaCorte && $h->fecha <= $fin);
            $empleadoMarcaciones = $empleado->marcaciones->filter(fn($m) => $m->fecha >= $fechaCorte && $m->fecha <= $fin);
            $estadosCount = $empleadoHorarios->countBy('estado');
            $dias = $fechaCorte->diffInDays($fin) + 1;

            $tardanza = 0;
            $horas = 0;
            $horasLaboradas = 0;
            $extra = 0;
            $anticipado = 0;
            $nocturno = 0;
            $extra_25 = 0;
            $extra_35 = 0;

            // hallar horas, tardanza, extra, ....
            $empleadoMarcaciones->each(function ($marcacion) use ($empleado, &$horas, &$horasLaboradas, &$tardanza, &$extra, &$anticipado, &$nocturno, &$extra_25, &$extra_35) {
                $horario = $empleado->horarios->firstWhere('fecha', $marcacion->fecha);
                $partTime = $empleado->jornada_id == 2 && !$marcacion->ingreso_refri; // se valida si se trata de partime y no tomo su refrigerio

                if ($horario && $marcacion->ingreso && $marcacion->salida) {
                    $horasTrabajadas = max(0, $horario->ingreso->diffInMinutes($empleado->jornada_id == 2 ? $horario->salida : $marcacion->salida, false));
                    $horasTardanza = max(0, $horario->ingreso->diffInMinutes($marcacion->ingreso, false)); // si es negativo devuelve 0
                    $horasExtras = $marcacion->estado_horas_extra == 1 ? $horario->salida->diffInMinutes($marcacion->salida, false) : 0;
                    $horasAnticipado = max(0, $marcacion->salida->diffInMinutes($horario->salida, false)); // hora antes de su salida programado considerar 20 min si es su salida programada 11 o 11:30
                    $descansoMedico = $horario->estado == 'M' && $empleado->jornada_id == 2 ? 240 : 0; // solo para part-time

                    $horas += ($horasTrabajadas - $horasTardanza - ($partTime ? 0 : 60) + $descansoMedico); // no se descuenta la hora de refrigerio si es parttime y si no tomo refrigerio
                    $horasLaboradas += (max(0, $horario->ingreso->diffInMinutes($horario->salida, false)) - ($partTime ? 0 : 60)); // no se descuenta la hora de refrigerio si es parttime y si no tomo refrigerio
                    $tardanza += $horasTardanza;
                    $extra += max(0, $horario->salida->diffInMinutes($marcacion->salida, false)); // tiempo despues de su hora de salida

                    if((in_array($horario->salida->format('H:i'), ['23:00', '23:30' , '23:59']) && ($empleado->empresa_id == 4 || $empleado->empresa_id == 3)) || ($horario->salida->format('H:i') == '18:30' && $empleado->empresa_id == 1)){ // solo para chacxra y granja
                        $minutosTolerancia = $empleado->empresa_id == 1 ? 30 : 20; // estos minutos son de tolerancia en salida anticipada, solo granja tiene hasta 30 minutos
                        $anticipado += $horasAnticipado >= $minutosTolerancia ? $horasAnticipado : 0;
                    }else{
                        $anticipado += $horasAnticipado;
                    }

                    if ($empleado->empresa_id == 1 || $empleado->empresa_id == 4 || $empleado->empresa_id == 3) { // nocturno + de 22horas
                        $nocturno += max(0, $horario->salida->copy()->setTime(22, 0)->diffInMinutes($horario->salida, false)); // se rige al horario
                    }

                    if ($marcacion->salida->greaterThan($horario->salida) && $horasExtras >= 30) {
                        $limite25 = min($horasExtras, 120); // solo coge el valor hasta 120
                        $extra_25 += $limite25 >= 120 ? 120 : ($limite25 >= 90 ? 90 : ($limite25 >= 60 ? 60 : 30));

                        if ($horasExtras > 120) {
                            $minutosRestantes = $horasExtras - 120;
                            $extra_35 += floor($minutosRestantes / 60) * 60;
                            $extra_35 += ($minutosRestantes % 60 >= 30) ? 30 : 0;
                        }
                    }
                }
            });

            return [
                'empleado' => $empleado,
                'compensa_pendiente' => $feriadosPendientes->get($empleado->id, 0),
                'falta_injustificada' => $estadosCount->get('FI', 0),
                'falta_justificada' => $estadosCount->get('FJ', 0),
                'descanso' => $estadosCount->get('D', 0),
                'feriado' => $estadosCount->get('F', 0),
                'feriado_laboral' => $estadosCount->get('FL', 0), // VERIFICAR CON RRHH
                'descanso_medico' => $estadosCount->get('M', 0),
                'vacaciones' => $estadosCount->get('V', 0),
                'compensa' => $compensas = $estadosCount->filter(fn($count, $estado) => in_array($estado, ['C', 'CA', 'CHE']))->sum(),
                'licencia_con_goce' => $estadosCount->get('LCG', 0),
                'licencia_sin_goce' => $estadosCount->get('LSG', 0),
                'licencia_paternidad' => $estadosCount->get('LP', 0),
                'licencia_maternidad' => $estadosCount->get('LM', 0),
                'licencia_fallecimiento' => $estadosCount->get('LF', 0),
				'sin_programacion' => $estadosCount->get('SP', 0),
                'suspension' => $estadosCount->filter(fn($count, $estado) => in_array($estado, ['S', 'SN', 'ST', 'SFI']))->sum(),
                'asistencia' => $estadosCount->get('L', 0),
                'total_pago' => $estadosCount->filter(fn($count, $estado) => in_array($estado, ['D', 'F', 'M', 'C', 'CA', 'CHE', 'LCG', 'LP', 'LM', 'LF', 'L']))->sum(),
                'total_100' => $empleadoHorarios->count(),
                // 'total_dias_trabajados' => 30 - $compensas - $estadosCount->filter(fn($count, $estado) => in_array($estado, ['M', 'FI', 'FJ', 'LCG', 'LSG', 'V', 'S']))->sum(),
                'total_dias_trabajados' => $empleadoHorarios->count(),
                'descuento' => $estadosCount->filter(fn($count, $estado) => in_array($estado, ['FI', 'FJ', 'LSG', 'S']))->sum(),
                'tardanza' => $tardanza,
                'horas' => $horas,
                'horasLaboradas' => $horasLaboradas,
                'horasExcedente' => $empleado->jornada_id == 2  ? ($dias == 7 ? $horas - 1410 : $horas - 5580) : ($dias == 7 ? $horas - 2880 : $horas - 14400),
                'extra_25' => $extra_25,
                'extra_35' => $extra_35,
                'anticipado' => $anticipado,
                'nocturno' => $nocturno,
            ];
        });

        return Inertia::render('reportes/tareo/index', [
            'filters' => $filters,
            'empresas' => $empresas,
            'areas' => $areas,
            'jornadas' => $jornadas,
            'tareos' => $lista,
            'csrf_token' => csrf_token(),
        ]);
    }

    public function tareoDownload(Request $request)
    {
        $data = $request->validate([
            'tareos' => 'required',
            'empresa' => 'required|integer|exists:empresas,id',
            'jornada' => 'required|integer|exists:jornadas,id',
            'area' => 'nullable|integer|exists:areas,id',
            'fechaInicio' => 'required|date',
            'fechaFin' => 'required|date|after_or_equal:fechaInicio',
        ]);

        return Excel::download(new TareoExport($data), 'reporte_tareo.xlsx');
    }

    public function tareoDownloadStarsoft(Request $request)
    {
        $data = $request->validate([
            'tareos' => 'required',
            'empresa' => 'required|integer|exists:empresas,id',
            'jornada' => 'required|integer|exists:jornadas,id',
            'area' => 'nullable|integer|exists:areas,id',
            'fechaInicio' => 'required|date',
            'fechaFin' => 'required|date|after_or_equal:fechaInicio',
        ]);

        return Excel::download(new TareoStarsoftExport($data), 'reporte_tareo_starsoft.xlsx');
    }

    public function amonestacionIndex(Request $request): Response
    {
        $filters = $request->validate([
            'empresa' => 'nullable|integer|exists:empresas,id',
            'area' => 'nullable|integer|exists:areas,id',
            'fechaInicio' => 'nullable|date',
            'fechaFin' => 'nullable|date|after_or_equal:fechaInicio',
        ]);

        $empresas = Empresa::where('estado', 1)->get(['id', 'razonsocial']);
        $areas = Area::where('estado', 1)->where('empresa_id', $request->empresa)->get(['id', 'nombre']);

        $lista = Suspension::whereHas('empleado', function ($query) use ($request) {
            $query->where('empresa_id', $request->empresa)
                ->when($request->area, fn($q) => $q->where('area_id', $request->area))
                ->whereNull('fecha_cese');
        })
            ->with('empleado.area')
            ->whereBetween('fecha', [$request->fechaInicio, $request->fechaFin])
            ->orderBy('estado')
            ->orderBy('fecha', 'desc')
            ->get()
            ->groupBy(function ($item) {
                if (str_starts_with($item->codigo, 'S')) {
                    return 'suspensiones';
                }
                return match (strtoupper($item->tipo)) {
                    'TARDANZA' => 'tardanzas',
                    'INCOMPLETO' => 'incompleto',
                    'REFRIGERIO' => 'refrigerio',
                    'NEGLIGENCIA' => 'negligencia',
                    'FALTA INJUSTIFICADA' => 'faltasInjustificadas',
                };
            });

        return Inertia::render('reportes/amonestaciones/index', [
            'filters' => $filters,
            'empresas' => $empresas,
            'areas' => $areas,
            'suspensiones' => $lista->get('suspensiones', collect()),
            'tardanzas' => $lista->get('tardanzas', collect()),
            'incompleto' => $lista->get('incompleto', collect()),
            'refrigerio' => $lista->get('refrigerio', collect()),
            'negligencia' => $lista->get('negligencia', collect()),
            'faltasInjustificadas' => $lista->get('faltasInjustificadas', collect()),
            'csrf_token' => csrf_token(),
        ]);
    }

    public function amonestacionDownload(Request $request)
    {
        $data = $request->validate([
            'amonestaciones' => 'required|json',
            'empresa' => 'required|integer|exists:empresas,id',
            'area' => 'nullable|integer|exists:empleados,id',
            'fechaInicio' => 'required|date',
            'fechaFin' => 'required|date|after_or_equal:fechaInicio',
        ]);

        return Excel::download(new AmonestacionExport($data), 'amonestaciones.xlsx');
    }

    public function compensaIndex(Request $request) //: Response
    {
        $filters = $request->validate([
            'empresa' => 'nullable|integer|exists:empresas,id',
            'encargado' => 'nullable|integer|exists:empleados,id',
            'fechaInicio' => 'nullable|date',
            'fechaFin' => 'nullable|date|after_or_equal:fechaInicio',
        ]);

        $empresas = Empresa::where('estado', 1)->get(['id', 'razonsocial']);
        $encargados = User::with('empleado')->where('estado', 1)->get()->sortBy(fn($encargado) => $encargado->empleado->apellidos)->values();

        $empleadoIds = Empleado::where('empresa_id', $request->empresa)
            ->when($request->encargado, fn($q) => $q->where('jefe_id', $request->encargado))
            ->whereNull('fecha_cese')
            ->pluck('id')
            ->unique();

        $feriadosDisponibles = Horario::whereIn('empleado_id', $empleadoIds) // fechas en las que el empleado a laborado
            ->with(['empleado.area', 'empleado.jornada', 'feriados'])
            ->where('estado', 'L')
            ->whereDate('fecha', '<=', now())
            ->get()
            ->groupBy('empleado_id')
            ->map(function ($horarios){
                $empleado = $horarios->first()->empleado;
                $fechasHorarios = $horarios->pluck('fecha');

                $feriados = Feriado::whereIn('fecha', $fechasHorarios)
                    ->whereDoesntHave('horarios', fn($q) => $q->where('empleado_id', $empleado->id))
                    ->select(['id', 'fecha', 'nombre'])
                    ->get();

                if ($feriados->isNotEmpty()) {
                    return [
                        'id' => $empleado->id,
                        'empleado' => $empleado->apellidos. ' ' .$empleado->nombres,
                        'dni' => $empleado->dni,
                        'fecha_ingreso' => $empleado->fecha_ingreso->format('d/m/Y'),
                        'area' => $empleado->area->nombre,
                        'jornada' => $empleado->jornada->nombre,
                        'feriados' => $feriados,
                    ];
                }
            })->filter()->values();

        $lista = Permiso::with(['empleado.area', 'empleado.jornada', 'tipo'])
            ->whereIn('empleado_id', $empleadoIds)
            ->whereBetween('fecha', [$request->fechaInicio, $request->fechaFin])
            ->whereIn('tipo_id', [4, 16])
            ->orderBy('estado')
            ->orderBy('fecha', 'desc')
            ->get()
            ->groupBy(function ($item) {

                return match ($item->tipo_id) {
                    4 => 'compensa',
                    16 => 'compensa_adelantada',
                };
            });

        return Inertia::render('reportes/compensas/index', [
            'filters' => $filters,
            'empresas' => $empresas,
            'encargados' => $encargados,
            'pendientes' => $feriadosDisponibles,
            'compensas' => $lista->get('compensa', collect()),
            'compensas_adelantadas' => $lista->get('compensa_adelantada', collect()),
            'csrf_token' => csrf_token(),
        ]);
    }

    public function compensaDownload(Request $request)
    {
        $data = $request->validate([
            'compensas' => 'sometimes|json',
            'compensas_adelantadas' => 'sometimes|json',
            'pendientes' => 'sometimes|json',
            'empresa' => 'required|integer|exists:empresas,id',
            'encargado' => 'nullable|integer|exists:empleados,id',
            'fechaInicio' => 'required|date',
            'fechaFin' => 'required|date|after_or_equal:fechaInicio',
        ]);

        $tipo = 'pendientes';
        if ($request->has('compensas')) {
            $tipo = 'compensas';
        } elseif ($request->has('compensas_adelantadas')) {
            $tipo = 'compensas_adelantadas';
        }

        return Excel::download(new CompensaExport($data, $tipo), 'compensas.xlsx');
    }

    public function extraIndex(Request $request)//: Response
    {
        $filters = $request->validate([
            'empresa' => 'nullable|integer|exists:empresas,id',
            'encargado' => 'nullable|integer|exists:empleados,id',
            'fechaInicio' => 'nullable|date',
            'fechaFin' => 'nullable|date|after_or_equal:fechaInicio',
        ]);

        $empresas = Empresa::where('estado', 1)->get(['id', 'razonsocial']);
        $encargados = User::with('empleado')->where('estado', 1)->get()->sortBy(fn($encargado) => $encargado->empleado->apellidos)->values();
        $empleados = Empleado::query()
            ->when($request->user()->rol_id == 4, fn($q) => $q->where('jefe_id', $request->user()->empleado_id))
            ->select('empleados.id', 'dni', 'nombres', 'apellidos', 'area_id', 'jornada_id', 'empresa_id', 'fecha_ingreso')
            ->with(['area:id,nombre', 'jornada:id,nombre', 'horarios' => function ($q) use ($request) {
                $q->whereBetween('fecha', [$request->fechaInicio, $request->fechaFin]);
            }, 'marcaciones' => function ($q) use ($request) {
                $q->whereBetween('fecha', [$request->fechaInicio, $request->fechaFin]);
            }])
            ->where('empresa_id', $request->empresa)
            // ->when($request->area, fn($query) => $query->where('area_id', $request->area))
            // ->where('jornada_id', $request->jornada)
            ->when($request->fechaFin, function ($query) use ($request) {
                $query->whereDate('fecha_ingreso', '<=', $request->fechaFin);
            })
            ->whereNull('fecha_cese')
            ->orderBy('apellidos')
            ->get();

        $pendientes = collect();
        $revision = collect();
        $aprobados = collect();

        $empleados->map(function ($empleado) use (&$pendientes, &$revision, &$aprobados){
            $empleadoMarcaciones = $empleado->marcaciones ?? collect();
            $horas = 0;
            $extra = 0;
            $estados_extras = [];

            // hallar horas, tardanza, extra, ....
            $empleadoMarcaciones->each(function ($marcacion) use ($empleado, &$horas, &$extra, &$estados_extras) {
                $horario = $empleado->horarios->firstWhere('fecha', $marcacion->fecha);
                $partTime = $empleado->jornada_id == 2 && !$marcacion->ingreso_refri; // se valida si se trata de partime y no tomo su refrigerio

                if ($horario && $marcacion->ingreso && $marcacion->salida) {
                    // $horasTrabajadas = max(0, $horario->ingreso->diffInMinutes($empleado->jornada_id == 2 ? $horario->salida : $marcacion->salida, false));
                    // $horasTardanza = max(0, $horario->ingreso->diffInMinutes($marcacion->ingreso, false)); // si es negativo devuelve 0
                    // $descansoMedico = $horario->estado == 'M' && $empleado->jornada_id == 2 ? 240 : 0; // solo para part-time

                    // $horas += (abs($horasTrabajadas) - abs($horasTardanza) - ($partTime ? 0 : 60) + $descansoMedico) * 0; // no se descuenta la hora de refrigerio si es parttime y si no tomo refrigerio
                    $extra += max(0, $horario->salida->diffInMinutes($marcacion->salida, false)); // tiempo despues de su hora de salida
                    $estados_extras[] = $marcacion->estado_horas_extra;
                }
            });

            $estadoFinal = null;
            if (!empty($estados_extras)) {
                if (in_array(0, $estados_extras)) {
                    $estadoFinal = 'pendientes';
                } elseif (in_array(2, $estados_extras)) {
                    $estadoFinal = 'revision';
                } else {
                    $estadoFinal = 'aprobados';
                }
            }

            if ($extra > 0) {
                $item = [
                    'empleado' => $empleado,
                    'horas' => 0,
                    'extra' => $extra,
                    'estado' => $estadoFinal
                ];

                // Clasificar según el estado
                if ($estadoFinal === 'pendientes') {
                    $pendientes->push($item);
                } elseif ($estadoFinal === 'revision') {
                    $revision->push($item);
                } elseif ($estadoFinal === 'aprobados') {
                    $aprobados->push($item);
                }
            }
        });

        return Inertia::render('reportes/extra/index', [
            'filters' => $filters,
            'empresas' => $empresas,
            'encargados' => $encargados,
            'pendientes' => $pendientes,
            'revision' => $revision,
            'aprobados' => $aprobados,
            'csrf_token' => csrf_token(),
        ]);
    }

    public function extraDownload(Request $request)
    {
        $data = $request->validate([
            'aprobados' => 'sometimes|json',
            'revision' => 'sometimes|json',
            'pendientes' => 'sometimes|json',
            'empresa' => 'required|integer|exists:empresas,id',
            'encargado' => 'nullable|integer|exists:empleados,id',
            'fechaInicio' => 'required|date',
            'fechaFin' => 'required|date|after_or_equal:fechaInicio',
        ]);

        return Excel::download(new HorasExtraExport($data), 'reporte_horas_extra.xlsx');
    }

}
