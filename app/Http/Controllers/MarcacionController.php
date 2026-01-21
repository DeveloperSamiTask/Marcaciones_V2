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

                    // ------------ agregar extra a la bd
                    // 1. Verificamos el candado (0 = automático, 1 = manual/bloqueado)
                    $esManual = (int) ($horario->calculo_manual ?? 0);

                    if ($esManual === 0) {
                        if ($m_salida) {
                            // Calculamos los minutos de diferencia
                            $minutosExtraReales = max(0, $h_salida->diffInMinutes($m_salida, false));

                            // Convertimos a formato HH:MM
                            $horasFormato = floor($minutosExtraReales / 60);
                            $minsFormato = $minutosExtraReales % 60;
                            $formatoExtraFinal = sprintf('%02d:%02d', $horasFormato, $minsFormato);

                            // 3. Guardamos en el modelo solo si está en modo automático
                            if ($horario) {
                                $horario->extra = $formatoExtraFinal;
                                // Importante: Nos aseguramos que al guardar, calculo_manual siga en 0
                                $horario->calculo_manual = 1;
                                $horario->save();
                            }
                        } else {
                            // Si no hay salida, el extra es cero
                             if ($horario) {
                                $horario->extra = '00:00';
                                $horario->save();
                             }
                        }
                    }
                    // 2. Si no es manual, calculamos la diferencia real HS - HSP

                    // } else {
                    //     // 4. Si es 1, el index NO toca la base de datos.
                    //     // Mantiene lo que puso tu método 'update' (ej: los 10 min de Ascencio)
                    //     //  \Log::info("Horario ID {$horario->id}: Se respetó el descuento manual y se saltó el cálculo automático.");
                    // }
                    // ------------ agregar extra a la bd

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
                            $finConteo = $m_salida->lt($h_salida_prog) ? $m_salida : $h_salida_prog;

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
                // \Log::info('---------- INICIO DE PROCESO DE DESCUENTO ----------');
                // \Log::info("Marcación Actual ID: {$marcacione->id} | Empleado: {$marcacione->empleado_id}");

                // 1. Fuente de horas extra
                $fuenteExtra = Marcacion::findOrFail($data['extraSeleccionada']);
                // \Log::info("Bolsa Seleccionada (Marcación ID): {$fuenteExtra->id} | Fecha Bolsa: {$fuenteExtra->fecha}");

                $horarioFuente = Horario::where('fecha', $fuenteExtra->fecha)
                    ->where('empleado_id', $fuenteExtra->empleado_id)
                    ->firstOrFail();

                // \Log::info("Registro en Horarios encontrado. ID Horario: {$horarioFuente->id} | Valor 'extra' actual: '{$horarioFuente->extra}'");

                // 2. Cálculo de minutos
                $partesExtra = explode(':', $horarioFuente->extra);
                $minutosReales = ($partesExtra[0] * 60) + $partesExtra[1];
                $minutosAConsumir = floor($minutosReales / 30) * 30;

                // \Log::info("Cálculo: Minutos Reales: {$minutosReales}m | Bloque de 30 a consumir: {$minutosAConsumir}m");

                if ($minutosAConsumir <= 0) {
                    // \Log::error("ERROR: No hay bloques de 30 min. Minutos reales: {$minutosReales}");
                    throw new \Exception('La bolsa seleccionada ya no tiene bloques de 30 minutos disponibles.');
                }

                // 3. Cálculo de la Nueva Hora
                $horaActualString = $marcacione->{$data['tipo']};
                $horaCarbon = \Carbon\Carbon::parse($horaActualString);
                $horaOriginalParaLog = $horaCarbon->format('H:i:s');

                if ($data['tipo'] === 'ingreso') {
                    $horaCarbon->subMinutes($minutosAConsumir);
                } else {
                    $horaCarbon->addMinutes($minutosAConsumir);
                }
                $nuevaHora = $horaCarbon->format('H:i:s');
                // \Log::info("Ajuste de Hora: Tipo: {$data['tipo']} | Original: {$horaOriginalParaLog} -> Nueva: {$nuevaHora}");

                // 4. Auditoría
                MarcacionEdicion::create([
                    'empleado_id' => $marcacione->empleado_id,
                    'user_id' => Auth::id(),
                    'fecha' => $marcacione->fecha,
                    'hora_original' => $marcacione->{$data['tipo']},
                    'hora' => $nuevaHora,
                    'motivo' => $data['motivo'],
                ]);

                // 5. Actualizar marcación corregida
                $marcacione->update([$data['tipo'] => $nuevaHora]);

                // 6. DESCUENTO DE LA BOLSA (Aquí está el problema)
                $nuevoSaldoMinutos = $minutosReales - $minutosAConsumir;
                $horas = floor($nuevoSaldoMinutos / 60);
                $mins = $nuevoSaldoMinutos % 60;
                $nuevoValorExtraString = sprintf('%02d:%02d:00', $horas, $mins);

                // ---- Logica para , determinar de donde salen las HE
                $fechaDestino = \Carbon\Carbon::parse($marcacione->fecha)->format('d/m');
                $mensajeDestino = "Usado {$minutosAConsumir}m para el {$fechaDestino}";

                DB::table('horarios')
                    ->where('id', $horarioFuente->id)
                    ->update([
                        'extra' => $nuevoValorExtraString,
                        'destino_compensacion' => $mensajeDestino, // <--- Nueva columna
                        'calculo_manual' => 1,
                    ]);

                // ---- Logica para , determinar de donde salen las HE
                // \Log::info("Nuevo saldo calculado: {$nuevoSaldoMinutos}m | String resultante: '{$nuevoValorExtraString}'");

                // Usamos Query Builder para forzar la escritura y ver el resultado
                // $afectado = DB::table('horarios')
                //     ->where('id', $horarioFuente->id)
                //     ->update(['extra' => $nuevoValorExtraString]);

                // \Log::info('Resultado del UPDATE en tabla Horarios: '.($afectado ? 'ÉXITO (1 fila)' : 'FALLO (0 filas afectas)'));

                // 7. Bloqueo de disponibilidad
                if ($nuevoSaldoMinutos < 30) {
                    $fuenteExtra->update(['estado_horas_extra' => 1]);
                    // \Log::info('Bolsa agotada (Saldo < 30). Se cambió estado_horas_extra a 0.');
                }

                // \Log::info('---------- FIN DE PROCESO EXITOSO ----------');
            });

            return back()->with('success', 'Marcación corregida y horas extra descontadas correctamente.');

        } catch (\Exception $e) {
            // \Log::error('---------- ERROR CRÍTICO ----------');
            // \Log::error('Mensaje: '.$e->getMessage());
            // \Log::error('Archivo: '.$e->getFile().' Línea: '.$e->getLine());

            return back()->withErrors(['message' => 'Error: '.$e->getMessage()]);
        }
    }

    public function getHorasExtraDisponibles(Request $request, $empleado)
    {
        $inicio = $request->query('fechaInicio');
        $fin = $request->query('fechaFin');

        // Buscamos el nombre del empleado para el log
        $worker = \DB::table('empleados')->where('id', $empleado)->select('nombres', 'apellidos')->first();
        $nombreCompleto = $worker ? "{$worker->apellidos} {$worker->nombres}" : 'DESCONOCIDO';

        // \Log::channel('single')->emergency('---------- REVISIÓN DE EXTRAS ----------');
        // \Log::channel('single')->emergency("EMPLEADO: [ID: $empleado] $nombreCompleto");
        // \Log::channel('single')->emergency("RANGO: $inicio hasta $fin");

        $query = \DB::table('marcacions as m')
            ->join('horarios as h', function ($join) {
                $join->on('h.fecha', '=', 'm.fecha')
                    ->on('h.empleado_id', '=', 'm.empleado_id');
            })
            ->where('m.empleado_id', $empleado)
            ->where('m.estado_horas_extra', 1)
            ->whereNotNull('h.extra')
            ->where('h.extra', '!=', '00:00');

        if ($inicio && $fin) {
            $query->whereBetween('m.fecha', [$inicio, $fin]);
        }

        $extras = $query->select('m.id', 'm.fecha', 'h.extra as extra_db', 'm.estado_horas_extra')->get();

        // \Log::channel('single')->emergency('REGISTROS ENCONTRADOS EN DB: '.$extras->count());

        $extrasProcesadas = $extras->map(function ($registro) {

            // \Log::channel('single')->emergency("DATO CRUDO EN DB PARA FECHA {$registro->fecha}: '{$registro->extra_db}'");

            $partes = explode(':', (string) $registro->extra_db);

            if (count($partes) < 2) {
                // \Log::channel('single')->emergency('FALLO EXPLODE: El formato no es HH:MM');

                return null;
            }

            $horas = (int) $partes[0];
            $minutos = (int) $partes[1];

            $minutosTotales = ($horas * 60) + $minutos;
            $ajustado = floor($minutosTotales / 30) * 30;

            // \Log::channel('single')->emergency("FECHA: {$registro->fecha} | DB: {$registro->extra_db} | MIN: $minutosTotales | AJUSTADO: $ajustado");

            return [
                'id' => $registro->id,
                'fecha' => $registro->fecha,
                'extra' => (int) $ajustado,
            ];
        })
            ->filter(fn ($item) => $item !== null && $item['extra'] >= 30)
            ->values();

        // \Log::channel('single')->emergency('FINAL ENVIADO AL FRONT: '.$extrasProcesadas->count());
        // \Log::channel('single')->emergency('----------------------------------------');

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

                // 2. Agrupamos por Jornada Lógica (Regla de las 05:00 AM)
                $grupos = [];
                foreach ($todasLasMarcasRaw as $item) {
                    $empleadoId = $dnis->get($item->tarjeta);
                    $f = \Carbon\Carbon::parse($item->fecha);
                    $fechaLogica = ($item->hora < '05:00:00') ? $f->subDay()->format('Y-m-d') : $f->format('Y-m-d');
                    $key = $fechaLogica.'-'.$empleadoId;
                    $grupos[$key][] = $item->hora;
                }

                // 3. Procesamos cada grupo con lógica antibug
                foreach ($grupos as $key => $horasArray) {
                    $partes = explode('-', $key);
                    $fechaLogica = $partes[0].'-'.$partes[1].'-'.$partes[2];
                    $empleadoId = end($partes);

                    $marcas = collect($horasArray)->unique()->sort()->values();
                    $madrugada = $marcas->filter(fn ($h) => $h < '05:00:00')->values();
                    $tarde = $marcas->filter(fn ($h) => $h >= '05:00:00')->values();

                    $ingreso = null;
                    $salida = null;
                    $ingreso_refri = null;
                    $salida_refri = null;

                    if ($tarde->isNotEmpty()) {
                        $ingreso = $tarde->first();

                        // CASO A: TIENE MARCA DE MADRUGADA (Salida al día siguiente)
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
                        // CASO B: TODO OCURRE EL MISMO DÍA
                        else {
                            $conteo = $tarde->count();
                            if ($conteo == 2) {
                                $salida = $tarde->last();
                            } elseif ($conteo == 3) {
                                // Lógica para AARON: ¿La 3ra marca es refri o salida?
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

                    // 4. GUARDADO FORZADO: updateOrCreate machaca errores previos
                    Marcacion::updateOrCreate(
                        ['empleado_id' => $empleadoId, 'fecha' => $fechaLogica],
                        [
                            'ingreso' => $ingreso,
                            'salida' => $salida,
                            'ingreso_refri' => $ingreso_refri,
                            'salida_refri' => $salida_refri,
                        ]
                    );

                    // Permisos de descanso
                    $h = $horarios->get($key);
                    if ($h && $h->estado === 'D' && ($ingreso || $salida)) {
                        Permiso::firstOrCreate([
                            'empleado_id' => $empleadoId, 'tipo_id' => 24, 'fecha' => $fechaLogica, 'estado' => 0,
                        ], ['motivo' => 'TRABAJO EN DIA DE DESCANSO']);
                    }
                }
            });

            return back()->with('success', 'Sincronización completada.');
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
