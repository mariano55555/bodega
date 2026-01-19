<?php

use App\Http\Requests\UpdateDonorRequest;
use App\Models\Donor;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component
{
    public Donor $donor;

    public string $name = '';

    public ?string $legal_name = '';

    public ?string $tax_id = '';

    public string $donor_type = '';

    public ?string $email = '';

    public ?string $phone = '';

    public ?string $website = '';

    public ?string $address = '';

    public string $departamento_id = '';

    public string $ciudad_id = '';

    public ?string $city = '';

    public ?string $state = '';

    public ?string $country = '';

    public ?string $postal_code = '';

    public ?string $contact_person = '';

    public ?string $contact_phone = '';

    public ?string $contact_email = '';

    public ?int $rating = null;

    public ?string $notes = '';

    public bool $is_active = true;

    public function mount(Donor $donor): void
    {
        $this->donor = $donor;
        $this->fill($donor->only([
            'name', 'legal_name', 'tax_id', 'donor_type', 'email', 'phone', 'website',
            'address', 'city', 'state', 'country', 'postal_code',
            'contact_person', 'contact_phone', 'contact_email',
            'rating', 'notes', 'is_active',
        ]));

        // Try to find and set departamento_id and ciudad_id based on existing city and state
        if ($this->state) {
            $departamento = \App\Models\Departamento::where('name', $this->state)->first();
            if ($departamento) {
                $this->departamento_id = (string) $departamento->id;

                if ($this->city) {
                    $ciudad = \App\Models\Ciudad::where('departamento_id', $departamento->id)
                        ->where('name', $this->city)
                        ->first();
                    if ($ciudad) {
                        $this->ciudad_id = (string) $ciudad->id;
                    }
                }
            }
        }
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
        $validated = $this->validate((new UpdateDonorRequest)->rules());
        $this->donor->update($validated);
        session()->flash('success', 'Donante actualizado exitosamente.');
        $this->redirect(route('donors.index'), navigate: true);
    }

    public function cancel(): void
    {
        $this->redirect(route('donors.index'), navigate: true);
    }
}; ?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Editar Donante</flux:heading>
            <flux:subheading class="mt-2">
                {{ $donor->name }}
            </flux:subheading>
        </div>
    </div>

    <form wire:submit="save" class="space-y-8">
        <flux:card>
            <flux:heading size="lg" class="mb-6">Información General</flux:heading>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <flux:field>
                    <flux:label badge="Requerido">Nombre</flux:label>
                    <flux:input wire:model="name" placeholder="Nombre del donante" />
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
                    <flux:label badge="Requerido">Tipo de Donante</flux:label>
                    <flux:select wire:model="donor_type" placeholder="Seleccione un tipo">
                        <option value="">Seleccione un tipo</option>
                        <option value="individual">Persona Individual</option>
                        <option value="organization">Organización</option>
                        <option value="government">Gobierno</option>
                        <option value="ngo">ONG</option>
                        <option value="international">Organización Internacional</option>
                    </flux:select>
                    <flux:error name="donor_type" />
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
                    <flux:input type="email" wire:model="email" placeholder="email@donante.com" icon="envelope" />
                    <flux:error name="email" />
                </flux:field>

                <flux:field>
                    <flux:label>Teléfono</flux:label>
                    <flux:input wire:model="phone" placeholder="0000-0000" icon="phone" />
                    <flux:error name="phone" />
                </flux:field>

                <flux:field>
                    <flux:label>Sitio Web</flux:label>
                    <flux:input wire:model="website" placeholder="https://www.donante.com" icon="globe-alt" />
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
                    <flux:input type="email" wire:model="contact_email" placeholder="contacto@donante.com" icon="envelope" />
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
            </div>
        </flux:card>

        <flux:card>
            <flux:heading size="lg" class="mb-6">Información Adicional</flux:heading>

            <flux:field>
                <flux:label>Notas</flux:label>
                <flux:textarea wire:model="notes" placeholder="Observaciones adicionales sobre el donante..." rows="3" />
                <flux:error name="notes" />
            </flux:field>
        </flux:card>

        <flux:card>
            <flux:heading size="lg" class="mb-6">Estado</flux:heading>

            <div class="space-y-4">
                <flux:switch wire:model="is_active" description="Los donantes activos están disponibles para registrar donaciones">
                    <flux:text>Donante activo</flux:text>
                </flux:switch>
                <flux:error name="is_active" />
            </div>
        </flux:card>

        <div class="flex items-center justify-between">
            <flux:button variant="ghost" wire:click="cancel" type="button">
                Cancelar
            </flux:button>

            <flux:button type="submit" variant="primary" icon="check">
                Actualizar Donante
            </flux:button>
        </div>
    </form>
</div>
