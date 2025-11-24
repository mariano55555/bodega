<?php

use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\Computed;
use App\Imports\ProductsImport;
use App\Imports\InventoriesImport;
use App\Exports\ProductsTemplateExport;
use Maatwebsite\Excel\Facades\Excel;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

new class extends Component {
    use WithFileUploads;

    public string $importType = 'products';
    public ?TemporaryUploadedFile $file = null;
    public bool $importing = false;
    public bool $previewing = false;
    public array $importResults = [];
    public array $previewResults = [];
    public bool $showResults = false;
    public bool $showPreview = false;

    public function updatedFile(): void
    {
        $this->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ], [
            'file.required' => 'Debe seleccionar un archivo',
            'file.mimes' => 'El archivo debe ser Excel (.xlsx, .xls) o CSV',
            'file.max' => 'El archivo no debe exceder 10MB',
        ]);

        // Reset preview when file changes
        $this->showPreview = false;
        $this->previewResults = [];
    }

    public function preview(): void
    {
        $this->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
            'importType' => 'required|in:products,inventories,adjustments',
        ]);

        $this->previewing = true;
        $this->showPreview = false;
        $this->showResults = false;

        try {
            $user = auth()->user();
            $companyId = $user->company_id;
            $userId = $user->id;

            $import = match ($this->importType) {
                'products' => new ProductsImport($companyId, $userId, true), // preview mode
                'inventories' => new InventoriesImport($companyId, $userId),
                default => throw new \Exception('Tipo de importación no válido'),
            };

            Excel::import($import, $this->file->getRealPath());

            if ($this->importType === 'products') {
                $this->previewResults = $import->getPreviewSummary();
            } else {
                // For other imports, show basic preview
                $this->previewResults = [
                    'total' => $import->getSuccessCount() + $import->getSkippedCount(),
                    'valid' => $import->getSuccessCount(),
                    'warnings' => 0,
                    'errors' => $import->getSkippedCount(),
                    'can_import' => true,
                    'units_to_create' => [],
                    'categories_to_create' => [],
                    'rows' => [],
                ];
            }

            $this->showPreview = true;
        } catch (\Exception $e) {
            $this->addError('file', 'Error al validar: ' . $e->getMessage());
        } finally {
            $this->previewing = false;
        }
    }

    public function confirmImport(): void
    {
        $this->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
            'importType' => 'required|in:products,inventories,adjustments',
        ]);

        $this->importing = true;
        $this->showPreview = false;
        $this->showResults = false;

        try {
            $user = auth()->user();
            $companyId = $user->company_id;
            $userId = $user->id;

            $import = match ($this->importType) {
                'products' => new ProductsImport($companyId, $userId, false), // import mode
                'inventories' => new InventoriesImport($companyId, $userId),
                default => throw new \Exception('Tipo de importación no válido'),
            };

            Excel::import($import, $this->file->getRealPath());

            $this->importResults = $import->getSummary();
            $this->showResults = true;
            $this->file = null;
            $this->previewResults = [];

            if ($this->importResults['success'] > 0) {
                $this->dispatch('import-completed', [
                    'success' => $this->importResults['success'],
                    'skipped' => $this->importResults['skipped'],
                ]);
            }
        } catch (\Exception $e) {
            $this->addError('file', 'Error al importar: ' . $e->getMessage());
        } finally {
            $this->importing = false;
        }
    }

    public function cancelPreview(): void
    {
        $this->showPreview = false;
        $this->previewResults = [];
    }

    public function downloadTemplate(string $type): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $filename = match ($type) {
            'products' => 'plantilla_productos.xlsx',
            'inventories' => 'plantilla_inventarios.xlsx',
            'adjustments' => 'plantilla_ajustes.xlsx',
            default => 'plantilla.xlsx',
        };

        return match ($type) {
            'products' => Excel::download(new ProductsTemplateExport, $filename),
            default => Excel::download(new ProductsTemplateExport, $filename),
        };
    }

    public function clearResults(): void
    {
        $this->showResults = false;
        $this->importResults = [];
    }
}; ?>

<div class="space-y-6">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl" class="text-zinc-900 dark:text-zinc-100">
                Importación de Datos
            </flux:heading>
            <flux:text class="text-zinc-600 dark:text-zinc-400">
                Importa productos, inventarios y ajustes desde archivos Excel
            </flux:text>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        {{-- Import Form --}}
        <flux:card class="lg:col-span-2">
            <div class="border-b border-zinc-200 p-4 dark:border-zinc-700">
                <flux:heading size="base" class="text-zinc-900 dark:text-zinc-100">
                    Cargar Archivo
                </flux:heading>
            </div>

            <div class="space-y-6 p-6">
                {{-- Import Type Selector --}}
                <flux:field>
                    <flux:label>Tipo de Importación</flux:label>
                    <flux:select wire:model.live="importType" variant="listbox">
                        <flux:select.option value="products">Productos</flux:select.option>
                        <flux:select.option value="inventories">Inventarios Iniciales</flux:select.option>
                        <flux:select.option value="adjustments">Ajustes de Inventario</flux:select.option>
                    </flux:select>
                </flux:field>

                {{-- File Upload --}}
                <flux:field>
                    <flux:label>Archivo Excel</flux:label>
                    <flux:input
                        type="file"
                        wire:model="file"
                        accept=".xlsx,.xls,.csv"
                    />
                    @error('file')
                        <flux:text size="sm" class="text-red-600 dark:text-red-400">{{ $message }}</flux:text>
                    @enderror
                    <flux:text size="sm" class="text-zinc-600 dark:text-zinc-400">
                        Formatos aceptados: .xlsx, .xls, .csv (máx. 10MB)
                    </flux:text>
                </flux:field>

                {{-- Import Instructions --}}
                <flux:callout variant="info">
                    <div class="space-y-2">
                        <flux:heading size="sm">Instrucciones</flux:heading>
                        @if($importType === 'products')
                            <ul class="list-inside list-disc space-y-1 text-sm">
                                <li>Descarga la plantilla de productos</li>
                                <li>Llena los datos requeridos (marcados con *)</li>
                                <li>Las categorías y unidades se crearán automáticamente si no existen</li>
                                <li>El SKU debe ser único por compañía</li>
                            </ul>
                        @elseif($importType === 'inventories')
                            <ul class="list-inside list-disc space-y-1 text-sm">
                                <li>Los productos deben existir previamente (usa el SKU)</li>
                                <li>Las bodegas deben existir (usa el nombre exacto)</li>
                                <li>Se creará un movimiento inicial por cada registro</li>
                                <li>Las existencias anteriores se sobrescribirán</li>
                            </ul>
                        @else
                            <ul class="list-inside list-disc space-y-1 text-sm">
                                <li>Los productos y bodegas deben existir</li>
                                <li>Se crearán ajustes de inventario</li>
                                <li>Las cantidades pueden ser positivas o negativas</li>
                            </ul>
                        @endif
                    </div>
                </flux:callout>

                {{-- Actions --}}
                <div class="flex items-center gap-3">
                    <flux:button
                        wire:click="preview"
                        variant="primary"
                        :disabled="!$file || $previewing || $showPreview"
                        wire:loading.attr="disabled"
                        wire:target="preview"
                        icon="eye"
                    >
                        <span wire:loading.remove wire:target="preview">Validar y Previsualizar</span>
                        <span wire:loading wire:target="preview">Validando...</span>
                    </flux:button>

                    <flux:button
                        wire:click="downloadTemplate('{{ $importType }}')"
                        variant="outline"
                        icon="document-arrow-down"
                    >
                        Descargar Plantilla
                    </flux:button>
                </div>
            </div>
        </flux:card>

        {{-- Quick Links & Help --}}
        <flux:card>
            <div class="border-b border-zinc-200 p-4 dark:border-zinc-700">
                <flux:heading size="base" class="text-zinc-900 dark:text-zinc-100">
                    Accesos Rápidos
                </flux:heading>
            </div>

            <div class="space-y-3 p-4">
                <flux:button
                    variant="outline"
                    :href="route('inventory.products.index')"
                    wire:navigate
                    class="w-full justify-start"
                    icon="cube"
                >
                    Ver Productos
                </flux:button>

                <flux:button
                    variant="outline"
                    :href="route('warehouse.warehouses.index')"
                    wire:navigate
                    class="w-full justify-start"
                    icon="building-office"
                >
                    Ver Bodegas
                </flux:button>

                <flux:button
                    variant="outline"
                    :href="route('inventory.movements.index')"
                    wire:navigate
                    class="w-full justify-start"
                    icon="arrows-right-left"
                >
                    Ver Movimientos
                </flux:button>
            </div>

            <div class="border-t border-zinc-200 p-4 dark:border-zinc-700">
                <flux:callout variant="warning" size="sm">
                    <flux:heading size="sm">Importante</flux:heading>
                    <flux:text size="sm">
                        Las importaciones son permanentes. Asegúrate de revisar tus datos antes de importar.
                    </flux:text>
                </flux:callout>
            </div>
        </flux:card>
    </div>

    {{-- Preview Results --}}
    @if($showPreview && !empty($previewResults))
        <flux:card>
            <div class="border-b border-zinc-200 p-4 dark:border-zinc-700">
                <div class="flex items-center justify-between">
                    <flux:heading size="base" class="text-zinc-900 dark:text-zinc-100">
                        <flux:icon name="eye" class="inline-block h-5 w-5 mr-2" />
                        Vista Previa de Importación
                    </flux:heading>
                    <flux:button
                        wire:click="cancelPreview"
                        variant="ghost"
                        size="sm"
                        icon="x-mark"
                    >
                        Cancelar
                    </flux:button>
                </div>
            </div>

            <div class="p-6">
                {{-- Summary Cards --}}
                <div class="mb-6 grid gap-4 sm:grid-cols-4">
                    <div class="rounded-lg border-l-4 border-blue-500 bg-blue-50 p-4 dark:bg-blue-950/20">
                        <flux:text size="sm" class="font-medium text-blue-600 dark:text-blue-400">
                            Total Registros
                        </flux:text>
                        <flux:heading size="xl" class="mt-1 text-blue-900 dark:text-blue-100">
                            {{ number_format($previewResults['total'] ?? 0) }}
                        </flux:heading>
                    </div>

                    <div class="rounded-lg border-l-4 border-green-500 bg-green-50 p-4 dark:bg-green-950/20">
                        <flux:text size="sm" class="font-medium text-green-600 dark:text-green-400">
                            Válidos
                        </flux:text>
                        <flux:heading size="xl" class="mt-1 text-green-900 dark:text-green-100">
                            {{ number_format($previewResults['valid'] ?? 0) }}
                        </flux:heading>
                    </div>

                    <div class="rounded-lg border-l-4 border-yellow-500 bg-yellow-50 p-4 dark:bg-yellow-950/20">
                        <flux:text size="sm" class="font-medium text-yellow-600 dark:text-yellow-400">
                            Con Advertencias
                        </flux:text>
                        <flux:heading size="xl" class="mt-1 text-yellow-900 dark:text-yellow-100">
                            {{ number_format($previewResults['warnings'] ?? 0) }}
                        </flux:heading>
                    </div>

                    <div class="rounded-lg border-l-4 border-red-500 bg-red-50 p-4 dark:bg-red-950/20">
                        <flux:text size="sm" class="font-medium text-red-600 dark:text-red-400">
                            Con Errores
                        </flux:text>
                        <flux:heading size="xl" class="mt-1 text-red-900 dark:text-red-100">
                            {{ number_format($previewResults['errors'] ?? 0) }}
                        </flux:heading>
                    </div>
                </div>

                {{-- New Entities to Create --}}
                @if(!empty($previewResults['units_to_create']) || !empty($previewResults['categories_to_create']))
                    <div class="mb-6 grid gap-4 md:grid-cols-2">
                        @if(!empty($previewResults['units_to_create']))
                            <flux:callout variant="warning">
                                <flux:heading size="sm">Unidades de Medida a Crear</flux:heading>
                                <flux:text size="sm" class="mt-2">
                                    Las siguientes unidades no existen y se crearán automáticamente:
                                </flux:text>
                                <div class="mt-2 flex flex-wrap gap-2">
                                    @foreach($previewResults['units_to_create'] as $unit)
                                        <flux:badge color="yellow">{{ ucfirst($unit) }}</flux:badge>
                                    @endforeach
                                </div>
                            </flux:callout>
                        @endif

                        @if(!empty($previewResults['categories_to_create']))
                            <flux:callout variant="warning">
                                <flux:heading size="sm">Categorías a Crear</flux:heading>
                                <flux:text size="sm" class="mt-2">
                                    Las siguientes categorías no existen y se crearán automáticamente:
                                </flux:text>
                                <div class="mt-2 flex flex-wrap gap-2">
                                    @foreach($previewResults['categories_to_create'] as $category)
                                        <flux:badge color="yellow">{{ ucfirst($category) }}</flux:badge>
                                    @endforeach
                                </div>
                            </flux:callout>
                        @endif
                    </div>
                @endif

                {{-- Row Details --}}
                @if(!empty($previewResults['rows']))
                    <div class="mb-6">
                        <flux:heading size="sm" class="mb-4 text-zinc-900 dark:text-zinc-100">
                            Detalle de Registros (primeros 50)
                        </flux:heading>

                        <div class="max-h-96 overflow-y-auto rounded-lg border border-zinc-200 dark:border-zinc-700">
                            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                                <thead class="bg-zinc-50 dark:bg-zinc-800 sticky top-0">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium uppercase text-zinc-500 dark:text-zinc-400">Fila</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium uppercase text-zinc-500 dark:text-zinc-400">Estado</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium uppercase text-zinc-500 dark:text-zinc-400">SKU</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium uppercase text-zinc-500 dark:text-zinc-400">Nombre</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium uppercase text-zinc-500 dark:text-zinc-400">Categoría</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium uppercase text-zinc-500 dark:text-zinc-400">Unidad</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium uppercase text-zinc-500 dark:text-zinc-400">Mensajes</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-900">
                                    @foreach(array_slice($previewResults['rows'], 0, 50) as $row)
                                        @php
                                            $rowClass = match($row['status']) {
                                                'error' => 'bg-red-50 dark:bg-red-950/20',
                                                'warning' => 'bg-yellow-50 dark:bg-yellow-950/20',
                                                default => ''
                                            };
                                        @endphp
                                        <tr class="{{ $rowClass }}">
                                            <td class="whitespace-nowrap px-4 py-3 text-sm text-zinc-900 dark:text-zinc-100">
                                                {{ $row['row'] }}
                                            </td>
                                            <td class="whitespace-nowrap px-4 py-3 text-sm">
                                                @if($row['status'] === 'valid')
                                                    <flux:badge color="green" size="sm">Válido</flux:badge>
                                                @elseif($row['status'] === 'warning')
                                                    <flux:badge color="yellow" size="sm">Advertencia</flux:badge>
                                                @else
                                                    <flux:badge color="red" size="sm">Error</flux:badge>
                                                @endif
                                            </td>
                                            <td class="whitespace-nowrap px-4 py-3 text-sm font-mono text-zinc-900 dark:text-zinc-100">
                                                {{ $row['sku'] ?? '-' }}
                                            </td>
                                            <td class="px-4 py-3 text-sm text-zinc-900 dark:text-zinc-100">
                                                {{ Str::limit($row['nombre'] ?? '-', 30) }}
                                            </td>
                                            <td class="px-4 py-3 text-sm">
                                                @if(isset($row['mappings']['category']))
                                                    @if($row['mappings']['category']['action'] === 'use_existing')
                                                        <flux:badge color="green" size="sm">{{ $row['categoria'] }}</flux:badge>
                                                    @else
                                                        <flux:badge color="yellow" size="sm">+ {{ $row['categoria'] }}</flux:badge>
                                                    @endif
                                                @else
                                                    {{ $row['categoria'] ?? '-' }}
                                                @endif
                                            </td>
                                            <td class="px-4 py-3 text-sm">
                                                @if(isset($row['mappings']['unit']))
                                                    @if($row['mappings']['unit']['action'] === 'use_existing')
                                                        <flux:badge color="green" size="sm">{{ $row['mappings']['unit']['symbol'] ?? $row['unidad_medida'] }}</flux:badge>
                                                    @else
                                                        <flux:badge color="yellow" size="sm">+ {{ $row['unidad_medida'] }}</flux:badge>
                                                    @endif
                                                @else
                                                    {{ $row['unidad_medida'] ?? '-' }}
                                                @endif
                                            </td>
                                            <td class="px-4 py-3 text-sm">
                                                @if(!empty($row['errors']))
                                                    <ul class="list-disc list-inside text-red-600 dark:text-red-400">
                                                        @foreach($row['errors'] as $error)
                                                            <li>{{ $error }}</li>
                                                        @endforeach
                                                    </ul>
                                                @endif
                                                @if(!empty($row['warnings']))
                                                    <ul class="list-disc list-inside text-yellow-600 dark:text-yellow-400">
                                                        @foreach($row['warnings'] as $warning)
                                                            <li>{{ Str::limit($warning, 60) }}</li>
                                                        @endforeach
                                                    </ul>
                                                @endif
                                                @if(empty($row['errors']) && empty($row['warnings']))
                                                    <flux:text size="sm" class="text-green-600 dark:text-green-400">OK</flux:text>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

                {{-- Action Buttons --}}
                <div class="flex items-center justify-end gap-3 pt-4 border-t border-zinc-200 dark:border-zinc-700">
                    <flux:button
                        wire:click="cancelPreview"
                        variant="outline"
                    >
                        Cancelar
                    </flux:button>

                    @if($previewResults['can_import'] ?? false)
                        <flux:button
                            wire:click="confirmImport"
                            variant="primary"
                            :disabled="$importing"
                            wire:loading.attr="disabled"
                            wire:target="confirmImport"
                            icon="arrow-up-tray"
                        >
                            <span wire:loading.remove wire:target="confirmImport">Confirmar Importación</span>
                            <span wire:loading wire:target="confirmImport">Importando...</span>
                        </flux:button>
                    @else
                        <flux:button
                            variant="primary"
                            disabled
                            icon="x-circle"
                        >
                            No se puede importar (hay errores)
                        </flux:button>
                    @endif
                </div>
            </div>
        </flux:card>
    @endif

    {{-- Import Results --}}
    @if($showResults)
        <flux:card>
            <div class="border-b border-zinc-200 p-4 dark:border-zinc-700">
                <div class="flex items-center justify-between">
                    <flux:heading size="base" class="text-zinc-900 dark:text-zinc-100">
                        Resultados de Importación
                    </flux:heading>
                    <flux:button
                        wire:click="clearResults"
                        variant="ghost"
                        size="sm"
                        icon="x-mark"
                    >
                        Cerrar
                    </flux:button>
                </div>
            </div>

            <div class="p-6">
                {{-- Summary Cards --}}
                <div class="mb-6 grid gap-4 sm:grid-cols-3">
                    <div class="rounded-lg border-l-4 border-green-500 bg-green-50 p-4 dark:bg-green-950/20">
                        <flux:text size="sm" class="font-medium text-green-600 dark:text-green-400">
                            Exitosos
                        </flux:text>
                        <flux:heading size="xl" class="mt-1 text-green-900 dark:text-green-100">
                            {{ number_format($importResults['success']) }}
                        </flux:heading>
                    </div>

                    <div class="rounded-lg border-l-4 border-red-500 bg-red-50 p-4 dark:bg-red-950/20">
                        <flux:text size="sm" class="font-medium text-red-600 dark:text-red-400">
                            Omitidos
                        </flux:text>
                        <flux:heading size="xl" class="mt-1 text-red-900 dark:text-red-100">
                            {{ number_format($importResults['skipped']) }}
                        </flux:heading>
                    </div>

                    <div class="rounded-lg border-l-4 border-blue-500 bg-blue-50 p-4 dark:bg-blue-950/20">
                        <flux:text size="sm" class="font-medium text-blue-600 dark:text-blue-400">
                            Total Procesados
                        </flux:text>
                        <flux:heading size="xl" class="mt-1 text-blue-900 dark:text-blue-100">
                            {{ number_format($importResults['success'] + $importResults['skipped']) }}
                        </flux:heading>
                    </div>
                </div>

                {{-- Errors List --}}
                @if(count($importResults['errors']) > 0)
                    <div class="space-y-3">
                        <flux:heading size="sm" class="text-zinc-900 dark:text-zinc-100">
                            Errores Encontrados ({{ count($importResults['errors']) }})
                        </flux:heading>

                        <div class="max-h-96 space-y-2 overflow-y-auto">
                            @foreach($importResults['errors'] as $error)
                                <div class="flex items-start gap-3 rounded-lg border border-red-200 bg-red-50 p-3 dark:border-red-800 dark:bg-red-900/20">
                                    <flux:icon name="exclamation-circle" class="h-5 w-5 flex-shrink-0 text-red-600 dark:text-red-400" />
                                    <div class="flex-1">
                                        <flux:text size="sm" class="font-medium text-red-900 dark:text-red-100">
                                            Fila {{ $error['row'] }}
                                        </flux:text>
                                        <flux:text size="sm" class="text-red-700 dark:text-red-300">
                                            {{ $error['error'] }}
                                        </flux:text>
                                        @if(isset($error['data']) && count($error['data']) > 0)
                                            <flux:text size="xs" class="mt-1 text-red-600 dark:text-red-400">
                                                Datos: {{ implode(', ', array_slice($error['data'], 0, 3)) }}...
                                            </flux:text>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @else
                    <flux:callout variant="success">
                        <flux:heading size="sm">¡Importación Exitosa!</flux:heading>
                        <flux:text size="sm">
                            Todos los registros se importaron correctamente sin errores.
                        </flux:text>
                    </flux:callout>
                @endif
            </div>
        </flux:card>
    @endif
</div>
