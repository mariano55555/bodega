<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
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
            'sku' => [
                'required',
                'string',
                'max:100',
                Rule::unique('products', 'sku')->where(function ($query) {
                    return $query->where('company_id', $this->company_id);
                }),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'category_id' => ['required', 'integer', 'exists:product_categories,id'],
            'unit_of_measure_id' => ['required', 'integer', 'exists:units_of_measure,id'],
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'cost' => ['required', 'numeric', 'min:0', 'max:999999999.99'],
            'price' => ['required', 'numeric', 'min:0', 'max:999999999.99'],
            'barcode' => ['nullable', 'string', 'max:255'],
            'image_path' => ['nullable', 'string', 'max:500'],
            'track_inventory' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'valuation_method' => ['required', 'string', 'in:fifo,lifo,average'],
            'minimum_stock' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'maximum_stock' => ['nullable', 'numeric', 'min:0', 'max:999999999.99', 'gte:minimum_stock'],
            'attributes' => ['nullable', 'array'],
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
            'name.required' => 'El nombre del producto es obligatorio.',
            'name.string' => 'El nombre del producto debe ser una cadena de texto.',
            'name.max' => 'El nombre del producto no puede tener más de :max caracteres.',

            'sku.required' => 'El código SKU es obligatorio.',
            'sku.string' => 'El código SKU debe ser una cadena de texto.',
            'sku.max' => 'El código SKU no puede tener más de :max caracteres.',
            'sku.unique' => 'Este código SKU ya existe en la empresa.',

            'description.string' => 'La descripción debe ser una cadena de texto.',
            'description.max' => 'La descripción no puede tener más de :max caracteres.',

            'category_id.required' => 'La categoría es obligatoria.',
            'category_id.integer' => 'La categoría debe ser un número válido.',
            'category_id.exists' => 'La categoría seleccionada no existe.',

            'unit_of_measure_id.required' => 'La unidad de medida es obligatoria.',
            'unit_of_measure_id.integer' => 'La unidad de medida debe ser un número válido.',
            'unit_of_measure_id.exists' => 'La unidad de medida seleccionada no existe.',

            'company_id.required' => 'La empresa es obligatoria.',
            'company_id.integer' => 'La empresa debe ser un número válido.',
            'company_id.exists' => 'La empresa seleccionada no existe.',

            'cost.required' => 'El costo unitario es obligatorio.',
            'cost.numeric' => 'El costo unitario debe ser un número.',
            'cost.min' => 'El costo unitario debe ser mayor o igual a :min.',
            'cost.max' => 'El costo unitario no puede ser mayor a :max.',

            'price.required' => 'El precio de venta es obligatorio.',
            'price.numeric' => 'El precio de venta debe ser un número.',
            'price.min' => 'El precio de venta debe ser mayor o igual a :min.',
            'price.max' => 'El precio de venta no puede ser mayor a :max.',

            'barcode.string' => 'El código de barras debe ser una cadena de texto.',
            'barcode.max' => 'El código de barras no puede tener más de :max caracteres.',

            'image_path.string' => 'La ruta de la imagen debe ser una cadena de texto.',
            'image_path.max' => 'La ruta de la imagen no puede tener más de :max caracteres.',

            'track_inventory.boolean' => 'El control de inventario debe ser verdadero o falso.',
            'is_active.boolean' => 'El estado activo debe ser verdadero o falso.',

            'valuation_method.required' => 'El método de valuación es obligatorio.',
            'valuation_method.string' => 'El método de valuación debe ser una cadena de texto.',
            'valuation_method.in' => 'El método de valuación seleccionado no es válido. Debe ser FIFO, LIFO o Promedio.',

            'minimum_stock.numeric' => 'El stock mínimo debe ser un número.',
            'minimum_stock.min' => 'El stock mínimo debe ser mayor o igual a :min.',
            'minimum_stock.max' => 'El stock mínimo no puede ser mayor a :max.',

            'maximum_stock.numeric' => 'El stock máximo debe ser un número.',
            'maximum_stock.min' => 'El stock máximo debe ser mayor o igual a :min.',
            'maximum_stock.max' => 'El stock máximo no puede ser mayor a :max.',
            'maximum_stock.gte' => 'El stock máximo debe ser mayor o igual al stock mínimo.',

            'attributes.array' => 'Los atributos deben ser un arreglo.',
        ];
    }
}
