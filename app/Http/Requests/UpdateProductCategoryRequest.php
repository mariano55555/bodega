<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductCategoryRequest extends FormRequest
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
        $categoryId = $this->route('category')?->id ?? $this->category;

        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50', 'alpha_dash', Rule::unique('product_categories', 'code')->ignore($categoryId)],
            'legacy_code' => ['nullable', 'string', 'max:10'],
            'parent_id' => ['nullable', 'integer', 'exists:product_categories,id'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
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
            'name.required' => 'El nombre de la categoría es obligatorio.',
            'name.string' => 'El nombre de la categoría debe ser una cadena de texto.',
            'name.max' => 'El nombre de la categoría no puede tener más de :max caracteres.',

            'code.string' => 'El código debe ser una cadena de texto.',
            'code.max' => 'El código no puede tener más de :max caracteres.',
            'code.alpha_dash' => 'El código solo puede contener letras, números, guiones y guiones bajos.',
            'code.unique' => 'Este código de categoría ya existe.',

            'legacy_code.string' => 'El código legacy debe ser una cadena de texto.',
            'legacy_code.max' => 'El código legacy no puede tener más de :max caracteres.',

            'parent_id.integer' => 'La categoría padre debe ser un número entero.',
            'parent_id.exists' => 'La categoría padre seleccionada no existe.',

            'description.string' => 'La descripción debe ser una cadena de texto.',
            'description.max' => 'La descripción no puede tener más de :max caracteres.',

            'is_active.boolean' => 'El estado activo debe ser verdadero o falso.',
        ];
    }
}
