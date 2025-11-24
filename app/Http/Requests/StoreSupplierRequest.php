<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $companyId = auth()->user()->company_id ?? null;

        return [
            'name' => ['required', 'string', 'max:255'],
            'legal_name' => ['nullable', 'string', 'max:255'],
            'tax_id' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('suppliers', 'tax_id')->where(function ($query) use ($companyId) {
                    return $query->where('company_id', $companyId);
                }),
            ],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'website' => ['nullable', 'url', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'payment_terms' => ['nullable', 'string', 'max:255'],
            'credit_limit' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'rating' => ['nullable', 'integer', 'min:1', 'max:5'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del proveedor es requerido.',
            'name.max' => 'El nombre no puede exceder 255 caracteres.',
            'legal_name.max' => 'El nombre legal no puede exceder 255 caracteres.',
            'tax_id.max' => 'El NIT/DUI no puede exceder 50 caracteres.',
            'tax_id.unique' => 'Ya existe un proveedor con este NIT/DUI en su compañía.',
            'email.email' => 'El correo electrónico debe ser una dirección válida.',
            'email.max' => 'El correo electrónico no puede exceder 255 caracteres.',
            'phone.max' => 'El teléfono no puede exceder 50 caracteres.',
            'website.url' => 'El sitio web debe ser una URL válida.',
            'website.max' => 'El sitio web no puede exceder 255 caracteres.',
            'address.max' => 'La dirección no puede exceder 500 caracteres.',
            'city.max' => 'La ciudad no puede exceder 100 caracteres.',
            'state.max' => 'El departamento/estado no puede exceder 100 caracteres.',
            'country.max' => 'El país no puede exceder 100 caracteres.',
            'postal_code.max' => 'El código postal no puede exceder 20 caracteres.',
            'contact_person.max' => 'El nombre del contacto no puede exceder 255 caracteres.',
            'contact_phone.max' => 'El teléfono de contacto no puede exceder 50 caracteres.',
            'contact_email.email' => 'El correo del contacto debe ser una dirección válida.',
            'contact_email.max' => 'El correo del contacto no puede exceder 255 caracteres.',
            'payment_terms.max' => 'Los términos de pago no pueden exceder 255 caracteres.',
            'credit_limit.numeric' => 'El límite de crédito debe ser un número.',
            'credit_limit.min' => 'El límite de crédito no puede ser negativo.',
            'credit_limit.max' => 'El límite de crédito excede el límite permitido.',
            'rating.integer' => 'La calificación debe ser un número entero.',
            'rating.min' => 'La calificación mínima es 1.',
            'rating.max' => 'La calificación máxima es 5.',
            'notes.max' => 'Las notas no pueden exceder 2000 caracteres.',
            'is_active.boolean' => 'El estado activo debe ser verdadero o falso.',
        ];
    }
}
