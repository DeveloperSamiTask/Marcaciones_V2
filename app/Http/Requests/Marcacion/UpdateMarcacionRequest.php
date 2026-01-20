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
            // Obligatorios para saber qué editar y por qué
            'motivo' => 'required|string|max:255',
            'tipo' => ['required', 'in:ingreso,salida,ingreso_refri,salida_refri'],

            // El ID de la bolsa de tiempo que el usuario eligió en el select
            // Lo validamos para asegurarnos que exista en la tabla marcacions
            'extraSeleccionada' => 'nullable|exists:marcacions,id',

            // Estos pueden venir del front como referencia, pero el Back manda
            'hora_original' => 'nullable',
            'hora_nueva' => 'nullable',
            'hsp' => 'nullable',
            'tiempo_extra' => 'nullable',
        ];
    }
}
