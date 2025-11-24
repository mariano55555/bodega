<?php

use Livewire\Volt\Component;
use Spatie\Permission\Models\Permission;
use Livewire\Attributes\Validate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;

new #[Layout('components.layouts.app')] class extends Component
{
    #[Validate('required|string|max:255|unique:permissions,name')]
    public string $name = '';

    #[Validate('nullable|string|max:255')]
    public string $display_name = '';

    #[Validate('nullable|string|max:500')]
    public string $description = '';

    #[Validate('required|string|max:100')]
    public string $module = '';

    #[Computed]
    public function availableModules()
    {
        return [
            'users' => 'Gestión de Usuarios',
            'roles' => 'Gestión de Roles',
            'permissions' => 'Gestión de Permisos',
            'companies' => 'Gestión de Empresas',
            'branches' => 'Gestión de Sucursales',
            'warehouses' => 'Gestión de Almacenes',
            'products' => 'Gestión de Productos',
            'inventory' => 'Gestión de Inventario',
            'movements' => 'Movimientos de Inventario',
            'transfers' => 'Transferencias',
            'dispatches' => 'Despachos',
            'purchases' => 'Compras',
            'suppliers' => 'Proveedores',
            'customers' => 'Clientes',
            'reports' => 'Reportes',
            'settings' => 'Configuración',
            'audit' => 'Auditoría',
        ];
    }

    #[Computed]
    public function commonActions()
    {
        return [
            'view' => 'Ver/Consultar',
            'create' => 'Crear',
            'edit' => 'Editar',
            'delete' => 'Eliminar',
            'manage' => 'Gestionar',
            'export' => 'Exportar',
            'import' => 'Importar',
            'approve' => 'Aprobar',
            'reject' => 'Rechazar',
        ];
    }

    public function generatePermissionName($module, $action): void
    {
        if ($module && $action) {
            $this->name = "{$module}.{$action}";
        }
    }

    public function save(): void
    {
        $this->validate();

        Permission::create([
            'name' => $this->name,
            'display_name' => $this->display_name,
            'description' => $this->description,
            'guard_name' => 'web',
        ]);

        $this->dispatch('permission-created', [
            'message' => 'Permiso creado exitosamente',
            'permission' => $this->display_name ?: $this->name
        ]);

        $this->redirect(route('admin.permissions.index'), navigate: true);
    }

    public function with(): array
    {
        return [
            'title' => 'Crear Nuevo Permiso',
        ];
    }
}; ?>

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Page Header -->
    <div class="mb-8">
        <div class="flex items-center gap-4 mb-4">
            <flux:button variant="ghost" icon="arrow-left" :href="route('admin.permissions.index')" wire:navigate>
                Volver
            </flux:button>
            <div>
                <flux:heading size="xl" class="text-zinc-900 dark:text-zinc-100">
                    Crear Nuevo Permiso
                </flux:heading>
                <flux:text class="mt-1 text-zinc-600 dark:text-zinc-400">
                    Define un nuevo permiso para el sistema de control de acceso
                </flux:text>
            </div>
        </div>
    </div>

    <form wire:submit="save" class="space-y-8">
        <!-- Basic Information -->
        <flux:card>
            <flux:heading>
                <flux:heading size="lg">Información del Permiso</flux:heading>
                <flux:text class="text-zinc-600 dark:text-zinc-400">
                    Datos principales del permiso en el sistema
                </flux:text>
            </flux:heading>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Module -->
                <flux:field>
                    <flux:label>Módulo *</flux:label>
                    <flux:select wire:model.live="module" placeholder="Selecciona un módulo" description="Módulo al que pertenece el permiso">
                        @foreach($this->availableModules as $key => $label)
                            <flux:select.option value="{{ $key }}">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="module" />
                </flux:field>

                <!-- Quick Actions -->
                @if($module)
                    <flux:field>
                        <flux:label>Acciones Rápidas</flux:label>
                        <div class="grid grid-cols-2 gap-2">
                            @foreach($this->commonActions as $action => $label)
                                <flux:button
                                    size="sm"
                                    variant="outline"
                                    type="button"
                                    wire:click="generatePermissionName('{{ $module }}', '{{ $action }}')"
                                >
                                    {{ $label }}
                                </flux:button>
                            @endforeach
                        </div>
                        <flux:text class="text-sm text-zinc-500 mt-2">Haz clic para generar automáticamente el nombre del permiso</flux:text>
                    </flux:field>
                @endif

                <!-- Permission Name -->
                <flux:field>
                    <flux:label>Nombre del Permiso *</flux:label>
                    <flux:input wire:model="name" placeholder="ej: users.create" description="Nombre técnico del permiso (formato: módulo.acción)" />
                    <flux:error name="name" />
                </flux:field>

                <!-- Display Name -->
                <flux:field>
                    <flux:label>Nombre para Mostrar</flux:label>
                    <flux:input wire:model="display_name" placeholder="ej: Crear Usuarios" description="Nombre amigable que se mostrará en la interfaz" />
                    <flux:error name="display_name" />
                </flux:field>

                <!-- Description -->
                <div class="lg:col-span-2">
                    <flux:field>
                        <flux:label>Descripción</flux:label>
                        <flux:textarea wire:model="description" rows="3" placeholder="Describe qué permite hacer este permiso..." description="Descripción detallada del alcance del permiso" />
                        <flux:error name="description" />
                    </flux:field>
                </div>
            </div>
        </flux:card>

        <!-- Permission Preview -->
        @if($name)
            <flux:card>
                <flux:heading>
                    <flux:heading size="lg">Vista Previa del Permiso</flux:heading>
                    <flux:text class="text-zinc-600 dark:text-zinc-400">
                        Así se verá el permiso en el sistema
                    </flux:text>
                </flux:heading>

                <div class="bg-zinc-50 dark:bg-zinc-800 rounded-lg p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <flux:text class="text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                                Nombre Técnico:
                            </flux:text>
                            <flux:text class="font-mono text-sm bg-white dark:bg-zinc-900 px-2 py-1 rounded">
                                {{ $name }}
                            </flux:text>
                        </div>

                        @if($display_name)
                            <div>
                                <flux:text class="text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                                    Nombre para Mostrar:
                                </flux:text>
                                <flux:text class="text-sm">
                                    {{ $display_name }}
                                </flux:text>
                            </div>
                        @endif

                        @if($module)
                            <div>
                                <flux:text class="text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                                    Módulo:
                                </flux:text>
                                <flux:badge variant="outline" color="blue">
                                    {{ $this->availableModules[$module] ?? $module }}
                                </flux:badge>
                            </div>
                        @endif

                        @if($description)
                            <div class="md:col-span-2">
                                <flux:text class="text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                                    Descripción:
                                </flux:text>
                                <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                                    {{ $description }}
                                </flux:text>
                            </div>
                        @endif
                    </div>
                </div>
            </flux:card>
        @endif

        <!-- Common Permission Patterns -->
        <flux:card>
            <flux:heading>
                <flux:heading size="lg">Patrones Comunes de Permisos</flux:heading>
                <flux:text class="text-zinc-600 dark:text-zinc-400">
                    Ejemplos de convenciones para nombres de permisos
                </flux:text>
            </flux:heading>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <flux:text class="font-medium text-sm mb-3">CRUD Básico:</flux:text>
                    <div class="space-y-2">
                        <div class="flex items-center gap-2">
                            <flux:text class="font-mono text-xs bg-zinc-100 dark:bg-zinc-800 px-2 py-1 rounded">usuarios.view</flux:text>
                            <flux:text class="text-sm text-zinc-500">Ver usuarios</flux:text>
                        </div>
                        <div class="flex items-center gap-2">
                            <flux:text class="font-mono text-xs bg-zinc-100 dark:bg-zinc-800 px-2 py-1 rounded">usuarios.create</flux:text>
                            <flux:text class="text-sm text-zinc-500">Crear usuarios</flux:text>
                        </div>
                        <div class="flex items-center gap-2">
                            <flux:text class="font-mono text-xs bg-zinc-100 dark:bg-zinc-800 px-2 py-1 rounded">usuarios.edit</flux:text>
                            <flux:text class="text-sm text-zinc-500">Editar usuarios</flux:text>
                        </div>
                        <div class="flex items-center gap-2">
                            <flux:text class="font-mono text-xs bg-zinc-100 dark:bg-zinc-800 px-2 py-1 rounded">usuarios.delete</flux:text>
                            <flux:text class="text-sm text-zinc-500">Eliminar usuarios</flux:text>
                        </div>
                    </div>
                </div>

                <div>
                    <flux:text class="font-medium text-sm mb-3">Acciones Especiales:</flux:text>
                    <div class="space-y-2">
                        <div class="flex items-center gap-2">
                            <flux:text class="font-mono text-xs bg-zinc-100 dark:bg-zinc-800 px-2 py-1 rounded">reportes.export</flux:text>
                            <flux:text class="text-sm text-zinc-500">Exportar reportes</flux:text>
                        </div>
                        <div class="flex items-center gap-2">
                            <flux:text class="font-mono text-xs bg-zinc-100 dark:bg-zinc-800 px-2 py-1 rounded">inventario.approve</flux:text>
                            <flux:text class="text-sm text-zinc-500">Aprobar inventario</flux:text>
                        </div>
                        <div class="flex items-center gap-2">
                            <flux:text class="font-mono text-xs bg-zinc-100 dark:bg-zinc-800 px-2 py-1 rounded">almacenes.manage</flux:text>
                            <flux:text class="text-sm text-zinc-500">Gestionar almacenes</flux:text>
                        </div>
                    </div>
                </div>
            </div>
        </flux:card>

        <!-- Form Actions -->
        <div class="flex items-center justify-between pt-6 border-t border-zinc-200 dark:border-zinc-700">
            <flux:button variant="ghost" :href="route('admin.permissions.index')" wire:navigate>
                Cancelar
            </flux:button>
            <flux:button type="submit" variant="primary" icon="check">
                Crear Permiso
            </flux:button>
        </div>
    </form>
</div>
