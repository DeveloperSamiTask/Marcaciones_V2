<?php

namespace App\Http\Requests\Marcacion;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMarcacionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // 1. Reglas comunes que siempre deben estar
        $rules = [
            'modo' => 'required|in:libre,compensar', // Vital para saber qué validar
            'motivo' => 'required|string|max:255',
            'tipo' => 'required|in:ingreso,salida,ingreso_refri,salida_refri',
            'hora_nueva' => 'required|date_format:H:i',
        ];

        // 2. Reglas específicas para el modo COMPENSAR (La lógica nueva)
        if ($this->modo === 'compensar') {
            $rules['extraSeleccionada'] = 'required|exists:horarios,id'; // Cambié a horarios si esa es tu tabla de extras
            $rules['tiempo_extra'] = 'required|date_format:H:i';
        }

        // 3. Reglas específicas para el modo LIBRE (La lógica de RRHH)
        if ($this->modo === 'libre') {
            // En modo libre, quizás no necesitas validar 'extraSeleccionada'
            $rules['extraSeleccionada'] = 'nullable';
        }

        // 4. Reglas informativas (opcionales)
        $rules['hora_original'] = 'nullable|date_format:H:i';
        $rules['hsp'] = 'nullable';

        return $rules;
    }

    /**
     * Mensajes personalizados para que RRHH entienda qué olvidó
     */
    public function messages()
    {
        return [
            'extraSeleccionada.required' => 'Debes seleccionar una bolsa de horas extras para compensar.',
            'hora_nueva.required' => 'La hora es obligatoria.',
            'motivo.required' => 'Debes justificar por qué estás editando esto.',
        ];
    }
}
