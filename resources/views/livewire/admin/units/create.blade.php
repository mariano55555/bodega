<?php

use Livewire\Volt\Component;
use App\Models\UnitOfMeasure;
use App\Models\Company;
use App\Http\Requests\StoreUnitOfMeasureRequest;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;

new #[Layout('components.layouts.app')] class extends Component
{
    public $name = '';
    public $symbol = '';
    public $description = '';
    public $type = 'quantity';
    public $company_id = '';
    public $is_active = true;

    #[Computed]
    public function companies()
    {
        return Company::active()->orderBy('name')->get(['id', 'name']);
    }

    public function save(): void
    {
        $validated = $this->validate((new StoreUnitOfMeasureRequest())->rules(), (new StoreUnitOfMeasureRequest())->messages());

        UnitOfMeasure::create($validated);

        session()->flash('success', 'Unidad de medida creada exitosamente.');

        $this->redirect(route('admin.units.index'), navigate: true);
    }

    public function cancel(): void
    {
        $this->redirect(route('admin.units.index'), navigate: true);
    }

    public function with(): array
    {
        return [
            'title' => __('Crear Unidad de Medida'),
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
        <flux:heading size="xl">Crear Nueva Unidad de Medida</flux:heading>
        <flux:text class="mt-2">Agrega una unidad de medida para tus productos</flux:text>
    </div>

    <form wire:submit="save">
        <flux:card class="space-y-6">
            <flux:field>
                <flux:label>Nombre</flux:label>
                <flux:input wire:model="name" placeholder="Ej: Kilogramo" />
                <flux:error name="name" />
            </flux:field>

            <flux:field>
                <flux:label>Símbolo</flux:label>
                <flux:input wire:model="symbol" placeholder="Ej: kg" />
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
                <flux:textarea wire:model="description" rows="2" placeholder="Descripción..." />
                <flux:error name="description" />
            </flux:field>

            <flux:field>
                <flux:switch wire:model="is_active">
                    <flux:text>Unidad activa</flux:text>
                </flux:switch>
            </flux:field>

            <div class="flex justify-end gap-3 pt-4 border-t border-zinc-200 dark:border-zinc-700">
                <flux:button type="button" variant="ghost" wire:click="cancel">Cancelar</flux:button>
                <flux:button type="submit" variant="primary">Crear Unidad</flux:button>
            </div>
        </flux:card>
    </form>
</div>
