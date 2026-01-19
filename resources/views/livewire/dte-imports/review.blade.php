<?php

use App\Models\DteImport;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Purchase;
use App\Models\PurchaseDetail;
use App\Models\UnitOfMeasure;
use App\Models\Warehouse;
use App\Services\DteImportService;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Volt\Component;

new class extends Component
{
    #[Locked]
    public int $dteImportId;

    public array $itemMappings = [];

    public bool $showCreateProductModal = false;

    public bool $showCreatePurchaseModal = false;

    public int $currentItemIndex = -1;

    public string $newProductName = '';

    public string $newProductSku = '';

    public string $newProductParentCategoryId = '';

    public string $newProductCategoryId = '';

    public string $newProductUnitOfMeasureId = '';

    public string $newProductCost = '';

    public string $newProductDescription = '';

    public bool $autoGenerateSku = false;

    public bool $showCreateUnitModal = false;

    public string $newUnitName = '';

    public string $newUnitAbbreviation = '';

    public string $newUnitType = 'quantity';

    public string $newUnitDescription = '';

    public ?int $selectedWarehouseId = null;

    public bool $autoReceive = true;

    public bool $showLinkProductModal = false;

    public string $productSearch = '';

    public function mount(DteImport $dteImport): void
    {
        $user = auth()->user();

        // Verify ownership (super admin can access all)
        if (! $user->isSuperAdmin() && $dteImport->company_id !== $user->company_id) {
            abort(403);
        }

        $this->dteImportId = $dteImport->id;

        // Analyze items and initialize mappings
        $this->initializeMappings();
    }

    protected function getCompanyId(): int
    {
        return $this->dteImport->company_id;
    }

    protected function checkAutoGenerateSku(): void
    {
        $companyId = $this->getCompanyId();
        if ($companyId) {
            $company = \App\Models\Company::find($companyId);
            $this->autoGenerateSku = $company && ($company->settings['auto_generate_sku'] ?? false);
        } else {
            $this->autoGenerateSku = false;
        }
    }

    #[Computed]
    public function dteImport(): DteImport
    {
        return DteImport::with(['supplier', 'purchase'])->findOrFail($this->dteImportId);
    }

    #[Computed]
    public function parentCategories()
    {
        return ProductCategory::active()
            ->parents()
            ->where('company_id', $this->getCompanyId())
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'legacy_code']);
    }

    #[Computed]
    public function subcategories()
    {
        if (! $this->newProductParentCategoryId) {
            return collect([]);
        }

        return ProductCategory::active()
            ->where('parent_id', $this->newProductParentCategoryId)
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'legacy_code']);
    }

    #[Computed]
    public function unitsOfMeasure()
    {
        return UnitOfMeasure::active()
            ->orderBy('name')
            ->get(['id', 'name', 'abbreviation']);
    }

    public function updatedNewProductParentCategoryId(): void
    {
        $this->newProductCategoryId = '';
    }

    #[Computed]
    public function existingProducts()
    {
        return Product::query()
            ->where('company_id', $this->getCompanyId())
            ->active()
            ->when($this->productSearch, function ($q) {
                $q->where(function ($query) {
                    $query->where('name', 'like', "%{$this->productSearch}%")
                        ->orWhere('sku', 'like', "%{$this->productSearch}%")
                        ->orWhere('barcode', 'like', "%{$this->productSearch}%");
                });
            })
            ->with('category')
            ->orderBy('name')
            ->limit(50)
            ->get(['id', 'name', 'sku', 'barcode', 'category_id']);
    }

    #[Computed]
    public function warehouses()
    {
        return Warehouse::query()
            ->where('company_id', $this->getCompanyId())
            ->whereNotNull('active_at')
            ->orderBy('name')
            ->get(['id', 'name', 'code']);
    }

    protected function initializeMappings(): void
    {
        $service = app(DteImportService::class);
        $dteImport = $this->dteImport;

        // If we have saved mappings, use them
        if (! empty($dteImport->mapping_data)) {
            $this->itemMappings = $dteImport->mapping_data;

            return;
        }

        // Analyze items
        $analysis = $service->analyzeItems($dteImport, $dteImport->supplier_id);

        $this->itemMappings = [];
        foreach ($analysis as $index => $item) {
            $this->itemMappings[$index] = [
                'num_item' => $item['num_item'],
                'supplier_code' => $item['supplier_code'],
                'supplier_description' => $item['supplier_description'],
                'parsed_name' => $item['parsed_name'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'total' => $item['total'],
                'unit_measure_code' => $item['unit_measure_code'],
                'iva' => $item['iva'],
                'product_id' => $item['product_id'],
                'product_name' => $item['product']?->name,
                'product_sku' => $item['product']?->sku,
                'match_type' => $item['match_type'],
                'needs_creation' => $item['needs_creation'],
                'action' => $item['product_id'] ? 'link' : 'create', // 'link', 'create', 'skip'
            ];
        }

        // Mark as reviewing
        $dteImport->markAsReviewing();
    }

    public function openCreateProductModal(int $index): void
    {
        $item = $this->itemMappings[$index] ?? null;
        if (! $item) {
            return;
        }

        $this->currentItemIndex = $index;
        $this->checkAutoGenerateSku();

        $this->newProductName = $item['parsed_name'];
        $this->newProductSku = $this->autoGenerateSku ? '' : 'PROD-'.$item['supplier_code'];
        $this->newProductDescription = '';
        $this->newProductParentCategoryId = '';
        $this->newProductCategoryId = '';
        $this->newProductUnitOfMeasureId = '';
        $this->newProductCost = (string) $item['unit_price'];
        $this->showCreateProductModal = true;
    }

    public function closeCreateProductModal(): void
    {
        $this->showCreateProductModal = false;
        $this->currentItemIndex = -1;
        $this->newProductName = '';
        $this->newProductSku = '';
        $this->newProductDescription = '';
        $this->newProductParentCategoryId = '';
        $this->newProductCategoryId = '';
        $this->newProductUnitOfMeasureId = '';
        $this->newProductCost = '';
        $this->autoGenerateSku = false;
    }

    public function openLinkProductModal(int $index): void
    {
        $this->currentItemIndex = $index;
        $this->productSearch = '';
        $this->showLinkProductModal = true;
    }

    public function closeLinkProductModal(): void
    {
        $this->showLinkProductModal = false;
        $this->currentItemIndex = -1;
        $this->productSearch = '';
    }

    public function createProduct(): void
    {
        $rules = [
            'newProductName' => 'required|string|max:255',
            'newProductCategoryId' => 'required|exists:product_categories,id',
            'newProductUnitOfMeasureId' => 'required|exists:units_of_measure,id',
        ];

        $messages = [
            'newProductName.required' => 'El nombre es requerido',
            'newProductCategoryId.required' => 'La subcategoría es requerida',
            'newProductUnitOfMeasureId.required' => 'La unidad de medida es requerida',
        ];

        // Only validate SKU if auto-generation is disabled
        if (! $this->autoGenerateSku) {
            $rules['newProductSku'] = 'required|string|max:50';
            $messages['newProductSku.required'] = 'El SKU es requerido';
        }

        $this->validate($rules, $messages);

        $dteImport = $this->dteImport;
        $item = $this->itemMappings[$this->currentItemIndex];
        $service = app(DteImportService::class);
        $companyId = $this->getCompanyId();

        // Get supplier_id, trying to find it if not set
        $supplierId = $dteImport->supplier_id;
        if (! $supplierId && $dteImport->emisor_nit) {
            $supplier = \App\Models\Supplier::forCompany($companyId)
                ->where('tax_id', $dteImport->emisor_nit)
                ->first();
            if ($supplier) {
                $supplierId = $supplier->id;
                // Update DTE with found supplier for future use
                $dteImport->update(['supplier_id' => $supplierId]);
            }
        }

        try {
            // Get unit of measure name
            $unitName = 'unidad';
            if ($this->newProductUnitOfMeasureId) {
                $unit = UnitOfMeasure::find($this->newProductUnitOfMeasureId);
                $unitName = $unit?->abbreviation ?? $unit?->name ?? 'unidad';
            }

            $product = $service->createProductFromItem(
                $item,
                $companyId,
                $supplierId,
                [
                    'name' => $this->newProductName,
                    'sku' => $this->newProductSku ?: null,
                    'description' => $this->newProductDescription ?: null,
                    'category_id' => $this->newProductCategoryId,
                    'unit_of_measure_id' => $this->newProductUnitOfMeasureId,
                    'unit_of_measure' => $unitName,
                ]
            );

            // Update mapping
            $this->itemMappings[$this->currentItemIndex]['product_id'] = $product->id;
            $this->itemMappings[$this->currentItemIndex]['product_name'] = $product->name;
            $this->itemMappings[$this->currentItemIndex]['product_sku'] = $product->sku;
            $this->itemMappings[$this->currentItemIndex]['match_type'] = 'created';
            $this->itemMappings[$this->currentItemIndex]['needs_creation'] = false;
            $this->itemMappings[$this->currentItemIndex]['action'] = 'link';

            // Save mappings
            $this->saveMappings();

            $this->closeCreateProductModal();

            session()->flash('success', "Producto '{$product->name}' creado exitosamente.");

        } catch (\Exception $e) {
            $this->addError('newProductName', 'Error al crear producto: '.$e->getMessage());
        }
    }

    public function linkToExistingProduct(int $index, int $productId): void
    {
        $dteImport = $this->dteImport;
        $item = $this->itemMappings[$index];
        $service = app(DteImportService::class);
        $companyId = $this->getCompanyId();

        $product = Product::where('company_id', $companyId)
            ->findOrFail($productId);

        // Create the product-supplier link
        $service->linkProductToSupplier(
            $productId,
            $dteImport->supplier_id,
            $item,
            $companyId
        );

        // Update mapping
        $this->itemMappings[$index]['product_id'] = $product->id;
        $this->itemMappings[$index]['product_name'] = $product->name;
        $this->itemMappings[$index]['product_sku'] = $product->sku;
        $this->itemMappings[$index]['match_type'] = 'manual_link';
        $this->itemMappings[$index]['needs_creation'] = false;
        $this->itemMappings[$index]['action'] = 'link';

        $this->saveMappings();

        // Close the modal after linking
        $this->closeLinkProductModal();

        session()->flash('success', "Producto '{$product->name}' vinculado exitosamente.");
    }

    public function skipItem(int $index): void
    {
        $this->itemMappings[$index]['action'] = 'skip';
        $this->saveMappings();
    }

    public function restoreItem(int $index): void
    {
        $this->itemMappings[$index]['action'] = $this->itemMappings[$index]['product_id'] ? 'link' : 'create';
        $this->saveMappings();
    }

    public function openCreateUnitModal(): void
    {
        $this->resetUnitForm();
        $this->showCreateUnitModal = true;
    }

    public function closeCreateUnitModal(): void
    {
        $this->showCreateUnitModal = false;
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

        $this->newProductUnitOfMeasureId = (string) $unit->id;
        $this->closeCreateUnitModal();

        \Flux::toast(
            variant: 'success',
            heading: 'Éxito',
            text: 'Unidad de medida creada exitosamente.',
        );
    }

    protected function saveMappings(): void
    {
        $service = app(DteImportService::class);
        $service->saveMappingData($this->dteImport, $this->itemMappings);
    }

    #[Computed]
    public function canFinalize(): bool
    {
        foreach ($this->itemMappings as $item) {
            if ($item['action'] !== 'skip' && empty($item['product_id'])) {
                return false;
            }
        }

        return true;
    }

    #[Computed]
    public function mappingSummary(): array
    {
        $linked = 0;
        $toCreate = 0;
        $skipped = 0;

        foreach ($this->itemMappings as $item) {
            if ($item['action'] === 'skip') {
                $skipped++;
            } elseif (! empty($item['product_id'])) {
                $linked++;
            } else {
                $toCreate++;
            }
        }

        return [
            'total' => count($this->itemMappings),
            'linked' => $linked,
            'to_create' => $toCreate,
            'skipped' => $skipped,
        ];
    }

    public function markAsReady(): void
    {
        if (! $this->canFinalize) {
            session()->flash('error', 'Todos los productos deben estar mapeados o marcados para omitir.');

            return;
        }

        $this->saveMappings();
        $this->dteImport->markAsReady();

        session()->flash('success', 'DTE marcado como listo. Puede proceder a crear la compra.');
    }

    public function openCreatePurchaseModal(): void
    {
        if (! $this->canFinalize) {
            session()->flash('error', 'Todos los productos deben estar mapeados antes de crear la compra.');

            return;
        }

        $this->showCreatePurchaseModal = true;
    }

    public function closeCreatePurchaseModal(): void
    {
        $this->showCreatePurchaseModal = false;
        $this->selectedWarehouseId = null;
        $this->autoReceive = true;
    }

    public function createPurchase(): void
    {
        $this->validate([
            'selectedWarehouseId' => 'required|exists:warehouses,id',
        ], [
            'selectedWarehouseId.required' => 'Debe seleccionar una bodega destino',
        ]);

        if (! $this->canFinalize) {
            $this->addError('selectedWarehouseId', 'Todos los productos deben estar mapeados.');

            return;
        }

        $dteImport = $this->dteImport;

        try {
            DB::beginTransaction();

            // Get default acquisition type
            $defaultAcquisitionType = \App\Models\AcquisitionType::where('is_active', true)->first();

            // Create the purchase
            $purchase = Purchase::create([
                'company_id' => $this->getCompanyId(),
                'warehouse_id' => $this->selectedWarehouseId,
                'supplier_id' => $dteImport->supplier_id,
                'document_type' => 'factura',
                'document_number' => $dteImport->numero_control,
                'document_date' => $dteImport->fecha_emision,
                'purchase_type' => 'contado',
                'payment_status' => 'pendiente',
                'acquisition_type_id' => $defaultAcquisitionType?->id,
                'subtotal' => $dteImport->total_gravado,
                'tax_amount' => $dteImport->total_iva,
                'discount_amount' => 0,
                'shipping_cost' => 0,
                'total' => $dteImport->total_pagar,
                'status' => $this->autoReceive ? 'aprobado' : 'pendiente',
                'notes' => "Importado desde DTE: {$dteImport->codigo_generacion}",
                'is_active' => true,
                'active_at' => now(),
            ]);

            // Create purchase details for mapped items
            foreach ($this->itemMappings as $item) {
                if ($item['action'] === 'skip' || empty($item['product_id'])) {
                    continue;
                }

                PurchaseDetail::create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_cost' => $item['unit_price'],
                    'tax_amount' => $item['iva'] ?? 0,
                    'discount_amount' => 0,
                    'total' => $item['total'],
                    'notes' => "Código proveedor: {$item['supplier_code']}",
                ]);
            }

            // Recalculate totals
            $purchase->calculateTotals();

            // If auto-receive, process the inventory movements
            if ($this->autoReceive) {
                $purchase->receive(auth()->id());
            }

            // Mark DTE as processed
            $dteImport->markAsProcessed($purchase->id);

            DB::commit();

            $this->closeCreatePurchaseModal();

            $message = $this->autoReceive
                ? 'Compra creada y recibida exitosamente. El inventario ha sido actualizado.'
                : 'Compra creada exitosamente. Recuerde aprobarla y recibirla para actualizar el inventario.';

            session()->flash('success', $message);

            // Redirect to purchase show page
            $this->redirect(route('purchases.show', $purchase), navigate: true);

        } catch (\Exception $e) {
            DB::rollBack();
            $this->addError('selectedWarehouseId', 'Error al crear la compra: '.$e->getMessage());
        }
    }

    public function goBack(): void
    {
        $this->redirect(route('dte-imports.index'), navigate: true);
    }
}; ?>

<div class="space-y-6">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <div class="flex items-center gap-3">
                <flux:button wire:click="goBack" variant="ghost" size="sm" icon="arrow-left" />
                <div>
                    <flux:heading size="xl" class="text-zinc-900 dark:text-zinc-100">
                        Revisar DTE
                    </flux:heading>
                    <flux:text class="text-zinc-600 dark:text-zinc-400">
                        Mapea los productos del DTE con tu inventario
                    </flux:text>
                </div>
            </div>
        </div>
        <flux:badge color="{{ $this->dteImport->status_color }}" size="lg">
            {{ $this->dteImport->status_label }}
        </flux:badge>
    </div>

    {{-- DTE Info Card --}}
    <flux:card>
        <div class="grid gap-6 p-4 md:grid-cols-4">
            <div>
                <flux:text size="sm" class="font-medium text-zinc-500 dark:text-zinc-400">Proveedor</flux:text>
                <flux:text class="font-medium text-zinc-900 dark:text-zinc-100">
                    {{ $this->dteImport->emisor_nombre }}
                </flux:text>
                <flux:text size="sm" class="text-zinc-600 dark:text-zinc-400">
                    NIT: {{ $this->dteImport->emisor_nit }}
                </flux:text>
            </div>
            <div>
                <flux:text size="sm" class="font-medium text-zinc-500 dark:text-zinc-400">Fecha</flux:text>
                <flux:text class="text-zinc-900 dark:text-zinc-100">
                    {{ $this->dteImport->fecha_emision->format('d/m/Y') }}
                </flux:text>
            </div>
            <div>
                <flux:text size="sm" class="font-medium text-zinc-500 dark:text-zinc-400">Total</flux:text>
                <flux:heading size="lg" class="text-zinc-900 dark:text-zinc-100">
                    ${{ number_format($this->dteImport->total_pagar, 2) }}
                </flux:heading>
            </div>
            <div>
                <flux:text size="sm" class="font-medium text-zinc-500 dark:text-zinc-400">Código</flux:text>
                <flux:text size="sm" class="font-mono text-zinc-600 dark:text-zinc-400">
                    {{ Str::limit($this->dteImport->codigo_generacion, 25) }}
                </flux:text>
            </div>
        </div>
    </flux:card>

    {{-- Mapping Summary --}}
    <div class="grid gap-4 sm:grid-cols-4">
        <div class="rounded-lg border-l-4 border-blue-500 bg-blue-50 p-4 dark:bg-blue-950/20">
            <flux:text size="sm" class="font-medium text-blue-600 dark:text-blue-400">Total Items</flux:text>
            <flux:heading size="xl" class="mt-1 text-blue-900 dark:text-blue-100">
                {{ $this->mappingSummary['total'] }}
            </flux:heading>
        </div>
        <div class="rounded-lg border-l-4 border-green-500 bg-green-50 p-4 dark:bg-green-950/20">
            <flux:text size="sm" class="font-medium text-green-600 dark:text-green-400">Mapeados</flux:text>
            <flux:heading size="xl" class="mt-1 text-green-900 dark:text-green-100">
                {{ $this->mappingSummary['linked'] }}
            </flux:heading>
        </div>
        <div class="rounded-lg border-l-4 border-amber-500 bg-amber-50 p-4 dark:bg-amber-950/20">
            <flux:text size="sm" class="font-medium text-amber-600 dark:text-amber-400">Por Crear</flux:text>
            <flux:heading size="xl" class="mt-1 text-amber-900 dark:text-amber-100">
                {{ $this->mappingSummary['to_create'] }}
            </flux:heading>
        </div>
        <div class="rounded-lg border-l-4 border-zinc-500 bg-zinc-50 p-4 dark:bg-zinc-950/20">
            <flux:text size="sm" class="font-medium text-zinc-600 dark:text-zinc-400">Omitidos</flux:text>
            <flux:heading size="xl" class="mt-1 text-zinc-900 dark:text-zinc-100">
                {{ $this->mappingSummary['skipped'] }}
            </flux:heading>
        </div>
    </div>

    {{-- Items List --}}
    <flux:card>
        <div class="border-b border-zinc-200 p-4 dark:border-zinc-700">
            <flux:heading size="base" class="text-zinc-900 dark:text-zinc-100">
                Productos del DTE
            </flux:heading>
            <flux:text size="sm" class="text-zinc-600 dark:text-zinc-400">
                Vincula cada producto del DTE con un producto existente o crea uno nuevo
            </flux:text>
        </div>

        <div class="divide-y divide-zinc-200 dark:divide-zinc-700">
            @foreach($itemMappings as $index => $item)
                <div class="p-4 {{ $item['action'] === 'skip' ? 'bg-zinc-50 dark:bg-zinc-800/50 opacity-60' : '' }}">
                    <div class="flex items-start gap-4">
                        {{-- Item Info --}}
                        <div class="flex-1">
                            <div class="flex items-center gap-2">
                                <flux:badge color="zinc" size="sm">{{ $item['num_item'] }}</flux:badge>
                                <flux:text class="font-medium text-zinc-900 dark:text-zinc-100">
                                    {{ $item['supplier_description'] }}
                                </flux:text>
                            </div>
                            <div class="mt-2 flex flex-wrap gap-4 text-sm text-zinc-600 dark:text-zinc-400">
                                <span>Código: <strong>{{ $item['supplier_code'] }}</strong></span>
                                <span>Cantidad: <strong>{{ number_format($item['quantity'], 2) }}</strong></span>
                                <span>Precio: <strong>${{ number_format($item['unit_price'], 2) }}</strong></span>
                                <span>Total: <strong>${{ number_format($item['total'], 2) }}</strong></span>
                            </div>
                        </div>

                        {{-- Status & Actions --}}
                        <div class="flex items-center gap-3">
                            @if($item['action'] === 'skip')
                                <flux:badge color="zinc" size="sm">Omitido</flux:badge>
                                <flux:button
                                    wire:click="restoreItem({{ $index }})"
                                    variant="ghost"
                                    size="sm"
                                    icon="arrow-uturn-left"
                                >
                                    Restaurar
                                </flux:button>
                            @elseif(!empty($item['product_id']))
                                <div class="text-right">
                                    <flux:badge color="green" size="sm">
                                        {{ $item['match_type'] === 'supplier_code' ? 'Auto' : ($item['match_type'] === 'created' ? 'Creado' : 'Manual') }}
                                    </flux:badge>
                                    <div class="mt-1">
                                        <flux:text size="sm" class="font-medium text-zinc-900 dark:text-zinc-100">
                                            {{ $item['product_name'] }}
                                        </flux:text>
                                        <flux:text size="xs" class="font-mono text-zinc-500">
                                            {{ $item['product_sku'] }}
                                        </flux:text>
                                    </div>
                                </div>
                            @else
                                <flux:badge color="amber" size="sm">Sin mapear</flux:badge>
                                <flux:dropdown>
                                    <flux:button variant="primary" size="sm" icon-trailing="chevron-down">
                                        Acciones
                                    </flux:button>
                                    <flux:menu>
                                        <flux:menu.item wire:click="openCreateProductModal({{ $index }})" icon="plus">
                                            Crear producto nuevo
                                        </flux:menu.item>
                                        <flux:menu.separator />
                                        <flux:menu.item wire:click="openLinkProductModal({{ $index }})" icon="link">
                                            Vincular a producto existente
                                        </flux:menu.item>
                                        <flux:menu.separator />
                                        <flux:menu.item wire:click="skipItem({{ $index }})" icon="x-mark" class="text-zinc-500">
                                            Omitir este item
                                        </flux:menu.item>
                                    </flux:menu>
                                </flux:dropdown>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </flux:card>

    {{-- Action Bar --}}
    <div class="flex items-center justify-between rounded-lg bg-zinc-50 p-4 dark:bg-zinc-800">
        <div>
            @if($this->dteImport->isProcessed())
                <flux:text class="text-emerald-600 dark:text-emerald-400">
                    <flux:icon name="check-badge" class="inline h-5 w-5" />
                    Este DTE ya fue procesado y la compra fue creada
                </flux:text>
            @elseif($this->canFinalize)
                <flux:text class="text-green-600 dark:text-green-400">
                    <flux:icon name="check-circle" class="inline h-5 w-5" />
                    Todos los productos están mapeados - listo para crear compra
                </flux:text>
            @else
                <flux:text class="text-amber-600 dark:text-amber-400">
                    <flux:icon name="exclamation-triangle" class="inline h-5 w-5" />
                    Hay {{ $this->mappingSummary['to_create'] }} productos pendientes de mapear
                </flux:text>
            @endif
        </div>
        <div class="flex items-center gap-3">
            <flux:button wire:click="goBack" variant="ghost">
                Volver al listado
            </flux:button>
            @if($this->dteImport->isProcessed() && $this->dteImport->purchase)
                <flux:button
                    :href="route('purchases.show', $this->dteImport->purchase)"
                    wire:navigate
                    variant="primary"
                    icon="eye"
                >
                    Ver Compra
                </flux:button>
            @elseif(!$this->dteImport->isProcessed())
                <flux:button
                    wire:click="openCreatePurchaseModal"
                    variant="primary"
                    :disabled="!$this->canFinalize"
                    icon="shopping-cart"
                >
                    Crear Compra
                </flux:button>
            @endif
        </div>
    </div>

    {{-- Create Product Modal --}}
    <flux:modal wire:model="showCreateProductModal" class="max-w-xl" wire:key="create-product-modal-v2">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Crear Producto</flux:heading>
                <flux:text class="text-zinc-600 dark:text-zinc-400">
                    Crea un nuevo producto a partir del item del DTE
                </flux:text>
            </div>

            @if($currentItemIndex >= 0 && isset($itemMappings[$currentItemIndex]))
                <flux:callout variant="info" size="sm">
                    <flux:text size="sm">
                        Producto del DTE: <strong>{{ $itemMappings[$currentItemIndex]['supplier_description'] }}</strong>
                    </flux:text>
                </flux:callout>
            @endif

            <div class="space-y-4">
                <flux:field>
                    <flux:label badge="Requerido">Nombre del Producto</flux:label>
                    <flux:input wire:model="newProductName" placeholder="Nombre del producto" />
                    <flux:error name="newProductName" />
                </flux:field>

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
                        wire:model="newProductSku"
                        placeholder="{{ $autoGenerateSku ? 'Se generará automáticamente (Ej: PRO-A7K9M2)' : 'Código único del producto' }}"
                        @if($autoGenerateSku) disabled @endif />
                    <flux:error name="newProductSku" />
                    @if($autoGenerateSku)
                        <flux:text class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            El sistema generará un código único automáticamente al crear el producto
                        </flux:text>
                    @endif
                </flux:field>

                <flux:field>
                    <flux:label badge="Requerido">Categoría</flux:label>
                    <flux:select wire:model.live="newProductParentCategoryId" variant="listbox" searchable placeholder="Seleccione una categoría">
                        @foreach($this->parentCategories as $category)
                            <flux:select.option value="{{ $category->id }}">{{ $category->legacy_code ?? $category->code }} - {{ $category->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="newProductParentCategoryId" />
                </flux:field>

                <flux:field>
                    <flux:label badge="Requerido">Subcategoría</flux:label>
                    <flux:select wire:model="newProductCategoryId" variant="listbox" searchable placeholder="Seleccione una subcategoría" :disabled="!$newProductParentCategoryId">
                        @foreach($this->subcategories as $subcategory)
                            <flux:select.option value="{{ $subcategory->id }}">{{ $subcategory->legacy_code ?? $subcategory->code }} - {{ $subcategory->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="newProductCategoryId" />
                </flux:field>

                <flux:field>
                    <div class="flex items-center justify-between">
                        <flux:label badge="Requerido">Unidad de Medida</flux:label>
                        <button type="button" wire:click="openCreateUnitModal" class="text-xs text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 flex items-center gap-1">
                            <flux:icon name="plus" class="h-3 w-3" />
                            Nueva unidad
                        </button>
                    </div>
                    <flux:select wire:model="newProductUnitOfMeasureId" variant="listbox" searchable placeholder="Seleccione una unidad">
                        @foreach($this->unitsOfMeasure as $unit)
                            <flux:select.option value="{{ $unit->id }}">{{ $unit->name }} ({{ $unit->abbreviation }})</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="newProductUnitOfMeasureId" />
                </flux:field>

                <flux:field>
                    <flux:label>Costo</flux:label>
                    <flux:input type="number" wire:model="newProductCost" step="0.01" readonly />
                    <flux:text size="xs" class="text-zinc-500">Del DTE</flux:text>
                </flux:field>

                <flux:field>
                    <flux:label>Descripción</flux:label>
                    <flux:textarea wire:model="newProductDescription" rows="2" placeholder="Descripción opcional..." />
                </flux:field>
            </div>

            <div class="flex items-center justify-end gap-3">
                <flux:button wire:click="closeCreateProductModal" variant="ghost">
                    Cancelar
                </flux:button>
                <flux:button wire:click="createProduct" variant="primary" icon="plus">
                    Crear Producto
                </flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- Create Unit of Measure Modal --}}
    <flux:modal wire:model="showCreateUnitModal" class="max-w-lg">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Nueva Unidad de Medida</flux:heading>
                <flux:text class="text-zinc-600 dark:text-zinc-400">
                    Completa la información para crear una nueva unidad de medida
                </flux:text>
            </div>

            <div class="space-y-4">
                <flux:field>
                    <flux:label badge="Requerido">Nombre</flux:label>
                    <flux:input wire:model="newUnitName" placeholder="Ej: Kilogramo, Litro, Pieza" />
                    <flux:error name="newUnitName" />
                </flux:field>

                <flux:field>
                    <flux:label badge="Requerido">Abreviatura</flux:label>
                    <flux:input wire:model.blur="newUnitAbbreviation" placeholder="Ej: kg, L, pza" maxlength="10" />
                    <flux:error name="newUnitAbbreviation" />
                    <flux:text class="text-xs text-zinc-500 dark:text-zinc-400">Máximo 10 caracteres</flux:text>
                </flux:field>

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

                <flux:field>
                    <flux:label>Descripción (Opcional)</flux:label>
                    <flux:textarea wire:model="newUnitDescription" rows="3" placeholder="Descripción adicional..." />
                    <flux:error name="newUnitDescription" />
                </flux:field>
            </div>

            <div class="flex items-center justify-end gap-3">
                <flux:button wire:click="closeCreateUnitModal" variant="ghost">
                    Cancelar
                </flux:button>
                <flux:button wire:click="saveUnit" variant="primary">
                    Guardar
                </flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- Create Purchase Modal --}}
    <flux:modal wire:model="showCreatePurchaseModal" class="max-w-xl">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Crear Compra desde DTE</flux:heading>
                <flux:text class="text-zinc-600 dark:text-zinc-400">
                    Seleccione la bodega donde se recibirá la mercadería
                </flux:text>
            </div>

            {{-- Purchase Summary --}}
            <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <flux:text size="sm" class="font-medium text-zinc-500 dark:text-zinc-400">Proveedor</flux:text>
                        <flux:text class="font-medium text-zinc-900 dark:text-zinc-100">
                            {{ $this->dteImport->emisor_nombre }}
                        </flux:text>
                    </div>
                    <div>
                        <flux:text size="sm" class="font-medium text-zinc-500 dark:text-zinc-400">Fecha</flux:text>
                        <flux:text class="text-zinc-900 dark:text-zinc-100">
                            {{ $this->dteImport->fecha_emision->format('d/m/Y') }}
                        </flux:text>
                    </div>
                    <div>
                        <flux:text size="sm" class="font-medium text-zinc-500 dark:text-zinc-400">Total Items</flux:text>
                        <flux:text class="text-zinc-900 dark:text-zinc-100">
                            {{ $this->mappingSummary['linked'] }} productos
                        </flux:text>
                    </div>
                    <div>
                        <flux:text size="sm" class="font-medium text-zinc-500 dark:text-zinc-400">Total</flux:text>
                        <flux:heading size="lg" class="text-green-600 dark:text-green-400">
                            ${{ number_format($this->dteImport->total_pagar, 2) }}
                        </flux:heading>
                    </div>
                </div>
            </div>

            {{-- Warehouse Selection --}}
            <flux:field>
                <flux:label>Bodega Destino *</flux:label>
                <flux:select wire:model="selectedWarehouseId" variant="listbox" placeholder="Seleccione una bodega...">
                    @foreach($this->warehouses as $warehouse)
                        <flux:select.option value="{{ $warehouse->id }}">
                            {{ $warehouse->name }} @if($warehouse->code)({{ $warehouse->code }})@endif
                        </flux:select.option>
                    @endforeach
                </flux:select>
                @error('selectedWarehouseId')
                    <flux:text size="sm" class="text-red-600">{{ $message }}</flux:text>
                @enderror
                <flux:text size="xs" class="text-zinc-500">
                    El inventario se actualizará en esta bodega
                </flux:text>
            </flux:field>

            {{-- Auto-receive Option --}}
            <div class="flex items-center gap-3 rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                <flux:switch wire:model="autoReceive" />
                <div>
                    <flux:text class="font-medium text-zinc-900 dark:text-zinc-100">
                        Recibir automáticamente
                    </flux:text>
                    <flux:text size="sm" class="text-zinc-600 dark:text-zinc-400">
                        La compra se marcará como recibida y el inventario se actualizará inmediatamente
                    </flux:text>
                </div>
            </div>

            @if(!$autoReceive)
                <flux:callout variant="warning" size="sm">
                    <flux:text size="sm">
                        La compra se creará en estado <strong>Pendiente</strong>. Deberá aprobarla y recibirla manualmente para actualizar el inventario.
                    </flux:text>
                </flux:callout>
            @endif

            <div class="flex items-center justify-end gap-3">
                <flux:button wire:click="closeCreatePurchaseModal" variant="ghost">
                    Cancelar
                </flux:button>
                <flux:button
                    wire:click="createPurchase"
                    variant="primary"
                    icon="shopping-cart"
                    wire:loading.attr="disabled"
                    wire:target="createPurchase"
                >
                    <span wire:loading.remove wire:target="createPurchase">Crear Compra</span>
                    <span wire:loading wire:target="createPurchase">Creando...</span>
                </flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- Link to Existing Product Modal --}}
    <flux:modal wire:model="showLinkProductModal" class="max-w-2xl">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Vincular a Producto Existente</flux:heading>
                <flux:text class="text-zinc-600 dark:text-zinc-400">
                    Busca y selecciona un producto de tu inventario
                </flux:text>
            </div>

            @if($currentItemIndex >= 0 && isset($itemMappings[$currentItemIndex]))
                <flux:callout variant="info" size="sm">
                    <flux:text size="sm">
                        Vinculando item: <strong>{{ $itemMappings[$currentItemIndex]['supplier_description'] }}</strong>
                    </flux:text>
                </flux:callout>
            @endif

            {{-- Search Input --}}
            <flux:field>
                <flux:label>Buscar producto</flux:label>
                <flux:input
                    wire:model.live.debounce.300ms="productSearch"
                    placeholder="Buscar por nombre, SKU o código de barras..."
                    icon="magnifying-glass"
                />
                <flux:text size="xs" class="text-zinc-500">
                    Escribe para buscar entre todos los productos de tu empresa
                </flux:text>
            </flux:field>

            {{-- Products List --}}
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700">
                <div class="max-h-96 overflow-y-auto">
                    @if($this->existingProducts->isEmpty())
                        <div class="p-8 text-center">
                            <flux:icon name="inbox" class="mx-auto h-12 w-12 text-zinc-400" />
                            <flux:text class="mt-2 text-zinc-600 dark:text-zinc-400">
                                @if($productSearch)
                                    No se encontraron productos que coincidan con "{{ $productSearch }}"
                                @else
                                    No hay productos disponibles
                                @endif
                            </flux:text>
                        </div>
                    @else
                        <div class="divide-y divide-zinc-200 dark:divide-zinc-700">
                            @foreach($this->existingProducts as $product)
                                <button
                                    wire:click="linkToExistingProduct({{ $currentItemIndex }}, {{ $product->id }})"
                                    type="button"
                                    class="w-full px-4 py-3 text-left transition hover:bg-zinc-50 focus:bg-zinc-50 focus:outline-none dark:hover:bg-zinc-800 dark:focus:bg-zinc-800"
                                >
                                    <div class="flex items-start justify-between gap-4">
                                        <div class="flex-1 min-w-0">
                                            <flux:text class="font-medium text-zinc-900 dark:text-zinc-100">
                                                {{ $product->name }}
                                            </flux:text>
                                            <div class="mt-1 flex flex-wrap gap-3 text-sm">
                                                @if($product->sku)
                                                    <span class="text-zinc-600 dark:text-zinc-400">
                                                        SKU: <span class="font-mono">{{ $product->sku }}</span>
                                                    </span>
                                                @endif
                                                @if($product->barcode)
                                                    <span class="text-zinc-600 dark:text-zinc-400">
                                                        Código: <span class="font-mono">{{ $product->barcode }}</span>
                                                    </span>
                                                @endif
                                                @if($product->category)
                                                    <span class="text-zinc-600 dark:text-zinc-400">
                                                        <flux:badge color="zinc" size="sm">{{ $product->category->name }}</flux:badge>
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                        <flux:icon name="chevron-right" class="h-5 w-5 shrink-0 text-zinc-400" />
                                    </div>
                                </button>
                            @endforeach
                        </div>

                        @if($this->existingProducts->count() >= 50)
                            <div class="border-t border-zinc-200 bg-zinc-50 px-4 py-2 dark:border-zinc-700 dark:bg-zinc-800">
                                <flux:text size="xs" class="text-zinc-600 dark:text-zinc-400">
                                    Mostrando los primeros 50 resultados. Usa el buscador para refinar los resultados.
                                </flux:text>
                            </div>
                        @endif
                    @endif
                </div>
            </div>

            <div class="flex items-center justify-end gap-3">
                <flux:button wire:click="closeLinkProductModal" variant="ghost">
                    Cancelar
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
