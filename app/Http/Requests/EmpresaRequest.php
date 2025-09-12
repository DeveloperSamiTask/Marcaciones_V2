<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EmpresaRequest extends FormRequest
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
        $empresaId = $this->route('empresa')?->id; // este es el id que ignora en la validacion sirve para editar.
        return [
            'ruc' => ['required', 'numeric', 'digits:11', Rule::unique('empresas', 'ruc')->ignore($empresaId)],
            'razonsocial' => 'required|string|max:255',
            'direccion' => 'required|string|max:255',
        ];
    }
}
