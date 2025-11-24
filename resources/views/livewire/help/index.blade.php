<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;

new #[Layout('components.layouts.app')] class extends Component
{
    #[Url(as: 'seccion', history: true)]
    public string $activeModule = 'overview';

    public string $search = '';
    public bool $showToc = true;

    public function mount(): void
    {
        // Validate the module from URL exists
        $validModules = array_keys($this->modules());
        if (!in_array($this->activeModule, $validModules)) {
            $this->activeModule = 'overview';
        }
    }

    public function setActiveModule(string $module): void
    {
        $this->activeModule = $module;
    }

    public function updatedSearch(): void
    {
        $filteredModules = $this->filteredModules;

        // If current active module is not in filtered results, switch to first available
        if (!empty($filteredModules) && !array_key_exists($this->activeModule, $filteredModules)) {
            $this->activeModule = array_key_first($filteredModules);
        }
        // If no modules match and we had a search, keep the current module
        // so user can still see content while searching
    }

    #[Computed]
    public function filteredModules()
    {
        $modules = $this->modules();

        if (empty($this->search)) {
            return $modules;
        }

        $searchLower = strtolower($this->search);

        return array_filter($modules, function ($module, $key) use ($searchLower) {
            return str_contains(strtolower($module['title']), $searchLower) ||
                   str_contains(strtolower($module['description']), $searchLower) ||
                   str_contains(strtolower($key), $searchLower);
        }, ARRAY_FILTER_USE_BOTH);
    }

    #[Computed]
    public function groupedModules()
    {
        $modules = $this->filteredModules;
        $grouped = [];

        foreach ($modules as $key => $module) {
            $group = $module['group'] ?? 'General';
            if (!isset($grouped[$group])) {
                $grouped[$group] = [];
            }
            $grouped[$group][$key] = $module;
        }

        return $grouped;
    }

    #[Computed]
    public function modules()
    {
        return [
            // General
            'overview' => [
                'title' => 'Resumen General',
                'icon' => 'home',
                'color' => 'blue',
                'description' => 'Introducción al sistema de gestión de bodegas',
                'group' => 'General'
            ],
            'authentication' => [
                'title' => 'Autenticación',
                'icon' => 'lock-closed',
                'color' => 'green',
                'description' => 'Sistema de login, registro y recuperación de contraseña',
                'group' => 'General'
            ],

            // Operaciones de Bodega
            'products' => [
                'title' => 'Catálogo de Productos',
                'icon' => 'cube',
                'color' => 'emerald',
                'description' => 'Catálogo de productos, categorías y especificaciones',
                'group' => 'Operaciones de Bodega'
            ],
            'purchases' => [
                'title' => 'Compras',
                'icon' => 'shopping-cart',
                'color' => 'blue',
                'description' => 'Registro de compras e ingreso de productos al inventario',
                'group' => 'Operaciones de Bodega'
            ],
            'donations' => [
                'title' => 'Donaciones',
                'icon' => 'gift',
                'color' => 'pink',
                'description' => 'Recepción de donaciones e ingreso al inventario',
                'group' => 'Operaciones de Bodega'
            ],
            'transfers' => [
                'title' => 'Traslados',
                'icon' => 'arrow-path',
                'color' => 'cyan',
                'description' => 'Movimiento de productos entre bodegas',
                'group' => 'Operaciones de Bodega'
            ],
            'dispatches' => [
                'title' => 'Despachos',
                'icon' => 'truck',
                'color' => 'orange',
                'description' => 'Salida de productos hacia clientes o destinos',
                'group' => 'Operaciones de Bodega'
            ],
            'adjustments' => [
                'title' => 'Ajustes de Inventario',
                'icon' => 'adjustments-horizontal',
                'color' => 'purple',
                'description' => 'Correcciones y ajustes del inventario físico',
                'group' => 'Operaciones de Bodega'
            ],
            'closures' => [
                'title' => 'Cierres Mensuales',
                'icon' => 'lock-closed',
                'color' => 'slate',
                'description' => 'Cierre contable mensual del inventario',
                'group' => 'Operaciones de Bodega'
            ],

            // Catálogos
            'suppliers' => [
                'title' => 'Proveedores',
                'icon' => 'building-storefront',
                'color' => 'indigo',
                'description' => 'Gestión de proveedores para compras',
                'group' => 'Catálogos'
            ],
            'donors' => [
                'title' => 'Donantes',
                'icon' => 'heart',
                'color' => 'rose',
                'description' => 'Gestión de donantes y fuentes de donación',
                'group' => 'Catálogos'
            ],
            'customers' => [
                'title' => 'Clientes',
                'icon' => 'users',
                'color' => 'teal',
                'description' => 'Gestión de clientes y destinatarios',
                'group' => 'Catálogos'
            ],

            // Gestión de Almacenes
            'companies' => [
                'title' => 'Empresas',
                'icon' => 'building-office-2',
                'color' => 'indigo',
                'description' => 'Administración de empresas y configuración corporativa',
                'group' => 'Gestión de Almacenes'
            ],
            'branches' => [
                'title' => 'Sucursales',
                'icon' => 'building-storefront',
                'color' => 'cyan',
                'description' => 'Manejo de sucursales y ubicaciones de la empresa',
                'group' => 'Gestión de Almacenes'
            ],
            'warehouses' => [
                'title' => 'Bodegas',
                'icon' => 'building-office',
                'color' => 'orange',
                'description' => 'Administración de bodegas y espacios de almacenamiento',
                'group' => 'Gestión de Almacenes'
            ],
            'storage-locations' => [
                'title' => 'Ubicaciones de Almacenamiento',
                'icon' => 'map-pin',
                'color' => 'amber',
                'description' => 'Organización de ubicaciones dentro de bodegas',
                'group' => 'Gestión de Almacenes'
            ],

            // Consultas e Inventario
            'inventory' => [
                'title' => 'Resumen de Inventario',
                'icon' => 'squares-2x2',
                'color' => 'emerald',
                'description' => 'Vista general del estado del inventario',
                'group' => 'Consultas e Inventario'
            ],
            'stock-query' => [
                'title' => 'Consulta de Existencias',
                'icon' => 'magnifying-glass',
                'color' => 'blue',
                'description' => 'Búsqueda y consulta de stock disponible',
                'group' => 'Consultas e Inventario'
            ],
            'movements' => [
                'title' => 'Consulta de Movimientos',
                'icon' => 'arrows-right-left',
                'color' => 'violet',
                'description' => 'Historial y kardex de movimientos de inventario',
                'group' => 'Consultas e Inventario'
            ],
            'alerts' => [
                'title' => 'Alertas de Stock',
                'icon' => 'bell',
                'color' => 'red',
                'description' => 'Configuración de alertas de mínimos y máximos',
                'group' => 'Consultas e Inventario'
            ],
            'traceability' => [
                'title' => 'Trazabilidad Histórica',
                'icon' => 'clock',
                'color' => 'gray',
                'description' => 'Seguimiento histórico de productos y lotes',
                'group' => 'Consultas e Inventario'
            ],

            // Reportería
            'reports' => [
                'title' => 'Reportes y Análisis',
                'icon' => 'chart-bar',
                'color' => 'teal',
                'description' => 'Informes, estadísticas y análisis de datos',
                'group' => 'Reportería'
            ],

            // Control de Usuarios
            'users' => [
                'title' => 'Gestión de Usuarios',
                'icon' => 'users',
                'color' => 'purple',
                'description' => 'Gestión de usuarios del sistema',
                'group' => 'Control de Usuarios'
            ],
            'roles' => [
                'title' => 'Gestión de Roles',
                'icon' => 'shield-check',
                'color' => 'green',
                'description' => 'Configuración de roles del sistema',
                'group' => 'Control de Usuarios'
            ],
            'permissions' => [
                'title' => 'Gestión de Permisos',
                'icon' => 'key',
                'color' => 'amber',
                'description' => 'Administración de permisos y accesos',
                'group' => 'Control de Usuarios'
            ],

            // Extras
            'shortcuts' => [
                'title' => 'Atajos de Teclado',
                'icon' => 'command-line',
                'color' => 'slate',
                'description' => 'Navegación rápida con atajos de teclado',
                'group' => 'Extras'
            ],
            'notifications' => [
                'title' => 'Notificaciones',
                'icon' => 'bell',
                'color' => 'red',
                'description' => 'Sistema de alertas y notificaciones en tiempo real',
                'group' => 'Extras'
            ]
        ];
    }

    public function with(): array
    {
        return [
            'title' => 'Documentación del Sistema',
        ];
    }
}; ?>

<div class="min-h-screen bg-zinc-50 dark:bg-zinc-900">
    <div class="max-w-screen-2xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header -->
        <div class="mb-8">
            <flux:heading size="xl" class="text-zinc-900 dark:text-zinc-100 mb-2">
                📚 Documentación del Sistema
            </flux:heading>
            <flux:text class="text-zinc-600 dark:text-zinc-400">
                Manual completo de usuario para el sistema de gestión de bodegas
            </flux:text>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
            <!-- Sidebar Navigation -->
            <div class="lg:col-span-1">
                <flux:card class="sticky top-6">
                    <!-- Search -->
                    <div class="mb-4">
                        <flux:input
                            wire:model.live.debounce.300ms="search"
                            placeholder="Buscar en la documentación..."
                            icon="magnifying-glass"
                        />
                    </div>

                    <!-- Table of Contents -->
                    <div class="space-y-4 max-h-[calc(100vh-200px)] overflow-y-auto">
                        @foreach($this->groupedModules as $groupName => $groupModules)
                            <div class="space-y-1">
                                <flux:heading size="xs" class="text-zinc-500 dark:text-zinc-400 uppercase tracking-wider px-2 py-1">
                                    {{ $groupName }}
                                </flux:heading>

                                @foreach($groupModules as $moduleKey => $module)
                                    <button
                                        wire:click="setActiveModule('{{ $moduleKey }}')"
                                        @class([
                                            'w-full text-left px-2 py-2 rounded-lg border border-transparent transition-all duration-200 group',
                                            'bg-blue-50 dark:bg-blue-950 border-blue-200 dark:border-blue-800' => $activeModule === $moduleKey && $module['color'] === 'blue',
                                            'bg-green-50 dark:bg-green-950 border-green-200 dark:border-green-800' => $activeModule === $moduleKey && $module['color'] === 'green',
                                            'bg-purple-50 dark:bg-purple-950 border-purple-200 dark:border-purple-800' => $activeModule === $moduleKey && $module['color'] === 'purple',
                                            'bg-indigo-50 dark:bg-indigo-950 border-indigo-200 dark:border-indigo-800' => $activeModule === $moduleKey && $module['color'] === 'indigo',
                                            'bg-cyan-50 dark:bg-cyan-950 border-cyan-200 dark:border-cyan-800' => $activeModule === $moduleKey && $module['color'] === 'cyan',
                                            'bg-orange-50 dark:bg-orange-950 border-orange-200 dark:border-orange-800' => $activeModule === $moduleKey && $module['color'] === 'orange',
                                            'bg-emerald-50 dark:bg-emerald-950 border-emerald-200 dark:border-emerald-800' => $activeModule === $moduleKey && $module['color'] === 'emerald',
                                            'bg-rose-50 dark:bg-rose-950 border-rose-200 dark:border-rose-800' => $activeModule === $moduleKey && $module['color'] === 'rose',
                                            'bg-pink-50 dark:bg-pink-950 border-pink-200 dark:border-pink-800' => $activeModule === $moduleKey && $module['color'] === 'pink',
                                            'bg-amber-50 dark:bg-amber-950 border-amber-200 dark:border-amber-800' => $activeModule === $moduleKey && $module['color'] === 'amber',
                                            'bg-teal-50 dark:bg-teal-950 border-teal-200 dark:border-teal-800' => $activeModule === $moduleKey && $module['color'] === 'teal',
                                            'bg-violet-50 dark:bg-violet-950 border-violet-200 dark:border-violet-800' => $activeModule === $moduleKey && $module['color'] === 'violet',
                                            'bg-zinc-100 dark:bg-zinc-800 border-zinc-200 dark:border-zinc-700' => $activeModule === $moduleKey && in_array($module['color'], ['slate', 'gray']),
                                            'bg-red-50 dark:bg-red-950 border-red-200 dark:border-red-800' => $activeModule === $moduleKey && $module['color'] === 'red',
                                            'hover:bg-zinc-100 dark:hover:bg-zinc-800' => $activeModule !== $moduleKey,
                                        ])
                                    >
                                        <div class="flex items-center gap-2">
                                            <flux:icon
                                                name="{{ $module['icon'] }}"
                                                @class([
                                                    'h-4 w-4 shrink-0',
                                                    'text-blue-600 dark:text-blue-400' => $activeModule === $moduleKey && $module['color'] === 'blue',
                                                    'text-green-600 dark:text-green-400' => $activeModule === $moduleKey && $module['color'] === 'green',
                                                    'text-purple-600 dark:text-purple-400' => $activeModule === $moduleKey && $module['color'] === 'purple',
                                                    'text-indigo-600 dark:text-indigo-400' => $activeModule === $moduleKey && $module['color'] === 'indigo',
                                                    'text-cyan-600 dark:text-cyan-400' => $activeModule === $moduleKey && $module['color'] === 'cyan',
                                                    'text-orange-600 dark:text-orange-400' => $activeModule === $moduleKey && $module['color'] === 'orange',
                                                    'text-emerald-600 dark:text-emerald-400' => $activeModule === $moduleKey && $module['color'] === 'emerald',
                                                    'text-rose-600 dark:text-rose-400' => $activeModule === $moduleKey && $module['color'] === 'rose',
                                                    'text-pink-600 dark:text-pink-400' => $activeModule === $moduleKey && $module['color'] === 'pink',
                                                    'text-amber-600 dark:text-amber-400' => $activeModule === $moduleKey && $module['color'] === 'amber',
                                                    'text-teal-600 dark:text-teal-400' => $activeModule === $moduleKey && $module['color'] === 'teal',
                                                    'text-violet-600 dark:text-violet-400' => $activeModule === $moduleKey && $module['color'] === 'violet',
                                                    'text-zinc-600 dark:text-zinc-400' => $activeModule === $moduleKey && in_array($module['color'], ['slate', 'gray']),
                                                    'text-red-600 dark:text-red-400' => $activeModule === $moduleKey && $module['color'] === 'red',
                                                    'text-zinc-500 group-hover:text-zinc-700 dark:text-zinc-400 dark:group-hover:text-zinc-200' => $activeModule !== $moduleKey,
                                                ])
                                            />
                                            <span @class([
                                                'font-medium text-sm truncate',
                                                'text-blue-700 dark:text-blue-300' => $activeModule === $moduleKey && $module['color'] === 'blue',
                                                'text-green-700 dark:text-green-300' => $activeModule === $moduleKey && $module['color'] === 'green',
                                                'text-purple-700 dark:text-purple-300' => $activeModule === $moduleKey && $module['color'] === 'purple',
                                                'text-indigo-700 dark:text-indigo-300' => $activeModule === $moduleKey && $module['color'] === 'indigo',
                                                'text-cyan-700 dark:text-cyan-300' => $activeModule === $moduleKey && $module['color'] === 'cyan',
                                                'text-orange-700 dark:text-orange-300' => $activeModule === $moduleKey && $module['color'] === 'orange',
                                                'text-emerald-700 dark:text-emerald-300' => $activeModule === $moduleKey && $module['color'] === 'emerald',
                                                'text-rose-700 dark:text-rose-300' => $activeModule === $moduleKey && $module['color'] === 'rose',
                                                'text-pink-700 dark:text-pink-300' => $activeModule === $moduleKey && $module['color'] === 'pink',
                                                'text-amber-700 dark:text-amber-300' => $activeModule === $moduleKey && $module['color'] === 'amber',
                                                'text-teal-700 dark:text-teal-300' => $activeModule === $moduleKey && $module['color'] === 'teal',
                                                'text-violet-700 dark:text-violet-300' => $activeModule === $moduleKey && $module['color'] === 'violet',
                                                'text-zinc-700 dark:text-zinc-300' => $activeModule === $moduleKey && in_array($module['color'], ['slate', 'gray']),
                                                'text-red-700 dark:text-red-300' => $activeModule === $moduleKey && $module['color'] === 'red',
                                                'text-zinc-700 dark:text-zinc-300' => $activeModule !== $moduleKey,
                                            ])>
                                                {{ $module['title'] }}
                                            </span>
                                        </div>
                                    </button>
                                @endforeach
                            </div>
                        @endforeach

                        @if($this->search && empty($this->filteredModules))
                            <div class="text-center py-8">
                                <flux:icon name="magnifying-glass" class="h-12 w-12 text-zinc-400 mx-auto mb-3" />
                                <flux:heading size="sm" class="text-zinc-600 dark:text-zinc-400 mb-2">
                                    No se encontraron resultados
                                </flux:heading>
                                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                                    No hay módulos que coincidan con "{{ $this->search }}"
                                </flux:text>
                                <flux:button
                                    size="sm"
                                    variant="ghost"
                                    wire:click="$set('search', '')"
                                    class="mt-3"
                                >
                                    Limpiar búsqueda
                                </flux:button>
                            </div>
                        @endif
                    </div>
                </flux:card>
            </div>

            <!-- Main Content -->
            <div class="lg:col-span-3">
                {{-- Share Section Header --}}
                <div class="mb-4 flex items-center justify-between" x-data="{ copied: false }">
                    <div class="flex items-center gap-2 text-sm text-zinc-500 dark:text-zinc-400">
                        <flux:icon name="link" class="h-4 w-4" />
                        <span class="hidden sm:inline">URL compartible:</span>
                        <code class="rounded bg-zinc-100 px-2 py-1 text-xs dark:bg-zinc-800">
                            {{ route('help.index') }}?seccion={{ $activeModule }}
                        </code>
                    </div>
                    <flux:button
                        variant="ghost"
                        size="sm"
                        icon="clipboard-document"
                        x-on:click="
                            navigator.clipboard.writeText('{{ route('help.index') }}?seccion={{ $activeModule }}');
                            copied = true;
                            setTimeout(() => copied = false, 2000);
                            $flux.toast({ heading: 'Enlace copiado', text: 'El enlace ha sido copiado al portapapeles', variant: 'success' });
                        "
                    >
                        <span x-show="!copied">Copiar enlace</span>
                        <span x-show="copied" x-cloak>Copiado!</span>
                    </flux:button>
                </div>

                <flux:card class="min-h-screen">
                    @if($activeModule === 'overview')
                        @include('livewire.help.modules.overview')
                    @elseif($activeModule === 'authentication')
                        @include('livewire.help.modules.authentication')
                    @elseif($activeModule === 'users')
                        @include('livewire.help.modules.users')
                    @elseif($activeModule === 'companies')
                        @include('livewire.help.modules.companies')
                    @elseif($activeModule === 'branches')
                        @include('livewire.help.modules.branches')
                    @elseif($activeModule === 'warehouses')
                        @include('livewire.help.modules.warehouses')
                    @elseif($activeModule === 'inventory')
                        @include('livewire.help.modules.inventory')
                    @elseif($activeModule === 'products')
                        @include('livewire.help.modules.products')
                    @elseif($activeModule === 'movements')
                        @include('livewire.help.modules.movements')
                    @elseif($activeModule === 'reports')
                        @include('livewire.help.modules.reports')
                    @elseif($activeModule === 'shortcuts')
                        @include('livewire.help.modules.shortcuts')
                    @elseif($activeModule === 'notifications')
                        @include('livewire.help.modules.notifications')
                    @elseif($activeModule === 'purchases')
                        @include('livewire.help.modules.purchases')
                    @elseif($activeModule === 'donations')
                        @include('livewire.help.modules.donations')
                    @elseif($activeModule === 'transfers')
                        @include('livewire.help.modules.transfers')
                    @elseif($activeModule === 'dispatches')
                        @include('livewire.help.modules.dispatches')
                    @elseif($activeModule === 'adjustments')
                        @include('livewire.help.modules.adjustments')
                    @elseif($activeModule === 'closures')
                        @include('livewire.help.modules.closures')
                    @elseif($activeModule === 'suppliers')
                        @include('livewire.help.modules.suppliers')
                    @elseif($activeModule === 'donors')
                        @include('livewire.help.modules.donors')
                    @elseif($activeModule === 'customers')
                        @include('livewire.help.modules.customers')
                    @elseif($activeModule === 'storage-locations')
                        @include('livewire.help.modules.storage-locations')
                    @elseif($activeModule === 'stock-query')
                        @include('livewire.help.modules.stock-query')
                    @elseif($activeModule === 'alerts')
                        @include('livewire.help.modules.alerts')
                    @elseif($activeModule === 'traceability')
                        @include('livewire.help.modules.traceability')
                    @elseif($activeModule === 'roles')
                        @include('livewire.help.modules.roles')
                    @elseif($activeModule === 'permissions')
                        @include('livewire.help.modules.permissions')
                    @else
                        @include('livewire.help.modules.coming-soon', ['module' => $this->modules[$activeModule] ?? null])
                    @endif
                </flux:card>
            </div>
        </div>
    </div>
</div>
