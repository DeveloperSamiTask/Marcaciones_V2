<?php

namespace App\Http\Controllers;

use App\Models\AsistenciaDetalle;
use App\Models\Empleado;
use App\Models\Empresa;
use App\Models\Feriado;
use App\Models\Horario;
use App\Models\Marcacion;
use App\Models\Permiso;
use App\Models\SolicitudHorasExtrasPT;
use App\Models\Suspension;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class PermisoController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'empresa' => 'nullable|integer|exists:empresas,id',
            'fechaInicio' => 'nullable|date',
            'fechaFin' => 'nullable|date|after_or_equal:fechaInicio',
        ]);

        $empresas = Empresa::where('estado', 1)->get(['id', 'razonsocial']);
        $permisos = Permiso::whereHas('empleado', function ($query) use ($request) {
            $query->where('empresa_id', $request->empresa)->whereNull('fecha_cese')
                ->when($request->user()->rol_id == 4, function ($q) use ($request) {
                    $q->where('jefe_id', $request->user()->empleado_id);
                });
        })
            ->whereNotIn('tipo_id', [2, 20])
            ->with(['empleado.area', 'tipo'])
            ->whereBetween('fecha', [$request->fechaInicio, $request->fechaFin])
            ->orderBy('fecha')
            ->get()
            ->groupBy(function ($item) {
                $estados = [
                    0 => 'pendientes',
                    1 => 'aprobados',
                    2 => 'rechazados',
                ];

                return $estados[$item->estado] ?? '';
            });

        session(['permisos_url' => $request->fullUrl()]);

        return Inertia::render('permisos/index', [
            'pendientes' => $permisos->get('pendientes', collect()),
            'aprobados' => $permisos->get('aprobados', collect()),
            'rechazados' => $permisos->get('rechazados', collect()),
            'empresas' => $empresas,
            'filters' => $filters,
        ]);
    }

    public function index_gerencia(Request $request): Response
    {

        $empresas = Empresa::where('estado', 1)->get(['id', 'razonsocial']);
        // ?? FILTROS
        $filters = $request->validate([
            'empresa' => 'nullable|integer|exists:empresas,id',
            'fechaInicio' => 'nullable|date',
            'fechaFin' => 'nullable|date|after_or_equal:fechaInicio',
        ]);

        // ?? QUERY CON FILTROS (SIN aprobador)
        $query = SolicitudHorasExtrasPT::with(['empleado.empresa']) // ?? SOLO esto
            ->where('estado', 0); // Solo pendientes

        // Aplicar filtros
        if (! empty($filters['empresa'])) {
            $query->whereHas('empleado', function ($q) use ($filters) {
                $q->where('empresa_id', $filters['empresa']);
            });
        }

        if (! empty($filters['fechaInicio'])) {
            $query->where('fecha_deteccion', '>=', $filters['fechaInicio']);
        }

        if (! empty($filters['fechaFin'])) {
            $query->where('fecha_deteccion', '<=', $filters['fechaFin']);
        }

        $solicitudes = $query->orderBy('fecha_limite_aprobacion', 'asc')->get();

        // ?? USUARIO LOGUEADO
        $usuario = auth()->user();

        return Inertia::render('permisos/index-gerencia', [
            'solicitudes' => $solicitudes->map(function ($solicitud) {
                return [
                    'id' => $solicitud->id,
                    'empleado_nombre' => $solicitud->empleado->nombres.' '.$solicitud->empleado->apellidos,
                    'empleado_area' => $solicitud->empleado_area,
                    'fecha_deteccion' => $solicitud->fecha_deteccion->format('d/m/Y'),
                    'fecha_cumplimiento_93h' => $solicitud->fecha_cumplimiento_93h->format('d/m/Y'),
                    'horas_acumuladas' => $solicitud->horas_acumuladas,
                    'horas_excedentes' => $solicitud->horas_acumuladas - 93,
                    'fecha_inicio_extras' => $solicitud->fecha_inicio_extras->format('d/m/Y'),
                    'fecha_limite_aprobacion' => $solicitud->fecha_limite_aprobacion->format('d/m/Y'),
                    'dias_restantes' => now()->diffInHours($solicitud->fecha_limite_aprobacion, false),
                    'estado' => $solicitud->estado,
                    // Para el futuro, cuando se apruebe:
                    'aprobado_por' => $solicitud->aprobado_por ?
                        \App\Models\User::find($solicitud->aprobado_por)->name : 'SISTEMA',
                ];
            }),
            'filters' => $filters,
            'usuario' => [
                'id' => $usuario->empleado_id,
                'nombre' => $usuario->name,
            ],
            'empresas' => $empresas,
        ]);
    }

    public function index_rrhh(Request $request): Response
    {
        return Inertia::render('permisos/index-rrhh');
    }

    public function extras(Request $request): Response
    {
        $filters = $request->validate([
            'empresa' => 'nullable|integer|exists:empresas,id',
            'fechaInicio' => 'nullable|date',
            'fechaFin' => 'nullable|date|after_or_equal:fechaInicio',
        ]);

        $empresas = Empresa::where('estado', 1)->get(['id', 'razonsocial']);

        $permisos = Permiso::whereHas('empleado', function ($query) use ($request) {
            $query->where('empresa_id', $request->empresa)
                ->whereNull('fecha_cese')
                ->when($request->user()->rol_id == 4, function ($q) use ($request) {
                    $q->where('jefe_id', $request->user()->empleado_id);
                });
        })
            ->whereIn('tipo_id', [2, 20])
            ->whereBetween('fecha', [$request->fechaInicio, $request->fechaFin])
            ->with(['empleado.area', 'tipo']) // Solo lo básico aquí
            ->orderBy('fecha')
            ->get();

        // Reemplazamos el 'each' costoso por lógica de colección en memoria
        $permisos->each(function ($permiso) {
            // Traemos solo EL registro que coincide con la fecha del permiso
            $horario = Horario::where('empleado_id', $permiso->empleado_id)
                ->whereDate('fecha', $permiso->fecha)
                ->first(['id', 'ingreso', 'salida', 'fecha']); // Solo columnas necesarias

            $marcacion = Marcacion::where('empleado_id', $permiso->empleado_id)
                ->whereDate('fecha', $permiso->fecha)
                ->first(['id', 'ingreso', 'salida', 'fecha']);

            // Inyectamos la relación directamente
            $permiso->setRelation('horario_dia', $horario);
            $permiso->setRelation('marcacion_dia', $marcacion);

            // Limpiamos la relación pesada de "empleado" para que no lleve
            // colecciones vacías de horarios/marcaciones al Front
            $permiso->empleado->unsetRelation('horarios');
            $permiso->empleado->unsetRelation('marcaciones');
        });

        $agrupados = $permisos->groupBy(function ($item) {
            $estados = [0 => 'pendientes', 1 => 'aprobados', 2 => 'rechazados'];

            return $estados[$item->estado] ?? 'otros';
        });

        session(['permisos_extras_url' => $request->fullUrl()]);

        return Inertia::render('permisos/extras', [
            'pendientes' => $agrupados->get('pendientes', collect()),
            'aprobados' => $agrupados->get('aprobados', collect()),
            'rechazados' => $agrupados->get('rechazados', collect()),
            'empresas' => $empresas,
            'filters' => $filters,
        ]);
    }

    // Aprobar permiso
    public function update(Request $request, Permiso $permiso)
    {
        if (! $permiso->comprobante && ($permiso->tipo_id == 7 || $permiso->tipo_id == 8 || $permiso->tipo_id == 10 || $permiso->tipo_id == 21 || $permiso->tipo_id == 22)) {
            return back()->withInput()->withErrors(['message' => 'Debes subir un comprobante']);
        }

        $validated = [];
        if ($permiso->tipo_id == 20) {
            $validated = $request->validate([
                // 'he_aprobada' => 'required|date_format:H:i',
                'he_anticipada' => 'required|numeric|min:0',
                'he_salida' => 'required|numeric|min:0',
            ]);
        }

        try {
            DB::transaction(function () use ($permiso, $validated) {
                $permiso->update(['estado' => 1]);

                $horario = Horario::where('empleado_id', $permiso->empleado_id) // buscamos el horario para actualizar su estado
                    ->whereDate('fecha', $permiso->fecha)
                    ->firstOrFail();

                /* Nueva seleccion de HE aprobadas */
                if ($permiso->tipo_id == 20) {

                    $totalMinutos = $validated['he_anticipada'] + $validated['he_salida'];
                    $totalFormateado = sprintf('%02d:%02d', floor($totalMinutos / 60), $totalMinutos % 60);



                    AsistenciaDetalle::where('empleado_id', $permiso->empleado_id)
                        ->whereDate('fecha', $permiso->fecha)
                        ->update(['estado_horas_extra' => 1]);

                    Marcacion::where('empleado_id', $permiso->empleado_id)
                        ->whereDate('fecha', $permiso->fecha)
                        ->update(['estado_horas_extra' => 1]);

                    $horario->update(['extra' => $totalFormateado]);

                    $empleado = Empleado::find($permiso->empleado_id, 'id');

                    Log:info('HE aprobadas: ' . json_encode([
                        'Emp: ' => $empleado->apellidos,
                        'Total: ' => $totalFormateado,
                        'Ant: ' => $validated['he_anticipada'],
                        'Sal: ' => $validated['he_salida'],
                    ], JSON_PRETTY_PRINT));

                    return;
                }

                if ($permiso->tipo_id == 2) { // SOLO PARA HORARIO PROGRAMADO EXTRA PARA PARTTIME
                    $horario->update(['estado' => 'L']);

                    return response()->json('Actualizado');
                }

                $horario->update(['estado' => $permiso->tipo->codigo]);

                if ($permiso->tipo_id == 24) {
                    $horario->update([
                        'estado' => 'TD',
                    ]);
                }

                if ($horario->estado == 'FI') { // se crea una suspension cuando es falta injustificada y cae un fin de semana
                    $finde = $horario->fecha->isWeekend();
                    $esFeriado = Feriado::whereDate('fecha', $horario->fecha)->exists();

                    $amonestacion = Suspension::create([
                        'empleado_id' => $permiso->empleado_id,
                        'tipo' => 'falta injustificada',
                        'fecha' => $horario->fecha,
                        'estado' => 0,
                    ]);
                    $amonestacion->update(['codigo' => ($finde || $esFeriado ? 'S' : 'AM').now()->format('dmY').$amonestacion->id]);
                }

            });
        } catch (Exception $e) {
            return back()->withInput()->withErrors(['message' => $e->getMessage()]);
        }
    }

    public function showHorarios(Permiso $permiso): JsonResponse
    {
        $jornada = $permiso->empleado->jornada_id;
        $inicioSemana = $permiso->fecha->copy()->startOfWeek(Carbon::MONDAY);
        $finSemana = $permiso->fecha->copy()->endOfWeek(Carbon::SUNDAY);
        $totalHorasTrabajadas = 0;
        $horasPorDia = [];

        $horarios = Horario::where('empleado_id', $permiso->empleado_id)
            ->whereBetween('fecha', [$inicioSemana, $finSemana])
            ->where('estado', '!=', 'PE')
            ->orderBy('fecha')
            ->get();

        $permisoLaboral = Horario::where('empleado_id', $permiso->empleado_id)
            ->whereDate('fecha', $permiso->fecha)
            ->first();

        // ?? DEFINIR ESTADOS QUE CUENTAN SEGÚN FASE
        if ($permiso->estado == 0) {
            // FASE 1 - PENDIENTE: solo cuenta 'L'
            $estadosQueCuentan = ['L'];
        } elseif ($permiso->estado == 1) {
            // FASE 2 - APROBADO: cuenta múltiples estados
            $estadosQueCuentan = ['L', 'PE', 'V', 'F', 'S', 'D', 'AHE', 'C', 'CA', 'CHE', 'FL', 'SP', 'M', 'SN', 'ST', 'SFI', 'FI', 'FJ', 'LCG', 'LSG', 'LP', 'LM', 'LF', 'TD'];
        }

        // ? CALCULAR CORRECTAMENTE
        foreach ($horarios as $horario) {
            $minutosDia = 0;

            // ?? SOLO CALCULAR SI TIENE HORARIOS VÁLIDOS
            if ($horario->ingreso && $horario->salida && $horario->ingreso != '00:00' && $horario->salida != '00:00') {
                $minutosDia = $horario->ingreso->diffInMinutes($horario->salida);

                // Restar refrigerio POR DÍA
                if ($minutosDia > 360) {
                    $minutosDia -= 60;
                }

                // ?? SUMAR AL TOTAL SOLO SI EL ESTADO CUENTA
                if (in_array($horario->estado, $estadosQueCuentan)) {
                    $totalHorasTrabajadas += $minutosDia;
                }
            }

            // ?? GUARDAR EN horas_por_dia PARA TODOS LOS ESTADOS
            $horasPorDia[$horario->fecha->format('Y-m-d')] = $minutosDia;
        }

        // Calcular tiempo extra
        $tiempoExtra = max(0, $totalHorasTrabajadas - ($jornada == 1 ? 2880 : 1410));

        return response()->json([
            'horarios' => $horarios,
            'extra' => $tiempoExtra,
            'laboral' => $totalHorasTrabajadas,
            'horarioExtra' => $permisoLaboral,
            'horas_por_dia' => $horasPorDia,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre' => 'required|min:3|string',
            'empleado_id' => 'required|exists:empleados,id',
        ]);
        try {
            DB::transaction(function () use ($data) {
                Permiso::create($data);
            });

            return redirect()->to(session('permisos_url', route('permisos.index')))->withSuccess(['message' => 'Permiso creado exitosamente!']);
        } catch (Exception $e) {
            return back()->withErrors(['message' => $e->getMessage()])->withInput();
        }
    }

    public function destroy(Request $request, Permiso $permiso)
    {
        $request->validate([
            'motivo_rechazo' => 'required|string',
        ]);

        try {
            DB::transaction(function () use ($request, $permiso) {

                $permiso->update([
                    'estado' => 2,
                    'motivo_rechazo' => $request->motivo_rechazo,
                ]);

                if ($permiso->tipo_id == 20) { // solo es para horas extra post marcacion
                    AsistenciaDetalle::where('empleado_id', $permiso->empleado_id)->whereDate('fecha', $permiso->fecha)->update(['estado_horas_extra' => 0]); // estado horas extra rechazado
                    Marcacion::where('empleado_id', $permiso->empleado_id)->whereDate('fecha', $permiso->fecha)->update(['estado_horas_extra' => 0]); // estado horas extra rechazado
                    Horario::where('empleado_id', $permiso->empleado_id)->whereDate('fecha', $permiso->fecha)->update(['extra' => null]); // horas extra rechazado

                    return response()->json('Actualizado');
                }

                $horario = Horario::where('empleado_id', $permiso->empleado_id) // buscamos el horario para actualizar su estado
                    ->whereDate('fecha', $permiso->fecha)
                    ->firstOrFail();

                $horarioAnterior = Horario::where('empleado_id', $permiso->empleado_id) // buscamos el horario para actualizar su estado
                    ->whereDate('fecha', '<', $permiso->fecha)
                    ->orderByDesc('fecha')
                    ->firstOrFail();

                $horario->update(['estado' => 'L', 'extra' => null]);

                if ($permiso->tipo_id == 2 && $permiso->empleado->jornada_id == 2) { // SOLO PARA HORARIO PROGRAMADO EXTRA PARA PARTTIME
                    // $horario->update(['estado' => 'HENA']);
                }

                $horario->feriados()->detach();

            });
        } catch (Exception $e) {
            return back()->withInput()->withErrors(['message' => $e->getMessage()]);
        }
    }

    public function upload(Request $request, Permiso $permiso)
    {
        $request->validate([
            'comprobante' => 'required|file|mimes:pdf,jpeg,png,jpg|max:2048',
        ]);

        try {
            DB::transaction(function () use ($permiso, $request) {
                if ($request->hasFile('comprobante')) { // verificamos que haya un archivo comrpobante
                    $file = $request->file('comprobante');
                    // $path = Storage::put('comprobantes', $file);
                    $path = $file->store('permisos/'.$permiso->id, 'public'); // Almacenar el archivo en la carpeta public del storage
                    $permiso->update(['comprobante' => "storage/$path"]);
                }
            });
        } catch (Exception $e) {
            return back()->withInput()->withErrors(['message' => $e->getMessage()]);
        }

    }

    public function imprimir(Request $request, Permiso $permiso)
    {
        $permiso->load(['empleado.area', 'empleado.empresa']);
        $permiso->update(['estado_print' => 1]);
        $fecha = now()->format('m-Y');

        return view('exports.pdf.permiso.faltaInjustificada', compact('permiso', 'fecha'));
    }
}
