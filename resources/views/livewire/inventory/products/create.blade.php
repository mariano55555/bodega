<?php

use App\Http\Requests\StoreProductRequest;
use App\Models\Company;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\UnitOfMeasure;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component
{
    public $name = '';

    public $sku = '';

    public $description = '';

    public $parent_category_id = '';

    public $category_id = '';

    public $unit_of_measure_id = '';

    public $company_id = '';

    public $cost = '';

    public $price = null; // Comentado en UI por petición del cliente

    public $barcode = '';

    public $image_path = '';

    public $track_inventory = true;

    public $is_active = true;

    public $valuation_method = null; // Comentado en UI por petición del cliente

    public $minimum_stock = '';

    public $maximum_stock = '';

    public $product_attributes = [];

    public $autoGenerateSku = false;

    public $skuPreview = '';

    // Modal para crear unidad de medida
    public $newUnitName = '';

    public $newUnitAbbreviation = '';

    public $newUnitType = 'quantity';

    public $newUnitDescription = '';

    // Modal para crear categoría
    public $newCategoryName = '';

    public $newCategoryCode = '';

    public $newCategoryLegacyCode = '';

    public $newCategoryDescription = '';

    public $newCategoryParentId = '';

    public $categoryModalType = 'parent'; // 'parent' o 'subcategory'

    public function mount(): void
    {
        // Set default company to user's company if not super admin
        if (! auth()->user()->isSuperAdmin()) {
            $this->company_id = auth()->user()->company_id;
        }

        // Check if auto-generate SKU is enabled
        $this->checkAutoGenerateSku();
    }

    public function checkAutoGenerateSku(): void
    {
        if ($this->company_id) {
            $company = \App\Models\Company::find($this->company_id);
            $this->autoGenerateSku = $company && ($company->settings['auto_generate_sku'] ?? false);
        } else {
            $this->autoGenerateSku = false;
        }
    }

    public function updatedCompanyId(): void
    {
        // Reset categories when company changes
        $this->parent_category_id = '';
        $this->category_id = '';

        // Update auto-generate SKU status
        $this->checkAutoGenerateSku();

        // Clear SKU if auto-generation is enabled
        if ($this->autoGenerateSku) {
            $this->sku = '';
        }
    }

    public function updatedParentCategoryId(): void
    {
        // Reset subcategory when parent changes
        $this->category_id = '';
        $this->skuPreview = '';
    }

    public function updatedCategoryId($value): void
    {
        if ($this->autoGenerateSku && $value && $this->company_id) {
            $this->skuPreview = Product::previewSkuForCategory((int) $this->company_id, (int) $value);
        } else {
            $this->skuPreview = '';
        }
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
    public function parentCategories()
    {
        $query = ProductCategory::active()->parents();

        if ($this->company_id) {
            $query->where('company_id', $this->company_id);
        }

        return $query->orderBy('name')->get(['id', 'name', 'code', 'legacy_code']);
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
            ->get(['id', 'name', 'code', 'legacy_code']);
    }

    #[Computed]
    public function unitsOfMeasure()
    {
        return UnitOfMeasure::orderBy('name')->get(['id', 'name', 'abbreviation']);
    }

    public function save(): void
    {
        $rules = (new StoreProductRequest)->rules();
        $messages = (new StoreProductRequest)->messages();

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
            'barcode' => $this->barcode ?: null, // Convert empty string to null
            'image_path' => $this->image_path,
            'track_inventory' => $this->track_inventory,
            'is_active' => $this->is_active,
            'valuation_method' => $this->valuation_method,
            'minimum_stock' => $this->minimum_stock !== '' ? $this->minimum_stock : null,
            'maximum_stock' => $this->maximum_stock !== '' ? $this->maximum_stock : null,
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

    public function openUnitModal(): void
    {
        $this->resetUnitForm();
        $this->modal('unit-modal')->show();
    }

    public function closeUnitModal(): void
    {
        $this->modal('unit-modal')->close();
        $this->resetUnitForm();
    }

    public function resetUnitForm(): void
    {
        $this->newUnitName = '';
        $this->newUnitAbbreviation = '';
        $this->newUnitType = 'quantity';
        $this->newUnitDescription = '';
        $this->resetValidation(['newUnitName', 'newUnitAbbreviation', 'newUnitType']);
    }

    public function saveUnit(): void
    {
        $this->validate([
            'newUnitName' => ['required', 'string', 'max:255', 'unique:units_of_measure,name'],
            'newUnitAbbreviation' => ['required', 'string', 'max:10', 'unique:units_of_measure,abbreviation'],
            'newUnitType' => ['required', 'in:weight,volume,length,quantity,area,time'],
            'newUnitDescription' => ['nullable', 'string', 'max:1000'],
        ], [
            'newUnitName.required' => 'El nombre es obligatorio.',
            'newUnitName.unique' => 'Esta unidad de medida ya existe.',
            'newUnitAbbreviation.required' => 'La abreviatura es obligatoria.',
            'newUnitAbbreviation.unique' => 'Esta abreviatura ya existe.',
            'newUnitAbbreviation.max' => 'La abreviatura no puede tener más de 10 caracteres.',
            'newUnitType.required' => 'El tipo es obligatorio.',
        ]);

        $unit = UnitOfMeasure::create([
            'name' => $this->newUnitName,
            'abbreviation' => $this->newUnitAbbreviation,
            'type' => $this->newUnitType,
            'description' => $this->newUnitDescription,
            'is_active' => true,
            'active_at' => now(),
            'created_by' => auth()->id(),
        ]);

        $this->unit_of_measure_id = $unit->id;
        $this->closeUnitModal();

        \Flux::toast(
            variant: 'success',
            heading: '¡Éxito!',
            text: 'Unidad de medida creada exitosamente.',
        );
    }

    public function openCategoryModal(string $type = 'parent'): void
    {
        $this->resetCategoryForm();
        $this->categoryModalType = $type;

        if ($type === 'subcategory') {
            $this->newCategoryParentId = $this->parent_category_id;
        }

        $this->modal('category-modal')->show();
    }

    public function closeCategoryModal(): void
    {
        $this->modal('category-modal')->close();
        $this->resetCategoryForm();
    }

    public function resetCategoryForm(): void
    {
        $this->newCategoryName = '';
        $this->newCategoryCode = '';
        $this->newCategoryLegacyCode = '';
        $this->newCategoryDescription = '';
        $this->newCategoryParentId = '';

        $this->resetValidation(['newCategoryName', 'newCategoryCode', 'newCategoryLegacyCode']);
    }

    public function saveCategory(): void
    {
        $this->validate([
            'newCategoryName' => ['required', 'string', 'max:255'],
            'newCategoryCode' => ['required', 'string', 'max:20', 'unique:product_categories,code'],
            'newCategoryLegacyCode' => ['nullable', 'string', 'max:10'],
            'newCategoryDescription' => ['nullable', 'string', 'max:1000'],
        ], [
            'newCategoryName.required' => 'El nombre es obligatorio.',
            'newCategoryCode.required' => 'El código es obligatorio.',
            'newCategoryCode.unique' => 'Este código ya existe.',
            'newCategoryCode.max' => 'El código no puede tener más de 20 caracteres.',
            'newCategoryLegacyCode.max' => 'El código legacy no puede tener más de 10 caracteres.',
        ]);

        $category = ProductCategory::create([
            'name' => $this->newCategoryName,
            'code' => $this->newCategoryCode,
            'legacy_code' => $this->newCategoryLegacyCode,
            'description' => $this->newCategoryDescription,
            'company_id' => $this->company_id,
            'parent_id' => $this->categoryModalType === 'subcategory' ? $this->newCategoryParentId : null,
            'is_active' => true,
            'active_at' => now(),
            'created_by' => auth()->id(),
        ]);

        if ($this->categoryModalType === 'parent') {
            $this->parent_category_id = $category->id;
        } else {
            $this->category_id = $category->id;
        }

        $this->closeCategoryModal();

        \Flux::toast(
            variant: 'success',
            heading: '¡Éxito!',
            text: ($this->categoryModalType === 'parent' ? 'Categoría' : 'Subcategoría').' creada exitosamente.',
        );
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
                            <div class="flex items-center justify-between">
                                <flux:label :badge="$autoGenerateSku ? '' : 'Requerido'">Código SKU</flux:label>
                                @if($autoGenerateSku)
                                    <flux:text class="text-xs text-green-600 dark:text-green-400">
                                        Generación automática activada
                                    </flux:text>
                                @endif
                            </div>
                            <flux:input
                                wire:model="sku"
                                placeholder="{{ $autoGenerateSku ? ($skuPreview ?: 'Seleccione una subcategoría para ver el SKU') : 'Ej: ALM-GAN-001' }}"
                                @if($autoGenerateSku) disabled @endif />
                            <flux:error name="sku" />
                            @if($autoGenerateSku)
                                @if($skuPreview)
                                    <flux:text class="text-xs text-blue-600 dark:text-blue-400 mt-1">
                                        <strong>Próximo SKU:</strong> {{ $skuPreview }}
                                    </flux:text>
                                @else
                                    <flux:text class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                        Seleccione una subcategoría para ver el SKU que se generará
                                    </flux:text>
                                @endif
                            @endif
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

                    <!-- Categoría Padre -->
                    <div>
                        <flux:field>
                            <div class="flex items-center justify-between">
                                <flux:label badge="Requerido">Categoría</flux:label>
                                <button type="button" wire:click="openCategoryModal('parent')" class="text-xs text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 flex items-center gap-1" @if($this->isSuperAdmin() && !$company_id) disabled @endif>
                                    <flux:icon name="plus" class="h-3 w-3" />
                                    Nueva categoría
                                </button>
                            </div>
                            <flux:select wire:model.live="parent_category_id" placeholder="Selecciona una categoría" :disabled="$this->isSuperAdmin() && !$company_id">
                                <flux:select.option value="">Seleccione una categoría</flux:select.option>
                                @foreach($this->parentCategories as $category)
                                <flux:select.option value="{{ $category->id }}">{{ $category->legacy_code ?? $category->code }} - {{ $category->name }}</flux:select.option>
                                @endforeach
                            </flux:select>
                            <flux:error name="parent_category_id" />
                        </flux:field>
                    </div>

                    <!-- Subcategoría -->
                    <div>
                        <flux:field>
                            <div class="flex items-center justify-between">
                                <flux:label badge="Requerido">Subcategoría</flux:label>
                                <button type="button" wire:click="openCategoryModal('subcategory')" class="text-xs text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 flex items-center gap-1" @if(!$parent_category_id) disabled @endif>
                                    <flux:icon name="plus" class="h-3 w-3" />
                                    Nueva subcategoría
                                </button>
                            </div>
                            <flux:select wire:model.live="category_id" placeholder="Selecciona una subcategoría" :disabled="!$parent_category_id">
                                <flux:select.option value="">Seleccione una subcategoría</flux:select.option>
                                @foreach($this->subcategories as $subcategory)
                                <flux:select.option value="{{ $subcategory->id }}">{{ $subcategory->legacy_code ?? $subcategory->code }} - {{ $subcategory->name }}</flux:select.option>
                                @endforeach
                            </flux:select>
                            <flux:error name="category_id" />
                        </flux:field>
                    </div>

                    <!-- Unidad de Medida -->
                    <div>
                        <flux:field>
                            <div class="flex items-center justify-between">
                                <flux:label badge="Requerido">Unidad de Medida</flux:label>
                                <button type="button" wire:click="openUnitModal" class="text-xs text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 flex items-center gap-1">
                                    <flux:icon name="plus" class="h-3 w-3" />
                                    Nueva unidad
                                </button>
                            </div>
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

                    {{-- Método de Valuación - Comentado por petición del cliente --}}
                    {{-- <div>
                        <flux:field>
                            <flux:label badge="Requerido">Método de Valuación</flux:label>
                            <flux:select wire:model="valuation_method">
                                <flux:select.option value="fifo">FIFO (Primero en Entrar, Primero en Salir)</flux:select.option>
                                <flux:select.option value="lifo">LIFO (Último en Entrar, Primero en Salir)</flux:select.option>
                                <flux:select.option value="average">Promedio Ponderado</flux:select.option>
                            </flux:select>
                            <flux:error name="valuation_method" />
                        </flux:field>
                    </div> --}}
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
                            <flux:input type="number" step="0.00001" min="0" wire:model="cost" placeholder="0.00000" />
                            <flux:error name="cost" />
                        </flux:field>
                    </div>

                    {{-- Precio de Venta - Comentado por petición del cliente --}}
                    {{-- <div>
                        <flux:field>
                            <flux:label badge="Requerido">Precio de Venta ($)</flux:label>
                            <flux:input type="number" step="0.01" min="0" wire:model="price" placeholder="0.00" />
                            <flux:error name="price" />
                        </flux:field>
                    </div> --}}
                </div>

                {{-- Margen de Ganancia - Comentado por petición del cliente --}}
                {{-- @if($cost && $price && $cost > 0)
                <div class="mt-4 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                    <flux:text class="text-sm text-blue-700 dark:text-blue-300">
                        <strong>Margen de Ganancia:</strong>
                        {{ number_format((($price - $cost) / $cost) * 100, 2) }}%
                        (${{ number_format($price - $cost, 2) }})
                    </flux:text>
                </div>
                @endif --}}
            </flux:card>

            <!-- Control de Inventario -->
            <flux:card>
                <flux:heading size="lg" class="mb-6">Control de Inventario</flux:heading>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Stock Mínimo -->
                    <div>
                        <flux:field>
                            <flux:label>Stock Mínimo (Opcional)</flux:label>
                            <flux:input type="number" step="0.00001" min="0" wire:model="minimum_stock" placeholder="0.00000" description="Nivel de alerta para reposición de inventario" />
                            <flux:error name="minimum_stock" />
                        </flux:field>
                    </div>

                    <!-- Stock Máximo -->
                    <div>
                        <flux:field>
                            <flux:label>Stock Máximo (Opcional)</flux:label>
                            <flux:input type="number" step="0.00001" min="0" wire:model="maximum_stock" placeholder="0.00000" description="Nivel máximo de inventario permitido" />
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

    <!-- Modal para crear unidad de medida -->
    <flux:modal name="unit-modal" class="min-w-[30rem]">
        <form wire:submit="saveUnit" class="space-y-6">
            <div>
                <flux:heading size="lg">Nueva Unidad de Medida</flux:heading>
                <flux:subheading>Completa la información para crear una nueva unidad de medida</flux:subheading>
            </div>

            <div class="space-y-6">
                <!-- Nombre -->
                <flux:field>
                    <flux:label badge="Requerido">Nombre</flux:label>
                    <flux:input wire:model="newUnitName" placeholder="Ej: Kilogramo, Litro, Pieza" />
                    <flux:error name="newUnitName" />
                </flux:field>

                <!-- Abreviatura -->
                <flux:field>
                    <flux:label badge="Requerido">Abreviatura</flux:label>
                    <flux:input wire:model.blur="newUnitAbbreviation" placeholder="Ej: kg, L, pza" maxlength="10" />
                    <flux:error name="newUnitAbbreviation" />
                    <flux:text class="text-xs text-gray-500 dark:text-gray-400">Máximo 10 caracteres</flux:text>
                </flux:field>

                <!-- Tipo -->
                <flux:field>
                    <flux:label badge="Requerido">Tipo</flux:label>
                    <flux:select wire:model="newUnitType">
                        <flux:select.option value="quantity">Cantidad</flux:select.option>
                        <flux:select.option value="weight">Peso</flux:select.option>
                        <flux:select.option value="volume">Volumen</flux:select.option>
                        <flux:select.option value="length">Longitud</flux:select.option>
                        <flux:select.option value="area">Área</flux:select.option>
                        <flux:select.option value="time">Tiempo</flux:select.option>
                    </flux:select>
                    <flux:error name="newUnitType" />
                </flux:field>

                <!-- Descripción -->
                <flux:field>
                    <flux:label>Descripción (Opcional)</flux:label>
                    <flux:textarea wire:model="newUnitDescription" rows="3" placeholder="Descripción adicional..." />
                    <flux:error name="newUnitDescription" />
                </flux:field>
            </div>

            <div class="flex gap-2">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button variant="ghost">Cancelar</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">Guardar</flux:button>
            </div>
        </form>
    </flux:modal>

    <!-- Modal para crear categoría -->
    <flux:modal name="category-modal" class="min-w-[30rem]">
        <form wire:submit="saveCategory" class="space-y-6">
            <div>
                <flux:heading size="lg">
                    @if($categoryModalType === 'parent')
                        Nueva Categoría
                    @else
                        Nueva Subcategoría
                    @endif
                </flux:heading>
                <flux:subheading>
                    @if($categoryModalType === 'parent')
                        Completa la información para crear una nueva categoría padre
                    @else
                        Completa la información para crear una nueva subcategoría
                    @endif
                </flux:subheading>
            </div>

            <div class="space-y-6">
                <!-- Categoría Padre (solo visible cuando es subcategoría) -->
                @if($categoryModalType === 'subcategory')
                    <flux:field>
                        <flux:label>Categoría Padre</flux:label>
                        <flux:input
                            value="{{ collect($this->parentCategories)->firstWhere('id', $newCategoryParentId)?->name ?? '' }}"
                            disabled
                            placeholder="Selecciona una categoría padre primero" />
                        <flux:text class="text-xs text-gray-500 dark:text-gray-400">
                            Esta subcategoría se creará dentro de la categoría seleccionada
                        </flux:text>
                    </flux:field>
                @endif

                <!-- Nombre -->
                <flux:field>
                    <flux:label badge="Requerido">Nombre</flux:label>
                    <flux:input wire:model="newCategoryName" placeholder="Ej: Alimentos, Insumos, Equipos" />
                    <flux:error name="newCategoryName" />
                </flux:field>

                <!-- Código -->
                <flux:field>
                    <flux:label badge="Requerido">Código</flux:label>
                    <flux:input wire:model="newCategoryCode" placeholder="Ej: CAT-001" maxlength="20" />
                    <flux:error name="newCategoryCode" />
                    <flux:text class="text-xs text-gray-500 dark:text-gray-400">Máximo 20 caracteres</flux:text>
                </flux:field>

                <!-- Código Legacy (Opcional) -->
                <flux:field>
                    <flux:label>Código Legacy (Opcional)</flux:label>
                    <flux:input wire:model="newCategoryLegacyCode" placeholder="Ej: 54" maxlength="10" />
                    <flux:error name="newCategoryLegacyCode" />
                    <flux:text class="text-xs text-gray-500 dark:text-gray-400">Código del sistema anterior. Máximo 10 caracteres</flux:text>
                </flux:field>

                <!-- Descripción -->
                <flux:field>
                    <flux:label>Descripción (Opcional)</flux:label>
                    <flux:textarea wire:model="newCategoryDescription" rows="3" placeholder="Descripción adicional..." />
                    <flux:error name="newCategoryDescription" />
                </flux:field>
            </div>

            <div class="flex gap-2">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button variant="ghost">Cancelar</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">Guardar</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
