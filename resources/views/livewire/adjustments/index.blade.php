<?php

use App\Models\InventoryAdjustment;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('components.layouts.app')] class extends Component {
    use WithPagination;

    public string $search = '';
    public string $statusFilter = '';
    public string $typeFilter = '';
    public string $warehouseFilter = '';

    public ?int $selectedAdjustmentId = null;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function isSuperAdmin(): bool
    {
        return auth()->user()->isSuperAdmin();
    }

    public function with(): array
    {
        $query = InventoryAdjustment::query()
            ->when(!$this->isSuperAdmin(), fn ($q) => $q->where('company_id', auth()->user()->company_id))
            ->with(['product', 'warehouse', 'warehouse.company', 'creator'])
            ->when($this->search, function ($q) {
                $q->where(function ($query) {
                    $query->where('adjustment_number', 'like', "%{$this->search}%")
                        ->orWhere('reason', 'like', "%{$this->search}%")
                        ->orWhereHas('product', fn ($q) => $q->where('name', 'like', "%{$this->search}%"));
                });
            })
            ->when($this->typeFilter, fn ($q) => $q->where('adjustment_type', $this->typeFilter))
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->warehouseFilter, fn ($q) => $q->where('warehouse_id', $this->warehouseFilter))
            ->latest();

        $warehouseQuery = \App\Models\Warehouse::query();
        if (!$this->isSuperAdmin()) {
            $warehouseQuery->where('company_id', auth()->user()->company_id);
        }
        $warehouses = $warehouseQuery->orderBy('name')->get();

        return [
            'adjustments' => $query->paginate(15),
            'warehouses' => $warehouses,
        ];
    }

    public function confirmSubmit(int $id): void
    {
        $this->selectedAdjustmentId = $id;
        $this->modal('confirm-submit')->show();
    }

    public function submit(): void
    {
        $adjustment = InventoryAdjustment::find($this->selectedAdjustmentId);

        if (! $adjustment || ! $adjustment->canBeSubmitted()) {
            session()->flash('error', 'El ajuste no puede ser enviado en su estado actual.');
            $this->modal('confirm-submit')->close();

            return;
        }

        if ($adjustment->submit(auth()->id())) {
            session()->flash('success', 'Ajuste enviado para aprobación exitosamente.');
        } else {
            session()->flash('error', 'Error al enviar el ajuste.');
        }

        $this->selectedAdjustmentId = null;
        $this->modal('confirm-submit')->close();
    }

    public function confirmApprove(int $id): void
    {
        $this->selectedAdjustmentId = $id;
        $this->modal('confirm-approve')->show();
    }

    public function approve(): void
    {
        $adjustment = InventoryAdjustment::find($this->selectedAdjustmentId);

        if (! $adjustment || ! $adjustment->canBeApproved()) {
            session()->flash('error', 'El ajuste no puede ser aprobado en su estado actual.');
            $this->modal('confirm-approve')->close();

            return;
        }

        if ($adjustment->approve(auth()->id())) {
            session()->flash('success', 'Ajuste aprobado exitosamente.');
        } else {
            session()->flash('error', 'Error al aprobar el ajuste.');
        }

        $this->selectedAdjustmentId = null;
        $this->modal('confirm-approve')->close();
    }

    public function confirmProcess(int $id): void
    {
        $this->selectedAdjustmentId = $id;
        $this->modal('confirm-process')->show();
    }

    public function process(): void
    {
        $adjustment = InventoryAdjustment::find($this->selectedAdjustmentId);

        if (! $adjustment || ! $adjustment->canBeProcessed()) {
            session()->flash('error', 'El ajuste no puede ser procesado en su estado actual.');
            $this->modal('confirm-process')->close();

            return;
        }

        if ($adjustment->process(auth()->id())) {
            session()->flash('success', 'Ajuste procesado exitosamente. Inventario actualizado.');
        } else {
            session()->flash('error', 'Error al procesar el ajuste. Revise los logs.');
        }

        $this->selectedAdjustmentId = null;
        $this->modal('confirm-process')->close();
    }

    public function confirmDelete(int $id): void
    {
        $this->selectedAdjustmentId = $id;
        $this->modal('confirm-delete')->show();
    }

    public function delete(): void
    {
        $adjustment = InventoryAdjustment::find($this->selectedAdjustmentId);

        if (! $adjustment || ! in_array($adjustment->status, ['borrador', 'rechazado'])) {
            session()->flash('error', 'Solo se pueden eliminar ajustes en estado borrador o rechazado.');
            $this->modal('confirm-delete')->close();

            return;
        }

        $adjustment->delete();
        session()->flash('success', 'Ajuste eliminado exitosamente.');

        $this->selectedAdjustmentId = null;
        $this->modal('confirm-delete')->close();
    }
}; ?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Ajustes de Inventario</flux:heading>
            <flux:text class="mt-1">Gestión de ajustes de inventario (daños, pérdidas, correcciones)</flux:text>
        </div>

        <flux:button variant="primary" icon="plus" href="{{ route('adjustments.create') }}" wire:navigate>
            Nuevo Ajuste
        </flux:button>
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

    <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
        <div class="md:col-span-2">
            <flux:input
                wire:model.live.debounce.300ms="search"
                placeholder="Buscar por número, motivo, producto..."
                icon="magnifying-glass"
            />
        </div>

        <flux:select wire:model.live="warehouseFilter">
            <option value="">Todas las bodegas</option>
            @foreach ($warehouses as $warehouse)
                <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
            @endforeach
        </flux:select>

        <flux:select wire:model.live="typeFilter">
            <option value="">Todos los tipos</option>
            <option value="positive">Ajuste Positivo</option>
            <option value="negative">Ajuste Negativo</option>
            <option value="damage">Producto Dañado</option>
            <option value="expiry">Producto Vencido</option>
            <option value="loss">Pérdida/Robo</option>
            <option value="correction">Corrección de Conteo</option>
            <option value="return">Devolución</option>
            <option value="other">Otro</option>
        </flux:select>

        <flux:select wire:model.live="statusFilter">
            <option value="">Todos los estados</option>
            <option value="borrador">Borrador</option>
            <option value="pendiente">Pendiente</option>
            <option value="aprobado">Aprobado</option>
            <option value="procesado">Procesado</option>
            <option value="rechazado">Rechazado</option>
            <option value="cancelado">Cancelado</option>
        </flux:select>
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column>Número</flux:table.column>
            <flux:table.column>{{ $this->isSuperAdmin() ? 'Empresa / Bodega' : 'Bodega' }}</flux:table.column>
            <flux:table.column>Producto</flux:table.column>
            <flux:table.column>Tipo</flux:table.column>
            <flux:table.column>Cantidad</flux:table.column>
            <flux:table.column>Motivo</flux:table.column>
            <flux:table.column>Estado</flux:table.column>
            <flux:table.column>Creado</flux:table.column>
            <flux:table.column>Acciones</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($adjustments as $adjustment)
                <flux:table.row wire:key="adjustment-{{ $adjustment->id }}">
                    <flux:table.cell>
                        <a href="{{ route('adjustments.show', $adjustment->slug) }}" wire:navigate class="text-blue-600 hover:text-blue-800 font-semibold">
                            {{ $adjustment->adjustment_number }}
                        </a>
                    </flux:table.cell>

                    <flux:table.cell>
                        @if($this->isSuperAdmin())
                            <div class="text-sm font-medium">{{ $adjustment->warehouse->company->name ?? '-' }}</div>
                        @endif
                        <div class="{{ $this->isSuperAdmin() ? 'text-xs text-gray-500' : '' }}">{{ $adjustment->warehouse->name }}</div>
                    </flux:table.cell>

                    <flux:table.cell>
                        <div class="font-medium">{{ $adjustment->product->name }}</div>
                        <div class="text-sm text-gray-500">{{ $adjustment->product->sku }}</div>
                    </flux:table.cell>

                    <flux:table.cell>
                        <flux:badge :color="$adjustment->isPositiveAdjustment() ? 'green' : 'red'" size="sm">
                            {{ $adjustment->adjustment_type_spanish }}
                        </flux:badge>
                    </flux:table.cell>

                    <flux:table.cell>
                        <span class="{{ $adjustment->isPositiveAdjustment() ? 'text-green-600' : 'text-red-600' }} font-semibold">
                            {{ $adjustment->isPositiveAdjustment() ? '+' : '' }}{{ number_format($adjustment->quantity, 2) }}
                        </span>
                    </flux:table.cell>

                    <flux:table.cell>
                        <div class="max-w-xs" title="{{ $adjustment->reason }}">{{ Str::limit($adjustment->reason, 30) }}</div>
                    </flux:table.cell>

                    <flux:table.cell>
                        <flux:badge
                            :color="match($adjustment->status) {
                                'borrador' => 'zinc',
                                'pendiente' => 'yellow',
                                'aprobado' => 'blue',
                                'procesado' => 'green',
                                'rechazado' => 'red',
                                'cancelado' => 'gray',
                                default => 'zinc'
                            }"
                            size="sm"
                        >
                            {{ $adjustment->status_spanish }}
                        </flux:badge>
                    </flux:table.cell>

                    <flux:table.cell>
                        <div class="text-sm">{{ $adjustment->created_at->format('d/m/Y') }}</div>
                        <div class="text-xs text-gray-500">{{ $adjustment->creator->name ?? 'Sistema' }}</div>
                    </flux:table.cell>

                    <flux:table.cell>
                        <div class="flex gap-2">
                            <flux:button size="sm" variant="ghost" icon="eye" href="{{ route('adjustments.show', $adjustment->slug) }}" wire:navigate />

                            @if ($adjustment->canBeSubmitted())
                                <flux:button
                                    size="sm"
                                    variant="ghost"
                                    icon="paper-airplane"
                                    wire:click="confirmSubmit({{ $adjustment->id }})"
                                />
                            @endif

                            @if ($adjustment->canBeApproved())
                                <flux:button
                                    size="sm"
                                    variant="ghost"
                                    icon="check"
                                    wire:click="confirmApprove({{ $adjustment->id }})"
                                />
                            @endif

                            @if ($adjustment->canBeProcessed())
                                <flux:button
                                    size="sm"
                                    variant="primary"
                                    icon="cog"
                                    wire:click="confirmProcess({{ $adjustment->id }})"
                                />
                            @endif

                            @if ($adjustment->canBeEdited())
                                <flux:button size="sm" variant="ghost" icon="pencil" href="{{ route('adjustments.edit', $adjustment->slug) }}" wire:navigate />
                            @endif

                            @if (in_array($adjustment->status, ['borrador', 'rechazado']))
                                <flux:button
                                    size="sm"
                                    variant="ghost"
                                    icon="trash"
                                    wire:click="confirmDelete({{ $adjustment->id }})"
                                />
                            @endif
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="9" class="text-center py-8 text-gray-500">
                        No se encontraron ajustes de inventario
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    <div class="mt-4">
        {{ $adjustments->links() }}
    </div>

    <!-- Modal: Confirmar Envío -->
    <flux:modal name="confirm-submit" class="min-w-[22rem]">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">¿Enviar para aprobación?</flux:heading>
                <flux:text class="mt-2">
                    <p>El ajuste será enviado para su revisión y aprobación.</p>
                    <p>No podrá editarlo mientras esté pendiente de aprobación.</p>
                </flux:text>
            </div>

            <div class="flex gap-2">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button variant="ghost">Cancelar</flux:button>
                </flux:modal.close>
                <flux:button variant="primary" wire:click="submit">Enviar</flux:button>
            </div>
        </div>
    </flux:modal>

    <!-- Modal: Confirmar Aprobación -->
    <flux:modal name="confirm-approve" class="min-w-[22rem]">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">¿Aprobar ajuste?</flux:heading>
                <flux:text class="mt-2">
                    <p>Una vez aprobado, el ajuste quedará listo para ser procesado.</p>
                </flux:text>
            </div>

            <div class="flex gap-2">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button variant="ghost">Cancelar</flux:button>
                </flux:modal.close>
                <flux:button variant="primary" wire:click="approve">Aprobar</flux:button>
            </div>
        </div>
    </flux:modal>

    <!-- Modal: Confirmar Procesamiento -->
    <flux:modal name="confirm-process" class="min-w-[22rem]">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">¿Procesar ajuste?</flux:heading>
                <flux:text class="mt-2">
                    <p>Esta acción actualizará el inventario.</p>
                    <p class="font-semibold text-red-600">Esta acción no se puede revertir.</p>
                </flux:text>
            </div>

            <div class="flex gap-2">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button variant="ghost">Cancelar</flux:button>
                </flux:modal.close>
                <flux:button variant="danger" wire:click="process">Procesar</flux:button>
            </div>
        </div>
    </flux:modal>

    <!-- Modal: Confirmar Eliminación -->
    <flux:modal name="confirm-delete" class="min-w-[22rem]">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">¿Eliminar ajuste?</flux:heading>
                <flux:text class="mt-2">
                    <p>El ajuste será eliminado permanentemente.</p>
                    <p>Esta acción no se puede deshacer.</p>
                </flux:text>
            </div>

            <div class="flex gap-2">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button variant="ghost">Cancelar</flux:button>
                </flux:modal.close>
                <flux:button variant="danger" wire:click="delete">Eliminar</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
