<?php
use Livewire\Volt\Component;
use App\Models\{Inventory, Warehouse, ProductCategory};
use Livewire\Attributes\{Computed, Layout};
use Livewire\WithPagination;

new #[Layout('components.layouts.app')] class extends Component {
    use WithPagination;
    public string $search = '';
    public string $warehouse = '';

    #[Computed]
    public function lowStockProducts() {
        $query = Inventory::query()->with(['product.category', 'warehouse'])->active()
            ->whereHas('product', function ($q) {
                $q->whereRaw('inventory.available_quantity <= products.minimum_stock');
            });
        if ($this->search) { $query->whereHas('product', fn($q) => $q->where('name', 'like', "%{$this->search}%")->orWhere('sku', 'like', "%{$this->search}%")); }
        if ($this->warehouse) { $query->where('warehouse_id', $this->warehouse); }
        return $query->orderBy('available_quantity')->paginate(20);
    }

    #[Computed]
    public function warehouses() { return Warehouse::active()->get(['id', 'name']); }

    public function clearFilters(): void { $this->search = ''; $this->warehouse = ''; $this->resetPage(); }
}; ?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-8">
        <flux:heading size="xl" class="text-zinc-900 dark:text-zinc-100">Productos con Stock Bajo</flux:heading>
        <flux:text class="mt-2 text-zinc-600 dark:text-zinc-400">Productos que han alcanzado su nivel mínimo de stock</flux:text>
    </div>

    <flux:card class="mb-6">
        <div class="space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <flux:input wire:model.live.debounce.300ms="search" placeholder="Buscar productos..." icon="magnifying-glass" />
                <flux:select wire:model.live="warehouse" placeholder="Todos los almacenes">
                    @foreach($this->warehouses as $w)
                    <flux:select.option value="{{ $w->id }}">{{ $w->name }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>
            @if($search || $warehouse)
            <div class="pt-4 border-t border-zinc-200 dark:border-zinc-700">
                <flux:button variant="ghost" size="sm" wire:click="clearFilters" icon="x-mark">Limpiar Filtros</flux:button>
            </div>
            @endif
        </div>
    </flux:card>

    <flux:card>
        <div class="overflow-x-auto">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Producto</flux:table.column>
                    <flux:table.column>Categoría</flux:table.column>
                    <flux:table.column>Almacén</flux:table.column>
                    <flux:table.column class="text-right">Stock Disponible</flux:table.column>
                    <flux:table.column class="text-right">Stock Mínimo</flux:table.column>
                    <flux:table.column class="text-right">Diferencia</flux:table.column>
                    <flux:table.column>Estado</flux:table.column>
                    <flux:table.column>Acciones</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @forelse($this->lowStockProducts as $item)
                    <flux:table.row wire:key="low-{{ $item->id }}">
                        <flux:table.cell>
                            <flux:text class="font-medium">{{ $item->product->name }}</flux:text>
                            <flux:text class="text-sm text-zinc-500">{{ $item->product->sku }}</flux:text>
                        </flux:table.cell>
                        <flux:table.cell>
                            @if($item->product->category)
                            <flux:badge color="blue" size="sm">{{ $item->product->category->name }}</flux:badge>
                            @else
                            <flux:text class="text-zinc-400 text-sm">Sin categoría</flux:text>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell><flux:text>{{ $item->warehouse->name }}</flux:text></flux:table.cell>
                        <flux:table.cell class="text-right">
                            <flux:text class="font-medium text-red-600">{{ number_format($item->available_quantity, 2) }}</flux:text>
                        </flux:table.cell>
                        <flux:table.cell class="text-right">
                            <flux:text class="font-medium">{{ number_format($item->product->minimum_stock ?? 0, 2) }}</flux:text>
                        </flux:table.cell>
                        <flux:table.cell class="text-right">
                            @php $diff = $item->available_quantity - ($item->product->minimum_stock ?? 0); @endphp
                            <flux:text class="font-medium {{ $diff < 0 ? 'text-red-600' : 'text-yellow-600' }}">
                                {{ number_format($diff, 2) }}
                            </flux:text>
                        </flux:table.cell>
                        <flux:table.cell>
                            @if($item->available_quantity <= 0)
                            <flux:badge color="red" size="sm">Sin Stock</flux:badge>
                            @elseif($item->available_quantity < ($item->product->minimum_stock ?? 0))
                            <flux:badge color="red" size="sm">Crítico</flux:badge>
                            @else
                            <flux:badge color="yellow" size="sm">Bajo</flux:badge>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:button variant="ghost" size="sm" href="{{ route('purchases.create') }}" wire:navigate>
                                Crear Compra
                            </flux:button>
                        </flux:table.cell>
                    </flux:table.row>
                    @empty
                    <flux:table.row>
                        <flux:table.cell colspan="8" class="text-center py-12">
                            <flux:icon name="check-circle" class="h-12 w-12 text-green-400 mx-auto mb-3" />
                            <flux:text class="text-zinc-500">No hay productos con stock bajo</flux:text>
                        </flux:table.cell>
                    </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>
        @if($this->lowStockProducts->hasPages())
        <div class="mt-6 px-6 pb-6">{{ $this->lowStockProducts->links() }}</div>
        @endif
    </flux:card>
</div>
