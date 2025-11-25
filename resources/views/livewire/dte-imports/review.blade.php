<?php

use App\Models\DteImport;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductSupplier;
use App\Models\Purchase;
use App\Models\PurchaseDetail;
use App\Models\UnitOfMeasure;
use App\Models\Warehouse;
use App\Services\DteImportService;
use Livewire\Volt\Component;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Illuminate\Support\Facades\DB;

new class extends Component {
    #[Locked]
    public int $dteImportId;

    public array $itemMappings = [];
    public bool $showCreateProductModal = false;
    public bool $showCreatePurchaseModal = false;
    public int $currentItemIndex = -1;
    public array $newProduct = [];
    public ?int $selectedWarehouseId = null;
    public bool $autoReceive = true;

    public function mount(DteImport $dteImport): void
    {
        $user = auth()->user();

        // Verify ownership (super admin can access all)
        if (!$user->isSuperAdmin() && $dteImport->company_id !== $user->company_id) {
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

    #[Computed]
    public function dteImport(): DteImport
    {
        return DteImport::with(['supplier', 'purchase'])->findOrFail($this->dteImportId);
    }

    #[Computed]
    public function categories()
    {
        return ProductCategory::query()
            ->where('company_id', $this->getCompanyId())
            ->whereNotNull('active_at')
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    #[Computed]
    public function unitsOfMeasure()
    {
        return UnitOfMeasure::query()
            ->where(function ($query) {
                $query->where('company_id', $this->getCompanyId())
                      ->orWhereNull('company_id');
            })
            ->whereNotNull('active_at')
            ->orderBy('name')
            ->get(['id', 'name', 'abbreviation']);
    }

    #[Computed]
    public function existingProducts()
    {
        return Product::query()
            ->where('company_id', $this->getCompanyId())
            ->active()
            ->orderBy('name')
            ->get(['id', 'name', 'sku']);
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
        if (!empty($dteImport->mapping_data)) {
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
        if (!$item) {
            return;
        }

        $this->currentItemIndex = $index;
        $this->newProduct = [
            'name' => $item['parsed_name'],
            'sku' => 'PROD-' . $item['supplier_code'],
            'description' => '',
            'category_id' => null,
            'unit_of_measure_id' => null,
            'cost' => $item['unit_price'],
            'price' => null,
        ];
        $this->showCreateProductModal = true;
    }

    public function closeCreateProductModal(): void
    {
        $this->showCreateProductModal = false;
        $this->currentItemIndex = -1;
        $this->newProduct = [];
    }

    public function createProduct(): void
    {
        $this->validate([
            'newProduct.name' => 'required|string|max:255',
            'newProduct.sku' => 'required|string|max:50',
            'newProduct.category_id' => 'nullable|exists:product_categories,id',
            'newProduct.unit_of_measure_id' => 'nullable|exists:units_of_measure,id',
        ], [
            'newProduct.name.required' => 'El nombre es requerido',
            'newProduct.sku.required' => 'El SKU es requerido',
        ]);

        $dteImport = $this->dteImport;
        $item = $this->itemMappings[$this->currentItemIndex];
        $service = app(DteImportService::class);
        $companyId = $this->getCompanyId();

        try {
            // Get unit of measure name
            $unitName = 'unidad';
            if ($this->newProduct['unit_of_measure_id']) {
                $unit = UnitOfMeasure::find($this->newProduct['unit_of_measure_id']);
                $unitName = $unit?->abbreviation ?? $unit?->name ?? 'unidad';
            }

            $product = $service->createProductFromItem(
                $item,
                $companyId,
                $dteImport->supplier_id,
                [
                    'name' => $this->newProduct['name'],
                    'sku' => $this->newProduct['sku'],
                    'description' => $this->newProduct['description'] ?? null,
                    'category_id' => $this->newProduct['category_id'],
                    'unit_of_measure_id' => $this->newProduct['unit_of_measure_id'],
                    'unit_of_measure' => $unitName,
                    'price' => $this->newProduct['price'],
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
            $this->addError('newProduct.name', 'Error al crear producto: ' . $e->getMessage());
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
            } elseif (!empty($item['product_id'])) {
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
        if (!$this->canFinalize) {
            session()->flash('error', 'Todos los productos deben estar mapeados o marcados para omitir.');
            return;
        }

        $this->saveMappings();
        $this->dteImport->markAsReady();

        session()->flash('success', 'DTE marcado como listo. Puede proceder a crear la compra.');
    }

    public function openCreatePurchaseModal(): void
    {
        if (!$this->canFinalize) {
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

        if (!$this->canFinalize) {
            $this->addError('selectedWarehouseId', 'Todos los productos deben estar mapeados.');
            return;
        }

        $dteImport = $this->dteImport;

        try {
            DB::beginTransaction();

            // Create the purchase
            $purchase = Purchase::create([
                'company_id' => $this->getCompanyId(),
                'warehouse_id' => $this->selectedWarehouseId,
                'supplier_id' => $dteImport->supplier_id,
                'document_type' => 'factura',
                'document_number' => $dteImport->numero_control,
                'document_date' => $dteImport->fecha_emision,
                'purchase_type' => 'efectivo',
                'payment_status' => 'pendiente',
                'acquisition_type' => 'normal',
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
            $this->addError('selectedWarehouseId', 'Error al crear la compra: ' . $e->getMessage());
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
                                        <flux:menu.submenu heading="Vincular a existente">
                                            @foreach($this->existingProducts->take(20) as $product)
                                                <flux:menu.item wire:click="linkToExistingProduct({{ $index }}, {{ $product->id }})">
                                                    {{ $product->name }} ({{ $product->sku }})
                                                </flux:menu.item>
                                            @endforeach
                                        </flux:menu.submenu>
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
    <flux:modal wire:model="showCreateProductModal" class="max-w-xl">
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
                    <flux:label>Nombre del Producto *</flux:label>
                    <flux:input wire:model="newProduct.name" placeholder="Nombre del producto" />
                    @error('newProduct.name')
                        <flux:text size="sm" class="text-red-600">{{ $message }}</flux:text>
                    @enderror
                </flux:field>

                <flux:field>
                    <flux:label>SKU *</flux:label>
                    <flux:input wire:model="newProduct.sku" placeholder="Código único del producto" />
                    @error('newProduct.sku')
                        <flux:text size="sm" class="text-red-600">{{ $message }}</flux:text>
                    @enderror
                </flux:field>

                <div class="grid gap-4 sm:grid-cols-2">
                    <flux:field>
                        <flux:label>Categoría</flux:label>
                        <flux:select wire:model="newProduct.category_id" variant="listbox" placeholder="Seleccionar...">
                            @foreach($this->categories as $category)
                                <flux:select.option value="{{ $category->id }}">{{ $category->name }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    </flux:field>

                    <flux:field>
                        <flux:label>Unidad de Medida</flux:label>
                        <flux:select wire:model="newProduct.unit_of_measure_id" variant="listbox" placeholder="Seleccionar...">
                            @foreach($this->unitsOfMeasure as $unit)
                                <flux:select.option value="{{ $unit->id }}">{{ $unit->name }} ({{ $unit->abbreviation }})</flux:select.option>
                            @endforeach
                        </flux:select>
                    </flux:field>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <flux:field>
                        <flux:label>Costo</flux:label>
                        <flux:input type="number" wire:model="newProduct.cost" step="0.01" readonly />
                        <flux:text size="xs" class="text-zinc-500">Del DTE</flux:text>
                    </flux:field>

                    <flux:field>
                        <flux:label>Precio de Venta</flux:label>
                        <flux:input type="number" wire:model="newProduct.price" step="0.01" placeholder="0.00" />
                    </flux:field>
                </div>

                <flux:field>
                    <flux:label>Descripción</flux:label>
                    <flux:textarea wire:model="newProduct.description" rows="2" placeholder="Descripción opcional..." />
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
</div>
