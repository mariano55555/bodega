<div class="space-y-8">
    <!-- Module Header -->
    <div class="border-b border-zinc-200 dark:border-zinc-700 pb-6">
        <div class="flex items-center gap-4 mb-4">
            <div class="p-3 bg-red-100 dark:bg-red-900 rounded-lg">
                <flux:icon name="bell" class="h-8 w-8 text-red-600 dark:text-red-400" />
            </div>
            <div>
                <flux:heading size="xl" class="text-zinc-900 dark:text-zinc-100">
                    Alertas de Stock
                </flux:heading>
                <flux:text class="text-zinc-600 dark:text-zinc-400">
                    Monitoreo en tiempo real de niveles de inventario y alertas automaticas
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
            El modulo de Alertas de Stock permite monitorear automaticamente los niveles de inventario
            y recibir notificaciones cuando se detectan situaciones que requieren atencion. El sistema
            verifica continuamente el stock disponible contra los parametros configurados en cada producto.
        </flux:text>

        <flux:callout variant="info" icon="information-circle">
            Las alertas se actualizan automaticamente cada 30 segundos para mantenerle informado
            sobre cambios en tiempo real.
        </flux:callout>
    </section>

    <!-- Access -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Acceso al Modulo
        </flux:heading>

        <flux:text>
            Navegue a <strong>Inventario → Alertas de Stock</strong> desde el menu lateral para acceder
            al panel de alertas.
        </flux:text>
    </section>

    <!-- Alert Types -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Tipos de Alertas
        </flux:heading>

        <flux:text>
            El sistema detecta cuatro tipos diferentes de alertas de stock:
        </flux:text>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <flux:card class="p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800">
                <div class="flex items-start gap-3">
                    <flux:icon name="exclamation-triangle" class="h-6 w-6 text-red-500 shrink-0" />
                    <div>
                        <flux:heading size="sm" class="text-red-700 dark:text-red-300">Stock Bajo</flux:heading>
                        <flux:text class="text-sm mt-2">
                            Productos cuya cantidad disponible esta por debajo del <strong>stock minimo</strong>
                            configurado. Requiere atencion para evitar desabastecimiento.
                        </flux:text>
                        <div class="mt-3 flex items-center gap-2">
                            <flux:badge color="red" size="sm">Critico</flux:badge>
                            <flux:text class="text-xs text-zinc-500">Prioridad alta</flux:text>
                        </div>
                    </div>
                </div>
            </flux:card>

            <flux:card class="p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800">
                <div class="flex items-start gap-3">
                    <flux:icon name="clock" class="h-6 w-6 text-yellow-500 shrink-0" />
                    <div>
                        <flux:heading size="sm" class="text-yellow-700 dark:text-yellow-300">Por Vencer</flux:heading>
                        <flux:text class="text-sm mt-2">
                            Productos con <strong>fecha de vencimiento</strong> dentro de los proximos
                            30 dias. Permite planificar promociones o rotacion de inventario.
                        </flux:text>
                        <div class="mt-3 flex items-center gap-2">
                            <flux:badge color="yellow" size="sm">Urgente</flux:badge>
                            <flux:text class="text-xs text-zinc-500">Prioridad media</flux:text>
                        </div>
                    </div>
                </div>
            </flux:card>

            <flux:card class="p-4 bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-800">
                <div class="flex items-start gap-3">
                    <flux:icon name="arrow-trending-up" class="h-6 w-6 text-orange-500 shrink-0" />
                    <div>
                        <flux:heading size="sm" class="text-orange-700 dark:text-orange-300">Exceso de Stock</flux:heading>
                        <flux:text class="text-sm mt-2">
                            Productos cuya cantidad supera el <strong>stock maximo</strong> configurado.
                            Puede indicar sobrecompra o baja rotacion.
                        </flux:text>
                        <div class="mt-3 flex items-center gap-2">
                            <flux:badge color="orange" size="sm">Atencion</flux:badge>
                            <flux:text class="text-xs text-zinc-500">Prioridad baja</flux:text>
                        </div>
                    </div>
                </div>
            </flux:card>

            <flux:card class="p-4 bg-zinc-50 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700">
                <div class="flex items-start gap-3">
                    <flux:icon name="x-circle" class="h-6 w-6 text-zinc-500 shrink-0" />
                    <div>
                        <flux:heading size="sm" class="text-zinc-700 dark:text-zinc-300">Sin Stock</flux:heading>
                        <flux:text class="text-sm mt-2">
                            Productos con <strong>stock cero</strong> o sin registros de inventario.
                            Requiere reabastecimiento inmediato si el producto esta activo.
                        </flux:text>
                        <div class="mt-3 flex items-center gap-2">
                            <flux:badge color="zinc" size="sm">Agotado</flux:badge>
                            <flux:text class="text-xs text-zinc-500">Prioridad variable</flux:text>
                        </div>
                    </div>
                </div>
            </flux:card>
        </div>
    </section>

    <!-- Filter Controls -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Filtros Disponibles
        </flux:heading>

        <flux:text>
            Puede filtrar las alertas para enfocarse en situaciones especificas:
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
                        <td class="px-4 py-3 font-medium">Busqueda</td>
                        <td class="px-4 py-3">Buscar por nombre de producto o codigo SKU</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3 font-medium">Bodega</td>
                        <td class="px-4 py-3">Filtrar alertas por bodega especifica o ver todas</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>

    <!-- Tabs Navigation -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Navegacion por Pestanas
        </flux:heading>

        <flux:text>
            Use las pestanas para cambiar entre los diferentes tipos de alertas:
        </flux:text>

        <div class="flex flex-wrap gap-2">
            <flux:button variant="primary" size="sm">
                <flux:icon name="exclamation-triangle" class="h-4 w-4" />
                Stock Bajo
                <flux:badge color="red" size="sm">5</flux:badge>
            </flux:button>

            <flux:button variant="ghost" size="sm">
                <flux:icon name="clock" class="h-4 w-4" />
                Por Vencer
                <flux:badge color="yellow" size="sm">3</flux:badge>
            </flux:button>

            <flux:button variant="ghost" size="sm">
                <flux:icon name="arrow-trending-up" class="h-4 w-4" />
                Exceso de Stock
                <flux:badge color="orange" size="sm">2</flux:badge>
            </flux:button>

            <flux:button variant="ghost" size="sm">
                <flux:icon name="x-circle" class="h-4 w-4" />
                Sin Stock
                <flux:badge color="zinc" size="sm">8</flux:badge>
            </flux:button>
        </div>

        <flux:text class="text-sm text-zinc-500">
            El numero en cada pestana indica la cantidad de alertas activas de ese tipo.
        </flux:text>
    </section>

    <!-- Alert Details -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Informacion de Alertas
        </flux:heading>

        <flux:text>
            Cada alerta muestra informacion detallada del producto afectado:
        </flux:text>

        <div class="bg-red-50 dark:bg-red-900/20 rounded-lg p-4 border border-red-200 dark:border-red-800">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <flux:icon name="exclamation-triangle" class="h-6 w-6 text-red-500" />
                    <div>
                        <flux:heading size="sm">Aceite de Cocina 1L</flux:heading>
                        <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                            SKU-001 • Bodega Central
                        </flux:text>
                    </div>
                </div>
                <div class="text-right">
                    <flux:badge color="red" size="sm">Stock Bajo</flux:badge>
                    <flux:text class="text-sm block mt-1">Actual: 15.00</flux:text>
                    <flux:text class="text-sm text-zinc-500">Minimo: 50.00</flux:text>
                </div>
            </div>
        </div>

        <ul class="space-y-2 text-sm text-zinc-600 dark:text-zinc-400">
            <li class="flex items-center gap-2">
                <flux:icon name="cube" class="h-4 w-4 text-zinc-500" />
                <strong>Nombre del producto</strong> - Identificacion principal
            </li>
            <li class="flex items-center gap-2">
                <flux:icon name="hashtag" class="h-4 w-4 text-zinc-500" />
                <strong>SKU</strong> - Codigo unico del producto
            </li>
            <li class="flex items-center gap-2">
                <flux:icon name="building-office" class="h-4 w-4 text-zinc-500" />
                <strong>Bodega</strong> - Ubicacion del inventario afectado
            </li>
            <li class="flex items-center gap-2">
                <flux:icon name="calculator" class="h-4 w-4 text-zinc-500" />
                <strong>Cantidad actual vs. parametro</strong> - Comparacion de valores
            </li>
        </ul>
    </section>

    <!-- Expiring Products -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Alertas de Vencimiento
        </flux:heading>

        <flux:text>
            Las alertas de productos por vencer incluyen informacion adicional:
        </flux:text>

        <div class="bg-yellow-50 dark:bg-yellow-900/20 rounded-lg p-4 border border-yellow-200 dark:border-yellow-800">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <flux:icon name="clock" class="h-6 w-6 text-yellow-500" />
                    <div>
                        <flux:heading size="sm">Leche en Polvo 400g</flux:heading>
                        <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                            SKU-025 • Bodega Norte
                        </flux:text>
                        <flux:text class="text-xs text-zinc-500">Lote: LOT-2024-1125</flux:text>
                    </div>
                </div>
                <div class="text-right">
                    <flux:badge color="red" size="sm">7 dias</flux:badge>
                    <flux:text class="text-sm block mt-1">30/11/2025</flux:text>
                    <flux:text class="text-sm text-zinc-500">25.00 unidades</flux:text>
                </div>
            </div>
        </div>

        <flux:text class="text-sm">
            Los colores de urgencia varian segun los dias restantes:
        </flux:text>

        <div class="flex flex-wrap gap-4">
            <div class="flex items-center gap-2">
                <flux:badge color="red" size="sm">0-7 dias</flux:badge>
                <flux:text class="text-sm">Urgente</flux:text>
            </div>
            <div class="flex items-center gap-2">
                <flux:badge color="yellow" size="sm">8-14 dias</flux:badge>
                <flux:text class="text-sm">Proximo</flux:text>
            </div>
            <div class="flex items-center gap-2">
                <flux:badge color="blue" size="sm">15-30 dias</flux:badge>
                <flux:text class="text-sm">Planificable</flux:text>
            </div>
        </div>
    </section>

    <!-- Quick Actions -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Acciones Rapidas
        </flux:heading>

        <flux:text>
            Desde el panel de alertas puede acceder rapidamente a las operaciones mas comunes:
        </flux:text>

        <div class="flex flex-wrap gap-4">
            <flux:button variant="primary" icon="plus">
                Registrar Entrada
            </flux:button>
            <flux:button variant="outline" icon="arrow-path">
                Crear Traslado
            </flux:button>
            <flux:button variant="outline" icon="chart-bar">
                Exportar Reporte
            </flux:button>
        </div>

        <flux:text class="text-sm text-zinc-500">
            Estas acciones le permiten responder inmediatamente a las alertas detectadas.
        </flux:text>
    </section>

    <!-- Configuration -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Configuracion de Parametros
        </flux:heading>

        <flux:text>
            Los parametros que activan las alertas se configuran en cada producto:
        </flux:text>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-zinc-100 dark:bg-zinc-800">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium">Parametro</th>
                        <th class="px-4 py-3 text-left font-medium">Donde Configurar</th>
                        <th class="px-4 py-3 text-left font-medium">Efecto</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    <tr>
                        <td class="px-4 py-3 font-medium">Stock Minimo</td>
                        <td class="px-4 py-3">Producto → Editar → Stock Minimo</td>
                        <td class="px-4 py-3">Activa alerta de Stock Bajo</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3 font-medium">Stock Maximo</td>
                        <td class="px-4 py-3">Producto → Editar → Stock Maximo</td>
                        <td class="px-4 py-3">Activa alerta de Exceso de Stock</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3 font-medium">Fecha Vencimiento</td>
                        <td class="px-4 py-3">Movimiento → Entrada → Fecha Vencimiento</td>
                        <td class="px-4 py-3">Activa alerta de Por Vencer</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <flux:callout variant="warning" icon="exclamation-triangle">
            Si un producto no tiene configurado el stock minimo o maximo, no generara alertas
            de Stock Bajo o Exceso de Stock respectivamente.
        </flux:callout>
    </section>

    <!-- Empty States -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Estados Sin Alertas
        </flux:heading>

        <flux:text>
            Cuando no hay alertas de un tipo especifico, vera un mensaje de confirmacion:
        </flux:text>

        <div class="text-center py-8 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800">
            <flux:icon name="check-circle" class="h-12 w-12 text-green-400 mx-auto mb-3" />
            <flux:heading size="lg" class="text-green-600 dark:text-green-400 mb-2">
                Todo en orden!
            </flux:heading>
            <flux:text class="text-zinc-500">
                No hay productos por debajo del nivel minimo de stock
            </flux:text>
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
                    Revision Diaria
                </flux:heading>
                <flux:text class="text-sm">
                    Revise las alertas de stock al inicio de cada jornada laboral para anticipar problemas.
                </flux:text>
            </flux:card>

            <flux:card class="p-4 border-l-4 border-l-blue-500">
                <flux:heading size="sm" class="mb-2 text-blue-700 dark:text-blue-300">
                    Configurar Parametros
                </flux:heading>
                <flux:text class="text-sm">
                    Defina stock minimo y maximo en todos los productos para maximizar la utilidad del sistema.
                </flux:text>
            </flux:card>

            <flux:card class="p-4 border-l-4 border-l-purple-500">
                <flux:heading size="sm" class="mb-2 text-purple-700 dark:text-purple-300">
                    Priorizar Criticos
                </flux:heading>
                <flux:text class="text-sm">
                    Atienda primero las alertas de Stock Bajo y Por Vencer (menos de 7 dias).
                </flux:text>
            </flux:card>

            <flux:card class="p-4 border-l-4 border-l-orange-500">
                <flux:heading size="sm" class="mb-2 text-orange-700 dark:text-orange-300">
                    Documentar Acciones
                </flux:heading>
                <flux:text class="text-sm">
                    Al resolver una alerta, registre la accion tomada para trazabilidad.
                </flux:text>
            </flux:card>
        </div>
    </section>
</div>
