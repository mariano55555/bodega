<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStorageLocationRequest extends FormRequest
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
        $locationId = $this->route('location')->id ?? $this->route('id');

        return [
            'code' => 'required|string|max:100|unique:storage_locations,code,'.$locationId,
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'warehouse_id' => 'required|exists:warehouses,id',
            'parent_location_id' => 'nullable|exists:storage_locations,id',
            'type' => 'required|in:shelf,pallet,bin,zone,floor',
            'capacity' => 'nullable|numeric|min:0',
            'capacity_unit' => 'nullable|in:units,m3,m2,pallets',
            'max_weight' => 'nullable|numeric|min:0',
            'weight_unit' => 'nullable|in:kg,ton,lb',
            'coordinates' => 'nullable|string|max:255',
            'sort_order' => 'nullable|integer|min:0',
            'is_pickable' => 'boolean',
            'is_receivable' => 'boolean',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'code.required' => 'El código es obligatorio.',
            'code.unique' => 'Este código ya está en uso.',
            'code.max' => 'El código no debe exceder 100 caracteres.',
            'name.required' => 'El nombre es obligatorio.',
            'name.max' => 'El nombre no debe exceder 255 caracteres.',
            'description.max' => 'La descripción no debe exceder 1000 caracteres.',
            'warehouse_id.required' => 'La bodega es obligatoria.',
            'warehouse_id.exists' => 'La bodega seleccionada no existe.',
            'parent_location_id.exists' => 'La ubicación padre seleccionada no existe.',
            'type.required' => 'El tipo de ubicación es obligatorio.',
            'type.in' => 'El tipo de ubicación debe ser: estante, pallet, contenedor, zona o piso.',
            'capacity.numeric' => 'La capacidad debe ser un número.',
            'capacity.min' => 'La capacidad no puede ser negativa.',
            'capacity_unit.in' => 'La unidad de capacidad debe ser: unidades, m³, m² o pallets.',
            'max_weight.numeric' => 'El peso máximo debe ser un número.',
            'max_weight.min' => 'El peso máximo no puede ser negativo.',
            'weight_unit.in' => 'La unidad de peso debe ser: kg, ton o lb.',
            'coordinates.max' => 'Las coordenadas no deben exceder 255 caracteres.',
            'sort_order.integer' => 'El orden de clasificación debe ser un número entero.',
            'sort_order.min' => 'El orden de clasificación no puede ser negativo.',
        ];
    }
}
