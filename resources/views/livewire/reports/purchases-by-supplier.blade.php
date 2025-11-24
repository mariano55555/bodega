<?php

use App\Models\Company;
use App\Models\Purchase;
use App\Models\Supplier;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component
{
    #[Url(as: 'empresa')]
    public $company_id = '';

    public $start_date = '';

    public $end_date = '';

    public $supplier_id = '';

    public $acquisition_type = '';

    public function mount(): void
    {
        // Default to current month
        $this->start_date = now()->startOfMonth()->format('Y-m-d');
        $this->end_date = now()->endOfMonth()->format('Y-m-d');

        // Set company_id for non-super-admin users
        if (! auth()->user()->isSuperAdmin()) {
            $this->company_id = (string) auth()->user()->company_id;
        }
    }

    public function updatedCompanyId(): void
    {
        $this->supplier_id = '';
    }

    public function with(): array
    {
        $isSuperAdmin = auth()->user()->isSuperAdmin();
        $effectiveCompanyId = $isSuperAdmin ? $this->company_id : auth()->user()->company_id;

        // Get companies for super admin dropdown
        $companies = $isSuperAdmin
            ? Company::where('is_active', true)->orderBy('name')->get()
            : collect();

        $query = Purchase::query()
            ->select([
                'supplier_id',
                DB::raw('COUNT(*) as invoice_count'),
                DB::raw('SUM(total) as total_amount'),
                DB::raw('SUM(subtotal) as subtotal_amount'),
                DB::raw('SUM(tax_amount) as tax_amount'),
                DB::raw('SUM(discount_amount) as discount_amount'),
            ])
            ->when($effectiveCompanyId, fn ($q) => $q->where('company_id', $effectiveCompanyId))
            ->whereIn('status', ['aprobado', 'recibido'])
            ->with(['supplier:id,name,tax_id'])
            ->groupBy('supplier_id');

        // Apply filters
        if ($this->start_date) {
            $query->where('document_date', '>=', $this->start_date);
        }

        if ($this->end_date) {
            $query->where('document_date', '<=', $this->end_date);
        }

        if ($this->supplier_id) {
            $query->where('supplier_id', $this->supplier_id);
        }

        if ($this->acquisition_type) {
            $query->where('acquisition_type', $this->acquisition_type);
        }

        $supplierData = $query->orderByDesc('total_amount')->get();

        // Calculate totals
        $totals = [
            'total_invoices' => $supplierData->sum('invoice_count'),
            'total_amount' => $supplierData->sum('total_amount'),
            'total_suppliers' => $supplierData->count(),
        ];

        // Get top 10 for chart
        $topSuppliers = $supplierData->take(10);

        // Get suppliers based on effective company
        $suppliers = $effectiveCompanyId
            ? Supplier::where('company_id', $effectiveCompanyId)
                ->where('is_active', true)
                ->orderBy('name')
                ->get()
            : collect();

        return [
            'supplierData' => $supplierData,
            'topSuppliers' => $topSuppliers,
            'totals' => $totals,
            'suppliers' => $suppliers,
            'companies' => $companies,
            'isSuperAdmin' => $isSuperAdmin,
        ];
    }

    public function exportPdf(): void
    {
        session()->flash('info', 'Exportación a PDF - próximamente disponible');
    }

    public function exportExcel(): void
    {
        session()->flash('info', 'Exportación a Excel - próximamente disponible');
    }
}; ?>

<div class="space-y-6">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Reporte de Compras por Proveedor</flux:heading>
            <flux:text class="mt-1">Análisis consolidado de compras realizadas a cada proveedor</flux:text>
        </div>

        <div class="flex items-center gap-2">
            <flux:button variant="ghost" icon="arrow-left" href="{{ route('reports.administrative') }}" wire:navigate>
                Volver
            </flux:button>
        </div>
    </div>

    @if (session('success'))
        <flux:callout variant="success" icon="check-circle">
            {{ session('success') }}
        </flux:callout>
    @endif

    @if (session('info'))
        <flux:callout variant="info" icon="information-circle">
            {{ session('info') }}
        </flux:callout>
    @endif

    {{-- Filters --}}
    <flux:card>
        <flux:heading size="lg" class="mb-4">Filtros</flux:heading>

        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
            @if ($isSuperAdmin)
                <flux:field class="md:col-span-5">
                    <flux:label>Empresa</flux:label>
                    <flux:select wire:model.live="company_id">
                        <flux:select.option value="">-- Seleccione una empresa --</flux:select.option>
                        @foreach ($companies as $company)
                            <flux:select.option value="{{ $company->id }}">{{ $company->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </flux:field>
            @endif

            <flux:field>
                <flux:label>Fecha Inicio</flux:label>
                <flux:input type="date" wire:model.live="start_date" />
            </flux:field>

            <flux:field>
                <flux:label>Fecha Fin</flux:label>
                <flux:input type="date" wire:model.live="end_date" />
            </flux:field>

            <flux:field>
                <flux:label>Proveedor</flux:label>
                <flux:select wire:model.live="supplier_id">
                    <flux:select.option value="">-- Todos los proveedores --</flux:select.option>
                    @foreach ($suppliers as $supplier)
                        <flux:select.option value="{{ $supplier->id }}">{{ $supplier->name }}</flux:select.option>
                    @endforeach
                </flux:select>
            </flux:field>

            <flux:field>
                <flux:label>Tipo de Adquisición</flux:label>
                <flux:select wire:model.live="acquisition_type">
                    <flux:select.option value="">-- Todos los tipos --</flux:select.option>
                    <flux:select.option value="normal">Compra Normal</flux:select.option>
                    <flux:select.option value="convenio">Convenio</flux:select.option>
                    <flux:select.option value="proyecto">Proyecto</flux:select.option>
                    <flux:select.option value="otro">Otro</flux:select.option>
                </flux:select>
            </flux:field>

            <div class="flex items-end gap-2">
                <flux:button wire:click="exportPdf" variant="ghost" icon="document-arrow-down" size="sm">
                    PDF
                </flux:button>
                <flux:button wire:click="exportExcel" variant="ghost" icon="document-arrow-down" size="sm">
                    Excel
                </flux:button>
            </div>
        </div>
    </flux:card>

    {{-- Summary Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <flux:card>
            <div class="flex items-center gap-4">
                <div class="p-3 bg-blue-100 dark:bg-blue-900 rounded-lg">
                    <flux:icon name="building-storefront" class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                </div>
                <div>
                    <flux:text class="text-sm text-gray-500 dark:text-gray-400">Total Proveedores</flux:text>
                    <flux:heading size="lg">{{ number_format($totals['total_suppliers']) }}</flux:heading>
                </div>
            </div>
        </flux:card>

        <flux:card>
            <div class="flex items-center gap-4">
                <div class="p-3 bg-green-100 dark:bg-green-900 rounded-lg">
                    <flux:icon name="document-text" class="w-6 h-6 text-green-600 dark:text-green-400" />
                </div>
                <div>
                    <flux:text class="text-sm text-gray-500 dark:text-gray-400">Total Facturas</flux:text>
                    <flux:heading size="lg">{{ number_format($totals['total_invoices']) }}</flux:heading>
                </div>
            </div>
        </flux:card>

        <flux:card>
            <div class="flex items-center gap-4">
                <div class="p-3 bg-purple-100 dark:bg-purple-900 rounded-lg">
                    <flux:icon name="currency-dollar" class="w-6 h-6 text-purple-600 dark:text-purple-400" />
                </div>
                <div>
                    <flux:text class="text-sm text-gray-500 dark:text-gray-400">Monto Total</flux:text>
                    <flux:heading size="lg">${{ number_format($totals['total_amount'], 2) }}</flux:heading>
                </div>
            </div>
        </flux:card>
    </div>

    {{-- Top 10 Suppliers Chart --}}
    @if ($topSuppliers->count() > 0)
        <flux:card>
            <flux:heading size="lg" class="mb-4">Top 10 Proveedores por Monto</flux:heading>

            <div class="space-y-3">
                @foreach ($topSuppliers as $index => $data)
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <flux:text class="text-sm font-medium">
                                {{ $index + 1 }}. {{ $data->supplier->name }}
                            </flux:text>
                            <flux:text class="text-sm font-semibold">
                                ${{ number_format($data->total_amount, 2) }}
                            </flux:text>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                            <div
                                class="bg-blue-600 dark:bg-blue-500 h-2 rounded-full"
                                style="width: {{ ($data->total_amount / $topSuppliers->max('total_amount')) * 100 }}%"
                            ></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </flux:card>
    @endif

    {{-- Detailed Table --}}
    <flux:card>
        <flux:heading size="lg" class="mb-4">Detalle por Proveedor</flux:heading>

        <div class="overflow-x-auto">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>#</flux:table.column>
                    <flux:table.column>Proveedor</flux:table.column>
                    <flux:table.column>Documento</flux:table.column>
                    <flux:table.column>Facturas</flux:table.column>
                    <flux:table.column>Subtotal</flux:table.column>
                    <flux:table.column>IVA</flux:table.column>
                    <flux:table.column>Descuento</flux:table.column>
                    <flux:table.column>Total</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse ($supplierData as $index => $data)
                        <flux:table.row :key="$data->supplier_id">
                            <flux:table.cell>{{ $index + 1 }}</flux:table.cell>

                            <flux:table.cell>
                                <div class="font-medium">{{ $data->supplier->name }}</div>
                            </flux:table.cell>

                            <flux:table.cell>
                                <flux:text class="text-sm text-gray-500">{{ $data->supplier->tax_id ?? '-' }}</flux:text>
                            </flux:table.cell>

                            <flux:table.cell>
                                <flux:badge variant="info">{{ $data->invoice_count }}</flux:badge>
                            </flux:table.cell>

                            <flux:table.cell>
                                ${{ number_format($data->subtotal_amount, 2) }}
                            </flux:table.cell>

                            <flux:table.cell>
                                ${{ number_format($data->tax_amount, 2) }}
                            </flux:table.cell>

                            <flux:table.cell>
                                ${{ number_format($data->discount_amount, 2) }}
                            </flux:table.cell>

                            <flux:table.cell>
                                <div class="font-semibold">${{ number_format($data->total_amount, 2) }}</div>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="8" class="text-center text-gray-500">
                                No se encontraron compras en el período seleccionado
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>
    </flux:card>
</div>
