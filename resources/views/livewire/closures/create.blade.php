<?php

use App\Models\InventoryClosure;
use App\Models\Warehouse;
use App\Models\Company;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component
{
    public $company_id = '';

    public $warehouse_id = '';

    public $year = '';

    public $month = '';

    public $closure_date = '';

    public $notes = '';

    public $observations = '';

    public function mount(): void
    {
        $this->year = now()->year;
        $this->month = now()->month;
        $this->closure_date = now()->format('Y-m-d');

        // If not super admin, set company_id to user's company
        if (!$this->isSuperAdmin()) {
            $this->company_id = auth()->user()->company_id;
        }
    }

    public function updatedCompanyId(): void
    {
        // Reset warehouse selection when company changes
        $this->warehouse_id = '';
    }

    public function isSuperAdmin(): bool
    {
        return auth()->user()->hasRole('super-admin');
    }

    public function save(): void
    {
        $rules = [
            'warehouse_id' => 'required|exists:warehouses,id',
            'year' => 'required|integer|min:2000|max:2100',
            'month' => 'required|integer|min:1|max:12',
            'closure_date' => 'required|date',
            'notes' => 'nullable|string|max:1000',
            'observations' => 'nullable|string|max:1000',
        ];

        // Add company_id validation for super admins
        if ($this->isSuperAdmin()) {
            $rules['company_id'] = 'required|exists:companies,id';
        }

        $customAttributes = [
            'warehouse_id' => 'bodega',
            'year' => 'año',
            'month' => 'mes',
            'closure_date' => 'fecha de cierre',
            'company_id' => 'empresa',
            'notes' => 'notas',
            'observations' => 'observaciones',
        ];

        $this->validate($rules, [], $customAttributes);

        // Use selected company_id or user's company_id for non-super-admins
        $companyId = $this->isSuperAdmin() ? $this->company_id : auth()->user()->company_id;

        // Check if closure already exists for this period
        $existingClosure = InventoryClosure::where('company_id', $companyId)
            ->where('warehouse_id', $this->warehouse_id)
            ->where('year', $this->year)
            ->where('month', $this->month)
            ->first();

        if ($existingClosure) {
            session()->flash('error', 'Ya existe un cierre para este período y bodega.');

            return;
        }

        $periodStart = date('Y-m-d', strtotime("{$this->year}-{$this->month}-01"));
        $periodEnd = date('Y-m-t', strtotime("{$this->year}-{$this->month}-01"));

        $closure = InventoryClosure::create([
            'company_id' => $companyId,
            'warehouse_id' => $this->warehouse_id,
            'year' => $this->year,
            'month' => $this->month,
            'closure_date' => $this->closure_date,
            'period_start_date' => $periodStart,
            'period_end_date' => $periodEnd,
            'notes' => $this->notes,
            'observations' => $this->observations,
            'status' => 'en_proceso',
            'created_by' => auth()->id(),
        ]);

        session()->flash('success', 'Cierre creado exitosamente. Ahora puede procesarlo para calcular los saldos.');
        $this->redirect(route('closures.show', $closure->slug), navigate: true);
    }

    #[Computed]
    public function companies()
    {
        if ($this->isSuperAdmin()) {
            return Company::active()->orderBy('name')->get();
        }

        return collect([]);
    }

    #[Computed]
    public function warehouses()
    {
        if ($this->isSuperAdmin()) {
            if (!$this->company_id) {
                return collect([]);
            }
            return Warehouse::where('company_id', $this->company_id)->active()->orderBy('name')->get();
        }

        // Non-super-admin users see only their company's warehouses
        return Warehouse::where('company_id', auth()->user()->company_id)->active()->orderBy('name')->get();
    }
}; ?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Nuevo Cierre de Inventario</flux:heading>
            <flux:text class="mt-1">Crear un nuevo cierre mensual</flux:text>
        </div>

        <flux:button variant="ghost" href="{{ route('closures.index') }}" wire:navigate>
            Cancelar
        </flux:button>
    </div>

    <form wire:submit="save" class="space-y-8">
        <!-- Información Básica -->
        <flux:card>
            <flux:heading size="lg" class="mb-6">Información del Cierre</flux:heading>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @if($this->isSuperAdmin())
                    <flux:field class="md:col-span-2">
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

                <flux:field class="md:col-span-2">
                    <flux:label badge="Requerido">Bodega</flux:label>
                    <flux:select wire:model="warehouse_id" :disabled="$this->isSuperAdmin() && !$company_id" :description="$this->isSuperAdmin() && !$company_id ? 'Primero selecciona una empresa' : 'Bodega donde se realizará el cierre'">
                        <option value="">Seleccione bodega</option>
                        @foreach ($this->warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                        @endforeach
                    </flux:select>
                    <flux:error name="warehouse_id" />
                </flux:field>

                <flux:field>
                    <flux:label badge="Requerido">Año</flux:label>
                    <flux:select wire:model="year">
                        @foreach (range(now()->year, now()->year - 2) as $y)
                            <option value="{{ $y }}">{{ $y }}</option>
                        @endforeach
                    </flux:select>
                    <flux:error name="year" />
                </flux:field>

                <flux:field>
                    <flux:label badge="Requerido">Mes</flux:label>
                    <flux:select wire:model="month">
                        @foreach ([
                            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
                            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
                            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
                        ] as $num => $name)
                            <option value="{{ $num }}">{{ $name }}</option>
                        @endforeach
                    </flux:select>
                    <flux:error name="month" />
                </flux:field>

                <flux:field class="md:col-span-2">
                    <flux:label badge="Requerido">Fecha de Cierre</flux:label>
                    <flux:input type="date" wire:model="closure_date" description="La fecha en que se realiza el cierre del período" />
                    <flux:error name="closure_date" />
                </flux:field>
            </div>
        </flux:card>

        <!-- Notas y Observaciones -->
        <flux:card>
            <flux:heading size="lg" class="mb-6">Notas y Observaciones</flux:heading>

            <div class="grid grid-cols-1 gap-6">
                <flux:field>
                    <flux:label>Notas</flux:label>
                    <flux:textarea wire:model="notes" rows="3" placeholder="Notas sobre el cierre..." />
                    <flux:error name="notes" />
                </flux:field>

                <flux:field>
                    <flux:label>Observaciones</flux:label>
                    <flux:textarea wire:model="observations" rows="3" placeholder="Observaciones adicionales..." />
                    <flux:error name="observations" />
                </flux:field>
            </div>
        </flux:card>

        <!-- Flujo de Trabajo -->
        <flux:card>
            <div class="flex items-center justify-between mb-4">
                <flux:heading size="lg">Flujo de Trabajo</flux:heading>
                <flux:button variant="ghost" size="sm" icon="question-mark-circle" :href="route('help.index', ['seccion' => 'closures'])" wire:navigate>
                    Ver guía completa
                </flux:button>
            </div>

            <ul class="space-y-2 text-sm text-zinc-600 dark:text-zinc-400">
                <li class="flex items-center gap-2">
                    <flux:badge color="blue" size="sm">1</flux:badge>
                    <span><strong>Crear:</strong> Selecciona bodega y período mensual</span>
                </li>
                <li class="flex items-center gap-2">
                    <flux:badge color="amber" size="sm">2</flux:badge>
                    <span><strong>Procesar:</strong> El sistema calcula saldos automáticamente</span>
                </li>
                <li class="flex items-center gap-2">
                    <flux:badge color="cyan" size="sm">3</flux:badge>
                    <span><strong>Revisar:</strong> Valida saldos y registra conteos físicos</span>
                </li>
                <li class="flex items-center gap-2">
                    <flux:badge color="lime" size="sm">4</flux:badge>
                    <span><strong>Aprobar:</strong> Un supervisor aprueba el cierre</span>
                </li>
                <li class="flex items-center gap-2">
                    <flux:badge color="green" size="sm">5</flux:badge>
                    <span><strong>Cerrar:</strong> El período queda bloqueado para auditoría</span>
                </li>
            </ul>

            <flux:callout variant="warning" icon="exclamation-triangle" class="mt-4">
                Procese todos los movimientos pendientes antes de crear el cierre.
            </flux:callout>
        </flux:card>

        <!-- Botones de Acción -->
        <div class="flex items-center justify-between">
            <flux:button variant="ghost" href="{{ route('closures.index') }}" wire:navigate type="button">
                Cancelar
            </flux:button>
            <flux:button type="submit" variant="primary" icon="check">
                Crear Cierre
            </flux:button>
        </div>
    </form>
</div>
