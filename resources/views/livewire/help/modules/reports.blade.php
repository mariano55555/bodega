<div class="space-y-8">
    <!-- Module Header -->
    <div class="border-b border-zinc-200 dark:border-zinc-700 pb-6">
        <div class="flex items-center gap-4 mb-4">
            <div class="p-3 bg-teal-100 dark:bg-teal-900 rounded-lg">
                <flux:icon name="chart-bar" class="h-8 w-8 text-teal-600 dark:text-teal-400" />
            </div>
            <div>
                <flux:heading size="xl" class="text-zinc-900 dark:text-zinc-100">
                    Reportes y Analisis
                </flux:heading>
                <flux:text class="text-zinc-600 dark:text-zinc-400">
                    Sistema completo de informes, estadisticas y analisis de datos de inventario
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
            El modulo de Reportes y Analisis proporciona herramientas completas para generar informes
            detallados sobre el estado y movimiento del inventario. Los reportes estan organizados por
            categorias y pueden exportarse en formatos PDF y Excel para analisis externo.
        </flux:text>

        <flux:callout variant="info" icon="information-circle">
            Todos los reportes respetan los permisos de acceso del usuario. Solo vera datos
            de las empresas y bodegas a las que tiene acceso autorizado.
        </flux:callout>
    </section>

    <!-- Report Categories -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Categorias de Reportes
        </flux:heading>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <!-- Kardex -->
            <flux:card class="p-4">
                <div class="flex items-start gap-3">
                    <div class="p-2 bg-blue-100 dark:bg-blue-900 rounded-lg">
                        <flux:icon name="document-text" class="h-5 w-5 text-blue-500" />
                    </div>
                    <div>
                        <flux:heading size="sm">Kardex</flux:heading>
                        <flux:text class="text-sm mt-1">
                            Registro detallado de movimientos por producto y almacen
                        </flux:text>
                    </div>
                </div>
            </flux:card>

            <!-- Inventory -->
            <flux:card class="p-4">
                <div class="flex items-start gap-3">
                    <div class="p-2 bg-green-100 dark:bg-green-900 rounded-lg">
                        <flux:icon name="cube" class="h-5 w-5 text-green-500" />
                    </div>
                    <div>
                        <flux:heading size="sm">Inventario</flux:heading>
                        <flux:text class="text-sm mt-1">
                            Estado actual, valorizacion y rotacion del inventario
                        </flux:text>
                    </div>
                </div>
            </flux:card>

            <!-- Movements -->
            <flux:card class="p-4">
                <div class="flex items-start gap-3">
                    <div class="p-2 bg-purple-100 dark:bg-purple-900 rounded-lg">
                        <flux:icon name="arrows-right-left" class="h-5 w-5 text-purple-500" />
                    </div>
                    <div>
                        <flux:heading size="sm">Movimientos</flux:heading>
                        <flux:text class="text-sm mt-1">
                            Analisis de entradas, salidas y transferencias
                        </flux:text>
                    </div>
                </div>
            </flux:card>

            <!-- Administrative -->
            <flux:card class="p-4">
                <div class="flex items-start gap-3">
                    <div class="p-2 bg-orange-100 dark:bg-orange-900 rounded-lg">
                        <flux:icon name="briefcase" class="h-5 w-5 text-orange-500" />
                    </div>
                    <div>
                        <flux:heading size="sm">Administrativos</flux:heading>
                        <flux:text class="text-sm mt-1">
                            Reportes ejecutivos y gerenciales consolidados
                        </flux:text>
                    </div>
                </div>
            </flux:card>

            <!-- Purchases -->
            <flux:card class="p-4">
                <div class="flex items-start gap-3">
                    <div class="p-2 bg-cyan-100 dark:bg-cyan-900 rounded-lg">
                        <flux:icon name="shopping-cart" class="h-5 w-5 text-cyan-500" />
                    </div>
                    <div>
                        <flux:heading size="sm">Compras</flux:heading>
                        <flux:text class="text-sm mt-1">
                            Analisis de compras por proveedor y periodo
                        </flux:text>
                    </div>
                </div>
            </flux:card>

            <!-- Custom -->
            <flux:card class="p-4">
                <div class="flex items-start gap-3">
                    <div class="p-2 bg-pink-100 dark:bg-pink-900 rounded-lg">
                        <flux:icon name="adjustments-horizontal" class="h-5 w-5 text-pink-500" />
                    </div>
                    <div>
                        <flux:heading size="sm">Personalizados</flux:heading>
                        <flux:text class="text-sm mt-1">
                            Reportes configurables segun necesidades especificas
                        </flux:text>
                    </div>
                </div>
            </flux:card>
        </div>
    </section>

    <!-- Kardex Report -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Kardex de Inventario
        </flux:heading>

        <flux:text>
            El Kardex es el reporte fundamental que muestra el historial completo de movimientos
            de un producto especifico en un almacen determinado. Es esencial para auditorias y
            control de inventario.
        </flux:text>

        <flux:heading size="sm" class="text-zinc-700 dark:text-zinc-300 mt-4">
            Filtros Disponibles
        </flux:heading>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-zinc-100 dark:bg-zinc-800">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium">Campo</th>
                        <th class="px-4 py-3 text-left font-medium">Descripcion</th>
                        <th class="px-4 py-3 text-left font-medium">Requerido</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    <tr>
                        <td class="px-4 py-3 font-medium">Empresa</td>
                        <td class="px-4 py-3">Empresa propietaria del inventario (solo super admin)</td>
                        <td class="px-4 py-3"><flux:badge color="red" size="sm">Obligatorio</flux:badge></td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3 font-medium">Producto</td>
                        <td class="px-4 py-3">Producto a consultar</td>
                        <td class="px-4 py-3"><flux:badge color="red" size="sm">Obligatorio</flux:badge></td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3 font-medium">Almacen</td>
                        <td class="px-4 py-3">Bodega o almacen especifico</td>
                        <td class="px-4 py-3"><flux:badge color="red" size="sm">Obligatorio</flux:badge></td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3 font-medium">Fecha Desde</td>
                        <td class="px-4 py-3">Inicio del periodo (por defecto: inicio del mes)</td>
                        <td class="px-4 py-3"><flux:badge color="zinc" size="sm">Opcional</flux:badge></td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3 font-medium">Fecha Hasta</td>
                        <td class="px-4 py-3">Fin del periodo (por defecto: fin del mes)</td>
                        <td class="px-4 py-3"><flux:badge color="zinc" size="sm">Opcional</flux:badge></td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3 font-medium">Tipo Movimiento</td>
                        <td class="px-4 py-3">Filtrar por entrada, salida, ajuste o transferencia</td>
                        <td class="px-4 py-3"><flux:badge color="zinc" size="sm">Opcional</flux:badge></td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3 font-medium">Motivo</td>
                        <td class="px-4 py-3">Filtrar por motivo del movimiento</td>
                        <td class="px-4 py-3"><flux:badge color="zinc" size="sm">Opcional</flux:badge></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <flux:heading size="sm" class="text-zinc-700 dark:text-zinc-300 mt-4">
            Columnas del Reporte
        </flux:heading>

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
                        <td class="px-4 py-3">Fecha del movimiento</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3 font-medium">Documento</td>
                        <td class="px-4 py-3">Numero de documento y referencia</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3 font-medium">Motivo</td>
                        <td class="px-4 py-3">Razon del movimiento</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3 font-medium">Entrada</td>
                        <td class="px-4 py-3">Cantidad de unidades ingresadas (verde)</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3 font-medium">Salida</td>
                        <td class="px-4 py-3">Cantidad de unidades despachadas (rojo)</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3 font-medium">Saldo</td>
                        <td class="px-4 py-3">Balance de inventario despues del movimiento</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3 font-medium">Costo Unit.</td>
                        <td class="px-4 py-3">Costo unitario del producto</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3 font-medium">Valor Total</td>
                        <td class="px-4 py-3">Valor monetario del saldo (Saldo x Costo)</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <flux:heading size="sm" class="text-zinc-700 dark:text-zinc-300 mt-4">
            Resumen del Kardex
        </flux:heading>

        <flux:text class="text-sm">
            Al final del reporte se muestra un resumen con:
        </flux:text>

        <ul class="space-y-2 text-sm text-zinc-600 dark:text-zinc-400">
            <li class="flex items-center gap-2">
                <flux:icon name="calculator" class="h-4 w-4 text-zinc-500" />
                <strong>Total de Movimientos</strong> - Cantidad de registros en el periodo
            </li>
            <li class="flex items-center gap-2">
                <flux:icon name="cube" class="h-4 w-4 text-blue-500" />
                <strong>Saldo Final</strong> - Cantidad de unidades al cierre
            </li>
            <li class="flex items-center gap-2">
                <flux:icon name="currency-dollar" class="h-4 w-4 text-green-500" />
                <strong>Valor en Inventario</strong> - Valor monetario total
            </li>
            <li class="flex items-center gap-2">
                <flux:icon name="arrow-down-tray" class="h-4 w-4 text-green-500" />
                <strong>Total Entradas</strong> - Suma de unidades ingresadas
            </li>
            <li class="flex items-center gap-2">
                <flux:icon name="arrow-up-tray" class="h-4 w-4 text-red-500" />
                <strong>Total Salidas</strong> - Suma de unidades despachadas
            </li>
        </ul>

        <flux:callout variant="info" icon="information-circle">
            Puede exportar el Kardex a PDF o Excel usando los botones en la parte superior del reporte.
        </flux:callout>
    </section>

    <!-- Inventory Reports -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Reportes de Inventario
        </flux:heading>

        <flux:text>
            Esta categoria incluye reportes sobre el estado actual del inventario:
        </flux:text>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <flux:card class="p-4 border-l-4 border-l-blue-500">
                <flux:heading size="sm" class="mb-2 text-blue-700 dark:text-blue-300">
                    Inventario Consolidado
                </flux:heading>
                <flux:text class="text-sm">
                    Vista general de todo el inventario disponible con totales por bodega,
                    categoria y producto. Ideal para tener una foto del estado actual.
                </flux:text>
                <flux:button variant="ghost" size="sm" class="mt-3" icon="arrow-right">
                    Acceder al reporte
                </flux:button>
            </flux:card>

            <flux:card class="p-4 border-l-4 border-l-green-500">
                <flux:heading size="sm" class="mb-2 text-green-700 dark:text-green-300">
                    Valorizacion de Inventario
                </flux:heading>
                <flux:text class="text-sm">
                    Calculo del valor monetario del inventario usando el costo promedio
                    o ultimo costo de compra. Esencial para reportes financieros.
                </flux:text>
                <flux:button variant="ghost" size="sm" class="mt-3" icon="arrow-right">
                    Acceder al reporte
                </flux:button>
            </flux:card>

            <flux:card class="p-4 border-l-4 border-l-purple-500">
                <flux:heading size="sm" class="mb-2 text-purple-700 dark:text-purple-300">
                    Rotacion de Inventario
                </flux:heading>
                <flux:text class="text-sm">
                    Analisis de la velocidad de rotacion de productos. Identifica articulos
                    de alta rotacion y productos estancados que requieren atencion.
                </flux:text>
                <flux:button variant="ghost" size="sm" class="mt-3" icon="arrow-right">
                    Acceder al reporte
                </flux:button>
            </flux:card>

            <flux:card class="p-4 border-l-4 border-l-orange-500">
                <flux:heading size="sm" class="mb-2 text-orange-700 dark:text-orange-300">
                    Dashboard de Inventario
                </flux:heading>
                <flux:text class="text-sm">
                    Panel visual con graficos e indicadores clave del rendimiento del
                    inventario. Metricas en tiempo real para toma de decisiones.
                </flux:text>
                <flux:button variant="ghost" size="sm" class="mt-3" icon="arrow-right">
                    Acceder al dashboard
                </flux:button>
            </flux:card>
        </div>
    </section>

    <!-- Movement Reports -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Reportes de Movimientos
        </flux:heading>

        <flux:text>
            Analisis detallado de las operaciones de inventario:
        </flux:text>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <flux:card class="p-4 border-l-4 border-l-indigo-500">
                <flux:heading size="sm" class="mb-2 text-indigo-700 dark:text-indigo-300">
                    Movimientos Mensuales
                </flux:heading>
                <flux:text class="text-sm">
                    Resumen mensual de todas las operaciones de inventario.
                    Permite comparar periodos y detectar tendencias.
                </flux:text>
            </flux:card>

            <flux:card class="p-4 border-l-4 border-l-green-500">
                <flux:heading size="sm" class="mb-2 text-green-700 dark:text-green-300">
                    Reporte de Ingresos
                </flux:heading>
                <flux:text class="text-sm">
                    Detalle de todas las entradas de inventario por compras,
                    donaciones u otros conceptos durante un periodo.
                </flux:text>
            </flux:card>

            <flux:card class="p-4 border-l-4 border-l-red-500">
                <flux:heading size="sm" class="mb-2 text-red-700 dark:text-red-300">
                    Consumo por Linea
                </flux:heading>
                <flux:text class="text-sm">
                    Analisis de consumo agrupado por linea de producto.
                    Util para planificacion de compras y presupuestos.
                </flux:text>
            </flux:card>

            <flux:card class="p-4 border-l-4 border-l-cyan-500">
                <flux:heading size="sm" class="mb-2 text-cyan-700 dark:text-cyan-300">
                    Reporte de Transferencias
                </flux:heading>
                <flux:text class="text-sm">
                    Historial de traslados entre bodegas con detalle de
                    origen, destino, productos y cantidades transferidas.
                </flux:text>
            </flux:card>
        </div>
    </section>

    <!-- Administrative Reports -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Reportes Administrativos
        </flux:heading>

        <flux:text>
            Informes ejecutivos y gerenciales organizados por categoria:
        </flux:text>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <flux:card class="p-4">
                <div class="flex items-start gap-3">
                    <div class="p-2 bg-green-100 dark:bg-green-900 rounded-lg">
                        <flux:icon name="currency-dollar" class="h-5 w-5 text-green-500" />
                    </div>
                    <div>
                        <flux:heading size="sm">Financieros</flux:heading>
                        <flux:text class="text-sm mt-1">
                            Valorizacion y costos del inventario
                        </flux:text>
                    </div>
                </div>
            </flux:card>

            <flux:card class="p-4">
                <div class="flex items-start gap-3">
                    <div class="p-2 bg-blue-100 dark:bg-blue-900 rounded-lg">
                        <flux:icon name="chart-bar" class="h-5 w-5 text-blue-500" />
                    </div>
                    <div>
                        <flux:heading size="sm">Operacionales</flux:heading>
                        <flux:text class="text-sm mt-1">
                            Eficiencia y productividad de operaciones
                        </flux:text>
                    </div>
                </div>
            </flux:card>

            <flux:card class="p-4">
                <div class="flex items-start gap-3">
                    <div class="p-2 bg-purple-100 dark:bg-purple-900 rounded-lg">
                        <flux:icon name="document-chart-bar" class="h-5 w-5 text-purple-500" />
                    </div>
                    <div>
                        <flux:heading size="sm">Gerenciales</flux:heading>
                        <flux:text class="text-sm mt-1">
                            KPIs e indicadores de desempeno
                        </flux:text>
                    </div>
                </div>
            </flux:card>

            <flux:card class="p-4">
                <div class="flex items-start gap-3">
                    <div class="p-2 bg-red-100 dark:bg-red-900 rounded-lg">
                        <flux:icon name="shield-check" class="h-5 w-5 text-red-500" />
                    </div>
                    <div>
                        <flux:heading size="sm">Cumplimiento</flux:heading>
                        <flux:text class="text-sm mt-1">
                            Auditoria, bitacora y control
                        </flux:text>
                    </div>
                </div>
            </flux:card>

            <flux:card class="p-4">
                <div class="flex items-start gap-3">
                    <div class="p-2 bg-yellow-100 dark:bg-yellow-900 rounded-lg">
                        <flux:icon name="arrows-right-left" class="h-5 w-5 text-yellow-500" />
                    </div>
                    <div>
                        <flux:heading size="sm">Comparativos</flux:heading>
                        <flux:text class="text-sm mt-1">
                            Analisis periodo a periodo
                        </flux:text>
                    </div>
                </div>
            </flux:card>

            <flux:card class="p-4">
                <div class="flex items-start gap-3">
                    <div class="p-2 bg-orange-100 dark:bg-orange-900 rounded-lg">
                        <flux:icon name="exclamation-triangle" class="h-5 w-5 text-orange-500" />
                    </div>
                    <div>
                        <flux:heading size="sm">Excepciones</flux:heading>
                        <flux:text class="text-sm mt-1">
                            Alertas, anomalias y diferencias
                        </flux:text>
                    </div>
                </div>
            </flux:card>
        </div>
    </section>

    <!-- Special Reports -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Reportes Especiales
        </flux:heading>

        <flux:text>
            Informes especializados para necesidades especificas:
        </flux:text>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <flux:card class="p-4 border-l-4 border-l-cyan-500">
                <flux:heading size="sm" class="mb-2 text-cyan-700 dark:text-cyan-300">
                    Compras por Proveedor
                </flux:heading>
                <flux:text class="text-sm">
                    Analisis de compras agrupadas por proveedor, con totales y promedios
                    para evaluacion de rendimiento de proveedores.
                </flux:text>
            </flux:card>

            <flux:card class="p-4 border-l-4 border-l-amber-500">
                <flux:heading size="sm" class="mb-2 text-amber-700 dark:text-amber-300">
                    Autoconsumo
                </flux:heading>
                <flux:text class="text-sm">
                    Registro de productos utilizados internamente por la organizacion.
                    Separado del inventario para despacho a clientes.
                </flux:text>
            </flux:card>

            <flux:card class="p-4 border-l-4 border-l-pink-500">
                <flux:heading size="sm" class="mb-2 text-pink-700 dark:text-pink-300">
                    Donaciones Consolidadas
                </flux:heading>
                <flux:text class="text-sm">
                    Resumen de todas las donaciones recibidas con detalle de donantes,
                    productos y valores. Util para reportes a benefactores.
                </flux:text>
            </flux:card>

            <flux:card class="p-4 border-l-4 border-l-red-500">
                <flux:heading size="sm" class="mb-2 text-red-700 dark:text-red-300">
                    Diferencias Pre-Cierre
                </flux:heading>
                <flux:text class="text-sm">
                    Comparacion entre inventario fisico y sistema antes del cierre mensual.
                    Identifica discrepancias que deben resolverse.
                </flux:text>
            </flux:card>
        </div>
    </section>

    <!-- Export Formats -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Formatos de Exportacion
        </flux:heading>

        <flux:text>
            Los reportes pueden exportarse en diferentes formatos:
        </flux:text>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <flux:card class="p-4 text-center">
                <flux:icon name="document-arrow-down" class="h-12 w-12 text-red-500 mx-auto mb-3" />
                <flux:heading size="sm">PDF</flux:heading>
                <flux:text class="text-sm mt-2">
                    Formato ideal para imprimir o compartir como documento oficial
                </flux:text>
            </flux:card>

            <flux:card class="p-4 text-center">
                <flux:icon name="table-cells" class="h-12 w-12 text-green-500 mx-auto mb-3" />
                <flux:heading size="sm">Excel (XLSX)</flux:heading>
                <flux:text class="text-sm mt-2">
                    Para analisis adicional en hojas de calculo
                </flux:text>
            </flux:card>

            <flux:card class="p-4 text-center">
                <flux:icon name="printer" class="h-12 w-12 text-blue-500 mx-auto mb-3" />
                <flux:heading size="sm">Impresion</flux:heading>
                <flux:text class="text-sm mt-2">
                    Vista optimizada para impresion directa desde el navegador
                </flux:text>
            </flux:card>
        </div>
    </section>

    <!-- Access and Permissions -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Acceso y Permisos
        </flux:heading>

        <flux:text>
            El acceso a los reportes esta controlado por el sistema de permisos:
        </flux:text>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-zinc-100 dark:bg-zinc-800">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium">Rol</th>
                        <th class="px-4 py-3 text-left font-medium">Acceso a Reportes</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    <tr>
                        <td class="px-4 py-3 font-medium">Super Administrador</td>
                        <td class="px-4 py-3">Acceso completo a todos los reportes de todas las empresas</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3 font-medium">Administrador Empresa</td>
                        <td class="px-4 py-3">Todos los reportes de su empresa</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3 font-medium">Administrador Bodega</td>
                        <td class="px-4 py-3">Reportes operativos de sus bodegas asignadas</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3 font-medium">Usuario</td>
                        <td class="px-4 py-3">Reportes basicos segun permisos especificos</td>
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
                    Programar Reportes Periodicos
                </flux:heading>
                <flux:text class="text-sm">
                    Genere reportes de inventario consolidado al menos semanalmente para mantener
                    visibilidad del estado del inventario.
                </flux:text>
            </flux:card>

            <flux:card class="p-4 border-l-4 border-l-blue-500">
                <flux:heading size="sm" class="mb-2 text-blue-700 dark:text-blue-300">
                    Usar Filtros Apropiados
                </flux:heading>
                <flux:text class="text-sm">
                    Aplique filtros de fecha y bodega para obtener informacion relevante
                    y evitar reportes demasiado extensos.
                </flux:text>
            </flux:card>

            <flux:card class="p-4 border-l-4 border-l-purple-500">
                <flux:heading size="sm" class="mb-2 text-purple-700 dark:text-purple-300">
                    Comparar Periodos
                </flux:heading>
                <flux:text class="text-sm">
                    Use los reportes comparativos para identificar tendencias y anomalias
                    entre diferentes periodos de tiempo.
                </flux:text>
            </flux:card>

            <flux:card class="p-4 border-l-4 border-l-orange-500">
                <flux:heading size="sm" class="mb-2 text-orange-700 dark:text-orange-300">
                    Exportar para Respaldo
                </flux:heading>
                <flux:text class="text-sm">
                    Exporte reportes importantes en PDF como respaldo documental
                    para auditorias y cumplimiento.
                </flux:text>
            </flux:card>
        </div>
    </section>

    <!-- Quick Access -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Acceso Rapido
        </flux:heading>

        <flux:text>
            Desde el menu principal puede acceder rapidamente a los reportes mas utilizados:
        </flux:text>

        <div class="flex flex-wrap gap-3">
            <flux:button variant="outline" size="sm" icon="document-text">
                Kardex
            </flux:button>
            <flux:button variant="outline" size="sm" icon="cube">
                Inventario Consolidado
            </flux:button>
            <flux:button variant="outline" size="sm" icon="currency-dollar">
                Valorizacion
            </flux:button>
            <flux:button variant="outline" size="sm" icon="chart-bar">
                Dashboard
            </flux:button>
            <flux:button variant="outline" size="sm" icon="arrow-path">
                Rotacion
            </flux:button>
        </div>
    </section>
</div>
