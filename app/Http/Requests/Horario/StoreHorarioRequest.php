<?php

namespace App\Http\Requests\Horario;

use Illuminate\Foundation\Http\FormRequest;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use App\Models\Empleado;

class StoreHorarioRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'empleado_id' => ['required', 'exists:empleados,id'],
            'fechaInicio' => ['required', 'date'],
            'fechaFin' => ['required', 'date', 'after_or_equal:fecha_ingreso'],
            'ingreso' => ['required'],
            'salida' => ['required'],
            'descripcion' => ['nullable', 'string', 'max:255'],
            'estado' => ['required', 'in:L,V,SP'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $fechaInicio = Carbon::parse($this->fechaInicio);
            $empleado = Empleado::find($this->empleado_id);

            if (! $empleado) {
                return;
            }

            // Usar fecha_ingreso del empleado
            $fechaIngresoEmpleado = Carbon::parse($empleado->fecha_ingreso);

            // Verificar que la fechaInicio no sea anterior al ingreso del empleado
            if ($fechaInicio->lt($fechaIngresoEmpleado)) {
                $fechaFormateada = $fechaIngresoEmpleado->format('d/m/Y');
                $validator->errors()->add(
                    'fechaInicio',
                    "No se pueden crear horarios para fechas anteriores al ingreso del empleado ($fechaFormateada)"
                );
            }
        });
    }
}
