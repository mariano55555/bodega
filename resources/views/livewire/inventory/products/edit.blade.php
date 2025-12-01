<?php

use Livewire\Volt\Component;
use App\Models\Product;
use App\Models\Company;
use App\Models\ProductCategory;
use App\Models\UnitOfMeasure;
use App\Http\Requests\UpdateProductRequest;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;

new #[Layout('components.layouts.app')] class extends Component
{
    public Product $product;

    public $name;
    public $sku;
    public $description;
    public $parent_category_id;
    public $category_id;
    public $unit_of_measure_id;
    public $company_id;
    public $cost;
    public $price;
    public $barcode;
    public $image_path;
    public $track_inventory;
    public $is_active;
    public $valuation_method;
    public $minimum_stock;
    public $maximum_stock;
    public $productAttributes = [];

    public function mount(Product $product): void
    {
        $this->product = $product;
        $this->fill($product->only([
            'name', 'sku', 'description', 'category_id', 'unit_of_measure_id',
            'company_id', 'cost', 'price', 'barcode', 'image_path', 'track_inventory',
            'is_active', 'valuation_method', 'minimum_stock', 'maximum_stock'
        ]));
        $this->productAttributes = $product->attributes ?? [];

        // Set parent_category_id from current category
        if ($this->category_id) {
            $category = ProductCategory::find($this->category_id);
            $this->parent_category_id = $category?->parent_id;
        }
    }

    public function updatedParentCategoryId(): void
    {
        // Reset subcategory when parent changes
        $this->category_id = '';
    }

    #[Computed]
    public function companies()
    {
        return Company::active()->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function parentCategories()
    {
        $query = ProductCategory::active()->parents();

        if ($this->company_id) {
            $query->where('company_id', $this->company_id);
        }

        return $query->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function subcategories()
    {
        if (! $this->parent_category_id) {
            return collect([]);
        }

        return ProductCategory::active()
            ->where('parent_id', $this->parent_category_id)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    #[Computed]
    public function unitsOfMeasure()
    {
        return UnitOfMeasure::orderBy('name')->get(['id', 'name', 'abbreviation']);
    }

    public function update(): void
    {
        $request = new UpdateProductRequest();
        $rules = collect($request->rules())->except(['attributes'])->toArray();
        $validated = $this->validate($rules, $request->messages());

        // Add attributes from renamed property
        $validated['attributes'] = $this->productAttributes;

        $this->product->update($validated);

        //session()->flash('success', 'Producto actualizado exitosamente.');

        Flux::toast(
            variant: 'success',
            heading: '¡Exito!',
            text: 'Producto actualizado exitosamente.',
        );

        $this->redirect(route('inventory.products.show', $this->product->slug), navigate: true);
    }

    public function cancel(): void
    {
        $this->redirect(route('inventory.products.show', $this->product->slug), navigate: true);
    }

    public function with(): array
    {
        return [
            'title' => __('Editar Producto'),
        ];
    }
}; ?>

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Page Header -->
    <div class="mb-8">
        <div class="flex items-center gap-4 mb-4">
            <flux:button variant="ghost" size="sm" icon="arrow-left" wire:click="cancel">
                Volver
            </flux:button>
        </div>
        <flux:heading size="xl" class="text-zinc-900 dark:text-zinc-100">
            Editar Producto
        </flux:heading>
        <flux:text class="mt-2 text-zinc-600 dark:text-zinc-400">
            Modifica la información del producto {{ $product->name }}
        </flux:text>
    </div>

    <!-- Form -->
    <form wire:submit="update">
        <div class="space-y-6">
            <!-- Información Básica -->
            <flux:card>
                <flux:heading size="lg" class="mb-6">Información Básica</flux:heading>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Nombre -->
                    <div class="md:col-span-2">
                        <flux:field>
                            <flux:label>Nombre del Producto</flux:label>
                            <flux:input wire:model="name" placeholder="Ej: Alimento para ganado Premium" />
                            <flux:error name="name" />
                        </flux:field>
                    </div>

                    <!-- SKU -->
                    <div>
                        <flux:field>
                            <flux:label>Código SKU</flux:label>
                            <flux:input wire:model="sku" placeholder="Ej: ALM-GAN-001" />
                            <flux:error name="sku" />
                        </flux:field>
                    </div>

                    <!-- Código de Barras -->
                    <div>
                        <flux:field>
                            <flux:label>Código de Barras (Opcional)</flux:label>
                            <flux:input wire:model="barcode" placeholder="Ej: 7501234567890" />
                            <flux:error name="barcode" />
                        </flux:field>
                    </div>

                    <!-- Descripción -->
                    <div class="md:col-span-2">
                        <flux:field>
                            <flux:label>Descripción (Opcional)</flux:label>
                            <flux:textarea wire:model="description" rows="3" placeholder="Descripción detallada del producto..." />
                            <flux:error name="description" />
                        </flux:field>
                    </div>
                </div>
            </flux:card>

            <!-- Categorización -->
            <flux:card>
                <flux:heading size="lg" class="mb-6">Categorización</flux:heading>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Empresa -->
                    <div>
                        <flux:field>
                            <flux:label>Empresa</flux:label>
                            <flux:select wire:model="company_id" placeholder="Selecciona una empresa">
                                @foreach($this->companies as $company)
                                <flux:select.option value="{{ $company->id }}">{{ $company->name }}</flux:select.option>
                                @endforeach
                            </flux:select>
                            <flux:error name="company_id" />
                        </flux:field>
                    </div>

                    <!-- Categoría Padre -->
                    <div>
                        <flux:field>
                            <div class="flex items-center justify-between">
                                <flux:label>Categoría</flux:label>
                                <a href="{{ route('admin.categories.create') }}" target="_blank" class="text-xs text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 flex items-center gap-1">
                                    <flux:icon name="plus" class="h-3 w-3" />
                                    Nueva categoría
                                </a>
                            </div>
                            <flux:select wire:model.live="parent_category_id" placeholder="Selecciona una categoría">
                                <flux:select.option value="">Seleccione una categoría</flux:select.option>
                                @foreach($this->parentCategories as $category)
                                <flux:select.option value="{{ $category->id }}">{{ $category->name }}</flux:select.option>
                                @endforeach
                            </flux:select>
                            <flux:error name="parent_category_id" />
                        </flux:field>
                    </div>

                    <!-- Subcategoría -->
                    <div>
                        <flux:field>
                            <flux:label>Subcategoría</flux:label>
                            <flux:select wire:model="category_id" placeholder="Selecciona una subcategoría" :disabled="!$parent_category_id">
                                <flux:select.option value="">Seleccione una subcategoría</flux:select.option>
                                @foreach($this->subcategories as $subcategory)
                                <flux:select.option value="{{ $subcategory->id }}">{{ $subcategory->name }}</flux:select.option>
                                @endforeach
                            </flux:select>
                            <flux:error name="category_id" />
                        </flux:field>
                    </div>

                    <!-- Unidad de Medida -->
                    <div>
                        <flux:field>
                            <flux:label>Unidad de Medida</flux:label>
                            <flux:select wire:model="unit_of_measure_id" placeholder="Selecciona una unidad">
                                @foreach($this->unitsOfMeasure as $unit)
                                <flux:select.option value="{{ $unit->id }}">
                                    {{ $unit->name }} ({{ $unit->abbreviation }})
                                </flux:select.option>
                                @endforeach
                            </flux:select>
                            <flux:error name="unit_of_measure_id" />
                        </flux:field>
                    </div>

                    <!-- Método de Valuación -->
                    <div>
                        <flux:field>
                            <flux:label>Método de Valuación</flux:label>
                            <flux:select wire:model="valuation_method">
                                <flux:select.option value="fifo">FIFO (Primero en Entrar, Primero en Salir)</flux:select.option>
                                <flux:select.option value="lifo">LIFO (Último en Entrar, Primero en Salir)</flux:select.option>
                                <flux:select.option value="average">Promedio Ponderado</flux:select.option>
                            </flux:select>
                            <flux:error name="valuation_method" />
                        </flux:field>
                    </div>
                </div>
            </flux:card>

            <!-- Precios y Costos -->
            <flux:card>
                <flux:heading size="lg" class="mb-6">Precios y Costos</flux:heading>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Costo Unitario -->
                    <div>
                        <flux:field>
                            <flux:label>Costo Unitario ($)</flux:label>
                            <flux:input type="number" step="0.01" min="0" wire:model="cost" placeholder="0.00" />
                            <flux:error name="cost" />
                        </flux:field>
                    </div>

                    <!-- Precio de Venta -->
                    <div>
                        <flux:field>
                            <flux:label>Precio de Venta ($)</flux:label>
                            <flux:input type="number" step="0.01" min="0" wire:model="price" placeholder="0.00" />
                            <flux:error name="price" />
                        </flux:field>
                    </div>
                </div>

                @if($cost && $price && $cost > 0)
                <div class="mt-4 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                    <flux:text class="text-sm text-blue-700 dark:text-blue-300">
                        <strong>Margen de Ganancia:</strong>
                        {{ number_format((($price - $cost) / $cost) * 100, 2) }}%
                        (${{ number_format($price - $cost, 2) }})
                    </flux:text>
                </div>
                @endif
            </flux:card>

            <!-- Control de Inventario -->
            <flux:card>
                <flux:heading size="lg" class="mb-6">Control de Inventario</flux:heading>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Stock Mínimo -->
                    <div>
                        <flux:field>
                            <flux:label>Stock Mínimo (Opcional)</flux:label>
                            <flux:input type="number" step="0.01" min="0" wire:model="minimum_stock" placeholder="0.00" description="Nivel de alerta para reposición de inventario" />
                            <flux:error name="minimum_stock" />
                        </flux:field>
                    </div>

                    <!-- Stock Máximo -->
                    <div>
                        <flux:field>
                            <flux:label>Stock Máximo (Opcional)</flux:label>
                            <flux:input type="number" step="0.01" min="0" wire:model="maximum_stock" placeholder="0.00" description="Nivel máximo de inventario permitido" />
                            <flux:error name="maximum_stock" />
                        </flux:field>
                    </div>

                    <!-- Track Inventory -->
                    <div class="md:col-span-2">
                        <flux:switch wire:model="track_inventory" description="Si está activo, el sistema llevará control de entradas, salidas y existencias">
                            <flux:text>Controlar inventario de este producto</flux:text>
                        </flux:switch>
                    </div>

                    <!-- Is Active -->
                    <div class="md:col-span-2">
                        <flux:switch wire:model="is_active" description="Los productos inactivos no estarán disponibles para nuevas transacciones">
                            <flux:text>Producto activo</flux:text>
                        </flux:switch>
                    </div>
                </div>
            </flux:card>

            <!-- Form Actions -->
            <div class="flex justify-end gap-3 pt-6">
                <flux:button type="button" variant="ghost" wire:click="cancel">
                    Cancelar
                </flux:button>
                <flux:button type="submit" variant="primary">
                    Guardar Cambios
                </flux:button>
            </div>
        </div>
    </form>
</div>
