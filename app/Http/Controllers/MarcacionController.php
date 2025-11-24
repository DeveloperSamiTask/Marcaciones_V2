<?php

namespace App\Http\Controllers;

use App\Exports\MarcacionExport;
use App\Http\Requests\Marcacion\StoreMarcacionRequest;
use App\Http\Requests\Marcacion\UpdateMarcacionRequest;
use App\Models\Descuento_extra;
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
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;

class MarcacionController extends Controller
{

    public function index(Request $request)//: Response
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
                    $partTime = $empleado->jornada_id == 2 && ! $marcacion->ingreso_refri;

                    // Tiempo total programado
                    $horasTrabajadas = $horario->ingreso->diffInMinutes($horario->salida, false);
                    $horasAnticipado = $marcacion->salida ? max(0, $marcacion->salida->diffInMinutes($horario->salida, false)) : 0; // hora antes de su salida programado considerar 20 min si es su salida programada 11 o 11:30

                    $tardanza = max(0, $horario->ingreso->diffInMinutes($marcacion->ingreso, false)); // si es negativo devuelve 0
                    $extra = max(0, $horario->salida->diffInMinutes($marcacion->salida, false)); // tiempo despues de su hora de salida
                    $horas = $horasTrabajadas - $tardanza - ($partTime ? 0 : 60); // no se descuenta la hora de refrigerio si es parttime y no tomo refrigerio
                    $anticipado = $horasAnticipado;

                    if ((in_array($horario->salida->format('H:i'), ['23:00', '23:30', '23:59']) && ($empleado->empresa_id == 4 || $empleado->empresa_id == 3)) || ($horario->salida->format('H:i') == '18:30' && $empleado->empresa_id == 1)) { // solo para chacxra y granja
                        $minutosTolerancia = $empleado->empresa_id == 1 ? 30 : 20; // estos minutos son de tolerancia en salida anticipada, solo granja tiene hasta 30 minutos
                        $anticipado += $horasAnticipado >= $minutosTolerancia ? $horasAnticipado : 0;
                    }

                    if ($empleado->empresa_id == 1 || $empleado->empresa_id == 4 || $empleado->empresa_id == 3) { // nocturno + de 22horas
                        $nocturno = max(0, $horario->salida->copy()->setTime(22, 0)->diffInMinutes($horario->salida, false));
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

    public function real(Request $request) //: Response
    {
        $filters = $request->validate([
            'empresa' => 'nullable|integer|exists:empresas,id',
            'encargado' => 'nullable|integer|exists:empleados,id',
            'fechaInicio' => 'nullable|date',
            'fechaFin' => 'nullable|date|after_or_equal:fechaInicio',
        ]);

        $empresas = Empresa::where('estado', 1)->get(['id', 'razonsocial']);
        $encargados = User::with('empleado')->where('estado', true)->get()->sortBy(fn ($encargado) => $encargado->empleado->apellidos)->values();

        $empleadosDnis = Empleado::query()
            ->where('empresa_id', $request->empresa)
            ->when($request->encargado, fn($query) => $query->where('jefe_id', $request->encargado))
            ->when($request->fechaFin, function ($query) use ($request) {
                $query->whereDate('fecha_ingreso', '<=', $request->fechaFin);
            })
            ->whereNull('fecha_cese')
            ->pluck('dni');

        /* jala la hora de la otra bd de la marcacion real */
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

    /*Enviar las marcaciones a asistencias */
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

                // VALIDACIÓN 1: Solo aplica para salida con HSP
                if ($data['tipo'] !== 'salida' || ! isset($data['hsp'])) {
                    $marcacione->update([$data['tipo'] => $data['hora_nueva']]);

                    return;
                }

                try {
                    // USAR Carbon::parse EN LUGAR DE createFromFormat
                    $hsActual = Carbon::parse($marcacione->salida);
                    $hsp = Carbon::parse($data['hsp']);
                    $nuevaHora = Carbon::parse($data['hora_nueva']);

                } catch (Exception $e) {
                    throw new Exception('Error en formato de hora: '.$e->getMessage());
                }
                // DEBUG: Log después de Carbon

                // VALIDACIÓN 2: No editar si HS ≥ HSP

                if ($hsActual->gte($hsp)) {
                    throw new Exception("No puede editar - La hora de salida actual ({$hsActual->format('H:i')}) ya es mayor o igual a la HSP ({$hsp->format('H:i')})");
                }
                // VALIDACIÓN 3: No permitir retroceder hora

                if ($nuevaHora->lte($hsActual)) {
                    throw new Exception("No puede disminuir la hora. Hora actual: {$hsActual->format('H:i')}, Nueva hora: {$nuevaHora->format('H:i')}");
                }
                // VALIDACIÓN 4: Nueva hora no puede exceder HSP
                if ($nuevaHora->gt($hsp)) {
                    throw new Exception("La nueva hora ({$nuevaHora->format('H:i')}) no puede exceder la HSP ({$hsp->format('H:i')})");
                }
                // VALIDACIÓN 5: Debe tener tiempo_extra
                if (! isset($data['tiempo_extra'])) {
                    throw new Exception('Se requiere tiempo extra para ajustar la hora de salida');
                }
                // VALIDACIÓN 6: Verificar horas extras disponibles
                $horariosConExtras = Horario::where('empleado_id', $marcacione->empleado_id)
                    ->whereNotNull('extra')
                    ->get();
                if ($horariosConExtras->isEmpty()) {
                    throw new Exception('No tiene horas extras disponibles para realizar el ajuste');
                }
                // Convertir tiempo_extra a minutos
                [$horas, $minutos] = explode(':', $data['tiempo_extra']);
                $tiempoExtraMinutos = ($horas * 60) + $minutos;
                // Calcular total horas extras disponibles
                $totalHorasExtras = $horariosConExtras->sum(function ($horario) {
                    try {
                        // VERIFICAR FORMATO DE EXTRA

                        if (is_numeric($horario->extra)) {
                            return (int) $horario->extra;
                        } elseif (is_string($horario->extra)) {
                            [$h, $m] = explode(':', $horario->extra);

                            return ($h * 60) + $m;
                        } else {
                            return Carbon::today()->diffInMinutes($horario->extra);
                        }
                    } catch (Exception $e) {

                        return 0;
                    }
                });
                // VALIDACIÓN 7: Tiempo extra no puede superar horas extras disponibles
                if ($tiempoExtraMinutos > $totalHorasExtras) {
                    throw new Exception("No tiene suficientes horas extras. Disponibles: {$totalHorasExtras} minutos, Requeridos: {$tiempoExtraMinutos} minutos");
                }
                // VALIDACIÓN 8: El tiempo_extra debe coincidir con la diferencia real
                $diferenciaReal = $hsActual->diffInMinutes($nuevaHora);
                if ($tiempoExtraMinutos != $diferenciaReal) {
                    throw new Exception("El tiempo extra ({$tiempoExtraMinutos}min) no coincide con la diferencia real ({$diferenciaReal}min)");
                }
                // ... resto del código de actualización ...
                // 1. REGISTRAR Descuento_extra
                $horarioExtra = Horario::where('fecha', $marcacione->fecha)
                    ->where('empleado_id', $marcacione->empleado_id)
                    ->first();

                // 2. DESCONTAR HORAS EXTRAS
                $tiempoRestante = $tiempoExtraMinutos;

                foreach ($horariosConExtras as $horario) {
                    if ($horario->extra && $tiempoRestante > 0) {
                        $extraDisponible = Carbon::today()->diffInMinutes($horario->extra);

                        if ($extraDisponible >= $tiempoRestante) {
                            $horario->extra = $horario->extra->subMinutes($tiempoRestante);
                            $horario->save();
                            break;
                        } else {
                            $tiempoRestante -= $extraDisponible;
                            $horario->extra = null;
                            $horario->save();
                        }
                    }
                }

                $marcacione->update(['salida' => $data['hora_nueva']]);
                // Verificar que sí se actualizó
                $marcacioneActualizada = $marcacione->fresh();
                // 4. REGISTRO DE AUDITORÍA
                Descuento_extra::create([

                    'marcacion_id' => $marcacione->id,
                    'horario_id' => $horarioExtra->id,
                    'user_id' => Auth::user()->id,

                    'hora_original' => $data['hora_original'],
                    'hora_modificada' => $marcacione->getOriginal('salida'),
                    'total_horas_descontadas' => $data['tiempo_extra'],
                    'motivo' => $data['motivo'],
                ]);

            });

        } catch (Exception $e) {

            return back()->withErrors(['message' => $e->getMessage()]);
        }

        return redirect()->back()->with('success', 'Marcación actualizada correctamente');
    }

    /*
    public function update_Respaldo(UpdateMarcacionRequest $request, Marcacion $marcacione)
    {

        $data = $request->validated();
        try {

            DB::transaction(function () use ($data, $marcacione) {

                $horarioExtra = Horario::where('fecha', $marcacione->fecha)
                    ->where('empleado_id', $marcacione->empleado_id)
                    ->first();

                Descuento_extra::create([
                    'marcacion_id' => $marcacione->id,
                    'user_id' => Auth::id(),
                    'horario_id' => $horarioExtra->id,
                    'hora_modificada' => $data['hora_original'],
                    'fecha' => $marcacione->fecha,
                    'total_horas_descontadas' => $data['tiempo_extra'],
                    'motivo' => $data['motivo'],
                ]);

                $marcacione->update([$data['tipo'] => $data['tiempo_extra']]);

                $horarios = Horario::where('empleado_id', $marcacione->empleado_id)->whereNotNull('extra')->get();

                if ($horarios->count() > 0) { // validar si hay horas extra registradas
                    // fecha que se paso
                    $horarioExtra = Horario::where('fecha', $marcacione->fecha)->where('empleado_id', $marcacione->empleado_id)->first();
                    // tiempo de + o menos
                    $marcacionExtra = $horarioExtra->salida->diffInMinutes($marcacione->salida);

                    // itera sobre todos los horarios para conseguir las h.extras
                    foreach ($horarios as $horario) {

                        // Verificar si tenemos suficiente  tiempo en el la iteracion actual
                        if ($horario->extra) {

                            // se consigue el extra de ese horario en particular
                            $extraTime = $horario->extra;

                            // si el extra es mayor al tiempo que se paso se resta y se sale del ciclo
                            if (Carbon::today()->diffInMinutes($horario->extra) > $marcacionExtra) {

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
    */

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
                    $path = $file->store('asistencia/' . $marcacion->id, 'public'); // Almacenar el archivo en la carpeta public del storage
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
                        return $horario->fecha->format('Y-m-d') . '-' . $horario->empleado_id;
                    });

                Zktimems::whereBetween('fecha', [$data['fecha'], now()->toDateString()])
                    ->whereIn('tarjeta', $dnis->keys())
                    ->get(['tarjeta', 'fecha', 'hora'])
                    ->groupBy(fn($item) => $item->fecha->format('Y-m-d') . '-' . $item->tarjeta)
                    ->each(function ($items) use ($dnis, $horarios) {
                        $item = $items->first();
                        $horas = $items->pluck('hora')->filter()->unique()->sort()->values();

                        if ($horas->count() > 4) {
                            $horas = Marcacion::validarHora($horas);
                        }

                        $ingreso = $horas->count() > 0 ? $horas->get(0) : null;
                        $salida = $horas->count() >= 2 ? $horas->last() : null;
                        $ingreso_refri = $horas->count() >= 3 ? $horas->get(1) : null;
                        $salida_refri = $horas->count() == 4 ? $horas->get(2) : null;

                        $empleadoId = $dnis->get($item->tarjeta);
                        $fecha = $item->fecha;
                        $fechaKey = $fecha->format('Y-m-d') . '-' . $empleadoId;

                        // Buscar el horario en la colección cargada
                        $horario = $horarios->get($fechaKey);

                        // Validar si es día de DESCANSO y tiene marcaciones
                        if ($horario && $horario->estado === 'D' && ($ingreso || $salida)) {

                            // Verificar si ya existe un permiso para evitar duplicados
                            $permisoExistente = Permiso::where('empleado_id', $empleadoId)
                                ->where('tipo_id', 24) // TRABAJO DIA DESCANSO
                                ->whereDate('fecha', $fecha)
                                ->where('estado', '!=', 2) // que no esté rechazado
                                ->exists();

                            if (!$permisoExistente) {
                                Permiso::create([
                                    'empleado_id' => $empleadoId,
                                    'tipo_id' => 24, // TRABAJO DIA DESCANSO
                                    'fecha' => $fecha,
                                    'motivo' => 'TRABAJO EN DIA DE DESCANSO',
                                    'estado' => 0, // Pendiente de aprobación
                                ]);

                                Log::info('Permiso creado: Trabajo en día de descanso', [
                                    'empleado_id' => $empleadoId,
                                    'fecha' => $fecha->format('Y-m-d'),
                                    'ingreso' => $ingreso,
                                    'salida' => $salida,
                                ]);
                            }
                        }

                        $marcacion = Marcacion::where('empleado_id', $empleadoId)
                            ->whereDate('fecha', $fecha)
                            ->first();

                        if (!$marcacion) {
                            $marcacion = Marcacion::create([
                                'empleado_id' => $empleadoId,
                                'fecha' => $fecha,
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
