<div class="space-y-8">
    <!-- Module Header -->
    <div class="border-b border-zinc-200 dark:border-zinc-700 pb-6">
        <div class="flex items-center gap-4 mb-4">
            <div class="p-3 bg-cyan-100 dark:bg-cyan-900 rounded-lg">
                <flux:icon name="users" class="h-8 w-8 text-cyan-600 dark:text-cyan-400" />
            </div>
            <div>
                <flux:heading size="xl" class="text-zinc-900 dark:text-zinc-100">
                    Clientes
                </flux:heading>
                <flux:text class="text-zinc-600 dark:text-zinc-400">
                    Gestión de clientes y destinatarios de despachos
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
                Los <strong>Clientes</strong> son las entidades o personas que reciben productos desde la organización,
                ya sea por venta, distribución o despacho. Una correcta gestión de clientes permite mantener información
                actualizada de facturación, envío y términos comerciales.
            </p>
        </div>
    </section>

    <!-- Tipos de Clientes -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Tipos de Clientes
        </flux:heading>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <flux:card class="p-4 border-l-4 border-l-zinc-500">
                <div class="flex items-center gap-2 mb-2">
                    <flux:badge color="zinc">Individual</flux:badge>
                </div>
                <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                    Personas naturales, consumidores finales o participantes de programas.
                </flux:text>
            </flux:card>

            <flux:card class="p-4 border-l-4 border-l-blue-500">
                <div class="flex items-center gap-2 mb-2">
                    <flux:badge color="blue">Empresa</flux:badge>
                </div>
                <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                    Personas jurídicas, negocios, cooperativas e instituciones.
                </flux:text>
            </flux:card>
        </div>
    </section>

    <!-- Campos del Cliente -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Campos del Cliente
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
                        <td class="px-4 py-3">Nombre del cliente</td>
                        <td class="px-4 py-3"><flux:badge color="green" size="sm">Sí</flux:badge></td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3 font-medium">Tipo</td>
                        <td class="px-4 py-3">Individual o Empresa</td>
                        <td class="px-4 py-3"><flux:badge color="green" size="sm">Sí</flux:badge></td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3 font-medium">Nombre de Empresa</td>
                        <td class="px-4 py-3">Razón social (solo tipo Empresa)</td>
                        <td class="px-4 py-3"><flux:badge color="amber" size="sm">Condicional</flux:badge></td>
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
                        <td class="px-4 py-3 font-medium">Teléfono / Celular</td>
                        <td class="px-4 py-3">Números de contacto</td>
                        <td class="px-4 py-3"><flux:badge color="zinc" size="sm">No</flux:badge></td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3 font-medium">Nombre de Contacto</td>
                        <td class="px-4 py-3">Persona de contacto para entregas</td>
                        <td class="px-4 py-3"><flux:badge color="zinc" size="sm">No</flux:badge></td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3 font-medium">Dirección Facturación</td>
                        <td class="px-4 py-3">Dirección para documentos fiscales</td>
                        <td class="px-4 py-3"><flux:badge color="zinc" size="sm">No</flux:badge></td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3 font-medium">Dirección Envío</td>
                        <td class="px-4 py-3">Dirección para entregas físicas</td>
                        <td class="px-4 py-3"><flux:badge color="zinc" size="sm">No</flux:badge></td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3 font-medium">Días de Crédito</td>
                        <td class="px-4 py-3">Plazo de pago en días</td>
                        <td class="px-4 py-3"><flux:badge color="zinc" size="sm">No</flux:badge></td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3 font-medium">Límite de Crédito</td>
                        <td class="px-4 py-3">Monto máximo de crédito</td>
                        <td class="px-4 py-3"><flux:badge color="zinc" size="sm">No</flux:badge></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>

    <!-- Direcciones -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Gestión de Direcciones
        </flux:heading>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <flux:card class="p-4 border-l-4 border-l-blue-500">
                <flux:heading size="sm" class="mb-2">Dirección de Facturación</flux:heading>
                <flux:text class="text-sm text-zinc-600 dark:text-zinc-400 mb-2">
                    Utilizada para documentos fiscales y cobros.
                </flux:text>
                <ul class="text-sm space-y-1 text-zinc-600 dark:text-zinc-400">
                    <li>Dirección completa</li>
                    <li>Ciudad y Departamento</li>
                    <li>Código Postal</li>
                    <li>País</li>
                </ul>
            </flux:card>

            <flux:card class="p-4 border-l-4 border-l-green-500">
                <flux:heading size="sm" class="mb-2">Dirección de Envío</flux:heading>
                <flux:text class="text-sm text-zinc-600 dark:text-zinc-400 mb-2">
                    Utilizada para entregas de productos.
                </flux:text>
                <ul class="text-sm space-y-1 text-zinc-600 dark:text-zinc-400">
                    <li>Puede ser igual a facturación</li>
                    <li>O una dirección diferente</li>
                    <li>Persona de contacto para entrega</li>
                    <li>Horarios de recepción</li>
                </ul>
            </flux:card>
        </div>

        <flux:callout variant="info" icon="information-circle">
            Active la opción "Misma que facturación" para usar automáticamente la dirección de facturación
            como dirección de envío.
        </flux:callout>
    </section>

    <!-- Estados -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Estados del Cliente
        </flux:heading>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <flux:card class="p-4 border-l-4 border-l-green-500">
                <div class="flex items-center gap-2 mb-2">
                    <flux:badge color="green">Activo</flux:badge>
                </div>
                <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                    Cliente disponible para operaciones. Aparece en el selector de despachos.
                </flux:text>
            </flux:card>

            <flux:card class="p-4 border-l-4 border-l-red-500">
                <div class="flex items-center gap-2 mb-2">
                    <flux:badge color="red">Inactivo</flux:badge>
                </div>
                <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                    Cliente temporalmente no disponible. No aparece en nuevos despachos.
                </flux:text>
            </flux:card>
        </div>
    </section>

    <!-- Términos Comerciales -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Términos Comerciales
        </flux:heading>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-zinc-100 dark:bg-zinc-800">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium">Término</th>
                        <th class="px-4 py-3 text-left font-medium">Descripción</th>
                        <th class="px-4 py-3 text-left font-medium">Ejemplo</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    <tr>
                        <td class="px-4 py-3 font-medium">Contado</td>
                        <td class="px-4 py-3">Pago al momento de la entrega</td>
                        <td class="px-4 py-3">0 días de crédito</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3 font-medium">Crédito 15 días</td>
                        <td class="px-4 py-3">Pago a 15 días de la factura</td>
                        <td class="px-4 py-3">Clientes frecuentes</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3 font-medium">Crédito 30 días</td>
                        <td class="px-4 py-3">Pago a 30 días de la factura</td>
                        <td class="px-4 py-3">Cooperativas, instituciones</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3 font-medium">Crédito 45+ días</td>
                        <td class="px-4 py-3">Plazos extendidos especiales</td>
                        <td class="px-4 py-3">Mayoristas, convenios</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>

    <!-- Relación con Despachos -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Relación con Despachos
        </flux:heading>

        <flux:callout variant="info" icon="information-circle">
            Cada despacho debe estar asociado a un cliente. Al crear un despacho, se muestran
            los datos del cliente incluyendo dirección de envío y términos de pago.
        </flux:callout>

        <div class="p-4 bg-zinc-50 dark:bg-zinc-800 rounded-lg">
            <flux:heading size="sm" class="mb-3">Información del Cliente en Despachos</flux:heading>
            <ul class="text-sm space-y-2 text-zinc-600 dark:text-zinc-400">
                <li class="flex items-center gap-2">
                    <flux:icon name="check" class="h-4 w-4 text-green-500" />
                    Nombre y datos de contacto
                </li>
                <li class="flex items-center gap-2">
                    <flux:icon name="check" class="h-4 w-4 text-green-500" />
                    Dirección de envío para la entrega
                </li>
                <li class="flex items-center gap-2">
                    <flux:icon name="check" class="h-4 w-4 text-green-500" />
                    Términos de pago acordados
                </li>
                <li class="flex items-center gap-2">
                    <flux:icon name="check" class="h-4 w-4 text-green-500" />
                    Crédito disponible (si aplica)
                </li>
            </ul>
        </div>
    </section>

    <!-- Flujo de Trabajo -->
    <section class="space-y-4">
        <flux:heading size="lg" class="text-zinc-800 dark:text-zinc-200">
            Crear un Nuevo Cliente
        </flux:heading>

        <div class="space-y-3">
            <div class="flex items-start gap-4 p-4 bg-blue-50 dark:bg-blue-950 rounded-lg">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-400 font-bold text-sm">
                    1
                </div>
                <div>
                    <flux:heading size="sm">Acceder al Módulo</flux:heading>
                    <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                        Vaya a Clientes y haga clic en "Nuevo Cliente".
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
                        Elija si es cliente Individual o Empresa. Si es Empresa, complete el nombre comercial.
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
                        Complete NIT/DUI, email, teléfono y persona de contacto.
                    </flux:text>
                </div>
            </div>

            <div class="flex items-start gap-4 p-4 bg-cyan-50 dark:bg-cyan-950 rounded-lg">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-cyan-100 dark:bg-cyan-900 text-cyan-600 dark:text-cyan-400 font-bold text-sm">
                    4
                </div>
                <div>
                    <flux:heading size="sm">Direcciones</flux:heading>
                    <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                        Configure la dirección de facturación y, si es diferente, la dirección de envío.
                    </flux:text>
                </div>
            </div>

            <div class="flex items-start gap-4 p-4 bg-orange-50 dark:bg-orange-950 rounded-lg">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-orange-100 dark:bg-orange-900 text-orange-600 dark:text-orange-400 font-bold text-sm">
                    5
                </div>
                <div>
                    <flux:heading size="sm">Términos Comerciales</flux:heading>
                    <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                        Defina días de crédito, método de pago y límite de crédito si aplica.
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
            Un cliente <strong>NO</strong> puede ser eliminado si tiene despachos asociados.
            En este caso, la opción recomendada es <strong>desactivar</strong> el cliente.
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
                    <li>Clasificar correctamente Individual/Empresa</li>
                    <li>Mantener direcciones actualizadas</li>
                    <li>Documentar términos de pago</li>
                    <li>Establecer límites de crédito realistas</li>
                    <li>Revisar clientes con crédito vencido</li>
                </ul>
            </flux:card>

            <flux:card class="p-4 border-l-4 border-l-red-500">
                <flux:heading size="sm" class="mb-2 text-red-700 dark:text-red-400">Evitar</flux:heading>
                <ul class="text-sm space-y-1 text-zinc-600 dark:text-zinc-400">
                    <li>Clientes sin información de contacto</li>
                    <li>Direcciones incompletas o desactualizadas</li>
                    <li>NIT duplicados o incorrectos</li>
                    <li>Eliminar clientes con historial</li>
                    <li>Crédito sin límite definido</li>
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
            <flux:button variant="outline" icon="users" :href="route('customers.index')" wire:navigate class="justify-start">
                Ir a Clientes
            </flux:button>
            <flux:button variant="outline" icon="plus" :href="route('customers.create')" wire:navigate class="justify-start">
                Nuevo Cliente
            </flux:button>
            <flux:button variant="outline" icon="truck" :href="route('dispatches.index')" wire:navigate class="justify-start">
                Ver Despachos
            </flux:button>
        </div>
    </section>
</div>
