<?php

use App\Models\Company;
use App\Models\User;
use App\Models\Warehouse;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component
{
    public ?Company $currentCompany = null;

    public function mount(): void
    {
        // Get current user's company (multi-tenant support)
        $this->currentCompany = auth()->user()->company ?? Company::first();
    }

    #[Computed]
    public function totalCompanies(): int
    {
        // Super admin can see all companies, otherwise only current company
        return auth()->user()->can('viewAny', Company::class)
            ? Company::active()->count()
            : 1;
    }

    #[Computed]
    public function totalBranches(): int
    {
        return $this->currentCompany
            ? $this->currentCompany->branches()->active()->count()
            : 0;
    }

    #[Computed]
    public function totalWarehouses(): int
    {
        return $this->currentCompany
            ? $this->currentCompany->warehouses()->active()->count()
            : 0;
    }

    #[Computed]
    public function totalCapacity(): float
    {
        return $this->currentCompany
            ? $this->currentCompany->warehouses()->active()->sum('total_capacity')
            : 0;
    }

    #[Computed]
    public function usedCapacity(): float
    {
        if (! $this->currentCompany) {
            return 0;
        }

        // Calculate used capacity from storage locations
        $warehouseIds = $this->currentCompany->warehouses()->active()->pluck('id');

        return \App\Models\StorageLocation::whereIn('warehouse_id', $warehouseIds)
            ->active()
            ->sum('capacity') ?: 0;
    }

    #[Computed]
    public function capacityUtilization(): float
    {
        return $this->totalCapacity > 0
            ? ($this->usedCapacity / $this->totalCapacity) * 100
            : 0;
    }

    #[Computed]
    public function branchesByLocation()
    {
        if (! $this->currentCompany) {
            return collect();
        }

        return $this->currentCompany->branches()
            ->active()
            ->withCount('warehouses')
            ->get()
            ->map(function ($branch) {
                return (object) [
                    'id' => $branch->id,
                    'name' => $branch->name,
                    'code' => $branch->code,
                    'location' => $branch->city.', '.$branch->state,
                    'type' => $branch->type,
                    'is_main' => $branch->is_main_branch,
                    'warehouses_count' => $branch->warehouses_count,
                    'manager' => $branch->manager_name,
                ];
            });
    }

    #[Computed]
    public function warehousesByCapacity()
    {
        if (! $this->currentCompany) {
            return collect();
        }

        return $this->currentCompany->warehouses()
            ->active()
            ->with('branch')
            ->withCount('storageLocations')
            ->orderByDesc('total_capacity')
            ->limit(10)
            ->get()
            ->map(function ($warehouse) {
                // Calculate used capacity from storage locations for this warehouse
                $usedCapacity = \App\Models\StorageLocation::where('warehouse_id', $warehouse->id)
                    ->active()
                    ->sum('capacity') ?: 0;

                $utilization = $warehouse->total_capacity > 0
                    ? ($usedCapacity / $warehouse->total_capacity) * 100
                    : 0;

                return (object) [
                    'id' => $warehouse->id,
                    'name' => $warehouse->name,
                    'code' => $warehouse->code,
                    'branch_name' => $warehouse->branch->name ?? __('ui.not_assigned'),
                    'location' => $warehouse->city,
                    'total_capacity' => $warehouse->total_capacity,
                    'used_capacity' => round($usedCapacity, 2),
                    'utilization' => round(min($utilization, 100), 1),
                    'capacity_unit' => $warehouse->capacity_unit ?? 'm³',
                    'status_color' => $utilization >= 90 ? 'red' : ($utilization >= 75 ? 'yellow' : 'green'),
                    'storage_locations_count' => $warehouse->storage_locations_count,
                ];
            });
    }

    #[Computed]
    public function recentActivities()
    {
        if (! $this->currentCompany) {
            return collect();
        }

        $warehouseIds = $this->currentCompany->warehouses()->pluck('id');

        return \App\Models\InventoryMovement::whereIn('warehouse_id', $warehouseIds)
            ->with(['product', 'warehouse', 'creator'])
            ->latest()
            ->limit(5)
            ->get()
            ->map(function ($movement) {
                $iconMap = [
                    'in' => 'arrow-down-circle',
                    'out' => 'arrow-up-circle',
                    'transfer' => 'arrows-right-left',
                    'transfer_in' => 'arrow-down-circle',
                    'transfer_out' => 'arrow-up-circle',
                    'adjustment' => 'adjustments-horizontal',
                    'receipt' => 'clipboard-document-check',
                    'purchase' => 'shopping-cart',
                    'sale' => 'currency-dollar',
                    'shipment' => 'truck',
                    'return_customer' => 'arrow-uturn-left',
                    'return_supplier' => 'arrow-uturn-right',
                    'expiry' => 'clock',
                    'damage' => 'exclamation-triangle',
                    'loss' => 'minus-circle',
                    'donation_in' => 'gift',
                    'donation_out' => 'gift',
                ];

                $colorMap = [
                    'in' => 'green',
                    'out' => 'red',
                    'transfer' => 'blue',
                    'transfer_in' => 'blue',
                    'transfer_out' => 'blue',
                    'adjustment' => 'yellow',
                    'receipt' => 'green',
                    'purchase' => 'green',
                    'sale' => 'red',
                    'shipment' => 'red',
                    'return_customer' => 'purple',
                    'return_supplier' => 'orange',
                    'expiry' => 'red',
                    'damage' => 'red',
                    'loss' => 'red',
                    'donation_in' => 'green',
                    'donation_out' => 'purple',
                ];

                $typeLabels = [
                    'in' => 'Entrada',
                    'out' => 'Salida',
                    'transfer' => 'Transferencia',
                    'transfer_in' => 'Transferencia Entrada',
                    'transfer_out' => 'Transferencia Salida',
                    'adjustment' => 'Ajuste',
                    'receipt' => 'Recepción',
                    'purchase' => 'Compra',
                    'sale' => 'Venta',
                    'shipment' => 'Envío',
                    'return_customer' => 'Devolución Cliente',
                    'return_supplier' => 'Devolución Proveedor',
                    'expiry' => 'Vencimiento',
                    'damage' => 'Daño',
                    'loss' => 'Pérdida',
                    'donation_in' => 'Donación Recibida',
                    'donation_out' => 'Donación Entregada',
                ];

                $productName = $movement->product?->name ?? __('ui.unknown');
                $warehouseName = $movement->warehouse?->name ?? __('ui.unknown');
                $quantity = number_format($movement->quantity, 2);
                $typeLabel = $typeLabels[$movement->movement_type] ?? $movement->movement_type;

                return (object) [
                    'id' => $movement->id,
                    'type' => $movement->movement_type,
                    'message' => "{$typeLabel}: {$quantity} {$productName} en {$warehouseName}",
                    'user' => $movement->creator?->name ?? __('ui.system'),
                    'timestamp' => $movement->created_at,
                    'icon' => $iconMap[$movement->movement_type] ?? 'document',
                    'color' => $colorMap[$movement->movement_type] ?? 'zinc',
                ];
            });
    }

    public function switchCompany(Company $company): void
    {
        $this->authorize('view', $company);
        $this->currentCompany = $company;
        $this->dispatch('company-switched', companyId: $company->id);
    }

    public function with(): array
    {
        return [
            'title' => __('warehouse.warehouse_management'),
        ];
    }
}; ?>

<div class="px-4 sm:px-6 lg:px-8 py-8">
    <!-- Page Header with Company Selector and Quick Actions -->
    <div class="mb-8">
        <div class="flex flex-col gap-4">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div>
                    <flux:heading size="xl" class="text-zinc-900 dark:text-zinc-100">
                        {{ __('warehouse.warehouse_management') }}
                    </flux:heading>
                    <flux:text class="mt-2 text-zinc-600 dark:text-zinc-400">
                        {{ __('warehouse.overview') }} - {{ $currentCompany?->name ?? __('warehouse.no_companies') }}
                    </flux:text>
                </div>

                @can('viewAny', App\Models\Company::class)
                    <div class="flex items-center gap-4">
                        <flux:select wire:model.live="currentCompany.id" placeholder="{{ __('warehouse.switch_company') }}">
                            @foreach(Company::active()->get() as $company)
                                <flux:select.option value="{{ $company->id }}">{{ $company->name }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    </div>
                @endcan
            </div>

            <!-- Quick Actions -->
            <div class="flex flex-wrap gap-3">
                <flux:button variant="primary" icon="plus" :href="route('warehouse.companies.create')" wire:navigate>
                    {{ __('warehouse.add_company') }}
                </flux:button>
                <flux:button variant="outline" icon="building-storefront" :href="route('warehouse.branches.create')" wire:navigate>
                    {{ __('warehouse.add_branch') }}
                </flux:button>
                <flux:button variant="outline" icon="building-office" :href="route('warehouse.warehouses.create')" wire:navigate>
                    {{ __('warehouse.add_warehouse') }}
                </flux:button>
                <flux:button variant="outline" icon="chart-pie" :href="route('warehouse.capacity.index')" wire:navigate>
                    {{ __('warehouse.capacity_management') }}
                </flux:button>
            </div>
        </div>
    </div>

    <!-- Key Metrics Dashboard -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Total Companies -->
        @can('viewAny', App\Models\Company::class)
            <flux:card class="bg-gradient-to-br from-indigo-50 to-indigo-100 dark:from-indigo-900/20 dark:to-indigo-800/20 border-indigo-200 dark:border-indigo-800">
                <div class="flex items-center justify-between">
                    <div>
                        <flux:text class="text-sm font-medium text-indigo-600 dark:text-indigo-400">
                            {{ __('warehouse.companies') }}
                        </flux:text>
                        <flux:heading size="2xl" class="text-indigo-900 dark:text-indigo-100">
                            {{ number_format($this->totalCompanies) }}
                        </flux:heading>
                    </div>
                    <flux:icon name="building-office-2" class="h-8 w-8 text-indigo-500" />
                </div>
            </flux:card>
        @endcan

        <!-- Total Branches -->
        <flux:card class="bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20 border-blue-200 dark:border-blue-800">
            <div class="flex items-center justify-between">
                <div>
                    <flux:text class="text-sm font-medium text-blue-600 dark:text-blue-400">
                        {{ __('warehouse.branches') }}
                    </flux:text>
                    <flux:heading size="2xl" class="text-blue-900 dark:text-blue-100">
                        {{ number_format($this->totalBranches) }}
                    </flux:heading>
                </div>
                <flux:icon name="building-storefront" class="h-8 w-8 text-blue-500" />
            </div>
        </flux:card>

        <!-- Total Warehouses -->
        <flux:card class="bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-800/20 border-green-200 dark:border-green-800">
            <div class="flex items-center justify-between">
                <div>
                    <flux:text class="text-sm font-medium text-green-600 dark:text-green-400">
                        {{ __('warehouse.warehouses') }}
                    </flux:text>
                    <flux:heading size="2xl" class="text-green-900 dark:text-green-100">
                        {{ number_format($this->totalWarehouses) }}
                    </flux:heading>
                </div>
                <flux:icon name="building-office" class="h-8 w-8 text-green-500" />
            </div>
        </flux:card>

        <!-- Capacity Utilization -->
        <flux:card class="bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-900/20 dark:to-purple-800/20 border-purple-200 dark:border-purple-800">
            <div class="flex items-center justify-between">
                <div>
                    <flux:text class="text-sm font-medium text-purple-600 dark:text-purple-400">
                        {{ __('warehouse.capacity_utilization') }}
                    </flux:text>
                    <flux:heading size="2xl" class="text-purple-900 dark:text-purple-100">
                        {{ number_format($this->capacityUtilization, 1) }}%
                    </flux:heading>
                </div>
                <flux:icon name="chart-pie" class="h-8 w-8 text-purple-500" />
            </div>
        </flux:card>
    </div>

    <!-- Dashboard Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Branches Overview -->
        <flux:card class="lg:col-span-2">
            <flux:heading>
                <div class="flex items-center justify-between">
                    <div>
                        <flux:heading size="lg">{{ __('warehouse.branch_overview') }}</flux:heading>
                        <flux:text class="text-zinc-600 dark:text-zinc-400">
                            {{ __('warehouse.branches') }} {{ __('ui.and') }} {{ __('warehouse.warehouses') }} {{ __('ui.by') }} {{ __('warehouse.location') }}
                        </flux:text>
                    </div>
                    <flux:button variant="outline" size="sm" :href="route('warehouse.branches.index')" wire:navigate>
                        {{ __('ui.view_all') }}
                    </flux:button>
                </div>
            </flux:heading>

            <div class="space-y-4">
                @forelse($this->branchesByLocation as $branch)
                    <div class="flex items-center justify-between p-4 bg-zinc-50 dark:bg-zinc-800 rounded-lg">
                        <div class="flex items-center gap-3">
                            <div class="flex-shrink-0">
                                @if($branch->is_main)
                                    <flux:icon name="star" class="h-5 w-5 text-yellow-500" />
                                @else
                                    <flux:icon name="building-storefront" class="h-5 w-5 text-zinc-500" />
                                @endif
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2">
                                    <flux:text class="font-medium">{{ $branch->name }}</flux:text>
                                    <flux:badge color="zinc" size="sm">{{ $branch->code }}</flux:badge>
                                    @if($branch->is_main)
                                        <flux:badge color="yellow" size="sm">{{ __('warehouse.main_branch') }}</flux:badge>
                                    @endif
                                </div>
                                <flux:text class="text-sm text-zinc-500">
                                    {{ $branch->location }} • {{ $branch->manager ?? __('ui.no_manager') }}
                                </flux:text>
                            </div>
                        </div>
                        <div class="text-right">
                            <flux:text class="font-semibold text-lg">{{ $branch->warehouses_count }}</flux:text>
                            <flux:text class="text-sm text-zinc-500">{{ __('warehouse.warehouses') }}</flux:text>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-8">
                        <flux:icon name="building-storefront" class="h-12 w-12 text-zinc-400 mx-auto mb-3" />
                        <flux:text class="text-zinc-500">{{ __('warehouse.no_branches') }}</flux:text>
                        <flux:button variant="primary" size="sm" class="mt-3" :href="route('warehouse.branches.create')" wire:navigate>
                            {{ __('warehouse.create_branch') }}
                        </flux:button>
                    </div>
                @endforelse
            </div>
        </flux:card>

        <!-- Recent Activities -->
        <flux:card>
            <flux:heading>
                <flux:heading size="lg">{{ __('warehouse.recent_activities') }}</flux:heading>
                <flux:text class="text-zinc-600 dark:text-zinc-400">
                    {{ __('warehouse.latest_activities') }}
                </flux:text>
            </flux:heading>

            <div class="space-y-4">
                @forelse($this->recentActivities as $activity)
                    <div class="flex items-start gap-3 p-3 bg-zinc-50 dark:bg-zinc-800 rounded-lg">
                        <div class="flex-shrink-0">
                            <flux:icon
                                :name="$activity->icon"
                                class="h-5 w-5 text-{{ $activity->color }}-500"
                            />
                        </div>
                        <div class="min-w-0 flex-1">
                            <flux:text class="text-sm">{{ $activity->message }}</flux:text>
                            <div class="flex items-center gap-2 mt-1">
                                <flux:text class="text-xs text-zinc-500">{{ $activity->user }}</flux:text>
                                <flux:text class="text-xs text-zinc-400">•</flux:text>
                                <flux:text class="text-xs text-zinc-500">{{ $activity->timestamp->diffForHumans() }}</flux:text>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-8">
                        <flux:icon name="clock" class="h-12 w-12 text-zinc-400 mx-auto mb-3" />
                        <flux:text class="text-zinc-500">{{ __('warehouse.no_recent_activities') }}</flux:text>
                    </div>
                @endforelse
            </div>
        </flux:card>
    </div>

    <!-- Warehouse Capacity Overview -->
    @if($this->warehousesByCapacity->count() > 0)
        <flux:card class="mt-8">
            <flux:heading>
                <div class="flex items-center justify-between">
                    <div>
                        <flux:heading size="lg">{{ __('warehouse.capacity_overview') }}</flux:heading>
                        <flux:text class="text-zinc-600 dark:text-zinc-400">
                            {{ __('warehouse.warehouse_capacity_by_utilization') }}
                        </flux:text>
                    </div>
                    <flux:button variant="outline" size="sm" :href="route('warehouse.capacity.index')" wire:navigate>
                        {{ __('warehouse.capacity_management') }}
                    </flux:button>
                </div>
            </flux:heading>

            <div class="overflow-x-auto">
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>{{ __('warehouse.warehouse') }}</flux:table.column>
                        <flux:table.column>{{ __('warehouse.branch') }}</flux:table.column>
                        <flux:table.column>{{ __('warehouse.location') }}</flux:table.column>
                        <flux:table.column>{{ __('warehouse.total_capacity') }}</flux:table.column>
                        <flux:table.column>{{ __('warehouse.used_capacity') }}</flux:table.column>
                        <flux:table.column>{{ __('warehouse.utilization') }}</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @foreach($this->warehousesByCapacity as $warehouse)
                            <flux:table.row>
                                <flux:table.cell>
                                    <div>
                                        <flux:text class="font-medium">{{ $warehouse->name }}</flux:text>
                                        <flux:text class="text-sm text-zinc-500">{{ $warehouse->code }}</flux:text>
                                    </div>
                                </flux:table.cell>
                                <flux:table.cell>{{ $warehouse->branch_name }}</flux:table.cell>
                                <flux:table.cell>{{ $warehouse->location }}</flux:table.cell>
                                <flux:table.cell>{{ number_format($warehouse->total_capacity, 0) }} {{ $warehouse->capacity_unit }}</flux:table.cell>
                                <flux:table.cell>{{ number_format($warehouse->used_capacity, 0) }} {{ $warehouse->capacity_unit }}</flux:table.cell>
                                <flux:table.cell>
                                    <div class="flex items-center gap-2">
                                        <div class="flex-1 bg-zinc-200 dark:bg-zinc-700 rounded-full h-2">
                                            <div
                                                class="bg-{{ $warehouse->status_color }}-500 h-2 rounded-full"
                                                style="width: {{ $warehouse->utilization }}%"
                                            ></div>
                                        </div>
                                        <flux:badge :color="$warehouse->status_color" size="sm">
                                            {{ $warehouse->utilization }}%
                                        </flux:badge>
                                    </div>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            </div>
        </flux:card>
    @endif
</div>
