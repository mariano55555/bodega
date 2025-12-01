<?php

use Livewire\Volt\Component;
use App\Models\Warehouse;
use App\Models\Company;
use App\Models\Branch;
use App\Models\User;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;

new #[Layout('components.layouts.app')] class extends Component
{
    use WithPagination;

    public string $search = '';
    public string $sortBy = 'name';
    public string $sortDirection = 'asc';
    public string $statusFilter = 'all';
    public string $companyFilter = 'all';
    public string $branchFilter = 'all';
    public string $capacityFilter = 'all';
    public ?int $warehouseToDelete = null;

    public function mount(): void
    {
        $this->authorize('viewAny', Warehouse::class);

        // For non-super admins, set company filter to their company
        if (!$this->isSuperAdmin()) {
            $this->companyFilter = (string) auth()->user()->company_id;
        }
    }

    public function isSuperAdmin(): bool
    {
        return auth()->user()->isSuperAdmin();
    }

    #[Computed]
    public function warehouses()
    {
        return Warehouse::query()
            ->with(['branch.company', 'manager'])
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%')
                        ->orWhere('code', 'like', '%' . $this->search . '%')
                        ->orWhere('description', 'like', '%' . $this->search . '%')
                        ->orWhere('address', 'like', '%' . $this->search . '%')
                        ->orWhere('city', 'like', '%' . $this->search . '%')
                        ->orWhereHas('branch', function ($branchQuery) {
                            $branchQuery->where('name', 'like', '%' . $this->search . '%');
                        })
                        ->orWhereHas('branch.company', function ($companyQuery) {
                            $companyQuery->where('name', 'like', '%' . $this->search . '%');
                        });
                });
            })
            ->when($this->statusFilter !== 'all', function ($query) {
                if ($this->statusFilter === 'active') {
                    $query->active();
                } else {
                    $query->where('is_active', false);
                }
            })
            ->when($this->companyFilter !== 'all', function ($query) {
                $query->whereHas('branch', function ($branchQuery) {
                    $branchQuery->where('company_id', $this->companyFilter);
                });
            })
            ->when($this->branchFilter !== 'all', function ($query) {
                $query->where('branch_id', $this->branchFilter);
            })
            ->when($this->capacityFilter !== 'all', function ($query) {
                switch ($this->capacityFilter) {
                    case 'small':
                        $query->where('total_capacity', '<', 1000);
                        break;
                    case 'medium':
                        $query->whereBetween('total_capacity', [1000, 10000]);
                        break;
                    case 'large':
                        $query->where('total_capacity', '>', 10000);
                        break;
                }
            })
            ->withCount(['storageLocations'])
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(12);
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
    public function branches()
    {
        if ($this->companyFilter === 'all') {
            return Branch::active()->with('company')->orderBy('name')->get();
        }

        return Branch::where('company_id', $this->companyFilter)
            ->active()
            ->with('company')
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function capacityFilters()
    {
        return [
            'small' => __('warehouse.capacity_small'),
            'medium' => __('warehouse.capacity_medium'),
            'large' => __('warehouse.capacity_large')
        ];
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedCompanyFilter(): void
    {
        $this->branchFilter = 'all';
        $this->resetPage();
    }

    public function sortBy($field): void
    {
        if ($this->sortBy === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function confirmDelete(int $warehouseId): void
    {
        $this->warehouseToDelete = $warehouseId;
        $this->modal('delete-warehouse')->show();
    }

    public function deleteWarehouse(): void
    {
        if (! $this->warehouseToDelete) {
            return;
        }

        $warehouse = Warehouse::findOrFail($this->warehouseToDelete);
        $this->authorize('delete', $warehouse);

        $warehouse->delete();

        $this->warehouseToDelete = null;
        $this->modal('delete-warehouse')->close();

        $this->dispatch('warehouse-deleted', [
            'message' => __('warehouse.warehouse_deleted_successfully')
        ]);
    }

    public function toggleStatus(int $warehouseId): void
    {
        $warehouse = Warehouse::findOrFail($warehouseId);
        $this->authorize('update', $warehouse);

        $warehouse->update(['is_active' => !$warehouse->is_active]);

        $this->dispatch('warehouse-status-toggled', [
            'message' => $warehouse->is_active
                ? __('warehouse.warehouse_activated_successfully')
                : __('warehouse.warehouse_deactivated_successfully')
        ]);
    }

    public function with(): array
    {
        return [
            'title' => __('warehouse.warehouse_management'),
        ];
    }
}; ?>

<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">{{ __('warehouse.warehouse_management') }}</flux:heading>
            <flux:text class="mt-1">{{ __('warehouse.manage_warehouses_description') }}</flux:text>
        </div>
        <div class="flex items-center gap-3">
                <flux:button variant="outline" icon="arrow-path" wire:click="$refresh">
                    {{ __('ui.refresh') }}
                </flux:button>
                <flux:button variant="outline" icon="building-office" :href="route('warehouse.hierarchy.index')" wire:navigate>
                    {{ __('warehouse.hierarchical_view') }}
                </flux:button>
                @can('create', App\Models\Warehouse::class)
                    <flux:button variant="primary" icon="plus" :href="route('warehouse.warehouses.create')" wire:navigate>
                        {{ __('warehouse.new_warehouse') }}
                    </flux:button>
                @endcan
        </div>
    </div>

    <!-- Filters and Search -->
    <flux:card>
        <div class="flex flex-col lg:flex-row gap-4">
            <!-- Search -->
            <div class="flex-1">
                <flux:input
                    wire:model.live.debounce.300ms="search"
                    :placeholder="__('warehouse.search_warehouses_placeholder')"
                    icon="magnifying-glass"
                />
            </div>

            <!-- Company Filter (Only for Super Admins) -->
            @if($this->isSuperAdmin())
                <div class="lg:w-48">
                    <flux:select wire:model.live="companyFilter" :placeholder="__('warehouse.filter_by_company')">
                        <flux:select.option value="all">{{ __('warehouse.all_companies') }}</flux:select.option>
                        @foreach($this->companies as $company)
                            <flux:select.option value="{{ $company->id }}">{{ $company->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
            @endif

            <!-- Branch Filter -->
            <div class="lg:w-48">
                <flux:select wire:model.live="branchFilter" :placeholder="__('warehouse.filter_by_branch')">
                    <flux:select.option value="all">{{ __('warehouse.all_branches') }}</flux:select.option>
                    @foreach($this->branches as $branch)
                        <flux:select.option value="{{ $branch->id }}">
                            {{ $branch->name }}
                            @if($companyFilter === 'all')
                                ({{ $branch->company->name }})
                            @endif
                        </flux:select.option>
                    @endforeach
                </flux:select>
            </div>

            <!-- Capacity Filter -->
            <div class="lg:w-48">
                <flux:select wire:model.live="capacityFilter" :placeholder="__('warehouse.filter_by_capacity')">
                    <flux:select.option value="all">{{ __('warehouse.all_capacities') }}</flux:select.option>
                    @foreach($this->capacityFilters as $value => $label)
                        <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>

            <!-- Status Filter -->
            <div class="lg:w-40">
                <flux:select wire:model.live="statusFilter" :placeholder="__('ui.status')">
                    <flux:select.option value="all">{{ __('ui.all') }}</flux:select.option>
                    <flux:select.option value="active">{{ __('ui.active') }}</flux:select.option>
                    <flux:select.option value="inactive">{{ __('ui.inactive') }}</flux:select.option>
                </flux:select>
            </div>

            <!-- Sort Options -->
            <div class="lg:w-48">
                <flux:select wire:model.live="sortBy" :placeholder="__('ui.sort_by')">
                    <flux:select.option value="name">{{ __('ui.name') }}</flux:select.option>
                    <flux:select.option value="total_capacity">{{ __('warehouse.capacity') }}</flux:select.option>
                    <flux:select.option value="created_at">{{ __('ui.created_at') }}</flux:select.option>
                    <flux:select.option value="storage_locations_count">{{ __('warehouse.storage_locations') }}</flux:select.option>
                </flux:select>
            </div>
        </div>
    </flux:card>

    <!-- Warehouses Grid -->
    @if($this->warehouses->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($this->warehouses as $warehouse)
                <flux:card class="hover:shadow-lg transition-shadow duration-200">
                    <flux:heading>
                        <div class="flex items-start justify-between">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 mb-2">
                                    <flux:heading size="lg" class="truncate">
                                        {{ $warehouse->name }}
                                    </flux:heading>
                                    <flux:badge :color="$warehouse->is_active ? 'green' : 'red'" size="sm">
                                        {{ $warehouse->is_active ? __('ui.active') : __('ui.inactive') }}
                                    </flux:badge>
                                </div>
                                @if($warehouse->code)
                                    <flux:text class="text-sm text-zinc-500">
                                        {{ __('ui.code') }}: {{ $warehouse->code }}
                                    </flux:text>
                                @endif
                                <flux:text class="text-sm text-blue-600 dark:text-blue-400 font-medium">
                                    {{ $warehouse->branch->company->name }}
                                </flux:text>
                                <flux:text class="text-sm text-green-600 dark:text-green-400">
                                    {{ $warehouse->branch->name }}
                                </flux:text>
                            </div>

                            <!-- Action Dropdown -->
                            <flux:dropdown align="end">
                                <flux:button variant="ghost" size="sm" icon="ellipsis-vertical" />
                                <flux:menu>
                                    @can('view', $warehouse)
                                        <flux:menu.item icon="eye" :href="route('warehouse.warehouses.edit', $warehouse)" wire:navigate>
                                            {{ __('ui.view') }}
                                        </flux:menu.item>
                                    @endcan
                                    @can('update', $warehouse)
                                        <flux:menu.item icon="pencil" :href="route('warehouse.warehouses.edit', $warehouse)" wire:navigate>
                                            {{ __('ui.edit') }}
                                        </flux:menu.item>
                                        <flux:menu.item
                                            icon="{{ $warehouse->is_active ? 'eye-slash' : 'eye' }}"
                                            wire:click="toggleStatus({{ $warehouse->id }})"
                                        >
                                            {{ $warehouse->is_active ? __('ui.deactivate') : __('ui.activate') }}
                                        </flux:menu.item>
                                    @endcan
                                    @can('delete', $warehouse)
                                        <flux:menu.separator />
                                        <flux:menu.item
                                            icon="trash"
                                            variant="danger"
                                            wire:click="confirmDelete({{ $warehouse->id }})"
                                        >
                                            {{ __('ui.delete') }}
                                        </flux:menu.item>
                                    @endcan
                                </flux:menu>
                            </flux:dropdown>
                        </div>
                    </flux:heading>

                    <!-- Warehouse Details -->
                    <div class="space-y-3">
                        @if($warehouse->description)
                            <flux:text class="text-sm line-clamp-2">
                                {{ $warehouse->description }}
                            </flux:text>
                        @endif

                        <!-- Manager Info -->
                        @if($warehouse->manager)
                            <div class="flex items-center gap-2">
                                <flux:icon name="user" class="h-4 w-4 text-zinc-400" />
                                <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                                    {{ __('warehouse.manager') }}: {{ $warehouse->manager->name }}
                                </flux:text>
                            </div>
                        @endif

                        <!-- Capacity -->
                        @if($warehouse->total_capacity)
                            <div class="flex items-center gap-2">
                                <flux:icon name="scale" class="h-4 w-4 text-zinc-400" />
                                <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                                    {{ __('warehouse.capacity') }}: {{ number_format($warehouse->total_capacity) }} {{ $warehouse->capacity_unit ?? __('warehouse.units') }}
                                </flux:text>
                            </div>
                        @endif

                        <!-- Location -->
                        @if($warehouse->latitude && $warehouse->longitude)
                            <div class="flex items-center gap-2">
                                <flux:icon name="map-pin" class="h-4 w-4 text-zinc-400" />
                                <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                                    GPS: {{ round($warehouse->latitude, 4) }}, {{ round($warehouse->longitude, 4) }}
                                </flux:text>
                            </div>
                        @endif

                        <!-- Stats -->
                        <div class="grid grid-cols-2 gap-4 pt-4 border-t border-zinc-200 dark:border-zinc-700">
                            <div class="text-center">
                                <flux:heading size="lg" class="text-purple-600 dark:text-purple-400">
                                    {{ $warehouse->storage_locations_count }}
                                </flux:heading>
                                <flux:text class="text-xs text-zinc-500">
                                    {{ __('warehouse.storage_locations') }}
                                </flux:text>
                            </div>
                            <div class="text-center">
                                @php
                                    $utilizationColor = 'green';
                                    $utilization = 0;
                                    if ($warehouse->total_capacity && $warehouse->total_capacity > 0) {
                                        $utilization = ($warehouse->storage_locations_count / $warehouse->total_capacity) * 100;
                                        if ($utilization > 80) $utilizationColor = 'red';
                                        elseif ($utilization > 60) $utilizationColor = 'yellow';
                                    }
                                @endphp
                                <flux:heading size="lg" class="text-{{ $utilizationColor }}-600 dark:text-{{ $utilizationColor }}-400">
                                    {{ round($utilization, 1) }}%
                                </flux:heading>
                                <flux:text class="text-xs text-zinc-500">
                                    {{ __('warehouse.utilization') }}
                                </flux:text>
                            </div>
                        </div>

                        <!-- Address -->
                        @if($warehouse->address || $warehouse->city)
                            <div class="pt-3 border-t border-zinc-200 dark:border-zinc-700">
                                <div class="flex items-center gap-2">
                                    <flux:icon name="map-pin" class="h-4 w-4 text-zinc-400" />
                                    <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                                        {{ collect([$warehouse->address, $warehouse->city, $warehouse->state])->filter()->implode(', ') }}
                                    </flux:text>
                                </div>
                            </div>
                        @endif

                        <!-- Operating Hours -->
                        @if($warehouse->operating_hours)
                            <div class="pt-2">
                                <div class="flex items-center gap-2">
                                    <flux:icon name="clock" class="h-4 w-4 text-zinc-400" />
                                    <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                                        @if(isset($warehouse->operating_hours['is_24_hours']) && $warehouse->operating_hours['is_24_hours'])
                                            {{ __('warehouse.24_hours') }}
                                        @else
                                            {{ $warehouse->operating_hours['opening_time'] ?? '08:00' }} - {{ $warehouse->operating_hours['closing_time'] ?? '18:00' }}
                                        @endif
                                    </flux:text>
                                </div>
                            </div>
                        @endif
                    </div>

                    <div>
                        <div class="flex items-center justify-between text-xs text-zinc-500">
                            <span>{{ __('ui.created') }}: {{ $warehouse->created_at->format('M d, Y') }}</span>
                            @if($warehouse->updated_at != $warehouse->created_at)
                                <span>{{ __('ui.updated') }}: {{ $warehouse->updated_at->diffForHumans() }}</span>
                            @endif
                        </div>
                    </div>
                </flux:card>
            @endforeach
        </div>

        <!-- Pagination -->
        {{ $this->warehouses->links() }}
    @else
        <!-- Empty State -->
        <flux:card class="text-center py-12">
            <flux:icon name="building-storefront" class="h-16 w-16 text-zinc-400 mx-auto mb-4" />
            <flux:heading size="lg" class="mb-2">
                {{ $search ? __('ui.no_results') : __('warehouse.no_warehouses') }}
            </flux:heading>
            <flux:text class="text-zinc-500 mb-6">
                @if($search)
                    {{ __('warehouse.no_warehouses_found_for') }} "{{ $search }}"
                @else
                    {{ __('warehouse.no_warehouses_description') }}
                @endif
            </flux:text>
            @if(!$search)
                @can('create', App\Models\Warehouse::class)
                    <flux:button variant="primary" icon="plus" :href="route('warehouse.warehouses.create')" wire:navigate>
                        {{ __('warehouse.create_first_warehouse') }}
                    </flux:button>
                @endcan
            @else
                <flux:button variant="outline" wire:click="$set('search', '')">
                    {{ __('ui.clear_search') }}
                </flux:button>
            @endif
        </flux:card>
    @endif

    <!-- Delete Confirmation Modal -->
    <flux:modal name="delete-warehouse" class="min-w-[22rem]">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('warehouse.delete_warehouse_title') }}</flux:heading>
                <flux:text class="mt-2">
                    <p>{{ __('warehouse.delete_warehouse_confirmation') }}</p>
                    <p class="mt-2 text-red-600 dark:text-red-400">{{ __('ui.this_action_cannot_be_undone') }}</p>
                </flux:text>
            </div>
            <div class="flex gap-2">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('ui.cancel') }}</flux:button>
                </flux:modal.close>
                <flux:button variant="danger" wire:click="deleteWarehouse">{{ __('ui.delete') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
