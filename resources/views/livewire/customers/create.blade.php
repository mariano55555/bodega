<?php

use App\Http\Requests\StoreCustomerRequest;
use App\Models\Company;
use App\Models\Customer;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component
{
    public $company_id = '';

    public $name = '';

    public $type = 'individual';

    public $business_name = '';

    public $registration_number = '';

    public $tax_id = '';

    public $email = '';

    public $phone = '';

    public $mobile = '';

    public $website = '';

    // Contact information
    public $contact_name = '';

    public $contact_email = '';

    public $contact_phone = '';

    public $contact_position = '';

    // Billing address
    public $billing_address = '';

    public $billing_departamento_id = '';

    public $billing_ciudad_id = '';

    public $billing_city = '';

    public $billing_state = '';

    public $billing_country = 'El Salvador';

    public $billing_postal_code = '';

    // Shipping address
    public $same_as_billing = true;

    public $shipping_address = '';

    public $shipping_departamento_id = '';

    public $shipping_ciudad_id = '';

    public $shipping_city = '';

    public $shipping_state = '';

    public $shipping_country = 'El Salvador';

    public $shipping_postal_code = '';

    // Payment terms
    public $payment_terms_days = 0;

    public $payment_method = '';

    public $currency = 'USD';

    public $credit_limit = 0;

    public $notes = '';

    public $status = '';

    public $is_active = true;

    public function mount(): void
    {
        // If not super admin, set company_id to user's company
        if (! $this->isSuperAdmin()) {
            $this->company_id = auth()->user()->company_id;
        }
    }

    public function isSuperAdmin(): bool
    {
        return auth()->user()->hasRole('super-admin');
    }

    #[Computed]
    public function companies()
    {
        if ($this->isSuperAdmin()) {
            return Company::active()->orderBy('name')->get();
        }

        return collect([]);
    }

    #[Computed]
    public function departamentos()
    {
        return \App\Models\Departamento::active()->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function billingCiudades()
    {
        if (! $this->billing_departamento_id) {
            return collect([]);
        }

        return \App\Models\Ciudad::where('departamento_id', $this->billing_departamento_id)
            ->active()
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    #[Computed]
    public function shippingCiudades()
    {
        if (! $this->shipping_departamento_id) {
            return collect([]);
        }

        return \App\Models\Ciudad::where('departamento_id', $this->shipping_departamento_id)
            ->active()
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function updatedBillingDepartamentoId(): void
    {
        $this->billing_ciudad_id = '';
        $this->billing_city = '';
        $this->billing_state = '';

        if ($this->billing_departamento_id) {
            $departamento = \App\Models\Departamento::find($this->billing_departamento_id);
            if ($departamento) {
                $this->billing_state = $departamento->name;
            }
        }
    }

    public function updatedBillingCiudadId(): void
    {
        if ($this->billing_ciudad_id) {
            $ciudad = \App\Models\Ciudad::find($this->billing_ciudad_id);
            if ($ciudad) {
                $this->billing_city = $ciudad->name;
            }
        } else {
            $this->billing_city = '';
        }
    }

    public function updatedShippingDepartamentoId(): void
    {
        $this->shipping_ciudad_id = '';
        $this->shipping_city = '';
        $this->shipping_state = '';

        if ($this->shipping_departamento_id) {
            $departamento = \App\Models\Departamento::find($this->shipping_departamento_id);
            if ($departamento) {
                $this->shipping_state = $departamento->name;
            }
        }
    }

    public function updatedShippingCiudadId(): void
    {
        if ($this->shipping_ciudad_id) {
            $ciudad = \App\Models\Ciudad::find($this->shipping_ciudad_id);
            if ($ciudad) {
                $this->shipping_city = $ciudad->name;
            }
        } else {
            $this->shipping_city = '';
        }
    }

    public function save(): void
    {
        $rules = (new StoreCustomerRequest)->rules();

        // Add company_id validation for super admins
        if ($this->isSuperAdmin()) {
            $rules['company_id'] = 'required|exists:companies,id';
        }

        $validated = $this->validate($rules);

        // Use selected company_id or user's company_id for non-super-admins
        $companyId = $this->isSuperAdmin() ? $this->company_id : auth()->user()->company_id;

        $customer = Customer::create([
            'company_id' => $companyId,
            'name' => $validated['name'],
            'type' => $validated['type'],
            'business_name' => $validated['business_name'] ?? null,
            'registration_number' => $validated['registration_number'] ?? null,
            'tax_id' => $validated['tax_id'] ?? null,
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'mobile' => $validated['mobile'] ?? null,
            'website' => $validated['website'] ?? null,
            'contact_name' => $validated['contact_name'] ?? null,
            'contact_email' => $validated['contact_email'] ?? null,
            'contact_phone' => $validated['contact_phone'] ?? null,
            'contact_position' => $validated['contact_position'] ?? null,
            'billing_address' => $validated['billing_address'] ?? null,
            'billing_city' => $validated['billing_city'] ?? null,
            'billing_state' => $validated['billing_state'] ?? null,
            'billing_country' => $validated['billing_country'] ?? null,
            'billing_postal_code' => $validated['billing_postal_code'] ?? null,
            'shipping_address' => $validated['same_as_billing'] ? ($validated['billing_address'] ?? null) : ($validated['shipping_address'] ?? null),
            'shipping_city' => $validated['same_as_billing'] ? ($validated['billing_city'] ?? null) : ($validated['shipping_city'] ?? null),
            'shipping_state' => $validated['same_as_billing'] ? ($validated['billing_state'] ?? null) : ($validated['shipping_state'] ?? null),
            'shipping_country' => $validated['same_as_billing'] ? ($validated['billing_country'] ?? null) : ($validated['shipping_country'] ?? null),
            'shipping_postal_code' => $validated['same_as_billing'] ? ($validated['billing_postal_code'] ?? null) : ($validated['shipping_postal_code'] ?? null),
            'payment_terms_days' => $validated['payment_terms_days'] ?? 0,
            'payment_method' => $validated['payment_method'] ?? null,
            'currency' => $validated['currency'] ?? 'USD',
            'credit_limit' => $validated['credit_limit'] ?? 0,
            'notes' => $validated['notes'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        session()->flash('success', 'Cliente creado exitosamente.');
        $this->redirect(route('customers.index'), navigate: true);
    }
}; ?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Nuevo Cliente</flux:heading>
            <flux:text class="mt-1">Registrar un nuevo cliente en el sistema</flux:text>
        </div>

        <flux:button variant="ghost" icon="arrow-left" href="{{ route('customers.index') }}" wire:navigate>
            Volver al listado
        </flux:button>
    </div>

    <form wire:submit="save" class="space-y-6">
        <!-- Basic Information -->
        <flux:card>
            <flux:heading size="lg">Información Básica</flux:heading>
            <flux:separator class="my-4" />

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @if(auth()->user()->hasRole('super-admin'))
                    <!-- Company Selection (Super Admin Only) -->
                    <flux:field class="md:col-span-2">
                        <flux:label>Empresa <span class="text-red-600">*</span></flux:label>
                        <flux:select wire:model="company_id" required description="Empresa para la cual se creará este cliente">
                            <option value="">Seleccione empresa</option>
                            @foreach ($this->companies as $company)
                                <option value="{{ $company->id }}">{{ $company->name }}</option>
                            @endforeach
                        </flux:select>
                        @error('company_id') <flux:text variant="danger">{{ $message }}</flux:text> @enderror
                    </flux:field>
                @endif

                <flux:field>
                    <flux:label>Tipo de Cliente <span class="text-red-600">*</span></flux:label>
                    <flux:select wire:model.live="type" required>
                        <option value="individual">Individual</option>
                        <option value="business">Empresa</option>
                    </flux:select>
                    @error('type') <flux:text variant="danger">{{ $message }}</flux:text> @enderror
                </flux:field>

                <flux:field>
                    <flux:label>Nombre Completo <span class="text-red-600">*</span></flux:label>
                    <flux:input wire:model="name" placeholder="Ej: Juan Pérez" required />
                    @error('name') <flux:text variant="danger">{{ $message }}</flux:text> @enderror
                </flux:field>

                @if($type === 'business')
                    <flux:field>
                        <flux:label>Nombre de Empresa <span class="text-red-600">*</span></flux:label>
                        <flux:input wire:model="business_name" placeholder="Ej: Comercial ABC S.A. de C.V." />
                        @error('business_name') <flux:text variant="danger">{{ $message }}</flux:text> @enderror
                    </flux:field>

                    <flux:field>
                        <flux:label>Número de Registro</flux:label>
                        <flux:input wire:model="registration_number" placeholder="Ej: 123456-7" />
                        @error('registration_number') <flux:text variant="danger">{{ $message }}</flux:text> @enderror
                    </flux:field>
                @endif

                <flux:field>
                    <flux:label>NIT/DUI</flux:label>
                    <flux:input wire:model="tax_id" placeholder="Ej: 0614-123456-123-4" />
                    @error('tax_id') <flux:text variant="danger">{{ $message }}</flux:text> @enderror
                </flux:field>

                <flux:field>
                    <flux:label>Email</flux:label>
                    <flux:input type="email" wire:model="email" placeholder="cliente@ejemplo.com" />
                    @error('email') <flux:text variant="danger">{{ $message }}</flux:text> @enderror
                </flux:field>

                <flux:field>
                    <flux:label>Teléfono</flux:label>
                    <flux:input wire:model="phone" placeholder="2222-2222" />
                    @error('phone') <flux:text variant="danger">{{ $message }}</flux:text> @enderror
                </flux:field>

                <flux:field>
                    <flux:label>Móvil</flux:label>
                    <flux:input wire:model="mobile" placeholder="7777-7777" />
                    @error('mobile') <flux:text variant="danger">{{ $message }}</flux:text> @enderror
                </flux:field>

                <flux:field>
                    <flux:label>Sitio Web</flux:label>
                    <flux:input wire:model="website" placeholder="https://ejemplo.com" />
                    @error('website') <flux:text variant="danger">{{ $message }}</flux:text> @enderror
                </flux:field>
            </div>
        </flux:card>

        <!-- Contact Person -->
        <flux:card>
            <flux:heading size="lg">Persona de Contacto</flux:heading>
            <flux:separator class="my-4" />

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <flux:field>
                    <flux:label>Nombre del Contacto</flux:label>
                    <flux:input wire:model="contact_name" placeholder="Nombre completo" />
                    @error('contact_name') <flux:text variant="danger">{{ $message }}</flux:text> @enderror
                </flux:field>

                <flux:field>
                    <flux:label>Email del Contacto</flux:label>
                    <flux:input type="email" wire:model="contact_email" placeholder="contacto@ejemplo.com" />
                    @error('contact_email') <flux:text variant="danger">{{ $message }}</flux:text> @enderror
                </flux:field>

                <flux:field>
                    <flux:label>Teléfono del Contacto</flux:label>
                    <flux:input wire:model="contact_phone" placeholder="7777-7777" />
                    @error('contact_phone') <flux:text variant="danger">{{ $message }}</flux:text> @enderror
                </flux:field>

                <flux:field>
                    <flux:label>Cargo/Posición</flux:label>
                    <flux:input wire:model="contact_position" placeholder="Ej: Gerente de Compras" />
                    @error('contact_position') <flux:text variant="danger">{{ $message }}</flux:text> @enderror
                </flux:field>
            </div>
        </flux:card>

        <!-- Billing Address -->
        <flux:card>
            <flux:heading size="lg">Dirección de Facturación</flux:heading>
            <flux:separator class="my-4" />

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <flux:field class="md:col-span-2">
                    <flux:label>Dirección</flux:label>
                    <flux:textarea wire:model="billing_address" placeholder="Calle, número, colonia..." rows="2" />
                    @error('billing_address') <flux:text variant="danger">{{ $message }}</flux:text> @enderror
                </flux:field>

                <flux:field>
                    <flux:label>Departamento</flux:label>
                    <flux:select wire:model.live="billing_departamento_id" placeholder="Seleccione departamento">
                        @foreach ($this->departamentos as $departamento)
                            <option value="{{ $departamento->id }}">{{ $departamento->name }}</option>
                        @endforeach
                    </flux:select>
                    @error('billing_state') <flux:text variant="danger">{{ $message }}</flux:text> @enderror
                </flux:field>

                <flux:field>
                    <flux:label>Ciudad/Municipio</flux:label>
                    <flux:select wire:model.live="billing_ciudad_id" placeholder="Seleccione ciudad" :disabled="!$billing_departamento_id">
                        @foreach ($this->billingCiudades as $ciudad)
                            <option value="{{ $ciudad->id }}">{{ $ciudad->name }}</option>
                        @endforeach
                    </flux:select>
                    @error('billing_city') <flux:text variant="danger">{{ $message }}</flux:text> @enderror
                </flux:field>

                <flux:field>
                    <flux:label>País</flux:label>
                    <flux:input wire:model="billing_country" readonly />
                    @error('billing_country') <flux:text variant="danger">{{ $message }}</flux:text> @enderror
                </flux:field>

                <flux:field>
                    <flux:label>Código Postal</flux:label>
                    <flux:input wire:model="billing_postal_code" placeholder="Ej: 1101" />
                    @error('billing_postal_code') <flux:text variant="danger">{{ $message }}</flux:text> @enderror
                </flux:field>
            </div>
        </flux:card>

        <!-- Shipping Address -->
        <flux:card>
            <flux:heading size="lg">Dirección de Envío</flux:heading>
            <flux:separator class="my-4" />

            <div class="mb-4">
                <flux:checkbox
                    wire:model.live="same_as_billing"
                    label="Usar la misma dirección de facturación"
                />
            </div>

            @if(!$same_as_billing)
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <flux:field class="md:col-span-2">
                        <flux:label>Dirección <span class="text-red-600">*</span></flux:label>
                        <flux:textarea wire:model="shipping_address" placeholder="Calle, número, colonia..." rows="2" />
                        @error('shipping_address') <flux:text variant="danger">{{ $message }}</flux:text> @enderror
                    </flux:field>

                    <flux:field>
                        <flux:label>Departamento</flux:label>
                        <flux:select wire:model.live="shipping_departamento_id" placeholder="Seleccione departamento">
                            @foreach ($this->departamentos as $departamento)
                                <option value="{{ $departamento->id }}">{{ $departamento->name }}</option>
                            @endforeach
                        </flux:select>
                        @error('shipping_state') <flux:text variant="danger">{{ $message }}</flux:text> @enderror
                    </flux:field>

                    <flux:field>
                        <flux:label>Ciudad/Municipio</flux:label>
                        <flux:select wire:model.live="shipping_ciudad_id" placeholder="Seleccione ciudad" :disabled="!$shipping_departamento_id">
                            @foreach ($this->shippingCiudades as $ciudad)
                                <option value="{{ $ciudad->id }}">{{ $ciudad->name }}</option>
                            @endforeach
                        </flux:select>
                        @error('shipping_city') <flux:text variant="danger">{{ $message }}</flux:text> @enderror
                    </flux:field>

                    <flux:field>
                        <flux:label>País</flux:label>
                        <flux:input wire:model="shipping_country" readonly />
                        @error('shipping_country') <flux:text variant="danger">{{ $message }}</flux:text> @enderror
                    </flux:field>

                    <flux:field>
                        <flux:label>Código Postal</flux:label>
                        <flux:input wire:model="shipping_postal_code" placeholder="Ej: 1101" />
                        @error('shipping_postal_code') <flux:text variant="danger">{{ $message }}</flux:text> @enderror
                    </flux:field>
                </div>
            @endif
        </flux:card>

        <!-- Payment Terms -->
        <flux:card>
            <flux:heading size="lg">Términos de Pago</flux:heading>
            <flux:separator class="my-4" />

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <flux:field>
                    <flux:label>Días de Crédito</flux:label>
                    <flux:input type="number" wire:model="payment_terms_days" min="0" max="365" />
                    <flux:text>Días de plazo para pago</flux:text>
                    @error('payment_terms_days') <flux:text variant="danger">{{ $message }}</flux:text> @enderror
                </flux:field>

                <flux:field>
                    <flux:label>Método de Pago Preferido</flux:label>
                    <flux:select wire:model="payment_method">
                        <option value="">Seleccionar...</option>
                        <option value="efectivo">Efectivo</option>
                        <option value="transferencia">Transferencia Bancaria</option>
                        <option value="cheque">Cheque</option>
                        <option value="tarjeta">Tarjeta</option>
                    </flux:select>
                    @error('payment_method') <flux:text variant="danger">{{ $message }}</flux:text> @enderror
                </flux:field>

                <flux:field>
                    <flux:label>Moneda</flux:label>
                    <flux:select wire:model="currency">
                        <option value="USD">USD - Dólar</option>
                        <option value="SVC">SVC - Colón</option>
                    </flux:select>
                    @error('currency') <flux:text variant="danger">{{ $message }}</flux:text> @enderror
                </flux:field>

                <flux:field>
                    <flux:label>Límite de Crédito</flux:label>
                    <flux:input type="number" wire:model="credit_limit" step="0.01" min="0" />
                    <flux:text>Monto máximo de crédito (USD)</flux:text>
                    @error('credit_limit') <flux:text variant="danger">{{ $message }}</flux:text> @enderror
                </flux:field>
            </div>
        </flux:card>

        <!-- Additional Notes -->
        <flux:card>
            <flux:heading size="lg">Notas Adicionales</flux:heading>
            <flux:separator class="my-4" />

            <flux:field>
                <flux:label>Notas</flux:label>
                <flux:textarea wire:model="notes" placeholder="Información adicional sobre el cliente..." rows="4" />
                @error('notes') <flux:text variant="danger">{{ $message }}</flux:text> @enderror
            </flux:field>

            <div class="mt-4">
                <flux:checkbox
                    wire:model="is_active"
                    label="Cliente activo"
                />
            </div>
        </flux:card>

        <!-- Actions -->
        <div class="flex justify-end gap-4">
            <flux:button variant="ghost" href="{{ route('customers.index') }}" wire:navigate>
                Cancelar
            </flux:button>
            <flux:button type="submit" variant="primary">
                Guardar Cliente
            </flux:button>
        </div>
    </form>
</div>
