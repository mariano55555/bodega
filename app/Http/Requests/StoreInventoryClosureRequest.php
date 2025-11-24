<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInventoryClosureRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
            'closure_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'observations' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'warehouse_id.required' => 'El almacén es requerido.',
            'warehouse_id.exists' => 'El almacén seleccionado no existe.',
            'year.required' => 'El año es requerido.',
            'year.integer' => 'El año debe ser un número entero.',
            'year.min' => 'El año debe ser mayor o igual a 2000.',
            'year.max' => 'El año debe ser menor o igual a 2100.',
            'month.required' => 'El mes es requerido.',
            'month.integer' => 'El mes debe ser un número entero.',
            'month.min' => 'El mes debe estar entre 1 y 12.',
            'month.max' => 'El mes debe estar entre 1 y 12.',
            'closure_date.required' => 'La fecha de cierre es requerida.',
            'closure_date.date' => 'La fecha de cierre debe ser válida.',
            'notes.max' => 'Las notas no pueden exceder 1000 caracteres.',
            'observations.max' => 'Las observaciones no pueden exceder 1000 caracteres.',
        ];
    }

    protected function prepareForValidation(): void
    {
        // Calculate period dates based on year and month
        if ($this->has('year') && $this->has('month')) {
            $year = (int) $this->year;
            $month = (int) $this->month;

            $periodStart = date('Y-m-d', strtotime("$year-$month-01"));
            $periodEnd = date('Y-m-t', strtotime("$year-$month-01"));

            $this->merge([
                'period_start_date' => $periodStart,
                'period_end_date' => $periodEnd,
            ]);
        }
    }
}
