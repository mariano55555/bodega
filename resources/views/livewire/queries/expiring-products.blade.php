<?php
use Livewire\Volt\Component;
use App\Models\{Inventory, Warehouse, ProductCategory};
use Livewire\Attributes\{Computed, Layout};
use Livewire\WithPagination;

new #[Layout('components.layouts.app')] class extends Component {
    use WithPagination;
    public string $search = '';
    public string $warehouse = '';
    public int $daysAhead = 30;

    #[Computed]
    public function expiringProducts() {
        $query = Inventory::query()->with(['product', 'warehouse'])->active()->expiringSoon($this->daysAhead);
        if ($this->search) { $query->whereHas('product', fn($q) => $q->where('name', 'like', "%{$this->search}%")->orWhere('sku', 'like', "%{$this->search}%")); }
        if ($this->warehouse) { $query->where('warehouse_id', $this->warehouse); }
        return $query->orderBy('expiration_date')->paginate(20);
    }

    #[Computed]
    public function warehouses() { return Warehouse::active()->get(['id', 'name']); }

    public function clearFilters(): void { $this->search = ''; $this->warehouse = ''; $this->daysAhead = 30; $this->resetPage(); }
}; ?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-8">
        <flux:heading size="xl" class="text-zinc-900 dark:text-zinc-100">Productos Próximos a Vencer</flux:heading>
        <flux:text class="mt-2 text-zinc-600 dark:text-zinc-400">Monitorea productos que están por vencer</flux:text>
    </div>

    <flux:card class="mb-6">
        <div class="space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <flux:input wire:model.live.debounce.300ms="search" placeholder="Buscar productos..." icon="magnifying-glass" />
                <flux:select wire:model.live="warehouse" placeholder="Todos los almacenes">
                    @foreach($this->warehouses as $w)
                    <flux:select.option value="{{ $w->id }}">{{ $w->name }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:select wire:model.live="daysAhead">
                    <flux:select.option value="7">Próximos 7 días</flux:select.option>
                    <flux:select.option value="15">Próximos 15 días</flux:select.option>
                    <flux:select.option value="30">Próximos 30 días</flux:select.option>
                    <flux:select.option value="60">Próximos 60 días</flux:select.option>
                </flux:select>
            </div>
            @if($search || $warehouse || $daysAhead != 30)
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
                    <flux:table.column>Lote</flux:table.column>
                    <flux:table.column>Almacén</flux:table.column>
                    <flux:table.column class="text-right">Cantidad</flux:table.column>
                    <flux:table.column>Fecha de Vencimiento</flux:table.column>
                    <flux:table.column>Días Restantes</flux:table.column>
                    <flux:table.column>Estado</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @forelse($this->expiringProducts as $item)
                    <flux:table.row wire:key="exp-{{ $item->id }}">
                        <flux:table.cell>
                            <flux:text class="font-medium">{{ $item->product->name }}</flux:text>
                            <flux:text class="text-sm text-zinc-500">{{ $item->product->sku }}</flux:text>
                        </flux:table.cell>
                        <flux:table.cell><flux:text>{{ $item->lot_number ?? 'N/A' }}</flux:text></flux:table.cell>
                        <flux:table.cell><flux:text>{{ $item->warehouse->name }}</flux:text></flux:table.cell>
                        <flux:table.cell class="text-right"><flux:text class="font-medium">{{ number_format($item->available_quantity, 2) }}</flux:text></flux:table.cell>
                        <flux:table.cell><flux:text>{{ $item->expiration_date->format('d/m/Y') }}</flux:text></flux:table.cell>
                        <flux:table.cell>
                            @php $days = now()->diffInDays($item->expiration_date, false); @endphp
                            <flux:text class="{{ $days < 0 ? 'text-red-600 font-bold' : ($days <= 7 ? 'text-red-600' : 'text-yellow-600') }}">
                                {{ $days < 0 ? 'Vencido' : $days . ' días' }}
                            </flux:text>
                        </flux:table.cell>
                        <flux:table.cell>
                            @if($item->isExpired())
                            <flux:badge color="red" size="sm">Vencido</flux:badge>
                            @elseif($item->isExpiringSoon(7))
                            <flux:badge color="red" size="sm">Urgente</flux:badge>
                            @else
                            <flux:badge color="yellow" size="sm">Próximo a Vencer</flux:badge>
                            @endif
                        </flux:table.cell>
                    </flux:table.row>
                    @empty
                    <flux:table.row>
                        <flux:table.cell colspan="7" class="text-center py-12">
                            <flux:icon name="check-circle" class="h-12 w-12 text-green-400 mx-auto mb-3" />
                            <flux:text class="text-zinc-500">No hay productos próximos a vencer</flux:text>
                        </flux:table.cell>
                    </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>
        @if($this->expiringProducts->hasPages())
        <div class="mt-6 px-6 pb-6">{{ $this->expiringProducts->links() }}</div>
        @endif
    </flux:card>
</div>
