<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAlertSettingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create alert settings');
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
            'low_stock_threshold_days' => ['required', 'integer', 'min:1', 'max:365'],
            'critical_stock_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'high_stock_percentage' => ['required', 'numeric', 'min:0', 'max:100', 'gte:critical_stock_percentage'],
            'medium_stock_percentage' => ['required', 'numeric', 'min:0', 'max:100', 'gte:high_stock_percentage'],
            'expiring_soon_days' => ['required', 'integer', 'min:1', 'max:365'],
            'expiring_critical_days' => ['required', 'integer', 'min:1', 'max:365', 'lte:expiring_soon_days'],
            'expiring_high_days' => ['required', 'integer', 'min:1', 'max:365', 'gte:expiring_critical_days', 'lte:expiring_soon_days'],
            'email_alerts_enabled' => ['boolean'],
            'email_recipients' => ['nullable', 'array'],
            'email_recipients.*' => ['email'],
            'email_on_critical_only' => ['boolean'],
            'email_on_low_stock' => ['boolean'],
            'email_on_out_of_stock' => ['boolean'],
            'email_on_expiring' => ['boolean'],
            'email_on_expired' => ['boolean'],
            'email_frequency' => ['required', 'in:immediate,daily_digest,weekly_digest'],
            'digest_time' => ['nullable', 'date_format:H:i'],
            'browser_notifications_enabled' => ['boolean'],
            'dashboard_alerts_enabled' => ['boolean'],
            'is_active' => ['boolean'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es requerido.',
            'name.max' => 'El nombre no puede tener más de 255 caracteres.',
            'low_stock_threshold_days.required' => 'Los días de umbral de stock bajo son requeridos.',
            'low_stock_threshold_days.integer' => 'Los días deben ser un número entero.',
            'low_stock_threshold_days.min' => 'Los días deben ser al menos 1.',
            'low_stock_threshold_days.max' => 'Los días no pueden ser más de 365.',
            'critical_stock_percentage.required' => 'El porcentaje crítico es requerido.',
            'critical_stock_percentage.numeric' => 'El porcentaje crítico debe ser un número.',
            'critical_stock_percentage.min' => 'El porcentaje crítico debe ser al menos 0.',
            'critical_stock_percentage.max' => 'El porcentaje crítico no puede ser más de 100.',
            'high_stock_percentage.required' => 'El porcentaje alto es requerido.',
            'high_stock_percentage.gte' => 'El porcentaje alto debe ser mayor o igual al porcentaje crítico.',
            'medium_stock_percentage.required' => 'El porcentaje medio es requerido.',
            'medium_stock_percentage.gte' => 'El porcentaje medio debe ser mayor o igual al porcentaje alto.',
            'expiring_soon_days.required' => 'Los días de vencimiento próximo son requeridos.',
            'expiring_critical_days.required' => 'Los días de vencimiento crítico son requeridos.',
            'expiring_critical_days.lte' => 'Los días críticos deben ser menores o iguales a los días de vencimiento próximo.',
            'expiring_high_days.required' => 'Los días de vencimiento alto son requeridos.',
            'expiring_high_days.gte' => 'Los días altos deben ser mayores o iguales a los días críticos.',
            'expiring_high_days.lte' => 'Los días altos deben ser menores o iguales a los días de vencimiento próximo.',
            'email_recipients.array' => 'Los destinatarios de email deben ser una lista.',
            'email_recipients.*.email' => 'Cada destinatario debe ser un email válido.',
            'email_frequency.required' => 'La frecuencia de email es requerida.',
            'email_frequency.in' => 'La frecuencia debe ser inmediata, resumen diario o resumen semanal.',
            'digest_time.date_format' => 'La hora del resumen debe estar en formato HH:MM.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'company_id' => $this->user()->company_id,
            'email_alerts_enabled' => $this->boolean('email_alerts_enabled'),
            'email_on_critical_only' => $this->boolean('email_on_critical_only'),
            'email_on_low_stock' => $this->boolean('email_on_low_stock'),
            'email_on_out_of_stock' => $this->boolean('email_on_out_of_stock'),
            'email_on_expiring' => $this->boolean('email_on_expiring'),
            'email_on_expired' => $this->boolean('email_on_expired'),
            'browser_notifications_enabled' => $this->boolean('browser_notifications_enabled', true),
            'dashboard_alerts_enabled' => $this->boolean('dashboard_alerts_enabled', true),
            'is_active' => $this->boolean('is_active', true),
        ]);
    }
}
