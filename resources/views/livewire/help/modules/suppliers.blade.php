<div class="space-y-8">
    <!-- Module Header -->
    <div class="border-b border-zinc-200 dark:border-zinc-700 pb-6">
        <div class="flex items-center gap-4 mb-4">
            <div class="p-3 bg-blue-100 dark:bg-blue-900 rounded-lg">
                <flux:icon name="truck" class="h-8 w-8 text-blue-600 dark:text-blue-400" />
            </div>
            <div>
                <flux:heading size="xl" class="text-zinc-900 dark:text-zinc-100">
                    Proveedores
                </flux:heading>
                <flux:text class="text-zinc-600 dark:text-zinc-400">
                    Gestión de proveedores de productos e insumos
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
                Los <strong>Proveedores</strong> son las entidades externas que suministran productos a la organización.
                Una correcta gestión de proveedores permite mantener un registro actualizado de todas las fuentes de
                suministro, evaluar su desempeño y gestionar términos comerciales.
            </p>
        </div>
    </section>

    <!-- Campos del Proveedor -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Campos del Proveedor
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
                        <td class="px-4 py-3">Nombre comercial del proveedor</td>
                        <td class="px-4 py-3"><flux:badge color="green" size="sm">Sí</flux:badge></td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3 font-medium">Nombre Legal</td>
                        <td class="px-4 py-3">Razón social o nombre legal</td>
                        <td class="px-4 py-3"><flux:badge color="zinc" size="sm">No</flux:badge></td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3 font-medium">NIT/DUI</td>
                        <td class="px-4 py-3">Número de identificación tributaria</td>
                        <td class="px-4 py-3"><flux:badge color="zinc" size="sm">No</flux:badge></td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3 font-medium">Email</td>
                        <td class="px-4 py-3">Correo electrónico principal</td>
                        <td class="px-4 py-3"><flux:badge color="zinc" size="sm">No</flux:badge></td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3 font-medium">Teléfono</td>
                        <td class="px-4 py-3">Número telefónico principal</td>
                        <td class="px-4 py-3"><flux:badge color="zinc" size="sm">No</flux:badge></td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3 font-medium">Sitio Web</td>
                        <td class="px-4 py-3">URL del sitio web</td>
                        <td class="px-4 py-3"><flux:badge color="zinc" size="sm">No</flux:badge></td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3 font-medium">Persona de Contacto</td>
                        <td class="px-4 py-3">Nombre del contacto principal</td>
                        <td class="px-4 py-3"><flux:badge color="zinc" size="sm">No</flux:badge></td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3 font-medium">Términos de Pago</td>
                        <td class="px-4 py-3">Condiciones de pago acordadas</td>
                        <td class="px-4 py-3"><flux:badge color="zinc" size="sm">No</flux:badge></td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3 font-medium">Límite de Crédito</td>
                        <td class="px-4 py-3">Monto máximo de crédito otorgado</td>
                        <td class="px-4 py-3"><flux:badge color="zinc" size="sm">No</flux:badge></td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3 font-medium">Calificación</td>
                        <td class="px-4 py-3">Evaluación del proveedor (1-5)</td>
                        <td class="px-4 py-3"><flux:badge color="zinc" size="sm">No</flux:badge></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>

    <!-- Estados -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Estados del Proveedor
        </flux:heading>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <flux:card class="p-4 border-l-4 border-l-green-500">
                <div class="flex items-center gap-2 mb-2">
                    <flux:badge color="green">Activo</flux:badge>
                </div>
                <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                    Proveedor disponible para operaciones. Aparece en el selector de compras.
                </flux:text>
            </flux:card>

            <flux:card class="p-4 border-l-4 border-l-red-500">
                <div class="flex items-center gap-2 mb-2">
                    <flux:badge color="red">Inactivo</flux:badge>
                </div>
                <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                    Proveedor temporalmente no disponible. No aparece en nuevas compras.
                </flux:text>
            </flux:card>
        </div>
    </section>

    <!-- Relación con Compras -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Relación con Compras
        </flux:heading>

        <flux:callout variant="info" icon="information-circle">
            Cada compra debe estar asociada a un proveedor. Al crear una compra, solo se muestran
            los proveedores activos en el selector.
        </flux:callout>

        <div class="p-4 bg-zinc-50 dark:bg-zinc-800 rounded-lg">
            <flux:heading size="sm" class="mb-3">Información del Proveedor en Compras</flux:heading>
            <ul class="text-sm space-y-2 text-zinc-600 dark:text-zinc-400">
                <li class="flex items-center gap-2">
                    <flux:icon name="check" class="h-4 w-4 text-green-500" />
                    Nombre y datos de contacto
                </li>
                <li class="flex items-center gap-2">
                    <flux:icon name="check" class="h-4 w-4 text-green-500" />
                    Términos de pago acordados
                </li>
                <li class="flex items-center gap-2">
                    <flux:icon name="check" class="h-4 w-4 text-green-500" />
                    Límite de crédito disponible
                </li>
                <li class="flex items-center gap-2">
                    <flux:icon name="check" class="h-4 w-4 text-green-500" />
                    Historial de compras anteriores
                </li>
            </ul>
        </div>
    </section>

    <!-- Calificación de Proveedores -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Calificación de Proveedores
        </flux:heading>

        <flux:callout variant="info" icon="information-circle">
            Puede asignar una calificación de 1 a 5 estrellas a cada proveedor desde el formulario de creación o edición.
            Esta calificación es manual y ayuda a identificar rápidamente la calidad del servicio.
        </flux:callout>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-zinc-100 dark:bg-zinc-800">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium">Calificación</th>
                        <th class="px-4 py-3 text-left font-medium">Descripción</th>
                        <th class="px-4 py-3 text-left font-medium">Recomendación</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    <tr>
                        <td class="px-4 py-3">★★★★★ (5)</td>
                        <td class="px-4 py-3">Excelente - Sin problemas</td>
                        <td class="px-4 py-3"><flux:badge color="green" size="sm">Priorizar</flux:badge></td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3">★★★★☆ (4)</td>
                        <td class="px-4 py-3">Bueno - Problemas menores</td>
                        <td class="px-4 py-3"><flux:badge color="blue" size="sm">Mantener</flux:badge></td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3">★★★☆☆ (3)</td>
                        <td class="px-4 py-3">Regular - Requiere mejoras</td>
                        <td class="px-4 py-3"><flux:badge color="amber" size="sm">Monitorear</flux:badge></td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3">★★☆☆☆ (2)</td>
                        <td class="px-4 py-3">Deficiente - Problemas frecuentes</td>
                        <td class="px-4 py-3"><flux:badge color="orange" size="sm">Revisar</flux:badge></td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3">★☆☆☆☆ (1)</td>
                        <td class="px-4 py-3">Malo - No recomendado</td>
                        <td class="px-4 py-3"><flux:badge color="red" size="sm">Desactivar</flux:badge></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>

    <!-- Flujo de Trabajo -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Crear un Nuevo Proveedor
        </flux:heading>

        <div class="space-y-3">
            <div class="flex items-start gap-4 p-4 bg-blue-50 dark:bg-blue-950 rounded-lg">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-400 font-bold text-sm">
                    1
                </div>
                <div>
                    <flux:heading size="sm">Acceder al Módulo</flux:heading>
                    <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                        Vaya a Compras > Proveedores y haga clic en "Nuevo Proveedor".
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
                        Complete nombre comercial, nombre legal y NIT.
                    </flux:text>
                </div>
            </div>

            <div class="flex items-start gap-4 p-4 bg-purple-50 dark:bg-purple-950 rounded-lg">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-purple-100 dark:bg-purple-900 text-purple-600 dark:text-purple-400 font-bold text-sm">
                    3
                </div>
                <div>
                    <flux:heading size="sm">Información de Contacto</flux:heading>
                    <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                        Agregue email, teléfono, persona de contacto y dirección.
                    </flux:text>
                </div>
            </div>

            <div class="flex items-start gap-4 p-4 bg-orange-50 dark:bg-orange-950 rounded-lg">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-orange-100 dark:bg-orange-900 text-orange-600 dark:text-orange-400 font-bold text-sm">
                    4
                </div>
                <div>
                    <flux:heading size="sm">Términos Comerciales</flux:heading>
                    <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                        Defina términos de pago, límite de crédito y calificación inicial.
                    </flux:text>
                </div>
            </div>
        </div>
    </section>

    <!-- Restricciones -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Restricciones
        </flux:heading>

        <flux:callout variant="warning" icon="exclamation-triangle">
            Un proveedor <strong>NO</strong> puede ser eliminado si tiene compras asociadas.
            En este caso, la opción recomendada es <strong>desactivar</strong> el proveedor.
        </flux:callout>
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
                    <li>Registrar información completa</li>
                    <li>Mantener contactos actualizados</li>
                    <li>Evaluar periódicamente el desempeño</li>
                    <li>Documentar términos de pago</li>
                    <li>Desactivar antes de eliminar</li>
                </ul>
            </flux:card>

            <flux:card class="p-4 border-l-4 border-l-red-500">
                <flux:heading size="sm" class="mb-2 text-red-700 dark:text-red-400">Evitar</flux:heading>
                <ul class="text-sm space-y-1 text-zinc-600 dark:text-zinc-400">
                    <li>Proveedores sin información de contacto</li>
                    <li>No actualizar calificaciones</li>
                    <li>Eliminar proveedores con historial</li>
                    <li>Información duplicada</li>
                    <li>NIT incorrectos o duplicados</li>
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
            <flux:button variant="outline" icon="truck" :href="route('purchases.suppliers.index')" wire:navigate class="justify-start">
                Ir a Proveedores
            </flux:button>
            <flux:button variant="outline" icon="plus" :href="route('purchases.suppliers.create')" wire:navigate class="justify-start">
                Nuevo Proveedor
            </flux:button>
            <flux:button variant="outline" icon="shopping-cart" :href="route('purchases.index')" wire:navigate class="justify-start">
                Ver Compras
            </flux:button>
        </div>
    </section>
</div>
