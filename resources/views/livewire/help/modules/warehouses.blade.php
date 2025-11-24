<div class="space-y-8">
    <!-- Module Header -->
    <div class="border-b border-zinc-200 dark:border-zinc-700 pb-6">
        <div class="flex items-center gap-4 mb-4">
            <div class="p-3 bg-green-100 dark:bg-green-900 rounded-lg">
                <flux:icon name="building-office" class="h-8 w-8 text-green-600 dark:text-green-400" />
            </div>
            <div>
                <flux:heading size="xl" class="text-zinc-900 dark:text-zinc-100">
                    Gestión de Almacenes
                </flux:heading>
                <flux:text class="text-zinc-600 dark:text-zinc-400">
                    Administración de bodegas, capacidad y ubicaciones de almacenamiento
                </flux:text>
            </div>
        </div>
    </div>

    <!-- Dashboard Overview -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Panel de Control
        </flux:heading>

        <flux:text>
            El dashboard de almacenes proporciona una visión general de toda la infraestructura de almacenamiento
            de la empresa, mostrando métricas clave, capacidad y actividad reciente.
        </flux:text>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <flux:card class="p-4 bg-indigo-50 dark:bg-indigo-950 border border-indigo-200 dark:border-indigo-800">
                <div class="flex items-center gap-3">
                    <flux:icon name="building-office-2" class="h-6 w-6 text-indigo-500" />
                    <div>
                        <flux:text class="text-xs text-indigo-600 dark:text-indigo-400">Empresas</flux:text>
                        <flux:text class="font-bold text-indigo-800 dark:text-indigo-200">Total activas</flux:text>
                    </div>
                </div>
            </flux:card>

            <flux:card class="p-4 bg-blue-50 dark:bg-blue-950 border border-blue-200 dark:border-blue-800">
                <div class="flex items-center gap-3">
                    <flux:icon name="building-storefront" class="h-6 w-6 text-blue-500" />
                    <div>
                        <flux:text class="text-xs text-blue-600 dark:text-blue-400">Sucursales</flux:text>
                        <flux:text class="font-bold text-blue-800 dark:text-blue-200">Por empresa</flux:text>
                    </div>
                </div>
            </flux:card>

            <flux:card class="p-4 bg-green-50 dark:bg-green-950 border border-green-200 dark:border-green-800">
                <div class="flex items-center gap-3">
                    <flux:icon name="building-office" class="h-6 w-6 text-green-500" />
                    <div>
                        <flux:text class="text-xs text-green-600 dark:text-green-400">Almacenes</flux:text>
                        <flux:text class="font-bold text-green-800 dark:text-green-200">Total activos</flux:text>
                    </div>
                </div>
            </flux:card>

            <flux:card class="p-4 bg-purple-50 dark:bg-purple-950 border border-purple-200 dark:border-purple-800">
                <div class="flex items-center gap-3">
                    <flux:icon name="chart-pie" class="h-6 w-6 text-purple-500" />
                    <div>
                        <flux:text class="text-xs text-purple-600 dark:text-purple-400">Utilización</flux:text>
                        <flux:text class="font-bold text-purple-800 dark:text-purple-200">% Capacidad</flux:text>
                    </div>
                </div>
            </flux:card>
        </div>
    </section>

    <!-- Warehouse Hierarchy -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Estructura Jerárquica
        </flux:heading>

        <flux:text>
            Los almacenes se organizan en una estructura jerárquica que facilita la gestión y el control de acceso.
        </flux:text>

        <div class="bg-gradient-to-r from-green-50 to-blue-50 dark:from-green-950 dark:to-blue-950 p-6 rounded-lg">
            <div class="space-y-4">
                <!-- Empresa -->
                <div class="bg-white dark:bg-zinc-800 p-4 rounded-lg shadow-sm border-2 border-indigo-200 dark:border-indigo-800">
                    <div class="flex items-center gap-3">
                        <flux:icon name="building-office-2" class="h-6 w-6 text-indigo-600" />
                        <div>
                            <flux:text class="font-bold text-indigo-800 dark:text-indigo-200">Empresa</flux:text>
                            <flux:text class="text-xs text-zinc-500">Escuela Nacional de Agricultura (ENA)</flux:text>
                        </div>
                    </div>
                </div>

                <!-- Sucursales -->
                <div class="ml-8 grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div class="bg-white dark:bg-zinc-800 p-3 rounded-lg shadow-sm border border-blue-200 dark:border-blue-800">
                        <div class="flex items-center gap-2">
                            <flux:icon name="building-storefront" class="h-5 w-5 text-blue-600" />
                            <div>
                                <flux:text class="font-medium text-blue-800 dark:text-blue-200">Sucursal Central</flux:text>
                                <flux:text class="text-xs text-zinc-500">Ciudad Arce</flux:text>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white dark:bg-zinc-800 p-3 rounded-lg shadow-sm border border-blue-200 dark:border-blue-800">
                        <div class="flex items-center gap-2">
                            <flux:icon name="building-storefront" class="h-5 w-5 text-blue-600" />
                            <div>
                                <flux:text class="font-medium text-blue-800 dark:text-blue-200">Sucursal Agrícola</flux:text>
                                <flux:text class="text-xs text-zinc-500">Finca Experimental</flux:text>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Almacenes -->
                <div class="ml-16 grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div class="bg-white dark:bg-zinc-800 p-3 rounded-lg shadow-sm border border-green-200 dark:border-green-800">
                        <div class="flex items-center gap-2">
                            <flux:icon name="building-office" class="h-4 w-4 text-green-600" />
                            <div>
                                <flux:text class="text-sm font-medium text-green-800 dark:text-green-200">Bodega Principal</flux:text>
                                <flux:text class="text-xs text-zinc-500">Insumos generales</flux:text>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white dark:bg-zinc-800 p-3 rounded-lg shadow-sm border border-green-200 dark:border-green-800">
                        <div class="flex items-center gap-2">
                            <flux:icon name="building-office" class="h-4 w-4 text-green-600" />
                            <div>
                                <flux:text class="text-sm font-medium text-green-800 dark:text-green-200">Bodega Fría</flux:text>
                                <flux:text class="text-xs text-zinc-500">Productos refrigerados</flux:text>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white dark:bg-zinc-800 p-3 rounded-lg shadow-sm border border-green-200 dark:border-green-800">
                        <div class="flex items-center gap-2">
                            <flux:icon name="building-office" class="h-4 w-4 text-green-600" />
                            <div>
                                <flux:text class="text-sm font-medium text-green-800 dark:text-green-200">Bodega Agroquímicos</flux:text>
                                <flux:text class="text-xs text-zinc-500">Productos controlados</flux:text>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Company Management -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Gestión de Empresas
        </flux:heading>

        <flux:text>
            Las empresas son la entidad principal del sistema multi-tenant. Cada empresa puede tener
            múltiples sucursales, almacenes y usuarios asociados.
        </flux:text>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <flux:card class="p-4">
                <flux:heading size="md" class="mb-3 flex items-center gap-2">
                    <flux:icon name="building-office-2" class="h-5 w-5 text-indigo-600" />
                    Información de Empresa
                </flux:heading>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between py-1 border-b border-zinc-100 dark:border-zinc-800">
                        <span class="text-zinc-600 dark:text-zinc-400">Nombre</span>
                        <span class="font-medium">Requerido</span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-zinc-100 dark:border-zinc-800">
                        <span class="text-zinc-600 dark:text-zinc-400">NIT/RUC</span>
                        <span class="font-medium">Único, Opcional</span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-zinc-100 dark:border-zinc-800">
                        <span class="text-zinc-600 dark:text-zinc-400">Número de Registro</span>
                        <span class="font-medium">Único, Opcional</span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-zinc-100 dark:border-zinc-800">
                        <span class="text-zinc-600 dark:text-zinc-400">Email</span>
                        <span class="font-medium">Único, Opcional</span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-zinc-100 dark:border-zinc-800">
                        <span class="text-zinc-600 dark:text-zinc-400">Teléfono</span>
                        <span class="font-medium">Opcional</span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-zinc-100 dark:border-zinc-800">
                        <span class="text-zinc-600 dark:text-zinc-400">Sitio Web</span>
                        <span class="font-medium">Opcional</span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-zinc-100 dark:border-zinc-800">
                        <span class="text-zinc-600 dark:text-zinc-400">Persona de Contacto</span>
                        <span class="font-medium">Opcional</span>
                    </div>
                </div>
            </flux:card>

            <flux:card class="p-4">
                <flux:heading size="md" class="mb-3 flex items-center gap-2">
                    <flux:icon name="cog" class="h-5 w-5 text-orange-600" />
                    Configuración de Empresa
                </flux:heading>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between py-1 border-b border-zinc-100 dark:border-zinc-800">
                        <span class="text-zinc-600 dark:text-zinc-400">Dirección</span>
                        <span class="font-medium">Opcional</span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-zinc-100 dark:border-zinc-800">
                        <span class="text-zinc-600 dark:text-zinc-400">Ciudad/Estado</span>
                        <span class="font-medium">Opcional</span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-zinc-100 dark:border-zinc-800">
                        <span class="text-zinc-600 dark:text-zinc-400">País</span>
                        <span class="font-medium">Seleccionable</span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-zinc-100 dark:border-zinc-800">
                        <span class="text-zinc-600 dark:text-zinc-400">Moneda</span>
                        <span class="font-medium">COP, USD, EUR, etc.</span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-zinc-100 dark:border-zinc-800">
                        <span class="text-zinc-600 dark:text-zinc-400">Zona Horaria</span>
                        <span class="font-medium">America/Bogota, etc.</span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-zinc-100 dark:border-zinc-800">
                        <span class="text-zinc-600 dark:text-zinc-400">Estado</span>
                        <span class="font-medium">Activo/Inactivo</span>
                    </div>
                </div>
            </flux:card>
        </div>

        <flux:callout variant="info" icon="information-circle">
            <strong>Acciones disponibles:</strong> Desde el listado de empresas puede crear nuevas empresas,
            editar información existente, activar/desactivar empresas y eliminar empresas (con confirmación).
            El botón "Refrescar" actualiza la lista sin recargar la página.
        </flux:callout>

        <div class="bg-zinc-50 dark:bg-zinc-800 p-4 rounded-lg">
            <flux:heading size="sm" class="mb-3">Estadísticas de Empresa</flux:heading>
            <flux:text class="text-sm">
                Al editar una empresa, el sistema muestra estadísticas en tiempo real:
            </flux:text>
            <div class="grid grid-cols-3 gap-4 mt-3">
                <div class="text-center p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                    <flux:icon name="building-storefront" class="h-6 w-6 text-blue-500 mx-auto mb-1" />
                    <flux:text class="text-xs text-blue-600 dark:text-blue-400">Sucursales</flux:text>
                </div>
                <div class="text-center p-3 bg-green-50 dark:bg-green-900/20 rounded-lg">
                    <flux:icon name="building-office" class="h-6 w-6 text-green-500 mx-auto mb-1" />
                    <flux:text class="text-xs text-green-600 dark:text-green-400">Almacenes</flux:text>
                </div>
                <div class="text-center p-3 bg-purple-50 dark:bg-purple-900/20 rounded-lg">
                    <flux:icon name="users" class="h-6 w-6 text-purple-500 mx-auto mb-1" />
                    <flux:text class="text-xs text-purple-600 dark:text-purple-400">Usuarios</flux:text>
                </div>
            </div>
        </div>
    </section>

    <!-- Warehouse Types -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Tipos de Almacén
        </flux:heading>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <flux:card class="p-4 border-l-4 border-l-green-500">
                <flux:heading size="md" class="mb-3 flex items-center gap-2">
                    <flux:icon name="cube" class="h-5 w-5 text-green-600" />
                    Almacén General
                </flux:heading>
                <flux:text class="text-sm mb-3">
                    Almacén principal que puede contener múltiples tipos de productos y subdividirse
                    en almacenes fraccionados.
                </flux:text>
                <ul class="space-y-1 text-sm text-zinc-600 dark:text-zinc-400">
                    <li class="flex items-center gap-2">
                        <flux:icon name="check" class="h-4 w-4 text-green-500" />
                        Capacidad total configurable
                    </li>
                    <li class="flex items-center gap-2">
                        <flux:icon name="check" class="h-4 w-4 text-green-500" />
                        Múltiples ubicaciones de almacenamiento
                    </li>
                    <li class="flex items-center gap-2">
                        <flux:icon name="check" class="h-4 w-4 text-green-500" />
                        Puede tener sub-almacenes
                    </li>
                </ul>
            </flux:card>

            <flux:card class="p-4 border-l-4 border-l-blue-500">
                <flux:heading size="md" class="mb-3 flex items-center gap-2">
                    <flux:icon name="squares-2x2" class="h-5 w-5 text-blue-600" />
                    Almacén Fraccionado
                </flux:heading>
                <flux:text class="text-sm mb-3">
                    Sub-almacén especializado que pertenece a un almacén general, usado para
                    organización específica de productos.
                </flux:text>
                <ul class="space-y-1 text-sm text-zinc-600 dark:text-zinc-400">
                    <li class="flex items-center gap-2">
                        <flux:icon name="check" class="h-4 w-4 text-blue-500" />
                        Pertenece a un almacén padre
                    </li>
                    <li class="flex items-center gap-2">
                        <flux:icon name="check" class="h-4 w-4 text-blue-500" />
                        Capacidad propia
                    </li>
                    <li class="flex items-center gap-2">
                        <flux:icon name="check" class="h-4 w-4 text-blue-500" />
                        Control granular de productos
                    </li>
                </ul>
            </flux:card>
        </div>
    </section>

    <!-- Capacity Management -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Gestión de Capacidad
        </flux:heading>

        <flux:text>
            El sistema monitorea la capacidad de cada almacén y ubicación en tiempo real,
            calculando la utilización basada en el inventario actual almacenado.
        </flux:text>

        <div class="bg-zinc-50 dark:bg-zinc-800 p-4 rounded-lg mb-4">
            <flux:heading size="sm" class="mb-3">Cálculo de Utilización en Tiempo Real</flux:heading>
            <div class="space-y-3">
                <div class="flex items-center gap-4">
                    <flux:badge color="zinc" size="sm">Fórmula</flux:badge>
                    <flux:text class="font-mono text-sm">
                        Utilización (%) = (Cantidad en Inventario / Capacidad Total) x 100
                    </flux:text>
                </div>
                <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                    El sistema suma automáticamente las cantidades de todos los registros de inventario
                    activos en cada ubicación y las compara con la capacidad configurada.
                </flux:text>
            </div>
        </div>

        <flux:heading size="md" class="text-zinc-800 dark:text-zinc-200">
            Formato de Visualización
        </flux:heading>

        <flux:text class="mb-3">
            La información de capacidad se muestra en el formato: <strong>Usado / Total (Disponible)</strong>
        </flux:text>

        <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-lg p-4 mb-4">
            <div class="flex items-center justify-between">
                <div>
                    <flux:text class="font-medium">Ubicación A-01</flux:text>
                    <flux:text class="text-sm text-zinc-500">75.00 / 100.00 (25.00 disponible)</flux:text>
                </div>
                <flux:badge color="yellow">75%</flux:badge>
            </div>
            <div class="mt-2 w-full bg-zinc-200 dark:bg-zinc-700 rounded-full h-2">
                <div class="bg-yellow-500 h-2 rounded-full" style="width: 75%"></div>
            </div>
        </div>

        <flux:heading size="md" class="text-zinc-800 dark:text-zinc-200">
            Indicadores Visuales de Capacidad
        </flux:heading>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-zinc-100 dark:bg-zinc-800">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium">Indicador</th>
                        <th class="px-4 py-3 text-left font-medium">Rango</th>
                        <th class="px-4 py-3 text-left font-medium">Significado</th>
                        <th class="px-4 py-3 text-left font-medium">Acción Recomendada</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    <tr>
                        <td class="px-4 py-3">
                            <flux:badge color="green">Verde</flux:badge>
                        </td>
                        <td class="px-4 py-3">0% - 74%</td>
                        <td class="px-4 py-3">Capacidad disponible adecuada</td>
                        <td class="px-4 py-3 text-zinc-500">Operación normal</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3">
                            <flux:badge color="yellow">Amarillo</flux:badge>
                        </td>
                        <td class="px-4 py-3">75% - 89%</td>
                        <td class="px-4 py-3">Capacidad limitada</td>
                        <td class="px-4 py-3 text-zinc-500">Considerar reorganización</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3">
                            <flux:badge color="red">Rojo</flux:badge>
                        </td>
                        <td class="px-4 py-3">90% - 100%</td>
                        <td class="px-4 py-3">Capacidad crítica</td>
                        <td class="px-4 py-3 text-zinc-500">Acción inmediata requerida</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <flux:callout variant="info" icon="information-circle">
            <strong>Nota:</strong> Las ubicaciones sin capacidad definida mostrarán "Sin capacidad definida".
            Se recomienda configurar la capacidad de todas las ubicaciones activas para un monitoreo preciso.
        </flux:callout>

        <flux:heading size="md" class="text-zinc-800 dark:text-zinc-200 mt-6">
            Panel de Capacidad de Almacenes
        </flux:heading>

        <flux:text>
            Desde el menú <strong>Bodegas → Capacidad de Almacenes</strong> puede:
        </flux:text>

        <ul class="space-y-2 text-sm text-zinc-600 dark:text-zinc-400 mt-3">
            <li class="flex items-center gap-2">
                <flux:icon name="building-office" class="h-4 w-4 text-green-500" />
                Seleccionar una bodega para ver sus ubicaciones
            </li>
            <li class="flex items-center gap-2">
                <flux:icon name="chart-pie" class="h-4 w-4 text-purple-500" />
                Ver el resumen de capacidad total del almacén
            </li>
            <li class="flex items-center gap-2">
                <flux:icon name="map-pin" class="h-4 w-4 text-amber-500" />
                Navegar directamente a cada ubicación haciendo clic en su nombre o código
            </li>
            <li class="flex items-center gap-2">
                <flux:icon name="eye" class="h-4 w-4 text-blue-500" />
                Ver detalles de utilización por ubicación individual
            </li>
        </ul>
    </section>

    <!-- Recent Activities -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Actividades Recientes
        </flux:heading>

        <flux:text>
            El dashboard muestra los últimos movimientos de inventario para monitoreo en tiempo real.
        </flux:text>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <flux:card class="p-4">
                <flux:heading size="sm" class="mb-2 text-green-700 dark:text-green-300">Entradas</flux:heading>
                <ul class="space-y-2 text-sm">
                    <li class="flex items-center gap-2">
                        <flux:icon name="arrow-down-circle" class="h-4 w-4 text-green-500" />
                        Entrada de inventario
                    </li>
                    <li class="flex items-center gap-2">
                        <flux:icon name="shopping-cart" class="h-4 w-4 text-green-500" />
                        Compra recibida
                    </li>
                    <li class="flex items-center gap-2">
                        <flux:icon name="gift" class="h-4 w-4 text-green-500" />
                        Donación recibida
                    </li>
                    <li class="flex items-center gap-2">
                        <flux:icon name="arrow-uturn-left" class="h-4 w-4 text-purple-500" />
                        Devolución de cliente
                    </li>
                </ul>
            </flux:card>

            <flux:card class="p-4">
                <flux:heading size="sm" class="mb-2 text-red-700 dark:text-red-300">Salidas</flux:heading>
                <ul class="space-y-2 text-sm">
                    <li class="flex items-center gap-2">
                        <flux:icon name="arrow-up-circle" class="h-4 w-4 text-red-500" />
                        Salida de inventario
                    </li>
                    <li class="flex items-center gap-2">
                        <flux:icon name="truck" class="h-4 w-4 text-red-500" />
                        Despacho/Envío
                    </li>
                    <li class="flex items-center gap-2">
                        <flux:icon name="clock" class="h-4 w-4 text-red-500" />
                        Vencimiento
                    </li>
                    <li class="flex items-center gap-2">
                        <flux:icon name="exclamation-triangle" class="h-4 w-4 text-red-500" />
                        Daño/Pérdida
                    </li>
                </ul>
            </flux:card>

            <flux:card class="p-4">
                <flux:heading size="sm" class="mb-2 text-blue-700 dark:text-blue-300">Otros</flux:heading>
                <ul class="space-y-2 text-sm">
                    <li class="flex items-center gap-2">
                        <flux:icon name="arrows-right-left" class="h-4 w-4 text-blue-500" />
                        Transferencia
                    </li>
                    <li class="flex items-center gap-2">
                        <flux:icon name="adjustments-horizontal" class="h-4 w-4 text-yellow-500" />
                        Ajuste de inventario
                    </li>
                    <li class="flex items-center gap-2">
                        <flux:icon name="clipboard-document-check" class="h-4 w-4 text-green-500" />
                        Recepción verificada
                    </li>
                </ul>
            </flux:card>
        </div>
    </section>

    <!-- Warehouse Fields -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Campos del Almacén
        </flux:heading>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <flux:card class="p-4">
                <flux:heading size="md" class="mb-3 flex items-center gap-2">
                    <flux:icon name="identification" class="h-5 w-5 text-blue-600" />
                    Información Básica
                </flux:heading>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between py-1 border-b border-zinc-100 dark:border-zinc-800">
                        <span class="text-zinc-600 dark:text-zinc-400">Nombre</span>
                        <span class="font-medium">Requerido</span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-zinc-100 dark:border-zinc-800">
                        <span class="text-zinc-600 dark:text-zinc-400">Código</span>
                        <span class="font-medium">Único, Requerido</span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-zinc-100 dark:border-zinc-800">
                        <span class="text-zinc-600 dark:text-zinc-400">Tipo</span>
                        <span class="font-medium">General/Fraccionado</span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-zinc-100 dark:border-zinc-800">
                        <span class="text-zinc-600 dark:text-zinc-400">Descripción</span>
                        <span class="font-medium">Opcional</span>
                    </div>
                </div>
            </flux:card>

            <flux:card class="p-4">
                <flux:heading size="md" class="mb-3 flex items-center gap-2">
                    <flux:icon name="map-pin" class="h-5 w-5 text-green-600" />
                    Ubicación
                </flux:heading>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between py-1 border-b border-zinc-100 dark:border-zinc-800">
                        <span class="text-zinc-600 dark:text-zinc-400">Dirección</span>
                        <span class="font-medium">Opcional</span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-zinc-100 dark:border-zinc-800">
                        <span class="text-zinc-600 dark:text-zinc-400">Ciudad</span>
                        <span class="font-medium">Opcional</span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-zinc-100 dark:border-zinc-800">
                        <span class="text-zinc-600 dark:text-zinc-400">Departamento</span>
                        <span class="font-medium">Opcional</span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-zinc-100 dark:border-zinc-800">
                        <span class="text-zinc-600 dark:text-zinc-400">Coordenadas GPS</span>
                        <span class="font-medium">Opcional</span>
                    </div>
                </div>
            </flux:card>

            <flux:card class="p-4">
                <flux:heading size="md" class="mb-3 flex items-center gap-2">
                    <flux:icon name="cube" class="h-5 w-5 text-purple-600" />
                    Capacidad
                </flux:heading>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between py-1 border-b border-zinc-100 dark:border-zinc-800">
                        <span class="text-zinc-600 dark:text-zinc-400">Capacidad Total</span>
                        <span class="font-medium">Numérico</span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-zinc-100 dark:border-zinc-800">
                        <span class="text-zinc-600 dark:text-zinc-400">Unidad de Medida</span>
                        <span class="font-medium">m³, pallets, etc.</span>
                    </div>
                </div>
            </flux:card>

            <flux:card class="p-4">
                <flux:heading size="md" class="mb-3 flex items-center gap-2">
                    <flux:icon name="cog" class="h-5 w-5 text-orange-600" />
                    Administración
                </flux:heading>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between py-1 border-b border-zinc-100 dark:border-zinc-800">
                        <span class="text-zinc-600 dark:text-zinc-400">Gerente</span>
                        <span class="font-medium">Usuario responsable</span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-zinc-100 dark:border-zinc-800">
                        <span class="text-zinc-600 dark:text-zinc-400">Horarios</span>
                        <span class="font-medium">JSON configurable</span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-zinc-100 dark:border-zinc-800">
                        <span class="text-zinc-600 dark:text-zinc-400">Estado</span>
                        <span class="font-medium">Activo/Inactivo</span>
                    </div>
                </div>
            </flux:card>
        </div>
    </section>

    <!-- Quick Actions -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Acciones Rápidas
        </flux:heading>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <flux:card class="p-4 text-center hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors">
                <flux:icon name="plus" class="h-8 w-8 text-indigo-500 mx-auto mb-2" />
                <flux:text class="font-medium">Agregar Empresa</flux:text>
                <flux:text class="text-xs text-zinc-500">Nueva empresa</flux:text>
            </flux:card>

            <flux:card class="p-4 text-center hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors">
                <flux:icon name="building-storefront" class="h-8 w-8 text-blue-500 mx-auto mb-2" />
                <flux:text class="font-medium">Agregar Sucursal</flux:text>
                <flux:text class="text-xs text-zinc-500">Nueva sucursal</flux:text>
            </flux:card>

            <flux:card class="p-4 text-center hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors">
                <flux:icon name="building-office" class="h-8 w-8 text-green-500 mx-auto mb-2" />
                <flux:text class="font-medium">Agregar Almacén</flux:text>
                <flux:text class="text-xs text-zinc-500">Nueva bodega</flux:text>
            </flux:card>

            <flux:card class="p-4 text-center hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors">
                <flux:icon name="chart-pie" class="h-8 w-8 text-purple-500 mx-auto mb-2" />
                <flux:text class="font-medium">Gestión Capacidad</flux:text>
                <flux:text class="text-xs text-zinc-500">Análisis detallado</flux:text>
            </flux:card>
        </div>
    </section>

    <!-- Best Practices -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Mejores Prácticas
        </flux:heading>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <flux:card class="p-4 border-l-4 border-l-green-500">
                <flux:heading size="sm" class="mb-2 text-green-700 dark:text-green-300">
                    Configurar Capacidad
                </flux:heading>
                <flux:text class="text-sm">
                    Siempre establezca la capacidad total del almacén para un monitoreo preciso
                    de la utilización.
                </flux:text>
            </flux:card>

            <flux:card class="p-4 border-l-4 border-l-blue-500">
                <flux:heading size="sm" class="mb-2 text-blue-700 dark:text-blue-300">
                    Usar Ubicaciones
                </flux:heading>
                <flux:text class="text-sm">
                    Cree ubicaciones de almacenamiento para un tracking más granular
                    de productos.
                </flux:text>
            </flux:card>

            <flux:card class="p-4 border-l-4 border-l-purple-500">
                <flux:heading size="sm" class="mb-2 text-purple-700 dark:text-purple-300">
                    Asignar Gerentes
                </flux:heading>
                <flux:text class="text-sm">
                    Defina responsables para cada almacén para mejor control
                    y accountability.
                </flux:text>
            </flux:card>

            <flux:card class="p-4 border-l-4 border-l-orange-500">
                <flux:heading size="sm" class="mb-2 text-orange-700 dark:text-orange-300">
                    Revisar Utilización
                </flux:heading>
                <flux:text class="text-sm">
                    Monitoree regularmente la capacidad para evitar saturación
                    y planificar expansiones.
                </flux:text>
            </flux:card>
        </div>
    </section>

    <!-- Integration -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Integración con Otros Módulos
        </flux:heading>

        <div class="bg-zinc-50 dark:bg-zinc-800 p-4 rounded-lg">
            <div class="grid grid-cols-2 md:grid-cols-3 gap-4 text-sm">
                <div class="flex items-center gap-2">
                    <flux:icon name="shopping-cart" class="h-5 w-5 text-green-500" />
                    <span>Compras (recepción)</span>
                </div>
                <div class="flex items-center gap-2">
                    <flux:icon name="gift" class="h-5 w-5 text-purple-500" />
                    <span>Donaciones</span>
                </div>
                <div class="flex items-center gap-2">
                    <flux:icon name="arrows-right-left" class="h-5 w-5 text-blue-500" />
                    <span>Transferencias</span>
                </div>
                <div class="flex items-center gap-2">
                    <flux:icon name="truck" class="h-5 w-5 text-orange-500" />
                    <span>Despachos</span>
                </div>
                <div class="flex items-center gap-2">
                    <flux:icon name="adjustments-horizontal" class="h-5 w-5 text-yellow-500" />
                    <span>Ajustes</span>
                </div>
                <div class="flex items-center gap-2">
                    <flux:icon name="calendar" class="h-5 w-5 text-red-500" />
                    <span>Cierres mensuales</span>
                </div>
            </div>
        </div>
    </section>
</div>
