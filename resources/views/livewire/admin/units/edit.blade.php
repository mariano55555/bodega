<?php

use Livewire\Volt\Component;
use App\Models\UnitOfMeasure;
use App\Http\Requests\UpdateUnitOfMeasureRequest;
use Livewire\Attributes\Layout;

new #[Layout('components.layouts.app')] class extends Component
{
    public UnitOfMeasure $unit;
    public $name;
    public $symbol;
    public $description;
    public $type;
    public $is_active;

    public function mount(UnitOfMeasure $unit): void
    {
        $this->unit = $unit;
        $this->fill($unit->only(['name', 'symbol', 'description', 'type', 'is_active']));
    }

    public function update(): void
    {
        $validated = $this->validate((new UpdateUnitOfMeasureRequest())->rules(), (new UpdateUnitOfMeasureRequest())->messages());

        $this->unit->update($validated);

        session()->flash('success', 'Unidad de medida actualizada exitosamente.');

        $this->redirect(route('admin.units.index'), navigate: true);
    }

    public function cancel(): void
    {
        $this->redirect(route('admin.units.index'), navigate: true);
    }

    public function with(): array
    {
        return [
            'title' => __('Editar Unidad de Medida'),
        ];
    }
}; ?>

<div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-8">
        <div class="flex items-center gap-4 mb-4">
            <flux:button variant="ghost" size="sm" icon="arrow-left" wire:click="cancel">
                Volver
            </flux:button>
        </div>
        <flux:heading size="xl">Editar Unidad de Medida</flux:heading>
        <flux:text class="mt-2">Modifica {{ $unit->name }}</flux:text>
    </div>

    <form wire:submit="update">
        <flux:card class="space-y-6">
            <flux:field>
                <flux:label>Nombre</flux:label>
                <flux:input wire:model="name" />
                <flux:error name="name" />
            </flux:field>

            <flux:field>
                <flux:label>Símbolo</flux:label>
                <flux:input wire:model="symbol" />
                <flux:error name="symbol" />
            </flux:field>

            <flux:field>
                <flux:label>Tipo de Unidad</flux:label>
                <flux:select wire:model="type">
                    <flux:select.option value="weight">Peso</flux:select.option>
                    <flux:select.option value="volume">Volumen</flux:select.option>
                    <flux:select.option value="length">Longitud</flux:select.option>
                    <flux:select.option value="area">Área</flux:select.option>
                    <flux:select.option value="quantity">Cantidad</flux:select.option>
                    <flux:select.option value="time">Tiempo</flux:select.option>
                    <flux:select.option value="temperature">Temperatura</flux:select.option>
                </flux:select>
                <flux:error name="type" />
            </flux:field>

            <flux:field>
                <flux:label>Descripción (Opcional)</flux:label>
                <flux:textarea wire:model="description" rows="2" />
                <flux:error name="description" />
            </flux:field>

            <flux:field>
                <flux:switch wire:model="is_active">
                    <flux:text>Unidad activa</flux:text>
                </flux:switch>
            </flux:field>

            <div class="pt-4 border-t border-zinc-200 dark:border-zinc-700">
                <flux:text class="text-sm text-zinc-500">
                    <strong>Productos asociados:</strong> {{ $unit->products()->count() }}
                </flux:text>
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t border-zinc-200 dark:border-zinc-700">
                <flux:button type="button" variant="ghost" wire:click="cancel">Cancelar</flux:button>
                <flux:button type="submit" variant="primary">Guardar Cambios</flux:button>
            </div>
        </flux:card>
    </form>
</div>
