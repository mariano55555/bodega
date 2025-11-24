<div class="space-y-8">
    <!-- Module Header -->
    <div class="border-b border-zinc-200 dark:border-zinc-700 pb-6">
        <div class="flex items-center gap-4 mb-4">
            <div class="p-3 bg-green-100 dark:bg-green-900 rounded-lg">
                <flux:icon name="shield-check" class="h-8 w-8 text-green-600 dark:text-green-400" />
            </div>
            <div>
                <flux:heading size="xl" class="text-zinc-900 dark:text-zinc-100">
                    Gestión de Roles
                </flux:heading>
                <flux:text class="text-zinc-600 dark:text-zinc-400">
                    Configuración de roles y asignación de permisos del sistema
                </flux:text>
            </div>
        </div>
    </div>

    <!-- What are Roles -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            ¿Qué son los Roles?
        </flux:heading>

        <flux:text>
            Los roles son conjuntos de permisos que definen qué acciones puede realizar un usuario en el sistema.
            En lugar de asignar permisos individualmente a cada usuario, se crean roles con permisos específicos
            y luego se asignan esos roles a los usuarios.
        </flux:text>

        <div class="bg-blue-50 dark:bg-blue-950 p-4 rounded-lg border border-blue-200 dark:border-blue-800">
            <flux:heading size="sm" class="text-blue-800 dark:text-blue-200 mb-2 flex items-center gap-2">
                <flux:icon name="light-bulb" class="h-5 w-5" />
                Beneficios del Sistema de Roles
            </flux:heading>
            <ul class="text-sm text-blue-700 dark:text-blue-300 space-y-1">
                <li>• Administración centralizada de permisos</li>
                <li>• Facilita cambios masivos de acceso</li>
                <li>• Auditoría más clara de privilegios</li>
                <li>• Consistencia en la asignación de permisos</li>
            </ul>
        </div>
    </section>

    <!-- Predefined Roles -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Roles Predefinidos del Sistema
        </flux:heading>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <flux:card class="p-4 border-l-4 border-l-red-500">
                <flux:heading size="md" class="text-red-700 dark:text-red-300 mb-2 flex items-center gap-2">
                    <flux:icon name="star" class="h-5 w-5" />
                    Super Administrador
                </flux:heading>
                <flux:text class="text-sm mb-2">
                    Acceso total al sistema, puede gestionar todas las empresas, usuarios y configuraciones.
                </flux:text>
                <flux:badge size="sm" color="red">Acceso Total</flux:badge>
            </flux:card>

            <flux:card class="p-4 border-l-4 border-l-purple-500">
                <flux:heading size="md" class="text-purple-700 dark:text-purple-300 mb-2 flex items-center gap-2">
                    <flux:icon name="building-office" class="h-5 w-5" />
                    Administrador de Empresa
                </flux:heading>
                <flux:text class="text-sm mb-2">
                    Gestión completa de su empresa: usuarios, sucursales, bodegas y configuraciones.
                </flux:text>
                <flux:badge size="sm" color="purple">Empresa Completa</flux:badge>
            </flux:card>

            <flux:card class="p-4 border-l-4 border-l-blue-500">
                <flux:heading size="md" class="text-blue-700 dark:text-blue-300 mb-2 flex items-center gap-2">
                    <flux:icon name="building-storefront" class="h-5 w-5" />
                    Gerente de Sucursal
                </flux:heading>
                <flux:text class="text-sm mb-2">
                    Administra una sucursal específica: usuarios, bodegas y operaciones de la sucursal.
                </flux:text>
                <flux:badge size="sm" color="blue">Sucursal</flux:badge>
            </flux:card>

            <flux:card class="p-4 border-l-4 border-l-orange-500">
                <flux:heading size="md" class="text-orange-700 dark:text-orange-300 mb-2 flex items-center gap-2">
                    <flux:icon name="building-office-2" class="h-5 w-5" />
                    Gerente de Bodega
                </flux:heading>
                <flux:text class="text-sm mb-2">
                    Control total de una bodega: inventario, movimientos, ajustes y reportes.
                </flux:text>
                <flux:badge size="sm" color="orange">Bodega</flux:badge>
            </flux:card>

            <flux:card class="p-4 border-l-4 border-l-green-500">
                <flux:heading size="md" class="text-green-700 dark:text-green-300 mb-2 flex items-center gap-2">
                    <flux:icon name="user" class="h-5 w-5" />
                    Operador de Bodega
                </flux:heading>
                <flux:text class="text-sm mb-2">
                    Operaciones diarias: registro de entradas, salidas y consultas de inventario.
                </flux:text>
                <flux:badge size="sm" color="green">Operaciones</flux:badge>
            </flux:card>

            <flux:card class="p-4 border-l-4 border-l-gray-500">
                <flux:heading size="md" class="text-gray-700 dark:text-gray-300 mb-2 flex items-center gap-2">
                    <flux:icon name="eye" class="h-5 w-5" />
                    Solo Lectura
                </flux:heading>
                <flux:text class="text-sm mb-2">
                    Acceso de consulta únicamente: puede ver información pero no modificar.
                </flux:text>
                <flux:badge size="sm" color="zinc">Consulta</flux:badge>
            </flux:card>
        </div>
    </section>

    <!-- Role Management -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Gestión de Roles
        </flux:heading>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <flux:card class="p-4 border-l-4 border-l-emerald-500">
                <flux:heading size="md" class="text-emerald-700 dark:text-emerald-300 mb-2 flex items-center gap-2">
                    <flux:icon name="plus" class="h-5 w-5" />
                    Crear Roles
                </flux:heading>
                <ul class="space-y-2 text-sm">
                    <li>• Nombre técnico único (ej: warehouse-manager)</li>
                    <li>• Nombre para mostrar (ej: Gerente de Bodega)</li>
                    <li>• Descripción detallada del rol</li>
                    <li>• Selección de permisos por módulo</li>
                </ul>
            </flux:card>

            <flux:card class="p-4 border-l-4 border-l-blue-500">
                <flux:heading size="md" class="text-blue-700 dark:text-blue-300 mb-2 flex items-center gap-2">
                    <flux:icon name="pencil" class="h-5 w-5" />
                    Editar Roles
                </flux:heading>
                <ul class="space-y-2 text-sm">
                    <li>• Modificar información básica</li>
                    <li>• Agregar o quitar permisos</li>
                    <li>• Ver usuarios asignados</li>
                    <li>• Duplicar roles existentes</li>
                </ul>
            </flux:card>

            <flux:card class="p-4 border-l-4 border-l-red-500">
                <flux:heading size="md" class="text-red-700 dark:text-red-300 mb-2 flex items-center gap-2">
                    <flux:icon name="trash" class="h-5 w-5" />
                    Eliminar Roles
                </flux:heading>
                <ul class="space-y-2 text-sm">
                    <li>• Solo roles sin usuarios asignados</li>
                    <li>• Confirmación obligatoria</li>
                    <li>• Verificación de dependencias</li>
                    <li>• Acción irreversible</li>
                </ul>
            </flux:card>
        </div>
    </section>

    <!-- Role Interface -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Interfaz de Roles
        </flux:heading>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <flux:card class="p-4">
                <flux:heading size="md" class="mb-3 flex items-center gap-2">
                    <flux:icon name="squares-2x2" class="h-5 w-5 text-blue-600" />
                    Vista de Tarjetas
                </flux:heading>
                <div class="space-y-3 text-sm">
                    <div class="flex items-center gap-2">
                        <flux:icon name="check" class="h-4 w-4 text-green-500" />
                        <flux:text>Visualización en cuadrícula de roles</flux:text>
                    </div>
                    <div class="flex items-center gap-2">
                        <flux:icon name="check" class="h-4 w-4 text-green-500" />
                        <flux:text>Contador de usuarios asignados</flux:text>
                    </div>
                    <div class="flex items-center gap-2">
                        <flux:icon name="check" class="h-4 w-4 text-green-500" />
                        <flux:text>Contador de permisos incluidos</flux:text>
                    </div>
                    <div class="flex items-center gap-2">
                        <flux:icon name="check" class="h-4 w-4 text-green-500" />
                        <flux:text>Acciones rápidas desde cada tarjeta</flux:text>
                    </div>
                </div>
            </flux:card>

            <flux:card class="p-4">
                <flux:heading size="md" class="mb-3 flex items-center gap-2">
                    <flux:icon name="funnel" class="h-5 w-5 text-green-600" />
                    Filtros y Búsqueda
                </flux:heading>
                <div class="space-y-3 text-sm">
                    <div class="flex items-center gap-2">
                        <flux:icon name="check" class="h-4 w-4 text-green-500" />
                        <flux:text>Búsqueda por nombre o descripción</flux:text>
                    </div>
                    <div class="flex items-center gap-2">
                        <flux:icon name="check" class="h-4 w-4 text-green-500" />
                        <flux:text>Ordenar por nombre, fecha o cantidad</flux:text>
                    </div>
                    <div class="flex items-center gap-2">
                        <flux:icon name="check" class="h-4 w-4 text-green-500" />
                        <flux:text>12 roles por página con paginación</flux:text>
                    </div>
                    <div class="flex items-center gap-2">
                        <flux:icon name="check" class="h-4 w-4 text-green-500" />
                        <flux:text>Actualización en tiempo real</flux:text>
                    </div>
                </div>
            </flux:card>
        </div>
    </section>

    <!-- Permission Assignment -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Asignación de Permisos a Roles
        </flux:heading>

        <flux:text>
            Al crear o editar un rol, los permisos se organizan por módulo para facilitar su selección.
            Cada módulo agrupa permisos relacionados (ver, crear, editar, eliminar, etc.).
        </flux:text>

        <div class="bg-gray-50 dark:bg-gray-900 p-4 rounded-lg">
            <flux:heading size="md" class="mb-3">Módulos de Permisos Disponibles</flux:heading>
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3 text-sm">
                <div class="flex items-center gap-2">
                    <flux:icon name="users" class="h-4 w-4 text-purple-500" />
                    <flux:text>Usuarios</flux:text>
                </div>
                <div class="flex items-center gap-2">
                    <flux:icon name="building-office" class="h-4 w-4 text-indigo-500" />
                    <flux:text>Empresas</flux:text>
                </div>
                <div class="flex items-center gap-2">
                    <flux:icon name="map-pin" class="h-4 w-4 text-cyan-500" />
                    <flux:text>Sucursales</flux:text>
                </div>
                <div class="flex items-center gap-2">
                    <flux:icon name="building-storefront" class="h-4 w-4 text-orange-500" />
                    <flux:text>Bodegas</flux:text>
                </div>
                <div class="flex items-center gap-2">
                    <flux:icon name="cube" class="h-4 w-4 text-emerald-500" />
                    <flux:text>Productos</flux:text>
                </div>
                <div class="flex items-center gap-2">
                    <flux:icon name="arrows-right-left" class="h-4 w-4 text-blue-500" />
                    <flux:text>Inventario</flux:text>
                </div>
                <div class="flex items-center gap-2">
                    <flux:icon name="shopping-cart" class="h-4 w-4 text-green-500" />
                    <flux:text>Compras</flux:text>
                </div>
                <div class="flex items-center gap-2">
                    <flux:icon name="chart-bar" class="h-4 w-4 text-teal-500" />
                    <flux:text>Reportes</flux:text>
                </div>
            </div>
        </div>

        <div class="bg-indigo-50 dark:bg-indigo-950 p-4 rounded-lg border border-indigo-200 dark:border-indigo-800">
            <flux:heading size="sm" class="text-indigo-800 dark:text-indigo-200 mb-2">
                Gestionar Permisos de un Rol
            </flux:heading>
            <flux:text class="text-sm text-indigo-700 dark:text-indigo-300">
                Use el botón "Gestionar permisos" en el menú de cada rol para modificar rápidamente
                los permisos sin editar toda la información del rol. Los permisos se agrupan por módulo
                y puede usar "Toggle all" para seleccionar o deseleccionar todos los permisos de un módulo.
            </flux:text>
        </div>
    </section>

    <!-- Duplicate Roles -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Duplicar Roles
        </flux:heading>

        <flux:text>
            La función de duplicar roles es útil cuando necesita crear un rol similar a uno existente.
            Al duplicar, se crea una copia con todos los permisos del rol original.
        </flux:text>

        <div class="flex items-start gap-3 p-3 bg-amber-50 dark:bg-amber-950 rounded-lg">
            <flux:icon name="information-circle" class="h-5 w-5 text-amber-600 mt-0.5" />
            <div>
                <flux:text class="font-medium">Nombre del Rol Duplicado</flux:text>
                <flux:text class="text-sm text-zinc-600 dark:text-zinc-400 block">
                    El rol duplicado se crea con el sufijo "_copia" en el nombre técnico y "(Copia)" en el nombre
                    para mostrar. Recuerde cambiar estos nombres antes de usarlo.
                </flux:text>
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
                <div class="p-2 bg-green-100 dark:bg-green-900 rounded-lg">
                    <flux:icon name="link" class="h-6 w-6 text-green-600 dark:text-green-400" />
                </div>
                <div>
                    <flux:text class="font-medium">Ruta de Acceso</flux:text>
                    <flux:text class="text-sm font-mono text-zinc-600 dark:text-zinc-400">
                        /admin/roles
                    </flux:text>
                </div>
            </div>
        </flux:card>

        <div class="bg-yellow-50 dark:bg-yellow-950 p-4 rounded-lg border border-yellow-200 dark:border-yellow-800">
            <flux:heading size="sm" class="text-yellow-800 dark:text-yellow-200 mb-2 flex items-center gap-2">
                <flux:icon name="exclamation-triangle" class="h-5 w-5" />
                Permisos Requeridos
            </flux:heading>
            <flux:text class="text-sm text-yellow-700 dark:text-yellow-300">
                Para acceder a la gestión de roles necesita tener asignados los permisos correspondientes:
                <strong>roles.view</strong>, <strong>roles.create</strong>, <strong>roles.edit</strong> o <strong>roles.delete</strong>
                según las acciones que necesite realizar.
            </flux:text>
        </div>
    </section>
</div>
