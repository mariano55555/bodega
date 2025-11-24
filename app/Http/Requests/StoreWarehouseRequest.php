<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWarehouseRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'nullable',
                'string',
                'max:10',
                'alpha_dash',
                Rule::unique('warehouses', 'code')->where(function ($query) {
                    return $query->where('company_id', $this->company_id);
                }),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'address' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'total_capacity' => ['nullable', 'numeric', 'min:0'],
            'capacity_unit' => ['nullable', 'string', 'max:50'],
            'manager_id' => ['nullable', 'integer', 'exists:users,id'],
            'is_active' => ['nullable', 'boolean'],
            'operating_hours' => ['nullable', 'array'],
            'operating_hours.*.day' => ['required_with:operating_hours.*', 'string', 'in:monday,tuesday,wednesday,thursday,friday,saturday,sunday'],
            'operating_hours.*.open' => ['nullable', 'date_format:H:i'],
            'operating_hours.*.close' => ['nullable', 'date_format:H:i'],
            'operating_hours.*.closed' => ['nullable', 'boolean'],
            'settings' => ['nullable', 'array'],
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
            'name.required' => 'El nombre del almacén es obligatorio.',
            'name.string' => 'El nombre del almacén debe ser una cadena de texto.',
            'name.max' => 'El nombre del almacén no puede tener más de :max caracteres.',

            'code.string' => 'El código del almacén debe ser una cadena de texto.',
            'code.max' => 'El código del almacén no puede tener más de :max caracteres.',
            'code.alpha_dash' => 'El código del almacén solo puede contener letras, números, guiones y guiones bajos.',
            'code.unique' => 'Este código de almacén ya existe en la empresa.',

            'description.string' => 'La descripción debe ser una cadena de texto.',
            'description.max' => 'La descripción no puede tener más de :max caracteres.',

            'company_id.required' => 'La empresa es obligatoria.',
            'company_id.integer' => 'La empresa debe ser un número válido.',
            'company_id.exists' => 'La empresa seleccionada no existe.',

            'branch_id.integer' => 'La sucursal debe ser un número válido.',
            'branch_id.exists' => 'La sucursal seleccionada no existe.',

            'address.string' => 'La dirección debe ser una cadena de texto.',
            'address.max' => 'La dirección no puede tener más de :max caracteres.',

            'city.string' => 'La ciudad debe ser una cadena de texto.',
            'city.max' => 'La ciudad no puede tener más de :max caracteres.',

            'state.string' => 'El estado/provincia debe ser una cadena de texto.',
            'state.max' => 'El estado/provincia no puede tener más de :max caracteres.',

            'country.string' => 'El país debe ser una cadena de texto.',
            'country.max' => 'El país no puede tener más de :max caracteres.',

            'postal_code.string' => 'El código postal debe ser una cadena de texto.',
            'postal_code.max' => 'El código postal no puede tener más de :max caracteres.',

            'latitude.numeric' => 'La latitud debe ser un número.',
            'latitude.between' => 'La latitud debe estar entre -90 y 90 grados.',

            'longitude.numeric' => 'La longitud debe ser un número.',
            'longitude.between' => 'La longitud debe estar entre -180 y 180 grados.',

            'total_capacity.numeric' => 'La capacidad total debe ser un número.',
            'total_capacity.min' => 'La capacidad total debe ser mayor o igual a :min.',

            'capacity_unit.string' => 'La unidad de capacidad debe ser una cadena de texto.',
            'capacity_unit.max' => 'La unidad de capacidad no puede tener más de :max caracteres.',

            'manager_id.integer' => 'El gerente debe ser un número válido.',
            'manager_id.exists' => 'El gerente seleccionado no existe.',

            'is_active.boolean' => 'El estado activo debe ser verdadero o falso.',

            'operating_hours.array' => 'Los horarios de operación deben ser un arreglo.',
            'operating_hours.*.day.required_with' => 'El día es obligatorio para cada horario.',
            'operating_hours.*.day.string' => 'El día debe ser una cadena de texto.',
            'operating_hours.*.day.in' => 'El día debe ser uno de los días válidos de la semana.',
            'operating_hours.*.open.date_format' => 'La hora de apertura debe tener el formato HH:MM.',
            'operating_hours.*.close.date_format' => 'La hora de cierre debe tener el formato HH:MM.',
            'operating_hours.*.closed.boolean' => 'El estado cerrado debe ser verdadero o falso.',

            'settings.array' => 'Las configuraciones deben ser un arreglo.',
        ];
    }
}
