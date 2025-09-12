<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
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
            'name' => 'required|string|min:3|max:255', // Requerido, cadena, entre 3 y 255 caracteres
            'email' => ['required', 'email:rfc,dns', Rule::unique('users')],
            'password' => 'required|string', // Requerido, mínimo 8 caracteres, debe coincidir con "password_confirmation"
            'rol_id' => 'required|exists:roles,id',
            'empleado_id' => 'required|exists:empleados,id',
        ];
    }
}
