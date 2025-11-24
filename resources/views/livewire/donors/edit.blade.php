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
            <flux:text class="mt-1">{{ $donor->name }}</flux:text>
        </div>
    </div>

    <form wire:submit="save" class="space-y-8">
        <flux:card>
            <flux:heading size="lg">Información General</flux:heading>
            <flux:separator />

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <flux:field>
                    <flux:label>Nombre *</flux:label>
                    <flux:input wire:model="name" />
                    <flux:error name="name" />
                </flux:field>

                <flux:field>
                    <flux:label>Razón Social</flux:label>
                    <flux:input wire:model="legal_name" />
                    <flux:error name="legal_name" />
                </flux:field>

                <flux:field>
                    <flux:label>NIT/DUI</flux:label>
                    <flux:input wire:model="tax_id" />
                    <flux:error name="tax_id" />
                </flux:field>

                <flux:field>
                    <flux:label>Tipo de Donante *</flux:label>
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
                    <flux:select wire:model="rating" placeholder="Sin calificar">
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
            <flux:heading size="lg">Contacto Principal</flux:heading>
            <flux:separator />

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <flux:field>
                    <flux:label>Correo Electrónico</flux:label>
                    <flux:input type="email" wire:model="email" icon="envelope" />
                    <flux:error name="email" />
                </flux:field>

                <flux:field>
                    <flux:label>Teléfono</flux:label>
                    <flux:input wire:model="phone" icon="phone" />
                    <flux:error name="phone" />
                </flux:field>

                <flux:field>
                    <flux:label>Sitio Web</flux:label>
                    <flux:input wire:model="website" icon="globe-alt" />
                    <flux:error name="website" />
                </flux:field>
            </div>
        </flux:card>

        <flux:card>
            <flux:heading size="lg">Persona de Contacto</flux:heading>
            <flux:separator />

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <flux:field>
                    <flux:label>Nombre</flux:label>
                    <flux:input wire:model="contact_person" />
                    <flux:error name="contact_person" />
                </flux:field>

                <flux:field>
                    <flux:label>Teléfono</flux:label>
                    <flux:input wire:model="contact_phone" icon="phone" />
                    <flux:error name="contact_phone" />
                </flux:field>

                <flux:field>
                    <flux:label>Correo Electrónico</flux:label>
                    <flux:input type="email" wire:model="contact_email" icon="envelope" />
                    <flux:error name="contact_email" />
                </flux:field>
            </div>
        </flux:card>

        <flux:card>
            <flux:heading size="lg">Dirección</flux:heading>
            <flux:separator />

            <div class="space-y-6">
                <flux:field>
                    <flux:label>Dirección</flux:label>
                    <flux:textarea wire:model="address" rows="2" />
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
                        <flux:error name="state" />
                    </flux:field>

                    <flux:field>
                        <flux:label>País</flux:label>
                        <flux:input wire:model="country" />
                        <flux:error name="country" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Código Postal</flux:label>
                        <flux:input wire:model="postal_code" />
                        <flux:error name="postal_code" />
                    </flux:field>
                </div>
            </div>
        </flux:card>

        <flux:card>
            <flux:heading size="lg">Información Adicional</flux:heading>
            <flux:separator />

            <flux:field>
                <flux:label>Notas</flux:label>
                <flux:textarea wire:model="notes" rows="3" />
                <flux:error name="notes" />
            </flux:field>
        </flux:card>

        <flux:card>
            <flux:field>
                <flux:label>Estado</flux:label>
                <flux:checkbox wire:model="is_active">Activo</flux:checkbox>
                <flux:error name="is_active" />
            </flux:field>
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
