<?php

use App\Models\Branch;
use App\Models\Company;
use App\Models\User;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component
{
    #[Validate('required|exists:companies,id')]
    public string $company_id = '';

    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('nullable|string|max:500')]
    public string $description = '';

    #[Validate('required|string|max:50|unique:branches,code')]
    public string $code = '';

    #[Validate('nullable|string|max:255')]
    public string $address = '';

    #[Validate('nullable|string|max:100')]
    public string $city = '';

    #[Validate('nullable|string|max:100')]
    public string $state = '';

    #[Validate('nullable|string|max:100')]
    public string $country = '';

    #[Validate('nullable|string|max:20')]
    public string $postal_code = '';

    #[Validate('nullable|exists:users,id')]
    public string $manager_id = '';

    #[Validate('boolean')]
    public bool $is_active = true;

    // Settings
    public string $type = '';

    public string $opening_time = '08:00';

    public string $closing_time = '18:00';

    public array $operating_days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];

    public bool $has_parking = false;

    public bool $has_security = false;

    public bool $is_24_hours = false;

    public function mount(): void
    {
        $this->authorize('create', Branch::class);

        // Set defaults
        $this->country = config('app.default_country', 'Colombia');
    }

    #[Computed]
    public function companies()
    {
        return Company::active()->orderBy('name')->get();
    }

    #[Computed]
    public function managers()
    {
        if (! $this->company_id) {
            return collect([]);
        }

        return User::where('company_id', $this->company_id)
            ->active()
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function branchTypes()
    {
        return [
            'warehouse' => __('warehouse.branch_type_warehouse'),
            'retail' => __('warehouse.branch_type_retail'),
            'office' => __('warehouse.branch_type_office'),
            'distribution' => __('warehouse.branch_type_distribution'),
            'manufacturing' => __('warehouse.branch_type_manufacturing'),
            'service' => __('warehouse.branch_type_service'),
        ];
    }

    #[Computed]
    public function daysOfWeek()
    {
        return [
            'monday' => __('ui.day_monday'),
            'tuesday' => __('ui.day_tuesday'),
            'wednesday' => __('ui.day_wednesday'),
            'thursday' => __('ui.day_thursday'),
            'friday' => __('ui.day_friday'),
            'saturday' => __('ui.day_saturday'),
            'sunday' => __('ui.day_sunday'),
        ];
    }

    public function updatedName(): void
    {
        if ($this->name && ! $this->code) {
            $this->code = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $this->name), 0, 10));
        }
    }

    public function updatedCompanyId(): void
    {
        $this->manager_id = '';
    }

    public function save(): void
    {
        $this->authorize('create', Branch::class);

        $validated = $this->validate();

        // Prepare settings
        $settings = [
            'type' => $this->type,
            'operating_hours' => [
                'opening_time' => $this->opening_time,
                'closing_time' => $this->closing_time,
                'operating_days' => $this->operating_days,
                'is_24_hours' => $this->is_24_hours,
            ],
            'facilities' => [
                'has_parking' => $this->has_parking,
                'has_security' => $this->has_security,
            ],
        ];

        $validated['settings'] = $settings;
        $validated['created_by'] = auth()->id();

        $branch = Branch::create($validated);

        $this->dispatch('branch-created', [
            'message' => __('warehouse.branch_created_successfully'),
            'branch' => $branch->name,
        ]);

        $this->redirect(route('warehouse.branches.index'), navigate: true);
    }

    public function with(): array
    {
        return [
            'title' => __('warehouse.create_branch'),
        ];
    }
}; ?>

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Page Header -->
    <div class="mb-8">
        <div class="flex items-center gap-4 mb-4">
            <flux:button variant="ghost" icon="arrow-left" :href="route('warehouse.branches.index')" wire:navigate>
                {{ __('ui.back') }}
            </flux:button>
            <div>
                <flux:heading size="xl" class="text-zinc-900 dark:text-zinc-100">
                    {{ __('warehouse.create_new_branch') }}
                </flux:heading>
                <flux:text class="mt-1 text-zinc-600 dark:text-zinc-400">
                    {{ __('warehouse.create_branch_description') }}
                </flux:text>
            </div>
        </div>
    </div>

    <form wire:submit="save" class="space-y-8">
        <!-- Basic Information -->
        <flux:card>
            <div class="mb-6">
                <flux:heading size="lg">{{ __('warehouse.basic_information') }}</flux:heading>
                <flux:text class="mt-1 text-zinc-600 dark:text-zinc-400">
                    {{ __('warehouse.branch_main_data') }}
                </flux:text>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Company Selection -->
                <div class="lg:col-span-2">
                    <flux:field>
                        <flux:label>{{ __('warehouse.company') }} *</flux:label>
                        <flux:select wire:model.live="company_id" :placeholder="__('warehouse.select_company')">
                            @foreach($this->companies as $company)
                                <flux:select.option value="{{ $company->id }}">{{ $company->name }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:error name="company_id" />
                    </flux:field>
                </div>

                <!-- Branch Name -->
                <flux:field>
                    <flux:label>{{ __('warehouse.branch_name') }} *</flux:label>
                    <flux:input wire:model.live="name" :placeholder="__('warehouse.branch_name_placeholder')" />
                    <flux:error name="name" />
                </flux:field>

                <!-- Branch Code -->
                <flux:field>
                    <flux:label>{{ __('ui.code') }} *</flux:label>
                    <flux:input wire:model="code" :placeholder="__('warehouse.code_placeholder')" :description="__('warehouse.unique_code_description')" />
                    <flux:error name="code" />
                </flux:field>

                <!-- Branch Type -->
                <flux:field>
                    <flux:label>{{ __('warehouse.branch_type') }}</flux:label>
                    <flux:select wire:model="type" :placeholder="__('warehouse.select_type')">
                        @foreach($this->branchTypes as $value => $label)
                            <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="type" />
                </flux:field>

                <!-- Manager -->
                <flux:field>
                    <flux:label>{{ __('warehouse.manager') }}</flux:label>
                    <flux:select wire:model="manager_id" :placeholder="__('warehouse.select_manager')" :description="!$company_id ? __('warehouse.select_company_first') : __('warehouse.responsible_manager_description')">
                        @foreach($this->managers as $manager)
                            <flux:select.option value="{{ $manager->id }}">{{ $manager->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="manager_id" />
                </flux:field>

                <!-- Description -->
                <div class="lg:col-span-2">
                    <flux:field>
                        <flux:label>{{ __('ui.description') }}</flux:label>
                        <flux:textarea wire:model="description" rows="3" :placeholder="__('warehouse.branch_description_placeholder')" />
                        <flux:error name="description" />
                    </flux:field>
                </div>
            </div>
        </flux:card>

        <!-- Address Information -->
        <flux:card>
            <div class="mb-6">
                <flux:heading size="lg">{{ __('warehouse.location_information') }}</flux:heading>
                <flux:text class="mt-1 text-zinc-600 dark:text-zinc-400">
                    {{ __('warehouse.address_and_location_data') }}
                </flux:text>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Address -->
                <div class="lg:col-span-2">
                    <flux:field>
                        <flux:label>{{ __('ui.address') }}</flux:label>
                        <flux:input wire:model="address" :placeholder="__('warehouse.address_placeholder')" />
                        <flux:error name="address" />
                    </flux:field>
                </div>

                <!-- City -->
                <flux:field>
                    <flux:label>{{ __('ui.city') }}</flux:label>
                    <flux:input wire:model="city" :placeholder="__('warehouse.city_placeholder')" />
                    <flux:error name="city" />
                </flux:field>

                <!-- State -->
                <flux:field>
                    <flux:label>{{ __('ui.state') }}</flux:label>
                    <flux:input wire:model="state" :placeholder="__('warehouse.state_placeholder')" />
                    <flux:error name="state" />
                </flux:field>

                <!-- Postal Code -->
                <flux:field>
                    <flux:label>{{ __('ui.postal_code') }}</flux:label>
                    <flux:input wire:model="postal_code" :placeholder="__('warehouse.postal_code_placeholder')" />
                    <flux:error name="postal_code" />
                </flux:field>

                <!-- Country -->
                <flux:field>
                    <flux:label>{{ __('ui.country') }}</flux:label>
                    <flux:select wire:model="country" :placeholder="__('warehouse.select_country')">
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

        <!-- Operating Hours -->
        <flux:card>
            <div class="mb-6">
                <flux:heading size="lg">{{ __('warehouse.operating_hours') }}</flux:heading>
                <flux:text class="mt-1 text-zinc-600 dark:text-zinc-400">
                    {{ __('warehouse.configure_operating_hours') }}
                </flux:text>
            </div>

            <div class="space-y-6">
                <!-- 24 Hours Option -->
                <flux:checkbox wire:model.live="is_24_hours" :label="__('warehouse.operates_24_hours')" :description="__('warehouse.operates_24_hours_description')" />

                @if(!$is_24_hours)
                    <!-- Operating Hours -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <flux:field>
                            <flux:label>{{ __('warehouse.opening_time') }}</flux:label>
                            <flux:input type="time" wire:model="opening_time" />
                        </flux:field>

                        <flux:field>
                            <flux:label>{{ __('warehouse.closing_time') }}</flux:label>
                            <flux:input type="time" wire:model="closing_time" />
                        </flux:field>
                    </div>
                @endif

                <!-- Operating Days -->
                <flux:field>
                    <flux:label>{{ __('warehouse.operating_days') }}</flux:label>
                    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mt-2">
                        @foreach($this->daysOfWeek as $day => $label)
                            <flux:checkbox wire:model="operating_days" value="{{ $day }}" :label="$label" />
                        @endforeach
                    </div>
                    <flux:text class="text-sm text-zinc-500 mt-2">{{ __('warehouse.select_operating_days') }}</flux:text>
                </flux:field>
            </div>
        </flux:card>

        <!-- Facilities -->
        <flux:card>
            <div class="mb-6">
                <flux:heading size="lg">{{ __('warehouse.facilities') }}</flux:heading>
                <flux:text class="mt-1 text-zinc-600 dark:text-zinc-400">
                    {{ __('warehouse.facilities_and_services') }}
                </flux:text>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <flux:checkbox wire:model="has_parking" :label="__('warehouse.has_parking')" :description="__('warehouse.has_parking_description')" />

                <flux:checkbox wire:model="has_security" :label="__('warehouse.has_security')" :description="__('warehouse.has_security_description')" />
            </div>
        </flux:card>

        <!-- Status -->
        <flux:card>
            <div class="mb-6">
                <flux:heading size="lg">{{ __('ui.status') }}</flux:heading>
                <flux:text class="mt-1 text-zinc-600 dark:text-zinc-400">
                    {{ __('warehouse.initial_status_configuration') }}
                </flux:text>
            </div>

            <flux:checkbox wire:model="is_active" :label="__('warehouse.active_branch')" :description="__('warehouse.active_branch_description')" />
            <flux:error name="is_active" />
        </flux:card>

        <!-- Form Actions -->
        <div class="flex items-center justify-end gap-4 pt-6 border-t border-zinc-200 dark:border-zinc-700">
            <flux:button variant="ghost" :href="route('warehouse.branches.index')" wire:navigate>
                {{ __('ui.cancel') }}
            </flux:button>
            <flux:button type="submit" variant="primary" icon="check">
                {{ __('warehouse.create_branch') }}
            </flux:button>
        </div>
    </form>
</div>
