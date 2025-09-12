<?php

namespace App\Http\Controllers;

use App\Models\AsistenciaDetalle;
use App\Models\Empleado;
use App\Models\Empresa;
use App\Models\Feriado;
use App\Models\Horario;
use App\Models\Marcacion;
use App\Models\Permiso;
use App\Models\Suspension;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
            ->when($request->user()->rol_id == 4, function($q) use ($request){
                $q->where('jefe_id', $request->user()->empleado_id);
            });
        })
        ->whereNotIn('tipo_id', [2, 20])
        ->with(['empleado.area', 'tipo'])
        ->whereBetween('fecha', [$request->fechaInicio, $request->fechaFin])
        ->orderBy('fecha')
        ->get()
        ->groupBy(function($item) {
            $estados = [
                0 => 'pendientes',
                1 => 'aprobados',
                2 => 'rechazados'
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

    public function extras(Request $request): Response
    {
        $filters = $request->validate([
            'empresa' => 'nullable|integer|exists:empresas,id',
            'fechaInicio' => 'nullable|date',
            'fechaFin' => 'nullable|date|after_or_equal:fechaInicio',
        ]);

        $empresas = Empresa::where('estado', 1)->get(['id', 'razonsocial']);
        $permisos = Permiso::whereHas('empleado', function ($query) use ($request) {
            $query->where('empresa_id', $request->empresa)->whereNull('fecha_cese')
            ->when($request->user()->rol_id == 4, function($q) use ($request){
                $q->where('jefe_id', $request->user()->empleado_id);
            });
        })
        ->whereIn('tipo_id', [2, 20])
        ->with(['empleado.area', 'tipo', 'empleado.horarios' => function ($query) use ($request) {
            $query->whereBetween('fecha', [$request->fechaInicio, $request->fechaFin]);
        }])
        ->whereBetween('fecha', [$request->fechaInicio, $request->fechaFin])
        ->orderBy('fecha')
        ->get()
        ->groupBy(function($item) {
            $estados = [
                0 => 'pendientes',
                1 => 'aprobados',
                2 => 'rechazados'
            ];

            return $estados[$item->estado] ?? '';
        });


        session(['permisos_extras_url' => $request->fullUrl()]);

        return Inertia::render('permisos/extras', [
            'pendientes' => $permisos->get('pendientes', collect()),
            'aprobados' => $permisos->get('aprobados', collect()),
            'rechazados' => $permisos->get('rechazados', collect()),
            'empresas' => $empresas,
            'filters' => $filters,
        ]);
    }

    public function showHorarios(Permiso $permiso): JsonResponse
    {
        $jornada = $permiso->empleado->jornada_id; // 1: jornada completa, 2: part time
        $inicioSemana = $permiso->fecha->copy()->startOfWeek(Carbon::MONDAY); // lunes
        $finSemana = $permiso->fecha->copy()->endOfWeek(Carbon::SUNDAY); // domingo
        $totalHorasTrabajadas = 0;

        // lista de horarios de la semana del permiso
        $horarios = Horario::where('empleado_id', $permiso->empleado_id)
        ->whereBetween('fecha', [$inicioSemana, $finSemana])
        ->where('estado', '!=', 'PE')
        ->orderBy('fecha')
        ->get();

        // horario que se desea aprobar
        $permisoLaboral = Horario::where('empleado_id', $permiso->empleado_id)
        ->whereDate('fecha', $permiso->fecha)
        ->first();

        foreach ($horarios as $horario) { // preguntar despues de cuantas horas se resta 60 min de refrigerio
            if($horario->estado == 'L'){
                $totalHorasTrabajadas += $horario->ingreso->diffInMinutes($horario->salida);
                if($totalHorasTrabajadas >= 360) { // si el horario programado es 6 horas a mas se resta 60 min de refirgerio
                    $totalHorasTrabajadas -= 60;
                }
            }
        }

        $tiempoLaboral = $permisoLaboral->ingreso->diffInMinutes($permisoLaboral->salida);
        if($tiempoLaboral >= 360) {// si el horario programado es 6 horas a mas se resta 60 min de refirgerio
            $tiempoLaboral -= 60;
        }
        $tiempoExtra = max(0, $totalHorasTrabajadas + $tiempoLaboral - ($jornada == 1 ? 2880 : 1410));
        return response()->json(['horarios' => $horarios, 'extra' => $tiempoExtra, 'laboral' => $totalHorasTrabajadas, 'horarioExtra' => $permisoLaboral]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre' => 'required|min:3|string',
            'empleado_id' => 'required|exists:empleados,id',
        ]);
        try{
            DB::transaction(function () use ($data) {
                Permiso::create($data);
            });
            return redirect()->to(session('permisos_url', route('permisos.index')))->withSuccess(['message' => 'Permiso creado exitosamente!']);
        } catch (Exception $e) {
            return back()->withErrors([ 'message' => $e->getMessage()])->withInput();
        }
    }

    public function update(Request $request, Permiso $permiso)
    {
        if (!$permiso->comprobante && ($permiso->tipo_id == 7 || $permiso->tipo_id == 8 || $permiso->tipo_id == 10 || $permiso->tipo_id == 21 || $permiso->tipo_id == 22)) {
            return back()->withInput()->withErrors([ 'message' => 'Debes subir un comprobante']);
        }

        try {
            DB::transaction(function () use ($permiso) {
                $permiso->update(['estado' => 1]);

                $horario = Horario::where('empleado_id', $permiso->empleado_id) // buscamos el horario para actualizar su estado
                ->whereDate('fecha', $permiso->fecha)
                ->firstOrFail();

                if($permiso->tipo_id == 20){ // SOLO SIRVE PARA APROBAR HORAS EXTRA POST MARCACION
                    AsistenciaDetalle::where('empleado_id', $permiso->empleado_id)->whereDate('fecha', $permiso->fecha)->update(['estado_horas_extra' => 1]); // horas extra aprobado
                    Marcacion::where('empleado_id', $permiso->empleado_id)->whereDate('fecha', $permiso->fecha)->update(['estado_horas_extra' => 1]); // horas extra enviado aprobado
                    // $horario->update(['estado' => $permiso->tipo->codigo]);
                    return response()->json('Actualizado');
                }

                if($permiso->tipo_id == 2){ // SOLO PARA HORARIO PROGRAMADO EXTRA PARA PARTTIME
                    $horario->update(['estado' => 'L']);
                    return response()->json('Actualizado');
                }

                $horario->update(['estado' => $permiso->tipo->codigo]);

                if ($horario->estado == 'FI') { // se crea una suspension cuando es falta injustificada y cae un fin de semana
                    $finde =  $horario->fecha->isWeekend();
                    $esFeriado = Feriado::whereDate('fecha', $horario->fecha)->exists();

                    $amonestacion = Suspension::create([
                        'empleado_id' => $permiso->empleado_id,
                        'tipo' => 'falta injustificada',
                        'fecha' => $horario->fecha,
                        'estado' => 0,
                    ]);
                    $amonestacion->update(['codigo' => ($finde || $esFeriado ? 'S' : 'AM') . now()->format('dmY') . $amonestacion->id]);
                }

            });
        } catch (Exception $e) {
            return back()->withInput()->withErrors([ 'message' => $e->getMessage()]);
        }
    }

    public function destroy(Request $request, Permiso $permiso)
    {
        $request->validate([
            'motivo_rechazo' => 'required|string'
        ]);

        try {
            DB::transaction(function () use ($request, $permiso) {

                $permiso->update([
                    'estado' => 2,
                    'motivo_rechazo' => $request->motivo_rechazo
                ]);

                if($permiso->tipo_id == 20){ // solo es para horas extra post marcacion
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

                if($permiso->tipo_id == 2 && $permiso->empleado->jornada_id == 2){ // SOLO PARA HORARIO PROGRAMADO EXTRA PARA PARTTIME
                    //$horario->update(['estado' => 'HENA']);
                }

                $horario->feriados()->detach();

            });
        } catch (Exception $e) {
            return back()->withInput()->withErrors([ 'message' => $e->getMessage()]);
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
            return back()->withInput()->withErrors([ 'message' => $e->getMessage()]);
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
