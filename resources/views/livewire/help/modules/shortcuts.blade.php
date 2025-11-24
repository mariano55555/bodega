<div class="space-y-8">
    <!-- Module Header -->
    <div class="border-b border-zinc-200 dark:border-zinc-700 pb-6">
        <div class="flex items-center gap-4 mb-4">
            <div class="p-3 bg-zinc-100 dark:bg-zinc-800 rounded-lg">
                <flux:icon name="command-line" class="h-8 w-8 text-zinc-600 dark:text-zinc-400" />
            </div>
            <div>
                <flux:heading size="xl" class="text-zinc-900 dark:text-zinc-100">
                    Atajos de Teclado
                </flux:heading>
                <flux:text class="text-zinc-600 dark:text-zinc-400">
                    Navegacion rapida con atajos de teclado
                </flux:text>
            </div>
        </div>
    </div>

    <!-- Introduccion -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Introduccion
        </flux:heading>
        <div class="prose dark:prose-invert max-w-none">
            <p>
                El sistema incluye atajos de teclado para navegar rapidamente entre las diferentes secciones
                sin necesidad de usar el mouse. Estos atajos estan disponibles en todas las paginas del sistema.
            </p>
        </div>

        <flux:callout variant="info" icon="information-circle">
            <strong>Consejo:</strong> Presiona <kbd class="px-2 py-1 bg-zinc-200 dark:bg-zinc-700 rounded text-sm">?</kbd> en cualquier momento para ver la lista completa de atajos disponibles.
        </flux:callout>
    </section>

    <!-- Atajos Generales -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Atajos Generales
        </flux:heading>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <div class="flex items-center justify-between p-3 bg-zinc-50 dark:bg-zinc-800 rounded-lg">
                <span class="text-sm font-medium">Mostrar atajos</span>
                <div class="flex gap-1">
                    <kbd class="px-2 py-1 text-xs bg-zinc-200 dark:bg-zinc-700 rounded font-mono">?</kbd>
                    <span class="text-zinc-400 text-xs">o</span>
                    <kbd class="px-2 py-1 text-xs bg-zinc-200 dark:bg-zinc-700 rounded font-mono">Ctrl</kbd>
                    <kbd class="px-2 py-1 text-xs bg-zinc-200 dark:bg-zinc-700 rounded font-mono">/</kbd>
                </div>
            </div>
            <div class="flex items-center justify-between p-3 bg-zinc-50 dark:bg-zinc-800 rounded-lg">
                <span class="text-sm font-medium">Enfocar busqueda</span>
                <kbd class="px-2 py-1 text-xs bg-zinc-200 dark:bg-zinc-700 rounded font-mono">/</kbd>
            </div>
            <div class="flex items-center justify-between p-3 bg-zinc-50 dark:bg-zinc-800 rounded-lg">
                <span class="text-sm font-medium">Cerrar modal / Limpiar</span>
                <kbd class="px-2 py-1 text-xs bg-zinc-200 dark:bg-zinc-700 rounded font-mono">Esc</kbd>
            </div>
        </div>
    </section>

    <!-- Atajos de Navegacion -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Atajos de Navegacion
        </flux:heading>

        <flux:callout variant="warning" icon="light-bulb">
            Los atajos de navegacion funcionan presionando <kbd class="px-1 bg-amber-100 dark:bg-amber-800 rounded">g</kbd> seguido de la letra correspondiente dentro de medio segundo.
        </flux:callout>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <div class="flex items-center justify-between p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                <div class="flex items-center gap-2">
                    <flux:icon name="squares-2x2" class="h-5 w-5 text-blue-600" />
                    <span class="text-sm font-medium">Dashboard</span>
                </div>
                <div class="flex gap-1">
                    <kbd class="px-2 py-1 text-xs bg-blue-100 dark:bg-blue-800 rounded font-mono">g</kbd>
                    <kbd class="px-2 py-1 text-xs bg-blue-100 dark:bg-blue-800 rounded font-mono">d</kbd>
                </div>
            </div>

            <div class="flex items-center justify-between p-3 bg-emerald-50 dark:bg-emerald-900/20 rounded-lg border border-emerald-200 dark:border-emerald-800">
                <div class="flex items-center gap-2">
                    <flux:icon name="cube" class="h-5 w-5 text-emerald-600" />
                    <span class="text-sm font-medium">Inventario</span>
                </div>
                <div class="flex gap-1">
                    <kbd class="px-2 py-1 text-xs bg-emerald-100 dark:bg-emerald-800 rounded font-mono">g</kbd>
                    <kbd class="px-2 py-1 text-xs bg-emerald-100 dark:bg-emerald-800 rounded font-mono">i</kbd>
                </div>
            </div>

            <div class="flex items-center justify-between p-3 bg-rose-50 dark:bg-rose-900/20 rounded-lg border border-rose-200 dark:border-rose-800">
                <div class="flex items-center gap-2">
                    <flux:icon name="tag" class="h-5 w-5 text-rose-600" />
                    <span class="text-sm font-medium">Productos</span>
                </div>
                <div class="flex gap-1">
                    <kbd class="px-2 py-1 text-xs bg-rose-100 dark:bg-rose-800 rounded font-mono">g</kbd>
                    <kbd class="px-2 py-1 text-xs bg-rose-100 dark:bg-rose-800 rounded font-mono">p</kbd>
                </div>
            </div>

            <div class="flex items-center justify-between p-3 bg-violet-50 dark:bg-violet-900/20 rounded-lg border border-violet-200 dark:border-violet-800">
                <div class="flex items-center gap-2">
                    <flux:icon name="arrows-right-left" class="h-5 w-5 text-violet-600" />
                    <span class="text-sm font-medium">Traslados</span>
                </div>
                <div class="flex gap-1">
                    <kbd class="px-2 py-1 text-xs bg-violet-100 dark:bg-violet-800 rounded font-mono">g</kbd>
                    <kbd class="px-2 py-1 text-xs bg-violet-100 dark:bg-violet-800 rounded font-mono">t</kbd>
                </div>
            </div>

            <div class="flex items-center justify-between p-3 bg-cyan-50 dark:bg-cyan-900/20 rounded-lg border border-cyan-200 dark:border-cyan-800">
                <div class="flex items-center gap-2">
                    <flux:icon name="shopping-cart" class="h-5 w-5 text-cyan-600" />
                    <span class="text-sm font-medium">Compras</span>
                </div>
                <div class="flex gap-1">
                    <kbd class="px-2 py-1 text-xs bg-cyan-100 dark:bg-cyan-800 rounded font-mono">g</kbd>
                    <kbd class="px-2 py-1 text-xs bg-cyan-100 dark:bg-cyan-800 rounded font-mono">c</kbd>
                </div>
            </div>

            <div class="flex items-center justify-between p-3 bg-teal-50 dark:bg-teal-900/20 rounded-lg border border-teal-200 dark:border-teal-800">
                <div class="flex items-center gap-2">
                    <flux:icon name="chart-bar" class="h-5 w-5 text-teal-600" />
                    <span class="text-sm font-medium">Reportes</span>
                </div>
                <div class="flex gap-1">
                    <kbd class="px-2 py-1 text-xs bg-teal-100 dark:bg-teal-800 rounded font-mono">g</kbd>
                    <kbd class="px-2 py-1 text-xs bg-teal-100 dark:bg-teal-800 rounded font-mono">r</kbd>
                </div>
            </div>

            <div class="flex items-center justify-between p-3 bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-200 dark:border-red-800">
                <div class="flex items-center gap-2">
                    <flux:icon name="bell" class="h-5 w-5 text-red-600" />
                    <span class="text-sm font-medium">Notificaciones</span>
                </div>
                <div class="flex gap-1">
                    <kbd class="px-2 py-1 text-xs bg-red-100 dark:bg-red-800 rounded font-mono">g</kbd>
                    <kbd class="px-2 py-1 text-xs bg-red-100 dark:bg-red-800 rounded font-mono">n</kbd>
                </div>
            </div>

            <div class="flex items-center justify-between p-3 bg-amber-50 dark:bg-amber-900/20 rounded-lg border border-amber-200 dark:border-amber-800">
                <div class="flex items-center gap-2">
                    <flux:icon name="question-mark-circle" class="h-5 w-5 text-amber-600" />
                    <span class="text-sm font-medium">Ayuda</span>
                </div>
                <div class="flex gap-1">
                    <kbd class="px-2 py-1 text-xs bg-amber-100 dark:bg-amber-800 rounded font-mono">g</kbd>
                    <kbd class="px-2 py-1 text-xs bg-amber-100 dark:bg-amber-800 rounded font-mono">h</kbd>
                </div>
            </div>
        </div>
    </section>

    <!-- Resumen Visual -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Resumen Rapido
        </flux:heading>

        <flux:card class="overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-zinc-100 dark:bg-zinc-800">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium">Atajo</th>
                        <th class="px-4 py-3 text-left font-medium">Accion</th>
                        <th class="px-4 py-3 text-left font-medium">Descripcion</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    <tr>
                        <td class="px-4 py-3"><kbd class="px-2 py-1 text-xs bg-zinc-200 dark:bg-zinc-700 rounded">?</kbd></td>
                        <td class="px-4 py-3">Mostrar atajos</td>
                        <td class="px-4 py-3 text-zinc-500">Abre el modal con todos los atajos</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3"><kbd class="px-2 py-1 text-xs bg-zinc-200 dark:bg-zinc-700 rounded">/</kbd></td>
                        <td class="px-4 py-3">Buscar</td>
                        <td class="px-4 py-3 text-zinc-500">Enfoca el campo de busqueda</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3"><kbd class="px-2 py-1 text-xs bg-zinc-200 dark:bg-zinc-700 rounded">Esc</kbd></td>
                        <td class="px-4 py-3">Cerrar/Limpiar</td>
                        <td class="px-4 py-3 text-zinc-500">Cierra modales o limpia busquedas</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3">
                            <kbd class="px-2 py-1 text-xs bg-zinc-200 dark:bg-zinc-700 rounded">g</kbd>
                            <kbd class="px-2 py-1 text-xs bg-zinc-200 dark:bg-zinc-700 rounded">x</kbd>
                        </td>
                        <td class="px-4 py-3">Navegacion</td>
                        <td class="px-4 py-3 text-zinc-500">g + letra para ir a la seccion</td>
                    </tr>
                </tbody>
            </table>
        </flux:card>
    </section>

    <!-- Notas -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Notas Importantes
        </flux:heading>

        <div class="space-y-3">
            <div class="flex items-start gap-3 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                <flux:icon name="information-circle" class="h-5 w-5 text-blue-600 shrink-0 mt-0.5" />
                <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                    Los atajos de navegacion solo funcionan cuando no estas escribiendo en un campo de texto, select o textarea.
                </flux:text>
            </div>

            <div class="flex items-start gap-3 p-4 bg-amber-50 dark:bg-amber-900/20 rounded-lg">
                <flux:icon name="light-bulb" class="h-5 w-5 text-amber-600 shrink-0 mt-0.5" />
                <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                    Para los atajos de navegacion (g + letra), tienes medio segundo entre presionar la primera tecla y la segunda.
                </flux:text>
            </div>
        </div>
    </section>
</div>
