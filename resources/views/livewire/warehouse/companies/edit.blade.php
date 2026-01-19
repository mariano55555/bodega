<?php

use App\Models\Company;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component
{
    public Company $company;

    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('nullable|string|max:255')]
    public ?string $legal_name = null;

    #[Validate('required|string|max:50')]
    public string $tax_id = '';

    #[Validate('nullable|email|max:255')]
    public ?string $email = null;

    #[Validate('nullable|string|max:20')]
    public ?string $phone = null;

    #[Validate('nullable|url|max:255')]
    public ?string $website = null;

    #[Validate('nullable|string|max:255')]
    public ?string $address = null;

    #[Validate('nullable|string|max:100')]
    public ?string $city = null;

    #[Validate('nullable|string|max:100')]
    public ?string $state = null;

    #[Validate('nullable|string|max:20')]
    public ?string $postal_code = null;

    #[Validate('nullable|string|max:100')]
    public ?string $country = null;

    #[Validate('nullable|string|max:3')]
    public ?string $default_currency = 'EUR';

    #[Validate('nullable|string|max:50')]
    public ?string $timezone = null;

    #[Validate('boolean')]
    public bool $is_active = true;

    public function mount(Company $company): void
    {
        $this->authorize('update', $company);

        $this->company = $company;
        $this->fill($company->only([
            'name', 'legal_name', 'tax_id', 'email',
            'phone', 'website', 'address', 'city', 'state', 'postal_code',
            'country', 'default_currency', 'timezone', 'is_active',
        ]));
    }

    public function save(): void
    {
        $this->authorize('update', $this->company);

        // Validate #[Validate] attributes first
        $this->validate();

        // Additional validation for unique fields
        $this->validate([
            'tax_id' => 'required|string|max:50|unique:companies,tax_id,'.$this->company->id,
            'email' => 'nullable|email|max:255|unique:companies,email,'.$this->company->id,
        ]);

        try {
            $this->company->update([
                'name' => $this->name,
                'legal_name' => $this->legal_name,
                'tax_id' => $this->tax_id,
                'email' => $this->email,
                'phone' => $this->phone,
                'website' => $this->website,
                'address' => $this->address,
                'city' => $this->city,
                'state' => $this->state,
                'postal_code' => $this->postal_code,
                'country' => $this->country,
                'default_currency' => $this->default_currency,
                'timezone' => $this->timezone,
                'is_active' => $this->is_active,
                'updated_by' => auth()->id(),
            ]);

            Flux::toast(
                text: "Empresa '{$this->company->name}' actualizada exitosamente",
                variant: 'success',
                duration: 3000
            );

            $this->redirect(route('warehouse.companies.index'), navigate: true);
        } catch (\Exception $e) {
            Flux::toast(
                text: 'Error al actualizar la empresa. Por favor intente nuevamente.',
                variant: 'danger',
                duration: 5000
            );

            \Log::error('Error updating company: '.$e->getMessage());
        }
    }

    public function with(): array
    {
        return [
            'title' => __('warehouse.edit_company').': '.$this->company->name,
        ];
    }
}; ?>

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Page Header -->
    <div class="mb-8">
        <div class="flex items-center gap-4 mb-4">
            <flux:button variant="ghost" icon="arrow-left" :href="route('warehouse.companies.index')" wire:navigate>
                {{ __('ui.back') }}
            </flux:button>
            <div class="flex-1">
                <flux:heading size="xl" class="text-zinc-900 dark:text-zinc-100">
                    {{ __('warehouse.edit_company') }}
                </flux:heading>
                <flux:text class="mt-1 text-zinc-600 dark:text-zinc-400">
                    {{ __('warehouse.edit_company_description') }}: {{ $company->name }}
                </flux:text>
            </div>
            <div class="flex items-center gap-2">
                <flux:badge :color="$company->is_active ? 'green' : 'red'">
                    {{ $company->is_active ? __('ui.active') : __('ui.inactive') }}
                </flux:badge>
                <flux:text class="text-sm text-zinc-500">
                    {{ __('ui.created_at') }}: {{ $company->created_at->format('M d, Y') }}
                </flux:text>
            </div>
        </div>
    </div>

    <!-- Company Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
        <flux:card class="bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-800">
            <div class="flex items-center gap-3">
                <flux:icon name="building-storefront" class="h-8 w-8 text-blue-500" />
                <div>
                    <flux:heading size="lg" class="text-blue-900 dark:text-blue-100">
                        {{ $company->branches()->count() }}
                    </flux:heading>
                    <flux:text class="text-sm text-blue-600 dark:text-blue-400">
                        {{ __('warehouse.branches') }}
                    </flux:text>
                </div>
            </div>
        </flux:card>

        <flux:card class="bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800">
            <div class="flex items-center gap-3">
                <flux:icon name="building-office" class="h-8 w-8 text-green-500" />
                <div>
                    <flux:heading size="lg" class="text-green-900 dark:text-green-100">
                        {{ $company->warehouses()->count() }}
                    </flux:heading>
                    <flux:text class="text-sm text-green-600 dark:text-green-400">
                        {{ __('warehouse.warehouses') }}
                    </flux:text>
                </div>
            </div>
        </flux:card>

        <flux:card class="bg-purple-50 dark:bg-purple-900/20 border-purple-200 dark:border-purple-800">
            <div class="flex items-center gap-3">
                <flux:icon name="users" class="h-8 w-8 text-purple-500" />
                <div>
                    <flux:heading size="lg" class="text-purple-900 dark:text-purple-100">
                        {{ $company->users()->count() }}
                    </flux:heading>
                    <flux:text class="text-sm text-purple-600 dark:text-purple-400">
                        {{ __('warehouse.users') }}
                    </flux:text>
                </div>
            </div>
        </flux:card>
    </div>

    <form wire:submit="save" class="space-y-8">
        <!-- Basic Information -->
        <flux:card>
            <flux:heading>
                <flux:heading size="lg">{{ __('warehouse.company_information') }}</flux:heading>
                <flux:text class="text-zinc-600 dark:text-zinc-400">
                    {{ __('warehouse.basic_company_info') }}
                </flux:text>
            </flux:heading>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Company Name -->
                <div class="lg:col-span-2">
                    <flux:field>
                        <flux:label>{{ __('warehouse.company_name') }} *</flux:label>
                        <flux:input wire:model="name" placeholder="{{ __('warehouse.enter_company_name') }}" />
                        <flux:error name="name" />
                    </flux:field>
                </div>

                <!-- Legal Name -->
                <flux:field>
                    <flux:label>{{ __('warehouse.legal_name') }}</flux:label>
                    <flux:input wire:model="legal_name" placeholder="{{ __('warehouse.enter_legal_name') }}" />
                    <flux:error name="legal_name" />
                    <flux:description>Nombre legal registrado de la empresa</flux:description>
                </flux:field>

                <!-- Tax ID -->
                <flux:field>
                    <flux:label>{{ __('warehouse.tax_id') }} *</flux:label>
                    <flux:input wire:model="tax_id" placeholder="{{ __('warehouse.enter_tax_id') }}" />
                    <flux:error name="tax_id" />
                </flux:field>

                <!-- Email -->
                <flux:field>
                    <flux:label>{{ __('ui.email') }}</flux:label>
                    <flux:input type="email" wire:model="email" placeholder="{{ __('warehouse.enter_company_email') }}" />
                    <flux:error name="email" />
                </flux:field>

                <!-- Phone -->
                <flux:field>
                    <flux:label>{{ __('ui.phone') }}</flux:label>
                    <flux:input wire:model="phone" placeholder="{{ __('warehouse.enter_company_phone') }}" />
                    <flux:error name="phone" />
                </flux:field>

                <!-- Website -->
                <div class="lg:col-span-2">
                    <flux:field>
                        <flux:label>{{ __('warehouse.website') }}</flux:label>
                        <flux:input wire:model="website" placeholder="https://example.com" />
                        <flux:error name="website" />
                    </flux:field>
                </div>
            </div>
        </flux:card>

        <!-- Address Information -->
        <flux:card>
            <flux:heading>
                <flux:heading size="lg">{{ __('warehouse.address_information') }}</flux:heading>
                <flux:text class="text-zinc-600 dark:text-zinc-400">
                    {{ __('warehouse.company_location_details') }}
                </flux:text>
            </flux:heading>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Address -->
                <div class="lg:col-span-2">
                    <flux:field>
                        <flux:label>{{ __('warehouse.address') }}</flux:label>
                        <flux:input wire:model="address" placeholder="{{ __('warehouse.enter_address') }}" />
                        <flux:error name="address" />
                    </flux:field>
                </div>

                <!-- City -->
                <flux:field>
                    <flux:label>{{ __('warehouse.city') }}</flux:label>
                    <flux:input wire:model="city" placeholder="{{ __('warehouse.enter_city') }}" />
                    <flux:error name="city" />
                </flux:field>

                <!-- State -->
                <flux:field>
                    <flux:label>{{ __('warehouse.state') }}</flux:label>
                    <flux:input wire:model="state" placeholder="{{ __('warehouse.enter_state') }}" />
                    <flux:error name="state" />
                </flux:field>

                <!-- Postal Code -->
                <flux:field>
                    <flux:label>{{ __('warehouse.postal_code') }}</flux:label>
                    <flux:input wire:model="postal_code" placeholder="{{ __('warehouse.enter_postal_code') }}" />
                    <flux:error name="postal_code" />
                </flux:field>

                <!-- Country -->
                <flux:field>
                    <flux:label>{{ __('warehouse.country') }}</flux:label>
                    <flux:select wire:model="country" placeholder="{{ __('warehouse.select_country') }}">
                        <flux:select.option value="Colombia">Colombia</flux:select.option>
                        <flux:select.option value="Venezuela">Venezuela</flux:select.option>
                        <flux:select.option value="Ecuador">Ecuador</flux:select.option>
                        <flux:select.option value="Perú">Perú</flux:select.option>
                        <flux:select.option value="Chile">Chile</flux:select.option>
                        <flux:select.option value="Argentina">Argentina</flux:select.option>
                        <flux:select.option value="México">México</flux:select.option>
                        <flux:select.option value="España">España</flux:select.option>
                        <flux:select.option value="Estados Unidos">Estados Unidos</flux:select.option>
                    </flux:select>
                    <flux:error name="country" />
                </flux:field>
            </div>
        </flux:card>

        <!-- Configuration -->
        <flux:card>
            <flux:heading>
                <flux:heading size="lg">{{ __('warehouse.configuration') }}</flux:heading>
                <flux:text class="text-zinc-600 dark:text-zinc-400">
                    {{ __('warehouse.company_settings') }}
                </flux:text>
            </flux:heading>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Currency -->
                <flux:field>
                    <flux:label>{{ __('warehouse.currency') }}</flux:label>
                    <flux:select wire:model="default_currency" placeholder="{{ __('warehouse.select_currency') }}">
                        <flux:select.option value="COP">COP - Peso Colombiano</flux:select.option>
                        <flux:select.option value="USD">USD - Dólar Estadounidense</flux:select.option>
                        <flux:select.option value="EUR">EUR - Euro</flux:select.option>
                        <flux:select.option value="VES">VES - Bolívar Venezolano</flux:select.option>
                        <flux:select.option value="PEN">PEN - Sol Peruano</flux:select.option>
                        <flux:select.option value="CLP">CLP - Peso Chileno</flux:select.option>
                        <flux:select.option value="ARS">ARS - Peso Argentino</flux:select.option>
                        <flux:select.option value="MXN">MXN - Peso Mexicano</flux:select.option>
                    </flux:select>
                    <flux:error name="default_currency" />
                </flux:field>

                <!-- Timezone -->
                <flux:field>
                    <flux:label>{{ __('warehouse.timezone') }}</flux:label>
                    <flux:select wire:model="timezone" placeholder="{{ __('warehouse.select_timezone') }}">
                        <flux:select.option value="America/Bogota">America/Bogota (COT)</flux:select.option>
                        <flux:select.option value="America/Caracas">America/Caracas (VET)</flux:select.option>
                        <flux:select.option value="America/Guayaquil">America/Guayaquil (ECT)</flux:select.option>
                        <flux:select.option value="America/Lima">America/Lima (PET)</flux:select.option>
                        <flux:select.option value="America/Santiago">America/Santiago (CLT)</flux:select.option>
                        <flux:select.option value="America/Argentina/Buenos_Aires">America/Argentina/Buenos_Aires (ART)</flux:select.option>
                        <flux:select.option value="America/Mexico_City">America/Mexico_City (CST)</flux:select.option>
                        <flux:select.option value="Europe/Madrid">Europe/Madrid (CET)</flux:select.option>
                        <flux:select.option value="America/New_York">America/New_York (EST)</flux:select.option>
                    </flux:select>
                    <flux:error name="timezone" />
                </flux:field>

                <!-- Status -->
                <div class="lg:col-span-2">
                    <flux:checkbox wire:model="is_active" :label="__('warehouse.company_active')" :description="__('warehouse.active_company_description')" />
                    <flux:error name="is_active" />
                </div>
            </div>
        </flux:card>

        <!-- Form Actions -->
        <div class="flex items-center justify-end gap-4 pt-6 border-t border-zinc-200 dark:border-zinc-700">
            <flux:button variant="ghost" :href="route('warehouse.companies.index')" wire:navigate>
                {{ __('ui.cancel') }}
            </flux:button>
            <flux:button type="submit" variant="primary" icon="check">
                {{ __('warehouse.update_company') }}
            </flux:button>
        </div>
    </form>
</div>
