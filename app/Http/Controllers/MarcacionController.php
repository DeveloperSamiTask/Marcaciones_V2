<?php

namespace App\Http\Controllers;

use App\Exports\MarcacionExport;
use App\Http\Requests\Marcacion\StoreMarcacionRequest;
use App\Models\Area;
use App\Models\Empleado;
use App\Models\Empresa;
use App\Models\Horario;
use App\Models\Jornada;
use App\Models\Marcacion;
use App\Models\MarcacionEdicion;
use App\Models\Permiso;
use App\Models\ReporteHeConsumida;
use App\Models\User;
use App\Models\Zktimems;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;

class MarcacionController extends Controller
{
    public function index(Request $request)
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
            ->select('empleados.id', 'dni', 'nombres', 'apellidos', 'area_id', 'jornada_id', 'empresa_id', 'fecha_ingreso')
            ->with(['area:id,nombre', 'jornada:id,nombre'])
            ->where('empresa_id', $request->empresa)
            ->when($request->encargado, fn ($query) => $query->where('jefe_id', $request->encargado))
            ->when($request->fechaFin, function ($query) use ($request) {
                $query->whereDate('fecha_ingreso', '<=', $request->fechaFin);
            })
            ->whereNull('fecha_cese')
            ->orderBy('apellidos')
            ->get();

        $empleadosIds = $empleados->pluck('id');

        // 2. CORRECCIÓN CLAVE PARA HORARIOS:
        // Buscamos los horarios del rango, pero CONDICIONADOS a que la fecha del horario sea >= a la fecha_ingreso del empleado
        $horarios = Horario::whereIn('empleado_id', $empleadosIds)
            ->whereBetween('fecha', [$request->fechaInicio, $request->fechaFin])
            ->whereHas('empleado', function ($query) {
                // Usamos el nombre real de la tabla de horarios dinámicamente
                $tablaHorarios = (new Horario)->getTable();
                $query->whereRaw("{$tablaHorarios}.fecha >= empleados.fecha_ingreso");
            })
            ->get()
            ->groupBy('empleado_id');

        // 3. CORRECCIÓN CLAVE PARA MARCACIONES:
        // Lo mismo para las marcaciones, no buscar nada antes de su fecha de ingreso
        $marcaciones = Marcacion::whereIn('empleado_id', $empleadosIds)
            ->whereBetween('fecha', [$request->fechaInicio, $request->fechaFin])
            ->whereHas('empleado', function($query) {
                // Usamos el nombre real de la tabla de marcaciones dinámicamente (así sea marcacions o marcaciones)
                $tablaMarcaciones = (new Marcacion)->getTable();
                $query->whereRaw("{$tablaMarcaciones}.fecha >= empleados.fecha_ingreso");
            })
            ->get()
            ->groupBy('empleado_id');

        $marcaciones = $marcaciones->map(function ($grupo) {
            return $grupo->unique('fecha');
        });

        $permisos = \App\Models\Permiso::whereIn('empleado_id', $empleados->pluck('id'))
            ->whereBetween('fecha', [$request->fechaInicio, $request->fechaFin])
            ->where('tipo_id', 4)
            ->get()
            ->groupBy('empleado_id');

        // 1. CAMBIO VITAL: Filtramos por fecha para evitar "Memory exhausted"
        // No traemos toda la historia, solo lo que necesitamos comparar (el rango actual)
        $todasLasMarcaciones = Marcacion::whereIn('empleado_id', $empleados->pluck('id'))
            ->whereBetween('fecha', [
                \Carbon\Carbon::parse($request->fechaInicio)->subMonths(2), // Un mes atr醩 por si las compensaciones son antiguas
                $request->fechaFin,
            ])
            ->get()
            ->groupBy('empleado_id');

        $lista = $empleados->flatMap(function ($empleado) use ($horarios /* $horariosExtra */, $marcaciones, $request, $permisos, $todasLasMarcaciones) {
            $fechas = \Carbon\CarbonPeriod::create($request->fechaInicio, $request->fechaFin);

            return collect($fechas)->map(function ($fecha) use ($empleado, $horarios /* $horariosExtra */, $marcaciones, $permisos, $todasLasMarcaciones) {

                $fechaStr = $fecha->format('Y-m-d');
                $horario = $horarios->get($empleado->id)?->firstWhere('fecha', $fecha);
                // $horarioExtra = $horariosExtra->get($empleado->id);
                $marcacion = $marcaciones->get($empleado->id)?->firstWhere('fecha', $fecha);

                $permisoFila = null;
                $refriEnOrigen = false;

                // ---------------- . Si tiene marcacion de refrigerio para la C , se usa para la resta
                if ($permisos->has($empleado->id)) {
                    $permisoFila = $permisos->get($empleado->id)->first(function ($p) use ($fechaStr) {
                        return \Carbon\Carbon::parse($p->fecha)->format('Y-m-d') === $fechaStr;
                    });

                    if ($permisoFila) {
                        preg_match('/(\d{2}\/\d{2}\/\d{4})/', $permisoFila->motivo, $matches);
                        if (isset($matches[1])) {
                            $fechaOrigen = \Carbon\Carbon::createFromFormat('d/m/Y', $matches[1])->format('Y-m-d');

                            // 2. BUSQUEDA EN MEMORIA: Usamos la colecci髇 filtrada en el punto 1
                            $marcacionOrigen = $todasLasMarcaciones->get($empleado->id)?->first(function ($m) use ($fechaOrigen) {
                                $fechaM = ($m->fecha instanceof \Carbon\Carbon) ? $m->fecha : \Carbon\Carbon::parse($m->fecha);

                                return $fechaM->format('Y-m-d') === $fechaOrigen;
                            });

                            $refriEnOrigen = $marcacionOrigen && $marcacionOrigen->ingreso_refri ? true : false;
                        }
                    }
                }

                $tardanza = 0;
                $horas = 0;
                $extra = 0;
                $anticipado = 0;
                $nocturno = 0;

                // ---------------- Evitar contar si no tiene HP
                if ($horario && $marcacion && $marcacion->ingreso) {
                    $hip_check = $horario->ingreso->format('H:i');
                    $hsp_check = $horario->salida->format('H:i');

                    if ($hip_check === '00:00' && $hsp_check === '00:00') {
                        return [
                            'empleado' => $empleado,
                            'fecha' => $fecha,
                            'fecha_db' => $fechaStr,
                            'horario' => $horario,
                            // 'horariosExtra' => $horarioExtra,
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

                    // ---------------- Preparar las HP y las marcaciones
                    $h_ingreso = $horario->ingreso->copy();
                    $h_salida = $horario->salida->copy();
                    if ($h_salida->lt($h_ingreso)) {
                        $h_salida->addDay();
                    }

                    $m_ingreso = $marcacion->ingreso->copy();
                    $m_salida = $marcacion->salida ? $marcacion->salida->copy() : null;
                    if ($m_salida && $m_salida->lt($m_ingreso)) {
                        $m_salida->addDay();
                    }

                    // ---------------- Tardanzas
                    $tardanza = max(0, $h_ingreso->diffInMinutes($m_ingreso, false));

                    // ---------------- Tiempo total (programado)
                    $minutosProgramados = $h_ingreso->diffInMinutes($h_salida, false);

                    $descuentoRefri = 0;

                    // ---------------- Descuento de refrigerio dependiendo de la jornada
                    if ($empleado->jornada_id == 1) {
                        $descuentoRefri = 60;
                    } else {
                        if ($marcacion->ingreso_refri || $marcacion->salida_refri) {
                            $descuentoRefri = 60;
                        }
                    }

                    // ---------------- Total real
                    $horas = max(0, $minutosProgramados - $descuentoRefri);

                    // ---------------- Extra y anticipado
                    if ($m_salida && $m_ingreso) {

                        $extra_salida = max(0, $h_salida->diffInMinutes($m_salida, false));

                        /*
                            Si ingreso antes de su HI entonces cuenta
                            HIP - HIR : positivo toma , si es negativo 0
                        */
                        $extra_ingreso = max(0, $m_ingreso->diffInMinutes($h_ingreso, false));

                        $extra = $extra_salida + $extra_ingreso;

                        // Log::info('Calculo de HE: ' . json_encode([
                        //     'empleado' => $empleado->apellidos,
                        //     'fecha' =>$fechaStr,
                        //     'HIP: ' => $h_ingreso,
                        //     'HI: ' => $m_ingreso,
                        //     'extra ingreso: ' => $extra_ingreso,
                        //     'HSP: ' => $m_salida,
                        //     'HS: ' => $h_salida,
                        //     'extra salida: ' => $extra_salida,
                        //     'TOTAL: ' => $extra,
                        // ],JSON_PRETTY_PRINT));

                        $horasAnticipado = max(0, $m_salida->diffInMinutes($h_salida, false));
                    } else {
                        $extra = 0;
                        $horasAnticipado = 0;
                    }

                    $anticipado = $horasAnticipado;

                    // ---------------- Tolerancia por empresa y horario
                    if ((in_array($h_salida->format('H:i'), ['23:00', '23:30', '23:59']) && ($empleado->empresa_id == 4 || $empleado->empresa_id == 3)) || ($h_salida->format('H:i') == '18:30' && $empleado->empresa_id == 1)) {
                        $minutosTolerancia = $empleado->empresa_id == 1 ? 30 : 20;
                        $anticipado = $horasAnticipado >= $minutosTolerancia ? $horasAnticipado : 0;
                    }

                    // ---------------- Nocturno , se necesita en bloques de 30 en 30
                    if (in_array($empleado->empresa_id, [1, 3, 4])) {
                        $inicioVentana = $m_ingreso->copy()->setTime(22, 0, 0);
                        $finVentana = $m_ingreso->copy()->addDay()->setTime(6, 0, 0);
                        $solo_hora_salida = \Carbon\Carbon::parse($horario->salida)->format('H:i:s');
                        $h_salida_prog = \Carbon\Carbon::parse($m_ingreso->format('Y-m-d').' '.$solo_hora_salida);
                        if ($h_salida_prog->hour < 10) {
                            $h_salida_prog->addDay();
                        }

                        if (! $m_salida || $m_salida->lte($inicioVentana)) {
                            $nocturno = 0;
                        } else {
                            $inicioConteo = $m_ingreso->gt($inicioVentana) ? $m_ingreso : $inicioVentana;
                            $finConteo = $h_salida_prog;
                            if ($finConteo->gt($finVentana)) {
                                $finConteo = $finVentana;
                            }

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
                    // 'horariosExtra' => $horarioExtra,
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

        // PASO 1: FILTRAR EMPRESAS SEG脷N USUARIO
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
                // Mantenemos tu l贸gica: < 05:00 AM se mueve al d铆a anterior
                $h = \Carbon\Carbon::parse($item->hora);

                if ($h->hour < 5) {
                    $item->fecha = \Carbon\Carbon::parse($item->fecha)->subDay()->format('Y-m-d');
                } else {
                    $item->fecha = \Carbon\Carbon::parse($item->fecha)->format('Y-m-d');
                }

                return $item;
            })
            // 1. Filtramos por la fecha ya corregida (L贸gica)
            ->filter(function ($item) use ($request) {
                return $item->fecha >= $request->fechaInicio && $item->fecha <= $request->fechaFin;
            })
            // 2. Ordenamiento Maestro para que RRHH no se queje
            ->sort(function ($a, $b) {
                // Si las fechas son distintas, orden normal de fecha
                if ($a->fecha !== $b->fecha) {
                    return strcmp($a->fecha, $b->fecha);
                }

                // Si es la misma fecha l贸gica, aplicamos el truco de la hora virtual
                // Las 00:06 se convierten en "24:00:06" para que vayan DESPU脡S de las 21:00
                $horaA = ($a->hora < '05:00:00') ? '24:'.$a->hora : $a->hora;
                $horaB = ($b->hora < '05:00:00') ? '24:'.$b->hora : $b->hora;

                return strcmp($horaA, $horaB);
            })
            ->values(); // Resetear 铆ndices para que Inertia no mande un objeto raro al frontend

        return Inertia::render('marcaciones/reales/index', [
            'marcaciones' => $marcaciones,
            'empresas' => $empresas,
            'encargados' => $encargados,
            'filters' => $filters,
            'csrf_token' => csrf_token(),
        ]);
    }

    public function store(StoreMarcacionRequest $request)
    {
        $data = $request->validated();
        try {
            DB::transaction(function () use ($data) {

                $marcacion = Marcacion::updateOrCreate(
                    [
                        'empleado_id' => $data['empleado_id'],
                        'fecha' => $data['fecha'],
                    ],
                    [
                        $data['tipo'] => $data['hora'],
                    ]
                );

                // ------------------------ Logica para que se registren las creaciones , en el registro que se usara luego en el pull

                // 2. ? CONSOLIDADO (AGREGADO)
                $mapaPrefijos = [
                    'ingreso' => 'hi',
                    'salida' => 'hs',
                    'ingreso_refri' => 'hri',
                    'salida_refri' => 'hrs',
                ];

                $pre = $mapaPrefijos[$data['tipo']];

                $edicionExistente = DB::table('marcacion_edicions')
                    ->where('empleado_id', $marcacion->empleado_id)
                    ->where('fecha', $marcacion->fecha)
                    ->where('es_consolidado', 1)
                    ->first();

                $datosActualizar = [
                    'user_id' => Auth::id(),
                    "{$pre}_edit" => $data['hora'],
                    'updated_at' => now(),
                ];

                if (! $edicionExistente || is_null($edicionExistente->{"{$pre}_orig"})) {
                    $datosActualizar["{$pre}_orig"] = null; // Era NULL
                }

                if (! $edicionExistente) {
                    $datosActualizar['created_at'] = now();
                    $datosActualizar['motivo'] = 'Registro consolidado';
                }

                DB::table('marcacion_edicions')->updateOrInsert(
                    [
                        'empleado_id' => $marcacion->empleado_id,
                        'fecha' => $marcacion->fecha,
                        'es_consolidado' => 1,
                    ],
                    $datosActualizar
                );

                // ------------------------ Logica para que se registren las creaciones , en el registro que se usara luego en el pull

                MarcacionEdicion::create([
                    'empleado_id' => $marcacion->empleado_id,
                    'user_id' => Auth::user()->id,
                    'fecha' => $marcacion->fecha,
                    'hora_original' => $marcacion->{$data['tipo']},
                    'hora' => $data['hora'],
                    'motivo' => $data['motivo'],
                ]);

                $horarios = Horario::where('empleado_id', $marcacion->empleado_id)
                    ->whereNotNull('extra')
                    ->get();

                if ($horarios->count() > 0) {
                    $horarioExtra = Horario::where('fecha', $marcacion->fecha)
                        ->where('empleado_id', $marcacion->empleado_id)
                        ->first();

                    if (! $horarioExtra || ! $horarioExtra->salida || ! $marcacion->salida) {
                        return;
                    }

                    // ?? FIX: Usar solo formato de fecha Y-m-d
                    $fechaSolo = Carbon::parse($marcacion->fecha)->format('Y-m-d');

                    // Extraer solo la hora
                    $horaSalidaProgramada = is_string($horarioExtra->salida)
                        ? $horarioExtra->salida
                        : $horarioExtra->salida->format('H:i:s');

                    $horaSalidaReal = is_string($marcacion->salida)
                        ? $marcacion->salida
                        : $marcacion->salida->format('H:i:s');

                    $salidaProgramada = Carbon::parse($fechaSolo.' '.$horaSalidaProgramada);
                    $salidaReal = Carbon::parse($fechaSolo.' '.$horaSalidaReal);

                    // Ajustar si cruza medianoche
                    if ($salidaReal->lt($salidaProgramada)) {
                        $salidaReal->addDay();
                    }

                    $marcacionExtra = $salidaProgramada->diffInMinutes($salidaReal);

                    foreach ($horarios as $horario) {
                        if ($horario->extra) {
                            $partesExtra = explode(':', $horario->extra);
                            $minutosExtra = ((int) $partesExtra[0] * 60) + (int) $partesExtra[1];

                            if ($minutosExtra > $marcacionExtra) {
                                $nuevoExtra = $minutosExtra - $marcacionExtra;
                                $horario->extra = sprintf('%02d:%02d:00', floor($nuevoExtra / 60), $nuevoExtra % 60);
                                $horario->save();
                                break;
                            } else {
                                $marcacionExtra -= $minutosExtra;
                                $horario->extra = null;
                                $horario->save();
                            }
                        }
                    }

                    if ($marcacionExtra > 0) {
                        $ultimoHorario = $horarios->last();
                        if ($ultimoHorario && $ultimoHorario->extra) {
                            $partesExtra = explode(':', $ultimoHorario->extra);
                            $minutosExtra = ((int) $partesExtra[0] * 60) + (int) $partesExtra[1];

                            $nuevoExtra = max(0, $minutosExtra - $marcacionExtra);
                            $ultimoHorario->extra = sprintf('%02d:%02d:00', floor($nuevoExtra / 60), $nuevoExtra % 60);
                            $ultimoHorario->save();
                        }
                    }
                }
            });
        } catch (Exception $e) {
            return back()->withErrors(['message' => $e->getMessage()]);
        }
    }

    /* ------------------------------------------------------------------------------------------------------------------------ Update */
    public function update(Request $request, Marcacion $marcacione)
    {
        // LOG DE ENTRADA INMEDIATA - Si no ves esto, el problema es la Ruta o Middleware
        \Log::emergency('=== PETICI脫N RECIBIDA ===');
        \Log::emergency('ID Marcacion en URL: '.$marcacione->id);
        \Log::emergency('Payload: '.json_encode($request->all()));

        // Usamos all() para saltarnos validaciones que puedan estar rebotando la petici贸n
        $data = $request->all();

        try {
            if (isset($data['modo']) && $data['modo'] === 'compensar') {
                return $this->updateModoCompensar($data, $marcacione);
            }

            if (isset($data['modo']) && $data['modo'] === 'compensarDia') {
                return $this->updateModoCompensarDia($data, $marcacione);
            }

            return $this->updateModoLibre($data, $marcacione);
        } catch (\Exception $e) {
            \Log::emergency('EXCEPCI脫N CACHADA: '.$e->getMessage());

            return back()->withErrors(['message' => 'Error: '.$e->getMessage()]);
        }
    }

    public function storeCompensarDia(Request $request)
    {
        try {

            \DB::beginTransaction();

            // 1. Crear marcación
            $marcacion = Marcacion::create([
                'empleado_id' => $request->empleado_id,
                'fecha' => $request->fecha,
                'tipo' => $request->tipo,
                'hora' => '00:00:00',
                'motivo' => $request->motivo,
            ]);

            // 2. Preparar data para reutilizar lógica existente
            $data = $request->all();

            $data['marcacion_id'] = $marcacion->id;

            // 3. Reutilizar lógica actual
            $response = $this->updateModoCompensarDia($data, $marcacion);

            \DB::commit();

            return $response;

        } catch (\Exception $e) {

            \DB::rollBack();

            \Log::error('ERROR STORE COMPENSAR DIA: '.$e->getMessage());

            return back()->withErrors([
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function updateModoCompensarDia($data, $marcacione = null)
    {
        try {

            if (! $marcacione->exists) {
                $marcacione = null;
            }

            // 1. datos
            $marcaciones = Marcacion::find($data['marcacion_id']);
            $fecha = isset($data['fecha']) ? \Carbon\Carbon::parse($data['fecha'])->format('Y-m-d') : \Carbon\Carbon::parse($marcacione->fecha)->format('Y-m-d');
            $horario = \DB::table('horarios as h')
                ->where('h.fecha', $fecha)
                ->where('h.empleado_id', $data['empleado_id'])
                ->first();

            $empleado = \DB::table('empleados as emp')
                ->where('emp.id', $horario->empleado_id)
                ->first();

            $area = Area::find($empleado->area_id);
            $jornada = Jornada::find($empleado->jornada_id);

            $total_he = $this->calcularHorasExtraDisponibles($empleado->id);

            /*
                \Log::info('DEBUG COMPENSAR DIA: '.json_encode([
                'marcacion' => $marcaciones,
                'horario' => $horario,
                'fecha parseada' => $fecha,
                'horario_id' => $horario ? $horario->id : 'NO ENCONTRADO',
                'empleado' => $empleado,
                '$total_he' => $total_he,
            ], JSON_PRETTY_PRINT));
            */

            // 2. comparar si hay suciente HE
            $inicio = \Carbon\Carbon::parse($horario->ingreso);
            $fin = \Carbon\Carbon::parse($horario->salida);
            $minutos_programados = $inicio->diffInMinutes($fin);

            if ($minutos_programados > 360) {
                $minutos_programados -= 60;
            }

            /*
            log::info('tiempos ingreso y salida : '.json_encode([
                 'inicio' => $inicio,
                 'fin' => $fin,
                 'programado' => $minutos_programados,
             ], JSON_PRETTY_PRINT));
            */

            if ($total_he < $minutos_programados) {
                log::info('Tiempos insuficientes : '.json_encode([
                    'Total HE' => $total_he,
                    'Programado' => $minutos_programados,
                ], JSON_PRETTY_PRINT));

                return back()->with('error', "Saldo insuficiente. Tienes {$total_he} min y necesitas {$minutos_programados} min.");
            } else {

                // 3. Empezar el calculo
                /*
                    Encontrar todos los horarios con HE aprobadas del inicio del año a hoy
                    Esto me retorna un array supongo
                    supongo que usar un forEach
                */
                $inicioAnio = \Carbon\Carbon::now()->startOfYear()->toDateString();
                $finAnio = \Carbon\Carbon::now()->endOfYear()->toDateString();
                $bolsa_acumulada = 0;
                $deuda_pendiente = $minutos_programados;
                $detalles_descuento = []; // Para tu log

                $extras = \DB::table('marcacions as m')
                    ->join('horarios as h', function ($join) {
                        $join->on('h.fecha', '=', 'm.fecha')
                            ->on('h.empleado_id', '=', 'm.empleado_id');
                    })
                    ->where('m.empleado_id', $empleado->id)
                    ->where('m.estado_horas_extra', 1)
                    ->whereNotNull('h.extra')
                    ->where('h.extra', '!=', '00:00:00')
                    ->whereBetween('m.fecha', [$inicioAnio, $finAnio])
                    ->select('h.id', 'h.extra as extra_db', 'h.extra_consumido', 'h.fecha')
                    ->orderBy('m.fecha', 'asc')
                    ->get();

                /*
                    log::info('horas extras : '.json_encode([
                    'Horas extras' => $extras,
                ], JSON_PRETTY_PRINT));
                */

                foreach ($extras as $e) {

                    /* voy sumando los minutos y RESTANDOLOS de horario.extras, mientras sea menor o igual que el programado se ira sumando
                       el tema es como haria si por algun motivo , ya en las ultimas pues , sobra tiempo de la bolsa al momento de completar la hora_programada
                    */
                    if ($deuda_pendiente <= 0) {
                        break;
                    }

                    $tiempo = \Carbon\Carbon::parse($e->extra_db);
                    $disponible_en_registro = ($tiempo->hour * 60) + $tiempo->minute;
                    // Tomamos el MINIMO entre lo que hay en el registro y lo que todavía debemos.
                    // Ejemplo: Si hay 60 min pero solo debo 10, min(60, 10) devolverá 10.
                    $a_consumir = min($disponible_en_registro, $deuda_pendiente);

                    $bolsa_acumulada += $a_consumir;
                    $deuda_pendiente -= $a_consumir;

                    $sobrante_en_registro = $disponible_en_registro - $a_consumir;

                    // --- FORMATEO PARA EL "EXTRA" (lo que sobra) ---
                    $h_sobra = floor($sobrante_en_registro / 60);
                    $m_sobra = $sobrante_en_registro % 60;
                    $extra_formateado = sprintf('%02d:%02d:00', $h_sobra, $m_sobra);

                    // --- FORMATEO PARA EL "EXTRA_CONSUMIDO" (lo que usaste) ---
                    $h_usado = floor($a_consumir / 60);
                    $m_usado = $a_consumir % 60;
                    $consumido_formateado = sprintf('%02d:%02d:00', $h_usado, $m_usado);

                    \DB::table('horarios')->where('id', $e->id)->update([
                        'extra' => $extra_formateado,
                        'extra_consumido' => $consumido_formateado,
                        'destino_compensacin' => 'Compensa total del dia : '.$fecha,
                    ]);

                    // actualizar las h.e

                    $detalles_descuento[] = [
                        'horario_id' => $e->id,
                        'tenia' => $disponible_en_registro,
                        'usamos' => $a_consumir,
                        'sobro_en_este_dia' => $sobrante_en_registro,
                        'falta_por_pagar (programado)' => $deuda_pendiente,
                    ];

                    log::info('Resumen del cobro: '.json_encode([
                        'detalle descuento' => $detalles_descuento,
                    ], JSON_PRETTY_PRINT));

                    $reporte = ReporteHeConsumida::create([
                        'empleado_id' => $empleado->id,
                        'apellidos' => $empleado->apellidos,
                        'nombres' => $empleado->nombres,
                        'dni' => $empleado->dni,
                        'area' => $area->nombre ?? 'N/A',
                        'jornada' => $jornada->nombre ?? 'N/A',

                        'fecha_he' => \Carbon\Carbon::parse($e->fecha)->format('Y-m-d'),
                        'extra_consumido' => $consumido_formateado,
                        'extra_restante' => $extra_formateado,
                        'destino_compensacin' => 'Compensa total del dia: '.$fecha,
                        'fecha_uso' => $fecha, // <-- CORREGIDO: ahora es una fecha
                        'fecha_edicin' => now()->format('Y-m-d H:i:s'),
                    ]);

                    /*
                        log::info(
                        'reporte HE: ' . json_encode([
                            'reporte HE' => $reporte,
                        ], JSON_PRETTY_PRINT)
                    );
                    */

                }

                // Ajuste de tiempos y descuentos

                \DB::table('horarios')
                    ->where('id', $horario->id) // El ID del día que estamos compensando
                    ->update(['estado' => 'CHE']);

                \DB::table('marcacions')
                    ->where('empleado_id', $empleado->id)
                    ->where('fecha', $fecha)
                    ->update([
                        'ingreso' => $horario->ingreso, // HI programada
                        'salida' => $horario->salida,  // HS programada
                    ]);

                // crear reportes

                \Log::info('COMPENSACIN COMPLETADA', [
                    'empleado' => $empleado->nombres,
                    'dia_compensado' => $fecha,
                    'minutos_gastados' => $bolsa_acumulada,
                    'detalles' => $detalles_descuento,
                ]);

                return back()->with('success', 'Procesando lógica de compensacin...');
            }

        } catch (\Exception $e) {
            \Log::emergency('EXCEPCIN CACHADA: '.$e->getMessage());

            return back()->withErrors(['message' => 'Error: '.$e->getMessage()]);
        }

        return back()->with('success', 'Procesando lógica de compensacin...');
    }

    private function updateModoCompensar($data, Marcacion $marcacione)
    {
        return DB::transaction(function () use ($data, $marcacione) {
            // 1. Identificar la BOLSA (Origen del tiempo)
            $idBolsa = $data['extraSeleccionada'] ?? null;

            if ($idBolsa) {
                $horarioFuente = Horario::find($idBolsa);
            } else {
                // Si no mandan ID, buscamos la bolsa más antigua con saldo real

                $inicioAnio = \Carbon\Carbon::now()->startOfYear()->toDateString();

                $horarioFuente = \DB::table('horarios as h')
                    ->join('marcacions as m', function ($join) {
                        $join->on('m.empleado_id', '=', 'h.empleado_id')
                            ->on('m.fecha', '=', 'h.fecha');
                    })
                    ->where('h.empleado_id', $marcacione->empleado_id)
                    ->where('m.estado_horas_extra', 1)
                    ->whereDate('h.fecha', '>=', $inicioAnio)
                    ->whereRaw('TIME_TO_SEC(h.extra) > 0')
                    ->orderBy('h.fecha', 'asc')
                    ->select('h.*')
                    ->first();

                if ($horarioFuente) {
                    $horarioFuente = Horario::find($horarioFuente->id);
                }
            }

            if (! $horarioFuente) {
                throw new \Exception('El empleado no tiene saldo de horas extra para compensar.');
            }

            \Log::emergency('extra_consumido raw: '.$horarioFuente->extra_consumido);
            \Log::emergency('extra raw: '.$horarioFuente->extra);

            // 2. Calcular Deuda del día (Destino del tiempo)
            $campoHora = $data['tipo'];
            $horarioDestino = Horario::where('empleado_id', $marcacione->empleado_id)
                ->whereDate('fecha', $marcacione->fecha)
                ->first();

            if (! $horarioDestino) {
                throw new \Exception('No hay horario programado para el día de la marcación.');
            }

            $horaProgramada = \Carbon\Carbon::parse($campoHora === 'ingreso' ? $horarioDestino->ingreso : $horarioDestino->salida);

            $horaProg = \Carbon\Carbon::createFromFormat('H:i:s',
                $campoHora === 'ingreso'
                    ? $horarioDestino->getRawOriginal('ingreso')
                    : $horarioDestino->getRawOriginal('salida')
            );
            $horaReal = \Carbon\Carbon::createFromFormat('H:i:s',
                $marcacione->getRawOriginal($campoHora)
            );

            // $minutosDeudaTotal = (int) abs($horaReal->diffInMinutes($horaProgramada));

            if ($campoHora === 'ingreso') {
                if ($horaReal->gt($horaProg)) {
                    $minutosDeudaTotal = (int) abs($horaReal->diffInMinutes($horaProg));
                } else {
                    throw new \Exception('No puedes compensar si no hay tardanza.');
                }
            } else {
                if ($horaReal->lt($horaProg)) {
                    $minutosDeudaTotal = (int) abs($horaReal->diffInMinutes($horaProg));
                } else {
                    throw new \Exception('No puedes compensar si no saliste antes de tu hora.');
                }
            }

            $extraStr = is_string($horarioFuente->extra)
                ? $horarioFuente->extra
                : \Carbon\Carbon::parse($horarioFuente->extra)->format('H:i:s');

            $partesExtra = explode(':', $extraStr);
            $minutosDisponibles = ((int) $partesExtra[0] * 60) + (int) ($partesExtra[1] ?? 0);

            // Convertir extra de la bolsa a minutos
            // $partesExtra = explode(':', $horarioFuente->extra);
            // $minutosDisponibles = ((int) $partesExtra[0] * 60) + (int) ($partesExtra[1] ?? 0);

            // 3. Consumo
            $minutosAConsumir = min($minutosDeudaTotal, $minutosDisponibles);
            $restoBolsa = $minutosDisponibles - $minutosAConsumir;

            \Log::emergency("minutosDeudaTotal: $minutosDeudaTotal");
            \Log::emergency("minutosDisponibles: $minutosDisponibles");
            \Log::emergency('horaReal: '.$horaReal->format('H:i'));
            \Log::emergency('horaProg: '.$horaProg->format('H:i'));

            // Manejo de extra_consumido acumulativo
            $extraConsumidoActual = is_string($horarioFuente->extra_consumido)
            ? $horarioFuente->extra_consumido
            : ($horarioFuente->extra_consumido
                ? \Carbon\Carbon::parse($horarioFuente->extra_consumido)->format('H:i:s')
                : '00:00:00');

            $partesCons = explode(':', $extraConsumidoActual);
            $yaConsumido = max(0, ((int) $partesCons[0] * 60) + (int) ($partesCons[1] ?? 0));
            $nuevoConsumidoTotal = $yaConsumido + $minutosAConsumir;

            // Formateo HH:MM:SS
            $nuevoExtraStr = sprintf('%02d:%02d:00', floor($restoBolsa / 60), $restoBolsa % 60);
            $consumidoStr = sprintf('%02d:%02d:00', floor($nuevoConsumidoTotal / 60), $nuevoConsumidoTotal % 60);

            // 4. AJUSTE DE RELOJ (Marcación)
            $horaCarbon = \Carbon\Carbon::parse($marcacione->$campoHora);
            $nuevaHora = ($campoHora === 'ingreso')
                ? $horaCarbon->subMinutes($minutosAConsumir)->format('H:i:s')
                : $horaCarbon->addMinutes($minutosAConsumir)->format('H:i:s');

            // --- UPDATES DIRECTOS (Para asegurar que entren a DB) ---

            // A. Actualizar la BOLSA (De donde sale el tiempo)
            DB::table('horarios')->where('id', $horarioFuente->id)->update([
                'extra' => $nuevoExtraStr,
                'extra_consumido' => $consumidoStr,
                'destino_compensacion' => 'Compensado dia '.$marcacione->fecha->format('Y-m-d'),
                'fecha_compensacion' => $marcacione->fecha->format('Y-m-d'),
                'calculo_manual' => 1,
                'updated_at' => now(),
            ]);

            // B. Actualizar la MARCACIÓN (El registro visual)
            DB::table('marcacions')->where('id', $marcacione->id)->update([
                $campoHora => $nuevaHora,
            ]);

            // C. Auditoría (Tu lógica sagrada)
            $mapa = ['ingreso' => 'hi', 'salida' => 'hs', 'ingreso_refri' => 'hri', 'salida_refri' => 'hrs'];
            $pre = $mapa[$campoHora];

            MarcacionEdicion::create([
                'empleado_id' => $marcacione->empleado_id,
                'user_id' => \Auth::id(),
                'fecha' => $marcacione->fecha,
                'hora_original' => $marcacione->{$campoHora},
                'hora' => $nuevaHora,
                'motivo' => $data['motivo'].' (Edicion Compensa)',
                'es_consolidado' => 0,
                'created_at' => now(), // <--- PARA QUE DATE-FNS NO EXPLOTE
                'updated_at' => now(),
            ]);

            $minutosDelta = $minutosAConsumir; // ESTO es lo que consumiste hoy
            $saldoRestante = $restoBolsa;

            $empleado = Empleado::find($marcacione->empleado_id);

            ReporteHeConsumida::create([
                'empleado_id' => $marcacione->empleado_id,
                'apellidos' => $empleado->apellidos,
                'nombres' => $empleado->nombres,
                'dni' => $empleado->dni,
                'area' => $empleado->area->nombre ?? 'N/A',
                'jornada' => $empleado->jornada->nombre ?? 'N/A',

                'fecha_he' => \Carbon\Carbon::parse($horarioFuente->fecha)->format('Y-m-d'),
                'extra_consumido' => sprintf('%02d:%02d:00', floor($minutosAConsumir / 60), $minutosAConsumir % 60),
                'extra_restante' => sprintf('%02d:%02d:00', floor($saldoRestante / 60), $saldoRestante % 60),
                'destino_compensacion' => 'Compensado dia '.$marcacione->fecha->format('Y-m-d'),
                'fecha_uso' => $marcacione->fecha, // <-- CORREGIDO: ahora es una fecha
                'fecha_edicion' => now()->format('Y-m-d H:i:s'),
            ]);

            DB::table('marcacion_edicions')->updateOrInsert(
                ['empleado_id' => $marcacione->empleado_id, 'fecha' => $marcacione->fecha, 'es_consolidado' => 1],
                [
                    'user_id' => \Auth::id(),
                    "{$pre}_edit" => $nuevaHora,
                    'motivo' => 'Registro consolidado',
                    'fecha' => $marcacione->fecha,
                    "{$pre}_orig" => $marcacione->{$campoHora},
                    'created_at' => \DB::raw('IFNULL(created_at, NOW())'),
                ]
            );

            return back()->with('success', "Compensados $minutosAConsumir minutos.");
        });
    }

    private function updateModoLibre($data, Marcacion $marcacione)
    {

        // \Log::info('DATA COMPLETA: '.json_encode($data));
        try {
            return DB::transaction(function () use ($data, $marcacione) {

                // \Log::emergency('?? INICIO TRANSACCIóN');

                $mapaPrefijos = [
                    'ingreso' => 'hi',
                    'salida' => 'hs',
                    'ingreso_refri' => 'hri',
                    'salida_refri' => 'hrs',
                ];

                $tipo = $data['tipo'];
                $pre = $mapaPrefijos[$tipo];
                $horaNueva = (! empty($data['hora_nueva']) && $data['hora_nueva'] !== 'null') ? $data['hora_nueva'] : null;

                // \Log::emergency('?? Tipo: '.$tipo.' | Prefijo: '.$pre.' | Hora nueva: '.($horaNueva ?? 'NULL'));

                // 1. Auditoría
                MarcacionEdicion::create([
                    'empleado_id' => $marcacione->empleado_id,
                    'user_id' => Auth::id(),
                    'fecha' => $marcacione->fecha,
                    'hora_original' => $marcacione->{$tipo},
                    'hora' => $horaNueva,
                    'motivo' => $data['motivo'].' (Edicion libre)',

                    'es_consolidado' => 0,
                    'created_at' => now(), // Forzado manual por si las moscas
                    'updated_at' => now(),
                ]);

                // 2. Consolidado - PROTEGER el _orig
                $edicionExistente = DB::table('marcacion_edicions')
                    ->where('empleado_id', $marcacione->empleado_id)
                    ->where('fecha', $marcacione->fecha)
                    ->where('es_consolidado', 1)
                    ->first();

                $datosActualizar = [
                    'user_id' => Auth::id(),
                    "{$pre}_edit" => $horaNueva,
                    'updated_at' => now(),
                    'motivo' => $data['motivo'].' (Edicion libre)',
                ];

                if (! $edicionExistente || is_null($edicionExistente->{"{$pre}_orig"})) {
                    $datosActualizar["{$pre}_orig"] = $marcacione->{$tipo} ?? null;
                }

                if (! $edicionExistente) {
                    $datosActualizar['created_at'] = now();
                    $datosActualizar['motivo'] = 'Registro consolidado';
                }

                // Si se borra una columna , se almacena el prefijo para saber cual fue borrada y no llenarla otra ves.
                if (is_null($horaNueva)) {
                    $borradosActuales = $edicionExistente
                        ? array_filter(explode(',', $edicionExistente->campos_borrados ?? ''))
                        : [];
                    $borradosActuales[] = $pre;
                    $datosActualizar['campos_borrados'] = implode(',', array_unique($borradosActuales));
                }

                DB::table('marcacion_edicions')->updateOrInsert(
                    [
                        'empleado_id' => $marcacione->empleado_id,
                        'fecha' => $marcacione->fecha,
                        'es_consolidado' => 1,
                    ],
                    $datosActualizar
                );

                // 3. Actualizar marcación
                $marcacione->update([$tipo => $horaNueva]);

                // 4. Blindaje
                DB::table('horarios')
                    ->where('empleado_id', $marcacione->empleado_id)
                    ->where('fecha', $marcacione->fecha)
                    ->update([
                        // 'calculo_manual' => 0,
                        'destino_compensacion' => 'Editado libremente por RRHH',
                    ]);

                return back()->with('success', 'Cambio guardado');
            });
        } catch (\Exception $e) {
            // \Log::emergency('?? ERROR CACHADO: '.$e->getMessage());
            // \Log::emergency('?? TRACE: '.$e->getTraceAsString());
            throw $e;
        }
    }

    private function calcularHorasExtraDisponibles($empleadoId): int
    {
        $inicioAnio = \Carbon\Carbon::now()->startOfYear()->toDateString();
        $finAnio = \Carbon\Carbon::now()->endOfYear()->toDateString();

        $extras = \DB::table('marcacions as m')
            ->join('horarios as h', function ($join) {
                $join->on('h.fecha', '=', 'm.fecha')
                    ->on('h.empleado_id', '=', 'm.empleado_id');
            })
            ->where('m.empleado_id', $empleadoId)
            ->where('m.estado_horas_extra', 1)
            ->whereNotNull('h.extra')
            ->where('h.extra', '!=', '00:00:00')
            ->whereBetween('m.fecha', [$inicioAnio, $finAnio])
            ->select('h.extra as extra_db')
            ->get();

        return $extras->reduce(function ($carry, $registro) {
            $partes = explode(':', (string) $registro->extra_db);
            if (count($partes) >= 2) {
                $carry += ((int) $partes[0] * 60) + (int) $partes[1];
            }

            return $carry;
        }, 0);
    }

    public function getHorasExtraDisponibles($empleadoId)
    {
        $minutosTotales = $this->calcularHorasExtraDisponibles($empleadoId);

        return response()->json([
            'total_minutos' => $minutosTotales,
            'label' => floor($minutosTotales / 60).'h '.($minutosTotales % 60).'min disponibles',
        ]);
    }

    /* ------------------------------------------------------------------------------------------------------------------------ Update */

    public function recalcularExtras(Request $request)
    {
        $inicio = microtime(true);

        $request->validate([
            'empresa' => 'required|integer',
            'fechaInicio' => 'required|date',
            'fechaFin' => 'required|date',
        ]);

        $empresaId = $request->empresa;
        $fechaInicio = $request->fechaInicio;
        $fechaFin = $request->fechaFin;
        // $ejecutadoPor = auth()->user()->name;

        // 1. Contamos el total de empleados activos para el log inicial
        $totalEmpleados = Empleado::where('empresa_id', $empresaId)
            ->whereNull('fecha_cese')
            ->count();

        \Log::info('INICIO PROCESO MASIVO HE', [
            // 'usuario' => $ejecutadoPor,
            'empresa_id' => $empresaId,
            'total_empleados_activos' => $totalEmpleados,
            'rango' => "$fechaInicio al $fechaFin",
        ]);

        // 2. Filtramos empleados: que pertenezcan a la empresa Y NO estén cesados
        Empleado::where('empresa_id', $empresaId)
            ->whereNull('fecha_cese')
            ->chunkById(50, function ($empleados) use ($fechaInicio, $fechaFin) {

                $empleadoIds = $empleados->pluck('id');

                // 3. Traemos horarios: Solo estado 'L' (Laboral) y que tengan hora de salida programada
                $todosLosHorarios = Horario::whereIn('empleado_id', $empleadoIds)
                    ->whereBetween('fecha', [$fechaInicio, $fechaFin])
                    ->where('estado', 'L')
                    ->whereNotNull('salida') // Validamos que exista hora programada
                    ->whereNull('calculo_manual') // Regla de oro: no tocar lo manual
                    ->get()
                    ->groupBy('empleado_id');

                // 4. Traemos marcaciones
                $todasLasMarcaciones = Marcacion::whereIn('empleado_id', $empleadoIds)
                    ->whereBetween('fecha', [$fechaInicio, $fechaFin])
                    ->whereNotNull('salida') // Validamos que exista marcación de salida real
                    ->get()
                    ->groupBy(function ($item) {
                        return $item->empleado_id.'_'.$item->fecha;
                    });

                foreach ($empleados as $empleado) {

                    \Log::info('Inicio del for each para el calculo');

                    $horariosEmpleado = $todosLosHorarios->get($empleado->id, []);

                    foreach ($horariosEmpleado as $horario) {
                        $key = $empleado->id.'_'.$horario->fecha;
                        $marcacion = $todasLasMarcaciones->get($key)?->first();

                        // Solo entramos si existe la marcación (la validación de salida real ya se hizo en el query)
                        if ($marcacion) {
                            $h_salida = Carbon::parse($horario->salida);
                            $m_salida = Carbon::parse($marcacion->salida);

                            $extraMinutos = max(0, $h_salida->diffInMinutes($m_salida, false));
                            $extraTime = gmdate('H:i:s', $extraMinutos * 60);

                            $horario->update([
                                'extra' => $extraTime,
                                'calculo_manual' => 1,
                                // 'estado' => 'Calculado Automat',
                            ]);

                            \Log::info('HE Calculada', [
                                'fecha' => $horario->fecha,
                                'trabajador' => trim("{$empleado->apellidos} {$empleado->nombres}"),
                                'prog_salida' => $h_salida->toTimeString(),
                                'real_salida' => $m_salida->toTimeString(),
                                'resultado_extra' => $extraTime,
                                // 'ejecutado_por' => $ejecutadoPor,
                            ]);
                        }
                    }
                }
            });

        $fin = microtime(true);
        $tiempo = round($fin - $inicio, 2);
        \Log::info('Recálculo completado exitosamente', [
            'tiempo_total_segundos' => $tiempo,
            'empresa' => $empresaId,
        ]);

        return back()->with('message', "Proceso completado en {$tiempo}s para {$totalEmpleados} empleados.");
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

                $horarios = Horario::whereIn('empleado_id', $dnis->values())
                    ->whereBetween('fecha', [$data['fecha'], now()->toDateString()])
                    ->get()
                    ->keyBy(fn ($h) => $h->fecha->format('Y-m-d').'-'.$h->empleado_id);

                // 1. Recolectamos TODAS las marcas de los relojes involucrados
                $todasLasMarcasRaw = Zktimems::whereIn('tarjeta', $dnis->keys())
                    ->whereBetween('fecha', [
                        \Carbon\Carbon::parse($data['fecha'])->format('Y-m-d'),
                        now()->addDay()->format('Y-m-d'),
                    ])
                    ->get(['tarjeta', 'fecha', 'hora']);

                // 2. Agrupamos por Jornada L贸gica (Regla de las 05:00 AM)
                $grupos = [];
                foreach ($todasLasMarcasRaw as $item) {
                    $empleadoId = $dnis->get($item->tarjeta);
                    $f = \Carbon\Carbon::parse($item->fecha);
                    $fechaLogica = ($item->hora < '05:00:00') ? $f->subDay()->format('Y-m-d') : $f->format('Y-m-d');
                    $key = $fechaLogica.'-'.$empleadoId;
                    $grupos[$key][] = $item->hora;
                }

                // 3. Procesamos cada grupo con l贸gica antibug
                foreach ($grupos as $key => $horasArray) {
                    $partes = explode('-', $key);
                    $fechaLogica = $partes[0].'-'.$partes[1].'-'.$partes[2];
                    $empleadoId = end($partes);

                    if ($fechaLogica < '2026-04-23') {
                        continue;
                    }

                    $marcas = collect($horasArray)->unique()->sort()->values();
                    $madrugada = $marcas->filter(fn ($h) => $h < '05:00:00')->values();
                    $tarde = $marcas->filter(fn ($h) => $h >= '05:00:00')->values();

                    $ingreso = null;
                    $salida = null;
                    $ingreso_refri = null;
                    $salida_refri = null;

                    if ($tarde->isNotEmpty()) {
                        $ingreso = $tarde->first();

                        // CASO A: TIENE MARCA DE MADRUGADA (Salida al d铆a siguiente)
                        if ($madrugada->isNotEmpty()) {
                            $salida = $madrugada->last();
                            // Si sobran marcas en la tarde, son refrigerio
                            if ($tarde->count() >= 2) {
                                $ingreso_refri = $tarde->get(1);
                            }
                            if ($tarde->count() >= 3) {
                                $salida_refri = $tarde->get(2);
                            }
                        }
                        // CASO B: TODO OCURRE EL MISMO D脥A
                        else {
                            $conteo = $tarde->count();
                            if ($conteo == 2) {
                                $salida = $tarde->last();
                            } elseif ($conteo == 3) {

                                // Si la marca es antes de las 14:00 (2 PM), es fin de refri
                                $ingreso_refri = $tarde->get(1);
                                if ($tarde->get(2) < '14:30:00') {
                                    $salida_refri = $tarde->get(2);
                                    $salida = null;
                                } else {
                                    $salida = $tarde->get(2);
                                }
                            } elseif ($conteo >= 4) {
                                // Caso Acevedo: Entrada, Inicio Refri, Fin Refri, Salida
                                $ingreso_refri = $tarde->get(1);
                                $salida_refri = $tarde->get(2);
                                $salida = $tarde->last();
                            }
                        }
                    }

                    // 4. -------------- GUARDADO SELECTIVO: Solo actualizar columnas NO editadas}
                    $edicionConsolidada = DB::table('marcacion_edicions')
                        ->where('empleado_id', $empleadoId)
                        ->where('fecha', $fechaLogica)
                        ->where('es_consolidado', 1)
                        ->first();

                    $borrados = $edicionConsolidada
                    ? array_filter(explode(',', $edicionConsolidada->campos_borrados ?? ''))
                    : [];

                    $datosActualizar = [];

                    if (! $edicionConsolidada || (is_null($edicionConsolidada->hi_edit) && ! in_array('hi', $borrados))) {
                        $datosActualizar['ingreso'] = $ingreso;
                    }
                    if (! $edicionConsolidada || (is_null($edicionConsolidada->hs_edit) && ! in_array('hs', $borrados))) {
                        $datosActualizar['salida'] = $salida;
                    }
                    if (! $edicionConsolidada || (is_null($edicionConsolidada->hri_edit) && ! in_array('hri', $borrados))) {
                        $datosActualizar['ingreso_refri'] = $ingreso_refri;
                    }
                    if (! $edicionConsolidada || (is_null($edicionConsolidada->hrs_edit) && ! in_array('hrs', $borrados))) {
                        $datosActualizar['salida_refri'] = $salida_refri;
                    }

                    // Solo actualizar si hay algo que tocar
                    if (! empty($datosActualizar)) {
                        Marcacion::updateOrCreate(
                            ['empleado_id' => $empleadoId, 'fecha' => $fechaLogica],
                            $datosActualizar
                        );
                    }

                    /*
                        if ($edicionConsolidada) {
                        // Si ya existe edición, saltamos al siguiente registro y no actualizamos nada de esta fila.
                        continue;
                        }
                    */

                    /*
                    \Log::emergency("=== EMPLEADO {$empleadoId} FECHA {$fechaLogica} ===");
                    \Log::emergency("Marcas: " . json_encode($marcas->toArray()));
                    \Log::emergency("Conteo tarde: " . $tarde->count());
                    \Log::emergency("Calculado - ingreso: {$ingreso}, salida: {$salida}, ing_refri: {$ingreso_refri}, sal_refri: {$salida_refri}");
                    \Log::emergency("Datos a actualizar: " . json_encode($datosActualizar));
                    \Log::emergency("========================================");
                    */

                    // Permisos de descanso
                    /*
                        La logica del TD es :
                            1. cuando el trabajador labora los dias que tiene marcado como horario.estado = D y tiene marcaciones
                            2. Se crea un permiso con permiso_tipo = 24 , que esu na TD con estado 0
                    */
                    $h = $horarios->get($key);
                    if ($h && $h->estado === 'D' && ($ingreso || $salida)) {
                        Permiso::firstOrCreate([
                            'empleado_id' => $empleadoId, 'tipo_id' => 24, 'fecha' => $fechaLogica, 'estado' => 0,
                        ], ['motivo' => 'TRABAJO EN DIA DE DESCANSO']);
                    }
                }
            });

            return back()->with('success', 'Sincronizaci贸n completada.');
        } catch (\Exception $e) {
            return back()->withErrors(['message' => $e->getMessage()]);
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
