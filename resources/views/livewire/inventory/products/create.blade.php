<?php

use Livewire\Volt\Component;
use App\Models\Product;
use App\Models\Company;
use App\Models\ProductCategory;
use App\Models\UnitOfMeasure;
use App\Http\Requests\StoreProductRequest;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;

new #[Layout('components.layouts.app')] class extends Component
{
    public $name = '';
    public $sku = '';
    public $description = '';
    public $category_id = '';
    public $unit_of_measure_id = '';
    public $company_id = '';
    public $cost = '';
    public $price = '';
    public $barcode = '';
    public $image_path = '';
    public $track_inventory = true;
    public $is_active = true;
    public $valuation_method = 'fifo';
    public $minimum_stock = '';
    public $maximum_stock = '';
    public $product_attributes = [];

    public function mount(): void
    {
        // Set default company to user's company if not super admin
        if (!auth()->user()->isSuperAdmin()) {
            $this->company_id = auth()->user()->company_id;
        }
    }

    public function updatedCompanyId(): void
    {
        // Reset category when company changes
        $this->category_id = '';
    }

    public function isSuperAdmin(): bool
    {
        return auth()->user()->isSuperAdmin();
    }

    #[Computed]
    public function companies()
    {
        if ($this->isSuperAdmin()) {
            return Company::active()->orderBy('name')->get(['id', 'name']);
        }

        return collect([]);
    }

    #[Computed]
    public function categories()
    {
        $query = ProductCategory::active();

        if ($this->company_id) {
            $query->where('company_id', $this->company_id);
        }

        return $query->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function unitsOfMeasure()
    {
        return UnitOfMeasure::orderBy('name')->get(['id', 'name', 'abbreviation']);
    }

    public function save(): void
    {
        $rules = (new StoreProductRequest())->rules();
        $messages = (new StoreProductRequest())->messages();

        // Replace 'attributes' with 'product_attributes' in validation rules
        if (isset($rules['attributes'])) {
            $rules['product_attributes'] = $rules['attributes'];
            unset($rules['attributes']);
        }

        if (isset($messages['attributes.array'])) {
            $messages['product_attributes.array'] = $messages['attributes.array'];
            unset($messages['attributes.array']);
        }

        $this->validate($rules, $messages);

        $productData = [
            'name' => $this->name,
            'sku' => $this->sku,
            'description' => $this->description,
            'category_id' => $this->category_id,
            'unit_of_measure_id' => $this->unit_of_measure_id,
            'company_id' => $this->company_id,
            'cost' => $this->cost,
            'price' => $this->price,
            'barcode' => $this->barcode,
            'image_path' => $this->image_path,
            'track_inventory' => $this->track_inventory,
            'is_active' => $this->is_active,
            'valuation_method' => $this->valuation_method,
            'minimum_stock' => $this->minimum_stock,
            'maximum_stock' => $this->maximum_stock,
            'attributes' => $this->product_attributes,
        ];

        $product = Product::create($productData);

        session()->flash('success', 'Producto creado exitosamente.');

        $this->redirect(route('inventory.products.show', $product->slug), navigate: true);
    }

    public function cancel(): void
    {
        $this->redirect(route('inventory.products.index'), navigate: true);
    }

    public function with(): array
    {
        return [
            'title' => __('Crear Producto'),
        ];
    }
}; ?>

<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Crear Nuevo Producto</flux:heading>
            <flux:text class="mt-1">Completa la información para registrar un nuevo producto en el sistema</flux:text>
        </div>
    </div>

    <!-- Form -->
    <form wire:submit="save" class="space-y-6">
            <!-- Información Básica -->
            <flux:card>
                <flux:heading size="lg" class="mb-6">Información Básica</flux:heading>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Nombre -->
                    <div class="md:col-span-2">
                        <flux:field>
                            <flux:label badge="Requerido">Nombre del Producto</flux:label>
                            <flux:input wire:model="name" placeholder="Ej: Alimento para ganado Premium" />
                            <flux:error name="name" />
                        </flux:field>
                    </div>

                    <!-- SKU -->
                    <div>
                        <flux:field>
                            <flux:label badge="Requerido">Código SKU</flux:label>
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
                    @if($this->isSuperAdmin())
                        <!-- Empresa -->
                        <div class="md:col-span-2">
                            <flux:field>
                                <flux:label badge="Requerido">Empresa</flux:label>
                                <flux:select wire:model.live="company_id" placeholder="Selecciona una empresa">
                                    <option value="">Seleccione una empresa</option>
                                    @foreach($this->companies as $company)
                                    <flux:select.option value="{{ $company->id }}">{{ $company->name }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                                <flux:error name="company_id" />
                            </flux:field>
                        </div>
                    @endif

                    <!-- Categoría -->
                    <div>
                        <flux:field>
                            <div class="flex items-center justify-between">
                                <flux:label badge="Requerido">Categoría</flux:label>
                                <a href="{{ route('admin.categories.create') }}" target="_blank" class="text-xs text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 flex items-center gap-1">
                                    <flux:icon name="plus" class="h-3 w-3" />
                                    Nueva categoría
                                </a>
                            </div>
                            <flux:select wire:model="category_id" placeholder="Selecciona una categoría" :disabled="$this->isSuperAdmin() && !$company_id">
                                <option value="">Seleccione una categoría</option>
                                @foreach($this->categories as $category)
                                <flux:select.option value="{{ $category->id }}">{{ $category->name }}</flux:select.option>
                                @endforeach
                            </flux:select>
                            <flux:error name="category_id" />
                        </flux:field>
                    </div>

                    <!-- Unidad de Medida -->
                    <div>
                        <flux:field>
                            <flux:label badge="Requerido">Unidad de Medida</flux:label>
                            <flux:select wire:model="unit_of_measure_id" placeholder="Selecciona una unidad">
                                <option value="">Seleccione una unidad</option>
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
                            <flux:label badge="Requerido">Método de Valuación</flux:label>
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
                            <flux:label badge="Requerido">Costo Unitario ($)</flux:label>
                            <flux:input type="number" step="0.01" min="0" wire:model="cost" placeholder="0.00" />
                            <flux:error name="cost" />
                        </flux:field>
                    </div>

                    <!-- Precio de Venta -->
                    <div>
                        <flux:field>
                            <flux:label badge="Requerido">Precio de Venta ($)</flux:label>
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
        <div class="flex justify-end gap-3">
            <flux:button type="button" variant="ghost" wire:click="cancel">
                Cancelar
            </flux:button>
            <flux:button type="submit" variant="primary">
                Crear Producto
            </flux:button>
        </div>
    </form>
</div>
