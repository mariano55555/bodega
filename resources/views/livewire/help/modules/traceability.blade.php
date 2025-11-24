<div class="space-y-8">
    <!-- Module Header -->
    <div class="border-b border-zinc-200 dark:border-zinc-700 pb-6">
        <div class="flex items-center gap-4 mb-4">
            <div class="p-3 bg-gray-100 dark:bg-gray-900 rounded-lg">
                <flux:icon name="clock" class="h-8 w-8 text-gray-600 dark:text-gray-400" />
            </div>
            <div>
                <flux:heading size="xl" class="text-zinc-900 dark:text-zinc-100">
                    Trazabilidad Historica
                </flux:heading>
                <flux:text class="text-zinc-600 dark:text-zinc-400">
                    Seguimiento completo del recorrido de productos desde su ingreso hasta su consumo
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
            El modulo de Trazabilidad Historica permite visualizar el recorrido completo de un producto
            a traves de todo el sistema de gestion de bodegas. Desde su ingreso inicial (compra o donacion)
            hasta su salida final (despacho o consumo), puede rastrear cada movimiento, ubicacion y
            transaccion asociada.
        </flux:text>

        <flux:callout variant="info" icon="information-circle">
            La trazabilidad es esencial para el control de calidad, auditorias y cumplimiento normativo,
            permitiendo identificar rapidamente el origen y destino de cualquier producto.
        </flux:callout>
    </section>

    <!-- Access -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Acceso al Modulo
        </flux:heading>

        <flux:text>
            Puede acceder a la trazabilidad de productos de dos maneras:
        </flux:text>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <flux:card class="p-4">
                <div class="flex items-center gap-3 mb-3">
                    <flux:icon name="bars-3" class="h-5 w-5 text-gray-500" />
                    <flux:heading size="sm">Menu Principal</flux:heading>
                </div>
                <flux:text class="text-sm">
                    Navegue a <strong>Trazabilidad → Timeline de Producto</strong> desde el menu lateral
                </flux:text>
            </flux:card>

            <flux:card class="p-4">
                <div class="flex items-center gap-3 mb-3">
                    <flux:icon name="cube" class="h-5 w-5 text-gray-500" />
                    <flux:heading size="sm">Desde Producto</flux:heading>
                </div>
                <flux:text class="text-sm">
                    En la vista de detalle de un producto, haga clic en <strong>"Ver Trazabilidad"</strong>
                </flux:text>
            </flux:card>
        </div>
    </section>

    <!-- Filters -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Filtros de Busqueda
        </flux:heading>

        <flux:text>
            Para consultar la trazabilidad, primero debe seleccionar el producto y opcionalmente
            aplicar filtros adicionales:
        </flux:text>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-zinc-100 dark:bg-zinc-800">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium">Filtro</th>
                        <th class="px-4 py-3 text-left font-medium">Descripcion</th>
                        <th class="px-4 py-3 text-left font-medium">Requerido</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    <tr>
                        <td class="px-4 py-3 font-medium">Producto</td>
                        <td class="px-4 py-3">Seleccione el producto a rastrear</td>
                        <td class="px-4 py-3"><flux:badge color="red" size="sm">Obligatorio</flux:badge></td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3 font-medium">Almacen</td>
                        <td class="px-4 py-3">Filtrar movimientos por almacen especifico</td>
                        <td class="px-4 py-3"><flux:badge color="zinc" size="sm">Opcional</flux:badge></td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3 font-medium">Tipo de Movimiento</td>
                        <td class="px-4 py-3">Entrada, Salida, Transferencia o Ajuste</td>
                        <td class="px-4 py-3"><flux:badge color="zinc" size="sm">Opcional</flux:badge></td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3 font-medium">Fecha Desde</td>
                        <td class="px-4 py-3">Inicio del periodo a consultar</td>
                        <td class="px-4 py-3"><flux:badge color="zinc" size="sm">Opcional</flux:badge></td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3 font-medium">Fecha Hasta</td>
                        <td class="px-4 py-3">Fin del periodo a consultar</td>
                        <td class="px-4 py-3"><flux:badge color="zinc" size="sm">Opcional</flux:badge></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <flux:callout variant="warning" icon="exclamation-triangle">
            Por defecto, el sistema muestra los ultimos 3 meses de movimientos. Puede ampliar
            o reducir este periodo ajustando las fechas.
        </flux:callout>
    </section>

    <!-- Summary Cards -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Tarjetas de Resumen
        </flux:heading>

        <flux:text>
            Al seleccionar un producto, el sistema muestra un resumen con metricas clave:
        </flux:text>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <flux:card class="p-4 bg-blue-50 dark:bg-blue-950 border border-blue-200 dark:border-blue-800">
                <div class="flex items-center gap-3">
                    <flux:icon name="clipboard-document-list" class="h-6 w-6 text-blue-500" />
                    <div>
                        <flux:text class="text-xs text-blue-600 dark:text-blue-400">Total Movimientos</flux:text>
                        <flux:text class="font-bold text-blue-800 dark:text-blue-200">Cantidad total</flux:text>
                    </div>
                </div>
            </flux:card>

            <flux:card class="p-4 bg-purple-50 dark:bg-purple-950 border border-purple-200 dark:border-purple-800">
                <div class="flex items-center gap-3">
                    <flux:icon name="building-storefront" class="h-6 w-6 text-purple-500" />
                    <div>
                        <flux:text class="text-xs text-purple-600 dark:text-purple-400">Almacenes</flux:text>
                        <flux:text class="font-bold text-purple-800 dark:text-purple-200">Ubicaciones visitadas</flux:text>
                    </div>
                </div>
            </flux:card>

            <flux:card class="p-4 bg-green-50 dark:bg-green-950 border border-green-200 dark:border-green-800">
                <div class="flex items-center gap-3">
                    <flux:icon name="arrow-down-tray" class="h-6 w-6 text-green-500" />
                    <div>
                        <flux:text class="text-xs text-green-600 dark:text-green-400">Total Entradas</flux:text>
                        <flux:text class="font-bold text-green-800 dark:text-green-200">Unidades ingresadas</flux:text>
                    </div>
                </div>
            </flux:card>

            <flux:card class="p-4 bg-red-50 dark:bg-red-950 border border-red-200 dark:border-red-800">
                <div class="flex items-center gap-3">
                    <flux:icon name="arrow-up-tray" class="h-6 w-6 text-red-500" />
                    <div>
                        <flux:text class="text-xs text-red-600 dark:text-red-400">Total Salidas</flux:text>
                        <flux:text class="font-bold text-red-800 dark:text-red-200">Unidades despachadas</flux:text>
                    </div>
                </div>
            </flux:card>
        </div>
    </section>

    <!-- Location History -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Historial de Ubicaciones
        </flux:heading>

        <flux:text>
            Esta seccion muestra todas las bodegas por donde ha pasado el producto, incluyendo:
        </flux:text>

        <ul class="space-y-2 text-sm text-zinc-600 dark:text-zinc-400">
            <li class="flex items-center gap-2">
                <flux:icon name="map-pin" class="h-4 w-4 text-blue-500" />
                <strong>Nombre del almacen</strong> - Identificacion de la ubicacion
            </li>
            <li class="flex items-center gap-2">
                <flux:icon name="calendar" class="h-4 w-4 text-green-500" />
                <strong>Primera visita</strong> - Fecha del primer movimiento en ese almacen
            </li>
            <li class="flex items-center gap-2">
                <flux:icon name="clock" class="h-4 w-4 text-purple-500" />
                <strong>Ultima visita</strong> - Fecha del movimiento mas reciente
            </li>
            <li class="flex items-center gap-2">
                <flux:icon name="hashtag" class="h-4 w-4 text-orange-500" />
                <strong>Total de movimientos</strong> - Cantidad de operaciones en ese almacen
            </li>
        </ul>
    </section>

    <!-- Timeline -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Linea de Tiempo
        </flux:heading>

        <flux:text>
            La linea de tiempo muestra cronologicamente cada movimiento del producto con detalle completo:
        </flux:text>

        <div class="bg-zinc-50 dark:bg-zinc-800 rounded-lg p-6 space-y-4">
            <div class="flex items-start gap-4">
                <div class="w-4 h-4 rounded-full bg-green-500 border-4 border-white dark:border-zinc-900 mt-1"></div>
                <div class="flex-1">
                    <div class="flex items-center gap-2 mb-2">
                        <flux:badge color="green">Entrada</flux:badge>
                        <flux:text class="font-medium">Bodega Central</flux:text>
                    </div>
                    <flux:text class="text-xs text-zinc-500">15/11/2025 10:30 • Juan Perez</flux:text>
                    <div class="mt-2 flex justify-between">
                        <flux:text class="text-green-600 font-bold">+100.00</flux:text>
                        <flux:text class="text-zinc-500">Saldo: 100.00</flux:text>
                    </div>
                </div>
            </div>

            <div class="flex items-start gap-4">
                <div class="w-4 h-4 rounded-full bg-blue-500 border-4 border-white dark:border-zinc-900 mt-1"></div>
                <div class="flex-1">
                    <div class="flex items-center gap-2 mb-2">
                        <flux:badge color="blue">Transferencia Salida</flux:badge>
                        <flux:text class="font-medium">Bodega Norte</flux:text>
                    </div>
                    <flux:text class="text-xs text-zinc-500">18/11/2025 14:15 • Maria Garcia</flux:text>
                    <div class="mt-2 flex justify-between">
                        <flux:text class="text-red-600 font-bold">-25.00</flux:text>
                        <flux:text class="text-zinc-500">Saldo: 75.00</flux:text>
                    </div>
                </div>
            </div>

            <div class="flex items-start gap-4">
                <div class="w-4 h-4 rounded-full bg-red-500 border-4 border-white dark:border-zinc-900 mt-1"></div>
                <div class="flex-1">
                    <div class="flex items-center gap-2 mb-2">
                        <flux:badge color="red">Salida</flux:badge>
                        <flux:text class="font-medium">Bodega Central</flux:text>
                    </div>
                    <flux:text class="text-xs text-zinc-500">20/11/2025 09:00 • Carlos Lopez</flux:text>
                    <div class="mt-2 flex justify-between">
                        <flux:text class="text-red-600 font-bold">-30.00</flux:text>
                        <flux:text class="text-zinc-500">Saldo: 45.00</flux:text>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Movement Types -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Tipos de Movimiento
        </flux:heading>

        <flux:text>
            Cada movimiento se identifica con un color segun su tipo:
        </flux:text>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <flux:card class="p-4">
                <div class="flex items-center gap-3">
                    <div class="w-4 h-4 rounded-full bg-green-500"></div>
                    <div>
                        <flux:badge color="green" size="sm">Entrada</flux:badge>
                        <flux:text class="text-sm mt-1">Ingreso por compra o donacion</flux:text>
                    </div>
                </div>
            </flux:card>

            <flux:card class="p-4">
                <div class="flex items-center gap-3">
                    <div class="w-4 h-4 rounded-full bg-red-500"></div>
                    <div>
                        <flux:badge color="red" size="sm">Salida</flux:badge>
                        <flux:text class="text-sm mt-1">Egreso por despacho o consumo</flux:text>
                    </div>
                </div>
            </flux:card>

            <flux:card class="p-4">
                <div class="flex items-center gap-3">
                    <div class="w-4 h-4 rounded-full bg-blue-500"></div>
                    <div>
                        <flux:badge color="blue" size="sm">Transferencia</flux:badge>
                        <flux:text class="text-sm mt-1">Movimiento entre bodegas</flux:text>
                    </div>
                </div>
            </flux:card>

            <flux:card class="p-4">
                <div class="flex items-center gap-3">
                    <div class="w-4 h-4 rounded-full bg-orange-500"></div>
                    <div>
                        <flux:badge color="orange" size="sm">Transf. Salida</flux:badge>
                        <flux:text class="text-sm mt-1">Salida hacia otra bodega</flux:text>
                    </div>
                </div>
            </flux:card>

            <flux:card class="p-4">
                <div class="flex items-center gap-3">
                    <div class="w-4 h-4 rounded-full bg-purple-500"></div>
                    <div>
                        <flux:badge color="purple" size="sm">Ajuste</flux:badge>
                        <flux:text class="text-sm mt-1">Correccion de inventario</flux:text>
                    </div>
                </div>
            </flux:card>
        </div>
    </section>

    <!-- Document Information -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Informacion de Documentos
        </flux:heading>

        <flux:text>
            Cada movimiento puede estar vinculado a un documento de origen:
        </flux:text>

        <ul class="space-y-2 text-sm text-zinc-600 dark:text-zinc-400">
            <li class="flex items-center gap-2">
                <flux:icon name="shopping-cart" class="h-4 w-4 text-blue-500" />
                <strong>Compra</strong> - Numero de documento de compra
            </li>
            <li class="flex items-center gap-2">
                <flux:icon name="gift" class="h-4 w-4 text-pink-500" />
                <strong>Donacion</strong> - Referencia de la donacion recibida
            </li>
            <li class="flex items-center gap-2">
                <flux:icon name="truck" class="h-4 w-4 text-orange-500" />
                <strong>Despacho</strong> - Numero de guia de despacho
            </li>
            <li class="flex items-center gap-2">
                <flux:icon name="arrow-path" class="h-4 w-4 text-cyan-500" />
                <strong>Traslado</strong> - Numero de documento de transferencia
            </li>
        </ul>

        <flux:callout variant="info" icon="information-circle">
            Puede hacer clic en el numero de documento para acceder directamente
            al registro original de la transaccion.
        </flux:callout>
    </section>

    <!-- Export -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Exportar Trazabilidad
        </flux:heading>

        <flux:text>
            Puede exportar la linea de tiempo completa para analisis externo:
        </flux:text>

        <div class="flex gap-4">
            <flux:button variant="outline" icon="arrow-down-tray" disabled>
                Exportar Timeline
            </flux:button>
        </div>

        <flux:text class="text-sm text-zinc-500">
            El archivo exportado incluira todos los movimientos visibles con sus detalles completos.
        </flux:text>
    </section>

    <!-- Use Cases -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Casos de Uso
        </flux:heading>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <flux:card class="p-4 border-l-4 border-l-blue-500">
                <flux:heading size="sm" class="mb-2 text-blue-700 dark:text-blue-300">
                    Auditoria de Inventario
                </flux:heading>
                <flux:text class="text-sm">
                    Verifique el historial completo de un producto para auditorias internas o externas.
                </flux:text>
            </flux:card>

            <flux:card class="p-4 border-l-4 border-l-green-500">
                <flux:heading size="sm" class="mb-2 text-green-700 dark:text-green-300">
                    Control de Calidad
                </flux:heading>
                <flux:text class="text-sm">
                    Identifique lotes afectados y rastree su distribucion en caso de problemas de calidad.
                </flux:text>
            </flux:card>

            <flux:card class="p-4 border-l-4 border-l-purple-500">
                <flux:heading size="sm" class="mb-2 text-purple-700 dark:text-purple-300">
                    Investigacion de Discrepancias
                </flux:heading>
                <flux:text class="text-sm">
                    Analice el flujo de un producto para identificar donde ocurrio una diferencia de inventario.
                </flux:text>
            </flux:card>

            <flux:card class="p-4 border-l-4 border-l-orange-500">
                <flux:heading size="sm" class="mb-2 text-orange-700 dark:text-orange-300">
                    Cumplimiento Normativo
                </flux:heading>
                <flux:text class="text-sm">
                    Genere reportes de trazabilidad requeridos por regulaciones gubernamentales.
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
                    Documentar Referencias
                </flux:heading>
                <flux:text class="text-sm">
                    Siempre incluya referencias y notas en los movimientos para facilitar el rastreo.
                </flux:text>
            </flux:card>

            <flux:card class="p-4 border-l-4 border-l-blue-500">
                <flux:heading size="sm" class="mb-2 text-blue-700 dark:text-blue-300">
                    Revisar Periodicamente
                </flux:heading>
                <flux:text class="text-sm">
                    Consulte la trazabilidad regularmente para detectar anomalias temprano.
                </flux:text>
            </flux:card>

            <flux:card class="p-4 border-l-4 border-l-purple-500">
                <flux:heading size="sm" class="mb-2 text-purple-700 dark:text-purple-300">
                    Gestionar Lotes
                </flux:heading>
                <flux:text class="text-sm">
                    Use numeros de lote para productos que requieren trazabilidad granular.
                </flux:text>
            </flux:card>

            <flux:card class="p-4 border-l-4 border-l-orange-500">
                <flux:heading size="sm" class="mb-2 text-orange-700 dark:text-orange-300">
                    Exportar para Respaldo
                </flux:heading>
                <flux:text class="text-sm">
                    Exporte la trazabilidad periodicamente como respaldo de informacion critica.
                </flux:text>
            </flux:card>
        </div>
    </section>
</div>
