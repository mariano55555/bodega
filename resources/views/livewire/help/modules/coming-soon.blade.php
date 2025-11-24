<div class="space-y-8">
    <!-- Module Header -->
    <div class="border-b border-zinc-200 dark:border-zinc-700 pb-6">
        <div class="flex items-center gap-4 mb-4">
            <div class="p-3 bg-zinc-100 dark:bg-zinc-800 rounded-lg">
                <flux:icon name="{{ $module['icon'] ?? 'document-text' }}" class="h-8 w-8 text-zinc-600 dark:text-zinc-400" />
            </div>
            <div>
                <flux:heading size="xl" class="text-zinc-900 dark:text-zinc-100">
                    {{ $module['title'] ?? 'Documentación' }}
                </flux:heading>
                <flux:text class="text-zinc-600 dark:text-zinc-400">
                    {{ $module['description'] ?? '' }}
                </flux:text>
            </div>
        </div>
    </div>

    <!-- Coming Soon Message -->
    <section class="space-y-4">
        <div class="text-center py-12">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-amber-100 dark:bg-amber-900/30 mb-4">
                <flux:icon name="clock" class="h-8 w-8 text-amber-600 dark:text-amber-400" />
            </div>
            <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200 mb-2">
                Documentación en Desarrollo
            </flux:heading>
            <flux:text class="text-zinc-600 dark:text-zinc-400 max-w-md mx-auto">
                Estamos trabajando en la documentación de este módulo. Pronto estará disponible con guías detalladas y ejemplos prácticos.
            </flux:text>
        </div>
    </section>

    <!-- Quick Tips -->
    <section class="space-y-4">
        <flux:card class="p-6 bg-blue-50 dark:bg-blue-950 border border-blue-200 dark:border-blue-800">
            <flux:heading size="md" class="mb-3 text-blue-700 dark:text-blue-300">
                Mientras tanto...
            </flux:heading>
            <ul class="space-y-2 text-sm text-blue-600 dark:text-blue-400">
                <li class="flex items-start gap-2">
                    <flux:icon name="check-circle" class="h-5 w-5 mt-0.5 shrink-0" />
                    <span>Explore la interfaz del módulo para familiarizarse con sus funciones</span>
                </li>
                <li class="flex items-start gap-2">
                    <flux:icon name="check-circle" class="h-5 w-5 mt-0.5 shrink-0" />
                    <span>Consulte otros módulos relacionados para entender el flujo de trabajo</span>
                </li>
                <li class="flex items-start gap-2">
                    <flux:icon name="check-circle" class="h-5 w-5 mt-0.5 shrink-0" />
                    <span>Contacte al administrador del sistema si tiene dudas específicas</span>
                </li>
            </ul>
        </flux:card>
    </section>
</div>
