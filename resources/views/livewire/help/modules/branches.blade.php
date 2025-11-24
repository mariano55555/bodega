<div class="space-y-8">
    <!-- Module Header -->
    <div class="border-b border-zinc-200 dark:border-zinc-700 pb-6">
        <div class="flex items-center gap-4 mb-4">
            <div class="p-3 bg-cyan-100 dark:bg-cyan-900 rounded-lg">
                <flux:icon name="map-pin" class="h-8 w-8 text-cyan-600 dark:text-cyan-400" />
            </div>
            <div>
                <flux:heading size="xl" class="text-zinc-900 dark:text-zinc-100">
                    Gestión de Sucursales
                </flux:heading>
                <flux:text class="text-zinc-600 dark:text-zinc-400">
                    Manejo de ubicaciones y puntos de operación de la empresa
                </flux:text>
            </div>
        </div>
    </div>

    <!-- Branch Concept -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            🏪 Concepto de Sucursal
        </flux:heading>

        <div class="space-y-4">
            <flux:text>
                Las <strong>sucursales</strong> representan ubicaciones físicas donde opera la empresa.
                Cada sucursal pertenece a una empresa y puede contener múltiples bodegas de almacenamiento.
            </flux:text>

            <div class="bg-cyan-50 dark:bg-cyan-950 p-4 rounded-lg border border-cyan-200 dark:border-cyan-800">
                <flux:heading size="md" class="text-cyan-800 dark:text-cyan-200 mb-3">
                    Ejemplos de Sucursales
                </flux:heading>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <div>
                        <flux:text class="font-medium text-cyan-700 dark:text-cyan-300">Comercio Retail:</flux:text>
                        <ul class="mt-1 space-y-1 text-cyan-600 dark:text-cyan-400">
                            <li>• Tienda Centro Comercial Norte</li>
                            <li>• Tienda Zona Industrial</li>
                            <li>• Tienda Centro Histórico</li>
                        </ul>
                    </div>
                    <div>
                        <flux:text class="font-medium text-cyan-700 dark:text-cyan-300">Distribución:</flux:text>
                        <ul class="mt-1 space-y-1 text-cyan-600 dark:text-cyan-400">
                            <li>• Centro de Distribución Regional</li>
                            <li>• Punto de Venta Mayorista</li>
                            <li>• Oficina Administrativa</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Branch Management -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            🛠️ Gestión de Sucursales
        </flux:heading>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <flux:card class="p-4">
                <flux:heading size="md" class="mb-3 flex items-center gap-2">
                    <flux:icon name="plus-circle" class="h-5 w-5 text-green-600" />
                    Crear Sucursal
                </flux:heading>
                <div class="space-y-3 text-sm">
                    <div class="flex items-start gap-2">
                        <flux:icon name="tag" class="h-4 w-4 text-blue-500 mt-0.5" />
                        <div>
                            <flux:text class="font-medium">Identificación</flux:text>
                            <flux:text class="text-xs text-zinc-500">Nombre, código único, tipo</flux:text>
                        </div>
                    </div>
                    <div class="flex items-start gap-2">
                        <flux:icon name="map-pin" class="h-4 w-4 text-green-500 mt-0.5" />
                        <div>
                            <flux:text class="font-medium">Ubicación</flux:text>
                            <flux:text class="text-xs text-zinc-500">Dirección, coordenadas, zona</flux:text>
                        </div>
                    </div>
                    <div class="flex items-start gap-2">
                        <flux:icon name="building-office" class="h-4 w-4 text-purple-500 mt-0.5" />
                        <div>
                            <flux:text class="font-medium">Empresa Padre</flux:text>
                            <flux:text class="text-xs text-zinc-500">Asignación a empresa específica</flux:text>
                        </div>
                    </div>
                </div>
            </flux:card>

            <flux:card class="p-4">
                <flux:heading size="md" class="mb-3 flex items-center gap-2">
                    <flux:icon name="cog" class="h-5 w-5 text-blue-600" />
                    Configuración Avanzada
                </flux:heading>
                <div class="space-y-3 text-sm">
                    <div class="flex items-start gap-2">
                        <flux:icon name="users" class="h-4 w-4 text-orange-500 mt-0.5" />
                        <div>
                            <flux:text class="font-medium">Personal Asignado</flux:text>
                            <flux:text class="text-xs text-zinc-500">Usuarios específicos por sucursal</flux:text>
                        </div>
                    </div>
                    <div class="flex items-start gap-2">
                        <flux:icon name="clock" class="h-4 w-4 text-red-500 mt-0.5" />
                        <div>
                            <flux:text class="font-medium">Horarios Operativos</flux:text>
                            <flux:text class="text-xs text-zinc-500">Independientes de la empresa</flux:text>
                        </div>
                    </div>
                    <div class="flex items-start gap-2">
                        <flux:icon name="shield-check" class="h-4 w-4 text-gray-500 mt-0.5" />
                        <div>
                            <flux:text class="font-medium">Estado y Permisos</flux:text>
                            <flux:text class="text-xs text-zinc-500">Control de acceso granular</flux:text>
                        </div>
                    </div>
                </div>
            </flux:card>
        </div>
    </section>

    <!-- Branch Features -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            ✨ Funcionalidades Disponibles
        </flux:heading>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <flux:card class="p-4 border-l-4 border-l-green-500">
                <flux:heading size="md" class="text-green-700 dark:text-green-300 mb-2">
                    📋 Listado y Búsqueda
                </flux:heading>
                <ul class="space-y-1 text-sm">
                    <li>• Vista general de todas las sucursales</li>
                    <li>• Filtrado por empresa</li>
                    <li>• Búsqueda por nombre o código</li>
                    <li>• Ordenamiento múltiple</li>
                    <li>• Paginación eficiente</li>
                </ul>
            </flux:card>

            <flux:card class="p-4 border-l-4 border-l-blue-500">
                <flux:heading size="md" class="text-blue-700 dark:text-blue-300 mb-2">
                    ✏️ Edición Completa
                </flux:heading>
                <ul class="space-y-1 text-sm">
                    <li>• Modificar información básica</li>
                    <li>• Actualizar ubicación</li>
                    <li>• Cambiar estado (activa/inactiva)</li>
                    <li>• Reasignar empresa padre</li>
                    <li>• Gestionar personal asignado</li>
                </ul>
            </flux:card>

            <flux:card class="p-4 border-l-4 border-l-purple-500">
                <flux:heading size="md" class="text-purple-700 dark:text-purple-300 mb-2">
                    📊 Información y Stats
                </flux:heading>
                <ul class="space-y-1 text-sm">
                    <li>• Número de bodegas asociadas</li>
                    <li>• Personal activo por sucursal</li>
                    <li>• Resumen de inventario</li>
                    <li>• Actividad reciente</li>
                    <li>• KPIs operacionales</li>
                </ul>
            </flux:card>
        </div>
    </section>

    <!-- Branch-Warehouse Relationship -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            🔗 Relación Sucursal-Bodega
        </flux:heading>

        <div class="space-y-4">
            <flux:text>
                Cada sucursal puede tener múltiples bodegas, permitiendo organizar el inventario
                según diferentes criterios operacionales.
            </flux:text>

            <div class="bg-gradient-to-r from-cyan-50 to-blue-50 dark:from-cyan-950 dark:to-blue-950 p-6 rounded-lg">
                <flux:heading size="md" class="mb-4 text-center">Estructura Típica de Sucursal</flux:heading>
                <div class="space-y-4">
                    <!-- Sucursal Header -->
                    <div class="bg-white dark:bg-zinc-800 p-4 rounded-lg shadow-sm border-2 border-cyan-200 dark:border-cyan-800 text-center">
                        <flux:icon name="map-pin" class="h-8 w-8 text-cyan-600 mx-auto mb-2" />
                        <flux:text class="font-bold text-cyan-800 dark:text-cyan-200">SUCURSAL CENTRO</flux:text>
                        <flux:text class="text-xs text-zinc-500">Av. Principal 123, Ciudad</flux:text>
                    </div>

                    <!-- Bodegas -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        <div class="bg-white dark:bg-zinc-800 p-3 rounded-lg shadow-sm border border-orange-200 dark:border-orange-800 text-center">
                            <flux:icon name="building-storefront" class="h-6 w-6 text-orange-600 mx-auto mb-1" />
                            <flux:text class="text-sm font-medium text-orange-800 dark:text-orange-200">Bodega Principal</flux:text>
                            <flux:text class="text-xs text-zinc-500">Productos generales</flux:text>
                        </div>
                        <div class="bg-white dark:bg-zinc-800 p-3 rounded-lg shadow-sm border border-orange-200 dark:border-orange-800 text-center">
                            <flux:icon name="building-storefront" class="h-6 w-6 text-orange-600 mx-auto mb-1" />
                            <flux:text class="text-sm font-medium text-orange-800 dark:text-orange-200">Bodega Fría</flux:text>
                            <flux:text class="text-xs text-zinc-500">Productos refrigerados</flux:text>
                        </div>
                        <div class="bg-white dark:bg-zinc-800 p-3 rounded-lg shadow-sm border border-orange-200 dark:border-orange-800 text-center">
                            <flux:icon name="building-storefront" class="h-6 w-6 text-orange-600 mx-auto mb-1" />
                            <flux:text class="text-sm font-medium text-orange-800 dark:text-orange-200">Bodega Especial</flux:text>
                            <flux:text class="text-xs text-zinc-500">Productos controlados</flux:text>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Filtering and Access Control -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            🔒 Control de Acceso por Sucursal
        </flux:heading>

        <div class="space-y-4">
            <flux:text>
                El sistema permite controlar el acceso de usuarios a sucursales específicas,
                garantizando que solo puedan operar en las ubicaciones autorizadas.
            </flux:text>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <flux:card class="p-4">
                    <flux:heading size="md" class="mb-3 flex items-center gap-2">
                        <flux:icon name="user-group" class="h-5 w-5 text-green-600" />
                        Asignación de Personal
                    </flux:heading>
                    <div class="space-y-2 text-sm">
                        <div class="flex items-center gap-2">
                            <flux:icon name="check" class="h-4 w-4 text-green-500" />
                            <flux:text>Usuarios por sucursal específica</flux:text>
                        </div>
                        <div class="flex items-center gap-2">
                            <flux:icon name="check" class="h-4 w-4 text-green-500" />
                            <flux:text>Permisos contextuales</flux:text>
                        </div>
                        <div class="flex items-center gap-2">
                            <flux:icon name="check" class="h-4 w-4 text-green-500" />
                            <flux:text>Filtrado automático de datos</flux:text>
                        </div>
                        <div class="flex items-center gap-2">
                            <flux:icon name="check" class="h-4 w-4 text-green-500" />
                            <flux:text>Reportes segmentados</flux:text>
                        </div>
                    </div>
                </flux:card>

                <flux:card class="p-4">
                    <flux:heading size="md" class="mb-3 flex items-center gap-2">
                        <flux:icon name="funnel" class="h-5 w-5 text-blue-600" />
                        Filtrado Inteligente
                    </flux:heading>
                    <div class="space-y-2 text-sm">
                        <div class="flex items-center gap-2">
                            <flux:icon name="eye" class="h-4 w-4 text-blue-500" />
                            <flux:text>Solo sucursales autorizadas</flux:text>
                        </div>
                        <div class="flex items-center gap-2">
                            <flux:icon name="cube" class="h-4 w-4 text-blue-500" />
                            <flux:text>Inventario por ubicación</flux:text>
                        </div>
                        <div class="flex items-center gap-2">
                            <flux:icon name="arrow-path" class="h-4 w-4 text-blue-500" />
                            <flux:text>Movimientos locales únicamente</flux:text>
                        </div>
                        <div class="flex items-center gap-2">
                            <flux:icon name="chart-bar" class="h-4 w-4 text-blue-500" />
                            <flux:text>Métricas por sucursal</flux:text>
                        </div>
                    </div>
                </flux:card>
            </div>
        </div>
    </section>

    <!-- Status Management -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            🚦 Gestión de Estado
        </flux:heading>

        <div class="space-y-4">
            <flux:text>
                Las sucursales pueden estar activas o inactivas, lo que afecta su operación
                y visibilidad en el sistema.
            </flux:text>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <flux:card class="p-4 bg-green-50 dark:bg-green-950 border border-green-200 dark:border-green-800">
                    <flux:heading size="md" class="text-green-800 dark:text-green-200 mb-2 flex items-center gap-2">
                        <flux:icon name="check-circle" class="h-5 w-5" />
                        Sucursal Activa
                    </flux:heading>
                    <ul class="space-y-1 text-sm text-green-700 dark:text-green-300">
                        <li>• Operaciones normales habilitadas</li>
                        <li>• Visible para todos los usuarios</li>
                        <li>• Puede recibir y enviar inventario</li>
                        <li>• Reportes y métricas actualizados</li>
                        <li>• Personal puede acceder y operar</li>
                    </ul>
                </flux:card>

                <flux:card class="p-4 bg-red-50 dark:bg-red-950 border border-red-200 dark:border-red-800">
                    <flux:heading size="md" class="text-red-800 dark:text-red-200 mb-2 flex items-center gap-2">
                        <flux:icon name="x-circle" class="h-5 w-5" />
                        Sucursal Inactiva
                    </flux:heading>
                    <ul class="space-y-1 text-sm text-red-700 dark:text-red-300">
                        <li>• Operaciones suspendidas temporalmente</li>
                        <li>• Visible solo para administradores</li>
                        <li>• Movimientos bloqueados</li>
                        <li>• Datos históricos preservados</li>
                        <li>• Acceso restringido del personal</li>
                    </ul>
                </flux:card>
            </div>
        </div>
    </section>

    <!-- Coming Soon -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            🚧 Funcionalidades en Desarrollo
        </flux:heading>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <flux:card class="p-4 bg-gradient-to-r from-purple-50 to-pink-50 dark:from-purple-950 dark:to-pink-950 border border-purple-200 dark:border-purple-800">
                <flux:heading size="md" class="text-purple-800 dark:text-purple-200 mb-2">
                    🗺️ Mapas Interactivos
                </flux:heading>
                <flux:text class="text-sm text-purple-700 dark:text-purple-300">
                    Visualización geográfica de sucursales con rutas optimizadas y análisis de cobertura territorial.
                </flux:text>
            </flux:card>

            <flux:card class="p-4 bg-gradient-to-r from-blue-50 to-cyan-50 dark:from-blue-950 dark:to-cyan-950 border border-blue-200 dark:border-blue-800">
                <flux:heading size="md" class="text-blue-800 dark:text-blue-200 mb-2">
                    📱 App Móvil por Sucursal
                </flux:heading>
                <flux:text class="text-sm text-blue-700 dark:text-blue-300">
                    Aplicación móvil específica para operaciones de campo y gestión local de inventario.
                </flux:text>
            </flux:card>

            <flux:card class="p-4 bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-950 dark:to-emerald-950 border border-green-200 dark:border-green-800">
                <flux:heading size="md" class="text-green-800 dark:text-green-200 mb-2">
                    ⚡ Sincronización Offline
                </flux:heading>
                <flux:text class="text-sm text-green-700 dark:text-green-300">
                    Capacidad de operar sin conexión a internet con sincronización automática posterior.
                </flux:text>
            </flux:card>

            <flux:card class="p-4 bg-gradient-to-r from-orange-50 to-red-50 dark:from-orange-950 dark:to-red-950 border border-orange-200 dark:border-orange-800">
                <flux:heading size="md" class="text-orange-800 dark:text-orange-200 mb-2">
                    🏪 Configuración de Layout
                </flux:heading>
                <flux:text class="text-sm text-orange-700 dark:text-orange-300">
                    Diseñador visual para definir la distribución física de productos dentro de la sucursal.
                </flux:text>
            </flux:card>
        </div>
    </section>
</div>
