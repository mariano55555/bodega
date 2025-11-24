<?php
use Livewire\Volt\Component;
use App\Models\InventoryAlert;
use Livewire\Attributes\{Computed, Layout};
use Livewire\WithPagination;

new #[Layout('components.layouts.app')] class extends Component {
    use WithPagination;
    public string $search = '';
    public string $alertType = '';
    public string $priority = '';
    public string $dateFrom = '';
    public string $dateTo = '';

    public function mount(): void {
        $this->dateFrom = now()->subMonth()->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
    }

    #[Computed]
    public function resolvedAlerts() {
        $query = InventoryAlert::query()->with(['product', 'warehouse'])->where('is_resolved', true);
        if ($this->search) { $query->where(function ($q) { $q->where('message', 'like', "%{$this->search}%")->orWhereHas('product', fn($q) => $q->where('name', 'like', "%{$this->search}%"))->orWhereHas('warehouse', fn($q) => $q->where('name', 'like', "%{$this->search}%")); }); }
        if ($this->alertType) { $query->where('alert_type', $this->alertType); }
        if ($this->priority) { $query->where('priority', $this->priority); }
        if ($this->dateFrom) { $query->whereDate('resolved_at', '>=', $this->dateFrom); }
        if ($this->dateTo) { $query->whereDate('resolved_at', '<=', $this->dateTo); }
        return $query->orderBy('resolved_at', 'desc')->paginate(25);
    }

    #[Computed]
    public function summary() {
        $alerts = InventoryAlert::where('is_resolved', true)
            ->when($this->dateFrom, fn($q) => $q->whereDate('resolved_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn($q) => $q->whereDate('resolved_at', '<=', $this->dateTo))
            ->get();
        return ['total' => $alerts->count(), 'auto_resolved' => $alerts->where('resolution_notes', 'like', 'Auto-resuelto%')->count(), 'manual_resolved' => $alerts->where('resolution_notes', 'not like', 'Auto-resuelto%')->whereNotNull('resolution_notes')->count()];
    }

    public function clearFilters(): void { $this->search = ''; $this->alertType = ''; $this->priority = ''; $this->dateFrom = now()->subMonth()->format('Y-m-d'); $this->dateTo = now()->format('Y-m-d'); $this->resetPage(); }
}; ?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-8">
        <flux:heading size="xl" class="text-zinc-900 dark:text-zinc-100">Historial de Alertas Resueltas</flux:heading>
        <flux:text class="mt-2 text-zinc-600 dark:text-zinc-400">Registro de todas las alertas que han sido resueltas</flux:text>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <flux:card class="bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-800/20">
            <div class="flex items-center justify-between">
                <div><flux:text class="text-sm font-medium text-green-600">Total Resueltas</flux:text><flux:heading size="2xl" class="text-green-900">{{ number_format($this->summary['total']) }}</flux:heading></div>
                <flux:icon name="check-circle" class="h-8 w-8 text-green-500" />
            </div>
        </flux:card>
        <flux:card class="bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20">
            <div class="flex items-center justify-between">
                <div><flux:text class="text-sm font-medium text-blue-600">Auto-Resueltas</flux:text><flux:heading size="2xl" class="text-blue-900">{{ number_format($this->summary['auto_resolved']) }}</flux:heading></div>
                <flux:icon name="cog" class="h-8 w-8 text-blue-500" />
            </div>
        </flux:card>
        <flux:card class="bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-900/20 dark:to-purple-800/20">
            <div class="flex items-center justify-between">
                <div><flux:text class="text-sm font-medium text-purple-600">Manual</flux:text><flux:heading size="2xl" class="text-purple-900">{{ number_format($this->summary['manual_resolved']) }}</flux:heading></div>
                <flux:icon name="user" class="h-8 w-8 text-purple-500" />
            </div>
        </flux:card>
    </div>

    <flux:card class="mb-6">
        <div class="space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <flux:input wire:model.live.debounce.300ms="search" placeholder="Buscar..." icon="magnifying-glass" />
                <flux:select wire:model.live="alertType" placeholder="Todos los tipos">
                    <flux:select.option value="low_stock">Stock Bajo</flux:select.option>
                    <flux:select.option value="out_of_stock">Sin Stock</flux:select.option>
                    <flux:select.option value="expiring_soon">Próximo a Vencer</flux:select.option>
                    <flux:select.option value="expired">Vencido</flux:select.option>
                    <flux:select.option value="stock_overflow">Intento Salida Excesiva</flux:select.option>
                    <flux:select.option value="closed_period">Período Cerrado</flux:select.option>
                </flux:select>
                <flux:select wire:model.live="priority" placeholder="Todas las prioridades">
                    <flux:select.option value="critical">Crítica</flux:select.option>
                    <flux:select.option value="high">Alta</flux:select.option>
                    <flux:select.option value="medium">Media</flux:select.option>
                    <flux:select.option value="low">Baja</flux:select.option>
                </flux:select>
                <flux:input type="date" wire:model.live="dateFrom" placeholder="Desde" />
                <flux:input type="date" wire:model.live="dateTo" placeholder="Hasta" />
            </div>
            @if($search || $alertType || $priority)
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
                    <flux:table.column>Tipo</flux:table.column>
                    <flux:table.column>Prioridad</flux:table.column>
                    <flux:table.column>Producto/Almacén</flux:table.column>
                    <flux:table.column>Mensaje</flux:table.column>
                    <flux:table.column>Fecha Resolución</flux:table.column>
                    <flux:table.column>Notas</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @forelse($this->resolvedAlerts as $alert)
                    <flux:table.row wire:key="alert-{{ $alert->id }}">
                        <flux:table.cell>
                            @php
                            $typeLabels = ['low_stock' => 'Stock Bajo', 'out_of_stock' => 'Sin Stock', 'expiring_soon' => 'Próximo a Vencer', 'expired' => 'Vencido', 'stock_overflow' => 'Salida Excesiva', 'closed_period' => 'Período Cerrado'];
                            $typeColors = ['low_stock' => 'yellow', 'out_of_stock' => 'red', 'expiring_soon' => 'orange', 'expired' => 'red', 'stock_overflow' => 'red', 'closed_period' => 'purple'];
                            @endphp
                            <flux:badge color="{{ $typeColors[$alert->alert_type] ?? 'zinc' }}" size="sm">{{ $typeLabels[$alert->alert_type] ?? $alert->alert_type }}</flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>
                            @php $priorityColors = ['critical' => 'red', 'high' => 'orange', 'medium' => 'yellow', 'low' => 'blue']; @endphp
                            <flux:badge color="{{ $priorityColors[$alert->priority] ?? 'zinc' }}" size="sm">{{ ucfirst($alert->priority) }}</flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>
                            @if($alert->product)
                            <flux:text class="font-medium">{{ $alert->product->name }}</flux:text>
                            <flux:text class="text-sm text-zinc-500 block">{{ $alert->product->sku }}</flux:text>
                            @endif
                            @if($alert->warehouse)
                            <flux:text class="text-sm text-zinc-600">{{ $alert->warehouse->name }}</flux:text>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell><flux:text class="text-sm">{{ Str::limit($alert->message, 60) }}</flux:text></flux:table.cell>
                        <flux:table.cell>
                            <flux:text class="font-medium">{{ $alert->resolved_at->format('d/m/Y') }}</flux:text>
                            <flux:text class="text-sm text-zinc-500 block">{{ $alert->resolved_at->diffForHumans() }}</flux:text>
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:text class="text-sm text-zinc-600">{{ $alert->resolution_notes ? Str::limit($alert->resolution_notes, 40) : '-' }}</flux:text>
                        </flux:table.cell>
                    </flux:table.row>
                    @empty
                    <flux:table.row>
                        <flux:table.cell colspan="6" class="text-center py-12">
                            <flux:icon name="check-circle" class="h-12 w-12 text-zinc-400 mx-auto mb-3" />
                            <flux:text class="text-zinc-500">No se encontraron alertas resueltas</flux:text>
                        </flux:table.cell>
                    </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>
        @if($this->resolvedAlerts->hasPages())
        <div class="mt-6 px-6 pb-6">{{ $this->resolvedAlerts->links() }}</div>
        @endif
    </flux:card>
</div>
