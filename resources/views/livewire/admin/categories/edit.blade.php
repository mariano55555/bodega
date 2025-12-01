<?php

use Livewire\Volt\Component;
use App\Models\ProductCategory;
use App\Http\Requests\UpdateProductCategoryRequest;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;

new #[Layout('components.layouts.app')] class extends Component
{
    public ProductCategory $category;
    public $name;
    public $code;
    public $legacy_code;
    public $parent_id;
    public $description;
    public $is_active;

    public function mount(ProductCategory $category): void
    {
        $this->category = $category;
        $this->fill($category->only(['name', 'code', 'legacy_code', 'parent_id', 'description', 'is_active']));
    }

    #[Computed]
    public function parentCategories()
    {
        return ProductCategory::active()
            ->parents()
            ->where('id', '!=', $this->category->id) // Exclude self
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function update(): void
    {
        $validated = $this->validate((new UpdateProductCategoryRequest())->rules(), (new UpdateProductCategoryRequest())->messages());

        // Convert empty string to null for parent_id
        if (empty($validated['parent_id'])) {
            $validated['parent_id'] = null;
        }

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
            <!-- Categoría Padre -->
            <flux:field>
                <flux:label>Categoría Padre (Opcional)</flux:label>
                <flux:select wire:model="parent_id" placeholder="Seleccione una categoría padre">
                    <flux:select.option value="">Sin categoría padre (es categoría principal)</flux:select.option>
                    @foreach($this->parentCategories as $parentCat)
                        <flux:select.option value="{{ $parentCat->id }}">{{ $parentCat->name }}</flux:select.option>
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

            <!-- Información adicional -->
            <div class="pt-4 border-t border-zinc-200 dark:border-zinc-700 space-y-2">
                <flux:text class="text-sm text-zinc-500">
                    <strong>Productos asociados:</strong> {{ $category->products()->count() }}
                </flux:text>
                @if($category->children()->count() > 0)
                    <flux:text class="text-sm text-zinc-500">
                        <strong>Subcategorías:</strong> {{ $category->children()->count() }}
                    </flux:text>
                @endif
                @if($category->parent)
                    <flux:text class="text-sm text-zinc-500">
                        <strong>Categoría padre actual:</strong> {{ $category->parent->name }}
                    </flux:text>
                @endif
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
