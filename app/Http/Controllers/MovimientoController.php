<?php

namespace App\Http\Controllers;

use App\Models\Empleado;
use App\Models\Movimiento;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class MovimientoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $movimientos = Movimiento::with('empleado')->orderBy('created_at', 'desc')->get();

        $data = $movimientos->map(function ($movimiento) {
            return [
                'dni' => $movimiento->dni,
                'empleados_id' => $movimiento->empleados_id,
                'motivo' => $movimiento->motivo,
                'nombres' => $movimiento->nombres,
                'tipo_movimiento' => $movimiento->tipo_movimiento,
                'fecha_movimiento' => Carbon::parse($movimiento->fecha_movimiento),
                'ultima_fecha_cese' => Carbon::parse($movimiento->ultima_fecha_cese),
                'ultima_fecha_activacion' => Carbon::parse($movimiento->ultima_fecha_activacion),
                'fecha_cese_actual' => Carbon::parse($movimiento->fecha_cese_actual),
                'fecha_activacion_actual' => Carbon::parse($movimiento->fecha_activacion_actual),
            ];
        });

        return response()->json($data, 200);
    }

    public function indexInertia(Request $request)
    {
        $fechaInicio = $request->input('fechaInicio');
        $fechaFin = $request->input('fechaFin');
        $search = $request->input('search');

        $query = Movimiento::with('empleado')->orderBy('created_at', 'desc');

        // Filtro por rango de fechas
        if ($fechaInicio && $fechaFin) {
            $query->whereBetween('fecha_movimiento', [$fechaInicio, $fechaFin]);
        }

        // Filtro por búsqueda libre
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('motivo', 'like', "%{$search}%")
                    ->orWhere('dni', 'like', "%{$search}%")
                    ->orWhereHas('empleado', function ($e) use ($search) {
                        $e->where('nombres', 'like', "%{$search}%")
                            ->orWhere('apellidos', 'like', "%{$search}%")
                            ->orWhereRaw("CONCAT(apellidos, ' ', nombres) LIKE ?", ["%{$search}%"]);
                    });
            });
        }

        $movimientos = $query->get();

        $data = $movimientos->map(function ($movimiento) {
            return [
                'id' => $movimiento->id,
                'empleados_id' => $movimiento->empleados_id,
                'empleado' => $movimiento->empleado,
                'dni' => $movimiento->dni,
                'fecha_movimiento' => optional($movimiento->fecha_movimiento)->format('Y-m-d'),
                'motivo' => $movimiento->motivo,
                'tipo_movimiento' => $movimiento->tipo_movimiento,
                'fecha_cese_actual' => optional($movimiento->fecha_cese_actual)->format('Y-m-d'),
                'fecha_activacion_actual' => optional($movimiento->fecha_activacion_actual)->format('Y-m-d'),
            ];
        });

        return Inertia::render('movimientos/index', [
            'movimientos' => $data,
            'filters' => [
                'fechaInicio' => $fechaInicio,
                'fechaFin' => $fechaFin,
                'search' => $search,
            ],
            'csrf_token' => csrf_token(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function toggleEstadoAPI(Request $request)
    {
        try {

            $request->validate([
                'empleado_id' => 'required|exists:empleados,id',
                'motivo' => 'required|string|min:3',
                'tipo_movimiento' => 'required|in:cese,reactivacion',
                'fecha_cambio' => 'required|date',
            ]);

            $empleado = Empleado::findOrFail($request->empleado_id);

            $registro_cese = Carbon::parse($empleado->fecha_cese);
            $registro_activacion = Carbon::parse($empleado->fecha_ingreso);

            if ($request->tipo_movimiento === 'cese') {
                $empleado->fecha_cese = Carbon::parse($request->fecha_cambio);
            } elseif ($request->tipo_movimiento === 'reactivacion') {

                $empleado->fecha_ingreso = Carbon::parse($request->fecha_cambio);
                $empleado->fecha_cese = null;
            }

            $empleado->save();

            // Registrar movimiento
            $movimiento = Movimiento::create([
                'empleado' => $empleado->apellidos.' '.$empleado->nombres,
                'dni' => $empleado->dni,
                'fecha_movimiento' => now()->format('d-m-Y'),
                'motivo' => $request->motivo,
                'tipo_movimiento' => $request->tipo_movimiento,
                'empleados_id' => $empleado->id,

                'ultima_fecha_cese' => Carbon::parse($registro_cese),
                'ultima_fecha_activacion' => Carbon::parse($registro_activacion),

                'fecha_cese_actual' => Carbon::parse($empleado->fecha_cese),
                'fecha_activacion_actual' => Carbon::parse($empleado->fecha_ingreso),
            ]);

            return response()->json([
                'message' => "Empleado {$request->tipo_movimiento} exitosamente.",
                'empleado_id' => $empleado->nombres,
                'empleado_f.cese' => optional($empleado->fecha_cese)->format('d-m-Y'),
                'empleado_f.activacion' => optional($empleado->fecha_ingreso)->format('d-m-Y'),
                'fecha_movimiento' => optional($request->fecha_cambio)->format('d-m-Y'),
                'movimiento' => $movimiento,
            ], 200);
        } catch (Exception $e) {
            Log::error('Error al cambiar estado del empleado: '.$e->getMessage());

            return response()->json([
                'error' => 'Ocurrió un error al procesar la solicitud.',
                'detalle' => $e->getMessage(),
            ], 500);
        }
    }

    public function toggleEstadoUsuariosInertia(Request $request)
    {
        try {

            $request->validate([
                'usuario_id' => 'required|exists:users,id',
                'motivo' => 'required|string|min:3',
                'tipo_movimiento' => 'required|in:archivado,reactivacion',
                'fecha_cambio' => 'required|date',
            ]);

            // 1. Encontrar usuario y empleado relacionado
            $user = User::findOrFail($request->usuario_id);
            $empleado = $user->empleado;

            if (! $empleado) {
                throw new Exception('El usuario no tiene un empleado asociado');
            }

            // Validar estado actual vs acción solicitada
            if ($request->tipo_movimiento === 'archivado' && $user->estado === 0) {
                throw new Exception('No se puede archivar un usuario ya archivado');
            }
            if ($request->tipo_movimiento === 'reactivacion' && $user->estado === 1) {
                throw new Exception('No se puede reactivar un usuario ya activo');
            }

            // Guardar fechas antiguas para el registro
            $registro_cese = $empleado->fecha_cese ? Carbon::parse($empleado->fecha_cese) : null;
            $registro_activacion = $empleado->fecha_ingreso ? Carbon::parse($empleado->fecha_ingreso) : null;

            // 2. Aplicar cambios a EMPLEADO (fecha_cese)
            if ($request->tipo_movimiento === 'archivado') {
                $empleado->fecha_cese = Carbon::parse($request->fecha_cambio);
            } elseif ($request->tipo_movimiento === 'reactivacion') {
                $empleado->fecha_ingreso = Carbon::parse($request->fecha_cambio);
                $empleado->fecha_cese = null;
            }

            // 3. Aplicar cambios a USUARIO (estado)
            if ($request->tipo_movimiento === 'archivado') {
                $user->estado = 0; // Archivado/Inactivo
            } elseif ($request->tipo_movimiento === 'reactivacion') {
                $user->estado = 1; // Activado
            }

            // 4. Guardar ambos modelos
            $empleado->save();
            $user->save();

            // 5. Registrar movimiento
            Movimiento::create([
                'empleado' => $empleado->apellidos.' '.$empleado->nombres,
                'dni' => $empleado->dni,
                'fecha_movimiento' => now()->format('d-m-Y'),
                'motivo' => $request->motivo,
                'tipo_movimiento' => $request->tipo_movimiento === 'archivado' ? 'cese' : 'reactivacion',
                'empleados_id' => $empleado->id,

                'ultima_fecha_cese' => $registro_cese,
                'ultima_fecha_activacion' => $registro_activacion,

                'fecha_cese_actual' => $empleado->fecha_cese ? Carbon::parse($empleado->fecha_cese) : null,
                'fecha_activacion_actual' => $empleado->fecha_ingreso ? Carbon::parse($empleado->fecha_ingreso) : null,
            ]);

            // 6. Redirigir a usuarios
            return Redirect::route('usuarios.index')->with('success', "Usuario {$request->tipo_movimiento} exitosamente.");

        } catch (Exception $e) {

            return Redirect::back()->withErrors([
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function toggleEstadoInertia(Request $request)
    {
        try {
            $request->validate([
                'empleado_id' => 'required|exists:empleados,id',
                'motivo' => 'required|string|min:3',
                'tipo_movimiento' => 'required|in:cese,reactivacion',
                'fecha_cambio' => 'required|date',
            ]);

            $empleado = Empleado::findOrFail($request->empleado_id);

            $registro_cese = Carbon::parse($empleado->fecha_cese);
            $registro_activacion = Carbon::parse($empleado->fecha_ingreso);

            if ($request->tipo_movimiento === 'cese') {
                $empleado->fecha_cese = Carbon::parse($request->fecha_cambio);
            } elseif ($request->tipo_movimiento === 'reactivacion') {

                $empleado->fecha_ingreso = Carbon::parse($request->fecha_cambio);
                $empleado->fecha_cese = null;
            }

            $empleado->save();

            // Registrar movimiento
            Movimiento::create([
                'empleado' => $empleado->apellidos.' '.$empleado->nombres,
                'dni' => $empleado->dni,
                'fecha_movimiento' => now()->format('d-m-Y'),
                'motivo' => $request->motivo,
                'tipo_movimiento' => $request->tipo_movimiento,
                'empleados_id' => $empleado->id,

                'ultima_fecha_cese' => Carbon::parse($registro_cese),
                'ultima_fecha_activacion' => Carbon::parse($registro_activacion),

                'fecha_cese_actual' => Carbon::parse($empleado->fecha_cese),
                'fecha_activacion_actual' => Carbon::parse($empleado->fecha_ingreso),
            ]);

            // Redirigir con mensaje flash para Inertia
            return Redirect::route('empleados.index')->with('success', "Empleado {$request->tipo_movimiento} exitosamente.");
        } catch (Exception $e) {
            Log::error('Error al cambiar estado del empleado: '.$e->getMessage());

            return Redirect::back()->withErrors([
                'message' => 'Ocurrió un error al procesar la solicitud.',
            ]);
        }
    }
}
