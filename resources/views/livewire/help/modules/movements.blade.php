<div class="space-y-8">
    <!-- Module Header -->
    <div class="border-b border-zinc-200 dark:border-zinc-700 pb-6">
        <div class="flex items-center gap-4 mb-4">
            <div class="p-3 bg-blue-100 dark:bg-blue-900 rounded-lg">
                <flux:icon name="arrows-right-left" class="h-8 w-8 text-blue-600 dark:text-blue-400" />
            </div>
            <div>
                <flux:heading size="xl" class="text-zinc-900 dark:text-zinc-100">
                    Consulta de Movimientos
                </flux:heading>
                <flux:text class="text-zinc-600 dark:text-zinc-400">
                    Registro y consulta de todos los movimientos de inventario
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
            El modulo de Consulta de Movimientos permite registrar, visualizar y gestionar todas las
            transacciones de inventario en el sistema. Desde aqui puede registrar entradas y salidas
            de productos, consultar el historial de movimientos y ver los detalles de cada operacion.
        </flux:text>

        <flux:callout variant="info" icon="information-circle">
            Todos los movimientos quedan registrados con informacion de auditoria incluyendo
            quien creo, confirmo, aprobo y completo cada operacion.
        </flux:callout>
    </section>

    <!-- Quick Actions -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Acciones Rapidas
        </flux:heading>

        <flux:text>
            En la parte superior de la pantalla encontrara botones para las operaciones mas frecuentes:
        </flux:text>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <flux:card class="p-4">
                <div class="flex items-center gap-3">
                    <flux:icon name="plus" class="h-5 w-5 text-green-500" />
                    <div>
                        <flux:text class="font-medium">Registrar Entrada</flux:text>
                        <flux:text class="text-sm text-zinc-500">
                            Ingreso de productos al inventario (compras, donaciones, devoluciones)
                        </flux:text>
                    </div>
                </div>
            </flux:card>

            <flux:card class="p-4">
                <div class="flex items-center gap-3">
                    <flux:icon name="minus" class="h-5 w-5 text-red-500" />
                    <div>
                        <flux:text class="font-medium">Registrar Salida</flux:text>
                        <flux:text class="text-sm text-zinc-500">
                            Egreso de productos del inventario (ventas, despachos, bajas)
                        </flux:text>
                    </div>
                </div>
            </flux:card>

            <flux:card class="p-4">
                <div class="flex items-center gap-3">
                    <flux:icon name="arrow-path" class="h-5 w-5 text-blue-500" />
                    <div>
                        <flux:text class="font-medium">Crear Traslado</flux:text>
                        <flux:text class="text-sm text-zinc-500">
                            Mover productos entre bodegas
                        </flux:text>
                    </div>
                </div>
            </flux:card>

            <flux:card class="p-4">
                <div class="flex items-center gap-3">
                    <flux:icon name="document-arrow-down" class="h-5 w-5 text-purple-500" />
                    <div>
                        <flux:text class="font-medium">Generar Reporte</flux:text>
                        <flux:text class="text-sm text-zinc-500">
                            Exportar historial de movimientos
                        </flux:text>
                    </div>
                </div>
            </flux:card>
        </div>
    </section>

    <!-- Movement Types -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Tipos de Movimiento
        </flux:heading>

        <flux:text>
            El sistema registra diferentes tipos de movimientos, cada uno identificado por un color distintivo:
        </flux:text>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Entry Types -->
            <flux:card class="p-4 border-l-4 border-l-green-500">
                <flux:heading size="sm" class="mb-3 text-green-700 dark:text-green-300">
                    Movimientos de Entrada
                </flux:heading>
                <div class="space-y-2">
                    <div class="flex items-center gap-2">
                        <flux:badge color="green" size="sm">Compra</flux:badge>
                        <flux:text class="text-sm">Adquisicion de productos a proveedores</flux:text>
                    </div>
                    <div class="flex items-center gap-2">
                        <flux:badge color="pink" size="sm">Donacion</flux:badge>
                        <flux:text class="text-sm">Productos recibidos como donacion</flux:text>
                    </div>
                    <div class="flex items-center gap-2">
                        <flux:badge color="blue" size="sm">Traslado Entrada</flux:badge>
                        <flux:text class="text-sm">Productos recibidos de otra bodega</flux:text>
                    </div>
                    <div class="flex items-center gap-2">
                        <flux:badge color="zinc" size="sm">Devolucion</flux:badge>
                        <flux:text class="text-sm">Productos devueltos por clientes</flux:text>
                    </div>
                </div>
            </flux:card>

            <!-- Exit Types -->
            <flux:card class="p-4 border-l-4 border-l-red-500">
                <flux:heading size="sm" class="mb-3 text-red-700 dark:text-red-300">
                    Movimientos de Salida
                </flux:heading>
                <div class="space-y-2">
                    <div class="flex items-center gap-2">
                        <flux:badge color="red" size="sm">Venta</flux:badge>
                        <flux:text class="text-sm">Productos vendidos a clientes</flux:text>
                    </div>
                    <div class="flex items-center gap-2">
                        <flux:badge color="cyan" size="sm">Traslado Salida</flux:badge>
                        <flux:text class="text-sm">Productos enviados a otra bodega</flux:text>
                    </div>
                    <div class="flex items-center gap-2">
                        <flux:badge color="orange" size="sm">Vencimiento</flux:badge>
                        <flux:text class="text-sm">Productos dados de baja por caducidad</flux:text>
                    </div>
                </div>
            </flux:card>

            <!-- Adjustment Types -->
            <flux:card class="p-4 border-l-4 border-l-yellow-500 md:col-span-2">
                <flux:heading size="sm" class="mb-3 text-yellow-700 dark:text-yellow-300">
                    Ajustes de Inventario
                </flux:heading>
                <div class="flex items-center gap-2">
                    <flux:badge color="yellow" size="sm">Ajuste</flux:badge>
                    <flux:text class="text-sm">Correcciones por diferencias en conteo fisico, mermas u otras razones</flux:text>
                </div>
            </flux:card>
        </div>
    </section>

    <!-- Filters Section -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Filtros de Busqueda
        </flux:heading>

        <flux:text>
            Utilice los filtros disponibles para encontrar movimientos especificos:
        </flux:text>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-zinc-100 dark:bg-zinc-800">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium">Filtro</th>
                        <th class="px-4 py-3 text-left font-medium">Descripcion</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    <tr>
                        <td class="px-4 py-3 font-medium">Buscar Producto</td>
                        <td class="px-4 py-3">Busca por nombre, SKU o codigo de barras del producto</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3 font-medium">Bodega</td>
                        <td class="px-4 py-3">Filtra movimientos por bodega especifica</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3 font-medium">Tipo de Movimiento</td>
                        <td class="px-4 py-3">Filtra por tipo (compra, venta, traslado, etc.)</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3 font-medium">Por Pagina</td>
                        <td class="px-4 py-3">Cantidad de registros a mostrar (10, 15, 25, 50, 100)</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <flux:callout variant="info" icon="link">
            Los filtros se guardan en la URL, lo que permite compartir busquedas especificas
            con otros usuarios o guardarlas en favoritos.
        </flux:callout>
    </section>

    <!-- Movement History Table -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Historial de Movimientos
        </flux:heading>

        <flux:text>
            La tabla de historial muestra todos los movimientos registrados con la siguiente informacion:
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
                        <td class="px-4 py-3 font-medium">Fecha</td>
                        <td class="px-4 py-3">Fecha y hora del movimiento</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3 font-medium">Tipo</td>
                        <td class="px-4 py-3">Tipo de movimiento con codigo de color</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3 font-medium">Producto</td>
                        <td class="px-4 py-3">Nombre y SKU del producto</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3 font-medium">Bodega</td>
                        <td class="px-4 py-3">Ubicacion donde ocurrio el movimiento</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3 font-medium">Cantidad</td>
                        <td class="px-4 py-3">Unidades movidas (+ entrada, - salida)</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3 font-medium">Acciones</td>
                        <td class="px-4 py-3">Boton para ver detalles del movimiento</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>

    <!-- Movement Detail Modal -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Detalle del Movimiento
        </flux:heading>

        <flux:text>
            Al hacer clic en "Ver Detalles" se abre un panel con informacion completa del movimiento:
        </flux:text>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <flux:card class="p-4">
                <flux:heading size="sm" class="mb-3">Informacion General</flux:heading>
                <ul class="space-y-2 text-sm text-zinc-600 dark:text-zinc-400">
                    <li class="flex items-center gap-2">
                        <flux:icon name="tag" class="h-4 w-4 text-zinc-500" />
                        Tipo de movimiento
                    </li>
                    <li class="flex items-center gap-2">
                        <flux:icon name="calendar" class="h-4 w-4 text-zinc-500" />
                        Fecha del movimiento
                    </li>
                    <li class="flex items-center gap-2">
                        <flux:icon name="document-text" class="h-4 w-4 text-zinc-500" />
                        Numero de referencia
                    </li>
                </ul>
            </flux:card>

            <flux:card class="p-4">
                <flux:heading size="sm" class="mb-3">Informacion del Producto</flux:heading>
                <ul class="space-y-2 text-sm text-zinc-600 dark:text-zinc-400">
                    <li class="flex items-center gap-2">
                        <flux:icon name="cube" class="h-4 w-4 text-zinc-500" />
                        Nombre y SKU
                    </li>
                    <li class="flex items-center gap-2">
                        <flux:icon name="calculator" class="h-4 w-4 text-zinc-500" />
                        Cantidad movida
                    </li>
                    <li class="flex items-center gap-2">
                        <flux:icon name="scale" class="h-4 w-4 text-zinc-500" />
                        Unidad de medida
                    </li>
                </ul>
            </flux:card>

            <flux:card class="p-4">
                <flux:heading size="sm" class="mb-3">Ubicacion y Lote</flux:heading>
                <ul class="space-y-2 text-sm text-zinc-600 dark:text-zinc-400">
                    <li class="flex items-center gap-2">
                        <flux:icon name="building-office" class="h-4 w-4 text-zinc-500" />
                        Bodega
                    </li>
                    <li class="flex items-center gap-2">
                        <flux:icon name="map-pin" class="h-4 w-4 text-zinc-500" />
                        Ubicacion especifica
                    </li>
                    <li class="flex items-center gap-2">
                        <flux:icon name="hashtag" class="h-4 w-4 text-zinc-500" />
                        Numero de lote
                    </li>
                    <li class="flex items-center gap-2">
                        <flux:icon name="clock" class="h-4 w-4 text-zinc-500" />
                        Fecha de vencimiento
                    </li>
                </ul>
            </flux:card>

            <flux:card class="p-4">
                <flux:heading size="sm" class="mb-3">Auditoria</flux:heading>
                <ul class="space-y-2 text-sm text-zinc-600 dark:text-zinc-400">
                    <li class="flex items-center gap-2">
                        <flux:icon name="user" class="h-4 w-4 text-zinc-500" />
                        Creado por
                    </li>
                    <li class="flex items-center gap-2">
                        <flux:icon name="check" class="h-4 w-4 text-zinc-500" />
                        Confirmado por
                    </li>
                    <li class="flex items-center gap-2">
                        <flux:icon name="shield-check" class="h-4 w-4 text-zinc-500" />
                        Aprobado por
                    </li>
                    <li class="flex items-center gap-2">
                        <flux:icon name="check-circle" class="h-4 w-4 text-zinc-500" />
                        Completado por
                    </li>
                </ul>
            </flux:card>
        </div>
    </section>

    <!-- Related Documents -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Documentos Relacionados
        </flux:heading>

        <flux:text>
            Los movimientos pueden estar vinculados a documentos fuente segun su tipo:
        </flux:text>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <flux:card class="p-4">
                <div class="flex items-center gap-3">
                    <flux:icon name="shopping-cart" class="h-5 w-5 text-green-500" />
                    <div>
                        <flux:text class="font-medium">Orden de Compra</flux:text>
                        <flux:text class="text-sm text-zinc-500">Para movimientos de compra</flux:text>
                    </div>
                </div>
            </flux:card>

            <flux:card class="p-4">
                <div class="flex items-center gap-3">
                    <flux:icon name="heart" class="h-5 w-5 text-pink-500" />
                    <div>
                        <flux:text class="font-medium">Donacion</flux:text>
                        <flux:text class="text-sm text-zinc-500">Para movimientos de donacion</flux:text>
                    </div>
                </div>
            </flux:card>

            <flux:card class="p-4">
                <div class="flex items-center gap-3">
                    <flux:icon name="arrow-path" class="h-5 w-5 text-blue-500" />
                    <div>
                        <flux:text class="font-medium">Traslado</flux:text>
                        <flux:text class="text-sm text-zinc-500">Para movimientos entre bodegas</flux:text>
                    </div>
                </div>
            </flux:card>

            <flux:card class="p-4">
                <div class="flex items-center gap-3">
                    <flux:icon name="truck" class="h-5 w-5 text-cyan-500" />
                    <div>
                        <flux:text class="font-medium">Despacho</flux:text>
                        <flux:text class="text-sm text-zinc-500">Para salidas por despacho</flux:text>
                    </div>
                </div>
            </flux:card>

            <flux:card class="p-4">
                <div class="flex items-center gap-3">
                    <flux:icon name="adjustments-horizontal" class="h-5 w-5 text-yellow-500" />
                    <div>
                        <flux:text class="font-medium">Ajuste</flux:text>
                        <flux:text class="text-sm text-zinc-500">Para correcciones de inventario</flux:text>
                    </div>
                </div>
            </flux:card>
        </div>
    </section>

    <!-- Entry/Exit Forms -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Formularios de Entrada y Salida
        </flux:heading>

        <flux:text>
            Al registrar una entrada o salida, complete los siguientes campos:
        </flux:text>

        <div class="bg-zinc-50 dark:bg-zinc-800 p-4 rounded-lg">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <flux:heading size="sm" class="mb-3">Campos Requeridos</flux:heading>
                    <ul class="space-y-2 text-sm text-zinc-600 dark:text-zinc-400">
                        <li class="flex items-center gap-2">
                            <flux:icon name="cube" class="h-4 w-4 text-zinc-500" />
                            <strong>Producto</strong> - Seleccione el producto
                        </li>
                        <li class="flex items-center gap-2">
                            <flux:icon name="calculator" class="h-4 w-4 text-zinc-500" />
                            <strong>Cantidad</strong> - Unidades a mover
                        </li>
                        <li class="flex items-center gap-2">
                            <flux:icon name="building-office" class="h-4 w-4 text-zinc-500" />
                            <strong>Bodega</strong> - Ubicacion del movimiento
                        </li>
                        <li class="flex items-center gap-2">
                            <flux:icon name="tag" class="h-4 w-4 text-zinc-500" />
                            <strong>Tipo</strong> - Razon del movimiento
                        </li>
                    </ul>
                </div>
                <div>
                    <flux:heading size="sm" class="mb-3">Campos Opcionales</flux:heading>
                    <ul class="space-y-2 text-sm text-zinc-600 dark:text-zinc-400">
                        <li class="flex items-center gap-2">
                            <flux:icon name="hashtag" class="h-4 w-4 text-zinc-500" />
                            <strong>Lote</strong> - Numero de lote
                        </li>
                        <li class="flex items-center gap-2">
                            <flux:icon name="calendar" class="h-4 w-4 text-zinc-500" />
                            <strong>Vencimiento</strong> - Fecha de caducidad
                        </li>
                        <li class="flex items-center gap-2">
                            <flux:icon name="map-pin" class="h-4 w-4 text-zinc-500" />
                            <strong>Ubicacion</strong> - Posicion en bodega
                        </li>
                        <li class="flex items-center gap-2">
                            <flux:icon name="document-text" class="h-4 w-4 text-zinc-500" />
                            <strong>Notas</strong> - Observaciones adicionales
                        </li>
                    </ul>
                </div>
            </div>
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
                    Registro Inmediato
                </flux:heading>
                <flux:text class="text-sm">
                    Registre los movimientos al momento de ocurrir para mantener el inventario actualizado.
                </flux:text>
            </flux:card>

            <flux:card class="p-4 border-l-4 border-l-blue-500">
                <flux:heading size="sm" class="mb-2 text-blue-700 dark:text-blue-300">
                    Documentar Lotes
                </flux:heading>
                <flux:text class="text-sm">
                    Siempre registre numeros de lote y fechas de vencimiento para trazabilidad.
                </flux:text>
            </flux:card>

            <flux:card class="p-4 border-l-4 border-l-purple-500">
                <flux:heading size="sm" class="mb-2 text-purple-700 dark:text-purple-300">
                    Verificar Cantidades
                </flux:heading>
                <flux:text class="text-sm">
                    Confirme fisicamente las cantidades antes de registrar el movimiento.
                </flux:text>
            </flux:card>

            <flux:card class="p-4 border-l-4 border-l-orange-500">
                <flux:heading size="sm" class="mb-2 text-orange-700 dark:text-orange-300">
                    Revisar Historial
                </flux:heading>
                <flux:text class="text-sm">
                    Consulte periodicamente el historial para identificar patrones o anomalias.
                </flux:text>
            </flux:card>
        </div>
    </section>

    <!-- Tips -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Consejos Utiles
        </flux:heading>

        <flux:callout variant="success" icon="light-bulb">
            <ul class="space-y-2 text-sm">
                <li>Use el filtro de tipo para ver solo entradas o salidas rapidamente</li>
                <li>Exporte reportes periodicos para analisis de rotacion de inventario</li>
                <li>Verifique los documentos relacionados para una auditoria completa</li>
                <li>Configure alertas de stock bajo desde el modulo de productos</li>
            </ul>
        </flux:callout>
    </section>
</div>
