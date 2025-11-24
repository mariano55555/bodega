<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateInventoryTransferRequest extends FormRequest
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
        $companyId = auth()->user()->company_id;

        return [
            'from_warehouse_id' => [
                'required',
                'integer',
                Rule::exists('warehouses', 'id')->where(function ($query) use ($companyId) {
                    return $query->where('company_id', $companyId)
                        ->where('is_active', true);
                }),
                'different:to_warehouse_id',
            ],
            'to_warehouse_id' => [
                'required',
                'integer',
                Rule::exists('warehouses', 'id')->where(function ($query) use ($companyId) {
                    return $query->where('company_id', $companyId)
                        ->where('is_active', true);
                }),
                'different:from_warehouse_id',
            ],
            'reason' => ['nullable', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'shipping_cost' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'products' => ['required', 'array', 'min:1'],
            'products.*.product_id' => [
                'required',
                'integer',
                Rule::exists('products', 'id')->where(function ($query) use ($companyId) {
                    return $query->where('company_id', $companyId)
                        ->where('is_active', true);
                }),
            ],
            'products.*.quantity' => ['required', 'numeric', 'min:0.0001', 'max:999999.9999'],
            'products.*.notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * Get custom error messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            // From Warehouse
            'from_warehouse_id.required' => 'La bodega de origen es obligatoria.',
            'from_warehouse_id.exists' => 'La bodega de origen seleccionada no existe o no está activa.',
            'from_warehouse_id.different' => 'La bodega de origen debe ser diferente a la bodega de destino.',

            // To Warehouse
            'to_warehouse_id.required' => 'La bodega de destino es obligatoria.',
            'to_warehouse_id.exists' => 'La bodega de destino seleccionada no existe o no está activa.',
            'to_warehouse_id.different' => 'La bodega de destino debe ser diferente a la bodega de origen.',

            // Reason & Notes
            'reason.string' => 'El motivo debe ser texto.',
            'reason.max' => 'El motivo no puede exceder 500 caracteres.',
            'notes.string' => 'Las notas deben ser texto.',
            'notes.max' => 'Las notas no pueden exceder 1000 caracteres.',

            // Shipping Cost
            'shipping_cost.numeric' => 'El costo de envío debe ser un número.',
            'shipping_cost.min' => 'El costo de envío no puede ser negativo.',
            'shipping_cost.max' => 'El costo de envío no puede exceder 999,999.99.',

            // Products Array
            'products.required' => 'Debe agregar al menos un producto al traslado.',
            'products.array' => 'Los productos deben ser un arreglo.',
            'products.min' => 'Debe agregar al menos un producto al traslado.',

            // Product Fields
            'products.*.product_id.required' => 'El producto es obligatorio.',
            'products.*.product_id.exists' => 'El producto seleccionado no existe o no está activo.',
            'products.*.quantity.required' => 'La cantidad es obligatoria.',
            'products.*.quantity.numeric' => 'La cantidad debe ser un número.',
            'products.*.quantity.min' => 'La cantidad debe ser mayor a 0.',
            'products.*.quantity.max' => 'La cantidad no puede exceder 999,999.9999.',
            'products.*.notes.string' => 'Las notas del producto deben ser texto.',
            'products.*.notes.max' => 'Las notas del producto no pueden exceder 500 caracteres.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'from_warehouse_id' => 'bodega de origen',
            'to_warehouse_id' => 'bodega de destino',
            'reason' => 'motivo',
            'notes' => 'notas',
            'shipping_cost' => 'costo de envío',
            'products' => 'productos',
            'products.*.product_id' => 'producto',
            'products.*.quantity' => 'cantidad',
            'products.*.notes' => 'notas del producto',
        ];
    }
}
