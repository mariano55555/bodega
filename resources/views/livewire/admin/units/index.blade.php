<?php

use Livewire\Volt\Component;
use App\Models\UnitOfMeasure;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\WithPagination;

new #[Layout('components.layouts.app')] class extends Component
{
    use WithPagination;

    public string $search = '';
    public string $typeFilter = '';

    #[Computed]
    public function units()
    {
        return UnitOfMeasure::query()
            ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%")
                ->orWhere('symbol', 'like', "%{$this->search}%"))
            ->when($this->typeFilter, fn($q) => $q->where('type', $this->typeFilter))
            ->withCount('products')
            ->orderBy('type')
            ->orderBy('name')
            ->paginate(15);
    }

    public function delete(UnitOfMeasure $unit): void
    {
        if ($unit->products()->exists()) {
            session()->flash('error', 'No se puede eliminar la unidad porque tiene productos asociados.');
            return;
        }

        $unit->delete();
        session()->flash('success', 'Unidad de medida eliminada exitosamente.');
    }

    public function getTypeLabel(string $type): string
    {
        return match($type) {
            'weight' => 'Peso',
            'volume' => 'Volumen',
            'length' => 'Longitud',
            'area' => 'Área',
            'quantity' => 'Cantidad',
            'time' => 'Tiempo',
            'temperature' => 'Temperatura',
            default => ucfirst($type),
        };
    }

    public function with(): array
    {
        return [
            'title' => __('Unidades de Medida'),
        ];
    }
}; ?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-8">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <flux:heading size="xl">Unidades de Medida</flux:heading>
                <flux:text class="mt-2">Gestiona las unidades de medida para tus productos</flux:text>
            </div>
            <flux:button variant="primary" icon="plus" href="{{ route('admin.units.create') }}" wire:navigate>
                Nueva Unidad
            </flux:button>
        </div>
    </div>

    <flux:card>
        <div class="mb-6 grid grid-cols-1 md:grid-cols-2 gap-4">
            <flux:input wire:model.live.debounce.300ms="search"
                       placeholder="Buscar unidades..."
                       icon="magnifying-glass" />

            <flux:select wire:model.live="typeFilter" placeholder="Todos los tipos">
                <flux:select.option value="weight">Peso</flux:select.option>
                <flux:select.option value="volume">Volumen</flux:select.option>
                <flux:select.option value="length">Longitud</flux:select.option>
                <flux:select.option value="area">Área</flux:select.option>
                <flux:select.option value="quantity">Cantidad</flux:select.option>
                <flux:select.option value="time">Tiempo</flux:select.option>
                <flux:select.option value="temperature">Temperatura</flux:select.option>
            </flux:select>
        </div>

        <flux:table>
            <flux:table.columns>
                <flux:table.column>Nombre</flux:table.column>
                <flux:table.column>Símbolo</flux:table.column>
                <flux:table.column>Tipo</flux:table.column>
                <flux:table.column>Productos</flux:table.column>
                <flux:table.column>Estado</flux:table.column>
                <flux:table.column>Acciones</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse($this->units as $unit)
                <flux:table.row wire:key="unit-{{ $unit->id }}">
                    <flux:table.cell>
                        <flux:text class="font-medium">{{ $unit->name }}</flux:text>
                        @if($unit->description)
                        <flux:text class="text-sm text-zinc-500 block">{{ Str::limit($unit->description, 40) }}</flux:text>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell>
                        <flux:badge color="blue">{{ $unit->symbol }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        <flux:text>{{ $this->getTypeLabel($unit->type) }}</flux:text>
                    </flux:table.cell>
                    <flux:table.cell>
                        <flux:text>{{ $unit->products_count }} productos</flux:text>
                    </flux:table.cell>
                    <flux:table.cell>
                        @if($unit->is_active)
                        <flux:badge color="green">Activo</flux:badge>
                        @else
                        <flux:badge color="red">Inactivo</flux:badge>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex items-center gap-2">
                            <flux:button variant="ghost" size="sm" icon="pencil" href="{{ route('admin.units.edit', $unit->slug) }}" wire:navigate />
                            <flux:button variant="ghost" size="sm" icon="trash" wire:click="delete({{ $unit->id }})" wire:confirm="¿Estás seguro de eliminar esta unidad?" />
                        </div>
                    </flux:table.cell>
                </flux:table.row>
                @empty
                <flux:table.row>
                    <flux:table.cell colspan="6" class="text-center py-12">
                        <flux:text class="text-zinc-500">No se encontraron unidades de medida</flux:text>
                    </flux:table.cell>
                </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>

        @if($this->units->hasPages())
        <div class="mt-6">
            {{ $this->units->links() }}
        </div>
        @endif
    </flux:card>
</div>
