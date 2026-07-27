<?php

use App\Models\Product;
use App\Models\ProductCategory;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('components.layouts.app')] class extends Component
{
    use WithPagination;

    public string $search = '';

    public string $company = '';

    public string $category = '';

    public string $subcategory = '';

    public string $statusFilter = '';

    public int $perPage = 15;

    public function mount(): void
    {
        if (! auth()->user()->isSuperAdmin()) {
            $this->company = (string) auth()->user()->company_id;
        }
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedCompany(): void
    {
        $this->category = '';
        $this->subcategory = '';
        unset($this->subcategories);
        $this->resetPage();
    }

    public function updatedCategory(): void
    {
        $this->subcategory = '';
        unset($this->subcategories);
        $this->resetPage();
    }

    public function updatedSubcategory(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function products()
    {
        $query = Product::query()
            ->with(['category', 'unitOfMeasure', 'company'])
            ->when(! auth()->user()->isSuperAdmin(), function ($q) {
                $q->where('company_id', auth()->user()->company_id);
            })
            ->when($this->company, function ($q) {
                $q->where('company_id', $this->company);
            })
            ->when($this->search, function ($q) {
                $q->where(function ($query) {
                    $query->where('name', 'like', "%{$this->search}%")
                        ->orWhere('sku', 'like', "%{$this->search}%")
                        ->orWhere('barcode', 'like', "%{$this->search}%");
                });
            })
            ->when($this->subcategory, function ($q) {
                $q->where('category_id', $this->subcategory);
            }, function ($q) {
                $q->when($this->category, function ($q2) {
                    $q2->where(function ($query) {
                        $query->where('category_id', $this->category)
                            ->orWhereHas('category', function ($catQuery) {
                                $catQuery->where('parent_id', $this->category);
                            });
                    });
                });
            })
            ->when($this->statusFilter !== '', function ($q) {
                if ($this->statusFilter === 'active') {
                    $q->active();
                } elseif ($this->statusFilter === 'inactive') {
                    $q->where(function ($query) {
                        $query->where('is_active', false)->orWhereNull('active_at');
                    });
                }
            });

        return $query->latest()->paginate($this->perPage);
    }

    #[Computed]
    public function companies()
    {
        return \App\Models\Company::where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function categories()
    {
        $query = ProductCategory::active()->parents();

        if ($this->company) {
            $query->where('company_id', $this->company);
        }

        return $query->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function subcategories()
    {
        if (! $this->category) {
            return collect([]);
        }

        return ProductCategory::active()
            ->where('parent_id', $this->category)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function clearFilters(): void
    {
        $this->search = '';
        if (auth()->user()->isSuperAdmin()) {
            $this->company = '';
        }
        $this->category = '';
        $this->subcategory = '';
        $this->statusFilter = '';
        unset($this->subcategories);
        $this->resetPage();
    }
}; ?>

<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <flux:heading size="xl">Catálogo de Productos</flux:heading>
            <flux:text class="mt-1">Listado maestro de todos los productos registrados</flux:text>
        </div>

        @can('products.create')
        <flux:button variant="primary" icon="plus" href="{{ route('inventory.products.create') }}" wire:navigate>
            Nuevo Producto
        </flux:button>
        @endcan
    </div>

    <!-- Filters -->
    <flux:card>
        <div class="space-y-4">
            @if (auth()->user()->isSuperAdmin())
                <div>
                    <flux:field>
                        <flux:label>Empresa</flux:label>
                        <flux:select variant="listbox" searchable wire:model.live="company" placeholder="Todas las empresas">
                            <flux:select.option value="">Todas las empresas</flux:select.option>
                            @foreach($this->companies as $comp)
                                <flux:select.option value="{{ $comp->id }}">{{ $comp->name }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    </flux:field>
                </div>
            @endif

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                <div>
                    <flux:input
                        wire:model.live.debounce.300ms="search"
                        placeholder="Buscar por nombre, SKU, código de barras..."
                        icon="magnifying-glass"
                    />
                </div>

                <div>
                    <flux:select variant="listbox" searchable wire:model.live="category" placeholder="Todas las categorías">
                        @foreach($this->categories as $cat)
                            <flux:select.option value="{{ $cat->id }}">{{ $cat->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                <div>
                    <flux:select variant="listbox" searchable wire:model.live="subcategory" placeholder="Todas las subcategorías" :disabled="!$category">
                        @foreach($this->subcategories as $subcat)
                            <flux:select.option value="{{ $subcat->id }}">{{ $subcat->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                <div>
                    <flux:select variant="listbox" wire:model.live="statusFilter" placeholder="Todos los estados">
                        <flux:select.option value="active">Activos</flux:select.option>
                        <flux:select.option value="inactive">Inactivos</flux:select.option>
                    </flux:select>
                </div>

                <div>
                    <flux:select wire:model.live="perPage" class="w-full">
                        <option value="15">15 por página</option>
                        <option value="25">25 por página</option>
                        <option value="50">50 por página</option>
                        <option value="100">100 por página</option>
                    </flux:select>
                </div>
            </div>

            @if($search || ($company && auth()->user()->isSuperAdmin()) || $category || $subcategory || $statusFilter !== '')
                <div class="pt-4 border-t border-zinc-200 dark:border-zinc-700">
                    <flux:button variant="ghost" size="sm" wire:click="clearFilters">
                        Limpiar filtros
                    </flux:button>
                </div>
            @endif
        </div>
    </flux:card>

    <!-- Stats -->
    <div class="flex items-center justify-between">
        <flux:text class="text-sm">
            Mostrando {{ $this->products->firstItem() ?? 0 }} - {{ $this->products->lastItem() ?? 0 }} de {{ $this->products->total() }} productos
        </flux:text>
    </div>

    <!-- Products Table -->
    <flux:card>
        <div class="overflow-x-auto">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>SKU</flux:table.column>
                    <flux:table.column>Producto</flux:table.column>
                    <flux:table.column>Categoría</flux:table.column>
                    <flux:table.column>Unidad</flux:table.column>
                    <flux:table.column>Costo</flux:table.column>
                    <flux:table.column>Stock Mín.</flux:table.column>
                    <flux:table.column>Estado</flux:table.column>
                    <flux:table.column>Acciones</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse($this->products as $product)
                        <flux:table.row wire:key="product-{{ $product->id }}">
                            <flux:table.cell>
                                <flux:text class="font-mono text-sm">{{ $product->sku }}</flux:text>
                            </flux:table.cell>

                            <flux:table.cell>
                                <div class="min-w-0">
                                    <flux:text class="font-medium text-zinc-900 dark:text-zinc-100">
                                        {{ $product->name }}
                                    </flux:text>
                                    @if($product->barcode)
                                        <flux:text class="text-xs text-zinc-500">
                                            {{ $product->barcode }}
                                        </flux:text>
                                    @endif
                                </div>
                            </flux:table.cell>

                            <flux:table.cell>
                                @if($product->category)
                                    <flux:badge color="blue" size="sm">
                                        {{ $product->category->name }}
                                    </flux:badge>
                                @else
                                    <flux:text class="text-zinc-400 text-xs">Sin categoría</flux:text>
                                @endif
                            </flux:table.cell>

                            <flux:table.cell>
                                {{ $product->unitOfMeasure?->abbreviation ?? $product->unitOfMeasure?->name ?? '-' }}
                            </flux:table.cell>

                            <flux:table.cell>
                                <flux:text class="text-right">${{ number_format($product->cost ?? 0, 2) }}</flux:text>
                            </flux:table.cell>

                            <flux:table.cell>
                                <flux:text>{{ $product->minimum_stock ? number_format($product->minimum_stock, 2) : '-' }}</flux:text>
                            </flux:table.cell>

                            <flux:table.cell>
                                <flux:badge :color="$product->is_active ? 'green' : 'red'" size="sm">
                                    {{ $product->is_active ? 'Activo' : 'Inactivo' }}
                                </flux:badge>
                            </flux:table.cell>

                            <flux:table.cell>
                                <div class="flex items-center gap-1">
                                    <flux:button
                                        variant="ghost"
                                        size="sm"
                                        icon="eye"
                                        href="{{ route('inventory.products.show', $product) }}"
                                        wire:navigate
                                        title="Ver detalle"
                                    />
                                    @can('products.edit')
                                    <flux:button
                                        variant="ghost"
                                        size="sm"
                                        icon="pencil-square"
                                        href="{{ route('inventory.products.edit', $product) }}"
                                        wire:navigate
                                        title="Editar"
                                    />
                                    @endcan
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="8" class="text-center py-12">
                                <flux:icon name="cube" class="h-12 w-12 text-zinc-400 mx-auto mb-3" />
                                <flux:text class="text-zinc-500">No se encontraron productos.</flux:text>
                                <flux:text class="text-sm text-zinc-400 mt-2">
                                    Intente cambiar los filtros o cree un nuevo producto.
                                </flux:text>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>

        @if($this->products->hasPages())
            <div class="mt-6 px-6 pb-6">
                {{ $this->products->links() }}
            </div>
        @endif
    </flux:card>
</div>
