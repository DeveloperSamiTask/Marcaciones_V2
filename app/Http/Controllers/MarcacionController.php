<?php

namespace App\Http\Controllers;

use App\Exports\MarcacionExport;
use App\Http\Requests\Marcacion\StoreMarcacionRequest;
use App\Http\Requests\Marcacion\UpdateMarcacionRequest;
use App\Models\Empleado;
use App\Models\Empresa;
use App\Models\Horario;
use App\Models\Marcacion;
use App\Models\MarcacionEdicion;
use App\Models\Permiso;
use App\Models\User;
use App\Models\Zktimems;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;

class MarcacionController extends Controller
{
    public function index(Request $request)// : Response
    {
        $filters = $request->validate([
            'empresa' => 'nullable|integer|exists:empresas,id',
            'encargado' => 'nullable|integer|exists:empleados,id',
            'fechaInicio' => 'nullable|date',
            'fechaFin' => 'nullable|date|after_or_equal:fechaInicio',
        ]);

        $empresas = Empresa::where('estado', 1)->get(['id', 'razonsocial']);
        $encargados = User::with('empleado')->where('estado', true)->get()->sortBy(fn ($encargado) => $encargado->empleado->apellidos)->values();

        $empleados = Empleado::query()
            ->select('empleados.id', 'dni', 'nombres', 'apellidos', 'area_id', 'jornada_id', 'empresa_id')
            ->with(['area:id,nombre', 'jornada:id,nombre'])
            ->where('empresa_id', $request->empresa)
            ->when($request->encargado, fn ($query) => $query->where('jefe_id', $request->encargado))
            ->when($request->fechaFin, function ($query) use ($request) {
                $query->whereDate('fecha_ingreso', '<=', $request->fechaFin);
            })
            ->whereNull('fecha_cese')
            ->orderBy('apellidos')
            ->get();

        $horarios = Horario::whereIn('empleado_id', $empleados->pluck('id'))
            ->whereBetween('fecha', [$request->fechaInicio, $request->fechaFin])
            ->get()
            ->groupBy('empleado_id');

        // horario
        $horariosExtra = Horario::whereIn('empleado_id', $empleados->pluck('id'))
            ->whereNotNull('extra')
            ->get()
            ->groupBy('empleado_id');

        // de aqui sale la hora de salida
        $marcaciones = Marcacion::whereIn('empleado_id', $empleados->pluck('id'))
            ->whereBetween('fecha', [$request->fechaInicio, $request->fechaFin])
            ->get()
            ->groupBy('empleado_id');

        // Evitamos que se agarren dos marcaciones -> Aplicar a HE , Tareo , etc
        $marcaciones = $marcaciones->map(function ($grupo) {
            return $grupo->unique('fecha');
        });

        $permisos = \App\Models\Permiso::whereIn('empleado_id', $empleados->pluck('id'))
            ->whereBetween('fecha', [$request->fechaInicio, $request->fechaFin])
            ->where('tipo_id', 4) // <--- Filtramos solo por Compensaciones
            ->get()
            ->groupBy('empleado_id');

        // \Log::info('Permisos Tipo 4 encontrados: '.$permisos->flatten()->count());

        $lista = $empleados->flatMap(function ($empleado) use ($horarios, $horariosExtra, $marcaciones, $request, $permisos) {
            $fechas = CarbonPeriod::create($request->fechaInicio, $request->fechaFin);

            return collect($fechas)->map(function ($fecha) use ($empleado, $horarios, $horariosExtra, $marcaciones, $permisos) {

                $fechaStr = $fecha->format('Y-m-d');
                $horario = $horarios->get($empleado->id)?->firstWhere('fecha', $fecha);
                $horarioExtra = $horariosExtra->get($empleado->id);
                $marcacion = $marcaciones->get($empleado->id)?->firstWhere('fecha', $fecha);

                $permisoFila = null;
                $refriEnOrigen = false;

                if ($permisos->has($empleado->id)) {
                    $permisoFila = $permisos->get($empleado->id)->first(function ($p) use ($fechaStr) {
                        return \Carbon\Carbon::parse($p->fecha)->format('Y-m-d') === $fechaStr;
                    });

                    // SI HAY PERMISO, BUSCAMOS SI EN ESA FECHA DE ORIGEN HUBO REFRIGERIO
                    if ($permisoFila) {
                        // Extraemos la fecha del motivo (ej: "09/12/2025")
                        preg_match('/(\d{2}\/\d{2}\/\d{4})/', $permisoFila->motivo, $matches);
                        if (isset($matches[1])) {
                            $fechaOrigen = \Carbon\Carbon::createFromFormat('d/m/Y', $matches[1])->format('Y-m-d');

                            // Buscamos la marcación de esa fecha específica
                            $marcacionOrigen = \App\Models\Marcacion::where('empleado_id', $empleado->id)
                                ->whereDate('fecha', $fechaOrigen)
                                ->first();

                            $refriEnOrigen = $marcacionOrigen && $marcacionOrigen->ingreso_refri ? true : false;
                        }
                    }
                }

                // LOG DE CADA FILA (Opcional, solo para debug)
                /*
                 if ($permisoFila) {
                    \Log::info("Empleado {$empleado->apellidos} tiene permiso el {$fechaStr}: ".$permisoFila->motivo);
                }
                */

                $tardanza = 0;
                $horas = 0;
                $extra = 0;
                $anticipado = 0;
                $nocturno = 0;

                if ($horario && $marcacion && $marcacion->ingreso) {
                    // Mantengo tu variable partTime por si la usas en otro lado,
                    // pero la lógica de descuento ahora es más precisa abajo.

                    // --- 🚨 VALIDACIÓN TD: EVITAR EXTRAS EN DESCANSO ---
                    $hip_check = $horario->ingreso->format('H:i');
                    $hsp_check = $horario->salida->format('H:i');

                    if ($hip_check === '00:00' && $hsp_check === '00:00') {
                        // Es un día de descanso. Retornamos todo en 0 y terminamos este ciclo.

                        return [
                            'empleado' => $empleado,
                            'fecha' => $fecha,
                            'fecha_db' => $fechaStr,
                            'horario' => $horario,
                            'horariosExtra' => $horarioExtra,
                            'marcacion' => $marcacion,
                            'permiso' => $permisoFila,
                            'refri_en_origen' => $refriEnOrigen,
                            'horas' => 0,
                            'tardanza' => 0,
                            'extra' => 0,
                            'anticipado' => 0,
                            'nocturno' => 0,
                        ];
                    }

                    $partTime = $empleado->jornada_id == 2 && ! $marcacion->ingreso_refri;

                    // --- HIP y HSP (Programado) ---
                    $h_ingreso = $horario->ingreso->copy();
                    $h_salida = $horario->salida->copy();

                    // Regla: Sumar 24h si cruza medianoche
                    if ($h_salida->lt($h_ingreso)) {
                        $h_salida->addDay();
                    }

                    // --- HI y HS (Real) ---
                    $m_ingreso = $marcacion->ingreso->copy();
                    $m_salida = $marcacion->salida ? $marcacion->salida->copy() : null;
                    if ($m_salida && $m_salida->lt($m_ingreso)) {
                        $m_salida->addDay();
                    }

                    // --- CÁLCULOS SEGÚN TU TABLA ---

                    // 1. CÁLCULO DE TARDANZA (Necesario antes que las horas para restar correctamente)
                    $tardanza = max(0, $h_ingreso->diffInMinutes($m_ingreso, false));

                    // 2. LÓGICA DE REFRIGERIO (Reglas FT y PT)
                    $minutosProgramados = $h_ingreso->diffInMinutes($h_salida, false);
                    $descuentoRefri = 0;

                    if ($empleado->jornada_id == 1) {
                        // Regla FT: Descuenta 1h si el programado es >= 6h

                        $descuentoRefri = 60;

                    } else {
                        // Regla PT: Descuenta 1h SOLO si marcó refrigerio (entrada o salida)
                        if ($marcacion->ingreso_refri || $marcacion->salida_refri) {
                            $descuentoRefri = 60;
                        }
                    }

                    // 3. TOTAL HORAS TRABAJADAS (Refactorizado)
                    // Fórmula: Programado - Descuento Refri - Tardanza
                    $horas = max(0, $minutosProgramados - $descuentoRefri);

                    // 4. EXTRA y ANTICIPADO
                    if ($m_salida) {
                        // EXTRA: HS - HSP
                        $extra = max(0, $h_salida->diffInMinutes($m_salida, false));
                        // ANTICIPADO: HSP - HS
                        $horasAnticipado = max(0, $m_salida->diffInMinutes($h_salida, false));
                    } else {
                        $extra = 0;
                        $horasAnticipado = 0;
                    }

                    $anticipado = $horasAnticipado;

                    // Tolerancia anticipado
                    if ((in_array($h_salida->format('H:i'), ['23:00', '23:30', '23:59']) && ($empleado->empresa_id == 4 || $empleado->empresa_id == 3)) || ($h_salida->format('H:i') == '18:30' && $empleado->empresa_id == 1)) {
                        $minutosTolerancia = $empleado->empresa_id == 1 ? 30 : 20;
                        $anticipado = $horasAnticipado >= $minutosTolerancia ? $horasAnticipado : 0;
                    }

                    // 4. Cálculo de Nocturno (Dinámico: lo que realmente trabajó entre 22:00 y 06:00)
                    // 4. NOCTURNO: Basado en PROGRAMACIÓN (Regla Xiomara)
                    // 4. NOCTURNO: (Salida Programada - 22:00) + CANDADO DE REALIDAD
                    if (in_array($empleado->empresa_id, [1, 3, 4])) {

                        // 1. Definimos la ventana legal (10 PM a 6 AM)
                        $inicioVentana = $m_ingreso->copy()->setTime(22, 0, 0);
                        $finVentana = $m_ingreso->copy()->addDay()->setTime(6, 0, 0);

                        // 2. Preparamos la Salida Programada (HSP)
                        $solo_hora_salida = Carbon::parse($horario->salida)->format('H:i:s');
                        $h_salida_prog = Carbon::parse($m_ingreso->format('Y-m-d').' '.$solo_hora_salida);

                        // Si la HSP es de madrugada, le sumamos el día
                        if ($h_salida_prog->hour < 10) {
                            $h_salida_prog->addDay();
                        }

                        // --- VALIDACIÓN: Solo calculamos si hay salida real y está después de las 10 PM ---
                        if (! $m_salida || $m_salida->lte($inicioVentana)) {
                            $nocturno = 0;
                        } else {
                            // --- CAMBIO PRINCIPAL: Usamos SOLO la hora programada, no la real ---

                            // Inicio: El punto más tarde entre su entrada real y las 10:00 PM
                            $inicioConteo = $m_ingreso->gt($inicioVentana) ? $m_ingreso : $inicioVentana;

                            // Fin: SIEMPRE usamos la salida PROGRAMADA (no la real)
                            $finConteo = $h_salida_prog;

                            // El fin tampoco puede pasarse de las 6 AM
                            if ($finConteo->gt($finVentana)) {
                                $finConteo = $finVentana;
                            }

                            // Calculamos los minutos nocturnos según la programación
                            if ($inicioConteo->lt($finConteo)) {
                                $nocturno = $inicioConteo->diffInMinutes($finConteo);
                                $nocturno = floor($nocturno / 30) * 30;
                            } else {
                                $nocturno = 0;
                            }
                        }
                    }
                }

                return [
                    'empleado' => $empleado,
                    'fecha' => $fecha,
                    'horario' => $horario,
                    'horariosExtra' => $horarioExtra,
                    'marcacion' => $marcacion,
                    'permiso' => $permisoFila,
                    'refri_en_origen' => $refriEnOrigen,
                    'horas' => $horas,
                    'tardanza' => $tardanza,
                    'extra' => $extra,
                    'anticipado' => $anticipado,
                    'nocturno' => $nocturno,
                ];
            });
        });

        return Inertia::render('marcaciones/index', [
            'marcaciones' => $lista,
            'empresas' => $empresas,
            'encargados' => $encargados,
            'filters' => $filters,
            'csrf_token' => csrf_token(),
        ]);
    }

    public function real(Request $request)
    {
        $filters = $request->validate([
            'empresa' => 'nullable|integer|exists:empresas,id',
            'encargado' => 'nullable|integer|exists:empleados,id',
            'fechaInicio' => 'nullable|date',
            'fechaFin' => 'nullable|date|after_or_equal:fechaInicio',
        ]);

        $user = $request->user();
        $encargados = User::with('empleado')->where('estado', true)->get()->sortBy(fn ($encargado) => $encargado->empleado->apellidos)->values();

        // PASO 1: FILTRAR EMPRESAS SEGÚN USUARIO
        if ($user->name === 'ANGELES TERRONES MILUSKA') {
            $empresas = Empresa::where('estado', 1)
                ->whereIn('id', [4, 10, 11])
                ->get(['id', 'razonsocial']);

            $empresaFiltro = $request->empresa && in_array($request->empresa, [4, 10, 11])
                ? $request->empresa
                : ($empresas->first()->id ?? null);

            // CONSULTA SEPARADA PARA MILUSKA - SIN FILTRO DE ENCARGADO
            $empleadosDnis = Empleado::where('empresa_id', $empresaFiltro)
                ->whereNull('fecha_cese')
                ->pluck('dni');

        } elseif ($user->id === 73) {
            // USUARIO ID 73 SOLO VE EMPRESAS 1 Y 5
            $empresas = Empresa::where('estado', 1)
                ->whereIn('id', [1, 5])
                ->get(['id', 'razonsocial']);

            $empresaFiltro = $request->empresa && in_array($request->empresa, [1, 5])
                ? $request->empresa
                : ($empresas->first()->id ?? null);

            // CONSULTA PARA USUARIO 73 - SIN FILTRO DE ENCARGADO
            $empleadosDnis = Empleado::where('empresa_id', $empresaFiltro)
                ->whereNull('fecha_cese')
                ->pluck('dni');

        } else {
            $empresas = Empresa::where('estado', 1)->get(['id', 'razonsocial']);

            // CONSULTA NORMAL PARA OTROS USUARIOS - CON FILTRO DE ENCARGADO
            $empleadosDnis = Empleado::where('empresa_id', $request->empresa)
                ->when($request->encargado, fn ($query) => $query->where('jefe_id', $request->encargado))
                ->when($request->fechaFin, function ($query) use ($request) {
                    $query->whereDate('fecha_ingreso', '<=', $request->fechaFin);
                })
                ->whereNull('fecha_cese')
                ->pluck('dni');
        }

        /*$marcaciones = Zktimems::query()
            ->with(['empleado' => function ($query) {
                $query->select('id', 'dni', 'nombres', 'apellidos');
            }])
            ->whereIn('tarjeta', $empleadosDnis)
            ->whereBetween('fecha', [$request->fechaInicio, $request->fechaFin])
            ->get(['hora', 'tarjeta', 'fecha']);*/

        $fechaFinBusqueda = \Carbon\Carbon::parse($request->fechaFin)->addDay()->toDateString();

        $marcaciones = Zktimems::query()
            ->with(['empleado' => function ($query) {
                $query->select('id', 'dni', 'nombres', 'apellidos');
            }])
            ->whereIn('tarjeta', $empleadosDnis)
            ->whereBetween('fecha', [$request->fechaInicio, $fechaFinBusqueda])
            ->get(['hora', 'tarjeta', 'fecha'])
            ->map(function ($item) {
                // Mantenemos tu lógica: < 05:00 AM se mueve al día anterior
                $h = \Carbon\Carbon::parse($item->hora);

                if ($h->hour < 5) {
                    $item->fecha = \Carbon\Carbon::parse($item->fecha)->subDay()->format('Y-m-d');
                } else {
                    $item->fecha = \Carbon\Carbon::parse($item->fecha)->format('Y-m-d');
                }

                return $item;
            })
            // 1. Filtramos por la fecha ya corregida (Lógica)
            ->filter(function ($item) use ($request) {
                return $item->fecha >= $request->fechaInicio && $item->fecha <= $request->fechaFin;
            })
            // 2. Ordenamiento Maestro para que RRHH no se queje
            ->sort(function ($a, $b) {
                // Si las fechas son distintas, orden normal de fecha
                if ($a->fecha !== $b->fecha) {
                    return strcmp($a->fecha, $b->fecha);
                }

                // Si es la misma fecha lógica, aplicamos el truco de la hora virtual
                // Las 00:06 se convierten en "24:00:06" para que vayan DESPUÉS de las 21:00
                $horaA = ($a->hora < '05:00:00') ? '24:'.$a->hora : $a->hora;
                $horaB = ($b->hora < '05:00:00') ? '24:'.$b->hora : $b->hora;

                return strcmp($horaA, $horaB);
            })
            ->values(); // Resetear índices para que Inertia no mande un objeto raro al frontend

        return Inertia::render('marcaciones/reales/index', [
            'marcaciones' => $marcaciones,
            'empresas' => $empresas,
            'encargados' => $encargados,
            'filters' => $filters,
            'csrf_token' => csrf_token(),
        ]);
    }

    /* Enviar las marcaciones a asistencias */
    public function store(StoreMarcacionRequest $request)
    {
        $data = $request->validated();
        try {
            DB::transaction(function () use ($data) {
                $marcacion = Marcacion::updateOrCreate(
                    [
                        'empleado_id' => $data['empleado_id'],
                        'fecha' => $data['fecha'], // Asegura formato YYYY-MM-DD
                    ],
                    [
                        $data['tipo'] => $data['hora'],
                    ]
                );

                MarcacionEdicion::create([
                    'empleado_id' => $marcacion->empleado_id,
                    'user_id' => Auth::user()->id,
                    'fecha' => $marcacion->fecha,
                    'hora_original' => $marcacion->{$data['tipo']},
                    'hora' => $data['hora'],
                    'motivo' => $data['motivo'],
                ]);
                $horarios = Horario::where('empleado_id', $marcacion->empleado_id)->whereNotNull('extra')->get();
                if ($horarios->count() > 0) { // validar si hay horas extra registradas
                    $horarioExtra = Horario::where('fecha', $marcacion->fecha)->where('empleado_id', $marcacion->empleado_id)->first();
                    $marcacionExtra = $horarioExtra->salida->diffInMinutes($marcacion->salida);

                    foreach ($horarios as $horario) {
                        // Verificar si tenemos suficiente  tiempo en el registro actual para restar
                        if ($horario->extra) {
                            $extraTime = $horario->extra;

                            if (Carbon::today()->diffInMinutes($horario->extra) > $marcacionExtra) {
                                // Si el valor de 'extra' es mayor que lo que resta, simplemente resta
                                $horario->extra = $extraTime->subMinutes($marcacionExtra);
                                $horario->save();
                                break; // Ya no necesitamos seguir iterando, porque hemos restado todo
                            } else {
                                // Si el valor de 'extra' es menor que lo que resta, ponlo a null
                                $marcacionExtra -= Carbon::today()->diffInMinutes($horario->extra); // Restamos lo que queda
                                $horario->extra = null; // Ponemos 'extra' en null
                                $horario->save();
                            }
                        }
                    }

                    if ($marcacionExtra > 0 && $horario->extra) {
                        $ultimoHorario = $horarios->last();
                        $ultimoHorario->extra = $extraTime->subMinutes($marcacionExtra); // Restamos lo que queda, asegurándonos de no pasar de cero
                        $ultimoHorario->save();
                    }
                }
            });
        } catch (Exception $e) {
            return back()->withErrors(['message' => $e->getMessage()]);
        }
    }

    public function update(UpdateMarcacionRequest $request, Marcacion $marcacione)
    {
        $data = $request->validated();
        try {
            DB::transaction(function () use ($data, $marcacione) {
                MarcacionEdicion::create([
                    'empleado_id' => $marcacione->empleado_id,
                    'user_id' => Auth::user()->id,
                    'fecha' => $marcacione->fecha,
                    'hora_original' => $marcacione->{$data['tipo']},
                    'hora' => $data['hora_nueva'],
                    'motivo' => $data['motivo'],
                ]);
                $marcacione->update([$data['tipo'] => $data['hora_nueva']]);
                $horarios = Horario::where('empleado_id', $marcacione->empleado_id)->whereNotNull('extra')->get();

                if ($horarios->count() > 0) { // validar si hay horas extra registradas
                    $horarioExtra = Horario::where('fecha', $marcacione->fecha)->where('empleado_id', $marcacione->empleado_id)->first();
                    $marcacionExtra = $horarioExtra->salida->diffInMinutes($marcacione->salida);

                    foreach ($horarios as $horario) {
                        // Verificar si tenemos suficiente  tiempo en el registro actual para restar
                        if ($horario->extra) {
                            $extraTime = $horario->extra;
                            if (Carbon::today()->diffInMinutes($horario->extra) > $marcacionExtra) {
                                // Si el valor de 'extra' es mayor que lo que resta, simplemente resta
                                $horario->extra = $extraTime->subMinutes($marcacionExtra);
                                $horario->save();
                                break; // Ya no necesitamos seguir iterando, porque hemos restado todo
                            } else {
                                // Si el valor de 'extra' es menor que lo que resta, ponlo a null
                                $marcacionExtra -= Carbon::today()->diffInMinutes($horario->extra); // Restamos lo que queda
                                $horario->extra = null; // Ponemos 'extra' en null
                                $horario->save();
                            }
                        }
                    }

                    if ($marcacionExtra > 0 && $horario->extra) {
                        $ultimoHorario = $horarios->last();
                        $ultimoHorario->extra = $extraTime->subMinutes($marcacionExtra); // Restamos lo que queda, asegurándonos de no pasar de cero
                        $ultimoHorario->save();
                    }
                }

            });
        } catch (Exception $e) {
            return back()->withErrors(['message' => $e->getMessage()]);
        }
    }

    public function getHorasExtraDisponibles(Request $request, $empleadoId)
    {
        $inicio = $request->query('fechaInicio');
        $fin = $request->query('fechaFin');

        \Log::emergency('=== FUNCIÓN EJECUTÁNDOSE ===');
        \Log::emergency('Empleado ID: '.$empleadoId);
        \Log::emergency('Inicio: '.$inicio);
        \Log::emergency('Fin: '.$fin);

        $extras = \DB::table('marcacions as m')
            ->join('horarios as h', function ($join) {
                $join->on('h.fecha', '=', 'm.fecha')
                    ->on('h.empleado_id', '=', 'm.empleado_id');
            })
            ->where('m.empleado_id', $empleadoId)
            //->where('h.validado', 1)
            ->where('m.estado_horas_extra', 1)
            //->where('m.estado', 1)
            ->whereNotNull('m.salida')
            ->when($inicio && $fin, function ($q) use ($inicio, $fin) {
                return $q->whereBetween('m.fecha', [$inicio, $fin]);
            })
            ->select(
                'm.id',
                'm.fecha',
                'h.ingreso as h_ingreso',
                'h.salida as h_salida',
                'm.salida as m_salida'
            )
            ->get();

        \Log::emergency('Registros obtenidos: '.$extras->count());

        $extrasProcesadas = $extras->map(function ($registro) {
            $hIngresoProg = \Carbon\Carbon::parse($registro->h_ingreso);
            $hSalidaProg = \Carbon\Carbon::parse($registro->h_salida);
            $mSalidaReal = \Carbon\Carbon::parse($registro->m_salida);

            if ($hIngresoProg->format('H:i') === '00:00' && $hSalidaProg->format('H:i') === '00:00') {
                return null;
            }

            if ($hSalidaProg->lte($hIngresoProg)) {
                $hSalidaProg->addDay();
            }

            if ($mSalidaReal->hour < $hIngresoProg->hour) {
                $mSalidaReal->addDay();
            }

            $diff = $hSalidaProg->diffInMinutes($mSalidaReal, false);

            if ($diff >= 1440) {
                $diff -= 1440;
            }
            if ($diff <= -1440) {
                $diff += 1440;
            }

            $minutosExtra = $diff > 0 ? $diff : 0;
            $ajustado = floor($minutosExtra / 30) * 30;

            \Log::emergency("Fecha {$registro->fecha}: diff={$diff}, extra={$minutosExtra}, ajustado={$ajustado}");

            return [
                'id' => $registro->id,
                'fecha' => \Carbon\Carbon::parse($registro->fecha)->format('Y-m-d'),
                'extra' => (int) $ajustado,
            ];
        })
            ->filter(fn ($item) => $item !== null && $item['extra'] >= 30)
            ->values();

        \Log::emergency('Extras procesadas: '.$extrasProcesadas->count());

        return response()->json($extrasProcesadas);
    }

    public function edicion(Request $request): Response
    {
        $filters = $request->validate([
            'empresa' => 'nullable|integer|exists:empresas,id',
            'tipo' => 'nullable|string|in:tardanza,refrigerio,incompleto',
            'fechaInicio' => 'nullable|date',
            'fechaFin' => 'nullable|date|after_or_equal:fechaInicio',
        ]);
        $empresas = Empresa::where('estado', 1)->get(['id', 'razonsocial']);

        $marcaciones = MarcacionEdicion::whereHas('empleado', function ($query) use ($request) {
            $query->where('empresa_id', $request->empresa);
        })
            ->with(['empleado', 'user'])
            ->whereBetween('fecha', [$request->fechaInicio, $request->fechaFin])
            ->get();

        return Inertia::render('marcaciones/ediciones/index', [
            'filters' => $filters,
            'empresas' => $empresas,
            'marcaciones' => $marcaciones,
        ]);
    }

    public function upload(Request $request, Marcacion $marcacion)
    {
        $request->validate([
            'sustento' => 'required|file|mimes:pdf,jpeg,png,jpg|max:2048',
        ]);

        try {
            DB::transaction(function () use ($marcacion, $request) {
                if ($request->hasFile('sustento')) { // verificamos que haya un archivo comrpobante
                    $file = $request->file('sustento');
                    // $path = Storage::put('comprobantes', $file);
                    $path = $file->store('asistencia/'.$marcacion->id, 'public'); // Almacenar el archivo en la carpeta public del storage
                    $marcacion->update(['sustento' => "storage/$path"]);
                }
            });
        } catch (Exception $e) {
            return back()->withInput()->withErrors(['message' => $e->getMessage()]);
        }
    }

    /*jala las marcas del reloj desde una fecha dada , identifica HI, HS , HRF
     y las convierte en registros oficiales de asistencia en la tabla marcaciones*/

    public function pull(Request $request)
    {
        $data = $request->validate([
            'empresa' => 'required|integer|exists:empresas,id',
            'fecha' => 'required|date',
        ]);

        try {
            DB::transaction(function () use ($data) {
                $dnis = Empleado::where('empresa_id', $data['empresa'])
                    ->whereNull('fecha_cese')
                    ->pluck('id', 'dni');

                // 1. AMPLIAMOS EL RANGO: Traemos hasta el día siguiente
                // para no perder las salidas de madrugada (como la de las 00:29)
                $fechaInicio = $data['fecha'];
                $fechaFinQuery = \Carbon\Carbon::parse($data['fecha'])->addDay()->toDateString();

                $horarios = Horario::whereIn('empleado_id', $dnis->values())
                    ->whereBetween('fecha', [$fechaInicio, $fechaFinQuery])
                    ->get()
                    ->keyBy(fn ($h) => $h->fecha->format('Y-m-d').'-'.$h->empleado_id);

                Zktimems::whereBetween('fecha', [$fechaInicio, $fechaFinQuery])
                    ->whereIn('tarjeta', $dnis->keys())
                    ->get(['tarjeta', 'fecha', 'hora'])
                    ->map(function ($item) use ($dnis) {
                        $empId = $dnis->get($item->tarjeta);
                        // NORMALIZACIÓN: Si marca < 05:00 AM, pertenece al día anterior
                        if ($item->hora < '05:00:00') {
                            $fLogica = \Carbon\Carbon::parse($item->fecha)->subDay()->format('Y-m-d');
                        } else {
                            $fLogica = $item->fecha->format('Y-m-d');
                        }
                        $item->logica_key = $fLogica.'-'.$empId;
                        $item->f_logica = $fLogica;
                        $item->emp_id = $empId;

                        return $item;
                    })
                    // Agrupamos por la fecha "lógica" (donde la madrugada ya se movió atrás)
                    ->groupBy('logica_key')
                    ->each(function ($items, $key) use ($horarios, $data) {
                        // 1. Extraemos los datos que ya calculamos en el map
                        $fechaLogica = $items->first()->f_logica;
                        $empleadoId = $items->first()->emp_id;

                        // 2. CORRECCIÓN: Filtramos para procesar solo el día solicitado
                        // Usamos $data['fecha'] que ahora sí entra gracias al 'use'
                        if ($fechaLogica !== $data['fecha']) {
                            return;
                        }

                        // 3. Limpieza de marcas (evitar duplicados de segundos)
                        $todas = $items->pluck('hora')->sort()->values();
                        $horas = collect();
                        foreach ($todas as $h) {
                            if ($horas->isEmpty() || \Carbon\Carbon::parse($h)->diffInMinutes(\Carbon\Carbon::parse($horas->last())) > 1) {
                                $horas->push($h);
                            }
                        }

                        // --- LÓGICA DE ASIGNACIÓN ---
                        $ingreso = null;
                        $salida = null;
                        $ingreso_refri = null;
                        $salida_refri = null;

                        $count = $horas->count();
                        if ($count >= 1) {
                            $ingreso = $horas->first();
                        }

                        if ($count == 2) {
                            $salida = $horas->last();
                        } elseif ($count == 3) {
                            $ingreso_refri = $horas->get(1);
                            $salida = $horas->get(2);
                        } elseif ($count >= 4) {
                            // Para Zarzosa: 15:03, 16:38, 17:37, 00:29
                            $ingreso_refri = $horas->get(1); // 16:38
                            $salida_refri = $horas->get(2);  // 17:37
                            $salida = $horas->last();        // 00:29 (Última marca de la jornada)
                        }

                        // --- GUARDADO ---
                        $marcacion = Marcacion::firstOrCreate(
                            ['empleado_id' => $empleadoId, 'fecha' => $fechaLogica]
                        );

                        if ($marcacion->estado == 0) {
                            $marcacion->update([
                                'ingreso' => $ingreso ?? $marcacion->ingreso,
                                'salida' => $salida ?? $marcacion->salida,
                                'ingreso_refri' => $ingreso_refri ?? $marcacion->ingreso_refri,
                                'salida_refri' => $salida_refri ?? $marcacion->salida_refri,
                            ]);
                        }

                        // Lógica de Permiso por día de descanso
                        $horario = $horarios->get($key);
                        if ($horario && $horario->estado === 'D' && ($ingreso || $salida)) {
                            Permiso::firstOrCreate([
                                'empleado_id' => $empleadoId,
                                'tipo_id' => 24,
                                'fecha' => $fechaLogica,
                                'estado' => 0,
                            ], ['motivo' => 'TRABAJO EN DIA DE DESCANSO']);
                        }
                    });
            });

            return back()->with('success', 'Sincronización exitosa. Zarzosa ya tiene su salida de las 00:29.');
        } catch (Exception $e) {
            return back()->withInput()->withErrors(['message' => $e->getMessage()]);
        }
    }

    public function download(Request $request)
    {
        $data = $request->validate([
            'marcaciones' => 'required',
            'empresa' => 'required|integer|exists:empresas,id',
            'encargado' => 'nullable|integer|exists:empleados,id',
            'fechaInicio' => 'required|date',
            'fechaFin' => 'required|date|after_or_equal:fechaInicio',
        ]);

        return Excel::download(new MarcacionExport($data), 'marcaciones.xlsx');

    }
}
