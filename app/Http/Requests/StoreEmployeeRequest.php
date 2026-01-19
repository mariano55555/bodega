<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $companyId = $this->company_id ?? auth()->user()->company_id ?? null;

        return [
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'area_id' => ['required', 'integer', 'exists:areas,id'],
            'employee_code' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('employees', 'employee_code')->where(function ($query) use ($companyId) {
                    return $query->where('company_id', $companyId);
                }),
            ],
            'name' => ['required', 'string', 'max:255'],
            'position' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'mobile' => ['nullable', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:255'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'company_id.required' => 'La empresa es requerida.',
            'company_id.exists' => 'La empresa seleccionada no existe.',
            'area_id.required' => 'El área es requerida.',
            'area_id.exists' => 'El área seleccionada no existe.',
            'employee_code.unique' => 'Ya existe un empleado con este código en la empresa.',
            'employee_code.max' => 'El código de empleado no puede exceder 255 caracteres.',
            'name.required' => 'El nombre es requerido.',
            'name.max' => 'El nombre no puede exceder 255 caracteres.',
            'position.max' => 'El puesto no puede exceder 255 caracteres.',
            'phone.max' => 'El teléfono no puede exceder 255 caracteres.',
            'mobile.max' => 'El celular no puede exceder 50 caracteres.',
            'email.required' => 'El correo electrónico es requerido.',
            'email.email' => 'El correo electrónico debe ser válido.',
            'email.max' => 'El correo electrónico no puede exceder 255 caracteres.',
            'is_active.boolean' => 'El estado activo debe ser verdadero o falso.',
        ];
    }
}
