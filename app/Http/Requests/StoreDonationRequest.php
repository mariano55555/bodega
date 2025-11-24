<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDonationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Basic Information
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'donor_name' => ['required', 'string', 'max:255'],
            'donor_type' => ['required', 'string', 'in:individual,organization,government'],
            'donor_contact' => ['nullable', 'string', 'max:255'],
            'donor_email' => ['nullable', 'email', 'max:255'],
            'donor_phone' => ['nullable', 'string', 'max:50'],
            'donor_address' => ['nullable', 'string', 'max:500'],

            // Document Information
            'document_type' => ['required', 'string', 'in:acta,carta,convenio,otro'],
            'document_number' => ['nullable', 'string', 'max:100'],
            'document_date' => ['required', 'date'],
            'reception_date' => ['required', 'date', 'after_or_equal:document_date'],

            // Purpose and Project
            'purpose' => ['nullable', 'string', 'max:500'],
            'intended_use' => ['nullable', 'string', 'max:500'],
            'project_name' => ['nullable', 'string', 'max:255'],

            // Financial Information
            'estimated_value' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'tax_deduction_value' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],

            // Additional Information
            'notes' => ['nullable', 'string', 'max:1000'],
            'conditions' => ['nullable', 'string', 'max:1000'],
            'attachments' => ['nullable', 'array'],

            // Tax Receipt
            'tax_receipt_required' => ['nullable', 'boolean'],

            // Donation Details (Line Items)
            'details' => ['required', 'array', 'min:1'],
            'details.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'details.*.quantity' => ['required', 'numeric', 'min:0.0001', 'max:999999.9999'],
            'details.*.estimated_unit_value' => ['required', 'numeric', 'min:0', 'max:999999999.99'],
            'details.*.condition' => ['required', 'string', 'in:nuevo,usado,reacondicionado'],
            'details.*.condition_notes' => ['nullable', 'string', 'max:500'],
            'details.*.lot_number' => ['nullable', 'string', 'max:100'],
            'details.*.expiration_date' => ['nullable', 'date', 'after:today'],
            'details.*.notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            // Basic Information
            'warehouse_id.required' => 'El almacén es requerido.',
            'warehouse_id.exists' => 'El almacén seleccionado no existe.',
            'donor_name.required' => 'El nombre del donante es requerido.',
            'donor_name.max' => 'El nombre del donante no puede exceder 255 caracteres.',
            'donor_type.required' => 'El tipo de donante es requerido.',
            'donor_type.in' => 'El tipo de donante debe ser individual, organización o gobierno.',
            'donor_email.email' => 'El correo electrónico debe ser válido.',
            'donor_email.max' => 'El correo electrónico no puede exceder 255 caracteres.',
            'donor_phone.max' => 'El teléfono no puede exceder 50 caracteres.',
            'donor_address.max' => 'La dirección no puede exceder 500 caracteres.',

            // Document Information
            'document_type.required' => 'El tipo de documento es requerido.',
            'document_type.in' => 'El tipo de documento debe ser acta, carta, convenio u otro.',
            'document_number.max' => 'El número de documento no puede exceder 100 caracteres.',
            'document_date.required' => 'La fecha del documento es requerida.',
            'document_date.date' => 'La fecha del documento debe ser válida.',
            'reception_date.required' => 'La fecha de recepción es requerida.',
            'reception_date.date' => 'La fecha de recepción debe ser válida.',
            'reception_date.after_or_equal' => 'La fecha de recepción debe ser igual o posterior a la fecha del documento.',

            // Purpose and Project
            'purpose.max' => 'El propósito no puede exceder 500 caracteres.',
            'intended_use.max' => 'El uso previsto no puede exceder 500 caracteres.',
            'project_name.max' => 'El nombre del proyecto no puede exceder 255 caracteres.',

            // Financial Information
            'estimated_value.numeric' => 'El valor estimado debe ser numérico.',
            'estimated_value.min' => 'El valor estimado debe ser al menos 0.',
            'estimated_value.max' => 'El valor estimado no puede exceder 999,999,999.99.',
            'tax_deduction_value.numeric' => 'El valor de deducción fiscal debe ser numérico.',
            'tax_deduction_value.min' => 'El valor de deducción fiscal debe ser al menos 0.',
            'tax_deduction_value.max' => 'El valor de deducción fiscal no puede exceder 999,999,999.99.',

            // Additional Information
            'notes.max' => 'Las notas no pueden exceder 1000 caracteres.',
            'conditions.max' => 'Las condiciones no pueden exceder 1000 caracteres.',
            'attachments.array' => 'Los archivos adjuntos deben ser un arreglo.',

            // Tax Receipt
            'tax_receipt_required.boolean' => 'El campo de recibo fiscal debe ser verdadero o falso.',

            // Donation Details
            'details.required' => 'Debe agregar al menos un producto a la donación.',
            'details.array' => 'Los detalles de donación deben ser un arreglo.',
            'details.min' => 'Debe agregar al menos un producto a la donación.',
            'details.*.product_id.required' => 'El producto en la posición :position es requerido.',
            'details.*.product_id.exists' => 'El producto en la posición :position no existe.',
            'details.*.quantity.required' => 'La cantidad en la posición :position es requerida.',
            'details.*.quantity.numeric' => 'La cantidad en la posición :position debe ser numérica.',
            'details.*.quantity.min' => 'La cantidad en la posición :position debe ser al menos 0.0001.',
            'details.*.quantity.max' => 'La cantidad en la posición :position no puede exceder 999,999.9999.',
            'details.*.estimated_unit_value.required' => 'El valor unitario estimado en la posición :position es requerido.',
            'details.*.estimated_unit_value.numeric' => 'El valor unitario estimado en la posición :position debe ser numérico.',
            'details.*.estimated_unit_value.min' => 'El valor unitario estimado en la posición :position debe ser al menos 0.',
            'details.*.estimated_unit_value.max' => 'El valor unitario estimado en la posición :position no puede exceder 999,999,999.99.',
            'details.*.condition.required' => 'La condición del producto en la posición :position es requerida.',
            'details.*.condition.in' => 'La condición en la posición :position debe ser nuevo, usado o reacondicionado.',
            'details.*.condition_notes.max' => 'Las notas de condición en la posición :position no pueden exceder 500 caracteres.',
            'details.*.lot_number.max' => 'El número de lote en la posición :position no puede exceder 100 caracteres.',
            'details.*.expiration_date.date' => 'La fecha de vencimiento en la posición :position debe ser válida.',
            'details.*.expiration_date.after' => 'La fecha de vencimiento en la posición :position debe ser posterior a hoy.',
            'details.*.notes.max' => 'Las notas en la posición :position no pueden exceder 500 caracteres.',
        ];
    }
}
