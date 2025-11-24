<?php

use Livewire\Volt\Component;
use App\Models\Product;
use Livewire\Attributes\Layout;

new #[Layout('components.layouts.app')] class extends Component
{
    public Product $product;

    public function mount(Product $product): void
    {
        $this->product = $product->load(['company', 'category', 'unitOfMeasure', 'creator', 'updater']);
    }

    public function edit(): void
    {
        $this->redirect(route('inventory.products.edit', $this->product->slug), navigate: true);
    }

    public function back(): void
    {
        $this->redirect(route('inventory.products.index'), navigate: true);
    }

    public function with(): array
    {
        return [
            'title' => __('Detalle del Producto'),
        ];
    }
}; ?>

<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Page Header -->
    <div class="mb-8">
        <div class="flex items-center gap-4 mb-4">
            <flux:button variant="ghost" size="sm" icon="arrow-left" wire:click="back">
                Volver
            </flux:button>
        </div>
        <div class="flex items-start justify-between">
            <div class="flex-1">
                <div class="flex items-center gap-3 mb-2">
                    <flux:heading size="xl" class="text-zinc-900 dark:text-zinc-100">
                        {{ $product->name }}
                    </flux:heading>
                    @if($product->is_active)
                    <flux:badge color="green">Activo</flux:badge>
                    @else
                    <flux:badge color="red">Inactivo</flux:badge>
                    @endif
                </div>
                <div class="flex items-center gap-4 text-sm text-zinc-600 dark:text-zinc-400">
                    <span>SKU: <strong>{{ $product->sku }}</strong></span>
                    @if($product->barcode)
                    <span>Código de Barras: <strong>{{ $product->barcode }}</strong></span>
                    @endif
                </div>
            </div>
            <flux:button variant="primary" icon="pencil" wire:click="edit">
                Editar
            </flux:button>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Información Básica -->
            <flux:card>
                <flux:heading size="lg" class="mb-6">Información Básica</flux:heading>

                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Nombre</dt>
                        <dd class="mt-1 text-sm text-zinc-900 dark:text-zinc-100">{{ $product->name }}</dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">SKU</dt>
                        <dd class="mt-1 text-sm text-zinc-900 dark:text-zinc-100">{{ $product->sku }}</dd>
                    </div>

                    @if($product->barcode)
                    <div>
                        <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Código de Barras</dt>
                        <dd class="mt-1 text-sm text-zinc-900 dark:text-zinc-100">{{ $product->barcode }}</dd>
                    </div>
                    @endif

                    <div>
                        <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Categoría</dt>
                        <dd class="mt-1 text-sm text-zinc-900 dark:text-zinc-100">
                            {{ $product->category->name ?? 'N/A' }}
                        </dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Unidad de Medida</dt>
                        <dd class="mt-1 text-sm text-zinc-900 dark:text-zinc-100">
                            {{ $product->unitOfMeasure->name ?? 'N/A' }}
                            @if($product->unitOfMeasure)
                            ({{ $product->unitOfMeasure->abbreviation }})
                            @endif
                        </dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Empresa</dt>
                        <dd class="mt-1 text-sm text-zinc-900 dark:text-zinc-100">
                            {{ $product->company->name ?? 'N/A' }}
                        </dd>
                    </div>

                    @if($product->description)
                    <div class="sm:col-span-2">
                        <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Descripción</dt>
                        <dd class="mt-1 text-sm text-zinc-900 dark:text-zinc-100">{{ $product->description }}</dd>
                    </div>
                    @endif
                </dl>
            </flux:card>

            <!-- Precios y Costos -->
            <flux:card>
                <flux:heading size="lg" class="mb-6">Precios y Costos</flux:heading>

                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Costo Unitario</dt>
                        <dd class="mt-1 text-lg font-semibold text-zinc-900 dark:text-zinc-100">
                            ${{ number_format($product->cost, 2) }}
                        </dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Precio de Venta</dt>
                        <dd class="mt-1 text-lg font-semibold text-zinc-900 dark:text-zinc-100">
                            ${{ number_format($product->price, 2) }}
                        </dd>
                    </div>

                    @if($product->cost > 0 && $product->price > 0)
                    <div class="sm:col-span-2">
                        <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                            <dt class="text-sm font-medium text-blue-700 dark:text-blue-300">Margen de Ganancia</dt>
                            <dd class="mt-1 text-lg font-semibold text-blue-900 dark:text-blue-100">
                                {{ number_format((($product->price - $product->cost) / $product->cost) * 100, 2) }}%
                                <span class="text-sm">(${{ number_format($product->price - $product->cost, 2) }})</span>
                            </dd>
                        </div>
                    </div>
                    @endif
                </dl>
            </flux:card>

            <!-- Control de Inventario -->
            <flux:card>
                <flux:heading size="lg" class="mb-6">Control de Inventario</flux:heading>

                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Método de Valuación</dt>
                        <dd class="mt-1 text-sm text-zinc-900 dark:text-zinc-100">
                            @if($product->valuation_method === 'fifo')
                            FIFO (Primero en Entrar, Primero en Salir)
                            @elseif($product->valuation_method === 'lifo')
                            LIFO (Último en Entrar, Primero en Salir)
                            @else
                            Promedio Ponderado
                            @endif
                        </dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Control de Inventario</dt>
                        <dd class="mt-1">
                            @if($product->track_inventory)
                            <flux:badge color="green">Activo</flux:badge>
                            @else
                            <flux:badge color="gray">Inactivo</flux:badge>
                            @endif
                        </dd>
                    </div>

                    @if($product->minimum_stock)
                    <div>
                        <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Stock Mínimo</dt>
                        <dd class="mt-1 text-sm text-zinc-900 dark:text-zinc-100">
                            {{ number_format($product->minimum_stock, 2) }}
                        </dd>
                    </div>
                    @endif

                    @if($product->maximum_stock)
                    <div>
                        <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Stock Máximo</dt>
                        <dd class="mt-1 text-sm text-zinc-900 dark:text-zinc-100">
                            {{ number_format($product->maximum_stock, 2) }}
                        </dd>
                    </div>
                    @endif
                </dl>
            </flux:card>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Estado y Estadísticas -->
            <flux:card>
                <flux:heading size="lg" class="mb-4">Estado</flux:heading>

                <div class="space-y-4">
                    <div>
                        <flux:text class="text-sm font-medium text-zinc-500 dark:text-zinc-400 block mb-2">
                            Estado del Producto
                        </flux:text>
                        @if($product->is_active)
                        <flux:badge color="green" size="lg">Activo</flux:badge>
                        @else
                        <flux:badge color="red" size="lg">Inactivo</flux:badge>
                        @endif
                    </div>

                    @if($product->active_at)
                    <div>
                        <flux:text class="text-sm font-medium text-zinc-500 dark:text-zinc-400 block mb-2">
                            Activo desde
                        </flux:text>
                        <flux:text class="text-sm">
                            {{ $product->active_at->format('d/m/Y H:i') }}
                        </flux:text>
                    </div>
                    @endif

                    <div>
                        <flux:text class="text-sm font-medium text-zinc-500 dark:text-zinc-400 block mb-2">
                            Creado
                        </flux:text>
                        <flux:text class="text-sm">
                            {{ $product->created_at->format('d/m/Y H:i') }}
                            @if($product->creator)
                            <br>por {{ $product->creator->name }}
                            @endif
                        </flux:text>
                    </div>

                    @if($product->updated_at && $product->updated_at != $product->created_at)
                    <div>
                        <flux:text class="text-sm font-medium text-zinc-500 dark:text-zinc-400 block mb-2">
                            Última modificación
                        </flux:text>
                        <flux:text class="text-sm">
                            {{ $product->updated_at->format('d/m/Y H:i') }}
                            @if($product->updater)
                            <br>por {{ $product->updater->name }}
                            @endif
                        </flux:text>
                    </div>
                    @endif
                </div>
            </flux:card>

            <!-- Acciones Rápidas -->
            <flux:card>
                <flux:heading size="lg" class="mb-4">Acciones Rápidas</flux:heading>

                <div class="space-y-2">
                    <flux:button variant="outline" class="w-full justify-start" icon="eye" href="{{ route('inventory.products.index') }}" wire:navigate>
                        Ver Inventario
                    </flux:button>
                    <flux:button variant="outline" class="w-full justify-start" icon="clock" href="{{ route('inventory.movements.index') }}" wire:navigate>
                        Ver Movimientos
                    </flux:button>
                    <flux:button variant="outline" class="w-full justify-start" icon="chart-bar" href="{{ route('reports.kardex') }}" wire:navigate>
                        Ver Kardex
                    </flux:button>
                </div>
            </flux:card>
        </div>
    </div>
</div>
