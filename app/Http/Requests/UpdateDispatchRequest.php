<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDispatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'dispatch_type' => ['required', 'string', 'in:venta,interno,externo,donacion'],
            'destination_unit' => ['nullable', 'string', 'max:255'],
            'recipient_name' => ['nullable', 'string', 'max:255'],
            'recipient_phone' => ['nullable', 'string', 'max:50'],
            'recipient_email' => ['nullable', 'email', 'max:255'],
            'delivery_address' => ['nullable', 'string', 'max:500'],
            'document_type' => ['nullable', 'string', 'max:100'],
            'document_number' => ['nullable', 'string', 'max:100'],
            'document_date' => ['nullable', 'date', 'before_or_equal:today'],
            'shipping_cost' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'admin_notes' => ['nullable', 'string', 'max:1000'],
            'justification' => ['nullable', 'string', 'max:1000'],
            'project_code' => ['nullable', 'string', 'max:100'],
            'cost_center' => ['nullable', 'string', 'max:100'],

            // Dispatch details
            'details' => ['required', 'array', 'min:1'],
            'details.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'details.*.product_lot_id' => ['nullable', 'integer', 'exists:product_lots,id'],
            'details.*.quantity' => ['required', 'numeric', 'min:0.0001', 'max:999999.9999'],
            'details.*.unit_of_measure_id' => ['required', 'integer', 'exists:units_of_measure,id'],
            'details.*.unit_price' => ['nullable', 'numeric', 'min:0', 'max:999999999.9999'],
            'details.*.discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'details.*.tax_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'details.*.notes' => ['nullable', 'string', 'max:500'],
            'details.*.batch_number' => ['nullable', 'string', 'max:100'],
            'details.*.expiration_date' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'warehouse_id.required' => 'La bodega es requerida.',
            'warehouse_id.exists' => 'La bodega seleccionada no existe.',
            'customer_id.exists' => 'El cliente seleccionado no existe.',
            'dispatch_type.required' => 'El tipo de despacho es requerido.',
            'dispatch_type.in' => 'El tipo de despacho debe ser venta, interno, externo o donación.',
            'destination_unit.max' => 'La unidad destino no puede exceder 255 caracteres.',
            'recipient_name.max' => 'El nombre del receptor no puede exceder 255 caracteres.',
            'recipient_phone.max' => 'El teléfono del receptor no puede exceder 50 caracteres.',
            'recipient_email.email' => 'El correo del receptor debe ser válido.',
            'recipient_email.max' => 'El correo del receptor no puede exceder 255 caracteres.',
            'delivery_address.max' => 'La dirección de entrega no puede exceder 500 caracteres.',
            'document_type.max' => 'El tipo de documento no puede exceder 100 caracteres.',
            'document_number.max' => 'El número de documento no puede exceder 100 caracteres.',
            'document_date.date' => 'La fecha del documento debe ser una fecha válida.',
            'document_date.before_or_equal' => 'La fecha del documento no puede ser futura.',
            'shipping_cost.numeric' => 'El costo de envío debe ser un número.',
            'shipping_cost.min' => 'El costo de envío no puede ser negativo.',
            'shipping_cost.max' => 'El costo de envío excede el límite permitido.',
            'notes.max' => 'Las notas no pueden exceder 1000 caracteres.',
            'admin_notes.max' => 'Las notas administrativas no pueden exceder 1000 caracteres.',
            'justification.max' => 'La justificación no puede exceder 1000 caracteres.',
            'project_code.max' => 'El código de proyecto no puede exceder 100 caracteres.',
            'cost_center.max' => 'El centro de costos no puede exceder 100 caracteres.',

            'details.required' => 'Debe agregar al menos un producto al despacho.',
            'details.min' => 'Debe agregar al menos un producto al despacho.',
            'details.*.product_id.required' => 'El producto es requerido en la línea :position.',
            'details.*.product_id.exists' => 'El producto seleccionado no existe en la línea :position.',
            'details.*.product_lot_id.exists' => 'El lote seleccionado no existe en la línea :position.',
            'details.*.quantity.required' => 'La cantidad es requerida en la línea :position.',
            'details.*.quantity.numeric' => 'La cantidad debe ser un número en la línea :position.',
            'details.*.quantity.min' => 'La cantidad debe ser mayor a cero en la línea :position.',
            'details.*.quantity.max' => 'La cantidad excede el límite permitido en la línea :position.',
            'details.*.unit_of_measure_id.required' => 'La unidad de medida es requerida en la línea :position.',
            'details.*.unit_of_measure_id.exists' => 'La unidad de medida seleccionada no existe en la línea :position.',
            'details.*.unit_price.numeric' => 'El precio unitario debe ser un número en la línea :position.',
            'details.*.unit_price.min' => 'El precio unitario no puede ser negativo en la línea :position.',
            'details.*.unit_price.max' => 'El precio unitario excede el límite permitido en la línea :position.',
            'details.*.discount_percent.numeric' => 'El porcentaje de descuento debe ser un número en la línea :position.',
            'details.*.discount_percent.min' => 'El porcentaje de descuento no puede ser negativo en la línea :position.',
            'details.*.discount_percent.max' => 'El porcentaje de descuento no puede exceder 100% en la línea :position.',
            'details.*.tax_percent.numeric' => 'El porcentaje de impuesto debe ser un número en la línea :position.',
            'details.*.tax_percent.min' => 'El porcentaje de impuesto no puede ser negativo en la línea :position.',
            'details.*.tax_percent.max' => 'El porcentaje de impuesto no puede exceder 100% en la línea :position.',
            'details.*.notes.max' => 'Las notas no pueden exceder 500 caracteres en la línea :position.',
            'details.*.batch_number.max' => 'El número de lote no puede exceder 100 caracteres en la línea :position.',
            'details.*.expiration_date.date' => 'La fecha de vencimiento debe ser una fecha válida en la línea :position.',
        ];
    }
}
