<div class="space-y-8">
    <!-- Module Header -->
    <div class="border-b border-zinc-200 dark:border-zinc-700 pb-6">
        <div class="flex items-center gap-4 mb-4">
            <div class="p-3 bg-emerald-100 dark:bg-emerald-900 rounded-lg">
                <flux:icon name="cube" class="h-8 w-8 text-emerald-600 dark:text-emerald-400" />
            </div>
            <div>
                <flux:heading size="xl" class="text-zinc-900 dark:text-zinc-100">
                    Catálogo de Productos
                </flux:heading>
                <flux:text class="text-zinc-600 dark:text-zinc-400">
                    Gestión de productos, categorías, unidades de medida y lotes
                </flux:text>
            </div>
        </div>
    </div>

    <!-- Descripción -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Descripción General
        </flux:heading>
        <div class="prose dark:prose-invert max-w-none">
            <p>
                El <strong>Catálogo de Productos</strong> es el registro maestro de todos los artículos que maneja
                la organización. Cada producto contiene información esencial como nombre, código SKU, categoría,
                unidad de medida, costos, precios y configuraciones de inventario.
            </p>
        </div>
    </section>

    <!-- Estructura del Catálogo -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Estructura del Catálogo
        </flux:heading>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <flux:card class="p-4 border-l-4 border-l-blue-500">
                <flux:heading size="sm" class="mb-2">Categorías</flux:heading>
                <flux:text class="text-sm text-zinc-600 dark:text-zinc-400 mb-2">
                    Organización de productos en grupos lógicos
                </flux:text>
                <ul class="text-sm space-y-1 text-zinc-600 dark:text-zinc-400">
                    <li>Nombre descriptivo</li>
                    <li>Código corto identificador</li>
                    <li>Estado activo/inactivo</li>
                </ul>
            </flux:card>

            <flux:card class="p-4 border-l-4 border-l-green-500">
                <flux:heading size="sm" class="mb-2">Unidades de Medida</flux:heading>
                <flux:text class="text-sm text-zinc-600 dark:text-zinc-400 mb-2">
                    Cómo se cuantifican los productos
                </flux:text>
                <ul class="text-sm space-y-1 text-zinc-600 dark:text-zinc-400">
                    <li>Peso: kg, lb, qq, ton</li>
                    <li>Volumen: lt, gal, ml</li>
                    <li>Unidad: und, doc, cja</li>
                </ul>
            </flux:card>

            <flux:card class="p-4 border-l-4 border-l-purple-500">
                <flux:heading size="sm" class="mb-2">Productos</flux:heading>
                <flux:text class="text-sm text-zinc-600 dark:text-zinc-400 mb-2">
                    Entidad central del catálogo
                </flux:text>
                <ul class="text-sm space-y-1 text-zinc-600 dark:text-zinc-400">
                    <li>Nombre, SKU, código de barras</li>
                    <li>Costos y precios</li>
                    <li>Stock mínimo y máximo</li>
                </ul>
            </flux:card>

            <flux:card class="p-4 border-l-4 border-l-orange-500">
                <flux:heading size="sm" class="mb-2">Lotes</flux:heading>
                <flux:text class="text-sm text-zinc-600 dark:text-zinc-400 mb-2">
                    Trazabilidad por fecha y origen
                </flux:text>
                <ul class="text-sm space-y-1 text-zinc-600 dark:text-zinc-400">
                    <li>Número de lote único</li>
                    <li>Fecha de vencimiento</li>
                    <li>Proveedor de origen</li>
                </ul>
            </flux:card>
        </div>
    </section>

    <!-- Campos del Producto -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Campos del Producto
        </flux:heading>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-zinc-100 dark:bg-zinc-800">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium">Campo</th>
                        <th class="px-4 py-3 text-left font-medium">Descripción</th>
                        <th class="px-4 py-3 text-left font-medium">Requerido</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    <tr>
                        <td class="px-4 py-3 font-medium">Nombre</td>
                        <td class="px-4 py-3">Nombre descriptivo del producto</td>
                        <td class="px-4 py-3"><flux:badge color="green" size="sm">Sí</flux:badge></td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3 font-medium">SKU</td>
                        <td class="px-4 py-3">Código único de identificación</td>
                        <td class="px-4 py-3"><flux:badge color="zinc" size="sm">No</flux:badge></td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3 font-medium">Código de Barras</td>
                        <td class="px-4 py-3">Código para escaneo rápido</td>
                        <td class="px-4 py-3"><flux:badge color="zinc" size="sm">No</flux:badge></td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3 font-medium">Categoría</td>
                        <td class="px-4 py-3">Clasificación del producto</td>
                        <td class="px-4 py-3"><flux:badge color="zinc" size="sm">No</flux:badge></td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3 font-medium">Unidad de Medida</td>
                        <td class="px-4 py-3">Unidad principal de conteo</td>
                        <td class="px-4 py-3"><flux:badge color="green" size="sm">Sí</flux:badge></td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3 font-medium">Costo</td>
                        <td class="px-4 py-3">Costo de adquisición</td>
                        <td class="px-4 py-3"><flux:badge color="zinc" size="sm">No</flux:badge></td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3 font-medium">Precio</td>
                        <td class="px-4 py-3">Precio de referencia/venta</td>
                        <td class="px-4 py-3"><flux:badge color="zinc" size="sm">No</flux:badge></td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3 font-medium">Stock Mínimo</td>
                        <td class="px-4 py-3">Cantidad mínima para alertas</td>
                        <td class="px-4 py-3"><flux:badge color="zinc" size="sm">No</flux:badge></td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3 font-medium">Stock Máximo</td>
                        <td class="px-4 py-3">Cantidad máxima permitida</td>
                        <td class="px-4 py-3"><flux:badge color="zinc" size="sm">No</flux:badge></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>

    <!-- Métodos de Valuación -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Métodos de Valuación
        </flux:heading>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <flux:card class="p-4 border-l-4 border-l-blue-500">
                <flux:heading size="sm" class="mb-2">FIFO</flux:heading>
                <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                    <strong>First In, First Out</strong><br>
                    Los primeros productos en entrar son los primeros en salir.
                    Ideal para productos no perecederos.
                </flux:text>
            </flux:card>

            <flux:card class="p-4 border-l-4 border-l-green-500">
                <flux:heading size="sm" class="mb-2">FEFO</flux:heading>
                <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                    <strong>First Expired, First Out</strong><br>
                    Los productos próximos a vencer salen primero.
                    Ideal para productos perecederos.
                </flux:text>
            </flux:card>

            <flux:card class="p-4 border-l-4 border-l-purple-500">
                <flux:heading size="sm" class="mb-2">Promedio</flux:heading>
                <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                    <strong>Promedio Ponderado</strong><br>
                    Se calcula un costo promedio de todas las entradas.
                    Ideal para precios fluctuantes.
                </flux:text>
            </flux:card>
        </div>
    </section>

    <!-- Último Conteo -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Último Conteo (Inventario Físico)
        </flux:heading>

        <flux:callout variant="info" icon="information-circle">
            La columna <strong>"Último Conteo"</strong> en el catálogo de productos muestra la fecha del último
            inventario físico realizado para ese producto, junto con el nombre del usuario que lo ejecutó.
        </flux:callout>

        <div class="p-4 bg-zinc-50 dark:bg-zinc-800 rounded-lg space-y-4">
            <flux:heading size="sm" class="mb-3">¿Qué es el Último Conteo?</flux:heading>
            <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                El <strong>Último Conteo</strong> es un registro que indica cuándo fue la última vez que se verificó
                físicamente la existencia de un producto en bodega. Esta información es fundamental para:
            </flux:text>
            <ul class="text-sm space-y-2 text-zinc-600 dark:text-zinc-400 mt-3">
                <li class="flex items-start gap-2">
                    <flux:icon name="check-circle" class="h-4 w-4 text-green-500 mt-0.5" />
                    <span><strong>Control de auditoría:</strong> Saber cuándo se verificaron las existencias físicamente</span>
                </li>
                <li class="flex items-start gap-2">
                    <flux:icon name="check-circle" class="h-4 w-4 text-green-500 mt-0.5" />
                    <span><strong>Trazabilidad:</strong> Identificar quién realizó el conteo</span>
                </li>
                <li class="flex items-start gap-2">
                    <flux:icon name="check-circle" class="h-4 w-4 text-green-500 mt-0.5" />
                    <span><strong>Planificación:</strong> Programar conteos periódicos para productos que no han sido verificados recientemente</span>
                </li>
                <li class="flex items-start gap-2">
                    <flux:icon name="check-circle" class="h-4 w-4 text-green-500 mt-0.5" />
                    <span><strong>Confiabilidad:</strong> Evaluar qué tan actualizados están los datos del inventario</span>
                </li>
            </ul>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <flux:card class="p-4 border-l-4 border-l-blue-500">
                <flux:heading size="sm" class="mb-2">Información Mostrada</flux:heading>
                <ul class="text-sm space-y-1 text-zinc-600 dark:text-zinc-400">
                    <li><strong>Fecha:</strong> Día del último conteo físico (dd/mm/aaaa)</li>
                    <li><strong>Usuario:</strong> Nombre de quien realizó el conteo</li>
                    <li><strong>Cantidad:</strong> Cantidad verificada en ese momento</li>
                </ul>
            </flux:card>

            <flux:card class="p-4 border-l-4 border-l-green-500">
                <flux:heading size="sm" class="mb-2">¿Cómo se Actualiza?</flux:heading>
                <ul class="text-sm space-y-1 text-zinc-600 dark:text-zinc-400">
                    <li>Al realizar un <strong>Ajuste de Inventario</strong></li>
                    <li>Durante el proceso de <strong>Cierre Mensual</strong></li>
                    <li>Al ejecutar un <strong>Conteo Físico</strong> programado</li>
                </ul>
            </flux:card>
        </div>

        <flux:callout variant="warning" icon="exclamation-triangle">
            <strong>Recomendación:</strong> Realice conteos físicos periódicos (al menos mensualmente) para mantener
            la precisión del inventario. Los productos sin conteo reciente pueden tener discrepancias entre el
            stock del sistema y el stock real.
        </flux:callout>
    </section>

    <!-- Alertas de Stock -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Alertas de Inventario
        </flux:heading>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-zinc-100 dark:bg-zinc-800">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium">Condición</th>
                        <th class="px-4 py-3 text-left font-medium">Tipo de Alerta</th>
                        <th class="px-4 py-3 text-left font-medium">Acción Recomendada</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    <tr>
                        <td class="px-4 py-3">Stock menor al mínimo</td>
                        <td class="px-4 py-3"><flux:badge color="yellow" size="sm">Stock Bajo</flux:badge></td>
                        <td class="px-4 py-3">Generar orden de compra</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3">Stock mayor al máximo</td>
                        <td class="px-4 py-3"><flux:badge color="orange" size="sm">Sobrestock</flux:badge></td>
                        <td class="px-4 py-3">Revisar almacenamiento</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3">Lote próximo a vencer</td>
                        <td class="px-4 py-3"><flux:badge color="amber" size="sm">Por Vencer</flux:badge></td>
                        <td class="px-4 py-3">Priorizar despacho</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3">Lote vencido</td>
                        <td class="px-4 py-3"><flux:badge color="red" size="sm">Vencido</flux:badge></td>
                        <td class="px-4 py-3">Ajuste por vencimiento</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>

    <!-- Flujo de Trabajo -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Crear un Nuevo Producto
        </flux:heading>

        <div class="space-y-3">
            <div class="flex items-start gap-4 p-4 bg-blue-50 dark:bg-blue-950 rounded-lg">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-400 font-bold text-sm">
                    1
                </div>
                <div>
                    <flux:heading size="sm">Acceder al Catálogo</flux:heading>
                    <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                        Vaya a Catálogos > Productos y haga clic en "Nuevo Producto".
                    </flux:text>
                </div>
            </div>

            <div class="flex items-start gap-4 p-4 bg-green-50 dark:bg-green-950 rounded-lg">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-green-100 dark:bg-green-900 text-green-600 dark:text-green-400 font-bold text-sm">
                    2
                </div>
                <div>
                    <flux:heading size="sm">Datos Básicos</flux:heading>
                    <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                        Complete nombre, SKU, categoría y unidad de medida.
                    </flux:text>
                </div>
            </div>

            <div class="flex items-start gap-4 p-4 bg-purple-50 dark:bg-purple-950 rounded-lg">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-purple-100 dark:bg-purple-900 text-purple-600 dark:text-purple-400 font-bold text-sm">
                    3
                </div>
                <div>
                    <flux:heading size="sm">Costos y Stock</flux:heading>
                    <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                        Defina costo, precio, stock mínimo y máximo.
                    </flux:text>
                </div>
            </div>

            <div class="flex items-start gap-4 p-4 bg-orange-50 dark:bg-orange-950 rounded-lg">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-orange-100 dark:bg-orange-900 text-orange-600 dark:text-orange-400 font-bold text-sm">
                    4
                </div>
                <div>
                    <flux:heading size="sm">Configuración</flux:heading>
                    <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                        Seleccione método de valuación, active control de inventario y agregue imagen si aplica.
                    </flux:text>
                </div>
            </div>
        </div>
    </section>

    <!-- Gestión de Lotes -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Gestión de Lotes
        </flux:heading>

        <flux:callout variant="info" icon="information-circle">
            Los lotes se crean automáticamente al recibir compras o donaciones que incluyan información de lote.
            Permiten rastrear productos por fecha de manufactura, vencimiento y proveedor de origen.
        </flux:callout>

        <div class="p-4 bg-zinc-50 dark:bg-zinc-800 rounded-lg">
            <flux:heading size="sm" class="mb-3">Información de un Lote</flux:heading>
            <ul class="text-sm space-y-2 text-zinc-600 dark:text-zinc-400">
                <li class="flex items-center gap-2">
                    <flux:icon name="check" class="h-4 w-4 text-green-500" />
                    Número de lote único
                </li>
                <li class="flex items-center gap-2">
                    <flux:icon name="check" class="h-4 w-4 text-green-500" />
                    Fecha de manufactura y vencimiento
                </li>
                <li class="flex items-center gap-2">
                    <flux:icon name="check" class="h-4 w-4 text-green-500" />
                    Cantidad producida y restante
                </li>
                <li class="flex items-center gap-2">
                    <flux:icon name="check" class="h-4 w-4 text-green-500" />
                    Costo unitario del lote
                </li>
                <li class="flex items-center gap-2">
                    <flux:icon name="check" class="h-4 w-4 text-green-500" />
                    Proveedor de origen
                </li>
            </ul>
        </div>
    </section>

    <!-- Trazabilidad -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Trazabilidad
        </flux:heading>

        <div class="p-4 bg-zinc-50 dark:bg-zinc-800 rounded-lg">
            <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                Cada producto mantiene trazabilidad completa:
            </flux:text>
            <ul class="text-sm mt-2 space-y-1 text-zinc-600 dark:text-zinc-400">
                <li>Quién lo creó y cuándo</li>
                <li>Quién lo modificó por última vez</li>
                <li>Historial completo de movimientos</li>
                <li>Lotes asociados con sus fechas</li>
                <li>Proveedores de origen</li>
            </ul>
        </div>
    </section>

    <!-- Buenas Prácticas -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Buenas Prácticas
        </flux:heading>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <flux:card class="p-4 border-l-4 border-l-green-500">
                <flux:heading size="sm" class="mb-2 text-green-700 dark:text-green-400">Recomendado</flux:heading>
                <ul class="text-sm space-y-1 text-zinc-600 dark:text-zinc-400">
                    <li>Usar SKU únicos y consistentes</li>
                    <li>Configurar stock mínimo basado en consumo</li>
                    <li>Elegir FEFO para productos perecederos</li>
                    <li>Revisar regularmente productos por vencer</li>
                </ul>
            </flux:card>

            <flux:card class="p-4 border-l-4 border-l-red-500">
                <flux:heading size="sm" class="mb-2 text-red-700 dark:text-red-400">Evitar</flux:heading>
                <ul class="text-sm space-y-1 text-zinc-600 dark:text-zinc-400">
                    <li>Categorías demasiado genéricas</li>
                    <li>SKU duplicados o inconsistentes</li>
                    <li>Ignorar alertas de stock bajo</li>
                    <li>Eliminar productos con historial</li>
                </ul>
            </flux:card>
        </div>
    </section>

    <!-- Enlaces Rápidos -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Enlaces Rápidos
        </flux:heading>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <flux:button variant="outline" icon="cube" :href="route('inventory.products.index')" wire:navigate class="justify-start">
                Ir a Productos
            </flux:button>
            <flux:button variant="outline" icon="plus" :href="route('inventory.products.create')" wire:navigate class="justify-start">
                Nuevo Producto
            </flux:button>
            <flux:button variant="outline" icon="folder" :href="route('admin.categories.index')" wire:navigate class="justify-start">
                Ver Categorías
            </flux:button>
        </div>
    </section>
</div>
