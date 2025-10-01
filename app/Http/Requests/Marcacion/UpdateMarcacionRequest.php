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

            'hora_original' => 'nullable|date_format:H:i',
            'motivo' => 'required|string|max:255',
            'tipo' => ['nullable', 'in:ingreso,salida,ingreso_refri,salida_refri'],

            'hora_nueva' => 'nullable|date_format:H:i',
            'tiempo_extra' => 'nullable|date_format:H:i',
            'hsp' => 'nullable|date_format:H:i',
        ];
    }
}
