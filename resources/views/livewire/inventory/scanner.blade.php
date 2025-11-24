<?php

use App\Models\Company;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\Warehouse;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component
{
    public string $scannedBarcode = '';

    public string $selectedCompanyId = '';

    public string $selectedWarehouseId = '';

    public string $quantity = '';

    public string $movementType = 'entry'; // 'entry' or 'exit'

    public string $notes = '';

    public string $referenceNumber = '';

    public ?Product $foundProduct = null;

    public bool $showConfirmationModal = false;

    public bool $isScanning = false;

    public function mount(): void
    {
        $user = auth()->user();

        // Pre-select company for non-super-admin users
        if ($user->company_id) {
            $this->selectedCompanyId = (string) $user->company_id;
        }
    }

    #[Computed]
    public function isSuperAdmin(): bool
    {
        return auth()->user()->company_id === null;
    }

    #[Computed]
    public function companies()
    {
        if (! $this->isSuperAdmin) {
            return collect();
        }

        return Company::where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function warehouses()
    {
        if (empty($this->selectedCompanyId)) {
            return collect();
        }

        return Warehouse::where('company_id', $this->selectedCompanyId)
            ->active()
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function selectedWarehouse()
    {
        if (empty($this->selectedWarehouseId)) {
            return null;
        }

        return Warehouse::find($this->selectedWarehouseId);
    }

    #[Computed]
    public function canScan(): bool
    {
        return ! empty($this->selectedWarehouseId);
    }

    #[Computed]
    public function currentStock(): ?float
    {
        if (! $this->foundProduct || empty($this->selectedWarehouseId)) {
            return null;
        }

        $inventory = Inventory::where('product_id', $this->foundProduct->id)
            ->where('warehouse_id', $this->selectedWarehouseId)
            ->first();

        return $inventory?->quantity ?? 0;
    }

    public function updatedSelectedCompanyId(): void
    {
        // Reset warehouse when company changes
        $this->selectedWarehouseId = '';
        $this->resetScannerState();
    }

    public function updatedSelectedWarehouseId(): void
    {
        $this->resetScannerState();
    }

    private function resetScannerState(): void
    {
        $this->foundProduct = null;
        $this->scannedBarcode = '';
        $this->isScanning = false;
        $this->showConfirmationModal = false;
    }

    public function startScanning(): void
    {
        if (! $this->canScan) {
            session()->flash('error', 'Debe seleccionar una bodega antes de escanear');

            return;
        }

        $this->isScanning = true;
        $this->foundProduct = null;
        $this->scannedBarcode = '';
    }

    public function stopScanning(): void
    {
        $this->isScanning = false;
    }

    public function manualBarcodeEntry(): void
    {
        if (! $this->canScan) {
            session()->flash('error', 'Debe seleccionar una bodega antes de buscar productos');

            return;
        }

        $this->lookupProduct($this->scannedBarcode);
    }

    public function lookupProduct(string $code): void
    {
        if (empty($code)) {
            return;
        }

        if (! $this->canScan) {
            session()->flash('error', 'Debe seleccionar una bodega antes de buscar productos');

            return;
        }

        // Search by barcode OR SKU within the selected company
        $this->foundProduct = Product::where('company_id', $this->selectedCompanyId)
            ->where(function ($query) use ($code) {
                $query->where('barcode', $code)
                    ->orWhere('sku', $code);
            })
            ->active()
            ->first();

        if ($this->foundProduct) {
            $this->showConfirmationModal = true;
            $this->stopScanning();
        } else {
            session()->flash('error', 'Producto no encontrado con código o SKU: '.$code);
            $this->scannedBarcode = '';
        }
    }

    public function confirmMovement(): void
    {
        $this->validate([
            'selectedWarehouseId' => 'required|exists:warehouses,id',
            'quantity' => 'required|numeric|min:0.01',
            'movementType' => 'required|in:entry,exit',
            'referenceNumber' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:500',
        ], [], [
            'selectedWarehouseId' => 'bodega',
            'quantity' => 'cantidad',
            'movementType' => 'tipo de movimiento',
            'referenceNumber' => 'número de referencia',
            'notes' => 'notas',
        ]);

        if ($this->movementType === 'entry') {
            $this->recordEntry();
        } else {
            $this->recordExit();
        }
    }

    private function recordEntry(): void
    {
        // Find existing inventory or create new one
        $inventory = Inventory::firstOrNew([
            'product_id' => $this->foundProduct->id,
            'warehouse_id' => $this->selectedWarehouseId,
        ]);

        // Update inventory
        $inventory->quantity = ($inventory->quantity ?? 0) + (float) $this->quantity;
        $inventory->unit_cost = $this->foundProduct->cost;
        $inventory->location = $inventory->location ?? 'A-01-01';
        $inventory->is_active = true;

        $inventory->save();

        session()->flash('success', 'Entrada registrada exitosamente: '.$this->foundProduct->name.' - Cantidad: '.$this->quantity);
        $this->resetProductForm();
    }

    private function recordExit(): void
    {
        // Find existing inventory
        $inventory = Inventory::where([
            'product_id' => $this->foundProduct->id,
            'warehouse_id' => $this->selectedWarehouseId,
        ])->where('quantity', '>=', $this->quantity)->first();

        if (! $inventory) {
            $this->addError('quantity', 'Stock insuficiente disponible');

            return;
        }

        // Update inventory
        $inventory->quantity = $inventory->quantity - (float) $this->quantity;
        $inventory->save();

        session()->flash('success', 'Salida registrada exitosamente: '.$this->foundProduct->name.' - Cantidad: '.$this->quantity);
        $this->resetProductForm();
    }

    public function resetProductForm(): void
    {
        $this->quantity = '';
        $this->notes = '';
        $this->referenceNumber = '';
        $this->foundProduct = null;
        $this->showConfirmationModal = false;
        $this->scannedBarcode = '';
        // Keep selectedWarehouseId and selectedCompanyId for continuous scanning
    }

    public function resetForm(): void
    {
        $this->resetProductForm();
        // Only reset warehouse if super admin
        if ($this->isSuperAdmin) {
            $this->selectedCompanyId = '';
            $this->selectedWarehouseId = '';
        }
    }

    public function cancelMovement(): void
    {
        $this->resetProductForm();
    }

    public function with(): array
    {
        return [
            'title' => 'Escáner de Código de Barras',
        ];
    }
}; ?>

<div class="px-4 sm:px-6 lg:px-8 py-8">
    <!-- Page Header -->
    <div class="mb-8">
        <flux:heading size="xl" class="text-zinc-900 dark:text-zinc-100">
            Escáner de Código de Barras
        </flux:heading>
        <flux:text class="mt-2 text-zinc-600 dark:text-zinc-400">
            Escanee códigos de barras o ingrese SKU para entrada y salida rápida de inventario
        </flux:text>
    </div>

    <!-- Success/Error Messages -->
    @if (session('success'))
        <flux:callout color="green" class="mb-6">
            {{ session('success') }}
        </flux:callout>
    @endif

    @if (session('error'))
        <flux:callout color="red" class="mb-6">
            {{ session('error') }}
        </flux:callout>
    @endif

    <!-- Step 1: Company & Warehouse Selection -->
    <flux:card class="mb-6">
        <flux:heading size="lg" class="mb-4">
            <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-blue-600 text-white text-sm font-bold mr-2">1</span>
            Seleccionar Ubicación
        </flux:heading>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @if($this->isSuperAdmin)
                <flux:field>
                    <flux:label>Empresa <span class="text-red-500">*</span></flux:label>
                    <flux:select wire:model.live="selectedCompanyId">
                        <option value="">Seleccione empresa</option>
                        @foreach($this->companies as $company)
                            <option value="{{ $company->id }}">{{ $company->name }}</option>
                        @endforeach
                    </flux:select>
                </flux:field>
            @endif

            <flux:field>
                <flux:label>Bodega <span class="text-red-500">*</span></flux:label>
                <flux:select wire:model.live="selectedWarehouseId" :disabled="empty($selectedCompanyId)">
                    <option value="">{{ empty($selectedCompanyId) ? 'Primero seleccione empresa' : 'Seleccione bodega' }}</option>
                    @foreach($this->warehouses as $warehouse)
                        <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                    @endforeach
                </flux:select>
            </flux:field>
        </div>

        @if($this->selectedWarehouse)
            <div class="mt-4 p-3 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800">
                <div class="flex items-center gap-2">
                    <flux:icon name="check-circle" class="h-5 w-5 text-green-600" />
                    <flux:text class="text-green-700 dark:text-green-300 font-medium">
                        Bodega seleccionada: {{ $this->selectedWarehouse->name }}
                    </flux:text>
                </div>
            </div>
        @endif
    </flux:card>

    <!-- Step 2: Movement Type Selection -->
    <flux:card class="mb-6 {{ !$this->canScan ? 'opacity-50' : '' }}">
        <flux:heading size="lg" class="mb-4">
            <span class="inline-flex items-center justify-center w-6 h-6 rounded-full {{ $this->canScan ? 'bg-blue-600' : 'bg-zinc-400' }} text-white text-sm font-bold mr-2">2</span>
            Tipo de Movimiento
        </flux:heading>
        <div class="flex gap-4">
            <flux:button
                wire:click="$set('movementType', 'entry')"
                :variant="$movementType === 'entry' ? 'primary' : 'outline'"
                icon="plus"
                :disabled="!$this->canScan"
                class="{{ $movementType === 'entry' ? 'bg-green-600 hover:bg-green-700' : '' }}"
            >
                Entrada
            </flux:button>
            <flux:button
                wire:click="$set('movementType', 'exit')"
                :variant="$movementType === 'exit' ? 'danger' : 'outline'"
                icon="minus"
                :disabled="!$this->canScan"
                class="{{ $movementType === 'exit' ? 'bg-red-600 hover:bg-red-700' : '' }}"
            >
                Salida
            </flux:button>
        </div>
    </flux:card>

    <!-- Step 3: Scanner Interface -->
    <flux:card class="mb-6 {{ !$this->canScan ? 'opacity-50' : '' }}">
        <flux:heading size="lg" class="mb-4">
            <span class="inline-flex items-center justify-center w-6 h-6 rounded-full {{ $this->canScan ? 'bg-blue-600' : 'bg-zinc-400' }} text-white text-sm font-bold mr-2">3</span>
            Escáner de Código
        </flux:heading>

        @if(!$this->canScan)
            <div class="text-center py-8">
                <flux:icon name="exclamation-circle" class="h-16 w-16 text-zinc-400 mx-auto mb-4" />
                <flux:text class="text-zinc-500 mb-4">
                    Seleccione {{ $this->isSuperAdmin && empty($selectedCompanyId) ? 'una empresa y ' : '' }}una bodega para comenzar a escanear
                </flux:text>
            </div>
        @elseif(!$isScanning)
            <div class="text-center py-8">
                <flux:icon name="qr-code" class="h-16 w-16 text-zinc-400 mx-auto mb-4" />
                <flux:text class="text-zinc-500 mb-4">
                    Listo para escanear códigos de barras
                </flux:text>
                <flux:button variant="primary" icon="camera" wire:click="startScanning">
                    Iniciar Cámara
                </flux:button>
            </div>
        @else
            <!-- Camera View -->
            <div class="relative">
                <div class="bg-zinc-900 rounded-lg p-8 text-center">
                    <video id="scanner-video" class="w-full max-w-md mx-auto rounded-lg" autoplay playsinline></video>
                    <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                        <div class="w-64 h-64 border-2 border-white opacity-50 rounded-lg"></div>
                    </div>
                </div>
                <div class="mt-4 flex justify-center gap-4">
                    <flux:button variant="outline" wire:click="stopScanning">
                        Detener Escáner
                    </flux:button>
                </div>
            </div>
        @endif

        <!-- Manual Barcode Entry -->
        @if($this->canScan)
            <div class="mt-6 pt-6 border-t border-zinc-200 dark:border-zinc-700">
                <flux:heading size="sm" class="mb-3">Entrada Manual</flux:heading>
                <div class="flex gap-3">
                    <div class="flex-1">
                        <flux:input
                            wire:model="scannedBarcode"
                            placeholder="Ingrese código de barras o SKU"
                            wire:keydown.enter="manualBarcodeEntry"
                        />
                    </div>
                    <flux:button variant="primary" wire:click="manualBarcodeEntry">
                        Buscar
                    </flux:button>
                </div>
            </div>
        @endif
    </flux:card>

    <!-- Confirmation Modal -->
    <flux:modal wire:model="showConfirmationModal" class="max-w-lg">
        @if($foundProduct)
            <flux:heading>
                <flux:heading size="lg">
                    {{ $movementType === 'entry' ? 'Confirmar Entrada' : 'Confirmar Salida' }}
                </flux:heading>
                <flux:text class="text-zinc-600 dark:text-zinc-400">
                    Producto encontrado. Por favor confirme los detalles del movimiento
                </flux:text>
            </flux:heading>

            <div class="space-y-6">
                <!-- Warehouse Info -->
                <div class="bg-blue-50 dark:bg-blue-900/20 p-3 rounded-lg border border-blue-200 dark:border-blue-800">
                    <div class="flex items-center gap-2">
                        <flux:icon name="building-office" class="h-5 w-5 text-blue-600" />
                        <flux:text class="text-blue-700 dark:text-blue-300 font-medium">
                            Bodega: {{ $this->selectedWarehouse?->name }}
                        </flux:text>
                    </div>
                </div>

                <!-- Product Info -->
                <div class="bg-zinc-50 dark:bg-zinc-800 p-4 rounded-lg">
                    <flux:heading size="sm" class="mb-2">Detalles del Producto</flux:heading>
                    <div class="space-y-2">
                        <div class="flex justify-between">
                            <flux:text class="text-zinc-600 dark:text-zinc-400">Nombre:</flux:text>
                            <flux:text class="font-medium">{{ $foundProduct->name }}</flux:text>
                        </div>
                        <div class="flex justify-between">
                            <flux:text class="text-zinc-600 dark:text-zinc-400">SKU:</flux:text>
                            <flux:text>{{ $foundProduct->sku }}</flux:text>
                        </div>
                        <div class="flex justify-between">
                            <flux:text class="text-zinc-600 dark:text-zinc-400">Código de Barras:</flux:text>
                            <flux:text>{{ $foundProduct->barcode ?? 'N/A' }}</flux:text>
                        </div>
                    </div>
                </div>

                <!-- Current Stock Info -->
                <div class="p-3 rounded-lg border {{ $this->currentStock > 0 ? 'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800' : 'bg-yellow-50 dark:bg-yellow-900/20 border-yellow-200 dark:border-yellow-800' }}">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <flux:icon name="cube" class="h-5 w-5 {{ $this->currentStock > 0 ? 'text-green-600' : 'text-yellow-600' }}" />
                            <flux:text class="{{ $this->currentStock > 0 ? 'text-green-700 dark:text-green-300' : 'text-yellow-700 dark:text-yellow-300' }}">
                                Stock actual en bodega:
                            </flux:text>
                        </div>
                        <flux:text class="font-bold text-lg {{ $this->currentStock > 0 ? 'text-green-700 dark:text-green-300' : 'text-yellow-700 dark:text-yellow-300' }}">
                            {{ number_format($this->currentStock ?? 0, 2) }} {{ $foundProduct->unitOfMeasure?->abbreviation ?? '' }}
                        </flux:text>
                    </div>
                    @if($movementType === 'exit' && $this->currentStock <= 0)
                        <div class="mt-2 text-sm text-red-600 dark:text-red-400">
                            <flux:icon name="exclamation-triangle" class="h-4 w-4 inline" />
                            No hay stock disponible para realizar una salida
                        </div>
                    @endif
                </div>

                <!-- Movement Details -->
                <flux:field>
                    <flux:label>
                        Cantidad <span class="text-red-500">*</span>
                        @if($movementType === 'exit' && $this->currentStock > 0)
                            <span class="text-sm font-normal text-zinc-500">(máximo: {{ number_format($this->currentStock, 2) }})</span>
                        @endif
                    </flux:label>
                    <flux:input
                        type="number"
                        step="0.01"
                        min="0.01"
                        :max="$movementType === 'exit' ? $this->currentStock : null"
                        wire:model="quantity"
                        placeholder="0.00"
                    />
                    <flux:error name="quantity" />
                </flux:field>

                <flux:field>
                    <flux:label>Número de Referencia</flux:label>
                    <flux:input wire:model="referenceNumber" placeholder="Referencia opcional" />
                    <flux:error name="referenceNumber" />
                </flux:field>

                <flux:field>
                    <flux:label>Notas</flux:label>
                    <flux:textarea wire:model="notes" placeholder="Notas opcionales" rows="3" />
                    <flux:error name="notes" />
                </flux:field>
            </div>

            <div class="flex gap-3 pt-4">
                <flux:button variant="outline" wire:click="cancelMovement">
                    Cancelar
                </flux:button>
                <flux:button
                    variant="{{ $movementType === 'entry' ? 'primary' : 'danger' }}"
                    wire:click="confirmMovement"
                    wire:loading.attr="disabled"
                    class="{{ $movementType === 'entry' ? 'bg-green-600 hover:bg-green-700' : 'bg-red-600 hover:bg-red-700' }}"
                >
                    <span wire:loading.remove>
                        {{ $movementType === 'entry' ? 'Registrar Entrada' : 'Registrar Salida' }}
                    </span>
                    <span wire:loading>Procesando...</span>
                </flux:button>
            </div>
        @endif
    </flux:modal>

    <!-- Quick Actions -->
    <div class="flex flex-wrap gap-4">
        <flux:button variant="outline" icon="table-cells" :href="route('inventory.movements.index')" wire:navigate>
            Movimientos
        </flux:button>
        <flux:button variant="outline" icon="arrow-path" :href="route('transfers.index')" wire:navigate>
            Traslados
        </flux:button>
        <flux:button variant="outline" icon="chart-bar" :href="route('inventory.dashboard')" wire:navigate>
            Panel de Control
        </flux:button>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/@zxing/library@latest/umd/index.min.js"></script>
<script>
document.addEventListener('livewire:initialized', () => {
    let stream = null;
    let codeReader = null;
    let scanning = false;

    // Initialize ZXing barcode reader
    codeReader = new ZXing.BrowserMultiFormatReader();

    // Start camera function
    async function startCamera() {
        try {
            const video = document.getElementById('scanner-video');
            if (video && !scanning) {
                scanning = true;

                // Start video stream
                stream = await navigator.mediaDevices.getUserMedia({
                    video: {
                        facingMode: 'environment', // Use back camera if available
                        width: { ideal: 640 },
                        height: { ideal: 480 }
                    }
                });

                video.srcObject = stream;

                // Start barcode detection
                startBarcodeDetection(video);
            }
        } catch (err) {
            console.error('Camera access error:', err);
            alert('No se pudo acceder a la cámara. Por favor, permite el acceso y usa la entrada manual.');
            @this.stopScanning();
            scanning = false;
        }
    }

    // Start barcode detection using ZXing
    function startBarcodeDetection(video) {
        if (!codeReader || !scanning) return;

        // Scan for codes continuously
        const scanLoop = () => {
            if (!scanning) return;

            codeReader.decodeOnceFromVideoDevice(undefined, 'scanner-video')
                .then((result) => {
                    if (result && result.text) {
                        console.log('Barcode detected:', result.text);

                        // Send barcode to Livewire component
                        @this.call('lookupProduct', result.text);

                        // Stop scanning after successful detection
                        stopCamera();
                    } else {
                        // Continue scanning if no barcode found
                        setTimeout(scanLoop, 250);
                    }
                })
                .catch((err) => {
                    // Continue scanning on error (unless it's a critical error)
                    if (scanning && !err.name.includes('NotFound')) {
                        setTimeout(scanLoop, 250);
                    }
                });
        };

        // Wait for video to be ready
        video.addEventListener('loadedmetadata', () => {
            setTimeout(scanLoop, 100);
        });

        // Start immediately if video is already ready
        if (video.readyState >= 2) {
            setTimeout(scanLoop, 100);
        }
    }

    // Stop camera function
    function stopCamera() {
        scanning = false;

        if (stream) {
            stream.getTracks().forEach(track => track.stop());
            stream = null;
        }

        // Reset video element
        const video = document.getElementById('scanner-video');
        if (video) {
            video.srcObject = null;
        }
    }

    // Handle component state changes
    @this.on('scanning-started', () => {
        setTimeout(() => startCamera(), 100);
    });

    @this.on('scanning-stopped', () => {
        stopCamera();
    });

    // Listen for scanning state changes from Livewire
    Livewire.on('start-camera', () => {
        startCamera();
    });

    Livewire.on('stop-camera', () => {
        stopCamera();
    });

    // Auto-start when scanning mode is activated
    window.addEventListener('livewire:update', () => {
        if (@this.isScanning && !scanning) {
            startCamera();
        }
    });

    // Cleanup on page unload
    window.addEventListener('beforeunload', () => {
        stopCamera();
    });
});
</script>
