<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAreaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $companyId = $this->company_id ?? auth()->user()->company_id;

        return [
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'nullable',
                'string',
                'max:50',
                'alpha_dash',
                Rule::unique('areas', 'code')
                    ->where('company_id', $companyId),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'company_id.required' => 'La empresa es obligatoria.',
            'company_id.integer' => 'La empresa debe ser un número válido.',
            'company_id.exists' => 'La empresa seleccionada no existe.',

            'name.required' => 'El nombre del área es obligatorio.',
            'name.string' => 'El nombre del área debe ser una cadena de texto.',
            'name.max' => 'El nombre del área no puede tener más de :max caracteres.',

            'code.string' => 'El código debe ser una cadena de texto.',
            'code.max' => 'El código no puede tener más de :max caracteres.',
            'code.alpha_dash' => 'El código solo puede contener letras, números, guiones y guiones bajos.',
            'code.unique' => 'Este código de área ya existe para esta empresa.',

            'description.string' => 'La descripción debe ser una cadena de texto.',
            'description.max' => 'La descripción no puede tener más de :max caracteres.',

            'is_active.boolean' => 'El estado activo debe ser verdadero o falso.',
        ];
    }
}
