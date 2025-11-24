<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInventoryAdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'adjustment_type' => ['required', 'string', 'in:positive,negative,damage,expiry,loss,correction,return,other'],
            'quantity' => ['required', 'numeric', 'not_in:0'],
            'unit_cost' => ['nullable', 'numeric', 'min:0', 'max:9999999.9999'],
            'reason' => ['required', 'string', 'max:500'],
            'justification' => ['nullable', 'string', 'max:2000'],
            'corrective_actions' => ['nullable', 'string', 'max:2000'],
            'reference_document' => ['nullable', 'string', 'max:255'],
            'reference_number' => ['nullable', 'string', 'max:255'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['string', 'max:500'],
            'storage_location_id' => ['nullable', 'integer', 'exists:storage_locations,id'],
            'batch_number' => ['nullable', 'string', 'max:100'],
            'expiry_date' => ['nullable', 'date', 'after:today'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'admin_notes' => ['nullable', 'string', 'max:1000'],
            'cost_center' => ['nullable', 'string', 'max:100'],
            'project_code' => ['nullable', 'string', 'max:100'],
            'department' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'string', 'in:borrador,pendiente'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'warehouse_id.required' => 'La bodega es requerida.',
            'warehouse_id.exists' => 'La bodega seleccionada no existe.',
            'product_id.required' => 'El producto es requerido.',
            'product_id.exists' => 'El producto seleccionado no existe.',
            'adjustment_type.required' => 'El tipo de ajuste es requerido.',
            'adjustment_type.in' => 'El tipo de ajuste debe ser uno de los siguientes: positivo, negativo, daño, vencido, pérdida, corrección, devolución u otro.',
            'quantity.required' => 'La cantidad es requerida.',
            'quantity.numeric' => 'La cantidad debe ser un número.',
            'quantity.not_in' => 'La cantidad no puede ser cero.',
            'unit_cost.numeric' => 'El costo unitario debe ser un número.',
            'unit_cost.min' => 'El costo unitario debe ser mayor o igual a 0.',
            'unit_cost.max' => 'El costo unitario no puede exceder 9,999,999.9999.',
            'reason.required' => 'El motivo es requerido.',
            'reason.max' => 'El motivo no puede exceder 500 caracteres.',
            'justification.max' => 'La justificación no puede exceder 2000 caracteres.',
            'corrective_actions.max' => 'Las acciones correctivas no pueden exceder 2000 caracteres.',
            'reference_document.max' => 'El documento de referencia no puede exceder 255 caracteres.',
            'reference_number.max' => 'El número de referencia no puede exceder 255 caracteres.',
            'attachments.array' => 'Los adjuntos deben ser una lista.',
            'attachments.*.string' => 'Cada adjunto debe ser una ruta válida.',
            'attachments.*.max' => 'La ruta del adjunto no puede exceder 500 caracteres.',
            'storage_location_id.exists' => 'La ubicación de almacenamiento seleccionada no existe.',
            'batch_number.max' => 'El número de lote no puede exceder 100 caracteres.',
            'expiry_date.date' => 'La fecha de vencimiento debe ser una fecha válida.',
            'expiry_date.after' => 'La fecha de vencimiento debe ser posterior a hoy.',
            'notes.max' => 'Las notas no pueden exceder 1000 caracteres.',
            'admin_notes.max' => 'Las notas administrativas no pueden exceder 1000 caracteres.',
            'cost_center.max' => 'El centro de costo no puede exceder 100 caracteres.',
            'project_code.max' => 'El código de proyecto no puede exceder 100 caracteres.',
            'department.max' => 'El departamento no puede exceder 100 caracteres.',
            'status.in' => 'El estado debe ser borrador o pendiente.',
            'is_active.boolean' => 'El estado activo debe ser verdadero o falso.',
        ];
    }

    protected function prepareForValidation(): void
    {
        // Ensure quantity is negative for negative adjustment types
        if (in_array($this->adjustment_type, ['negative', 'damage', 'expiry', 'loss']) && $this->quantity > 0) {
            $this->merge([
                'quantity' => -abs($this->quantity),
            ]);
        }

        // Ensure quantity is positive for positive adjustment types
        if (in_array($this->adjustment_type, ['positive', 'return']) && $this->quantity < 0) {
            $this->merge([
                'quantity' => abs($this->quantity),
            ]);
        }
    }
}
