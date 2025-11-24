<?php

use Livewire\Volt\Component;
use App\Models\ProductCategory;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\WithPagination;

new #[Layout('components.layouts.app')] class extends Component
{
    use WithPagination;

    public string $search = '';

    #[Computed]
    public function categories()
    {
        return ProductCategory::query()
            ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%")
                ->orWhere('code', 'like', "%{$this->search}%"))
            ->withCount('products')
            ->orderBy('name')
            ->paginate(15);
    }

    public function delete(ProductCategory $category): void
    {
        if ($category->products()->exists()) {
            session()->flash('error', 'No se puede eliminar la categoría porque tiene productos asociados.');
            return;
        }

        $category->delete();
        session()->flash('success', 'Categoría eliminada exitosamente.');
    }

    public function toggleStatus(int $categoryId): void
    {
        $category = ProductCategory::find($categoryId);

        if (!$category) {
            \Flux\Flux::toast('Categoría no encontrada.', variant: 'danger');
            return;
        }

        $category->is_active = !$category->is_active;
        $category->active_at = $category->is_active ? now() : null;
        $category->save();

        \Flux\Flux::toast(
            $category->is_active ? 'Categoría activada.' : 'Categoría desactivada.',
            variant: 'success'
        );
    }

    public function with(): array
    {
        return [
            'title' => __('Categorías de Productos'),
        ];
    }
}; ?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-8">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <flux:heading size="xl">Categorías de Productos</flux:heading>
                <flux:text class="mt-2">Gestiona las categorías para organizar tus productos</flux:text>
            </div>
            <flux:button variant="primary" icon="plus" href="{{ route('admin.categories.create') }}" wire:navigate>
                Nueva Categoría
            </flux:button>
        </div>
    </div>

    <flux:card>
        <div class="mb-6">
            <flux:input wire:model.live.debounce.300ms="search"
                       placeholder="Buscar categorías..."
                       icon="magnifying-glass" />
        </div>

        <flux:table>
            <flux:table.columns>
                <flux:table.column>Nombre</flux:table.column>
                <flux:table.column>Código</flux:table.column>
                <flux:table.column>Productos</flux:table.column>
                <flux:table.column>Estado</flux:table.column>
                <flux:table.column>Acciones</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse($this->categories as $category)
                <flux:table.row wire:key="category-{{ $category->id }}">
                    <flux:table.cell>
                        <flux:text class="font-medium">{{ $category->name }}</flux:text>
                        @if($category->description)
                        <flux:text class="text-sm text-zinc-500 block">{{ Str::limit($category->description, 50) }}</flux:text>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell>
                        @if($category->code)
                        <flux:badge color="zinc">{{ $category->code }}</flux:badge>
                        @else
                        <flux:text class="text-zinc-400 text-sm">Sin código</flux:text>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell>
                        <flux:text>{{ $category->products_count }} productos</flux:text>
                    </flux:table.cell>
                    <flux:table.cell>
                        @if($category->is_active)
                        <flux:badge color="green">Activo</flux:badge>
                        @else
                        <flux:badge color="red">Inactivo</flux:badge>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex items-center gap-2">
                            <flux:button variant="ghost" size="sm" icon="pencil" href="{{ route('admin.categories.edit', $category->slug) }}" wire:navigate title="Editar" />
                            <flux:button
                                size="sm"
                                variant="ghost"
                                :icon="$category->is_active ? 'x-circle' : 'check-circle'"
                                wire:click="toggleStatus({{ $category->id }})"
                                :title="$category->is_active ? 'Desactivar' : 'Activar'"
                            />
                            <flux:button variant="ghost" size="sm" icon="trash" wire:click="delete({{ $category->id }})" wire:confirm="¿Estás seguro de eliminar esta categoría?" title="Eliminar" class="text-red-600 hover:text-red-700 dark:text-red-500 dark:hover:text-red-400" />
                        </div>
                    </flux:table.cell>
                </flux:table.row>
                @empty
                <flux:table.row>
                    <flux:table.cell colspan="5" class="text-center py-12">
                        <flux:text class="text-zinc-500">No se encontraron categorías</flux:text>
                    </flux:table.cell>
                </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>

        @if($this->categories->hasPages())
        <div class="mt-6">
            {{ $this->categories->links() }}
        </div>
        @endif
    </flux:card>
</div>
