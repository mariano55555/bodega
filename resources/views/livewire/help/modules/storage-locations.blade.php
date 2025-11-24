<div class="space-y-8">
    <!-- Module Header -->
    <div class="border-b border-zinc-200 dark:border-zinc-700 pb-6">
        <div class="flex items-center gap-4 mb-4">
            <div class="p-3 bg-amber-100 dark:bg-amber-900 rounded-lg">
                <flux:icon name="map-pin" class="h-8 w-8 text-amber-600 dark:text-amber-400" />
            </div>
            <div>
                <flux:heading size="xl" class="text-zinc-900 dark:text-zinc-100">
                    Ubicaciones de Almacenamiento
                </flux:heading>
                <flux:text class="text-zinc-600 dark:text-zinc-400">
                    Organiza y gestiona las ubicaciones dentro de tus bodegas
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
            Las ubicaciones de almacenamiento permiten organizar el espacio dentro de cada bodega de manera
            jerarquica y estructurada. Esto facilita el control del inventario, la localizacion rapida de
            productos y el monitoreo de la capacidad utilizada.
        </flux:text>

        <flux:callout variant="info" icon="information-circle">
            Una buena organizacion de ubicaciones permite operaciones de picking mas eficientes y un mejor
            control de la capacidad de almacenamiento.
        </flux:callout>
    </section>

    <!-- Location Types -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Tipos de Ubicacion
        </flux:heading>

        <flux:text>
            El sistema soporta diferentes tipos de ubicaciones para adaptarse a la estructura de tu bodega:
        </flux:text>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <flux:card class="p-4 border-l-4 border-l-blue-500">
                <flux:heading size="md" class="mb-2 flex items-center gap-2">
                    <flux:icon name="squares-2x2" class="h-5 w-5 text-blue-600" />
                    Zona
                </flux:heading>
                <flux:text class="text-sm">
                    Area grande dentro de la bodega. Ejemplo: Zona de Recepcion, Zona de Despacho,
                    Zona de Almacenamiento General.
                </flux:text>
            </flux:card>

            <flux:card class="p-4 border-l-4 border-l-green-500">
                <flux:heading size="md" class="mb-2 flex items-center gap-2">
                    <flux:icon name="arrows-right-left" class="h-5 w-5 text-green-600" />
                    Pasillo
                </flux:heading>
                <flux:text class="text-sm">
                    Corredor entre estanterias. Generalmente identificado con letras o numeros
                    (A, B, C o 1, 2, 3).
                </flux:text>
            </flux:card>

            <flux:card class="p-4 border-l-4 border-l-purple-500">
                <flux:heading size="md" class="mb-2 flex items-center gap-2">
                    <flux:icon name="rectangle-stack" class="h-5 w-5 text-purple-600" />
                    Estante
                </flux:heading>
                <flux:text class="text-sm">
                    Estructura vertical con multiples niveles. Cada estante puede tener varias
                    posiciones o contenedores.
                </flux:text>
            </flux:card>

            <flux:card class="p-4 border-l-4 border-l-orange-500">
                <flux:heading size="md" class="mb-2 flex items-center gap-2">
                    <flux:icon name="cube" class="h-5 w-5 text-orange-600" />
                    Contenedor
                </flux:heading>
                <flux:text class="text-sm">
                    Posicion especifica para almacenar productos. Es el nivel mas detallado
                    de ubicacion (bin location).
                </flux:text>
            </flux:card>

            <flux:card class="p-4 border-l-4 border-l-cyan-500">
                <flux:heading size="md" class="mb-2 flex items-center gap-2">
                    <flux:icon name="truck" class="h-5 w-5 text-cyan-600" />
                    Muelle
                </flux:heading>
                <flux:text class="text-sm">
                    Area de carga y descarga de vehiculos. Usado para recepciones y despachos
                    de mercaderia.
                </flux:text>
            </flux:card>

            <flux:card class="p-4 border-l-4 border-l-pink-500">
                <flux:heading size="md" class="mb-2 flex items-center gap-2">
                    <flux:icon name="clipboard-document-list" class="h-5 w-5 text-pink-600" />
                    Preparacion
                </flux:heading>
                <flux:text class="text-sm">
                    Area temporal para preparar pedidos (staging area). Productos en proceso
                    de picking o empaque.
                </flux:text>
            </flux:card>
        </div>
    </section>

    <!-- Hierarchy -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Jerarquia de Ubicaciones
        </flux:heading>

        <flux:text>
            Las ubicaciones pueden organizarse jerarquicamente, donde una ubicacion padre contiene
            ubicaciones hijas. Esto permite una navegacion logica del espacio.
        </flux:text>

        <div class="bg-gradient-to-r from-amber-50 to-orange-50 dark:from-amber-950 dark:to-orange-950 p-6 rounded-lg">
            <div class="space-y-3">
                <!-- Zona -->
                <div class="bg-white dark:bg-zinc-800 p-4 rounded-lg shadow-sm border-2 border-blue-200 dark:border-blue-800">
                    <div class="flex items-center gap-3">
                        <flux:icon name="squares-2x2" class="h-6 w-6 text-blue-600" />
                        <div>
                            <flux:text class="font-bold text-blue-800 dark:text-blue-200">Zona A - Almacenamiento General</flux:text>
                            <flux:text class="text-xs text-zinc-500">Tipo: Zona | Codigo: Z-A</flux:text>
                        </div>
                    </div>
                </div>

                <!-- Pasillos -->
                <div class="ml-8 grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div class="bg-white dark:bg-zinc-800 p-3 rounded-lg shadow-sm border border-green-200 dark:border-green-800">
                        <div class="flex items-center gap-2">
                            <flux:icon name="arrows-right-left" class="h-5 w-5 text-green-600" />
                            <div>
                                <flux:text class="font-medium text-green-800 dark:text-green-200">Pasillo 1</flux:text>
                                <flux:text class="text-xs text-zinc-500">Codigo: Z-A-01</flux:text>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white dark:bg-zinc-800 p-3 rounded-lg shadow-sm border border-green-200 dark:border-green-800">
                        <div class="flex items-center gap-2">
                            <flux:icon name="arrows-right-left" class="h-5 w-5 text-green-600" />
                            <div>
                                <flux:text class="font-medium text-green-800 dark:text-green-200">Pasillo 2</flux:text>
                                <flux:text class="text-xs text-zinc-500">Codigo: Z-A-02</flux:text>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Estantes -->
                <div class="ml-16 grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div class="bg-white dark:bg-zinc-800 p-3 rounded-lg shadow-sm border border-purple-200 dark:border-purple-800">
                        <div class="flex items-center gap-2">
                            <flux:icon name="rectangle-stack" class="h-4 w-4 text-purple-600" />
                            <div>
                                <flux:text class="text-sm font-medium text-purple-800 dark:text-purple-200">Estante A</flux:text>
                                <flux:text class="text-xs text-zinc-500">Z-A-01-A</flux:text>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white dark:bg-zinc-800 p-3 rounded-lg shadow-sm border border-purple-200 dark:border-purple-800">
                        <div class="flex items-center gap-2">
                            <flux:icon name="rectangle-stack" class="h-4 w-4 text-purple-600" />
                            <div>
                                <flux:text class="text-sm font-medium text-purple-800 dark:text-purple-200">Estante B</flux:text>
                                <flux:text class="text-xs text-zinc-500">Z-A-01-B</flux:text>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white dark:bg-zinc-800 p-3 rounded-lg shadow-sm border border-purple-200 dark:border-purple-800">
                        <div class="flex items-center gap-2">
                            <flux:icon name="rectangle-stack" class="h-4 w-4 text-purple-600" />
                            <div>
                                <flux:text class="text-sm font-medium text-purple-800 dark:text-purple-200">Estante C</flux:text>
                                <flux:text class="text-xs text-zinc-500">Z-A-01-C</flux:text>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Contenedores -->
                <div class="ml-24 grid grid-cols-2 md:grid-cols-4 gap-2">
                    <div class="bg-white dark:bg-zinc-800 p-2 rounded-lg shadow-sm border border-orange-200 dark:border-orange-800">
                        <div class="flex items-center gap-1">
                            <flux:icon name="cube" class="h-3 w-3 text-orange-600" />
                            <flux:text class="text-xs font-medium text-orange-800 dark:text-orange-200">Bin 01</flux:text>
                        </div>
                    </div>
                    <div class="bg-white dark:bg-zinc-800 p-2 rounded-lg shadow-sm border border-orange-200 dark:border-orange-800">
                        <div class="flex items-center gap-1">
                            <flux:icon name="cube" class="h-3 w-3 text-orange-600" />
                            <flux:text class="text-xs font-medium text-orange-800 dark:text-orange-200">Bin 02</flux:text>
                        </div>
                    </div>
                    <div class="bg-white dark:bg-zinc-800 p-2 rounded-lg shadow-sm border border-orange-200 dark:border-orange-800">
                        <div class="flex items-center gap-1">
                            <flux:icon name="cube" class="h-3 w-3 text-orange-600" />
                            <flux:text class="text-xs font-medium text-orange-800 dark:text-orange-200">Bin 03</flux:text>
                        </div>
                    </div>
                    <div class="bg-white dark:bg-zinc-800 p-2 rounded-lg shadow-sm border border-orange-200 dark:border-orange-800">
                        <div class="flex items-center gap-1">
                            <flux:icon name="cube" class="h-3 w-3 text-orange-600" />
                            <flux:text class="text-xs font-medium text-orange-800 dark:text-orange-200">Bin 04</flux:text>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Location Fields -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Campos de la Ubicacion
        </flux:heading>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <flux:card class="p-4">
                <flux:heading size="md" class="mb-3 flex items-center gap-2">
                    <flux:icon name="identification" class="h-5 w-5 text-blue-600" />
                    Informacion Basica
                </flux:heading>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between py-1 border-b border-zinc-100 dark:border-zinc-800">
                        <span class="text-zinc-600 dark:text-zinc-400">Codigo</span>
                        <span class="font-medium">Unico, Requerido</span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-zinc-100 dark:border-zinc-800">
                        <span class="text-zinc-600 dark:text-zinc-400">Nombre</span>
                        <span class="font-medium">Requerido</span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-zinc-100 dark:border-zinc-800">
                        <span class="text-zinc-600 dark:text-zinc-400">Bodega</span>
                        <span class="font-medium">Requerido</span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-zinc-100 dark:border-zinc-800">
                        <span class="text-zinc-600 dark:text-zinc-400">Tipo</span>
                        <span class="font-medium">Zona/Pasillo/Estante/etc.</span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-zinc-100 dark:border-zinc-800">
                        <span class="text-zinc-600 dark:text-zinc-400">Ubicacion Padre</span>
                        <span class="font-medium">Opcional (jerarquia)</span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-zinc-100 dark:border-zinc-800">
                        <span class="text-zinc-600 dark:text-zinc-400">Descripcion</span>
                        <span class="font-medium">Opcional</span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-zinc-100 dark:border-zinc-800">
                        <span class="text-zinc-600 dark:text-zinc-400">Codigo de Barras</span>
                        <span class="font-medium">Opcional</span>
                    </div>
                </div>
            </flux:card>

            <flux:card class="p-4">
                <flux:heading size="md" class="mb-3 flex items-center gap-2">
                    <flux:icon name="cube" class="h-5 w-5 text-purple-600" />
                    Capacidad y Dimensiones
                </flux:heading>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between py-1 border-b border-zinc-100 dark:border-zinc-800">
                        <span class="text-zinc-600 dark:text-zinc-400">Capacidad</span>
                        <span class="font-medium">Numerico</span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-zinc-100 dark:border-zinc-800">
                        <span class="text-zinc-600 dark:text-zinc-400">Unidad de Capacidad</span>
                        <span class="font-medium">unidades, kg, m3, etc.</span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-zinc-100 dark:border-zinc-800">
                        <span class="text-zinc-600 dark:text-zinc-400">Peso Maximo</span>
                        <span class="font-medium">kg</span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-zinc-100 dark:border-zinc-800">
                        <span class="text-zinc-600 dark:text-zinc-400">Largo</span>
                        <span class="font-medium">metros</span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-zinc-100 dark:border-zinc-800">
                        <span class="text-zinc-600 dark:text-zinc-400">Ancho</span>
                        <span class="font-medium">metros</span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-zinc-100 dark:border-zinc-800">
                        <span class="text-zinc-600 dark:text-zinc-400">Alto</span>
                        <span class="font-medium">metros</span>
                    </div>
                </div>
            </flux:card>

            <flux:card class="p-4">
                <flux:heading size="md" class="mb-3 flex items-center gap-2">
                    <flux:icon name="map-pin" class="h-5 w-5 text-green-600" />
                    Ubicacion Fisica
                </flux:heading>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between py-1 border-b border-zinc-100 dark:border-zinc-800">
                        <span class="text-zinc-600 dark:text-zinc-400">Seccion</span>
                        <span class="font-medium">Opcional</span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-zinc-100 dark:border-zinc-800">
                        <span class="text-zinc-600 dark:text-zinc-400">Pasillo</span>
                        <span class="font-medium">Opcional</span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-zinc-100 dark:border-zinc-800">
                        <span class="text-zinc-600 dark:text-zinc-400">Estante</span>
                        <span class="font-medium">Opcional</span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-zinc-100 dark:border-zinc-800">
                        <span class="text-zinc-600 dark:text-zinc-400">Contenedor</span>
                        <span class="font-medium">Opcional</span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-zinc-100 dark:border-zinc-800">
                        <span class="text-zinc-600 dark:text-zinc-400">Nivel</span>
                        <span class="font-medium">Numerico</span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-zinc-100 dark:border-zinc-800">
                        <span class="text-zinc-600 dark:text-zinc-400">Orden</span>
                        <span class="font-medium">Para ordenamiento</span>
                    </div>
                </div>
            </flux:card>

            <flux:card class="p-4">
                <flux:heading size="md" class="mb-3 flex items-center gap-2">
                    <flux:icon name="cog" class="h-5 w-5 text-orange-600" />
                    Configuracion
                </flux:heading>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between py-1 border-b border-zinc-100 dark:border-zinc-800">
                        <span class="text-zinc-600 dark:text-zinc-400">Estado</span>
                        <span class="font-medium">Activo/Inactivo</span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-zinc-100 dark:border-zinc-800">
                        <span class="text-zinc-600 dark:text-zinc-400">Ubicacion de Picking</span>
                        <span class="font-medium">Si/No</span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-zinc-100 dark:border-zinc-800">
                        <span class="text-zinc-600 dark:text-zinc-400">Ubicacion de Recepcion</span>
                        <span class="font-medium">Si/No</span>
                    </div>
                </div>
            </flux:card>
        </div>
    </section>

    <!-- Picking and Receiving Flags -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Flags de Picking y Recepcion
        </flux:heading>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <flux:card class="p-4 border-l-4 border-l-blue-500">
                <flux:heading size="md" class="mb-3 flex items-center gap-2">
                    <flux:badge color="blue" size="sm">Picking</flux:badge>
                    Ubicacion de Picking
                </flux:heading>
                <flux:text class="text-sm mb-3">
                    Ubicaciones marcadas como "pickable" son elegibles para operaciones de preparacion
                    de pedidos. El sistema sugiere estas ubicaciones cuando se preparan despachos.
                </flux:text>
                <ul class="space-y-1 text-sm text-zinc-600 dark:text-zinc-400">
                    <li class="flex items-center gap-2">
                        <flux:icon name="check" class="h-4 w-4 text-blue-500" />
                        Acceso rapido para preparacion
                    </li>
                    <li class="flex items-center gap-2">
                        <flux:icon name="check" class="h-4 w-4 text-blue-500" />
                        Prioridad en busqueda de stock
                    </li>
                    <li class="flex items-center gap-2">
                        <flux:icon name="check" class="h-4 w-4 text-blue-500" />
                        Ideal para contenedores de nivel bajo
                    </li>
                </ul>
            </flux:card>

            <flux:card class="p-4 border-l-4 border-l-purple-500">
                <flux:heading size="md" class="mb-3 flex items-center gap-2">
                    <flux:badge color="purple" size="sm">Recepcion</flux:badge>
                    Ubicacion de Recepcion
                </flux:heading>
                <flux:text class="text-sm mb-3">
                    Ubicaciones marcadas como "receivable" son elegibles para recibir mercaderia
                    entrante. Ideales para areas de muelles y zonas de staging.
                </flux:text>
                <ul class="space-y-1 text-sm text-zinc-600 dark:text-zinc-400">
                    <li class="flex items-center gap-2">
                        <flux:icon name="check" class="h-4 w-4 text-purple-500" />
                        Sugeridas en recepciones de compra
                    </li>
                    <li class="flex items-center gap-2">
                        <flux:icon name="check" class="h-4 w-4 text-purple-500" />
                        Prioridad para donaciones entrantes
                    </li>
                    <li class="flex items-center gap-2">
                        <flux:icon name="check" class="h-4 w-4 text-purple-500" />
                        Areas temporales de descarga
                    </li>
                </ul>
            </flux:card>
        </div>
    </section>

    <!-- Capacity Management -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Gestion de Capacidad
        </flux:heading>

        <flux:text>
            Cada ubicacion puede tener una capacidad definida. El sistema calcula automaticamente
            la utilizacion basada en el inventario almacenado en cada ubicacion.
        </flux:text>

        <div class="bg-zinc-50 dark:bg-zinc-800 p-4 rounded-lg">
            <flux:heading size="sm" class="mb-3">Calculo de Utilizacion</flux:heading>
            <div class="space-y-3">
                <div class="flex items-center gap-4">
                    <flux:badge color="zinc" size="sm">Formula</flux:badge>
                    <flux:text class="font-mono text-sm">
                        Utilizacion (%) = (Cantidad en Inventario / Capacidad Total) x 100
                    </flux:text>
                </div>
                <div class="flex items-center gap-4">
                    <flux:badge color="zinc" size="sm">Ejemplo</flux:badge>
                    <flux:text class="text-sm">
                        Si una ubicacion tiene capacidad de 100 unidades y contiene 75 unidades,
                        la utilizacion es 75%
                    </flux:text>
                </div>
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
                        <th class="px-4 py-3 text-left font-medium">Accion Recomendada</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    <tr>
                        <td class="px-4 py-3">
                            <flux:badge color="green">Verde</flux:badge>
                        </td>
                        <td class="px-4 py-3">0% - 74%</td>
                        <td class="px-4 py-3">Espacio disponible suficiente</td>
                        <td class="px-4 py-3 text-zinc-500">Operacion normal</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3">
                            <flux:badge color="yellow">Amarillo</flux:badge>
                        </td>
                        <td class="px-4 py-3">75% - 89%</td>
                        <td class="px-4 py-3">Espacio limitado</td>
                        <td class="px-4 py-3 text-zinc-500">Planificar redistribucion</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3">
                            <flux:badge color="red">Rojo</flux:badge>
                        </td>
                        <td class="px-4 py-3">90% - 100%</td>
                        <td class="px-4 py-3">Capacidad critica</td>
                        <td class="px-4 py-3 text-zinc-500">Accion inmediata requerida</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <flux:callout variant="info" icon="information-circle">
            La utilizacion se calcula en tiempo real sumando las cantidades del inventario activo
            en cada ubicacion. El formato mostrado es: <strong>Usado / Total (Disponible)</strong>.
        </flux:callout>
    </section>

    <!-- Actions -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Acciones Disponibles
        </flux:heading>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <flux:card class="p-4">
                <flux:heading size="sm" class="mb-2 text-green-700 dark:text-green-300">
                    Desde el Listado
                </flux:heading>
                <ul class="space-y-2 text-sm">
                    <li class="flex items-center gap-2">
                        <flux:icon name="eye" class="h-4 w-4 text-blue-500" />
                        Ver detalles de ubicacion
                    </li>
                    <li class="flex items-center gap-2">
                        <flux:icon name="pencil" class="h-4 w-4 text-amber-500" />
                        Editar ubicacion
                    </li>
                    <li class="flex items-center gap-2">
                        <flux:icon name="check-circle" class="h-4 w-4 text-green-500" />
                        Activar/Desactivar ubicacion
                    </li>
                    <li class="flex items-center gap-2">
                        <flux:icon name="trash" class="h-4 w-4 text-red-500" />
                        Eliminar ubicacion (con confirmacion)
                    </li>
                </ul>
            </flux:card>

            <flux:card class="p-4">
                <flux:heading size="sm" class="mb-2 text-blue-700 dark:text-blue-300">
                    Filtros de Busqueda
                </flux:heading>
                <ul class="space-y-2 text-sm">
                    <li class="flex items-center gap-2">
                        <flux:icon name="magnifying-glass" class="h-4 w-4 text-zinc-500" />
                        Buscar por codigo, nombre o descripcion
                    </li>
                    <li class="flex items-center gap-2">
                        <flux:icon name="building-office" class="h-4 w-4 text-zinc-500" />
                        Filtrar por bodega
                    </li>
                    <li class="flex items-center gap-2">
                        <flux:icon name="tag" class="h-4 w-4 text-zinc-500" />
                        Filtrar por tipo de ubicacion
                    </li>
                    <li class="flex items-center gap-2">
                        <flux:icon name="check" class="h-4 w-4 text-zinc-500" />
                        Filtrar por estado (activo/inactivo)
                    </li>
                </ul>
            </flux:card>
        </div>
    </section>

    <!-- Detail View -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Vista de Detalle
        </flux:heading>

        <flux:text>
            La vista de detalle muestra toda la informacion de la ubicacion organizada en secciones:
        </flux:text>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="space-y-2">
                <flux:heading size="sm" class="text-zinc-700 dark:text-zinc-300">Informacion General</flux:heading>
                <ul class="space-y-1 text-sm text-zinc-600 dark:text-zinc-400">
                    <li>- Codigo y nombre de la ubicacion</li>
                    <li>- Bodega a la que pertenece</li>
                    <li>- Tipo de ubicacion</li>
                    <li>- Ubicacion padre (si existe)</li>
                    <li>- Descripcion</li>
                    <li>- Codigo de barras</li>
                    <li>- Ubicacion fisica detallada</li>
                </ul>
            </div>

            <div class="space-y-2">
                <flux:heading size="sm" class="text-zinc-700 dark:text-zinc-300">Capacidad y Configuracion</flux:heading>
                <ul class="space-y-1 text-sm text-zinc-600 dark:text-zinc-400">
                    <li>- Capacidad total y unidad</li>
                    <li>- Peso maximo permitido</li>
                    <li>- Dimensiones (L x A x H)</li>
                    <li>- Nivel y orden de la ubicacion</li>
                    <li>- Flags de picking y recepcion</li>
                    <li>- Lista de ubicaciones hijas</li>
                </ul>
            </div>
        </div>

        <flux:callout variant="info" icon="information-circle">
            Desde la vista de detalle puede acceder rapidamente a las ubicaciones hijas haciendo
            clic en cada una de ellas, asi como navegar a la ubicacion padre.
        </flux:callout>
    </section>

    <!-- Best Practices -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Mejores Practicas
        </flux:heading>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <flux:card class="p-4 border-l-4 border-l-green-500">
                <flux:heading size="sm" class="mb-2 text-green-700 dark:text-green-300">
                    Codigos Consistentes
                </flux:heading>
                <flux:text class="text-sm">
                    Use un esquema de codificacion consistente que refleje la jerarquia.
                    Ejemplo: BOD-ZA-P01-E01-B01 (Bodega-Zona-Pasillo-Estante-Bin).
                </flux:text>
            </flux:card>

            <flux:card class="p-4 border-l-4 border-l-blue-500">
                <flux:heading size="sm" class="mb-2 text-blue-700 dark:text-blue-300">
                    Definir Capacidades
                </flux:heading>
                <flux:text class="text-sm">
                    Siempre establezca la capacidad de las ubicaciones de nivel mas bajo
                    (contenedores) para un monitoreo preciso de utilizacion.
                </flux:text>
            </flux:card>

            <flux:card class="p-4 border-l-4 border-l-purple-500">
                <flux:heading size="sm" class="mb-2 text-purple-700 dark:text-purple-300">
                    Usar Codigos de Barras
                </flux:heading>
                <flux:text class="text-sm">
                    Asigne codigos de barras unicos a cada ubicacion para facilitar
                    el escaneo durante operaciones de picking y recepcion.
                </flux:text>
            </flux:card>

            <flux:card class="p-4 border-l-4 border-l-orange-500">
                <flux:heading size="sm" class="mb-2 text-orange-700 dark:text-orange-300">
                    Configurar Flags Correctamente
                </flux:heading>
                <flux:text class="text-sm">
                    Marque las ubicaciones apropiadas como "pickable" y "receivable"
                    para optimizar las sugerencias del sistema.
                </flux:text>
            </flux:card>
        </div>
    </section>

    <!-- Integration -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Integracion con Otros Modulos
        </flux:heading>

        <div class="bg-zinc-50 dark:bg-zinc-800 p-4 rounded-lg">
            <div class="grid grid-cols-2 md:grid-cols-3 gap-4 text-sm">
                <div class="flex items-center gap-2">
                    <flux:icon name="squares-2x2" class="h-5 w-5 text-emerald-500" />
                    <span>Inventario (stock por ubicacion)</span>
                </div>
                <div class="flex items-center gap-2">
                    <flux:icon name="shopping-cart" class="h-5 w-5 text-blue-500" />
                    <span>Compras (recepcion)</span>
                </div>
                <div class="flex items-center gap-2">
                    <flux:icon name="gift" class="h-5 w-5 text-pink-500" />
                    <span>Donaciones (recepcion)</span>
                </div>
                <div class="flex items-center gap-2">
                    <flux:icon name="truck" class="h-5 w-5 text-orange-500" />
                    <span>Despachos (picking)</span>
                </div>
                <div class="flex items-center gap-2">
                    <flux:icon name="arrows-right-left" class="h-5 w-5 text-cyan-500" />
                    <span>Transferencias</span>
                </div>
                <div class="flex items-center gap-2">
                    <flux:icon name="chart-pie" class="h-5 w-5 text-purple-500" />
                    <span>Capacidad de Almacenes</span>
                </div>
            </div>
        </div>
    </section>
</div>
