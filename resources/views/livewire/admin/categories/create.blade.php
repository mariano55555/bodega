<?php

use Livewire\Volt\Component;
use App\Models\ProductCategory;
use App\Http\Requests\StoreProductCategoryRequest;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;

new #[Layout('components.layouts.app')] class extends Component
{
    public $name = '';
    public $code = '';
    public $legacy_code = '';
    public $parent_id = '';
    public $description = '';
    public $is_active = true;

    #[Computed]
    public function parentCategories()
    {
        return ProductCategory::active()
            ->parents()
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function save(): void
    {
        $validated = $this->validate((new StoreProductCategoryRequest())->rules(), (new StoreProductCategoryRequest())->messages());

        // Convert empty string to null for parent_id
        if (empty($validated['parent_id'])) {
            $validated['parent_id'] = null;
        }

        ProductCategory::create($validated);

        session()->flash('success', 'Categoría creada exitosamente.');

        $this->redirect(route('admin.categories.index'), navigate: true);
    }

    public function cancel(): void
    {
        $this->redirect(route('admin.categories.index'), navigate: true);
    }

    public function with(): array
    {
        return [
            'title' => __('Crear Categoría'),
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
        <flux:heading size="xl">Crear Nueva Categoría</flux:heading>
        <flux:text class="mt-2">Agrega una categoría para organizar tus productos</flux:text>
    </div>

    <form wire:submit="save">
        <flux:card class="space-y-6">
            <!-- Categoría Padre -->
            <flux:field>
                <flux:label>Categoría Padre (Opcional)</flux:label>
                <flux:select wire:model="parent_id" placeholder="Seleccione una categoría padre">
                    <flux:select.option value="">Sin categoría padre (es categoría principal)</flux:select.option>
                    @foreach($this->parentCategories as $category)
                        <flux:select.option value="{{ $category->id }}">{{ $category->name }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:description>Si selecciona una categoría padre, esta será una subcategoría</flux:description>
                <flux:error name="parent_id" />
            </flux:field>

            <!-- Nombre -->
            <flux:field>
                <flux:label badge="Requerido">Nombre de la Categoría</flux:label>
                <flux:input wire:model="name" placeholder="Ej: Productos Agropecuarios" />
                <flux:error name="name" />
            </flux:field>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Código -->
                <flux:field>
                    <flux:label>Código (Opcional)</flux:label>
                    <flux:input wire:model="code" placeholder="Ej: PROD-AGRO" />
                    <flux:description>Código único interno</flux:description>
                    <flux:error name="code" />
                </flux:field>

                <!-- Código Legacy -->
                <flux:field>
                    <flux:label>Código Legacy (Opcional)</flux:label>
                    <flux:input wire:model="legacy_code" placeholder="Ej: 54102" />
                    <flux:description>Código del sistema anterior</flux:description>
                    <flux:error name="legacy_code" />
                </flux:field>
            </div>

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

            <!-- Botones -->
            <div class="flex justify-end gap-3 pt-4 border-t border-zinc-200 dark:border-zinc-700">
                <flux:button type="button" variant="ghost" wire:click="cancel">
                    Cancelar
                </flux:button>
                <flux:button type="submit" variant="primary">
                    Crear Categoría
                </flux:button>
            </div>
        </flux:card>
    </form>
</div>
