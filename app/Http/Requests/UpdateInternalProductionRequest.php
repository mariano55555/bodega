<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInternalProductionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'area_id' => ['required', 'integer', 'exists:areas,id'],
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'employee_id' => ['nullable', 'integer', 'exists:employees,id'],
            'physical_document_number' => ['required', 'string', 'max:100'],
            'document_date' => ['nullable', 'date', 'before_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'admin_notes' => ['nullable', 'string', 'max:1000'],
            'status' => ['nullable', 'string', 'in:borrador,pendiente'],

            // Production details
            'details' => ['required', 'array', 'min:1'],
            'details.*.id' => ['nullable', 'integer', 'exists:internal_production_details,id'],
            'details.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'details.*.description' => ['nullable', 'string', 'max:500'],
            'details.*.unit_of_measure_id' => ['required', 'integer', 'exists:units_of_measure,id'],
            'details.*.quantity' => ['required', 'numeric', 'min:0.00001', 'max:999999.99999'],
            'details.*.unit_price' => ['required', 'numeric', 'min:0', 'max:999999999.99999'],
            'details.*.notes' => ['nullable', 'string', 'max:500'],
            'details.*.batch_number' => ['nullable', 'string', 'max:100'],
            'details.*.expiration_date' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'area_id.required' => 'La unidad de origen es obligatoria.',
            'area_id.integer' => 'La unidad de origen debe ser un número válido.',
            'area_id.exists' => 'La unidad de origen seleccionada no existe.',
            'warehouse_id.required' => 'La bodega destino es obligatoria.',
            'warehouse_id.integer' => 'La bodega destino debe ser un número válido.',
            'warehouse_id.exists' => 'La bodega destino seleccionada no existe.',
            'employee_id.integer' => 'La persona que entrega debe ser un número válido.',
            'employee_id.exists' => 'La persona que entrega seleccionada no existe.',
            'physical_document_number.required' => 'El número de documento físico es obligatorio.',
            'physical_document_number.string' => 'El número de documento físico debe ser texto.',
            'physical_document_number.max' => 'El número de documento físico no puede exceder 100 caracteres.',
            'document_date.date' => 'La fecha del documento debe ser una fecha válida.',
            'document_date.before_or_equal' => 'La fecha del documento no puede ser futura.',
            'notes.max' => 'Las notas no pueden exceder 1000 caracteres.',
            'admin_notes.max' => 'Las notas administrativas no pueden exceder 1000 caracteres.',
            'status.in' => 'El estado debe ser borrador o pendiente.',

            'details.required' => 'Debe agregar al menos un producto a la producción.',
            'details.min' => 'Debe agregar al menos un producto a la producción.',
            'details.*.product_id.required' => 'El producto es requerido en la línea :position.',
            'details.*.product_id.exists' => 'El producto seleccionado no existe en la línea :position.',
            'details.*.description.max' => 'La descripción no puede exceder 500 caracteres en la línea :position.',
            'details.*.unit_of_measure_id.required' => 'La unidad de medida es requerida en la línea :position.',
            'details.*.unit_of_measure_id.exists' => 'La unidad de medida seleccionada no existe en la línea :position.',
            'details.*.quantity.required' => 'La cantidad es requerida en la línea :position.',
            'details.*.quantity.numeric' => 'La cantidad debe ser un número en la línea :position.',
            'details.*.quantity.min' => 'La cantidad debe ser mayor a cero en la línea :position.',
            'details.*.quantity.max' => 'La cantidad excede el límite permitido en la línea :position.',
            'details.*.unit_price.required' => 'El precio unitario es requerido en la línea :position.',
            'details.*.unit_price.numeric' => 'El precio unitario debe ser un número en la línea :position.',
            'details.*.unit_price.min' => 'El precio unitario no puede ser negativo en la línea :position.',
            'details.*.unit_price.max' => 'El precio unitario excede el límite permitido en la línea :position.',
            'details.*.notes.max' => 'Las notas no pueden exceder 500 caracteres en la línea :position.',
            'details.*.batch_number.max' => 'El número de lote no puede exceder 100 caracteres en la línea :position.',
            'details.*.expiration_date.date' => 'La fecha de vencimiento debe ser una fecha válida en la línea :position.',
        ];
    }
}
