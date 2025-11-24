<?php

use Livewire\Volt\Component;
use App\Models\ProductCategory;
use App\Http\Requests\UpdateProductCategoryRequest;
use Livewire\Attributes\Layout;

new #[Layout('components.layouts.app')] class extends Component
{
    public ProductCategory $category;
    public $name;
    public $code;
    public $description;
    public $is_active;

    public function mount(ProductCategory $category): void
    {
        $this->category = $category;
        $this->fill($category->only(['name', 'code', 'description', 'is_active']));
    }

    public function update(): void
    {
        $validated = $this->validate((new UpdateProductCategoryRequest())->rules(), (new UpdateProductCategoryRequest())->messages());

        $this->category->update($validated);

        session()->flash('success', 'Categoría actualizada exitosamente.');

        $this->redirect(route('admin.categories.index'), navigate: true);
    }

    public function cancel(): void
    {
        $this->redirect(route('admin.categories.index'), navigate: true);
    }

    public function with(): array
    {
        return [
            'title' => __('Editar Categoría'),
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
        <flux:heading size="xl">Editar Categoría</flux:heading>
        <flux:text class="mt-2">Modifica la información de {{ $category->name }}</flux:text>
    </div>

    <form wire:submit="update">
        <flux:card class="space-y-6">
            <!-- Nombre -->
            <flux:field>
                <flux:label>Nombre de la Categoría</flux:label>
                <flux:input wire:model="name" placeholder="Ej: Alimentos para Ganado" />
                <flux:error name="name" />
            </flux:field>

            <!-- Código -->
            <flux:field>
                <flux:label>Código (Opcional)</flux:label>
                <flux:input wire:model="code" placeholder="Ej: ALM-GAN" description="Código único para identificar la categoría internamente" />
                <flux:error name="code" />
            </flux:field>

            <!-- Descripción -->
            <flux:field>
                <flux:label>Descripción (Opcional)</flux:label>
                <flux:textarea wire:model="description" rows="3" placeholder="Descripción de la categoría..." />
                <flux:error name="description" />
            </flux:field>

            <!-- Estado -->
            <flux:switch wire:model="is_active" description="Las categorías inactivas no estarán disponibles para nuevos productos">
                <flux:text>Categoría activa</flux:text>
            </flux:switch>

            <!-- Información adicional -->
            <div class="pt-4 border-t border-zinc-200 dark:border-zinc-700">
                <flux:text class="text-sm text-zinc-500">
                    <strong>Productos asociados:</strong> {{ $category->products()->count() }}
                </flux:text>
            </div>

            <!-- Botones -->
            <div class="flex justify-end gap-3 pt-4 border-t border-zinc-200 dark:border-zinc-700">
                <flux:button type="button" variant="ghost" wire:click="cancel">
                    Cancelar
                </flux:button>
                <flux:button type="submit" variant="primary">
                    Guardar Cambios
                </flux:button>
            </div>
        </flux:card>
    </form>
</div>
