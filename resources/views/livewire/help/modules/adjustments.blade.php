<div class="space-y-8">
    <!-- Module Header -->
    <div class="border-b border-zinc-200 dark:border-zinc-700 pb-6">
        <div class="flex items-center gap-4 mb-4">
            <div class="p-3 bg-purple-100 dark:bg-purple-900 rounded-lg">
                <flux:icon name="adjustments-horizontal" class="h-8 w-8 text-purple-600 dark:text-purple-400" />
            </div>
            <div>
                <flux:heading size="xl" class="text-zinc-900 dark:text-zinc-100">
                    Ajustes de Inventario
                </flux:heading>
                <flux:text class="text-zinc-600 dark:text-zinc-400">
                    Correcciones y ajustes del inventario físico vs. sistema
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
                El módulo de <strong>Ajustes de Inventario</strong> permite corregir diferencias entre el inventario físico
                y el registrado en el sistema. Cada ajuste requiere justificación y pasa por un flujo de aprobación
                antes de afectar el stock.
            </p>
        </div>
    </section>

    <!-- Tipos de Ajuste -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Tipos de Ajuste
        </flux:heading>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            <div class="p-3 bg-green-50 dark:bg-green-900/20 rounded text-center">
                <flux:badge color="green" size="sm">Positivo</flux:badge>
                <flux:text class="text-xs mt-1">Sobrante (+Stock)</flux:text>
            </div>
            <div class="p-3 bg-red-50 dark:bg-red-900/20 rounded text-center">
                <flux:badge color="red" size="sm">Negativo</flux:badge>
                <flux:text class="text-xs mt-1">Faltante (-Stock)</flux:text>
            </div>
            <div class="p-3 bg-orange-50 dark:bg-orange-900/20 rounded text-center">
                <flux:badge color="orange" size="sm">Dañado</flux:badge>
                <flux:text class="text-xs mt-1">Producto dañado (-Stock)</flux:text>
            </div>
            <div class="p-3 bg-amber-50 dark:bg-amber-900/20 rounded text-center">
                <flux:badge color="amber" size="sm">Vencido</flux:badge>
                <flux:text class="text-xs mt-1">Producto vencido (-Stock)</flux:text>
            </div>
            <div class="p-3 bg-rose-50 dark:bg-rose-900/20 rounded text-center">
                <flux:badge color="rose" size="sm">Pérdida/Robo</flux:badge>
                <flux:text class="text-xs mt-1">Producto perdido (-Stock)</flux:text>
            </div>
            <div class="p-3 bg-blue-50 dark:bg-blue-900/20 rounded text-center">
                <flux:badge color="blue" size="sm">Corrección</flux:badge>
                <flux:text class="text-xs mt-1">Error de conteo (+/-)</flux:text>
            </div>
            <div class="p-3 bg-indigo-50 dark:bg-indigo-900/20 rounded text-center">
                <flux:badge color="indigo" size="sm">Devolución</flux:badge>
                <flux:text class="text-xs mt-1">Producto devuelto (+/-)</flux:text>
            </div>
            <div class="p-3 bg-zinc-50 dark:bg-zinc-800 rounded text-center">
                <flux:badge color="zinc" size="sm">Otro</flux:badge>
                <flux:text class="text-xs mt-1">Otros casos (+/-)</flux:text>
            </div>
        </div>
    </section>

    <!-- Estados de un Ajuste -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Estados de un Ajuste
        </flux:heading>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-zinc-100 dark:bg-zinc-800">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium">Estado</th>
                        <th class="px-4 py-3 text-left font-medium">Descripción</th>
                        <th class="px-4 py-3 text-left font-medium">Puede Editarse</th>
                        <th class="px-4 py-3 text-left font-medium">Inventario</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    <tr>
                        <td class="px-4 py-3"><flux:badge color="zinc" size="sm">Borrador</flux:badge></td>
                        <td class="px-4 py-3">En proceso de edición</td>
                        <td class="px-4 py-3"><flux:badge color="green" size="sm">Sí</flux:badge></td>
                        <td class="px-4 py-3">No afecta</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3"><flux:badge color="yellow" size="sm">Pendiente</flux:badge></td>
                        <td class="px-4 py-3">Esperando aprobación</td>
                        <td class="px-4 py-3"><flux:badge color="red" size="sm">No</flux:badge></td>
                        <td class="px-4 py-3">No afecta</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3"><flux:badge color="blue" size="sm">Aprobado</flux:badge></td>
                        <td class="px-4 py-3">Listo para procesar</td>
                        <td class="px-4 py-3"><flux:badge color="red" size="sm">No</flux:badge></td>
                        <td class="px-4 py-3">No afecta</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3"><flux:badge color="green" size="sm">Procesado</flux:badge></td>
                        <td class="px-4 py-3">Inventario actualizado</td>
                        <td class="px-4 py-3"><flux:badge color="red" size="sm">No</flux:badge></td>
                        <td class="px-4 py-3 font-medium">+/- Stock</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3"><flux:badge color="red" size="sm">Rechazado</flux:badge></td>
                        <td class="px-4 py-3">No aprobado</td>
                        <td class="px-4 py-3"><flux:badge color="green" size="sm">Sí</flux:badge></td>
                        <td class="px-4 py-3">No afecta</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3"><flux:badge color="zinc" size="sm">Cancelado</flux:badge></td>
                        <td class="px-4 py-3">Ajuste cancelado</td>
                        <td class="px-4 py-3"><flux:badge color="red" size="sm">No</flux:badge></td>
                        <td class="px-4 py-3">No afecta</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>

    <!-- Flujo de Trabajo -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Flujo de Trabajo
        </flux:heading>

        <div class="p-4 bg-purple-50 dark:bg-purple-900/20 rounded-lg">
            <div class="flex flex-wrap items-center gap-2 text-sm">
                <flux:badge color="zinc">Borrador</flux:badge>
                <flux:icon name="arrow-right" class="h-4 w-4 text-zinc-400" />
                <flux:badge color="yellow">Pendiente</flux:badge>
                <flux:icon name="arrow-right" class="h-4 w-4 text-zinc-400" />
                <flux:badge color="blue">Aprobado</flux:badge>
                <flux:icon name="arrow-right" class="h-4 w-4 text-zinc-400" />
                <flux:badge color="green">Procesado</flux:badge>
            </div>
            <flux:text class="text-xs mt-2 text-zinc-500">
                Si un ajuste es rechazado, puede editarse y reenviarse para aprobación.
            </flux:text>
        </div>
    </section>

    <!-- Crear un Ajuste -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Crear un Ajuste
        </flux:heading>

        <div class="space-y-3">
            <div class="flex items-start gap-4 p-4 bg-green-50 dark:bg-green-950 rounded-lg">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-green-100 dark:bg-green-900 text-green-600 dark:text-green-400 font-bold text-sm">
                    1
                </div>
                <div>
                    <flux:heading size="sm">Información Básica</flux:heading>
                    <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                        Seleccione la bodega, producto, tipo de ajuste y cantidad. El costo unitario se autocompleta.
                    </flux:text>
                </div>
            </div>

            <div class="flex items-start gap-4 p-4 bg-blue-50 dark:bg-blue-950 rounded-lg">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-400 font-bold text-sm">
                    2
                </div>
                <div>
                    <flux:heading size="sm">Justificación</flux:heading>
                    <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                        Proporcione el motivo del ajuste (obligatorio), justificación detallada y acciones correctivas.
                    </flux:text>
                </div>
            </div>

            <div class="flex items-start gap-4 p-4 bg-purple-50 dark:bg-purple-950 rounded-lg">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-purple-100 dark:bg-purple-900 text-purple-600 dark:text-purple-400 font-bold text-sm">
                    3
                </div>
                <div>
                    <flux:heading size="sm">Documentación</flux:heading>
                    <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                        Agregue referencias a documentos de soporte (actas, informes) y notas adicionales.
                    </flux:text>
                </div>
            </div>
        </div>
    </section>

    <!-- Información por Tipo -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Información Recomendada por Tipo
        </flux:heading>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <flux:card class="p-4 border-l-4 border-l-red-500">
                <flux:heading size="sm" class="mb-2">Pérdida/Robo</flux:heading>
                <ul class="text-sm space-y-1 text-zinc-600 dark:text-zinc-400">
                    <li>Informe de investigación</li>
                    <li>Denuncia policial (si aplica)</li>
                    <li>Medidas de seguridad propuestas</li>
                </ul>
            </flux:card>

            <flux:card class="p-4 border-l-4 border-l-amber-500">
                <flux:heading size="sm" class="mb-2">Producto Vencido</flux:heading>
                <ul class="text-sm space-y-1 text-zinc-600 dark:text-zinc-400">
                    <li>Fecha de vencimiento (obligatoria)</li>
                    <li>Número de lote</li>
                    <li>Plan de disposición del producto</li>
                </ul>
            </flux:card>

            <flux:card class="p-4 border-l-4 border-l-orange-500">
                <flux:heading size="sm" class="mb-2">Producto Dañado</flux:heading>
                <ul class="text-sm space-y-1 text-zinc-600 dark:text-zinc-400">
                    <li>Descripción del daño</li>
                    <li>Causa del daño (humedad, golpe, etc.)</li>
                    <li>Mejoras en almacenamiento</li>
                </ul>
            </flux:card>

            <flux:card class="p-4 border-l-4 border-l-blue-500">
                <flux:heading size="sm" class="mb-2">Corrección de Conteo</flux:heading>
                <ul class="text-sm space-y-1 text-zinc-600 dark:text-zinc-400">
                    <li>Referencia al conteo físico</li>
                    <li>Explicación del error detectado</li>
                    <li>Documento de inventario</li>
                </ul>
            </flux:card>
        </div>
    </section>

    <!-- Movimientos Generados -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Movimientos de Inventario
        </flux:heading>

        <flux:callout variant="info" icon="information-circle">
            Al procesar un ajuste, se genera un movimiento de tipo <strong>"adjustment"</strong> con razón
            <strong>ADJ_POS</strong> (positivo) o <strong>ADJ_NEG</strong> (negativo).
        </flux:callout>

        <div class="p-4 bg-zinc-50 dark:bg-zinc-800 rounded-lg font-mono text-sm">
            <div class="text-zinc-500 mb-2">Ejemplo de ajuste negativo procesado:</div>
            <div class="space-y-1 text-zinc-700 dark:text-zinc-300">
                <div>Tipo: <span class="text-purple-600">adjustment</span></div>
                <div>Razón: <span class="text-red-600">ADJ_NEG (Ajuste Negativo)</span></div>
                <div>Producto: Fertilizante NPK 15-15-15</div>
                <div>Cantidad: <span class="text-red-600">-5 sacos</span></div>
                <div>Saldo anterior: 45 sacos</div>
                <div>Nuevo saldo: <span class="font-bold">40 sacos</span></div>
                <div>Documento: ADJ-20241122-XYZ789</div>
            </div>
        </div>
    </section>

    <!-- Mejores Prácticas -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Mejores Prácticas
        </flux:heading>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <flux:card class="p-4 border-l-4 border-l-green-500">
                <flux:heading size="sm" class="mb-2 text-green-700 dark:text-green-400">Sí Hacer</flux:heading>
                <ul class="text-sm space-y-1 text-zinc-600 dark:text-zinc-400">
                    <li>Documentar siempre con justificación clara</li>
                    <li>Incluir acciones correctivas</li>
                    <li>Referenciar documentos de soporte</li>
                    <li>Procesar ajustes oportunamente</li>
                </ul>
            </flux:card>

            <flux:card class="p-4 border-l-4 border-l-red-500">
                <flux:heading size="sm" class="mb-2 text-red-700 dark:text-red-400">No Hacer</flux:heading>
                <ul class="text-sm space-y-1 text-zinc-600 dark:text-zinc-400">
                    <li>No omitir la justificación</li>
                    <li>No usar ajustes para operaciones normales</li>
                    <li>No procesar sin revisión previa</li>
                    <li>No ignorar ajustes rechazados</li>
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
            <flux:button variant="outline" icon="adjustments-horizontal" :href="route('adjustments.index')" wire:navigate class="justify-start">
                Ir a Ajustes
            </flux:button>
            <flux:button variant="outline" icon="plus" :href="route('adjustments.create')" wire:navigate class="justify-start">
                Nuevo Ajuste
            </flux:button>
            <flux:button variant="outline" icon="arrows-right-left" :href="route('inventory.movements.index')" wire:navigate class="justify-start">
                Ver Movimientos
            </flux:button>
        </div>
    </section>
</div>
