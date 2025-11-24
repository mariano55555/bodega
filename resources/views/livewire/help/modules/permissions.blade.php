<div class="space-y-8">
    <!-- Module Header -->
    <div class="border-b border-zinc-200 dark:border-zinc-700 pb-6">
        <div class="flex items-center gap-4 mb-4">
            <div class="p-3 bg-amber-100 dark:bg-amber-900 rounded-lg">
                <flux:icon name="key" class="h-8 w-8 text-amber-600 dark:text-amber-400" />
            </div>
            <div>
                <flux:heading size="xl" class="text-zinc-900 dark:text-zinc-100">
                    Gestión de Permisos
                </flux:heading>
                <flux:text class="text-zinc-600 dark:text-zinc-400">
                    Administración granular de permisos y control de acceso
                </flux:text>
            </div>
        </div>
    </div>

    <!-- What are Permissions -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            ¿Qué son los Permisos?
        </flux:heading>

        <flux:text>
            Los permisos son las acciones específicas que un usuario puede realizar en el sistema.
            Cada permiso representa una acción concreta como "ver usuarios", "crear productos",
            "editar bodegas", etc. Los permisos se agrupan por módulos y se asignan a roles.
        </flux:text>

        <div class="bg-amber-50 dark:bg-amber-950 p-4 rounded-lg border border-amber-200 dark:border-amber-800">
            <flux:heading size="sm" class="text-amber-800 dark:text-amber-200 mb-2 flex items-center gap-2">
                <flux:icon name="information-circle" class="h-5 w-5" />
                Convención de Nombres
            </flux:heading>
            <flux:text class="text-sm text-amber-700 dark:text-amber-300">
                Los permisos siguen el formato <strong>módulo.acción</strong> (ej: users.create, products.edit).
                Esto facilita su organización y búsqueda en el sistema.
            </flux:text>
        </div>
    </section>

    <!-- Permission Types -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Tipos de Permisos
        </flux:heading>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <flux:card class="p-4">
                <div class="flex items-center gap-3 mb-3">
                    <flux:badge size="lg" color="green">view</flux:badge>
                    <flux:heading size="md">Ver / Listar</flux:heading>
                </div>
                <flux:text class="text-sm">
                    Permite ver y listar elementos del módulo. Es el permiso más básico y generalmente
                    se otorga a todos los usuarios que necesitan acceso al módulo.
                </flux:text>
            </flux:card>

            <flux:card class="p-4">
                <div class="flex items-center gap-3 mb-3">
                    <flux:badge size="lg" color="blue">create</flux:badge>
                    <flux:heading size="md">Crear</flux:heading>
                </div>
                <flux:text class="text-sm">
                    Permite crear nuevos elementos en el módulo. Incluye acceso a formularios de
                    creación y procesamiento de nuevos registros.
                </flux:text>
            </flux:card>

            <flux:card class="p-4">
                <div class="flex items-center gap-3 mb-3">
                    <flux:badge size="lg" color="yellow">edit</flux:badge>
                    <flux:heading size="md">Editar</flux:heading>
                </div>
                <flux:text class="text-sm">
                    Permite modificar elementos existentes. Incluye acceso a formularios de edición
                    y actualización de registros.
                </flux:text>
            </flux:card>

            <flux:card class="p-4">
                <div class="flex items-center gap-3 mb-3">
                    <flux:badge size="lg" color="red">delete</flux:badge>
                    <flux:heading size="md">Eliminar</flux:heading>
                </div>
                <flux:text class="text-sm">
                    Permite eliminar elementos del sistema. Es un permiso sensible que debe
                    otorgarse con cuidado.
                </flux:text>
            </flux:card>

            <flux:card class="p-4">
                <div class="flex items-center gap-3 mb-3">
                    <flux:badge size="lg" color="purple">manage</flux:badge>
                    <flux:heading size="md">Gestionar</flux:heading>
                </div>
                <flux:text class="text-sm">
                    Permite acciones especiales de gestión como cambiar estados, aprobar,
                    o realizar operaciones administrativas.
                </flux:text>
            </flux:card>

            <flux:card class="p-4">
                <div class="flex items-center gap-3 mb-3">
                    <flux:badge size="lg" color="cyan">export</flux:badge>
                    <flux:heading size="md">Exportar</flux:heading>
                </div>
                <flux:text class="text-sm">
                    Permite exportar datos a formatos como Excel, PDF o CSV.
                    Importante para control de datos sensibles.
                </flux:text>
            </flux:card>
        </div>
    </section>

    <!-- Permission Modules -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Módulos de Permisos
        </flux:heading>

        <flux:text>
            Los permisos están organizados por módulos funcionales del sistema.
            Cada módulo agrupa los permisos relacionados para facilitar su gestión.
        </flux:text>

        <div class="bg-gray-50 dark:bg-gray-900 p-4 rounded-lg">
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                <div class="flex items-center gap-2 p-2 bg-white dark:bg-zinc-800 rounded-lg">
                    <flux:icon name="users" class="h-5 w-5 text-purple-500" />
                    <div>
                        <flux:text class="font-medium text-sm">users</flux:text>
                        <flux:text class="text-xs text-zinc-500">Usuarios</flux:text>
                    </div>
                </div>
                <div class="flex items-center gap-2 p-2 bg-white dark:bg-zinc-800 rounded-lg">
                    <flux:icon name="shield-check" class="h-5 w-5 text-green-500" />
                    <div>
                        <flux:text class="font-medium text-sm">roles</flux:text>
                        <flux:text class="text-xs text-zinc-500">Roles</flux:text>
                    </div>
                </div>
                <div class="flex items-center gap-2 p-2 bg-white dark:bg-zinc-800 rounded-lg">
                    <flux:icon name="building-office" class="h-5 w-5 text-indigo-500" />
                    <div>
                        <flux:text class="font-medium text-sm">companies</flux:text>
                        <flux:text class="text-xs text-zinc-500">Empresas</flux:text>
                    </div>
                </div>
                <div class="flex items-center gap-2 p-2 bg-white dark:bg-zinc-800 rounded-lg">
                    <flux:icon name="building-storefront" class="h-5 w-5 text-cyan-500" />
                    <div>
                        <flux:text class="font-medium text-sm">branches</flux:text>
                        <flux:text class="text-xs text-zinc-500">Sucursales</flux:text>
                    </div>
                </div>
                <div class="flex items-center gap-2 p-2 bg-white dark:bg-zinc-800 rounded-lg">
                    <flux:icon name="building-office-2" class="h-5 w-5 text-orange-500" />
                    <div>
                        <flux:text class="font-medium text-sm">warehouses</flux:text>
                        <flux:text class="text-xs text-zinc-500">Bodegas</flux:text>
                    </div>
                </div>
                <div class="flex items-center gap-2 p-2 bg-white dark:bg-zinc-800 rounded-lg">
                    <flux:icon name="cube" class="h-5 w-5 text-emerald-500" />
                    <div>
                        <flux:text class="font-medium text-sm">products</flux:text>
                        <flux:text class="text-xs text-zinc-500">Productos</flux:text>
                    </div>
                </div>
                <div class="flex items-center gap-2 p-2 bg-white dark:bg-zinc-800 rounded-lg">
                    <flux:icon name="arrows-right-left" class="h-5 w-5 text-blue-500" />
                    <div>
                        <flux:text class="font-medium text-sm">inventory</flux:text>
                        <flux:text class="text-xs text-zinc-500">Inventario</flux:text>
                    </div>
                </div>
                <div class="flex items-center gap-2 p-2 bg-white dark:bg-zinc-800 rounded-lg">
                    <flux:icon name="chart-bar" class="h-5 w-5 text-teal-500" />
                    <div>
                        <flux:text class="font-medium text-sm">reports</flux:text>
                        <flux:text class="text-xs text-zinc-500">Reportes</flux:text>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Managing Permissions -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Gestión de Permisos
        </flux:heading>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <flux:card class="p-4">
                <flux:heading size="md" class="mb-3 flex items-center gap-2">
                    <flux:icon name="plus" class="h-5 w-5 text-green-600" />
                    Crear Permisos
                </flux:heading>
                <div class="space-y-3 text-sm">
                    <div class="flex items-start gap-2">
                        <flux:icon name="check" class="h-4 w-4 text-green-500 mt-0.5" />
                        <flux:text><strong>Módulo:</strong> Seleccione el módulo al que pertenece</flux:text>
                    </div>
                    <div class="flex items-start gap-2">
                        <flux:icon name="check" class="h-4 w-4 text-green-500 mt-0.5" />
                        <flux:text><strong>Acción:</strong> Nombre de la acción (view, create, edit, etc.)</flux:text>
                    </div>
                    <div class="flex items-start gap-2">
                        <flux:icon name="check" class="h-4 w-4 text-green-500 mt-0.5" />
                        <flux:text><strong>Nombre para mostrar:</strong> Texto legible para usuarios</flux:text>
                    </div>
                    <div class="flex items-start gap-2">
                        <flux:icon name="check" class="h-4 w-4 text-green-500 mt-0.5" />
                        <flux:text><strong>Descripción:</strong> Explicación de lo que permite</flux:text>
                    </div>
                </div>
            </flux:card>

            <flux:card class="p-4">
                <flux:heading size="md" class="mb-3 flex items-center gap-2">
                    <flux:icon name="user-group" class="h-5 w-5 text-blue-600" />
                    Asignar a Roles
                </flux:heading>
                <div class="space-y-3 text-sm">
                    <div class="flex items-start gap-2">
                        <flux:icon name="check" class="h-4 w-4 text-blue-500 mt-0.5" />
                        <flux:text>Use "Gestionar roles" para asignar el permiso a múltiples roles</flux:text>
                    </div>
                    <div class="flex items-start gap-2">
                        <flux:icon name="check" class="h-4 w-4 text-blue-500 mt-0.5" />
                        <flux:text>Selección masiva de permisos para asignar a un rol</flux:text>
                    </div>
                    <div class="flex items-start gap-2">
                        <flux:icon name="check" class="h-4 w-4 text-blue-500 mt-0.5" />
                        <flux:text>Toggle por módulo para seleccionar todos los permisos relacionados</flux:text>
                    </div>
                    <div class="flex items-start gap-2">
                        <flux:icon name="check" class="h-4 w-4 text-blue-500 mt-0.5" />
                        <flux:text>Vista de roles asignados en cada permiso</flux:text>
                    </div>
                </div>
            </flux:card>
        </div>
    </section>

    <!-- Bulk Actions -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Acciones Masivas
        </flux:heading>

        <flux:text>
            El módulo de permisos permite realizar acciones masivas para facilitar la administración.
        </flux:text>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <flux:card class="p-4 border-l-4 border-l-blue-500">
                <flux:heading size="md" class="text-blue-700 dark:text-blue-300 mb-2">
                    Selección de Permisos
                </flux:heading>
                <ul class="space-y-2 text-sm">
                    <li>• Checkbox individual por permiso</li>
                    <li>• Seleccionar/Deseleccionar todo el módulo</li>
                    <li>• Seleccionar todos los permisos visibles</li>
                    <li>• Contador de permisos seleccionados</li>
                </ul>
            </flux:card>

            <flux:card class="p-4 border-l-4 border-l-green-500">
                <flux:heading size="md" class="text-green-700 dark:text-green-300 mb-2">
                    Asignación Masiva a Rol
                </flux:heading>
                <ul class="space-y-2 text-sm">
                    <li>• Seleccione múltiples permisos</li>
                    <li>• Elija el rol destino</li>
                    <li>• Todos los permisos se asignan al rol</li>
                    <li>• Confirmación de la acción</li>
                </ul>
            </flux:card>
        </div>
    </section>

    <!-- Interface Features -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Características de la Interfaz
        </flux:heading>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <flux:card class="p-4">
                <flux:heading size="md" class="mb-3 flex items-center gap-2">
                    <flux:icon name="magnifying-glass" class="h-5 w-5 text-blue-600" />
                    Búsqueda
                </flux:heading>
                <flux:text class="text-sm">
                    Busque permisos por nombre, nombre para mostrar o descripción en tiempo real.
                </flux:text>
            </flux:card>

            <flux:card class="p-4">
                <flux:heading size="md" class="mb-3 flex items-center gap-2">
                    <flux:icon name="funnel" class="h-5 w-5 text-green-600" />
                    Filtros
                </flux:heading>
                <flux:text class="text-sm">
                    Filtre por módulo para ver solo los permisos de un área específica del sistema.
                </flux:text>
            </flux:card>

            <flux:card class="p-4">
                <flux:heading size="md" class="mb-3 flex items-center gap-2">
                    <flux:icon name="arrows-up-down" class="h-5 w-5 text-purple-600" />
                    Ordenamiento
                </flux:heading>
                <flux:text class="text-sm">
                    Ordene por nombre, fecha de creación o cantidad de roles asignados.
                </flux:text>
            </flux:card>
        </div>
    </section>

    <!-- Best Practices -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Mejores Prácticas
        </flux:heading>

        <div class="space-y-3">
            <div class="flex items-start gap-3 p-3 bg-green-50 dark:bg-green-950 rounded-lg">
                <flux:icon name="shield-check" class="h-5 w-5 text-green-600 mt-0.5" />
                <div>
                    <flux:text class="font-medium">Principio del Menor Privilegio</flux:text>
                    <flux:text class="text-sm text-zinc-600 dark:text-zinc-400 block">
                        Asigne solo los permisos mínimos necesarios para que cada rol pueda cumplir sus funciones
                    </flux:text>
                </div>
            </div>

            <div class="flex items-start gap-3 p-3 bg-blue-50 dark:bg-blue-950 rounded-lg">
                <flux:icon name="document-text" class="h-5 w-5 text-blue-600 mt-0.5" />
                <div>
                    <flux:text class="font-medium">Documentación Clara</flux:text>
                    <flux:text class="text-sm text-zinc-600 dark:text-zinc-400 block">
                        Use nombres descriptivos y agregue descripciones detalladas a cada permiso
                    </flux:text>
                </div>
            </div>

            <div class="flex items-start gap-3 p-3 bg-purple-50 dark:bg-purple-950 rounded-lg">
                <flux:icon name="shield-exclamation" class="h-5 w-5 text-purple-600 mt-0.5" />
                <div>
                    <flux:text class="font-medium">Cuidado con delete y manage</flux:text>
                    <flux:text class="text-sm text-zinc-600 dark:text-zinc-400 block">
                        Los permisos de eliminación y gestión son sensibles, otórguelos solo a roles de confianza
                    </flux:text>
                </div>
            </div>

            <div class="flex items-start gap-3 p-3 bg-orange-50 dark:bg-orange-950 rounded-lg">
                <flux:icon name="clock" class="h-5 w-5 text-orange-600 mt-0.5" />
                <div>
                    <flux:text class="font-medium">Revisión Periódica</flux:text>
                    <flux:text class="text-sm text-zinc-600 dark:text-zinc-400 block">
                        Revise regularmente los permisos asignados, especialmente después de cambios organizacionales
                    </flux:text>
                </div>
            </div>
        </div>
    </section>

    <!-- Access Route -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Acceso al Módulo
        </flux:heading>

        <flux:card class="p-4">
            <div class="flex items-center gap-4">
                <div class="p-2 bg-amber-100 dark:bg-amber-900 rounded-lg">
                    <flux:icon name="link" class="h-6 w-6 text-amber-600 dark:text-amber-400" />
                </div>
                <div>
                    <flux:text class="font-medium">Ruta de Acceso</flux:text>
                    <flux:text class="text-sm font-mono text-zinc-600 dark:text-zinc-400">
                        /admin/permissions
                    </flux:text>
                </div>
            </div>
        </flux:card>

        <div class="bg-red-50 dark:bg-red-950 p-4 rounded-lg border border-red-200 dark:border-red-800">
            <flux:heading size="sm" class="text-red-800 dark:text-red-200 mb-2 flex items-center gap-2">
                <flux:icon name="exclamation-triangle" class="h-5 w-5" />
                Acceso Restringido
            </flux:heading>
            <flux:text class="text-sm text-red-700 dark:text-red-300">
                La gestión de permisos es una función administrativa sensible. Solo usuarios con los permisos
                <strong>permissions.view</strong>, <strong>permissions.create</strong>, <strong>permissions.edit</strong>
                pueden acceder a estas funciones.
            </flux:text>
        </div>
    </section>
</div>
