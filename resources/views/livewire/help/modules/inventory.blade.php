<div class="space-y-8">
    <!-- Module Header -->
    <div class="border-b border-zinc-200 dark:border-zinc-700 pb-6">
        <div class="flex items-center gap-4 mb-4">
            <div class="p-3 bg-emerald-100 dark:bg-emerald-900 rounded-lg">
                <flux:icon name="squares-2x2" class="h-8 w-8 text-emerald-600 dark:text-emerald-400" />
            </div>
            <div>
                <flux:heading size="xl" class="text-zinc-900 dark:text-zinc-100">
                    Resumen de Inventario
                </flux:heading>
                <flux:text class="text-zinc-600 dark:text-zinc-400">
                    Panel de control para monitorear el estado del inventario en tiempo real
                </flux:text>
            </div>
        </div>
    </div>

    <!-- Introduction -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Introduccion
        </flux:heading>

        <flux:text>
            El Resumen de Inventario es el panel principal que proporciona una vision general
            del estado actual de todo el inventario. Desde aqui puede monitorear metricas clave,
            identificar problemas de stock y acceder rapidamente a las operaciones mas comunes.
        </flux:text>

        <flux:callout variant="info" icon="information-circle">
            Este panel se actualiza en tiempo real y muestra datos consolidados de todas las
            bodegas a las que tiene acceso segun su rol y permisos.
        </flux:callout>
    </section>

    <!-- Key Metrics -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Metricas Clave
        </flux:heading>

        <flux:text>
            En la parte superior del panel encontrara cuatro tarjetas con indicadores esenciales:
        </flux:text>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <flux:card class="p-4 bg-blue-50 dark:bg-blue-950 border border-blue-200 dark:border-blue-800">
                <div class="flex items-center gap-3">
                    <flux:icon name="cube" class="h-6 w-6 text-blue-500" />
                    <div>
                        <flux:text class="text-xs text-blue-600 dark:text-blue-400">Productos</flux:text>
                        <flux:text class="font-bold text-blue-800 dark:text-blue-200">Total en catalogo</flux:text>
                    </div>
                </div>
            </flux:card>

            <flux:card class="p-4 bg-green-50 dark:bg-green-950 border border-green-200 dark:border-green-800">
                <div class="flex items-center gap-3">
                    <flux:icon name="building-office" class="h-6 w-6 text-green-500" />
                    <div>
                        <flux:text class="text-xs text-green-600 dark:text-green-400">Bodegas</flux:text>
                        <flux:text class="font-bold text-green-800 dark:text-green-200">Activas</flux:text>
                    </div>
                </div>
            </flux:card>

            <flux:card class="p-4 bg-red-50 dark:bg-red-950 border border-red-200 dark:border-red-800">
                <div class="flex items-center gap-3">
                    <flux:icon name="exclamation-triangle" class="h-6 w-6 text-red-500" />
                    <div>
                        <flux:text class="text-xs text-red-600 dark:text-red-400">Alertas</flux:text>
                        <flux:text class="font-bold text-red-800 dark:text-red-200">Stock Bajo</flux:text>
                    </div>
                </div>
            </flux:card>

            <flux:card class="p-4 bg-purple-50 dark:bg-purple-950 border border-purple-200 dark:border-purple-800">
                <div class="flex items-center gap-3">
                    <flux:icon name="clock" class="h-6 w-6 text-purple-500" />
                    <div>
                        <flux:text class="text-xs text-purple-600 dark:text-purple-400">Actividad</flux:text>
                        <flux:text class="font-bold text-purple-800 dark:text-purple-200">Reciente</flux:text>
                    </div>
                </div>
            </flux:card>
        </div>
    </section>

    <!-- Quick Actions -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Acciones Rapidas
        </flux:heading>

        <flux:text>
            En la cabecera del panel se encuentran botones de acceso rapido a las operaciones mas frecuentes:
        </flux:text>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <flux:card class="p-4">
                <div class="flex items-center gap-3">
                    <flux:icon name="plus" class="h-5 w-5 text-green-500" />
                    <div>
                        <flux:text class="font-medium">Registrar Entrada</flux:text>
                        <flux:text class="text-sm text-zinc-500">Ingreso de productos (compras, donaciones)</flux:text>
                    </div>
                </div>
            </flux:card>

            <flux:card class="p-4">
                <div class="flex items-center gap-3">
                    <flux:icon name="minus" class="h-5 w-5 text-red-500" />
                    <div>
                        <flux:text class="font-medium">Registrar Salida</flux:text>
                        <flux:text class="text-sm text-zinc-500">Egreso de productos (despachos, bajas)</flux:text>
                    </div>
                </div>
            </flux:card>

            <flux:card class="p-4">
                <div class="flex items-center gap-3">
                    <flux:icon name="arrow-path" class="h-5 w-5 text-blue-500" />
                    <div>
                        <flux:text class="font-medium">Crear Traslado</flux:text>
                        <flux:text class="text-sm text-zinc-500">Mover productos entre bodegas</flux:text>
                    </div>
                </div>
            </flux:card>

            <flux:card class="p-4">
                <div class="flex items-center gap-3">
                    <flux:icon name="chart-bar" class="h-5 w-5 text-purple-500" />
                    <div>
                        <flux:text class="font-medium">Generar Reporte</flux:text>
                        <flux:text class="text-sm text-zinc-500">Crear informes de inventario</flux:text>
                    </div>
                </div>
            </flux:card>
        </div>
    </section>

    <!-- Stock by Warehouse -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Niveles de Stock por Bodega
        </flux:heading>

        <flux:text>
            Esta seccion muestra un resumen del stock disponible en cada bodega:
        </flux:text>

        <ul class="space-y-2 text-sm text-zinc-600 dark:text-zinc-400">
            <li class="flex items-center gap-2">
                <flux:icon name="building-office" class="h-4 w-4 text-zinc-500" />
                <strong>Nombre de la bodega</strong> - Identificacion de la ubicacion
            </li>
            <li class="flex items-center gap-2">
                <flux:icon name="map-pin" class="h-4 w-4 text-zinc-500" />
                <strong>Ubicacion</strong> - Direccion o descripcion
            </li>
            <li class="flex items-center gap-2">
                <flux:icon name="cube" class="h-4 w-4 text-zinc-500" />
                <strong>Total de articulos</strong> - Cantidad total en stock
            </li>
        </ul>
    </section>

    <!-- Critical Stock -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Productos con Stock Critico
        </flux:heading>

        <flux:text>
            Esta seccion destaca los productos que requieren atencion inmediata debido a niveles
            de inventario por debajo del minimo establecido.
        </flux:text>

        <div class="bg-red-50 dark:bg-red-900/20 p-4 rounded-lg border border-red-200 dark:border-red-800">
            <flux:heading size="sm" class="mb-2 text-red-700 dark:text-red-300">Indicadores de Alerta</flux:heading>
            <ul class="space-y-2 text-sm text-zinc-600 dark:text-zinc-400">
                <li class="flex items-center gap-2">
                    <flux:badge color="red" size="sm">Stock Bajo</flux:badge>
                    <span>Cantidad por debajo del stock minimo</span>
                </li>
                <li class="flex items-center gap-2">
                    <flux:badge color="red" size="sm">Sin Stock</flux:badge>
                    <span>Producto completamente agotado</span>
                </li>
            </ul>
        </div>
    </section>

    <!-- Recent Movements -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Movimientos Recientes
        </flux:heading>

        <flux:text>
            La tabla de movimientos recientes muestra las ultimas transacciones de inventario:
        </flux:text>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-zinc-100 dark:bg-zinc-800">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium">Columna</th>
                        <th class="px-4 py-3 text-left font-medium">Descripcion</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    <tr>
                        <td class="px-4 py-3 font-medium">Producto</td>
                        <td class="px-4 py-3">Nombre y SKU del producto</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3 font-medium">Bodega</td>
                        <td class="px-4 py-3">Ubicacion del movimiento</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3 font-medium">Tipo</td>
                        <td class="px-4 py-3">
                            <div class="flex flex-wrap gap-2">
                                <flux:badge color="green" size="sm">Entrada</flux:badge>
                                <flux:badge color="red" size="sm">Salida</flux:badge>
                                <flux:badge color="blue" size="sm">Traslado</flux:badge>
                                <flux:badge color="yellow" size="sm">Ajuste</flux:badge>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3 font-medium">Cantidad</td>
                        <td class="px-4 py-3">Unidades (+ entradas, - salidas)</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3 font-medium">Fecha</td>
                        <td class="px-4 py-3">Fecha y hora del movimiento</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>

    <!-- Best Practices -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Mejores Practicas
        </flux:heading>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <flux:card class="p-4 border-l-4 border-l-green-500">
                <flux:heading size="sm" class="mb-2 text-green-700 dark:text-green-300">
                    Monitoreo Diario
                </flux:heading>
                <flux:text class="text-sm">
                    Revise el panel al inicio de cada jornada para identificar alertas de stock.
                </flux:text>
            </flux:card>

            <flux:card class="p-4 border-l-4 border-l-blue-500">
                <flux:heading size="sm" class="mb-2 text-blue-700 dark:text-blue-300">
                    Atender Alertas
                </flux:heading>
                <flux:text class="text-sm">
                    Priorice productos con stock critico para evitar desabastecimientos.
                </flux:text>
            </flux:card>

            <flux:card class="p-4 border-l-4 border-l-purple-500">
                <flux:heading size="sm" class="mb-2 text-purple-700 dark:text-purple-300">
                    Configurar Minimos
                </flux:heading>
                <flux:text class="text-sm">
                    Configure stock minimo en productos para activar alertas automaticas.
                </flux:text>
            </flux:card>

            <flux:card class="p-4 border-l-4 border-l-orange-500">
                <flux:heading size="sm" class="mb-2 text-orange-700 dark:text-orange-300">
                    Revisar Movimientos
                </flux:heading>
                <flux:text class="text-sm">
                    Verifique periodicamente los movimientos para detectar anomalias.
                </flux:text>
            </flux:card>
        </div>
    </section>
</div>
