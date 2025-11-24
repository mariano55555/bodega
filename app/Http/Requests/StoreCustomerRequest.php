<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCustomerRequest extends FormRequest
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
            'type' => ['required', 'string', 'in:individual,business'],
            'business_name' => ['nullable', 'required_if:type,business', 'string', 'max:255'],
            'registration_number' => ['nullable', 'string', 'max:100'],
            'tax_id' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('customers', 'tax_id')->where(function ($query) use ($companyId) {
                    return $query->where('company_id', $companyId);
                }),
            ],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'mobile' => ['nullable', 'string', 'max:50'],
            'website' => ['nullable', 'url', 'max:255'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'contact_position' => ['nullable', 'string', 'max:255'],
            'billing_address' => ['nullable', 'string', 'max:500'],
            'billing_city' => ['nullable', 'string', 'max:100'],
            'billing_state' => ['nullable', 'string', 'max:100'],
            'billing_country' => ['nullable', 'string', 'max:100'],
            'billing_postal_code' => ['nullable', 'string', 'max:20'],
            'same_as_billing' => ['nullable', 'boolean'],
            'shipping_address' => ['nullable', 'required_if:same_as_billing,false', 'string', 'max:500'],
            'shipping_city' => ['nullable', 'string', 'max:100'],
            'shipping_state' => ['nullable', 'string', 'max:100'],
            'shipping_country' => ['nullable', 'string', 'max:100'],
            'shipping_postal_code' => ['nullable', 'string', 'max:20'],
            'payment_terms_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'payment_method' => ['nullable', 'string', 'max:100'],
            'currency' => ['nullable', 'string', 'max:3'],
            'credit_limit' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'status' => ['nullable', 'string', 'max:50'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del cliente es requerido.',
            'name.max' => 'El nombre no puede exceder 255 caracteres.',
            'type.required' => 'El tipo de cliente es requerido.',
            'type.in' => 'El tipo debe ser individual o empresa.',
            'business_name.required_if' => 'El nombre de la empresa es requerido para clientes tipo empresa.',
            'business_name.max' => 'El nombre de la empresa no puede exceder 255 caracteres.',
            'registration_number.max' => 'El número de registro no puede exceder 100 caracteres.',
            'tax_id.max' => 'El NIT/DUI no puede exceder 50 caracteres.',
            'tax_id.unique' => 'Ya existe un cliente con este NIT/DUI en su compañía.',
            'email.email' => 'El correo electrónico debe ser una dirección válida.',
            'email.max' => 'El correo electrónico no puede exceder 255 caracteres.',
            'phone.max' => 'El teléfono no puede exceder 50 caracteres.',
            'mobile.max' => 'El celular no puede exceder 50 caracteres.',
            'website.url' => 'El sitio web debe ser una URL válida.',
            'website.max' => 'El sitio web no puede exceder 255 caracteres.',
            'contact_name.max' => 'El nombre del contacto no puede exceder 255 caracteres.',
            'contact_email.email' => 'El correo del contacto debe ser válido.',
            'contact_email.max' => 'El correo del contacto no puede exceder 255 caracteres.',
            'contact_phone.max' => 'El teléfono del contacto no puede exceder 50 caracteres.',
            'contact_position.max' => 'El cargo del contacto no puede exceder 255 caracteres.',
            'billing_address.max' => 'La dirección de facturación no puede exceder 500 caracteres.',
            'billing_city.max' => 'La ciudad no puede exceder 100 caracteres.',
            'billing_state.max' => 'El departamento/estado no puede exceder 100 caracteres.',
            'billing_country.max' => 'El país no puede exceder 100 caracteres.',
            'billing_postal_code.max' => 'El código postal no puede exceder 20 caracteres.',
            'same_as_billing.boolean' => 'El campo debe ser verdadero o falso.',
            'shipping_address.required_if' => 'La dirección de envío es requerida cuando es diferente a facturación.',
            'shipping_address.max' => 'La dirección de envío no puede exceder 500 caracteres.',
            'shipping_city.max' => 'La ciudad de envío no puede exceder 100 caracteres.',
            'shipping_state.max' => 'El departamento de envío no puede exceder 100 caracteres.',
            'shipping_country.max' => 'El país de envío no puede exceder 100 caracteres.',
            'shipping_postal_code.max' => 'El código postal de envío no puede exceder 20 caracteres.',
            'payment_terms_days.integer' => 'Los días de crédito deben ser un número entero.',
            'payment_terms_days.min' => 'Los días de crédito no pueden ser negativos.',
            'payment_terms_days.max' => 'Los días de crédito no pueden exceder 365.',
            'payment_method.max' => 'El método de pago no puede exceder 100 caracteres.',
            'currency.max' => 'La moneda no puede exceder 3 caracteres.',
            'credit_limit.numeric' => 'El límite de crédito debe ser un número.',
            'credit_limit.min' => 'El límite de crédito no puede ser negativo.',
            'credit_limit.max' => 'El límite de crédito excede el límite permitido.',
            'status.max' => 'El estado no puede exceder 50 caracteres.',
            'is_active.boolean' => 'El estado activo debe ser verdadero o falso.',
        ];
    }
}
