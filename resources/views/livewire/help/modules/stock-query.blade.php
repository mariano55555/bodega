<div class="space-y-8">
    <!-- Module Header -->
    <div class="border-b border-zinc-200 dark:border-zinc-700 pb-6">
        <div class="flex items-center gap-4 mb-4">
            <div class="p-3 bg-blue-100 dark:bg-blue-900 rounded-lg">
                <flux:icon name="magnifying-glass" class="h-8 w-8 text-blue-600 dark:text-blue-400" />
            </div>
            <div>
                <flux:heading size="xl" class="text-zinc-900 dark:text-zinc-100">
                    Consulta de Existencias
                </flux:heading>
                <flux:text class="text-zinc-600 dark:text-zinc-400">
                    Busqueda y consulta del stock disponible en todas las bodegas
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
            La Consulta de Existencias permite buscar y visualizar el stock disponible de productos
            en todas las bodegas. Es una herramienta esencial para verificar disponibilidad,
            identificar productos con problemas de stock y tomar decisiones de abastecimiento.
        </flux:text>

        <flux:callout variant="info" icon="information-circle">
            Los filtros de busqueda se guardan en la URL, permitiendo compartir o guardar
            consultas especificas como marcadores.
        </flux:callout>
    </section>

    <!-- Filters -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Filtros de Busqueda
        </flux:heading>

        <flux:text>
            Utilice los siguientes filtros para encontrar rapidamente la informacion que necesita:
        </flux:text>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <flux:card class="p-4">
                <flux:heading size="sm" class="mb-2 flex items-center gap-2">
                    <flux:icon name="building-office-2" class="h-5 w-5 text-indigo-600" />
                    Empresa
                </flux:heading>
                <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                    <strong>Solo Super Administradores:</strong> Permite filtrar por empresa.
                    Los usuarios regulares ven solo los datos de su empresa asignada.
                </flux:text>
            </flux:card>

            <flux:card class="p-4">
                <flux:heading size="sm" class="mb-2 flex items-center gap-2">
                    <flux:icon name="magnifying-glass" class="h-5 w-5 text-blue-600" />
                    Busqueda General
                </flux:heading>
                <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                    Busque por nombre de producto, SKU o codigo de barras.
                    La busqueda es en tiempo real con debounce de 300ms.
                </flux:text>
            </flux:card>

            <flux:card class="p-4">
                <flux:heading size="sm" class="mb-2 flex items-center gap-2">
                    <flux:icon name="building-office" class="h-5 w-5 text-green-600" />
                    Bodega
                </flux:heading>
                <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                    Filtre los resultados por una bodega especifica o vea todas las bodegas.
                </flux:text>
            </flux:card>

            <flux:card class="p-4">
                <flux:heading size="sm" class="mb-2 flex items-center gap-2">
                    <flux:icon name="funnel" class="h-5 w-5 text-purple-600" />
                    Estado de Stock
                </flux:heading>
                <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                    <strong>Todos:</strong> Muestra todos los registros<br>
                    <strong>Stock Bajo:</strong> Productos por debajo del minimo<br>
                    <strong>Sin Stock:</strong> Productos agotados
                </flux:text>
            </flux:card>
        </div>

        <flux:callout variant="info" icon="information-circle">
            Use el boton <strong>Limpiar Filtros</strong> para restablecer todos los filtros
            a sus valores predeterminados.
        </flux:callout>
    </section>

    <!-- Results Table -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Tabla de Resultados
        </flux:heading>

        <flux:text>
            La tabla muestra los registros de inventario con la siguiente informacion:
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
                        <td class="px-4 py-3">Nombre del producto y categoria a la que pertenece</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3 font-medium">SKU</td>
                        <td class="px-4 py-3">Codigo unico de identificacion del producto</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3 font-medium">Bodega</td>
                        <td class="px-4 py-3">Ubicacion donde se encuentra el stock</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3 font-medium">Cantidad</td>
                        <td class="px-4 py-3">
                            Unidades disponibles con codigo de colores:<br>
                            <span class="text-green-600">Verde</span> = Stock normal,
                            <span class="text-yellow-600">Amarillo</span> = Stock bajo,
                            <span class="text-red-600">Rojo</span> = Sin stock
                        </td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3 font-medium">Unidad</td>
                        <td class="px-4 py-3">Unidad de medida del producto (kg, unidades, litros, etc.)</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3 font-medium">Ubicacion</td>
                        <td class="px-4 py-3">Ubicacion especifica dentro de la bodega (si esta definida)</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3 font-medium">Estado</td>
                        <td class="px-4 py-3">
                            Indicador visual del estado del stock:
                            <div class="flex flex-wrap gap-2 mt-1">
                                <flux:badge color="green" size="sm">Disponible</flux:badge>
                                <flux:badge color="yellow" size="sm">Stock Bajo</flux:badge>
                                <flux:badge color="red" size="sm">Sin Stock</flux:badge>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>

    <!-- Stock Status Indicators -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Indicadores de Estado
        </flux:heading>

        <flux:text>
            El sistema utiliza indicadores visuales para identificar rapidamente el estado del stock:
        </flux:text>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <flux:card class="p-4 border-l-4 border-l-green-500">
                <div class="flex items-center gap-3 mb-2">
                    <flux:badge color="green" size="sm">Disponible</flux:badge>
                </div>
                <flux:text class="text-sm">
                    El producto tiene suficiente stock. La cantidad es mayor al stock minimo configurado.
                </flux:text>
            </flux:card>

            <flux:card class="p-4 border-l-4 border-l-yellow-500">
                <div class="flex items-center gap-3 mb-2">
                    <flux:badge color="yellow" size="sm">Stock Bajo</flux:badge>
                </div>
                <flux:text class="text-sm">
                    La cantidad es menor o igual al stock minimo configurado en el producto.
                    Se recomienda reabastecer.
                </flux:text>
            </flux:card>

            <flux:card class="p-4 border-l-4 border-l-red-500">
                <div class="flex items-center gap-3 mb-2">
                    <flux:badge color="red" size="sm">Sin Stock</flux:badge>
                </div>
                <flux:text class="text-sm">
                    El producto esta completamente agotado (cantidad = 0 o negativa).
                    Requiere atencion inmediata.
                </flux:text>
            </flux:card>
        </div>
    </section>

    <!-- Pagination -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Paginacion
        </flux:heading>

        <flux:text>
            La consulta soporta paginacion para manejar grandes cantidades de registros:
        </flux:text>

        <ul class="space-y-2 text-sm text-zinc-600 dark:text-zinc-400">
            <li class="flex items-center gap-2">
                <flux:icon name="check" class="h-4 w-4 text-green-500" />
                <strong>Registros por pagina:</strong> 10, 15, 25, 50 o 100 registros
            </li>
            <li class="flex items-center gap-2">
                <flux:icon name="check" class="h-4 w-4 text-green-500" />
                <strong>Contador:</strong> Muestra "Mostrando X - Y de Z registros"
            </li>
            <li class="flex items-center gap-2">
                <flux:icon name="check" class="h-4 w-4 text-green-500" />
                <strong>Navegacion:</strong> Enlaces para moverse entre paginas
            </li>
        </ul>

        <flux:callout variant="info" icon="information-circle">
            La configuracion de registros por pagina se mantiene al cambiar filtros.
        </flux:callout>
    </section>

    <!-- URL Parameters -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Parametros de URL
        </flux:heading>

        <flux:text>
            Los filtros se reflejan en la URL, permitiendo compartir consultas especificas:
        </flux:text>

        <div class="bg-zinc-50 dark:bg-zinc-800 p-4 rounded-lg font-mono text-sm">
            <div class="space-y-1">
                <p><strong>empresa</strong> = ID de la empresa</p>
                <p><strong>q</strong> = Texto de busqueda</p>
                <p><strong>bodega</strong> = ID de la bodega</p>
                <p><strong>estado</strong> = all | low | out</p>
                <p><strong>porpagina</strong> = 10 | 15 | 25 | 50 | 100</p>
            </div>
        </div>

        <flux:text class="text-sm">
            <strong>Ejemplo:</strong> <code class="bg-zinc-100 dark:bg-zinc-800 px-2 py-1 rounded">/stock-query?q=arroz&estado=low&bodega=1</code>
        </flux:text>
    </section>

    <!-- Use Cases -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Casos de Uso Comunes
        </flux:heading>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <flux:card class="p-4">
                <flux:heading size="sm" class="mb-2 text-blue-700 dark:text-blue-300">
                    Verificar Disponibilidad
                </flux:heading>
                <flux:text class="text-sm">
                    Busque un producto especifico para verificar si hay stock disponible
                    antes de comprometer una venta o despacho.
                </flux:text>
            </flux:card>

            <flux:card class="p-4">
                <flux:heading size="sm" class="mb-2 text-orange-700 dark:text-orange-300">
                    Identificar Faltantes
                </flux:heading>
                <flux:text class="text-sm">
                    Filtre por "Sin Stock" para obtener una lista de todos los productos
                    que necesitan reabastecimiento urgente.
                </flux:text>
            </flux:card>

            <flux:card class="p-4">
                <flux:heading size="sm" class="mb-2 text-green-700 dark:text-green-300">
                    Revisar Bodega Especifica
                </flux:heading>
                <flux:text class="text-sm">
                    Seleccione una bodega para ver todo el inventario disponible
                    en esa ubicacion especifica.
                </flux:text>
            </flux:card>

            <flux:card class="p-4">
                <flux:heading size="sm" class="mb-2 text-purple-700 dark:text-purple-300">
                    Planificar Compras
                </flux:heading>
                <flux:text class="text-sm">
                    Filtre por "Stock Bajo" para generar una lista de productos
                    que deben incluirse en la proxima orden de compra.
                </flux:text>
            </flux:card>
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
                    Consultas Frecuentes
                </flux:heading>
                <flux:text class="text-sm">
                    Guarde las URLs de consultas frecuentes como marcadores para acceso rapido.
                </flux:text>
            </flux:card>

            <flux:card class="p-4 border-l-4 border-l-blue-500">
                <flux:heading size="sm" class="mb-2 text-blue-700 dark:text-blue-300">
                    Revision Diaria
                </flux:heading>
                <flux:text class="text-sm">
                    Revise productos con stock bajo al inicio de cada jornada laboral.
                </flux:text>
            </flux:card>

            <flux:card class="p-4 border-l-4 border-l-purple-500">
                <flux:heading size="sm" class="mb-2 text-purple-700 dark:text-purple-300">
                    Configurar Minimos
                </flux:heading>
                <flux:text class="text-sm">
                    Asegure que todos los productos tengan stock minimo configurado
                    para que las alertas funcionen correctamente.
                </flux:text>
            </flux:card>

            <flux:card class="p-4 border-l-4 border-l-orange-500">
                <flux:heading size="sm" class="mb-2 text-orange-700 dark:text-orange-300">
                    Usar Filtros Combinados
                </flux:heading>
                <flux:text class="text-sm">
                    Combine filtros de bodega y estado para obtener informacion
                    mas especifica y relevante.
                </flux:text>
            </flux:card>
        </div>
    </section>

    <!-- Related Modules -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Modulos Relacionados
        </flux:heading>

        <div class="bg-zinc-50 dark:bg-zinc-800 p-4 rounded-lg">
            <div class="grid grid-cols-2 md:grid-cols-3 gap-4 text-sm">
                <div class="flex items-center gap-2">
                    <flux:icon name="squares-2x2" class="h-5 w-5 text-emerald-500" />
                    <span>Resumen de Inventario</span>
                </div>
                <div class="flex items-center gap-2">
                    <flux:icon name="bell" class="h-5 w-5 text-red-500" />
                    <span>Alertas de Stock</span>
                </div>
                <div class="flex items-center gap-2">
                    <flux:icon name="arrows-right-left" class="h-5 w-5 text-violet-500" />
                    <span>Movimientos</span>
                </div>
                <div class="flex items-center gap-2">
                    <flux:icon name="cube" class="h-5 w-5 text-blue-500" />
                    <span>Catalogo de Productos</span>
                </div>
                <div class="flex items-center gap-2">
                    <flux:icon name="shopping-cart" class="h-5 w-5 text-green-500" />
                    <span>Compras</span>
                </div>
                <div class="flex items-center gap-2">
                    <flux:icon name="chart-bar" class="h-5 w-5 text-purple-500" />
                    <span>Reportes</span>
                </div>
            </div>
        </div>
    </section>
</div>
