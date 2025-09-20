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

        $horariosExtra = Horario::whereIn('empleado_id', $empleados->pluck('id'))
            ->whereNotNull('extra')
            ->get()
            ->groupBy('empleado_id');

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

                    // 1. Tardanza
                    $tardanza = max(0, $horario->ingreso->diffInMinutes($marcacion->ingreso, false));

                    // 2. Extra (si salió después)
                    if ($marcacion->salida && $marcacion->salida->gt($horario->salida)) {
                        $extra = $horario->salida->diffInMinutes($marcacion->salida);
                    }

                    // 3. Anticipado (si salió antes)
                    if ($marcacion->salida && $horario->salida && $marcacion->salida->lt($horario->salida)) {
                        $anticipado = $horario->salida->diffInMinutes($marcacion->salida);

                        // Aplica tolerancia según empresa
                        /*if (
                            (in_array($horario->salida->format('H:i'), ['23:00', '23:30', '23:59']) && in_array($empleado->empresa_id, [3, 4])) ||
                            ($horario->salida->format('H:i') == '18:30' && $empleado->empresa_id == 1)
                        ) {
                            $minutosTolerancia = $empleado->empresa_id == 1 ? 30 : 20;
                            $anticipado = $anticipado >= $minutosTolerancia ? $anticipado : 0;
                        }*/
                    }

                    // 4. Total de horas (restando tardanza y refrigerio si aplica)
                    $horas = $horasTrabajadas - $tardanza - ($partTime ? 0 : 60);

                    // 5. Nocturno (después de las 22:00)
                    if (in_array($empleado->empresa_id, [1, 3, 4])) {
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

    public function real(Request $request)// : Response
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
            ->when($request->encargado, fn ($query) => $query->where('jefe_id', $request->encargado))
            ->when($request->fechaFin, function ($query) use ($request) {
                $query->whereDate('fecha_ingreso', '<=', $request->fechaFin);
            })
            ->whereNull('fecha_cese')
            ->pluck('dni');

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
                    'hora' => $data['hora'],
                    'motivo' => $data['motivo'],
                ]);

                $marcacione->update([$data['tipo'] => $data['hora']]);

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

    public function update_Prueba(UpdateMarcacionRequest $request, Marcacion $marcacione)
    {
        $data = $request->validated();

        // horario dond se paso
        $horarioExtra = Horario::where('fecha', $marcacione->fecha)->where('empleado_id', $marcacione->empleado_id)->first();

        try {

            /*
            $table->foreignId('marcacion_id')->constrained('permisos')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            //$table->foreignId('permiso_id')->constrained('permisos')->onDelete('cascade');

            $table->foreignId('horario_id')->constrained('horarios')->onDelete('cascade');



            $table->time('hora_modificada');

            $table->time('total_horas_descontadas');

            $table->string('motivo')->nullable();
            */

            DB::transaction(function () use ($data, $marcacione) {
                // Registrar la edición
                Descuento_extra::create([
                    'marcacion_id' => $marcacione->id,
                    'user_id' => Auth::id(),
                    'fecha' => $marcacione->fecha,
                    'hora_original' => $marcacione->{$data['tipo']},
                    'hora' => $data['hora'],
                    'motivo' => $data['motivo'],
                ]);

            });

            return redirect()->back()->with('success', 'Marcación actualizada correctamente.');
        } catch (\Exception $e) {
            return back()->withErrors(['message' => 'Error al actualizar la marcación: '.$e->getMessage()]);
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

    public function pull(Request $request)
    {
        $data = $request->validate([
            'empresa' => 'required|integer|exists:empresas,id',
            'fecha' => 'required|date',
        ]);

        try {
            DB::transaction(function () use ($data) {
                $dnis = Empleado::where('empresa_id', $data['empresa'])->whereNull('fecha_cese')->pluck('id', 'dni');
                Zktimems::whereBetween('fecha', [$data['fecha'], now()->toDateString()])
                    ->whereIn('tarjeta', $dnis->keys())
                    ->get(['tarjeta', 'fecha', 'hora'])
                    ->groupBy(fn ($item) => $item->fecha->format('Y-m-d').'-'.$item->tarjeta)
                    ->each(function ($items) use ($dnis) {
                        $item = $items->first();
                        $horas = $items->pluck('hora')->filter()->unique()->sort()->values();
                        if ($horas->count() > 4) {
                            $horas = Marcacion::validarHora($horas);
                        }
                        $ingreso = $horas->count() > 0 ? $horas->get(0) : null;
                        $salida = $horas->count() >= 2 ? $horas->last() : null;
                        $ingreso_refri = $horas->count() >= 3 ? $horas->get(1) : null;
                        $salida_refri = $horas->count() == 4 ? $horas->get(2) : null;
                        $marcacion = Marcacion::where('empleado_id', $dnis->get($item->tarjeta))->whereDate('fecha', $item->fecha)->first();

                        if (! $marcacion) {
                            $marcacion = Marcacion::create([
                                'empleado_id' => $dnis->get($item->tarjeta),
                                'fecha' => $item->fecha,
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
