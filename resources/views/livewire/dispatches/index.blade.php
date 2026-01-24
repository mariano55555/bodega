<?php

use App\Models\Dispatch;
use App\Models\DispatchDetail;
use App\Models\Employee;
use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\MovementReason;
use App\Models\Product;
use App\Models\UnitOfMeasure;
use App\Models\Warehouse;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('components.layouts.app')] class extends Component
{
    use WithPagination;

    public string $search = '';

    public string $statusFilter = '';

    public string $typeFilter = '';

    public string $warehouseFilter = '';

    public string $companyFilter = '';

    // Quick Dispatch Form
    public bool $showQuickDispatch = false;

    public string $company_id = '';

    public string $warehouse_id = '';

    public string $area_id = '';

    public string $employee_id = '';

    public string $dispatch_type = 'interno';

    public string $notes = '';

    // Multiple products support
    public array $quickItems = [];

    public function mount(): void
    {
        if (! $this->isSuperAdmin()) {
            $this->company_id = (string) auth()->user()->company_id;
        }
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedTypeFilter(): void
    {
        $this->resetPage();
    }

    public function updatedWarehouseFilter(): void
    {
        $this->resetPage();
    }

    public function updatedCompanyFilter(): void
    {
        $this->warehouseFilter = '';
        unset($this->filterWarehouses);
        $this->resetPage();
    }

    public function isSuperAdmin(): bool
    {
        return auth()->user()->isSuperAdmin();
    }

    public function updatedCompanyId(): void
    {
        $this->warehouse_id = '';
        $this->area_id = '';
        $this->employee_id = '';
        $this->quickItems = [$this->getEmptyQuickItem()];
    }

    public function updatedAreaId(): void
    {
        $this->employee_id = '';
    }

    public function updatedWarehouseId(): void
    {
        $this->quickItems = [$this->getEmptyQuickItem()];
        unset($this->products);
    }

    public function updatedQuickItems($value, $key): void
    {
        // When product_id changes, update stock info for that item
        if (str_contains($key, '.product_id')) {
            $index = (int) explode('.', $key)[0];
            $this->updateQuickItemStock($index);
        }
    }

    public function updatedShowQuickDispatch($value): void
    {
        if (! $value) {
            $this->resetQuickDispatchForm();
        }
    }

    public function resetQuickDispatchForm(): void
    {
        $this->warehouse_id = '';
        $this->area_id = '';
        $this->employee_id = '';
        $this->dispatch_type = 'interno';
        $this->notes = '';
        $this->quickItems = [$this->getEmptyQuickItem()];

        if ($this->isSuperAdmin()) {
            $this->company_id = '';
        }
    }

    private function getEmptyQuickItem(): array
    {
        return [
            'product_id' => '',
            'quantity' => '',
            'stock' => null,
            'unit' => '',
            'cost' => null,
        ];
    }

    public function addQuickItem(): void
    {
        $this->quickItems[] = $this->getEmptyQuickItem();
    }

    public function removeQuickItem(int $index): void
    {
        if (count($this->quickItems) > 1) {
            unset($this->quickItems[$index]);
            $this->quickItems = array_values($this->quickItems);
        }
    }

    private function updateQuickItemStock(int $index): void
    {
        $productId = $this->quickItems[$index]['product_id'] ?? '';

        if ($productId && $this->warehouse_id) {
            $inventory = Inventory::where('product_id', (int) $productId)
                ->where('warehouse_id', (int) $this->warehouse_id)
                ->where('is_active', true)
                ->first();

            $product = Product::with('unitOfMeasure')->find($productId);
            $this->quickItems[$index]['unit'] = $product?->unitOfMeasure?->abbreviation ?? '';
            $this->quickItems[$index]['stock'] = $inventory ? number_format($inventory->available_quantity, 2) : '0.00';
            $this->quickItems[$index]['cost'] = $product?->cost ? number_format($product->cost, 4) : '0.0000';
        } else {
            $this->quickItems[$index]['stock'] = null;
            $this->quickItems[$index]['unit'] = '';
            $this->quickItems[$index]['cost'] = null;
        }
    }

    #[Computed]
    public function companies()
    {
        if ($this->isSuperAdmin()) {
            return \App\Models\Company::active()->orderBy('name')->get(['id', 'name']);
        }

        return collect([]);
    }

    /**
     * Get warehouses for the filter dropdown (depends on companyFilter for super admins)
     */
    #[Computed]
    public function filterWarehouses()
    {
        if ($this->isSuperAdmin()) {
            // If super admin has selected a company, filter by that company
            if ($this->companyFilter) {
                return Warehouse::where('company_id', $this->companyFilter)
                    ->where('is_active', true)
                    ->orderBy('name')
                    ->get(['id', 'name', 'company_id']);
            }

            // Otherwise show all warehouses with company name
            return Warehouse::with('company:id,name')
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'company_id']);
        }

        // Regular users only see their company's warehouses
        return Warehouse::where('company_id', auth()->user()->company_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    #[Computed]
    public function warehouses()
    {
        if (! $this->company_id) {
            return collect([]);
        }

        return Warehouse::where('company_id', $this->company_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function areas()
    {
        if (! $this->company_id) {
            return collect([]);
        }

        return \App\Models\Area::where('company_id', $this->company_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function employees()
    {
        if (! $this->company_id || ! $this->area_id) {
            return collect([]);
        }

        return Employee::where('company_id', $this->company_id)
            ->where('area_id', $this->area_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function products()
    {
        if (! $this->company_id || ! $this->warehouse_id) {
            return collect([]);
        }

        // Get products that have stock in the selected warehouse
        $productIdsWithStock = Inventory::where('warehouse_id', $this->warehouse_id)
            ->where('is_active', true)
            ->pluck('product_id');

        return Product::where('company_id', $this->company_id)
            ->where('is_active', true)
            ->whereIn('id', $productIdsWithStock)
            ->with('unitOfMeasure')
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function units()
    {
        if (! $this->company_id) {
            return collect([]);
        }

        return UnitOfMeasure::forCompany($this->company_id)->active()->get();
    }

    public function showForm(): void
    {
        $this->showQuickDispatch = true;
        $this->resetQuickDispatchForm();

        // Re-set company for non-super-admins
        if (! $this->isSuperAdmin()) {
            $this->company_id = (string) auth()->user()->company_id;
        }

        // Initialize with one empty item
        $this->quickItems = [$this->getEmptyQuickItem()];
    }

    public function createQuickDispatch(): void
    {
        $this->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'dispatch_type' => 'required|in:venta,interno,externo,donacion',
            'quickItems' => 'required|array|min:1',
            'quickItems.*.product_id' => 'required|exists:products,id',
            'quickItems.*.quantity' => 'required|numeric|min:0.0001',
        ], [
            'quickItems.*.product_id.required' => 'Seleccione un producto.',
            'quickItems.*.quantity.required' => 'Ingrese la cantidad.',
            'quickItems.*.quantity.min' => 'La cantidad debe ser mayor a 0.',
        ], [
            'warehouse_id' => 'bodega',
            'dispatch_type' => 'tipo de despacho',
        ]);

        // Validate stock and get unit of measure for each item
        $validatedItems = [];
        foreach ($this->quickItems as $index => $item) {
            $productId = $item['product_id'];
            $quantity = (float) $item['quantity'];

            $product = Product::find($productId);
            if (! $product || ! $product->unit_of_measure_id) {
                $this->addError("quickItems.{$index}.product_id", 'El producto no tiene una unidad de medida asignada.');

                return;
            }

            $inventory = Inventory::where('product_id', $productId)
                ->where('warehouse_id', (int) $this->warehouse_id)
                ->where('is_active', true)
                ->first();

            $availableQty = $inventory ? (float) $inventory->available_quantity : 0;

            if ($quantity > $availableQty) {
                $this->addError("quickItems.{$index}.quantity", "La cantidad ({$quantity}) excede el stock disponible ({$availableQty}).");

                return;
            }

            $validatedItems[] = [
                'product_id' => $productId,
                'quantity' => $quantity,
                'unit_of_measure_id' => $product->unit_of_measure_id,
                'inventory' => $inventory,
            ];
        }

        $companyId = $this->isSuperAdmin() ? $this->company_id : auth()->user()->company_id;

        \DB::beginTransaction();
        try {
            // Create dispatch directly in 'despachado' status
            $dispatch = Dispatch::create([
                'company_id' => $companyId,
                'warehouse_id' => $this->warehouse_id,
                'employee_id' => $this->employee_id ?: null,
                'dispatch_type' => $this->dispatch_type,
                'notes' => $this->notes,
                'status' => 'despachado',
                'dispatched_at' => now(),
                'dispatched_by' => auth()->id(),
            ]);

            // Create inventory movement settings
            $movementType = match ($this->dispatch_type) {
                'venta' => 'sale',
                'interno' => 'transfer_out',
                'externo' => 'transfer_out',
                'donacion' => 'sale',
                default => 'sale',
            };

            $movementReasonCode = match ($this->dispatch_type) {
                'venta' => 'DISPATCH_SALE',
                'interno' => 'DISPATCH_INTERNAL',
                'externo' => 'DISPATCH_EXTERNAL',
                'donacion' => 'DISPATCH_DONATION',
                default => 'DISPATCH_INTERNAL',
            };

            $movementReason = MovementReason::where('code', $movementReasonCode)->first();
            if (! $movementReason) {
                $movementReason = MovementReason::where('movement_type', 'out')->first();
            }

            // Process each item
            foreach ($validatedItems as $item) {
                // Create dispatch detail
                DispatchDetail::create([
                    'dispatch_id' => $dispatch->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_of_measure_id' => $item['unit_of_measure_id'],
                    'unit_price' => 0,
                    'quantity_dispatched' => $item['quantity'],
                    'is_reserved' => true,
                    'reserved_by' => auth()->id(),
                    'reserved_at' => now(),
                ]);

                // Get current stock balance
                $currentStock = InventoryMovement::where('warehouse_id', $this->warehouse_id)
                    ->where('product_id', $item['product_id'])
                    ->whereNotNull('balance_quantity')
                    ->orderBy('movement_date', 'desc')
                    ->orderBy('id', 'desc')
                    ->first();

                $previousBalance = $currentStock ? $currentStock->balance_quantity : 0;
                $newBalance = $previousBalance - $item['quantity'];

                // Create inventory movement
                InventoryMovement::create([
                    'company_id' => $companyId,
                    'warehouse_id' => $this->warehouse_id,
                    'product_id' => $item['product_id'],
                    'movement_reason_id' => $movementReason?->id,
                    'dispatch_id' => $dispatch->id,
                    'movement_type' => $movementType,
                    'movement_date' => now(),
                    'quantity' => $item['quantity'],
                    'quantity_in' => 0,
                    'quantity_out' => $item['quantity'],
                    'balance_quantity' => $newBalance,
                    'previous_quantity' => $previousBalance,
                    'new_quantity' => $newBalance,
                    'unit_cost' => 0,
                    'total_cost' => 0,
                    'notes' => "Despacho Rápido {$dispatch->dispatch_number} - {$this->dispatch_type}",
                    'is_active' => true,
                    'active_at' => now(),
                    'created_by' => auth()->id(),
                ]);

                // Update inventory
                if ($item['inventory']) {
                    $item['inventory']->quantity -= $item['quantity'];
                    $item['inventory']->available_quantity -= $item['quantity'];
                    $item['inventory']->save();
                }
            }

            // Calculate totals
            $dispatch->calculateTotals();

            \DB::commit();

            $itemCount = count($validatedItems);
            \Flux::toast(
                variant: 'success',
                heading: 'Despacho Rápido Creado',
                text: "Despacho {$dispatch->dispatch_number} con {$itemCount} producto(s) procesado exitosamente.",
            );

            $this->showQuickDispatch = false;
            $this->resetQuickDispatchForm();

        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::error('Error creating quick dispatch: '.$e->getMessage());
            $this->addError('general', 'Error al procesar el despacho. Por favor intente nuevamente.');
        }
    }

    public function with(): array
    {
        $query = Dispatch::query()
            ->with(['employee', 'warehouse', 'warehouse.company'])
            // Regular users can only see their company's dispatches
            ->when(! $this->isSuperAdmin(), fn ($q) => $q->where('company_id', auth()->user()->company_id))
            // Super admin company filter
            ->when($this->isSuperAdmin() && $this->companyFilter, fn ($q) => $q->where('company_id', $this->companyFilter))
            ->when($this->search, function ($q) {
                $q->where(function ($query) {
                    $query->where('dispatch_number', 'like', "%{$this->search}%")
                        ->orWhere('document_number', 'like', "%{$this->search}%")
                        ->orWhere('recipient_name', 'like', "%{$this->search}%")
                        ->orWhereHas('employee', fn ($q) => $q->where('name', 'like', "%{$this->search}%"));
                });
            })
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->typeFilter, fn ($q) => $q->where('dispatch_type', $this->typeFilter))
            ->when($this->warehouseFilter, fn ($q) => $q->where('warehouse_id', (int) $this->warehouseFilter))
            ->latest('created_at');

        return [
            'dispatches' => $query->paginate(15),
        ];
    }

    public function delete(int $dispatchId): void
    {
        $dispatch = Dispatch::find($dispatchId);

        if (! $dispatch) {
            session()->flash('error', 'Despacho no encontrado.');
            return;
        }

        if (in_array($dispatch->status, ['despachado', 'entregado'])) {
            session()->flash('error', 'No se puede eliminar un despacho que ya fue despachado o entregado.');
            return;
        }

        $dispatch->delete();
        session()->flash('success', 'Despacho eliminado exitosamente.');
    }
}; ?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Despachos</flux:heading>
            <flux:text class="mt-1">Gestión de despachos y entregas</flux:text>
        </div>

        <div class="flex items-center gap-2">
            <flux:button variant="outline" icon="bolt" wire:click="showForm">
                Despacho Rápido
            </flux:button>
            <flux:button variant="primary" icon="plus" href="{{ route('dispatches.create') }}" wire:navigate>
                Nuevo Despacho
            </flux:button>
        </div>
    </div>

    @if (session('success'))
        <flux:callout variant="success" icon="check-circle">
            {{ session('success') }}
        </flux:callout>
    @endif

    @if (session('error'))
        <flux:callout variant="danger" icon="x-circle">
            {{ session('error') }}
        </flux:callout>
    @endif

    <div class="grid grid-cols-1 {{ $this->isSuperAdmin() ? 'md:grid-cols-6' : 'md:grid-cols-5' }} gap-4">
        <div class="{{ $this->isSuperAdmin() ? 'md:col-span-1' : 'md:col-span-2' }}">
            <flux:input
                wire:model.live.debounce.300ms="search"
                placeholder="Buscar..."
                icon="magnifying-glass"
            />
        </div>

        @if ($this->isSuperAdmin())
            <flux:select wire:model.live="companyFilter" placeholder="Todas las empresas">
                <option value="">Todas las empresas</option>
                @foreach ($this->companies as $company)
                    <option value="{{ $company->id }}">{{ $company->name }}</option>
                @endforeach
            </flux:select>
        @endif

        <flux:select wire:model.live="warehouseFilter" placeholder="Todas las bodegas">
            <option value="">Todas las bodegas</option>
            @foreach ($this->filterWarehouses as $wh)
                <option value="{{ $wh->id }}">
                    {{ $wh->name }}
                    @if ($this->isSuperAdmin() && ! $companyFilter && $wh->company)
                        ({{ $wh->company->name }})
                    @endif
                </option>
            @endforeach
        </flux:select>

        <flux:select wire:model.live="statusFilter" placeholder="Todos los estados">
            <option value="">Todos los estados</option>
            <option value="borrador">Borrador</option>
            <option value="pendiente">Pendiente</option>
            <option value="aprobado">Aprobado</option>
            <option value="despachado">Despachado</option>
            <option value="entregado">Entregado</option>
            <option value="cancelado">Anulado</option>
        </flux:select>

        <flux:select wire:model.live="typeFilter" placeholder="Todos los tipos">
            <option value="">Todos los tipos</option>
            <option value="venta">Venta</option>
            <option value="interno">Interno</option>
            <option value="externo">Externo</option>
            <option value="donacion">Donación</option>
        </flux:select>
    </div>

    <div class="overflow-x-auto">
        <flux:table>
            <flux:table.columns>
                <flux:table.column>Número</flux:table.column>
                <flux:table.column>Fecha</flux:table.column>
                @if ($this->isSuperAdmin())
                    <flux:table.column>Empresa</flux:table.column>
                @endif
                <flux:table.column>Empleado/Receptor</flux:table.column>
                <flux:table.column>Bodega</flux:table.column>
                <flux:table.column>Tipo</flux:table.column>
                <flux:table.column>Total</flux:table.column>
                <flux:table.column>Estado</flux:table.column>
                <flux:table.column>Acciones</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($dispatches as $dispatch)
                    <flux:table.row :key="$dispatch->id">
                        <flux:table.cell>
                            <div>
                                <div class="font-medium text-gray-900 dark:text-gray-100">
                                    {{ $dispatch->dispatch_number }}
                                </div>
                                @if ($dispatch->document_number)
                                    <div class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ ucfirst($dispatch->document_type ?? 'Doc') }}: {{ $dispatch->document_number }}
                                    </div>
                                @endif
                            </div>
                        </flux:table.cell>

                        <flux:table.cell>
                            {{ $dispatch->created_at->format('d/m/Y') }}
                        </flux:table.cell>

                        @if ($this->isSuperAdmin())
                            <flux:table.cell>
                                {{ $dispatch->warehouse->company->name ?? 'N/A' }}
                            </flux:table.cell>
                        @endif

                        <flux:table.cell>
                            <div>
                                @if ($dispatch->employee)
                                    <div class="font-medium">{{ $dispatch->employee->name }}</div>
                                @endif
                                @if ($dispatch->recipient_name)
                                    <div class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ $dispatch->recipient_name }}
                                    </div>
                                @endif
                            </div>
                        </flux:table.cell>

                        <flux:table.cell>
                            {{ $dispatch->warehouse->name }}
                        </flux:table.cell>

                        <flux:table.cell>
                            <flux:badge
                                size="sm"
                                :color="match($dispatch->dispatch_type) {
                                    'venta' => 'emerald',
                                    'interno' => 'sky',
                                    'externo' => 'amber',
                                    'donacion' => 'pink',
                                    default => 'zinc'
                                }"
                                :icon="match($dispatch->dispatch_type) {
                                    'venta' => 'currency-dollar',
                                    'interno' => 'arrow-path',
                                    'externo' => 'arrow-up-right',
                                    'donacion' => 'gift',
                                    default => 'cube'
                                }"
                            >
                                {{ $dispatch->getDispatchTypeSpanishAttribute() }}
                            </flux:badge>
                        </flux:table.cell>

                        <flux:table.cell>
                            ${{ number_format($dispatch->total, 2) }}
                        </flux:table.cell>

                        <flux:table.cell>
                            <flux:badge
                                size="sm"
                                :color="match($dispatch->status) {
                                    'borrador' => 'zinc',
                                    'pendiente' => 'amber',
                                    'aprobado' => 'sky',
                                    'despachado' => 'indigo',
                                    'entregado' => 'emerald',
                                    'cancelado' => 'red',
                                    default => 'zinc'
                                }"
                                :icon="match($dispatch->status) {
                                    'borrador' => 'pencil-square',
                                    'pendiente' => 'clock',
                                    'aprobado' => 'check-circle',
                                    'despachado' => 'truck',
                                    'entregado' => 'check-badge',
                                    'cancelado' => 'x-circle',
                                    default => 'question-mark-circle'
                                }"
                            >
                                {{ $dispatch->getStatusSpanishAttribute() }}
                            </flux:badge>
                        </flux:table.cell>

                        <flux:table.cell>
                            <div class="flex items-center gap-2">
                                <flux:button size="sm" variant="ghost" icon="eye" href="{{ route('dispatches.show', $dispatch) }}" wire:navigate />

                                @if ($dispatch->canBeEdited())
                                    <flux:button size="sm" variant="ghost" icon="pencil" href="{{ route('dispatches.edit', $dispatch) }}" wire:navigate />
                                @endif

                                @if ($dispatch->status === 'cancelado')
                                    <flux:button
                                        size="sm"
                                        variant="ghost"
                                        icon="trash"
                                        wire:click="delete({{ $dispatch->id }})"
                                        wire:confirm="¿Está seguro de eliminar este despacho?"
                                    />
                                @endif
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell :colspan="$this->isSuperAdmin() ? 10 : 9" class="text-center py-12">
                            <div class="text-gray-500 dark:text-gray-400">
                                No se encontraron despachos
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>

    <div class="mt-4">
        {{ $dispatches->links() }}
    </div>

    {{-- Quick Dispatch Modal --}}
    <flux:modal name="quick-dispatch" class="max-w-2xl" variant="flyout" wire:model="showQuickDispatch">
        <flux:heading>
            <flux:heading size="lg">Despacho Rápido</flux:heading>
            <flux:text class="text-zinc-600 dark:text-zinc-400">
                Crear y procesar un despacho inmediatamente (sin flujo de aprobación)
            </flux:text>
        </flux:heading>

        <div class="space-y-6 mt-6">
            @error('general')
                <flux:callout variant="danger" icon="x-circle">
                    {{ $message }}
                </flux:callout>
            @enderror

            @if($this->isSuperAdmin())
                <flux:field>
                    <flux:label badge="Requerido">Empresa</flux:label>
                    <flux:select variant="listbox" searchable wire:model.live="company_id" placeholder="Seleccione una empresa">
                        @foreach ($this->companies as $company)
                            <flux:select.option value="{{ $company->id }}">{{ $company->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="company_id" />
                </flux:field>
            @endif

            <flux:field>
                <flux:label badge="Requerido">Bodega</flux:label>
                <flux:select variant="listbox" searchable wire:model.live="warehouse_id" :disabled="$this->isSuperAdmin() && !$company_id" placeholder="Seleccione bodega">
                    @foreach ($this->warehouses as $warehouse)
                        <flux:select.option value="{{ $warehouse->id }}">{{ $warehouse->name }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:error name="warehouse_id" />
            </flux:field>

            <flux:field>
                <flux:label badge="Requerido">Área/Departamento</flux:label>
                <flux:select variant="listbox" searchable wire:model.live="area_id" :disabled="$this->isSuperAdmin() && !$company_id" placeholder="Seleccione área">
                    @foreach ($this->areas as $area)
                        <flux:select.option value="{{ $area->id }}">{{ $area->name }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:description>Seleccione el área solicitante del despacho</flux:description>
                <flux:error name="area_id" />
            </flux:field>

            <flux:field>
                <flux:label>Empleado Solicitante</flux:label>
                <flux:select variant="listbox" searchable wire:model="employee_id" :disabled="!$this->area_id" placeholder="Sin empleado">
                    @foreach ($this->employees as $employee)
                        <flux:select.option value="{{ $employee->id }}">{{ $employee->name }}{{ $employee->position ? ' - ' . $employee->position : '' }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:description>El empleado solo se puede seleccionar después de elegir el área</flux:description>
            </flux:field>

            <flux:separator />

            {{-- Products Section --}}
            <div>
                <div class="flex items-center justify-between mb-3">
                    <flux:label badge="Requerido">Productos</flux:label>
                    <flux:button
                        type="button"
                        variant="outline"
                        size="sm"
                        icon="plus"
                        wire:click="addQuickItem"
                        :disabled="!$warehouse_id"
                    >
                        Agregar producto
                    </flux:button>
                </div>

                @if(!$warehouse_id)
                    <flux:callout variant="warning" icon="exclamation-triangle" class="mb-3">
                        Debe seleccionar una bodega para ver los productos disponibles.
                    </flux:callout>
                @endif

                <div class="space-y-3">
                    @foreach($quickItems as $index => $item)
                        <div wire:key="quick-item-{{ $index }}" class="p-3 border rounded-lg bg-zinc-50 dark:bg-zinc-800/50">
                            <div class="flex items-start gap-3">
                                {{-- Product Select and Info --}}
                                <div class="flex-1 space-y-2">
                                    <flux:select
                                        variant="listbox"
                                        searchable
                                        wire:model.live="quickItems.{{ $index }}.product_id"
                                        :disabled="!$warehouse_id"
                                        placeholder="Seleccione producto"
                                    >
                                        @foreach ($this->products as $product)
                                            <flux:select.option value="{{ $product->id }}">
                                                {{ $product->name }}
                                                @if($product->unitOfMeasure)
                                                    ({{ $product->unitOfMeasure->abbreviation }})
                                                @endif
                                            </flux:select.option>
                                        @endforeach
                                    </flux:select>
                                    @error("quickItems.{$index}.product_id")
                                        <flux:text class="text-red-600 dark:text-red-400 text-sm">{{ $message }}</flux:text>
                                    @enderror

                                    {{-- Product info badges --}}
                                    @if(!empty($item['stock']))
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium {{ $item['stock'] === '0.00' ? 'bg-red-100 dark:bg-red-900 text-red-700 dark:text-red-300' : 'bg-green-100 dark:bg-green-900 text-green-700 dark:text-green-300' }}">
                                                Stock: {{ $item['stock'] }} {{ $item['unit'] }}
                                            </span>
                                            @if(!empty($item['unit']))
                                                <span class="inline-flex items-center rounded-md bg-zinc-100 dark:bg-zinc-700 px-2 py-1 text-xs font-medium text-zinc-600 dark:text-zinc-300">
                                                    Unidad: {{ $item['unit'] }}
                                                </span>
                                            @endif
                                            @if(!empty($item['cost']))
                                                <span class="inline-flex items-center rounded-md bg-blue-100 dark:bg-blue-900 px-2 py-1 text-xs font-medium text-blue-700 dark:text-blue-300">
                                                    Precio: ${{ $item['cost'] }}
                                                </span>
                                            @endif
                                        </div>
                                    @endif
                                </div>

                                {{-- Quantity --}}
                                <div class="w-24">
                                    <flux:input
                                        type="number"
                                        step="0.01"
                                        min="0.01"
                                        wire:model="quickItems.{{ $index }}.quantity"
                                        placeholder="Cant."
                                    />
                                    @error("quickItems.{$index}.quantity")
                                        <flux:text class="text-red-600 dark:text-red-400 text-xs">{{ $message }}</flux:text>
                                    @enderror
                                </div>

                                {{-- Remove Button --}}
                                @if(count($quickItems) > 1)
                                    <flux:button
                                        type="button"
                                        variant="ghost"
                                        size="sm"
                                        icon="trash"
                                        wire:click="removeQuickItem({{ $index }})"
                                        class="text-red-600 hover:text-red-700 dark:text-red-400"
                                    />
                                @else
                                    <div class="w-8"></div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
                @error('quickItems')
                    <flux:text class="text-red-600 dark:text-red-400 text-sm mt-2">{{ $message }}</flux:text>
                @enderror
            </div>

            <flux:field>
                <flux:label>Notas</flux:label>
                <flux:textarea wire:model="notes" rows="2" placeholder="Notas adicionales del despacho" />
            </flux:field>

            {{-- Summary --}}
            @php
                $hasProducts = collect($quickItems)->filter(fn($i) => !empty($i['product_id']) && !empty($i['quantity']))->count() > 0;
            @endphp
            @if($hasProducts && $warehouse_id)
                <div class="bg-zinc-50 dark:bg-zinc-800 p-4 rounded-lg border">
                    <flux:heading size="sm" class="mb-3">Resumen del Despacho</flux:heading>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-zinc-600 dark:text-zinc-400">Bodega:</span>
                            <span>{{ $this->warehouses->firstWhere('id', (int) $warehouse_id)?->name }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-zinc-600 dark:text-zinc-400">Productos:</span>
                            <span>{{ collect($quickItems)->filter(fn($i) => !empty($i['product_id']))->count() }}</span>
                        </div>
                        <flux:separator class="my-2" />
                        @foreach($quickItems as $item)
                            @if(!empty($item['product_id']) && !empty($item['quantity']))
                                @php
                                    $prod = $this->products->firstWhere('id', (int) $item['product_id']);
                                @endphp
                                <div class="flex justify-between text-xs">
                                    <span class="text-zinc-500 truncate max-w-[200px]">{{ $prod?->name }}</span>
                                    <span class="font-medium">{{ number_format((float) $item['quantity'], 2) }} {{ $item['unit'] }}</span>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        <flux:separator class="my-6" />

        <div class="flex justify-end gap-3">
            <flux:button variant="ghost" x-on:click="$flux.modal('quick-dispatch').close()">
                Cancelar
            </flux:button>
            <flux:button
                variant="primary"
                wire:click="createQuickDispatch"
                wire:loading.attr="disabled"
                :disabled="!$warehouse_id"
            >
                <span wire:loading.remove wire:target="createQuickDispatch">Crear y Procesar Despacho</span>
                <span wire:loading wire:target="createQuickDispatch">Procesando...</span>
            </flux:button>
        </div>
    </flux:modal>

    {{-- Mobile Quick Action Button --}}
    <div class="fixed bottom-6 right-6 lg:hidden">
        <flux:button
            variant="primary"
            icon="bolt"
            wire:click="showForm"
            class="rounded-full p-4 shadow-lg"
        >
            <span class="sr-only">Despacho Rápido</span>
        </flux:button>
    </div>
</div>
