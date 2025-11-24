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

        <!-- Proceso de Cierre - Visual Guide -->
        <flux:card class="!p-0 overflow-hidden">
            <div class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-950/30 dark:to-indigo-950/30 px-6 py-4 border-b border-zinc-200 dark:border-zinc-700">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900/50">
                        <flux:icon.document-text class="size-5 text-blue-600 dark:text-blue-400" />
                    </div>
                    <div>
                        <flux:heading size="lg">Proceso de Cierre de Inventario</flux:heading>
                        <flux:text class="text-sm">Guía paso a paso del flujo de trabajo</flux:text>
                    </div>
                </div>
            </div>

            <div class="p-6 space-y-6">
                {{-- Step 1: Crear --}}
                <div class="flex gap-4">
                    <div class="flex flex-col items-center">
                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-500 text-white font-bold text-sm shadow-sm">
                            1
                        </div>
                        <div class="w-0.5 flex-1 bg-zinc-200 dark:bg-zinc-700 mt-2"></div>
                    </div>
                    <div class="flex-1 pb-6">
                        <div class="flex items-center gap-2 mb-1">
                            <flux:badge color="blue" size="sm">Actual</flux:badge>
                            <span class="font-semibold text-zinc-900 dark:text-white">Crear Cierre</span>
                        </div>
                        <p class="text-sm text-zinc-600 dark:text-zinc-400 mb-3">
                            Selecciona la bodega y el período mensual. El sistema verificará que no exista un cierre previo para evitar duplicados.
                        </p>
                        <div class="flex flex-wrap gap-2">
                            <div class="inline-flex items-center gap-1.5 rounded-md bg-zinc-100 dark:bg-zinc-800 px-2.5 py-1 text-xs">
                                <flux:icon.building-storefront class="size-3.5 text-zinc-500" />
                                <span>Bodega</span>
                            </div>
                            <div class="inline-flex items-center gap-1.5 rounded-md bg-zinc-100 dark:bg-zinc-800 px-2.5 py-1 text-xs">
                                <flux:icon.calendar class="size-3.5 text-zinc-500" />
                                <span>Período</span>
                            </div>
                            <div class="inline-flex items-center gap-1.5 rounded-md bg-zinc-100 dark:bg-zinc-800 px-2.5 py-1 text-xs">
                                <flux:icon.document-text class="size-3.5 text-zinc-500" />
                                <span>Notas</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Step 2: Procesar --}}
                <div class="flex gap-4">
                    <div class="flex flex-col items-center">
                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-zinc-200 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-400 font-bold text-sm">
                            2
                        </div>
                        <div class="w-0.5 flex-1 bg-zinc-200 dark:bg-zinc-700 mt-2"></div>
                    </div>
                    <div class="flex-1 pb-6">
                        <div class="flex items-center gap-2 mb-1">
                            <flux:badge color="amber" size="sm">En Proceso</flux:badge>
                            <span class="font-semibold text-zinc-900 dark:text-white">Procesar Saldos</span>
                        </div>
                        <p class="text-sm text-zinc-600 dark:text-zinc-400 mb-3">
                            El sistema calculará automáticamente los saldos iniciales, entradas, salidas y saldos finales de cada producto en la bodega.
                        </p>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 p-2 text-center">
                                <flux:icon.arrow-down-tray class="size-4 mx-auto text-green-500 mb-1" />
                                <span class="text-xs text-zinc-600 dark:text-zinc-400">Entradas</span>
                            </div>
                            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 p-2 text-center">
                                <flux:icon.arrow-up-tray class="size-4 mx-auto text-red-500 mb-1" />
                                <span class="text-xs text-zinc-600 dark:text-zinc-400">Salidas</span>
                            </div>
                            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 p-2 text-center">
                                <flux:icon.scale class="size-4 mx-auto text-blue-500 mb-1" />
                                <span class="text-xs text-zinc-600 dark:text-zinc-400">Ajustes</span>
                            </div>
                            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 p-2 text-center">
                                <flux:icon.calculator class="size-4 mx-auto text-purple-500 mb-1" />
                                <span class="text-xs text-zinc-600 dark:text-zinc-400">Saldo Final</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Step 3: Revisar --}}
                <div class="flex gap-4">
                    <div class="flex flex-col items-center">
                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-zinc-200 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-400 font-bold text-sm">
                            3
                        </div>
                        <div class="w-0.5 flex-1 bg-zinc-200 dark:bg-zinc-700 mt-2"></div>
                    </div>
                    <div class="flex-1 pb-6">
                        <div class="flex items-center gap-2 mb-1">
                            <flux:badge color="cyan" size="sm">Revisión</flux:badge>
                            <span class="font-semibold text-zinc-900 dark:text-white">Revisar y Validar</span>
                        </div>
                        <p class="text-sm text-zinc-600 dark:text-zinc-400 mb-3">
                            Revisa los saldos calculados. Puedes registrar conteos físicos para detectar diferencias y realizar ajustes antes de cerrar.
                        </p>
                        <div class="flex flex-wrap gap-2">
                            <div class="inline-flex items-center gap-1.5 rounded-md bg-cyan-50 dark:bg-cyan-900/20 text-cyan-700 dark:text-cyan-300 px-2.5 py-1 text-xs">
                                <flux:icon.clipboard-document-check class="size-3.5" />
                                <span>Conteo Físico</span>
                            </div>
                            <div class="inline-flex items-center gap-1.5 rounded-md bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-300 px-2.5 py-1 text-xs">
                                <flux:icon.exclamation-triangle class="size-3.5" />
                                <span>Detectar Diferencias</span>
                            </div>
                            <div class="inline-flex items-center gap-1.5 rounded-md bg-purple-50 dark:bg-purple-900/20 text-purple-700 dark:text-purple-300 px-2.5 py-1 text-xs">
                                <flux:icon.adjustments-horizontal class="size-3.5" />
                                <span>Ajustes</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Step 4: Aprobar --}}
                <div class="flex gap-4">
                    <div class="flex flex-col items-center">
                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-zinc-200 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-400 font-bold text-sm">
                            4
                        </div>
                        <div class="w-0.5 flex-1 bg-zinc-200 dark:bg-zinc-700 mt-2"></div>
                    </div>
                    <div class="flex-1 pb-6">
                        <div class="flex items-center gap-2 mb-1">
                            <flux:badge color="lime" size="sm">Aprobación</flux:badge>
                            <span class="font-semibold text-zinc-900 dark:text-white">Aprobar Cierre</span>
                        </div>
                        <p class="text-sm text-zinc-600 dark:text-zinc-400">
                            Una vez validados los saldos, un supervisor puede aprobar el cierre. Este paso es requerido antes del cierre final.
                        </p>
                    </div>
                </div>

                {{-- Step 5: Cerrar --}}
                <div class="flex gap-4">
                    <div class="flex flex-col items-center">
                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-zinc-200 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-400 font-bold text-sm">
                            5
                        </div>
                    </div>
                    <div class="flex-1">
                        <div class="flex items-center gap-2 mb-1">
                            <flux:badge color="green" size="sm">Cerrado</flux:badge>
                            <span class="font-semibold text-zinc-900 dark:text-white">Cerrar Período</span>
                        </div>
                        <p class="text-sm text-zinc-600 dark:text-zinc-400 mb-3">
                            El cierre queda registrado. Los saldos finales se convierten en saldos iniciales del siguiente período.
                        </p>
                        <div class="rounded-lg bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 p-3">
                            <div class="flex items-start gap-2">
                                <flux:icon.check-circle class="size-5 text-green-600 dark:text-green-400 mt-0.5 shrink-0" />
                                <div class="text-sm text-green-700 dark:text-green-300">
                                    <span class="font-medium">Una vez cerrado:</span> Los movimientos del período quedan bloqueados y el historial se preserva para auditorías.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Footer with important notes --}}
            <div class="bg-amber-50 dark:bg-amber-950/30 border-t border-amber-200 dark:border-amber-900 px-6 py-4">
                <div class="flex gap-3">
                    <flux:icon.exclamation-triangle class="size-5 text-amber-600 dark:text-amber-400 shrink-0 mt-0.5" />
                    <div class="text-sm">
                        <span class="font-medium text-amber-800 dark:text-amber-200">Importante:</span>
                        <span class="text-amber-700 dark:text-amber-300">
                            Se recomienda procesar todos los movimientos pendientes (entradas, salidas, ajustes) antes de crear el cierre del período.
                        </span>
                    </div>
                </div>
            </div>
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
