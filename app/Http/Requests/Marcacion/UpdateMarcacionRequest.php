<?php

namespace App\Http\Requests\Marcacion;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMarcacionRequest extends FormRequest
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
            'hora' => 'nullable',

            'extraSeleccionada' => 'nullable|integer|min:1',
            'tiempoDescontar' => 'nullable|integer|in:30,40,60',

            'motivo' => 'required|string|max:255',
            'tipo' => ['nullable', 'in:ingreso,salida,ingreso_refri,salida_refri'],
        ];
    }
}
