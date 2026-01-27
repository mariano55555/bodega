<?php

use App\Models\Area;
use App\Models\InternalProduction;
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

    public string $warehouseFilter = '';

    public string $areaFilter = '';

    public string $companyFilter = '';

    public function mount(): void
    {
        if (! $this->isSuperAdmin()) {
            $this->companyFilter = (string) auth()->user()->company_id;
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

    public function updatedWarehouseFilter(): void
    {
        $this->resetPage();
    }

    public function updatedAreaFilter(): void
    {
        $this->resetPage();
    }

    public function updatedCompanyFilter(): void
    {
        $this->warehouseFilter = '';
        $this->areaFilter = '';
        unset($this->filterWarehouses);
        unset($this->filterAreas);
        $this->resetPage();
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
    public function filterWarehouses()
    {
        if ($this->isSuperAdmin()) {
            if ($this->companyFilter) {
                return Warehouse::where('company_id', $this->companyFilter)
                    ->where('is_active', true)
                    ->orderBy('name')
                    ->get(['id', 'name', 'company_id']);
            }

            return Warehouse::with('company:id,name')
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'company_id']);
        }

        return Warehouse::where('company_id', auth()->user()->company_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    #[Computed]
    public function filterAreas()
    {
        if ($this->isSuperAdmin()) {
            if ($this->companyFilter) {
                return Area::where('company_id', $this->companyFilter)
                    ->where('is_active', true)
                    ->orderBy('name')
                    ->get(['id', 'name', 'company_id']);
            }

            return Area::with('company:id,name')
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'company_id']);
        }

        return Area::where('company_id', auth()->user()->company_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function with(): array
    {
        $query = InternalProduction::query()
            ->with(['area', 'warehouse', 'warehouse.company', 'employee'])
            ->when(! $this->isSuperAdmin(), fn ($q) => $q->where('company_id', auth()->user()->company_id))
            ->when($this->isSuperAdmin() && $this->companyFilter, fn ($q) => $q->where('company_id', $this->companyFilter))
            ->when($this->search, function ($q) {
                $q->where(function ($query) {
                    $query->where('production_number', 'like', "%{$this->search}%")
                        ->orWhere('physical_document_number', 'like', "%{$this->search}%")
                        ->orWhereHas('employee', fn ($q) => $q->where('name', 'like', "%{$this->search}%"));
                });
            })
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->warehouseFilter, fn ($q) => $q->where('warehouse_id', (int) $this->warehouseFilter))
            ->when($this->areaFilter, fn ($q) => $q->where('area_id', (int) $this->areaFilter))
            ->latest('created_at');

        return [
            'productions' => $query->paginate(15),
        ];
    }

    public function delete(int $productionId): void
    {
        $production = InternalProduction::find($productionId);

        if (! $production) {
            session()->flash('error', 'Producción no encontrada.');

            return;
        }

        if (in_array($production->status, ['completado'])) {
            session()->flash('error', 'No se puede eliminar una producción completada.');

            return;
        }

        $production->delete();
        session()->flash('success', 'Producción eliminada exitosamente.');
    }
}; ?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Producción Interna</flux:heading>
            <flux:text class="mt-1">Gestión de producciones internas</flux:text>
        </div>

        <flux:button variant="primary" icon="plus" href="{{ route('internal-productions.create') }}" wire:navigate>
            Nueva Producción
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

    <div class="grid grid-cols-1 {{ $this->isSuperAdmin() ? 'md:grid-cols-5' : 'md:grid-cols-4' }} gap-4">
        <div class="{{ $this->isSuperAdmin() ? 'md:col-span-1' : 'md:col-span-1' }}">
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

        <flux:select wire:model.live="areaFilter" placeholder="Todas las áreas">
            <option value="">Todas las áreas</option>
            @foreach ($this->filterAreas as $area)
                <option value="{{ $area->id }}">
                    {{ $area->name }}
                    @if ($this->isSuperAdmin() && ! $companyFilter && $area->company)
                        ({{ $area->company->name }})
                    @endif
                </option>
            @endforeach
        </flux:select>

        <flux:select wire:model.live="statusFilter" placeholder="Todos los estados">
            <option value="">Todos los estados</option>
            <option value="borrador">Borrador</option>
            <option value="pendiente">Pendiente</option>
            <option value="aprobado">Aprobado</option>
            <option value="completado">Completado</option>
            <option value="cancelado">Anulado</option>
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
                <flux:table.column>Unidad Origen</flux:table.column>
                <flux:table.column>Bodega Destino</flux:table.column>
                <flux:table.column>Entrega</flux:table.column>
                <flux:table.column>Total</flux:table.column>
                <flux:table.column>Estado</flux:table.column>
                <flux:table.column>Acciones</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($productions as $production)
                    <flux:table.row :key="$production->id">
                        <flux:table.cell>
                            <div>
                                <div class="font-medium text-gray-900 dark:text-gray-100">
                                    {{ $production->production_number }}
                                </div>
                                @if ($production->physical_document_number)
                                    <div class="text-sm text-gray-500 dark:text-gray-400">
                                        Doc: {{ $production->physical_document_number }}
                                    </div>
                                @endif
                            </div>
                        </flux:table.cell>

                        <flux:table.cell>
                            {{ $production->created_at->format('d/m/Y') }}
                        </flux:table.cell>

                        @if ($this->isSuperAdmin())
                            <flux:table.cell>
                                {{ $production->warehouse->company->name ?? 'N/A' }}
                            </flux:table.cell>
                        @endif

                        <flux:table.cell>
                            {{ $production->area->name ?? 'N/A' }}
                        </flux:table.cell>

                        <flux:table.cell>
                            {{ $production->warehouse->name ?? 'N/A' }}
                        </flux:table.cell>

                        <flux:table.cell>
                            {{ $production->employee->name ?? '-' }}
                        </flux:table.cell>

                        <flux:table.cell>
                            ${{ number_format($production->total, 5) }}
                        </flux:table.cell>

                        <flux:table.cell>
                            <flux:badge
                                size="sm"
                                :color="match($production->status) {
                                    'borrador' => 'zinc',
                                    'pendiente' => 'amber',
                                    'aprobado' => 'sky',
                                    'completado' => 'emerald',
                                    'cancelado' => 'red',
                                    default => 'zinc'
                                }"
                                :icon="match($production->status) {
                                    'borrador' => 'pencil-square',
                                    'pendiente' => 'clock',
                                    'aprobado' => 'check-circle',
                                    'completado' => 'check-badge',
                                    'cancelado' => 'x-circle',
                                    default => 'question-mark-circle'
                                }"
                            >
                                {{ $production->getStatusSpanishAttribute() }}
                            </flux:badge>
                        </flux:table.cell>

                        <flux:table.cell>
                            <div class="flex items-center gap-2">
                                <flux:button size="sm" variant="ghost" icon="eye" href="{{ route('internal-productions.show', $production) }}" wire:navigate />

                                @if ($production->canBeEdited())
                                    <flux:button size="sm" variant="ghost" icon="pencil" href="{{ route('internal-productions.edit', $production) }}" wire:navigate />
                                @endif

                                @if ($production->status === 'cancelado')
                                    <flux:button
                                        size="sm"
                                        variant="ghost"
                                        icon="trash"
                                        wire:click="delete({{ $production->id }})"
                                        wire:confirm="¿Está seguro de eliminar esta producción?"
                                    />
                                @endif
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell :colspan="$this->isSuperAdmin() ? 9 : 8" class="text-center py-12">
                            <div class="text-gray-500 dark:text-gray-400">
                                No se encontraron producciones internas
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>

    <div class="mt-4">
        {{ $productions->links() }}
    </div>
</div>
