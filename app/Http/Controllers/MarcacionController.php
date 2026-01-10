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

        $lista = $empleados->flatMap(function ($empleado) use ($horarios, $horariosExtra, $marcaciones, $request) {
            $fechas = CarbonPeriod::create($request->fechaInicio, $request->fechaFin);

            return collect($fechas)->map(function ($fecha) use ($empleado, $horarios, $horariosExtra, $marcaciones) {
                $horario = $horarios->get($empleado->id)?->firstWhere('fecha', $fecha);
                $horarioExtra = $horariosExtra->get($empleado->id);
                $marcacion = $marcaciones->get($empleado->id)?->firstWhere('fecha', $fecha);

                $tardanza = 0;
                $horas = 0;
                $extra = 0;
                $anticipado = 0;
                $nocturno = 0;

                if ($horario && $marcacion && $marcacion->ingreso) {
                    // Mantengo tu variable partTime por si la usas en otro lado,
                    // pero la lógica de descuento ahora es más precisa abajo.
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
                                $nocturno = floor($nocturno / 60) * 60;
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

        $marcaciones = Zktimems::query()
            ->with(['empleado' => function ($query) {
                $query->select('id', 'dni', 'nombres', 'apellidos');
            }])
            ->whereIn('tarjeta', $empleadosDnis)
            ->whereBetween('fecha', [$request->fechaInicio, $request->fechaFin])
            ->get(['hora', 'tarjeta', 'fecha']);

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
        // 1. Valida que venga una empresa válida y una fecha válida
        $data = $request->validate([
            'empresa' => 'required|integer|exists:empresas,id',
            'fecha' => 'required|date',
        ]);

        try {
            DB::transaction(function () use ($data) {
                $dnis = Empleado::where('empresa_id', $data['empresa'])->whereNull('fecha_cese')->pluck('id', 'dni');

                // CARGAR TODOS LOS HORARIOS DE UNA SOLA VEZ
                $horarios = Horario::whereIn('empleado_id', $dnis->values())
                    ->whereBetween('fecha', [$data['fecha'], now()->toDateString()])
                    ->get()
                    ->keyBy(function ($horario) {
                        return $horario->fecha->format('Y-m-d').'-'.$horario->empleado_id;
                    });

                Zktimems::whereBetween('fecha', [$data['fecha'], now()->toDateString()])
                    ->whereIn('tarjeta', $dnis->keys())
                    ->get(['tarjeta', 'fecha', 'hora'])
                    ->groupBy(function ($item) use ($dnis, $horarios) {
                        $empleadoId = $dnis->get($item->tarjeta);
                        $horaMarca = $item->hora; // formato HH:MM:SS
                        $fechaMarca = $item->fecha->format('Y-m-d');

                        // REGLA DE ORO: Si marca entre 00:00 y 05:00 AM
                        if ($horaMarca < '05:00:00') {
                            $fechaAyer = $item->fecha->copy()->subDay()->format('Y-m-d');
                            $keyAyer = $fechaAyer.'-'.$empleadoId;

                            // Verificamos si ayer tenía un horario que salía en la madrugada
                            $horarioAyer = $horarios->get($keyAyer);

                            if ($horarioAyer) {
                                $h_ingreso = \Carbon\Carbon::parse($horarioAyer->ingreso);
                                $h_salida = \Carbon\Carbon::parse($horarioAyer->salida);

                                // Si el horario de ayer era nocturno (ej. 15:00 a 01:00),
                                // esta marca de las 00:04 le pertenece a AYER.
                                if ($h_salida->lt($h_ingreso)) {
                                    return $keyAyer;
                                }
                            }
                        }

                        // Si no es madrugada o no tiene horario nocturno, se queda en su fecha normal
                        return $fechaMarca.'-'.$empleadoId;
                    })
                    ->each(function ($items, $key) use ($horarios) {
                        // 1. Extraemos datos de la llave
                        $partes = explode('-', $key);
                        $fechaLogica = $partes[0].'-'.$partes[1].'-'.$partes[2];
                        $empleadoId = end($partes);

                        // 2. Procesamos todas las horas del grupo
                        $todasLasHoras = $items->pluck('hora')->filter()->unique()->sort()->values();

                        // SEPARACIÓN CRÍTICA:
                        // Marcas de madrugada (salidas del día anterior)
                        $marcasMadrugada = $todasLasHoras->filter(fn ($h) => $h < '05:00:00');
                        // Marcas normales (ingresos y salidas de hoy)
                        $horas = $todasLasHoras->filter(fn ($h) => $h >= '05:00:00')->values();

                        // --- A. PROCESO DE "DEVOLUCIÓN" A AYER ---
                        if ($marcasMadrugada->isNotEmpty()) {
                            $fechaAyer = \Carbon\Carbon::parse($fechaLogica)->subDay()->format('Y-m-d');
                            $marcacionAyer = Marcacion::where('empleado_id', $empleadoId)
                                ->whereDate('fecha', $fechaAyer)
                                ->first();

                            // Si existe el registro de ayer y no está validado, le ponemos su salida
                            if ($marcacionAyer && $marcacionAyer->estado == 0) {
                                $marcacionAyer->update([
                                    'salida' => $marcasMadrugada->last(),
                                ]);
                            }
                        }

                        // --- B. PROCESO DE HOY (Tu lógica original mejorada) ---
                        if ($horas->isNotEmpty()) {
                            if ($horas->count() > 4) {
                                $horas = Marcacion::validarHora($horas);
                            }

                            // Ahora $ingreso será realmente la marca de la tarde, no la de las 00:04
                            $ingreso = $horas->count() > 0 ? $horas->get(0) : null;
                            $salida = $horas->count() >= 2 ? $horas->last() : null;
                            $ingreso_refri = $horas->count() >= 3 ? $horas->get(1) : null;
                            $salida_refri = $horas->count() == 4 ? $horas->get(2) : null;

                            $horario = $horarios->get($key);

                            // Lógica de Permisos (Días de descanso)
                            if ($horario && $horario->estado === 'D' && ($ingreso || $salida)) {
                                $permisoExistente = Permiso::where('empleado_id', $empleadoId)
                                    ->where('tipo_id', 24)
                                    ->whereDate('fecha', $fechaLogica)
                                    ->where('estado', '!=', 2)
                                    ->exists();

                                if (! $permisoExistente) {
                                    Permiso::create([
                                        'empleado_id' => $empleadoId,
                                        'tipo_id' => 24,
                                        'fecha' => $fechaLogica,
                                        'motivo' => 'TRABAJO EN DIA DE DESCANSO',
                                        'estado' => 0,
                                    ]);
                                }
                            }

                            // Búsqueda y actualización de la Marcación de HOY
                            $marcacion = Marcacion::where('empleado_id', $empleadoId)
                                ->whereDate('fecha', $fechaLogica)
                                ->first();

                            if (! $marcacion) {
                                $marcacion = Marcacion::create([
                                    'empleado_id' => $empleadoId,
                                    'fecha' => $fechaLogica,
                                ]);
                            }

                            if ($marcacion->estado == 0) {
                                $marcacion->update([
                                    'ingreso' => $ingreso ?? $marcacion->ingreso,
                                    'salida' => $salida ?? $marcacion->salida,
                                    'ingreso_refri' => $ingreso_refri ?? $marcacion->ingreso_refri,
                                    'salida_refri' => $salida_refri ?? $marcacion->salida_refri,
                                ]);
                            }
                        }
                    });
            });

            return back()->with('success', 'Marcaciones sincronizadas correctamente');
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
