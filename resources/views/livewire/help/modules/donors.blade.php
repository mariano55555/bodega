<div class="space-y-8">
    <!-- Module Header -->
    <div class="border-b border-zinc-200 dark:border-zinc-700 pb-6">
        <div class="flex items-center gap-4 mb-4">
            <div class="p-3 bg-purple-100 dark:bg-purple-900 rounded-lg">
                <flux:icon name="gift" class="h-8 w-8 text-purple-600 dark:text-purple-400" />
            </div>
            <div>
                <flux:heading size="xl" class="text-zinc-900 dark:text-zinc-100">
                    Donantes
                </flux:heading>
                <flux:text class="text-zinc-600 dark:text-zinc-400">
                    Gestión de donantes y fuentes de donaciones
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
                Los <strong>Donantes</strong> son entidades o personas que contribuyen productos, equipos o recursos
                a la organización. Una correcta gestión de donantes permite mantener trazabilidad de las donaciones,
                generar reportes para rendición de cuentas y cumplir con requisitos de transparencia.
            </p>
        </div>
    </section>

    <!-- Tipos de Donantes -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Tipos de Donantes
        </flux:heading>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <flux:card class="p-4 border-l-4 border-l-zinc-500">
                <div class="flex items-center gap-2 mb-2">
                    <flux:badge color="zinc">Persona Individual</flux:badge>
                </div>
                <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                    Donantes particulares, ciudadanos individuales que desean contribuir.
                </flux:text>
            </flux:card>

            <flux:card class="p-4 border-l-4 border-l-blue-500">
                <div class="flex items-center gap-2 mb-2">
                    <flux:badge color="blue">Organización</flux:badge>
                </div>
                <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                    Empresas privadas, fundaciones y organizaciones con fines de RSE.
                </flux:text>
            </flux:card>

            <flux:card class="p-4 border-l-4 border-l-amber-500">
                <div class="flex items-center gap-2 mb-2">
                    <flux:badge color="amber">Gobierno</flux:badge>
                </div>
                <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                    Entidades gubernamentales, ministerios e instituciones públicas.
                </flux:text>
            </flux:card>

            <flux:card class="p-4 border-l-4 border-l-green-500">
                <div class="flex items-center gap-2 mb-2">
                    <flux:badge color="green">ONG</flux:badge>
                </div>
                <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                    Organizaciones No Gubernamentales nacionales o locales.
                </flux:text>
            </flux:card>

            <flux:card class="p-4 border-l-4 border-l-purple-500">
                <div class="flex items-center gap-2 mb-2">
                    <flux:badge color="purple">Org. Internacional</flux:badge>
                </div>
                <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                    Agencias internacionales como ONU, USAID, FAO, PMA, etc.
                </flux:text>
            </flux:card>
        </div>
    </section>

    <!-- Campos del Donante -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Campos del Donante
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
                        <td class="px-4 py-3">Nombre del donante</td>
                        <td class="px-4 py-3"><flux:badge color="green" size="sm">Sí</flux:badge></td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3 font-medium">Tipo de Donante</td>
                        <td class="px-4 py-3">Clasificación del donante</td>
                        <td class="px-4 py-3"><flux:badge color="green" size="sm">Sí</flux:badge></td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3 font-medium">Nombre Legal</td>
                        <td class="px-4 py-3">Razón social o nombre legal</td>
                        <td class="px-4 py-3"><flux:badge color="zinc" size="sm">No</flux:badge></td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3 font-medium">NIT/DUI</td>
                        <td class="px-4 py-3">Número de identificación</td>
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
                        <td class="px-4 py-3 font-medium">Persona de Contacto</td>
                        <td class="px-4 py-3">Nombre del contacto principal</td>
                        <td class="px-4 py-3"><flux:badge color="zinc" size="sm">No</flux:badge></td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3 font-medium">Dirección</td>
                        <td class="px-4 py-3">Dirección física completa</td>
                        <td class="px-4 py-3"><flux:badge color="zinc" size="sm">No</flux:badge></td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3 font-medium">Calificación</td>
                        <td class="px-4 py-3">Evaluación del donante (1-5)</td>
                        <td class="px-4 py-3"><flux:badge color="zinc" size="sm">No</flux:badge></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>

    <!-- Estados -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Estados del Donante
        </flux:heading>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <flux:card class="p-4 border-l-4 border-l-green-500">
                <div class="flex items-center gap-2 mb-2">
                    <flux:badge color="green">Activo</flux:badge>
                </div>
                <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                    Donante disponible para recibir donaciones. Aparece en el selector de donaciones.
                </flux:text>
            </flux:card>

            <flux:card class="p-4 border-l-4 border-l-red-500">
                <div class="flex items-center gap-2 mb-2">
                    <flux:badge color="red">Inactivo</flux:badge>
                </div>
                <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                    Donante temporalmente no disponible. No aparece en nuevas donaciones.
                </flux:text>
            </flux:card>
        </div>
    </section>

    <!-- Relación con Donaciones -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Relación con Donaciones
        </flux:heading>

        <flux:callout variant="info" icon="information-circle">
            Cada donación debe estar asociada a un donante. El sistema mantiene trazabilidad completa
            del origen de todos los productos donados para reportes y auditorías.
        </flux:callout>

        <div class="p-4 bg-zinc-50 dark:bg-zinc-800 rounded-lg">
            <flux:heading size="sm" class="mb-3">Información Registrada por Donación</flux:heading>
            <ul class="text-sm space-y-2 text-zinc-600 dark:text-zinc-400">
                <li class="flex items-center gap-2">
                    <flux:icon name="check" class="h-4 w-4 text-green-500" />
                    Donante y tipo de donante
                </li>
                <li class="flex items-center gap-2">
                    <flux:icon name="check" class="h-4 w-4 text-green-500" />
                    Productos donados con valores estimados
                </li>
                <li class="flex items-center gap-2">
                    <flux:icon name="check" class="h-4 w-4 text-green-500" />
                    Convenio o proyecto asociado
                </li>
                <li class="flex items-center gap-2">
                    <flux:icon name="check" class="h-4 w-4 text-green-500" />
                    Fecha y responsable de recepción
                </li>
            </ul>
        </div>
    </section>

    <!-- Flujo de Trabajo -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Crear un Nuevo Donante
        </flux:heading>

        <div class="space-y-3">
            <div class="flex items-start gap-4 p-4 bg-blue-50 dark:bg-blue-950 rounded-lg">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-400 font-bold text-sm">
                    1
                </div>
                <div>
                    <flux:heading size="sm">Acceder al Módulo</flux:heading>
                    <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                        Vaya a Donantes y haga clic en "Nuevo Donante".
                    </flux:text>
                </div>
            </div>

            <div class="flex items-start gap-4 p-4 bg-green-50 dark:bg-green-950 rounded-lg">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-green-100 dark:bg-green-900 text-green-600 dark:text-green-400 font-bold text-sm">
                    2
                </div>
                <div>
                    <flux:heading size="sm">Seleccionar Tipo</flux:heading>
                    <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                        Elija el tipo de donante: Individual, Organización, Gobierno, ONG u Org. Internacional.
                    </flux:text>
                </div>
            </div>

            <div class="flex items-start gap-4 p-4 bg-purple-50 dark:bg-purple-950 rounded-lg">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-purple-100 dark:bg-purple-900 text-purple-600 dark:text-purple-400 font-bold text-sm">
                    3
                </div>
                <div>
                    <flux:heading size="sm">Datos del Donante</flux:heading>
                    <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                        Complete nombre, identificación, contacto y dirección.
                    </flux:text>
                </div>
            </div>

            <div class="flex items-start gap-4 p-4 bg-orange-50 dark:bg-orange-950 rounded-lg">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-orange-100 dark:bg-orange-900 text-orange-600 dark:text-orange-400 font-bold text-sm">
                    4
                </div>
                <div>
                    <flux:heading size="sm">Notas y Convenios</flux:heading>
                    <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                        Agregue notas sobre convenios, acuerdos o información importante.
                    </flux:text>
                </div>
            </div>
        </div>
    </section>

    <!-- Reportes y Rendición de Cuentas -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Reportes y Rendición de Cuentas
        </flux:heading>

        <flux:callout variant="warning" icon="clock">
            <strong>Próximamente:</strong> Las siguientes funcionalidades estarán disponibles en futuras versiones del sistema.
        </flux:callout>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 opacity-60">
            <flux:card class="p-4 border-l-4 border-l-zinc-400">
                <flux:heading size="sm" class="mb-2">Reporte por Donante</flux:heading>
                <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                    Historial completo de donaciones recibidas de cada donante con valores y fechas.
                </flux:text>
            </flux:card>

            <flux:card class="p-4 border-l-4 border-l-zinc-400">
                <flux:heading size="sm" class="mb-2">Certificados de Donación</flux:heading>
                <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                    Generación de certificados oficiales para entregar a los donantes.
                </flux:text>
            </flux:card>

            <flux:card class="p-4 border-l-4 border-l-zinc-400">
                <flux:heading size="sm" class="mb-2">Reporte por Tipo</flux:heading>
                <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                    Análisis de donaciones agrupadas por tipo de donante.
                </flux:text>
            </flux:card>

            <flux:card class="p-4 border-l-4 border-l-zinc-400">
                <flux:heading size="sm" class="mb-2">Trazabilidad de Bienes</flux:heading>
                <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                    Seguimiento del uso de bienes donados para informes a cooperantes.
                </flux:text>
            </flux:card>
        </div>
    </section>

    <!-- Restricciones -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Restricciones
        </flux:heading>

        <flux:callout variant="warning" icon="exclamation-triangle">
            Un donante <strong>NO</strong> puede ser eliminado si tiene donaciones asociadas.
            En este caso, la opción recomendada es <strong>desactivar</strong> el donante.
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
                    <li>Clasificar correctamente el tipo de donante</li>
                    <li>Registrar información completa</li>
                    <li>Documentar convenios y acuerdos</li>
                    <li>Mantener contactos actualizados</li>
                    <li>Generar certificados de recepción</li>
                </ul>
            </flux:card>

            <flux:card class="p-4 border-l-4 border-l-red-500">
                <flux:heading size="sm" class="mb-2 text-red-700 dark:text-red-400">Evitar</flux:heading>
                <ul class="text-sm space-y-1 text-zinc-600 dark:text-zinc-400">
                    <li>Donantes sin tipo definido</li>
                    <li>Información incompleta de contacto</li>
                    <li>No registrar convenios vigentes</li>
                    <li>Eliminar donantes con historial</li>
                    <li>No generar reportes de rendición</li>
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
            <flux:button variant="outline" icon="gift" :href="route('donors.index')" wire:navigate class="justify-start">
                Ir a Donantes
            </flux:button>
            <flux:button variant="outline" icon="plus" :href="route('donors.create')" wire:navigate class="justify-start">
                Nuevo Donante
            </flux:button>
            <flux:button variant="outline" icon="inbox-arrow-down" :href="route('donations.index')" wire:navigate class="justify-start">
                Ver Donaciones
            </flux:button>
        </div>
    </section>
</div>
