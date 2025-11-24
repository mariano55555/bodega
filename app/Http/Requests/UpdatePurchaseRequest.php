<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePurchaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'supplier_id' => ['required', 'integer', 'exists:suppliers,id'],
            'document_type' => ['required', 'string', 'in:factura,ccf,ticket,otro'],
            'document_number' => ['nullable', 'string', 'max:100'],
            'document_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:document_date'],
            'purchase_type' => ['required', 'string', 'in:efectivo,credito'],
            'payment_method' => ['nullable', 'string', 'max:100'],
            'acquisition_type' => ['required', 'string', 'in:normal,convenio,proyecto,otro'],
            'project_name' => ['nullable', 'required_if:acquisition_type,proyecto', 'string', 'max:255'],
            'agreement_number' => ['nullable', 'required_if:acquisition_type,convenio', 'string', 'max:255'],
            'fund_source' => ['nullable', 'string', 'max:255'],
            'shipping_cost' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'admin_notes' => ['nullable', 'string', 'max:1000'],

            // Purchase details
            'details' => ['required', 'array', 'min:1'],
            'details.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'details.*.quantity' => ['required', 'numeric', 'min:0.0001', 'max:999999.9999'],
            'details.*.unit_cost' => ['required', 'numeric', 'min:0', 'max:999999999.99'],
            'details.*.discount_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'details.*.tax_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'details.*.lot_number' => ['nullable', 'string', 'max:100'],
            'details.*.expiration_date' => ['nullable', 'date', 'after:today'],
            'details.*.notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'warehouse_id.required' => 'La bodega es requerida.',
            'warehouse_id.exists' => 'La bodega seleccionada no existe.',
            'supplier_id.required' => 'El proveedor es requerido.',
            'supplier_id.exists' => 'El proveedor seleccionado no existe.',
            'document_type.required' => 'El tipo de documento es requerido.',
            'document_type.in' => 'El tipo de documento debe ser factura, CCF, ticket u otro.',
            'document_number.max' => 'El número de documento no puede exceder 100 caracteres.',
            'document_date.required' => 'La fecha del documento es requerida.',
            'document_date.date' => 'La fecha del documento debe ser una fecha válida.',
            'due_date.date' => 'La fecha de vencimiento debe ser una fecha válida.',
            'due_date.after_or_equal' => 'La fecha de vencimiento debe ser igual o posterior a la fecha del documento.',
            'purchase_type.required' => 'El tipo de compra es requerido.',
            'purchase_type.in' => 'El tipo de compra debe ser efectivo o crédito.',
            'payment_method.max' => 'El método de pago no puede exceder 100 caracteres.',
            'acquisition_type.required' => 'El tipo de adquisición es requerido.',
            'acquisition_type.in' => 'El tipo de adquisición debe ser normal, convenio, proyecto u otro.',
            'project_name.required_if' => 'El nombre del proyecto es requerido cuando el tipo de adquisición es proyecto.',
            'project_name.max' => 'El nombre del proyecto no puede exceder 255 caracteres.',
            'agreement_number.required_if' => 'El número de convenio es requerido cuando el tipo de adquisición es convenio.',
            'agreement_number.max' => 'El número de convenio no puede exceder 255 caracteres.',
            'fund_source.max' => 'El origen de fondos no puede exceder 255 caracteres.',
            'shipping_cost.numeric' => 'El costo de envío debe ser un número.',
            'shipping_cost.min' => 'El costo de envío no puede ser negativo.',
            'shipping_cost.max' => 'El costo de envío excede el límite permitido.',
            'notes.max' => 'Las notas no pueden exceder 1000 caracteres.',
            'admin_notes.max' => 'Las notas administrativas no pueden exceder 1000 caracteres.',

            'details.required' => 'Debe agregar al menos un producto a la compra.',
            'details.min' => 'Debe agregar al menos un producto a la compra.',
            'details.*.product_id.required' => 'El producto es requerido en la línea :position.',
            'details.*.product_id.exists' => 'El producto seleccionado no existe en la línea :position.',
            'details.*.quantity.required' => 'La cantidad es requerida en la línea :position.',
            'details.*.quantity.numeric' => 'La cantidad debe ser un número en la línea :position.',
            'details.*.quantity.min' => 'La cantidad debe ser mayor a cero en la línea :position.',
            'details.*.quantity.max' => 'La cantidad excede el límite permitido en la línea :position.',
            'details.*.unit_cost.required' => 'El costo unitario es requerido en la línea :position.',
            'details.*.unit_cost.numeric' => 'El costo unitario debe ser un número en la línea :position.',
            'details.*.unit_cost.min' => 'El costo unitario no puede ser negativo en la línea :position.',
            'details.*.unit_cost.max' => 'El costo unitario excede el límite permitido en la línea :position.',
            'details.*.discount_percentage.numeric' => 'El porcentaje de descuento debe ser un número en la línea :position.',
            'details.*.discount_percentage.min' => 'El porcentaje de descuento no puede ser negativo en la línea :position.',
            'details.*.discount_percentage.max' => 'El porcentaje de descuento no puede exceder 100% en la línea :position.',
            'details.*.tax_percentage.numeric' => 'El porcentaje de impuesto debe ser un número en la línea :position.',
            'details.*.tax_percentage.min' => 'El porcentaje de impuesto no puede ser negativo en la línea :position.',
            'details.*.tax_percentage.max' => 'El porcentaje de impuesto no puede exceder 100% en la línea :position.',
            'details.*.lot_number.max' => 'El número de lote no puede exceder 100 caracteres en la línea :position.',
            'details.*.expiration_date.date' => 'La fecha de vencimiento debe ser una fecha válida en la línea :position.',
            'details.*.expiration_date.after' => 'La fecha de vencimiento debe ser posterior al día de hoy en la línea :position.',
            'details.*.notes.max' => 'Las notas no pueden exceder 500 caracteres en la línea :position.',
        ];
    }
}
