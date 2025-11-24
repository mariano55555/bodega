<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUnitOfMeasureRequest extends FormRequest
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
            'symbol' => ['required', 'string', 'max:20'],
            'description' => ['nullable', 'string', 'max:1000'],
            'type' => ['required', 'string', 'in:weight,volume,length,area,quantity,time,temperature'],
            'base_unit_ratio' => ['nullable', 'numeric', 'min:0'],
            'base_unit_id' => ['nullable', 'integer', 'exists:units_of_measure,id'],
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
            'is_active' => ['nullable', 'boolean'],
            'is_default' => ['nullable', 'boolean'],
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
            'name.required' => 'El nombre de la unidad de medida es obligatorio.',
            'name.string' => 'El nombre debe ser una cadena de texto.',
            'name.max' => 'El nombre no puede tener más de :max caracteres.',

            'symbol.required' => 'El símbolo es obligatorio.',
            'symbol.string' => 'El símbolo debe ser una cadena de texto.',
            'symbol.max' => 'El símbolo no puede tener más de :max caracteres.',

            'description.string' => 'La descripción debe ser una cadena de texto.',
            'description.max' => 'La descripción no puede tener más de :max caracteres.',

            'type.required' => 'El tipo de unidad es obligatorio.',
            'type.string' => 'El tipo debe ser una cadena de texto.',
            'type.in' => 'El tipo seleccionado no es válido.',

            'base_unit_ratio.numeric' => 'La tasa de conversión debe ser un número.',
            'base_unit_ratio.min' => 'La tasa de conversión debe ser mayor o igual a :min.',

            'base_unit_id.integer' => 'La unidad base debe ser un número válido.',
            'base_unit_id.exists' => 'La unidad base seleccionada no existe.',

            'company_id.integer' => 'La empresa debe ser un número válido.',
            'company_id.exists' => 'La empresa seleccionada no existe.',

            'is_active.boolean' => 'El estado activo debe ser verdadero o falso.',
            'is_default.boolean' => 'El estado por defecto debe ser verdadero o falso.',
        ];
    }
}
