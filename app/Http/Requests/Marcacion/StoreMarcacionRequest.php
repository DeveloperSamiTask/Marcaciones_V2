<?php

namespace App\Http\Requests\Marcacion;

use Illuminate\Foundation\Http\FormRequest;

class StoreMarcacionRequest extends FormRequest
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
            'empleado_id' => 'required|exists:empleados,id',
            'hora' => 'required|string|max:5|min:5',
            'fecha' => 'required|date',
            'tipo' =>  'required|in:ingreso,salida,ingreso_refri,salida_refri',
            'motivo'     => 'required|string|max:255',
        ];
    }
}
