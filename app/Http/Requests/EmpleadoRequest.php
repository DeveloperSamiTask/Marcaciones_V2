<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EmpleadoRequest extends FormRequest
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
            'jornada_id' => 'required|exists:jornadas,id',
            'jefe_id' => 'nullable|exists:empleados,id',
            'empresa_id' => 'required|exists:empresas,id',
            'area_id' => 'required|exists:areas,id',
            'nombres' => 'required|string|max:255',
            'apellidos' => 'required|string|max:255',
            'dni' => ['required', 'string', 'min:7', 'max:8'],
            'sexo' => 'required|in:M,F',
            'fecha_nacimiento' => 'required|date|before:today',
            'fecha_ingreso' => 'nullable|date|after:fecha_nacimiento',
            'email' => 'nullable|string|max:255',
            'domicilio' => 'nullable|string|max:255',
            'peso' => 'nullable|string|max:255',
            'talla' => 'nullable|string|max:255',
            'cargo' => 'nullable|string|max:255',
            'horas' => 'required|numeric|min:1',
        ];
    }
}
