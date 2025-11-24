<?php

use App\Models\Company;
use App\Models\Inventory;
use App\Models\ProductCategory;
use App\Models\Warehouse;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;

new class extends Component
{
    #[Url(as: 'empresa')]
    public $company_id = '';

    #[Url]
    public ?int $warehouse_id = null;

    #[Url]
    public ?int $category_id = null;

    #[Url]
    public ?string $type = null;

    public function mount(): void
    {
        if (! auth()->user()->isSuperAdmin()) {
            $this->company_id = (string) auth()->user()->company_id;
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
    public function inventories()
    {
        if (! $this->effectiveCompanyId) {
            return collect();
        }

        $companyId = $this->effectiveCompanyId;

        $query = Inventory::query()
            ->whereHas('warehouse', function ($q) use ($companyId) {
                $q->where('company_id', $companyId);
            })
            ->where('quantity', '>', 0)
            ->with(['product', 'warehouse', 'storageLocation']);

        if ($this->warehouse_id) {
            $query->where('warehouse_id', $this->warehouse_id);
        }

        if ($this->type && $this->type !== 'global') {
            $query->whereHas('warehouse', function ($q) {
                if ($this->type === 'fractional') {
                    $q->where('warehouse_type', 'fractional');
                } elseif ($this->type === 'individual') {
                    $q->where('warehouse_type', 'general');
                }
            });
        }

        if ($this->category_id) {
            $query->whereHas('product', function ($q) {
                $q->where('category_id', $this->category_id);
            });
        }

        return $query->orderBy('warehouse_id')
            ->orderBy('product_id')
            ->get();
    }

    #[Computed]
    public function summary()
    {
        $inventories = $this->inventories;

        return [
            'total_products' => $inventories->unique('product_id')->count(),
            'total_quantity' => $inventories->sum('quantity'),
            'total_value' => $inventories->sum(function ($inventory) {
                return $inventory->quantity * ($inventory->product->cost ?? 0);
            }),
            'by_warehouse' => $inventories->groupBy('warehouse_id')->map(function ($items) {
                return [
                    'warehouse' => $items->first()->warehouse,
                    'quantity' => $items->sum('quantity'),
                    'value' => $items->sum(function ($inventory) {
                        return $inventory->quantity * ($inventory->product->cost ?? 0);
                    }),
                ];
            }),
        ];
    }

    public function exportExcel(): void
    {
        $this->redirect(route('reports.inventory.consolidated.export', [
            'warehouse_id' => $this->warehouse_id,
            'category_id' => $this->category_id,
            'type' => $this->type,
            'empresa' => $this->effectiveCompanyId,
        ]));
    }

    public function exportPdf(): void
    {
        $this->redirect(route('reports.inventory.consolidated.pdf', [
            'warehouse_id' => $this->warehouse_id,
            'category_id' => $this->category_id,
            'type' => $this->type,
            'empresa' => $this->effectiveCompanyId,
        ]));
    }

    public function resetFilters(): void
    {
        $this->warehouse_id = null;
        $this->category_id = null;
        $this->type = null;
    }
}; ?>

<div>
    <div class="mb-6 flex items-center justify-between">
        <flux:heading size="xl">Inventario Consolidado</flux:heading>

        <flux:button :href="route('reports.inventory.index')" variant="ghost">
            <flux:icon.arrow-left class="size-4" />
            Volver a Reportes
        </flux:button>
    </div>

    {{-- Filters --}}
    <flux:card class="mb-6">
        <div class="grid grid-cols-1 gap-6 md:grid-cols-4">
            {{-- Company Selection (Super Admin Only) --}}
            @if ($this->isSuperAdmin)
                <flux:field class="md:col-span-4">
                    <flux:label>Empresa</flux:label>
                    <flux:select wire:model.live="company_id">
                        <flux:select.option value="">-- Seleccione una empresa --</flux:select.option>
                        @foreach ($this->companies as $company)
                            <flux:select.option value="{{ $company->id }}">{{ $company->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </flux:field>
            @endif

            {{-- Warehouse Selection --}}
            <flux:field>
                <flux:label>Bodega</flux:label>
                <flux:select wire:model.live="warehouse_id">
                    <flux:select.option value="">-- Todas las bodegas --</flux:select.option>
                    @foreach ($this->warehouses as $warehouse)
                        <flux:select.option value="{{ $warehouse->id }}">{{ $warehouse->name }}</flux:select.option>
                    @endforeach
                </flux:select>
            </flux:field>

            {{-- Warehouse Type --}}
            <flux:field>
                <flux:label>Tipo de Bodega</flux:label>
                <flux:select wire:model.live="type">
                    <flux:select.option value="">-- Todos los tipos --</flux:select.option>
                    <flux:select.option value="individual">Bodegas Generales</flux:select.option>
                    <flux:select.option value="fractional">Bodegas Fraccionarias</flux:select.option>
                    <flux:select.option value="global">Todas (Global)</flux:select.option>
                </flux:select>
            </flux:field>

            {{-- Category Selection --}}
            <flux:field>
                <flux:label>Categoría</flux:label>
                <flux:select wire:model.live="category_id">
                    <flux:select.option value="">-- Todas las categorías --</flux:select.option>
                    @foreach ($this->categories as $category)
                        <flux:select.option value="{{ $category->id }}">{{ $category->name }}</flux:select.option>
                    @endforeach
                </flux:select>
            </flux:field>
        </div>

        <div class="mt-4 flex gap-3">
            <flux:button wire:click="resetFilters" variant="ghost">
                Limpiar Filtros
            </flux:button>

            <flux:button wire:click="exportPdf" variant="primary" icon="document-arrow-down">
                Exportar PDF
            </flux:button>

            <flux:button wire:click="exportExcel" variant="primary" icon="table-cells">
                Exportar Excel
            </flux:button>
        </div>
    </flux:card>

    {{-- Summary Cards --}}
    <div class="mb-6 grid grid-cols-1 gap-6 md:grid-cols-3">
        <flux:card>
            <div class="flex items-center justify-between">
                <div>
                    <flux:text class="mb-1 text-sm text-zinc-600 dark:text-zinc-400">Total de Productos</flux:text>
                    <flux:heading size="xl">{{ number_format($this->summary['total_products']) }}</flux:heading>
                </div>
                <div class="flex size-12 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900/30">
                    <flux:icon.cube class="size-6 text-blue-600 dark:text-blue-400" />
                </div>
            </div>
        </flux:card>

        <flux:card>
            <div class="flex items-center justify-between">
                <div>
                    <flux:text class="mb-1 text-sm text-zinc-600 dark:text-zinc-400">Cantidad Total</flux:text>
                    <flux:heading size="xl">{{ number_format($this->summary['total_quantity'], 2) }}</flux:heading>
                </div>
                <div class="flex size-12 items-center justify-center rounded-lg bg-purple-100 dark:bg-purple-900/30">
                    <flux:icon.cube-transparent class="size-6 text-purple-600 dark:text-purple-400" />
                </div>
            </div>
        </flux:card>

        <flux:card>
            <div class="flex items-center justify-between">
                <div>
                    <flux:text class="mb-1 text-sm text-zinc-600 dark:text-zinc-400">Valor Total</flux:text>
                    <flux:heading size="xl" class="text-green-600 dark:text-green-400">
                        ${{ number_format($this->summary['total_value'], 2) }}
                    </flux:heading>
                </div>
                <div class="flex size-12 items-center justify-center rounded-lg bg-green-100 dark:bg-green-900/30">
                    <flux:icon.currency-dollar class="size-6 text-green-600 dark:text-green-400" />
                </div>
            </div>
        </flux:card>
    </div>

    {{-- Inventory Table --}}
    <flux:card>
        @if ($this->inventories->isEmpty())
            <div class="py-12 text-center">
                <flux:icon.cube class="mx-auto size-12 text-zinc-400 dark:text-zinc-600" />
                <flux:heading size="lg" class="mt-4">No hay inventario</flux:heading>
                <flux:text class="mt-2">
                    No se encontró inventario con los filtros seleccionados
                </flux:text>
            </div>
        @else
            <div class="overflow-x-auto">
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>Bodega</flux:table.column>
                        <flux:table.column>SKU</flux:table.column>
                        <flux:table.column>Producto</flux:table.column>
                        <flux:table.column>Categoría</flux:table.column>
                        <flux:table.column>Ubicación</flux:table.column>
                        <flux:table.column class="text-right">Cantidad</flux:table.column>
                        <flux:table.column class="text-right">Costo Unit.</flux:table.column>
                        <flux:table.column class="text-right">Valor Total</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @foreach ($this->inventories as $inventory)
                            <flux:table.row>
                                <flux:table.cell>
                                    <div class="flex flex-col">
                                        <span class="font-medium">{{ $inventory->warehouse->name }}</span>
                                        @if ($inventory->warehouse->warehouse_type === 'fractional')
                                            <flux:badge size="sm" color="purple">Fraccionaria</flux:badge>
                                        @endif
                                    </div>
                                </flux:table.cell>

                                <flux:table.cell>
                                    <span class="font-mono text-sm">{{ $inventory->product->sku }}</span>
                                </flux:table.cell>

                                <flux:table.cell>
                                    {{ $inventory->product->name }}
                                </flux:table.cell>

                                <flux:table.cell>
                                    {{ $inventory->product->category?->name ?? 'Sin categoría' }}
                                </flux:table.cell>

                                <flux:table.cell>
                                    {{ $inventory->storageLocation?->name ?? 'Sin ubicación' }}
                                </flux:table.cell>

                                <flux:table.cell class="text-right font-medium tabular-nums">
                                    {{ number_format($inventory->quantity, 2) }}
                                    <span class="ml-1 text-xs text-zinc-500">
                                        {{ $inventory->product->unitOfMeasure?->abbreviation ?? 'UND' }}
                                    </span>
                                </flux:table.cell>

                                <flux:table.cell class="text-right tabular-nums">
                                    ${{ number_format($inventory->product->cost ?? 0, 2) }}
                                </flux:table.cell>

                                <flux:table.cell class="text-right font-semibold tabular-nums">
                                    @php
                                        $totalValue = $inventory->quantity * ($inventory->product->cost ?? 0);
                                    @endphp
                                    ${{ number_format($totalValue, 2) }}
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            </div>

            {{-- Summary by Warehouse --}}
            @if ($this->summary['by_warehouse']->isNotEmpty())
                <div class="mt-6 border-t border-zinc-200 pt-6 dark:border-zinc-700">
                    <flux:heading size="md" class="mb-4">Resumen por Bodega</flux:heading>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                        @foreach ($this->summary['by_warehouse'] as $warehouseData)
                            <flux:card>
                                <flux:heading size="sm" class="mb-2">{{ $warehouseData['warehouse']->name }}</flux:heading>
                                <div class="space-y-1">
                                    <div class="flex justify-between">
                                        <flux:text class="text-sm">Cantidad:</flux:text>
                                        <flux:text class="text-sm font-medium">{{ number_format($warehouseData['quantity'], 2) }}</flux:text>
                                    </div>
                                    <div class="flex justify-between">
                                        <flux:text class="text-sm">Valor:</flux:text>
                                        <flux:text class="text-sm font-semibold text-green-600 dark:text-green-400">
                                            ${{ number_format($warehouseData['value'], 2) }}
                                        </flux:text>
                                    </div>
                                </div>
                            </flux:card>
                        @endforeach
                    </div>
                </div>
            @endif
        @endif
    </flux:card>
</div>
