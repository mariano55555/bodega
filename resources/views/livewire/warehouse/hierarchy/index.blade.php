<?php

use Livewire\Volt\Component;
use App\Models\Company;
use App\Models\Branch;
use App\Models\Warehouse;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;

new #[Layout('components.layouts.app')] class extends Component
{
    public string $search = '';
    public string $expandedCompanies = '';
    public string $expandedBranches = '';
    public string $viewMode = 'tree'; // tree, cards, table

    public function mount(): void
    {
        $this->authorize('viewAny', Company::class);
    }

    #[Computed]
    public function hierarchicalData()
    {
        return Company::query()
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%')
                        ->orWhereHas('branches', function ($branchQuery) {
                            $branchQuery->where('name', 'like', '%' . $this->search . '%')
                                ->orWhereHas('warehouses', function ($warehouseQuery) {
                                    $warehouseQuery->where('name', 'like', '%' . $this->search . '%');
                                });
                        });
                });
            })
            ->with([
                'branches' => function ($query) {
                    $query->when($this->search, function ($q) {
                        $q->where('name', 'like', '%' . $this->search . '%')
                            ->orWhereHas('warehouses', function ($warehouseQuery) {
                                $warehouseQuery->where('name', 'like', '%' . $this->search . '%');
                            });
                    })->withCount(['warehouses']);
                },
                'branches.warehouses' => function ($query) {
                    $query->when($this->search, function ($q) {
                        $q->where('name', 'like', '%' . $this->search . '%');
                    })->withCount(['storageLocations']);
                },
                'branches.manager',
                'branches.warehouses.manager'
            ])
            ->withCount(['branches', 'warehouses', 'users'])
            ->active()
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function summaryStats()
    {
        $stats = [
            'total_companies' => Company::active()->count(),
            'total_branches' => Branch::active()->count(),
            'total_warehouses' => Warehouse::active()->count(),
            'total_capacity' => Warehouse::active()->sum('total_capacity') ?? 0,
        ];

        return $stats;
    }

    public function toggleCompany($companyId): void
    {
        $expanded = explode(',', $this->expandedCompanies);
        $companyId = (string) $companyId;

        if (in_array($companyId, $expanded)) {
            $expanded = array_diff($expanded, [$companyId]);
        } else {
            $expanded[] = $companyId;
        }

        $this->expandedCompanies = implode(',', array_filter($expanded));
    }

    public function toggleBranch($branchId): void
    {
        $expanded = explode(',', $this->expandedBranches);
        $branchId = (string) $branchId;

        if (in_array($branchId, $expanded)) {
            $expanded = array_diff($expanded, [$branchId]);
        } else {
            $expanded[] = $branchId;
        }

        $this->expandedBranches = implode(',', array_filter($expanded));
    }

    public function expandAll(): void
    {
        $companyIds = Company::active()->pluck('id')->map('strval')->toArray();
        $branchIds = Branch::active()->pluck('id')->map('strval')->toArray();

        $this->expandedCompanies = implode(',', $companyIds);
        $this->expandedBranches = implode(',', $branchIds);
    }

    public function collapseAll(): void
    {
        $this->expandedCompanies = '';
        $this->expandedBranches = '';
    }

    public function isCompanyExpanded($companyId): bool
    {
        return in_array((string) $companyId, explode(',', $this->expandedCompanies));
    }

    public function isBranchExpanded($branchId): bool
    {
        return in_array((string) $branchId, explode(',', $this->expandedBranches));
    }

    public function with(): array
    {
        return [
            'title' => 'Jerarquía Organizacional',
        ];
    }
}; ?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Page Header -->
    <div class="mb-8">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div>
                <flux:heading size="xl" class="text-zinc-900 dark:text-zinc-100">
                    Jerarquía Organizacional
                </flux:heading>
                <flux:text class="mt-2 text-zinc-600 dark:text-zinc-400">
                    Vista jerárquica de empresas, sucursales y almacenes
                </flux:text>
            </div>
            <div class="flex items-center gap-3">
                <flux:button variant="outline" icon="arrow-path" wire:click="$refresh">
                    Actualizar
                </flux:button>
                <flux:button variant="outline" icon="arrows-pointing-out" wire:click="expandAll">
                    Expandir Todo
                </flux:button>
                <flux:button variant="outline" icon="arrows-pointing-in" wire:click="collapseAll">
                    Contraer Todo
                </flux:button>
            </div>
        </div>
    </div>

    <!-- Summary Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <flux:card class="p-6">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                    <flux:icon name="building-office-2" class="h-8 w-8 text-blue-600 dark:text-blue-400" />
                </div>
                <div>
                    <flux:heading size="xl" class="text-blue-600 dark:text-blue-400">
                        {{ $this->summaryStats['total_companies'] }}
                    </flux:heading>
                    <flux:text class="text-zinc-600 dark:text-zinc-400">Empresas</flux:text>
                </div>
            </div>
        </flux:card>

        <flux:card class="p-6">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-green-100 dark:bg-green-900/30 rounded-lg">
                    <flux:icon name="building-office" class="h-8 w-8 text-green-600 dark:text-green-400" />
                </div>
                <div>
                    <flux:heading size="xl" class="text-green-600 dark:text-green-400">
                        {{ $this->summaryStats['total_branches'] }}
                    </flux:heading>
                    <flux:text class="text-zinc-600 dark:text-zinc-400">Sucursales</flux:text>
                </div>
            </div>
        </flux:card>

        <flux:card class="p-6">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-purple-100 dark:bg-purple-900/30 rounded-lg">
                    <flux:icon name="building-storefront" class="h-8 w-8 text-purple-600 dark:text-purple-400" />
                </div>
                <div>
                    <flux:heading size="xl" class="text-purple-600 dark:text-purple-400">
                        {{ $this->summaryStats['total_warehouses'] }}
                    </flux:heading>
                    <flux:text class="text-zinc-600 dark:text-zinc-400">Almacenes</flux:text>
                </div>
            </div>
        </flux:card>

        <flux:card class="p-6">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-orange-100 dark:bg-orange-900/30 rounded-lg">
                    <flux:icon name="scale" class="h-8 w-8 text-orange-600 dark:text-orange-400" />
                </div>
                <div>
                    <flux:heading size="xl" class="text-orange-600 dark:text-orange-400">
                        {{ number_format($this->summaryStats['total_capacity']) }}
                    </flux:heading>
                    <flux:text class="text-zinc-600 dark:text-zinc-400">Capacidad Total</flux:text>
                </div>
            </div>
        </flux:card>
    </div>

    <!-- Search and Controls -->
    <flux:card class="mb-6">
        <div class="flex flex-col lg:flex-row gap-4">
            <!-- Search -->
            <div class="flex-1">
                <flux:input
                    wire:model.live.debounce.300ms="search"
                    placeholder="Buscar empresas, sucursales o almacenes..."
                    icon="magnifying-glass"
                />
            </div>

            <!-- Quick Actions -->
            <div class="flex items-center gap-3">
                @can('create', App\Models\Company::class)
                    <flux:button variant="outline" icon="plus" :href="route('warehouse.companies.create')" wire:navigate>
                        Nueva Empresa
                    </flux:button>
                @endcan
                @can('create', App\Models\Branch::class)
                    <flux:button variant="outline" icon="plus" :href="route('warehouse.branches.create')" wire:navigate>
                        Nueva Sucursal
                    </flux:button>
                @endcan
                @can('create', App\Models\Warehouse::class)
                    <flux:button variant="outline" icon="plus" :href="route('warehouse.warehouses.create')" wire:navigate>
                        Nuevo Almacén
                    </flux:button>
                @endcan
            </div>
        </div>
    </flux:card>

    <!-- Hierarchical Tree View -->
    @if($this->hierarchicalData->count() > 0)
        <div class="space-y-4">
            @foreach($this->hierarchicalData as $company)
                <!-- Company Level -->
                <flux:card class="overflow-hidden">
                    <div class="p-6">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-4">
                                <!-- Expand/Collapse Button -->
                                @if($company->branches->count() > 0)
                                    <flux:button
                                        variant="ghost"
                                        size="sm"
                                        icon="{{ $this->isCompanyExpanded($company->id) ? 'chevron-down' : 'chevron-right' }}"
                                        wire:click="toggleCompany({{ $company->id }})"
                                    />
                                @else
                                    <div class="w-8"></div>
                                @endif

                                <!-- Company Info -->
                                <div class="flex items-center gap-3">
                                    <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                                        <flux:icon name="building-office-2" class="h-6 w-6 text-blue-600 dark:text-blue-400" />
                                    </div>
                                    <div>
                                        <flux:heading size="lg" class="text-zinc-900 dark:text-zinc-100">
                                            {{ $company->name }}
                                        </flux:heading>
                                        @if($company->description)
                                            <flux:text class="text-sm text-zinc-500 line-clamp-1">
                                                {{ $company->description }}
                                            </flux:text>
                                        @endif
                                    </div>
                                </div>

                                <!-- Company Stats -->
                                <div class="flex items-center gap-6 ml-auto">
                                    <div class="text-center">
                                        <flux:text class="text-lg font-semibold text-green-600 dark:text-green-400">
                                            {{ $company->branches_count }}
                                        </flux:text>
                                        <flux:text class="text-xs text-zinc-500">Sucursales</flux:text>
                                    </div>
                                    <div class="text-center">
                                        <flux:text class="text-lg font-semibold text-purple-600 dark:text-purple-400">
                                            {{ $company->warehouses_count }}
                                        </flux:text>
                                        <flux:text class="text-xs text-zinc-500">Almacenes</flux:text>
                                    </div>
                                    <div class="text-center">
                                        <flux:text class="text-lg font-semibold text-orange-600 dark:text-orange-400">
                                            {{ $company->users_count }}
                                        </flux:text>
                                        <flux:text class="text-xs text-zinc-500">Usuarios</flux:text>
                                    </div>
                                </div>
                            </div>

                            <!-- Company Actions -->
                            <flux:dropdown align="end">
                                <flux:button variant="ghost" size="sm" icon="ellipsis-vertical" />
                                <flux:menu>
                                    @can('view', $company)
                                        <flux:menu.item icon="eye" :href="route('warehouse.companies.edit', $company)" wire:navigate>
                                            Ver Empresa
                                        </flux:menu.item>
                                    @endcan
                                    @can('update', $company)
                                        <flux:menu.item icon="pencil" :href="route('warehouse.companies.edit', $company)" wire:navigate>
                                            Editar Empresa
                                        </flux:menu.item>
                                    @endcan
                                    @can('create', App\Models\Branch::class)
                                        <flux:menu.item icon="plus" :href="route('warehouse.branches.create', ['company_id' => $company->id])" wire:navigate>
                                            Nueva Sucursal
                                        </flux:menu.item>
                                    @endcan
                                </flux:menu>
                            </flux:dropdown>
                        </div>
                    </div>

                    <!-- Branches (Expanded) -->
                    @if($this->isCompanyExpanded($company->id) && $company->branches->count() > 0)
                        <div class="border-t border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800/50">
                            <div class="space-y-3 p-6">
                                @foreach($company->branches as $branch)
                                    <div class="bg-white dark:bg-zinc-900 rounded-lg p-4 ml-8">
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center gap-4">
                                                <!-- Branch Expand/Collapse -->
                                                @if($branch->warehouses->count() > 0)
                                                    <flux:button
                                                        variant="ghost"
                                                        size="sm"
                                                        icon="{{ $this->isBranchExpanded($branch->id) ? 'chevron-down' : 'chevron-right' }}"
                                                        wire:click="toggleBranch({{ $branch->id }})"
                                                    />
                                                @else
                                                    <div class="w-8"></div>
                                                @endif

                                                <!-- Branch Info -->
                                                <div class="flex items-center gap-3">
                                                    <div class="p-2 bg-green-100 dark:bg-green-900/30 rounded-lg">
                                                        <flux:icon name="building-office" class="h-5 w-5 text-green-600 dark:text-green-400" />
                                                    </div>
                                                    <div>
                                                        <flux:heading size="md" class="text-zinc-900 dark:text-zinc-100">
                                                            {{ $branch->name }}
                                                        </flux:heading>
                                                        <div class="flex items-center gap-4 mt-1">
                                                            @if($branch->code)
                                                                <flux:text class="text-xs text-zinc-500">
                                                                    {{ $branch->code }}
                                                                </flux:text>
                                                            @endif
                                                            @if($branch->manager)
                                                                <flux:text class="text-xs text-zinc-500">
                                                                    Gerente: {{ $branch->manager->name }}
                                                                </flux:text>
                                                            @endif
                                                            @if($branch->city)
                                                                <flux:text class="text-xs text-zinc-500">
                                                                    {{ $branch->city }}
                                                                </flux:text>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Branch Stats -->
                                                <div class="flex items-center gap-4 ml-auto">
                                                    <div class="text-center">
                                                        <flux:text class="text-md font-semibold text-purple-600 dark:text-purple-400">
                                                            {{ $branch->warehouses_count }}
                                                        </flux:text>
                                                        <flux:text class="text-xs text-zinc-500">Almacenes</flux:text>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Branch Actions -->
                                            <flux:dropdown align="end">
                                                <flux:button variant="ghost" size="sm" icon="ellipsis-vertical" />
                                                <flux:menu>
                                                    @can('view', $branch)
                                                        <flux:menu.item icon="eye" :href="route('warehouse.branches.edit', $branch)" wire:navigate>
                                                            Ver Sucursal
                                                        </flux:menu.item>
                                                    @endcan
                                                    @can('update', $branch)
                                                        <flux:menu.item icon="pencil" :href="route('warehouse.branches.edit', $branch)" wire:navigate>
                                                            Editar Sucursal
                                                        </flux:menu.item>
                                                    @endcan
                                                    @can('create', App\Models\Warehouse::class)
                                                        <flux:menu.item icon="plus" :href="route('warehouse.warehouses.create', ['branch_id' => $branch->id])" wire:navigate>
                                                            Nuevo Almacén
                                                        </flux:menu.item>
                                                    @endcan
                                                </flux:menu>
                                            </flux:dropdown>
                                        </div>

                                        <!-- Warehouses (Expanded) -->
                                        @if($this->isBranchExpanded($branch->id) && $branch->warehouses->count() > 0)
                                            <div class="mt-4 space-y-2 ml-8">
                                                @foreach($branch->warehouses as $warehouse)
                                                    <div class="bg-zinc-50 dark:bg-zinc-800 rounded-lg p-3">
                                                        <div class="flex items-center justify-between">
                                                            <div class="flex items-center gap-3">
                                                                <div class="p-1.5 bg-purple-100 dark:bg-purple-900/30 rounded-lg">
                                                                    <flux:icon name="building-storefront" class="h-4 w-4 text-purple-600 dark:text-purple-400" />
                                                                </div>
                                                                <div>
                                                                    <flux:text class="font-medium text-zinc-900 dark:text-zinc-100">
                                                                        {{ $warehouse->name }}
                                                                    </flux:text>
                                                                    <div class="flex items-center gap-4 mt-1">
                                                                        @if($warehouse->code)
                                                                            <flux:text class="text-xs text-zinc-500">
                                                                                {{ $warehouse->code }}
                                                                            </flux:text>
                                                                        @endif
                                                                        @if($warehouse->manager)
                                                                            <flux:text class="text-xs text-zinc-500">
                                                                                Gerente: {{ $warehouse->manager->name }}
                                                                            </flux:text>
                                                                        @endif
                                                                        @if($warehouse->total_capacity)
                                                                            <flux:text class="text-xs text-zinc-500">
                                                                                Capacidad: {{ number_format($warehouse->total_capacity) }} {{ $warehouse->capacity_unit ?? 'unidades' }}
                                                                            </flux:text>
                                                                        @endif
                                                                        <flux:text class="text-xs text-zinc-500">
                                                                            {{ $warehouse->storage_locations_count }} ubicaciones
                                                                        </flux:text>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <!-- Warehouse Actions -->
                                                            <flux:dropdown align="end">
                                                                <flux:button variant="ghost" size="sm" icon="ellipsis-vertical" />
                                                                <flux:menu>
                                                                    @can('view', $warehouse)
                                                                        <flux:menu.item icon="eye" :href="route('warehouse.warehouses.edit', $warehouse)" wire:navigate>
                                                                            Ver Almacén
                                                                        </flux:menu.item>
                                                                    @endcan
                                                                    @can('update', $warehouse)
                                                                        <flux:menu.item icon="pencil" :href="route('warehouse.warehouses.edit', $warehouse)" wire:navigate>
                                                                            Editar Almacén
                                                                        </flux:menu.item>
                                                                    @endcan
                                                                </flux:menu>
                                                            </flux:dropdown>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </flux:card>
            @endforeach
        </div>
    @else
        <!-- Empty State -->
        <flux:card class="text-center py-12">
            <flux:icon name="building-office-2" class="h-16 w-16 text-zinc-400 mx-auto mb-4" />
            <flux:heading size="lg" class="mb-2">
                {{ $search ? 'Sin resultados' : 'No hay datos organizacionales' }}
            </flux:heading>
            <flux:text class="text-zinc-500 mb-6">
                @if($search)
                    No se encontraron resultados para "{{ $search }}"
                @else
                    Comienza creando empresas, sucursales y almacenes
                @endif
            </flux:text>
            @if(!$search)
                @can('create', App\Models\Company::class)
                    <flux:button variant="primary" icon="plus" :href="route('warehouse.companies.create')" wire:navigate>
                        Crear Primera Empresa
                    </flux:button>
                @endcan
            @else
                <flux:button variant="outline" wire:click="$set('search', '')">
                    Limpiar búsqueda
                </flux:button>
            @endif
        </flux:card>
    @endif
</div>