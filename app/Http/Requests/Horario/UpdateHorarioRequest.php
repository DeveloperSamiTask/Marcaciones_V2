<?php

namespace App\Http\Requests\Horario;

use App\Models\Horario;
use Illuminate\Foundation\Http\FormRequest;

class UpdateHorarioRequest extends FormRequest
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
            'ingreso' => ['required'],
            'salida' => ['required'],
            'descripcion' => ['nullable', 'string', 'max:255'],
            'estado' => ['nullable', 'in:L,D,C,AHE,HE,CA,CHE,F,FL,SP,V,M,SN,ST,SFI,HE,,FI,FJ,LCG,LSG,LP,LM,LF,TD'],
            'extras' => 'nullable',
            'feriado' => ['nullable'],
            // 'comprobante' => 'nullable|file|mimes:pdf,jpeg,png,jpg|max:2048',
        ];
    }

    /*
      public function withValidator($validator)
    {
        // 🔥 SOLUCIÓN SIMPLE: No intentar obtener el horario actual
        // Solo validar feriado cuando se ENVÍA un estado C/CA/TD
        $validator->sometimes('feriado', ['required'], function ($input) {
            return ! empty($input->estado) && in_array($input->estado, ['C', 'CA', 'TD']);
        });
    }
    */

}
