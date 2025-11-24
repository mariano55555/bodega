<?php

use App\Models\Company;
use App\Models\InventoryMovement;
use App\Models\ProductCategory;
use App\Models\Warehouse;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component
{
    #[Url(as: 'empresa')]
    public $company_id = '';

    #[Url]
    public ?int $warehouse_id = null;

    #[Url]
    public ?int $category_id = null;

    #[Url]
    public ?string $date_from = null;

    #[Url]
    public ?string $date_to = null;

    public function mount(): void
    {
        if (! auth()->user()->isSuperAdmin()) {
            $this->company_id = (string) auth()->user()->company_id;
        }

        // Default to current month
        if (! $this->date_from) {
            $this->date_from = now()->startOfMonth()->format('Y-m-d');
        }
        if (! $this->date_to) {
            $this->date_to = now()->endOfMonth()->format('Y-m-d');
        }
    }

    public function updatedCompanyId(): void
    {
        $this->warehouse_id = null;
        $this->category_id = null;
    }

    #[Computed]
    public function isSuperAdmin(): bool
    {
        return auth()->user()->isSuperAdmin();
    }

    #[Computed]
    public function companies()
    {
        return $this->isSuperAdmin
            ? Company::where('is_active', true)->orderBy('name')->get()
            : collect();
    }

    #[Computed]
    public function effectiveCompanyId()
    {
        return $this->isSuperAdmin ? $this->company_id : auth()->user()->company_id;
    }

    #[Computed]
    public function warehouses()
    {
        if (! $this->effectiveCompanyId) {
            return collect();
        }

        return Warehouse::where('company_id', $this->effectiveCompanyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function categories()
    {
        if (! $this->effectiveCompanyId) {
            return collect();
        }

        return ProductCategory::where('company_id', $this->effectiveCompanyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function movements()
    {
        if (! $this->effectiveCompanyId) {
            return collect();
        }

        $companyId = $this->effectiveCompanyId;

        $query = InventoryMovement::query()
            ->where('company_id', $companyId)
            ->whereBetween('movement_date', [$this->date_from, $this->date_to])
            ->where('quantity_out', '>', 0)
            ->with(['product.category', 'warehouse']);

        if ($this->warehouse_id) {
            $query->where('warehouse_id', $this->warehouse_id);
        }

        if ($this->category_id) {
            $query->whereHas('product', function ($q) {
                $q->where('category_id', $this->category_id);
            });
        }

        return $query->get();
    }

    #[Computed]
    public function byCategory()
    {
        return $this->movements->groupBy('product.category.name')->map(function ($items) {
            return [
                'category' => $items->first()->product->category,
                'total_quantity' => $items->sum('quantity_out'),
                'total_value' => $items->sum(function ($item) {
                    return $item->quantity_out * ($item->product->cost ?? 0);
                }),
                'products_count' => $items->pluck('product_id')->unique()->count(),
                'movements_count' => $items->count(),
            ];
        })->sortByDesc('total_quantity');
    }

    #[Computed]
    public function summary()
    {
        $movements = $this->movements;

        return [
            'total_movements' => $movements->count(),
            'total_quantity' => $movements->sum('quantity_out'),
            'total_value' => $movements->sum(function ($item) {
                return $item->quantity_out * ($item->product->cost ?? 0);
            }),
            'categories_count' => $this->byCategory->count(),
        ];
    }

    public function exportExcel(): void
    {
        $this->redirect(route('reports.movements.consumption-by-line.export', [
            'warehouse_id' => $this->warehouse_id,
            'category_id' => $this->category_id,
            'date_from' => $this->date_from,
            'date_to' => $this->date_to,
            'empresa' => $this->effectiveCompanyId,
        ]));
    }

    public function resetFilters(): void
    {
        $this->warehouse_id = null;
        $this->category_id = null;
        $this->date_from = now()->startOfMonth()->format('Y-m-d');
        $this->date_to = now()->endOfMonth()->format('Y-m-d');
    }
}; ?>

<div>
    <div class="mb-6 flex items-center justify-between">
        <flux:heading size="xl">Consumo por Linea de Productos</flux:heading>

        <flux:button :href="route('reports.inventory.index')" variant="ghost">
            <flux:icon.arrow-left class="size-4" />
            Volver a Reportes
        </flux:button>
    </div>

    {{-- Filters --}}
    <flux:card class="mb-6">
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-5">
            @if ($this->isSuperAdmin)
                <flux:field>
                    <flux:label>Empresa</flux:label>
                    <flux:select wire:model.live="company_id" placeholder="Seleccione una empresa">
                        <option value="">Seleccione una empresa</option>
                        @foreach ($this->companies as $company)
                            <option value="{{ $company->id }}">{{ $company->name }}</option>
                        @endforeach
                    </flux:select>
                </flux:field>
            @endif

            <flux:field>
                <flux:label>Bodega</flux:label>
                <flux:select wire:model.live="warehouse_id" placeholder="Todas las bodegas" :disabled="!$this->effectiveCompanyId">
                    <option value="">Todas las bodegas</option>
                    @foreach ($this->warehouses as $warehouse)
                        <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                    @endforeach
                </flux:select>
            </flux:field>

            <flux:field>
                <flux:label>Categoria</flux:label>
                <flux:select wire:model.live="category_id" placeholder="Todas las categorias" :disabled="!$this->effectiveCompanyId">
                    <option value="">Todas las categorias</option>
                    @foreach ($this->categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </flux:select>
            </flux:field>

            <flux:field>
                <flux:label>Fecha Desde</flux:label>
                <flux:input type="date" wire:model.live="date_from" :disabled="!$this->effectiveCompanyId" />
            </flux:field>

            <flux:field>
                <flux:label>Fecha Hasta</flux:label>
                <flux:input type="date" wire:model.live="date_to" :disabled="!$this->effectiveCompanyId" />
            </flux:field>
        </div>

        <div class="mt-4 flex gap-3">
            <flux:button wire:click="resetFilters" variant="ghost">
                Limpiar Filtros
            </flux:button>

            <flux:button wire:click="exportExcel" variant="primary" :disabled="!$this->effectiveCompanyId" icon="table-cells">
                Exportar Excel
            </flux:button>
        </div>
    </flux:card>

    @if (! $this->effectiveCompanyId)
        <flux:card>
            <div class="py-12 text-center">
                <flux:icon.building-office class="mx-auto size-12 text-zinc-400 dark:text-zinc-600" />
                <flux:heading size="lg" class="mt-4">Seleccione una Empresa</flux:heading>
                <flux:text class="mt-2">
                    Seleccione una empresa para ver el consumo por linea de productos
                </flux:text>
            </div>
        </flux:card>
    @else
        {{-- Summary Cards --}}
        <div class="mb-6 grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4">
            <flux:card>
                <div class="flex items-center justify-between">
                    <div>
                        <flux:text class="mb-1 text-sm text-zinc-600 dark:text-zinc-400">Total Movimientos</flux:text>
                        <flux:heading size="2xl">{{ number_format($this->summary['total_movements']) }}</flux:heading>
                    </div>
                    <div class="flex size-16 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900/30">
                        <flux:icon.arrow-up-tray class="size-8 text-blue-600 dark:text-blue-400" />
                    </div>
                </div>
            </flux:card>

            <flux:card>
                <div class="flex items-center justify-between">
                    <div>
                        <flux:text class="mb-1 text-sm text-zinc-600 dark:text-zinc-400">Cantidad Total</flux:text>
                        <flux:heading size="2xl" class="text-red-600 dark:text-red-400">
                            -{{ number_format($this->summary['total_quantity'], 2) }}
                        </flux:heading>
                    </div>
                    <div class="flex size-16 items-center justify-center rounded-lg bg-red-100 dark:bg-red-900/30">
                        <flux:icon.cube class="size-8 text-red-600 dark:text-red-400" />
                    </div>
                </div>
            </flux:card>

            <flux:card>
                <div class="flex items-center justify-between">
                    <div>
                        <flux:text class="mb-1 text-sm text-zinc-600 dark:text-zinc-400">Valor Total</flux:text>
                        <flux:heading size="2xl" class="text-amber-600 dark:text-amber-400">
                            ${{ number_format($this->summary['total_value'], 2) }}
                        </flux:heading>
                    </div>
                    <div class="flex size-16 items-center justify-center rounded-lg bg-amber-100 dark:bg-amber-900/30">
                        <flux:icon.currency-dollar class="size-8 text-amber-600 dark:text-amber-400" />
                    </div>
                </div>
            </flux:card>

            <flux:card>
                <div class="flex items-center justify-between">
                    <div>
                        <flux:text class="mb-1 text-sm text-zinc-600 dark:text-zinc-400">Categorias</flux:text>
                        <flux:heading size="2xl" class="text-purple-600 dark:text-purple-400">
                            {{ number_format($this->summary['categories_count']) }}
                        </flux:heading>
                    </div>
                    <div class="flex size-16 items-center justify-center rounded-lg bg-purple-100 dark:bg-purple-900/30">
                        <flux:icon.tag class="size-8 text-purple-600 dark:text-purple-400" />
                    </div>
                </div>
            </flux:card>
        </div>

        {{-- By Category Table --}}
        <flux:card>
            <flux:heading size="lg" class="mb-4">Consumo por Categoria</flux:heading>

            @if ($this->byCategory->isEmpty())
                <div class="py-12 text-center">
                    <flux:icon.arrow-up-tray class="mx-auto size-12 text-zinc-400 dark:text-zinc-600" />
                    <flux:heading size="lg" class="mt-4">Sin Consumo</flux:heading>
                    <flux:text class="mt-2">
                        No se encontraron movimientos de salida para el periodo seleccionado
                    </flux:text>
                </div>
            @else
                <div class="overflow-x-auto">
                    <flux:table>
                        <flux:table.columns>
                            <flux:table.column>Categoria</flux:table.column>
                            <flux:table.column class="text-right">Productos</flux:table.column>
                            <flux:table.column class="text-right">Movimientos</flux:table.column>
                            <flux:table.column class="text-right">Cantidad</flux:table.column>
                            <flux:table.column class="text-right">Valor</flux:table.column>
                            <flux:table.column class="text-right">% del Total</flux:table.column>
                        </flux:table.columns>

                        <flux:table.rows>
                            @foreach ($this->byCategory as $categoryName => $data)
                                <flux:table.row>
                                    <flux:table.cell class="font-medium">
                                        {{ $categoryName ?? 'Sin Categoria' }}
                                    </flux:table.cell>

                                    <flux:table.cell class="text-right tabular-nums">
                                        {{ number_format($data['products_count']) }}
                                    </flux:table.cell>

                                    <flux:table.cell class="text-right tabular-nums">
                                        {{ number_format($data['movements_count']) }}
                                    </flux:table.cell>

                                    <flux:table.cell class="text-right tabular-nums text-red-600 dark:text-red-400">
                                        -{{ number_format($data['total_quantity'], 2) }}
                                    </flux:table.cell>

                                    <flux:table.cell class="text-right tabular-nums font-medium">
                                        ${{ number_format($data['total_value'], 2) }}
                                    </flux:table.cell>

                                    <flux:table.cell class="text-right tabular-nums">
                                        @php
                                            $percentage = $this->summary['total_value'] > 0
                                                ? ($data['total_value'] / $this->summary['total_value']) * 100
                                                : 0;
                                        @endphp
                                        <div class="flex items-center justify-end gap-2">
                                            <div class="h-2 w-16 overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-700">
                                                <div class="h-full bg-purple-500" style="width: {{ min($percentage, 100) }}%"></div>
                                            </div>
                                            <span>{{ number_format($percentage, 1) }}%</span>
                                        </div>
                                    </flux:table.cell>
                                </flux:table.row>
                            @endforeach
                        </flux:table.rows>
                    </flux:table>
                </div>
            @endif
        </flux:card>
    @endif
</div>
