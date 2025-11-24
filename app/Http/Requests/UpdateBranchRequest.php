<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBranchRequest extends FormRequest
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
        $branchId = $this->route('branch')?->id ?? $this->branch;

        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'nullable',
                'string',
                'max:10',
                'alpha_dash',
                Rule::unique('branches', 'code')
                    ->where(function ($query) {
                        return $query->where('company_id', $this->company_id);
                    })
                    ->ignore($branchId),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'manager_name' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'country' => ['nullable', 'string', 'max:100'],
            'type' => ['required', 'string', 'in:main,branch,warehouse,distribution,retail,office'],
            'settings' => ['nullable', 'array'],
            'is_active' => ['nullable', 'boolean'],
            'is_main_branch' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Get custom error messages for validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'El nombre de la sucursal es obligatorio.',
            'name.string' => 'El nombre de la sucursal debe ser una cadena de texto.',
            'name.max' => 'El nombre de la sucursal no puede tener más de :max caracteres.',

            'code.string' => 'El código de la sucursal debe ser una cadena de texto.',
            'code.max' => 'El código de la sucursal no puede tener más de :max caracteres.',
            'code.alpha_dash' => 'El código de la sucursal solo puede contener letras, números, guiones y guiones bajos.',
            'code.unique' => 'Este código de sucursal ya existe en la empresa.',

            'description.string' => 'La descripción debe ser una cadena de texto.',
            'description.max' => 'La descripción no puede tener más de :max caracteres.',

            'company_id.required' => 'La empresa es obligatoria.',
            'company_id.integer' => 'La empresa debe ser un número válido.',
            'company_id.exists' => 'La empresa seleccionada no existe.',

            'email.email' => 'El correo electrónico debe tener un formato válido.',
            'email.max' => 'El correo electrónico no puede tener más de :max caracteres.',

            'phone.string' => 'El teléfono debe ser una cadena de texto.',
            'phone.max' => 'El teléfono no puede tener más de :max caracteres.',

            'manager_name.string' => 'El nombre del gerente debe ser una cadena de texto.',
            'manager_name.max' => 'El nombre del gerente no puede tener más de :max caracteres.',

            'address.string' => 'La dirección debe ser una cadena de texto.',
            'address.max' => 'La dirección no puede tener más de :max caracteres.',

            'city.string' => 'La ciudad debe ser una cadena de texto.',
            'city.max' => 'La ciudad no puede tener más de :max caracteres.',

            'state.string' => 'El estado/provincia debe ser una cadena de texto.',
            'state.max' => 'El estado/provincia no puede tener más de :max caracteres.',

            'postal_code.string' => 'El código postal debe ser una cadena de texto.',
            'postal_code.max' => 'El código postal no puede tener más de :max caracteres.',

            'country.string' => 'El país debe ser una cadena de texto.',
            'country.max' => 'El país no puede tener más de :max caracteres.',

            'type.required' => 'El tipo de sucursal es obligatorio.',
            'type.string' => 'El tipo de sucursal debe ser una cadena de texto.',
            'type.in' => 'El tipo de sucursal seleccionado no es válido.',

            'settings.array' => 'Las configuraciones deben ser un arreglo.',

            'is_active.boolean' => 'El estado activo debe ser verdadero o falso.',
            'is_main_branch.boolean' => 'La sucursal principal debe ser verdadero o falso.',
        ];
    }
}
