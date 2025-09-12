<?php

namespace App\Http\Requests\Horario;

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
            'estado' => ['required', 'in:L,D,C,AHE,HE,CA,CHE,F,FL,SP,V,M,SN,ST,SFI,HE,,FI,FJ,LCG,LSG,LP,LM,LF'],
            'extras' => 'nullable',
            // 'comprobante' => 'nullable|file|mimes:pdf,jpeg,png,jpg|max:2048',
        ];
    }

    public function withValidator($validator)
    {
        $validator->sometimes('feriado', ['required'], function ($input) { // se aplica en automatico cuando se envia el estado con valor C o CA
            return $input->estado === 'C' || $input->estado === 'CA';
        });
        // $validator->sometimes('comprobante', ['required', 'file', 'mimes:pdf,jpeg,png,jpg', 'max:2048'], function ($input) {
        //     return $input->estado !== 'L';
        // });
    }
}
