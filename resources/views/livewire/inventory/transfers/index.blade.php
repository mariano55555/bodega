<?php

use Livewire\Volt\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use App\Models\{Product, Warehouse, Inventory, InventoryTransfer, InventoryTransferDetail};

new #[Layout('components.layouts.app')] class extends Component
{
    use WithPagination;

    public string $search = '';

    #[Url(history: true)]
    public int $perPage = 15;
    public string $company_id = '';
    public string $selectedProductId = '';
    public string $fromWarehouseId = '';
    public string $toWarehouseId = '';
    public string $quantity = '';
    public string $notes = '';
    public string $referenceNumber = '';
    public bool $showTransferForm = false;
    public ?string $availableStock = null;

    public function mount(): void
    {
        // Set default company to user's company if not super admin
        if (!auth()->user()->isSuperAdmin()) {
            $this->company_id = auth()->user()->company_id;
        }
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    public function updatedCompanyId(): void
    {
        // Reset selections when company changes
        $this->selectedProductId = '';
        $this->fromWarehouseId = '';
        $this->toWarehouseId = '';
        $this->availableStock = null;
    }

    public function updatedShowTransferForm($value): void
    {
        // When modal closes (value becomes false), reset all form values
        if (!$value) {
            $this->selectedProductId = '';
            $this->fromWarehouseId = '';
            $this->toWarehouseId = '';
            $this->quantity = '';
            $this->notes = '';
            $this->referenceNumber = '';
            $this->availableStock = null;

            // Also reset company_id for super admins on close
            if ($this->isSuperAdmin()) {
                $this->company_id = '';
            }
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
            return \App\Models\Company::active()->orderBy('name')->get(['id', 'name']);
        }

        return collect([]);
    }

    #[Computed]
    public function products()
    {
        $query = Product::query();

        if ($this->company_id) {
            $query->where('company_id', $this->company_id);
        }

        return $query->when($this->search, function ($query) {
            $query->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('sku', 'like', '%' . $this->search . '%');
        })->orderBy('name')->get();
    }

    #[Computed]
    public function warehouses()
    {
        $query = Warehouse::query();

        if ($this->company_id) {
            $query->where('company_id', $this->company_id);
        }

        return $query->orderBy('name')->get();
    }

    #[Computed]
    public function transfers()
    {
        return InventoryTransfer::query()
            ->with(['fromWarehouse', 'toWarehouse'])
            ->latest('created_at')
            ->paginate($this->perPage);
    }

    public function hasEnoughStock(): bool
    {
        if ($this->availableStock === null || $this->availableStock === '0.00') {
            return false;
        }
        return (float) str_replace(',', '', $this->availableStock) > 0;
    }

    public function showForm(): void
    {
        $this->showTransferForm = true;
        $this->resetForm();
    }

    public function resetForm(): void
    {
        // Don't reset company_id for super admins - they need to keep their selection
        $this->selectedProductId = '';
        $this->fromWarehouseId = '';
        $this->toWarehouseId = '';
        $this->quantity = '';
        $this->notes = '';
        $this->referenceNumber = '';
        $this->availableStock = null;
    }

    public function updatedFromWarehouseId(): void
    {
        $this->checkAvailableStock();
    }

    public function updatedSelectedProductId(): void
    {
        $this->checkAvailableStock();
    }

    private function checkAvailableStock(): void
    {
        if ($this->selectedProductId !== '' && $this->fromWarehouseId !== '') {
            $inventory = Inventory::where([
                'product_id' => (int) $this->selectedProductId,
                'warehouse_id' => (int) $this->fromWarehouseId,
            ])->first();

            $this->availableStock = $inventory ? number_format($inventory->available_quantity, 2) : '0.00';
        } else {
            $this->availableStock = null;
        }
    }

    public function createTransfer(): void
    {
        $this->validate([
            'selectedProductId' => 'required|exists:products,id',
            'fromWarehouseId' => 'required|exists:warehouses,id',
            'toWarehouseId' => 'required|exists:warehouses,id|different:fromWarehouseId',
            'quantity' => 'required|numeric|min:0.01',
            'referenceNumber' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:500',
        ], [], [
            'selectedProductId' => 'producto',
            'fromWarehouseId' => 'almacén de origen',
            'toWarehouseId' => 'almacén de destino',
            'quantity' => 'cantidad',
            'referenceNumber' => 'número de referencia',
            'notes' => 'notas',
        ]);

        // Cast IDs to integers for database queries
        $productId = (int) $this->selectedProductId;
        $fromWarehouse = (int) $this->fromWarehouseId;
        $toWarehouse = (int) $this->toWarehouseId;

        // Check available stock
        $fromInventory = Inventory::where([
            'product_id' => $productId,
            'warehouse_id' => $fromWarehouse,
        ])->first();

        if (!$fromInventory || $fromInventory->available_quantity < (float) $this->quantity) {
            $this->addError('quantity', __('Insufficient stock available for transfer'));
            return;
        }

        // Process transfer - Create with pending status (workflow-based)
        \DB::beginTransaction();
        try {
            // Create the InventoryTransfer record with PENDING status
            $transfer = InventoryTransfer::create([
                'from_warehouse_id' => $fromWarehouse,
                'to_warehouse_id' => $toWarehouse,
                'status' => 'pending', // Requires approval workflow
                'reason' => $this->notes ?: 'Traslado entre bodegas',
                'notes' => $this->referenceNumber ? "Ref: {$this->referenceNumber}" : null,
                'requested_at' => now(),
                'is_active' => true,
            ]);

            // Create the transfer detail record
            InventoryTransferDetail::create([
                'transfer_id' => $transfer->id,
                'product_id' => $productId,
                'quantity' => (float) $this->quantity,
                'notes' => $this->notes,
            ]);

            // NOTE: Inventory is NOT moved here - it will be moved when the transfer is shipped/received
            // The workflow is: Pending -> Approved -> Shipped (inventory out) -> Received (inventory in)

            \DB::commit();

            // Flash success message
            \Flux::toast(
                variant: 'success',
                heading: 'Traslado Creado',
                text: "Traslado {$transfer->transfer_number} creado. Pendiente de aprobación.",
            );

            $this->showTransferForm = false;
            $this->resetForm();

        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::error('Error creating transfer: ' . $e->getMessage());
            $this->addError('general', __('Error processing transfer. Please try again.'));
        }
    }

    public function with(): array
    {
        return [
            'title' => __('Transfer Management'),
        ];
    }
}; ?>

<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl" class="text-zinc-900 dark:text-zinc-100">
                {{ __('Inter-warehouse Transfer') }}
            </flux:heading>
            <flux:text class="mt-2 text-zinc-600 dark:text-zinc-400">
                {{ __('Transfer products between warehouses with proper validation') }}
            </flux:text>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="flex flex-wrap gap-4">
        <flux:button
            variant="primary"
            icon="plus"
            :href="route('transfers.create')"
            wire:navigate
        >
            Nuevo Traslado
        </flux:button>

        <flux:button
            variant="outline"
            icon="bolt"
            wire:click="showForm"
        >
            Traslado Rápido
        </flux:button>

        <flux:button variant="outline" icon="chart-bar" :href="route('reports.movements.transfers')" wire:navigate>
            Reporte de Traslados
        </flux:button>
    </div>

    <!-- Success Message -->
    @if (session('success'))
        <flux:callout color="green">
            {{ session('success') }}
        </flux:callout>
    @endif

    <!-- Transfer Form Modal -->
    <flux:modal name="transfer-form" class="max-w-3xl" variant="flyout" wire:model="showTransferForm">
        <flux:heading>
            <flux:heading size="lg">Traslado Rápido</flux:heading>
            <flux:text class="text-zinc-600 dark:text-zinc-400">
                Trasladar un producto entre bodegas (requiere aprobación)
            </flux:text>
        </flux:heading>

            <div class="space-y-6 mt-6">
                <!-- General Error -->
                @error('general')
                    <flux:callout color="red" class="mb-6">
                        {{ $message }}
                    </flux:callout>
                @enderror

                <div class="space-y-6">
                    @if($this->isSuperAdmin())
                        <!-- Company Selection for Super Admin -->
                        <flux:field>
                            <flux:label badge="Requerido">Empresa</flux:label>
                            <flux:select wire:model.live="company_id">
                                <option value="">Seleccione una empresa</option>
                                @foreach ($this->companies as $company)
                                    <option value="{{ $company->id }}">{{ $company->name }}</option>
                                @endforeach
                            </flux:select>
                            <flux:error name="company_id" />
                        </flux:field>
                    @endif

                    <!-- Product Selection -->
                    <flux:field>
                        <flux:label badge="Requerido">{{ __('Product') }}</flux:label>
                        <flux:select wire:model.live="selectedProductId" placeholder="{{ __('Select a product') }}" :disabled="$this->isSuperAdmin() && !$company_id">
                            @foreach($this->products as $product)
                                <flux:select.option value="{{ $product->id }}">
                                    {{ $product->name }} ({{ $product->sku }})
                                </flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:error name="selectedProductId" />
                    </flux:field>

                    <!-- From and To Warehouses -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <flux:field>
                            <flux:label badge="Requerido">{{ __('From Warehouse') }}</flux:label>
                            <flux:select wire:model.live="fromWarehouseId" placeholder="{{ __('Select source warehouse') }}" :disabled="$this->isSuperAdmin() && !$company_id">
                                @foreach($this->warehouses as $warehouse)
                                    <flux:select.option value="{{ $warehouse->id }}">
                                        {{ $warehouse->name }}
                                    </flux:select.option>
                                @endforeach
                            </flux:select>
                            <flux:error name="fromWarehouseId" />
                        </flux:field>

                        <flux:field>
                            <flux:label badge="Requerido">{{ __('To Warehouse') }}</flux:label>
                            <flux:select wire:model="toWarehouseId" placeholder="{{ __('Select destination warehouse') }}" :disabled="$this->isSuperAdmin() && !$company_id">
                                @foreach($this->warehouses as $warehouse)
                                    @if((string) $warehouse->id !== $fromWarehouseId)
                                        <flux:select.option value="{{ $warehouse->id }}">
                                            {{ $warehouse->name }}
                                        </flux:select.option>
                                    @endif
                                @endforeach
                            </flux:select>
                            <flux:error name="toWarehouseId" />
                        </flux:field>
                    </div>

                    <!-- Available Stock Info -->
                    @if($availableStock !== null)
                        <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg border border-blue-200 dark:border-blue-800">
                            <div class="flex items-center gap-2">
                                <flux:icon name="information-circle" class="h-5 w-5 text-blue-500" />
                                <flux:text class="text-blue-700 dark:text-blue-300 font-medium">
                                    {{ __('Available') }}: {{ $availableStock }} {{ __('units') }}
                                </flux:text>
                            </div>
                        </div>
                    @endif

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Quantity -->
                        <flux:field>
                            <flux:label badge="Requerido">{{ __('Transfer Quantity') }}</flux:label>
                            <flux:input
                                type="number"
                                step="0.01"
                                min="0.01"
                                wire:model="quantity"
                                placeholder="0.00"
                            />
                            <flux:error name="quantity" />
                        </flux:field>

                        <!-- Reference Number -->
                        <flux:field>
                            <flux:label>{{ __('Reference Number') }}</flux:label>
                            <flux:input
                                wire:model="referenceNumber"
                                placeholder="{{ __('Transfer reference') }}"
                            />
                            <flux:error name="referenceNumber" />
                        </flux:field>
                    </div>

                    <!-- Notes -->
                    <flux:field>
                        <flux:label>{{ __('Notes') }}</flux:label>
                        <flux:textarea
                            wire:model="notes"
                            placeholder="{{ __('Transfer notes and comments') }}"
                            rows="3"
                        />
                        <flux:error name="notes" />
                    </flux:field>

                    <!-- Transfer Summary -->
                    @if($selectedProductId !== '' && $fromWarehouseId !== '' && $toWarehouseId !== '' && $quantity)
                        <div class="bg-zinc-50 dark:bg-zinc-800 p-4 rounded-lg border">
                            <flux:heading size="sm" class="mb-3">{{ __('Transfer Summary') }}</flux:heading>
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-zinc-600 dark:text-zinc-400">{{ __('Product') }}:</span>
                                    <span>{{ $this->products->firstWhere('id', (int) $selectedProductId)?->name }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-zinc-600 dark:text-zinc-400">{{ __('From') }}:</span>
                                    <span>{{ $this->warehouses->firstWhere('id', (int) $fromWarehouseId)?->name }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-zinc-600 dark:text-zinc-400">{{ __('To') }}:</span>
                                    <span>{{ $this->warehouses->firstWhere('id', (int) $toWarehouseId)?->name }}</span>
                                </div>
                                <div class="flex justify-between font-medium">
                                    <span class="text-zinc-600 dark:text-zinc-400">{{ __('Quantity') }}:</span>
                                    <span>{{ number_format((float) $quantity, 2) }} {{ __('units') }}</span>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <flux:separator class="my-6" />

            <div class="flex justify-end gap-3">
                <flux:button variant="ghost" x-on:click="$flux.modal('transfer-form').close()">
                    {{ __('Cancel') }}
                </flux:button>
                <flux:button
                    variant="primary"
                    wire:click="createTransfer"
                    wire:loading.attr="disabled"
                    :disabled="$availableStock === '0.00' || $availableStock === null"
                >
                    <span wire:loading.remove>{{ __('Create Transfer') }}</span>
                    <span wire:loading>{{ __('Processing') }}...</span>
                </flux:button>
            </div>

            @if($availableStock === '0.00')
                <div class="mt-4">
                    <flux:callout color="amber" icon="exclamation-triangle">
                        {{ __('No stock available for this product in the selected warehouse. Select a different product or warehouse.') }}
                    </flux:callout>
                </div>
            @endif
    </flux:modal>

    <!-- Transfer Guidelines -->
    <flux:card>
        <flux:heading size="lg" class="mb-6">{{ __('Transfer Guidelines') }}</flux:heading>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div class="flex items-start gap-3">
                <flux:icon name="check-circle" class="h-6 w-6 text-green-500 mt-1 flex-shrink-0" />
                <div>
                    <flux:text class="font-medium">{{ __('Stock Validation') }}</flux:text>
                    <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                        {{ __('Automatically checks available stock before transfer') }}
                    </flux:text>
                </div>
            </div>

            <div class="flex items-start gap-3">
                <flux:icon name="clock" class="h-6 w-6 text-blue-500 mt-1 flex-shrink-0" />
                <div>
                    <flux:text class="font-medium">{{ __('Real-time Updates') }}</flux:text>
                    <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                        {{ __('Stock levels update immediately after transfer') }}
                    </flux:text>
                </div>
            </div>

            <div class="flex items-start gap-3">
                <flux:icon name="shield-check" class="h-6 w-6 text-purple-500 mt-1 flex-shrink-0" />
                <div>
                    <flux:text class="font-medium">{{ __('Audit Trail') }}</flux:text>
                    <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                        {{ __('Complete record of all transfer activities') }}
                    </flux:text>
                </div>
            </div>
        </div>
    </flux:card>

    <!-- Recent Transfers -->
    <flux:card>
        <div class="mb-6">
            <flux:heading size="lg">{{ __('Recent Transfers') }}</flux:heading>
            <flux:text class="mt-2 text-zinc-600 dark:text-zinc-400">
                {{ __('Latest inter-warehouse transfers') }}
            </flux:text>
        </div>

        <!-- Stats and Per Page -->
        <div class="flex items-center justify-between mb-4">
            <div class="text-sm text-gray-600 dark:text-gray-400">
                Mostrando {{ $this->transfers->firstItem() ?? 0 }} - {{ $this->transfers->lastItem() ?? 0 }} de {{ $this->transfers->total() }} traslados
            </div>
            <div class="flex items-center gap-2">
                <flux:text class="text-sm">Por página:</flux:text>
                <flux:select wire:model.live="perPage" class="w-20">
                    <option value="10">10</option>
                    <option value="15">15</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </flux:select>
            </div>
        </div>

        <div class="overflow-x-auto">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>N° Traslado</flux:table.column>
                    <flux:table.column>Origen</flux:table.column>
                    <flux:table.column>Destino</flux:table.column>
                    <flux:table.column>Razón</flux:table.column>
                    <flux:table.column>Estado</flux:table.column>
                    <flux:table.column>Fecha</flux:table.column>
                    <flux:table.column>Acciones</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse($this->transfers as $transfer)
                        <flux:table.row wire:key="transfer-{{ $transfer->id }}">
                            <flux:table.cell>
                                <span class="font-medium">{{ $transfer->transfer_number }}</span>
                            </flux:table.cell>
                            <flux:table.cell>
                                {{ $transfer->fromWarehouse?->name ?? '-' }}
                            </flux:table.cell>
                            <flux:table.cell>
                                {{ $transfer->toWarehouse?->name ?? '-' }}
                            </flux:table.cell>
                            <flux:table.cell>
                                <span class="text-sm text-zinc-600 dark:text-zinc-400">
                                    {{ Str::limit($transfer->reason, 40) }}
                                </span>
                            </flux:table.cell>
                            <flux:table.cell>
                                @php
                                    $statusColors = [
                                        'draft' => 'zinc',
                                        'pending' => 'yellow',
                                        'approved' => 'blue',
                                        'in_transit' => 'purple',
                                        'received' => 'cyan',
                                        'completed' => 'green',
                                        'cancelled' => 'red',
                                    ];
                                    $statusLabels = [
                                        'draft' => 'Borrador',
                                        'pending' => 'Pendiente',
                                        'approved' => 'Aprobado',
                                        'in_transit' => 'En Tránsito',
                                        'received' => 'Recibido',
                                        'completed' => 'Completado',
                                        'cancelled' => 'Cancelado',
                                    ];
                                @endphp
                                <flux:badge :color="$statusColors[$transfer->status] ?? 'zinc'" size="sm">
                                    {{ $statusLabels[$transfer->status] ?? ucfirst($transfer->status) }}
                                </flux:badge>
                            </flux:table.cell>
                            <flux:table.cell>
                                {{ $transfer->created_at?->format('d/m/Y H:i') }}
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:button
                                    variant="ghost"
                                    size="sm"
                                    icon="eye"
                                    :href="route('transfers.show', $transfer->transfer_number)"
                                    wire:navigate
                                >
                                    Ver
                                </flux:button>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="7" class="text-center py-8">
                                <flux:icon name="arrow-path" class="h-12 w-12 text-zinc-400 mx-auto mb-3" />
                                <flux:text class="text-zinc-500">No se encontraron traslados</flux:text>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>

        @if($this->transfers->hasPages())
            <div class="mt-4">
                {{ $this->transfers->links() }}
            </div>
        @endif
    </flux:card>

    <!-- Mobile-Friendly Quick Actions -->
    <div class="fixed bottom-6 right-6 lg:hidden">
        <flux:button
            variant="primary"
            icon="arrow-path"
            wire:click="showForm"
            class="bg-blue-600 hover:bg-blue-700 text-white rounded-full p-4 shadow-lg"
        >
            <span class="sr-only">{{ __('Create Transfer') }}</span>
        </flux:button>
    </div>
</div>
