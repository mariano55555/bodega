<?php

use App\Models\Branch;
use App\Models\Company;
use App\Models\Inventory;
use App\Models\StorageLocation;
use App\Models\Warehouse;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component
{
    public string $selectedCompany = '';

    public string $selectedBranch = '';

    public string $selectedWarehouse = '';

    public string $view = 'summary'; // summary, warehouses, locations

    public function mount(): void
    {
        $this->authorize('viewAny', Warehouse::class);

        // Set default company for non-super admins
        if (! auth()->user()->hasRole('super-admin') && auth()->user()->company_id) {
            $this->selectedCompany = (string) auth()->user()->company_id;
        }
    }

    #[Computed]
    public function companies()
    {
        if (auth()->user()->hasRole('super-admin')) {
            return Company::active()->get();
        }

        return Company::where('id', auth()->user()->company_id)->active()->get();
    }

    #[Computed]
    public function branches()
    {
        if (! $this->selectedCompany) {
            return collect();
        }

        return Branch::where('company_id', $this->selectedCompany)->active()->get();
    }

    #[Computed]
    public function warehouses()
    {
        $query = Warehouse::query()->active();

        if ($this->selectedCompany) {
            $query->where('company_id', $this->selectedCompany);
        }

        if ($this->selectedBranch) {
            $query->where('branch_id', $this->selectedBranch);
        }

        return $query->with(['company', 'branch'])->get();
    }

    #[Computed]
    public function capacitySummary()
    {
        $warehouses = $this->warehouses;

        return [
            'total_warehouses' => $warehouses->count(),
            'total_capacity' => $warehouses->sum('total_capacity'),
            'avg_capacity' => $warehouses->avg('total_capacity') ?? 0,
            'capacity_units' => $warehouses->pluck('capacity_unit')->unique()->filter()->values(),
        ];
    }

    #[Computed]
    public function warehouseDetails()
    {
        return $this->warehouses->map(function ($warehouse) {
            $storageLocations = StorageLocation::where('warehouse_id', $warehouse->id)
                ->active()
                ->get();

            $locationCapacity = $storageLocations->sum('capacity');
            $locationCount = $storageLocations->count();

            return [
                'warehouse' => $warehouse,
                'storage_locations_count' => $locationCount,
                'storage_capacity' => $locationCapacity,
                'capacity_utilization' => $warehouse->total_capacity > 0
                    ? round(($locationCapacity / $warehouse->total_capacity) * 100, 2)
                    : 0,
                'available_capacity' => max(0, $warehouse->total_capacity - $locationCapacity),
            ];
        });
    }

    #[Computed]
    public function storageLocations()
    {
        if (! $this->selectedWarehouse) {
            return collect();
        }

        return StorageLocation::where('warehouse_id', $this->selectedWarehouse)
            ->active()
            ->with(['warehouse', 'parentLocation', 'capacityUnit'])
            ->withSum(['inventory as total_inventory_quantity' => function ($query) {
                $query->active();
            }], 'quantity')
            ->get()
            ->map(function ($location) {
                $usedQuantity = (float) ($location->total_inventory_quantity ?? 0);
                $totalCapacity = (float) ($location->capacity ?? 0);

                // Calculate utilization percentage
                $utilization = $totalCapacity > 0
                    ? round(($usedQuantity / $totalCapacity) * 100, 1)
                    : 0;

                // Calculate available capacity
                $availableCapacity = max(0, $totalCapacity - $usedQuantity);

                return [
                    'location' => $location,
                    'utilization' => $utilization,
                    'used_quantity' => $usedQuantity,
                    'available_capacity' => $availableCapacity,
                    'available_weight' => $location->available_weight,
                ];
            });
    }

    public function updatedSelectedCompany(): void
    {
        $this->selectedBranch = '';
        $this->selectedWarehouse = '';
    }

    public function updatedSelectedBranch(): void
    {
        $this->selectedWarehouse = '';
    }

    public function setView(string $view): void
    {
        $this->view = $view;
    }

    public function refresh(): void
    {
        // Force refresh of computed properties
        unset($this->companies);
        unset($this->branches);
        unset($this->warehouses);
        unset($this->capacitySummary);
        unset($this->warehouseDetails);
        unset($this->storageLocations);
    }

    public function with(): array
    {
        return [
            'title' => __('warehouse.capacity_management'),
        ];
    }
}; ?>

<div class="px-4 sm:px-6 lg:px-8 py-8">
    <!-- Page Header -->
    <div class="mb-8">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div>
                <flux:heading size="xl" class="text-zinc-900 dark:text-zinc-100">
                    {{ __('warehouse.capacity_management') }}
                </flux:heading>
                <flux:text class="mt-2 text-zinc-600 dark:text-zinc-400">
                    {{ __('warehouse.capacity_management_description') }}
                </flux:text>
            </div>
            <div class="flex items-center gap-3">
                <flux:button variant="outline" icon="arrow-path" wire:click="refresh">
                    {{ __('ui.refresh') }}
                </flux:button>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <flux:card class="mb-6">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Company Filter -->
            <div>
                <flux:field>
                    <flux:label>{{ __('warehouse.company') }}</flux:label>
                    <flux:select wire:model.live="selectedCompany" placeholder="{{ __('ui.select') }} {{ strtolower(__('warehouse.company')) }}">
                        @foreach($this->companies as $company)
                            <flux:select.option value="{{ $company->id }}">{{ $company->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </flux:field>
            </div>

            <!-- Branch Filter -->
            <div>
                <flux:field>
                    <flux:label>{{ __('warehouse.branch') }}</flux:label>
                    <flux:select wire:model.live="selectedBranch" placeholder="{{ __('ui.select') }} {{ strtolower(__('warehouse.branch')) }}" :disabled="!$selectedCompany">
                        @foreach($this->branches as $branch)
                            <flux:select.option value="{{ $branch->id }}">{{ $branch->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </flux:field>
            </div>

            <!-- Warehouse Filter -->
            <div>
                <flux:field>
                    <flux:label>{{ __('warehouse.warehouse') }}</flux:label>
                    <flux:select wire:model.live="selectedWarehouse" placeholder="{{ __('ui.select') }} {{ strtolower(__('warehouse.warehouse')) }}">
                        @foreach($this->warehouses as $warehouse)
                            <flux:select.option value="{{ $warehouse->id }}">{{ $warehouse->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </flux:field>
            </div>

            <!-- View Toggle -->
            <div>
                <flux:field>
                    <flux:label>{{ __('ui.view') }}</flux:label>
                    <flux:select wire:model.live="view">
                        <flux:select.option value="summary">{{ __('warehouse.summary') }}</flux:select.option>
                        <flux:select.option value="warehouses">{{ __('warehouse.warehouses') }}</flux:select.option>
                        <flux:select.option value="locations">{{ __('warehouse.storage_locations') }}</flux:select.option>
                    </flux:select>
                </flux:field>
            </div>
        </div>
    </flux:card>

    <!-- Summary View -->
    @if($view === 'summary')
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <flux:card>
                <div class="text-center">
                    <flux:heading size="2xl" class="text-blue-600 dark:text-blue-400">
                        {{ $this->capacitySummary['total_warehouses'] }}
                    </flux:heading>
                    <flux:text class="text-zinc-600 dark:text-zinc-400">
                        {{ __('warehouse.total_warehouses') }}
                    </flux:text>
                </div>
            </flux:card>

            <flux:card>
                <div class="text-center">
                    <flux:heading size="2xl" class="text-green-600 dark:text-green-400">
                        {{ number_format($this->capacitySummary['total_capacity'], 2) }}
                    </flux:heading>
                    <flux:text class="text-zinc-600 dark:text-zinc-400">
                        {{ __('warehouse.total_capacity') }}
                    </flux:text>
                </div>
            </flux:card>

            <flux:card>
                <div class="text-center">
                    <flux:heading size="2xl" class="text-purple-600 dark:text-purple-400">
                        {{ number_format($this->capacitySummary['avg_capacity'], 2) }}
                    </flux:heading>
                    <flux:text class="text-zinc-600 dark:text-zinc-400">
                        {{ __('warehouse.avg_capacity') }}
                    </flux:text>
                </div>
            </flux:card>

            <flux:card>
                <div class="text-center">
                    <flux:heading size="2xl" class="text-orange-600 dark:text-orange-400">
                        {{ $this->capacitySummary['capacity_units']->implode(', ') ?: 'N/A' }}
                    </flux:heading>
                    <flux:text class="text-zinc-600 dark:text-zinc-400">
                        {{ __('warehouse.capacity_units') }}
                    </flux:text>
                </div>
            </flux:card>
        </div>
    @endif

    <!-- Warehouses View -->
    @if($view === 'warehouses')
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            @foreach($this->warehouseDetails as $detail)
                <flux:card>
                    <flux:heading>
                        <div class="flex items-start justify-between">
                            <div>
                                <flux:heading size="lg">{{ $detail['warehouse']->name }}</flux:heading>
                                <flux:text class="text-sm text-zinc-500">
                                    {{ $detail['warehouse']->company->name }} → {{ $detail['warehouse']->branch->name }}
                                </flux:text>
                            </div>
                            <flux:badge :color="$detail['capacity_utilization'] > 80 ? 'red' : ($detail['capacity_utilization'] > 60 ? 'yellow' : 'green')">
                                {{ $detail['capacity_utilization'] }}% {{ __('warehouse.utilized') }}
                            </flux:badge>
                        </div>
                    </flux:heading>

                    <div class="space-y-4">
                        <!-- Capacity Bar -->
                        <div>
                            <div class="flex justify-between text-sm mb-2">
                                <span>{{ __('warehouse.capacity') }}</span>
                                <span>{{ number_format($detail['storage_capacity'], 2) }} / {{ number_format($detail['warehouse']->total_capacity, 2) }} {{ $detail['warehouse']->capacity_unit }}</span>
                            </div>
                            <div class="w-full bg-zinc-200 rounded-full h-2 dark:bg-zinc-700">
                                <div class="bg-blue-600 h-2 rounded-full" style="width: {{ min($detail['capacity_utilization'], 100) }}%"></div>
                            </div>
                        </div>

                        <!-- Stats Grid -->
                        <div class="grid grid-cols-2 gap-4 pt-4 border-t border-zinc-200 dark:border-zinc-700">
                            <div>
                                <flux:heading size="lg" class="text-blue-600 dark:text-blue-400">
                                    {{ $detail['storage_locations_count'] }}
                                </flux:heading>
                                <flux:text class="text-xs">{{ __('warehouse.storage_locations') }}</flux:text>
                            </div>
                            <div>
                                <flux:heading size="lg" class="text-green-600 dark:text-green-400">
                                    {{ number_format($detail['available_capacity'], 2) }}
                                </flux:heading>
                                <flux:text class="text-xs">{{ __('warehouse.available') }}</flux:text>
                            </div>
                        </div>
                    </div>
                </flux:card>
            @endforeach
        </div>
    @endif

    <!-- Storage Locations View -->
    @if($view === 'locations' && $selectedWarehouse)
        <div class="space-y-4">
            @foreach($this->storageLocations as $locationData)
                @php $location = $locationData['location']; @endphp
                <flux:card>
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <div class="flex items-center gap-3">
                                <a href="{{ route('storage-locations.show', $location->slug) }}" wire:navigate class="hover:underline">
                                    <flux:heading size="md">{{ $location->name }}</flux:heading>
                                </a>
                                <a href="{{ route('storage-locations.show', $location->slug) }}" wire:navigate class="hover:opacity-80">
                                    <flux:badge variant="outline">{{ $location->code }}</flux:badge>
                                </a>
                                @php
                                    $typeLabels = [
                                        'zone' => 'Zona',
                                        'aisle' => 'Pasillo',
                                        'shelf' => 'Estante',
                                        'bin' => 'Contenedor',
                                        'dock' => 'Muelle',
                                        'staging' => 'Preparación',
                                    ];
                                    $typeColors = [
                                        'zone' => 'purple',
                                        'aisle' => 'blue',
                                        'shelf' => 'green',
                                        'bin' => 'yellow',
                                        'dock' => 'orange',
                                        'staging' => 'cyan',
                                    ];
                                @endphp
                                <flux:badge :color="$typeColors[$location->type] ?? 'zinc'">
                                    {{ $typeLabels[$location->type] ?? ucfirst($location->type) }}
                                </flux:badge>
                            </div>

                            @if($location->description)
                                <flux:text class="text-sm text-zinc-500 mt-1">{{ $location->description }}</flux:text>
                            @endif

                            <div class="flex items-center gap-6 mt-3">
                                @if($location->capacity)
                                    <div class="flex items-center gap-2">
                                        <flux:icon name="cube" class="h-4 w-4 text-zinc-400" />
                                        <span class="text-sm">
                                            {{ number_format($locationData['used_quantity'], 2) }} / {{ number_format($location->capacity, 2) }} {{ $location->capacityUnit?->abbreviation ?? '' }}
                                        </span>
                                    </div>
                                @else
                                    <div class="flex items-center gap-2">
                                        <flux:icon name="cube" class="h-4 w-4 text-zinc-400" />
                                        <span class="text-sm text-zinc-400">{{ __('warehouse.no_capacity_defined') }}</span>
                                    </div>
                                @endif

                                @if($location->weight_limit)
                                    <div class="flex items-center gap-2">
                                        <flux:icon name="scale" class="h-4 w-4 text-zinc-400" />
                                        <span class="text-sm">{{ number_format($location->weight_limit, 2) }} kg</span>
                                    </div>
                                @endif

                                @if($location->parentLocation)
                                    <div class="flex items-center gap-2">
                                        <flux:icon name="map-pin" class="h-4 w-4 text-zinc-400" />
                                        <span class="text-sm">{{ $location->parentLocation->name }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="text-right">
                            <flux:badge :color="$locationData['utilization'] > 80 ? 'red' : ($locationData['utilization'] > 60 ? 'yellow' : 'green')">
                                {{ $locationData['utilization'] }}% {{ __('warehouse.utilized') }}
                            </flux:badge>

                            <div class="mt-2">
                                <div class="flex gap-2">
                                    @if($location->is_pickable)
                                        <flux:badge color="green" size="sm">{{ __('warehouse.pickable') }}</flux:badge>
                                    @endif
                                    @if($location->is_receivable)
                                        <flux:badge color="blue" size="sm">{{ __('warehouse.receivable') }}</flux:badge>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </flux:card>
            @endforeach
        </div>
    @elseif($view === 'locations' && !$selectedWarehouse)
        <flux:card class="text-center py-12">
            <flux:icon name="cube-transparent" class="h-16 w-16 text-zinc-400 mx-auto mb-4" />
            <flux:heading size="lg" class="mb-2">{{ __('warehouse.select_warehouse') }}</flux:heading>
            <flux:text class="text-zinc-500">{{ __('warehouse.select_warehouse_description') }}</flux:text>
        </flux:card>
    @endif

    <!-- Empty State -->
    @if($this->warehouses->isEmpty() && $selectedCompany)
        <flux:card class="text-center py-12">
            <flux:icon name="building-storefront" class="h-16 w-16 text-zinc-400 mx-auto mb-4" />
            <flux:heading size="lg" class="mb-2">{{ __('warehouse.no_warehouses') }}</flux:heading>
            <flux:text class="text-zinc-500">{{ __('warehouse.no_warehouses_description') }}</flux:text>
        </flux:card>
    @endif
</div>
