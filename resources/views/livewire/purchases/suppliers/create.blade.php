<?php

use App\Http\Requests\StoreSupplierRequest;
use App\Models\Supplier;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component
{
    public string $name = '';

    public ?string $legal_name = '';

    public ?string $tax_id = '';

    public ?string $email = '';

    public ?string $phone = '';

    public ?string $website = '';

    public ?string $address = '';

    public string $departamento_id = '';

    public string $ciudad_id = '';

    public ?string $city = '';

    public ?string $state = '';

    public string $country = 'El Salvador';

    public ?string $postal_code = '';

    public ?string $contact_person = '';

    public ?string $contact_phone = '';

    public ?string $contact_email = '';

    public ?string $payment_terms = '';

    public ?float $credit_limit = null;

    public ?int $rating = null;

    public ?string $notes = '';

    public bool $is_active = true;

    public string $company_id = '';

    public function mount(): void
    {
        // Set default company to user's company if not super admin
        if (! auth()->user()->isSuperAdmin()) {
            $this->company_id = auth()->user()->company_id;
        }
    }

    public function isSuperAdmin(): bool
    {
        return auth()->user()->isSuperAdmin();
    }

    #[\Livewire\Attributes\Computed]
    public function companies()
    {
        if ($this->isSuperAdmin()) {
            return \App\Models\Company::active()->orderBy('name')->get(['id', 'name']);
        }

        return collect([]);
    }

    #[\Livewire\Attributes\Computed]
    public function departamentos()
    {
        return \App\Models\Departamento::active()->orderBy('name')->get(['id', 'name']);
    }

    #[\Livewire\Attributes\Computed]
    public function ciudades()
    {
        if (! $this->departamento_id) {
            return collect([]);
        }

        return \App\Models\Ciudad::where('departamento_id', $this->departamento_id)
            ->active()
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function updatedDepartamentoId(): void
    {
        $this->ciudad_id = '';
        $this->city = '';
        $this->state = '';

        if ($this->departamento_id) {
            $departamento = \App\Models\Departamento::find($this->departamento_id);
            if ($departamento) {
                $this->state = $departamento->name;
            }
        }
    }

    public function updatedCiudadId(): void
    {
        if ($this->ciudad_id) {
            $ciudad = \App\Models\Ciudad::find($this->ciudad_id);
            if ($ciudad) {
                $this->city = $ciudad->name;
            }
        } else {
            $this->city = '';
        }
    }

    public function save(): void
    {
        $validated = $this->validate((new StoreSupplierRequest)->rules());

        // Use selected company_id or user's company_id for non-super-admins
        $companyId = $this->isSuperAdmin() ? $this->company_id : auth()->user()->company_id;

        $supplier = Supplier::create([
            ...$validated,
            'company_id' => $companyId,
        ]);

        session()->flash('success', 'Proveedor creado exitosamente.');
        $this->redirect(route('purchases.suppliers.index'), navigate: true);
    }

    public function cancel(): void
    {
        $this->redirect(route('purchases.suppliers.index'), navigate: true);
    }
}; ?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Nuevo Proveedor</flux:heading>
            <flux:subheading class="mt-2">
                Complete la información del proveedor. Los campos marcados con (*) son obligatorios.
            </flux:subheading>
        </div>
    </div>

    <form wire:submit="save" class="space-y-8">
        <flux:card>
            <flux:heading size="lg" class="mb-6">Información General</flux:heading>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @if($this->isSuperAdmin())
                    <flux:field class="md:col-span-2">
                        <flux:label badge="Requerido">Empresa</flux:label>
                        <flux:select wire:model.live="company_id">
                            <option value="">Seleccione una empresa</option>
                            @foreach ($this->companies as $company)
                                <option value="{{ $company->id }}">{{ $company->name }}</option>
                            @endforeach
                        </flux:select>
                        <flux:error name="company_id" />
                    </flux:field>
                @endif

                <flux:field>
                    <flux:label badge="Requerido">Nombre Comercial</flux:label>
                    <flux:input wire:model="name" placeholder="Nombre del proveedor" />
                    <flux:error name="name" />
                </flux:field>

                <flux:field>
                    <flux:label>Razón Social</flux:label>
                    <flux:input wire:model="legal_name" placeholder="Nombre legal completo" />
                    <flux:error name="legal_name" />
                </flux:field>

                <flux:field>
                    <flux:label>NIT/DUI</flux:label>
                    <flux:input wire:model="tax_id" placeholder="0000-000000-000-0" />
                    <flux:error name="tax_id" />
                </flux:field>

                <flux:field>
                    <flux:label>Calificación</flux:label>
                    <flux:select wire:model="rating" placeholder="Seleccione una calificación">
                        <option value="">Sin calificar</option>
                        <option value="1">⭐ 1 estrella</option>
                        <option value="2">⭐⭐ 2 estrellas</option>
                        <option value="3">⭐⭐⭐ 3 estrellas</option>
                        <option value="4">⭐⭐⭐⭐ 4 estrellas</option>
                        <option value="5">⭐⭐⭐⭐⭐ 5 estrellas</option>
                    </flux:select>
                    <flux:error name="rating" />
                </flux:field>
            </div>
        </flux:card>

        <flux:card>
            <flux:heading size="lg" class="mb-6">Contacto Principal</flux:heading>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <flux:field>
                    <flux:label>Correo Electrónico</flux:label>
                    <flux:input type="email" wire:model="email" placeholder="email@proveedor.com" icon="envelope" />
                    <flux:error name="email" />
                </flux:field>

                <flux:field>
                    <flux:label>Teléfono</flux:label>
                    <flux:input wire:model="phone" placeholder="0000-0000" icon="phone" />
                    <flux:error name="phone" />
                </flux:field>

                <flux:field>
                    <flux:label>Sitio Web</flux:label>
                    <flux:input wire:model="website" placeholder="https://www.proveedor.com" icon="globe-alt" />
                    <flux:error name="website" />
                </flux:field>
            </div>
        </flux:card>

        <flux:card>
            <flux:heading size="lg" class="mb-6">Persona de Contacto</flux:heading>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <flux:field>
                    <flux:label>Nombre Completo</flux:label>
                    <flux:input wire:model="contact_person" placeholder="Nombre del contacto" />
                    <flux:error name="contact_person" />
                </flux:field>

                <flux:field>
                    <flux:label>Teléfono</flux:label>
                    <flux:input wire:model="contact_phone" placeholder="0000-0000" icon="phone" />
                    <flux:error name="contact_phone" />
                </flux:field>

                <flux:field>
                    <flux:label>Correo Electrónico</flux:label>
                    <flux:input type="email" wire:model="contact_email" placeholder="contacto@proveedor.com" icon="envelope" />
                    <flux:error name="contact_email" />
                </flux:field>
            </div>
        </flux:card>

        <flux:card>
            <flux:heading size="lg" class="mb-6">Dirección</flux:heading>

            <div class="grid grid-cols-1 gap-6">
                <flux:field>
                    <flux:label>Dirección Completa</flux:label>
                    <flux:textarea wire:model="address" placeholder="Calle, número, colonia..." rows="2" />
                    <flux:error name="address" />
                </flux:field>

                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <flux:field>
                        <flux:label>Departamento</flux:label>
                        <flux:select wire:model.live="departamento_id" placeholder="Seleccione departamento">
                            @foreach ($this->departamentos as $departamento)
                                <option value="{{ $departamento->id }}">{{ $departamento->name }}</option>
                            @endforeach
                        </flux:select>
                        <flux:error name="state" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Ciudad/Municipio</flux:label>
                        <flux:select wire:model.live="ciudad_id" placeholder="Seleccione ciudad" :disabled="!$departamento_id">
                            @foreach ($this->ciudades as $ciudad)
                                <option value="{{ $ciudad->id }}">{{ $ciudad->name }}</option>
                            @endforeach
                        </flux:select>
                        <flux:error name="city" />
                    </flux:field>

                    <flux:field>
                        <flux:label>País</flux:label>
                        <flux:input wire:model="country" readonly />
                        <flux:error name="country" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Código Postal</flux:label>
                        <flux:input wire:model="postal_code" placeholder="1101" />
                        <flux:error name="postal_code" />
                    </flux:field>
                </div>
            </div>
        </flux:card>

        <flux:card>
            <flux:heading size="lg" class="mb-6">Condiciones Comerciales</flux:heading>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <flux:field>
                    <flux:label>Términos de Pago</flux:label>
                    <flux:input wire:model="payment_terms" placeholder="Ej: 30 días, Contado, etc." />
                    <flux:error name="payment_terms" />
                </flux:field>

                <flux:field>
                    <flux:label>Límite de Crédito ($)</flux:label>
                    <flux:input type="number" step="0.01" wire:model="credit_limit" placeholder="0.00" />
                    <flux:error name="credit_limit" />
                </flux:field>
            </div>

            <flux:field class="mt-6">
                <flux:label>Notas</flux:label>
                <flux:textarea wire:model="notes" placeholder="Observaciones adicionales sobre el proveedor..." rows="3" />
                <flux:error name="notes" />
            </flux:field>
        </flux:card>

        <flux:card>
            <flux:heading size="lg" class="mb-6">Estado</flux:heading>

            <div class="space-y-4">
                <flux:switch wire:model="is_active" description="Los proveedores activos están disponibles para crear órdenes de compra">
                    <flux:text>Proveedor activo</flux:text>
                </flux:switch>
                <flux:error name="is_active" />
            </div>
        </flux:card>

        <div class="flex items-center justify-between">
            <flux:button variant="ghost" wire:click="cancel" type="button">
                Cancelar
            </flux:button>

            <flux:button type="submit" variant="primary" icon="check">
                Guardar Proveedor
            </flux:button>
        </div>
    </form>
</div>
