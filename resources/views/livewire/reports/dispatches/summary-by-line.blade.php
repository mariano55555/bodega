<?php

use App\Models\Company;
use App\Models\DispatchDetail;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component
{
    #[Url(as: 'empresa')]
    public $company_id = '';

    #[Url(as: 'inicio')]
    public $start_date = '';

    #[Url(as: 'fin')]
    public $end_date = '';

    public function mount(): void
    {
        $this->start_date = now()->startOfMonth()->format('Y-m-d');
        $this->end_date = now()->endOfMonth()->format('Y-m-d');

        if (! auth()->user()->isSuperAdmin()) {
            $this->company_id = (string) auth()->user()->company_id;
        }
    }

    #[Computed]
    public function isSuperAdmin(): bool
    {
        return auth()->user()->isSuperAdmin();
    }

    #[Computed]
    public function effectiveCompanyId()
    {
        return $this->isSuperAdmin ? $this->company_id : auth()->user()->company_id;
    }

    #[Computed]
    public function companies()
    {
        return $this->isSuperAdmin
            ? Company::where('is_active', true)->orderBy('name')->get()
            : collect();
    }

    #[Computed]
    public function reportData()
    {
        if (! $this->effectiveCompanyId) {
            return collect();
        }

        $query = DispatchDetail::query()
            ->select([
                'product_categories.id as category_id',
                'product_categories.name as category_name',
                'product_categories.legacy_code as category_code',
                'product_categories.parent_id',
                'parent_categories.id as parent_id',
                'parent_categories.name as parent_name',
                'parent_categories.legacy_code as parent_code',
                DB::raw('SUM(dispatch_details.total) as total_amount'),
            ])
            ->join('dispatches', 'dispatch_details.dispatch_id', '=', 'dispatches.id')
            ->join('products', 'dispatch_details.product_id', '=', 'products.id')
            ->leftJoin('product_categories', 'products.category_id', '=', 'product_categories.id')
            ->leftJoin('product_categories as parent_categories', 'product_categories.parent_id', '=', 'parent_categories.id')
            ->where('dispatches.company_id', $this->effectiveCompanyId)
            ->whereIn('dispatches.status', ['despachado', 'entregado'])
            ->where('dispatches.document_date', '>=', $this->start_date)
            ->where('dispatches.document_date', '<=', $this->end_date)
            ->groupBy([
                'product_categories.id',
                'product_categories.name',
                'product_categories.legacy_code',
                'product_categories.parent_id',
                'parent_categories.id',
                'parent_categories.name',
                'parent_categories.legacy_code',
            ])
            ->orderBy('parent_categories.legacy_code')
            ->orderBy('product_categories.legacy_code')
            ->get();

        return $query;
    }

    #[Computed]
    public function groupedByParent()
    {
        return $this->reportData->groupBy('parent_name')->map(function ($items, $parentName) {
            $firstItem = $items->first();
            return (object) [
                'parent_name' => $parentName ?: 'Sin Categoría',
                'parent_code' => $firstItem->parent_code ?? '',
                'lines' => $items,
                'subtotal' => $items->sum('total_amount'),
            ];
        });
    }

    #[Computed]
    public function totals(): array
    {
        $data = $this->reportData;

        return [
            'total_categories' => $data->pluck('parent_id')->unique()->count(),
            'total_lines' => $data->count(),
            'total_amount' => $data->sum('total_amount'),
        ];
    }

    public function exportPdf()
    {
        $params = [
            'empresa' => $this->effectiveCompanyId,
            'inicio' => $this->start_date,
            'fin' => $this->end_date,
        ];

        return $this->redirect(route('reports.dispatches.summary-by-line.pdf', $params));
    }

    public function exportExcel()
    {
        $params = [
            'empresa' => $this->effectiveCompanyId,
            'inicio' => $this->start_date,
            'fin' => $this->end_date,
        ];

        return $this->redirect(route('reports.dispatches.summary-by-line.excel', $params));
    }
}; ?>

<div class="space-y-6">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Resumen Salidas por Línea Presupuestaria</flux:heading>
            <flux:text class="mt-1">Consolidado de salidas agrupadas por categoría y línea presupuestaria</flux:text>
        </div>

        <div class="flex items-center gap-2">
            <flux:button variant="ghost" icon="arrow-left" href="{{ route('reports.dispatches.hub') }}" wire:navigate>
                Volver
            </flux:button>
        </div>
    </div>

    {{-- Filters --}}
    <flux:card>
        <flux:heading size="lg" class="mb-4">Filtros</flux:heading>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            @if ($this->isSuperAdmin)
                <flux:field class="md:col-span-4">
                    <flux:label>Empresa</flux:label>
                    <flux:select wire:model.live="company_id">
                        <flux:select.option value="">-- Seleccione una empresa --</flux:select.option>
                        @foreach ($this->companies as $company)
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

            <div class="md:col-span-2 flex items-end gap-2">
                <flux:button wire:click="exportPdf" variant="filled" icon="document-arrow-down" size="sm" class="bg-red-600 hover:bg-red-700">
                    PDF
                </flux:button>
                <flux:button wire:click="exportExcel" variant="filled" icon="document-arrow-down" size="sm" class="bg-green-600 hover:bg-green-700">
                    Excel
                </flux:button>
            </div>
        </div>
    </flux:card>

    @if (! $this->effectiveCompanyId)
        <flux:card>
            <div class="py-12 text-center">
                <flux:icon.building-office class="mx-auto size-12 text-zinc-400 dark:text-zinc-600" />
                <flux:heading size="lg" class="mt-4">Seleccione una Empresa</flux:heading>
                <flux:text class="mt-2">
                    Seleccione una empresa para ver el resumen de salidas por línea
                </flux:text>
            </div>
        </flux:card>
    @else
        {{-- Summary Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <flux:card class="text-center">
                <flux:text class="text-sm text-gray-500 dark:text-gray-400">Total Categorías</flux:text>
                <flux:heading size="xl">{{ number_format($this->totals['total_categories']) }}</flux:heading>
            </flux:card>

            <flux:card class="text-center">
                <flux:text class="text-sm text-gray-500 dark:text-gray-400">Total Líneas Presupuestarias</flux:text>
                <flux:heading size="xl">{{ number_format($this->totals['total_lines']) }}</flux:heading>
            </flux:card>

            <flux:card class="text-center bg-amber-50 dark:bg-amber-900/20">
                <flux:text class="text-sm text-gray-500 dark:text-gray-400">Total Mensual</flux:text>
                <flux:heading size="xl" class="text-amber-600 dark:text-amber-400">
                    ${{ number_format($this->totals['total_amount'], 2) }}
                </flux:heading>
            </flux:card>
        </div>

        {{-- Grouped Data by Category --}}
        @forelse ($this->groupedByParent as $parentName => $group)
            <flux:card>
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-amber-100 dark:bg-amber-900 rounded-lg">
                            <flux:icon name="folder" class="w-5 h-5 text-amber-600 dark:text-amber-400" />
                        </div>
                        <div>
                            <flux:heading size="lg">Categoría: {{ $group->parent_name }} - {{ $group->parent_code }}</flux:heading>
                        </div>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <flux:table>
                        <flux:table.columns>
                            <flux:table.column>Línea Presupuestaria</flux:table.column>
                            <flux:table.column>Código de Línea</flux:table.column>
                            <flux:table.column class="text-right">Total Mensual</flux:table.column>
                        </flux:table.columns>

                        <flux:table.rows>
                            @foreach ($group->lines as $line)
                                <flux:table.row :key="$line->category_id">
                                    <flux:table.cell>
                                        <div class="font-medium">{{ $line->category_name ?? 'Sin Línea' }}</div>
                                    </flux:table.cell>

                                    <flux:table.cell>
                                        <flux:badge>{{ $line->category_code ?? '-' }}</flux:badge>
                                    </flux:table.cell>

                                    <flux:table.cell class="text-right font-medium">
                                        ${{ number_format($line->total_amount, 2) }}
                                    </flux:table.cell>
                                </flux:table.row>
                            @endforeach
                        </flux:table.rows>
                    </flux:table>
                </div>

                {{-- Subtotal for this category --}}
                <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <flux:text class="font-medium">Subtotal {{ $group->parent_name }}</flux:text>
                        <flux:heading size="lg">${{ number_format($group->subtotal, 2) }}</flux:heading>
                    </div>
                </div>
            </flux:card>
        @empty
            <flux:card>
                <div class="py-12 text-center">
                    <flux:icon.document-magnifying-glass class="mx-auto size-12 text-zinc-400 dark:text-zinc-600" />
                    <flux:heading size="lg" class="mt-4">Sin Resultados</flux:heading>
                    <flux:text class="mt-2">
                        No se encontraron salidas en el período seleccionado
                    </flux:text>
                </div>
            </flux:card>
        @endforelse

        {{-- Grand Total --}}
        @if ($this->groupedByParent->count() > 0)
            <flux:card class="bg-gradient-to-r from-amber-50 to-orange-50 dark:from-amber-900/30 dark:to-orange-900/30 border-amber-200 dark:border-amber-800">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="p-3 bg-amber-100 dark:bg-amber-900 rounded-xl">
                            <flux:icon name="calculator" class="w-6 h-6 text-amber-600 dark:text-amber-400" />
                        </div>
                        <flux:heading size="lg">Total Mensual</flux:heading>
                    </div>
                    <flux:heading size="xl" class="text-amber-600 dark:text-amber-400">
                        ${{ number_format($this->totals['total_amount'], 2) }}
                    </flux:heading>
                </div>
            </flux:card>
        @endif
    @endif
</div>
