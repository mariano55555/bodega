<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        <flux:sidebar sticky stashable class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.toggle class="lg:hidden" icon="x-mark" />

            <div class="flex items-center justify-between w-full">
                <a href="{{ route('dashboard') }}" class="flex items-center space-x-2 rtl:space-x-reverse" wire:navigate>
                    <x-app-logo />
                </a>
                <!-- Desktop Notification Bell -->
                <div class="hidden lg:block">
                    <livewire:notifications.dropdown />
                </div>
            </div>

            <flux:navlist variant="outline">
                <flux:navlist.group :heading="__('Platform')" class="grid">
                    <flux:navlist.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>{{ __('Dashboard') }}</flux:navlist.item>
                </flux:navlist.group>

                <!-- 🏭 Operaciones de Bodega -->
                <flux:navlist.group :heading="__('Operaciones de Bodega')" class="grid">
                    <flux:navlist.item icon="cube" :href="route('inventory.products.index')" :current="request()->routeIs('inventory.products.*')" wire:navigate>{{ __('Catálogo de Productos') }}</flux:navlist.item>
                    <flux:navlist.item icon="shopping-cart" :href="route('purchases.index')" :current="request()->routeIs('purchases.index') || request()->routeIs('purchases.create') || request()->routeIs('purchases.show') || request()->routeIs('purchases.edit')" wire:navigate>{{ __('Compras') }}</flux:navlist.item>
                    <flux:navlist.item icon="gift" :href="route('donations.index')" :current="request()->routeIs('donations.*')" wire:navigate>{{ __('Donaciones') }}</flux:navlist.item>
                    <flux:navlist.item icon="arrow-path" :href="route('transfers.index')" :current="request()->routeIs('transfers.*')" wire:navigate>{{ __('Traslados') }}</flux:navlist.item>
                    <flux:navlist.item icon="truck" :href="route('dispatches.index')" :current="request()->routeIs('dispatches.*')" wire:navigate>{{ __('Despachos') }}</flux:navlist.item>
                    <flux:navlist.item icon="adjustments-horizontal" :href="route('adjustments.index')" :current="request()->routeIs('adjustments.*')" wire:navigate>{{ __('Ajustes de Inventario') }}</flux:navlist.item>
                    <flux:navlist.item icon="lock-closed" :href="route('closures.index')" :current="request()->routeIs('closures.*')" wire:navigate>{{ __('Cierres Mensuales') }}</flux:navlist.item>
                </flux:navlist.group>

                <!-- 👥 Catálogos -->
                <flux:navlist.group :heading="__('Catálogos')" class="grid">
                    <flux:navlist.item icon="tag" :href="route('admin.categories.index')" :current="request()->routeIs('admin.categories.*')" wire:navigate>{{ __('Categorías de Productos') }}</flux:navlist.item>
                    <flux:navlist.item icon="scale" :href="route('admin.units.index')" :current="request()->routeIs('admin.units.*')" wire:navigate>{{ __('Unidades de Medida') }}</flux:navlist.item>
                    <flux:navlist.item icon="building-storefront" :href="route('purchases.suppliers.index')" :current="request()->routeIs('purchases.suppliers.*')" wire:navigate>{{ __('Proveedores') }}</flux:navlist.item>
                    <flux:navlist.item icon="heart" :href="route('donors.index')" :current="request()->routeIs('donors.*')" wire:navigate>{{ __('Donantes') }}</flux:navlist.item>
                    <flux:navlist.item icon="users" :href="route('customers.index')" :current="request()->routeIs('customers.*')" wire:navigate>{{ __('Clientes') }}</flux:navlist.item>
                    <flux:navlist.item icon="arrow-up-tray" :href="route('imports.index')" :current="request()->routeIs('imports.*')" wire:navigate>{{ __('Importación de Datos') }}</flux:navlist.item>
                </flux:navlist.group>

                <!-- 🏢 Gestión de Almacenes -->
                <flux:navlist.group :heading="__('Gestión de Almacenes')" class="grid">
                    <flux:navlist.item icon="building-office-2" :href="route('warehouse.dashboard')" :current="request()->routeIs('warehouse.dashboard')" wire:navigate>{{ __('Resumen General') }}</flux:navlist.item>
                    <flux:navlist.item icon="building-office-2" :href="route('warehouse.companies.index')" :current="request()->routeIs('warehouse.companies.*')" wire:navigate>{{ __('Empresas') }}</flux:navlist.item>
                    <flux:navlist.item icon="building-storefront" :href="route('warehouse.branches.index')" :current="request()->routeIs('warehouse.branches.*')" wire:navigate>{{ __('Sucursales') }}</flux:navlist.item>
                    <flux:navlist.item icon="building-office" :href="route('warehouse.warehouses.index')" :current="request()->routeIs('warehouse.warehouses.*')" wire:navigate>{{ __('Bodegas') }}</flux:navlist.item>
                    <flux:navlist.item icon="map-pin" :href="route('storage-locations.index')" :current="request()->routeIs('storage-locations.*')" wire:navigate>{{ __('Ubicaciones de Almacenamiento') }}</flux:navlist.item>
                    <flux:navlist.item icon="chart-pie" :href="route('warehouse.capacity.index')" :current="request()->routeIs('warehouse.capacity.*')" wire:navigate>{{ __('Capacidad de Almacenes') }}</flux:navlist.item>
                </flux:navlist.group>

                <!-- 🔍 Consultas e Inventario -->
                <flux:navlist.group :heading="__('Consultas e Inventario')" class="grid">
                    <flux:navlist.item icon="squares-2x2" :href="route('inventory.dashboard')" :current="request()->routeIs('inventory.dashboard')" wire:navigate>{{ __('Resumen de Inventario') }}</flux:navlist.item>
                    <flux:navlist.item icon="magnifying-glass" :href="route('inventory.stock.query')" :current="request()->routeIs('inventory.stock.query')" wire:navigate>{{ __('Consulta de Existencias') }}</flux:navlist.item>
                    <flux:navlist.item icon="arrows-right-left" :href="route('inventory.movements.index')" :current="request()->routeIs('inventory.movements.*')" wire:navigate>{{ __('Consulta de Movimientos') }}</flux:navlist.item>
                    <flux:navlist.item icon="qr-code" :href="route('inventory.scanner')" :current="request()->routeIs('inventory.scanner')" wire:navigate>{{ __('Escáner de Códigos') }}</flux:navlist.item>
                    <flux:navlist.item icon="bell" :href="route('inventory.alerts.index')" :current="request()->routeIs('inventory.alerts.*')" wire:navigate>{{ __('Alertas de Stock') }}</flux:navlist.item>
                    <flux:navlist.item icon="clock" :href="route('traceability.product-timeline')" :current="request()->routeIs('traceability.*')" wire:navigate>{{ __('Trazabilidad Histórica') }}</flux:navlist.item>
                </flux:navlist.group>

                <!-- 📊 Reportería -->
                <flux:navlist.group :heading="__('Reportería')" class="grid">
                    <flux:navlist.item icon="document-text" :href="route('reports.inventory.consolidated')" :current="request()->routeIs('reports.inventory.consolidated')" wire:navigate>{{ __('Inventario Consolidado') }}</flux:navlist.item>
                    <flux:navlist.item icon="document-chart-bar" :href="route('reports.kardex')" :current="request()->routeIs('reports.kardex')" wire:navigate>{{ __('Reportes de Kardex') }}</flux:navlist.item>
                    <flux:navlist.item icon="chart-bar" :href="route('reports.movements.monthly')" :current="request()->routeIs('reports.movements.*')" wire:navigate>{{ __('Reportes de Movimientos') }}</flux:navlist.item>
                    <flux:navlist.item icon="currency-dollar" :href="route('reports.administrative')" :current="request()->routeIs('reports.administrative')" wire:navigate>{{ __('Reportes Administrativos') }}</flux:navlist.item>
                    <flux:navlist.item icon="cog" :href="route('reports.custom')" :current="request()->routeIs('reports.custom')" wire:navigate>{{ __('Reportes Personalizados') }}</flux:navlist.item>
                    <flux:navlist.item icon="arrow-down-tray" :href="route('reports.exports')" :current="request()->routeIs('reports.exports')" wire:navigate>{{ __('Exportación de Datos') }}</flux:navlist.item>
                </flux:navlist.group>

                <!-- 👥 Control de Usuarios -->
                <flux:navlist.group :heading="__('Control de Usuarios')" class="grid">
                    <flux:navlist.item icon="users" :href="route('admin.users.index')" :current="request()->routeIs('admin.users.*')" wire:navigate>{{ __('Gestión de Usuarios') }}</flux:navlist.item>
                    <flux:navlist.item icon="shield-check" :href="route('admin.roles.index')" :current="request()->routeIs('admin.roles.*')" wire:navigate>{{ __('Gestión de Roles') }}</flux:navlist.item>
                    <flux:navlist.item icon="key" :href="route('admin.permissions.index')" :current="request()->routeIs('admin.permissions.*')" wire:navigate>{{ __('Gestión de Permisos') }}</flux:navlist.item>
                    <flux:navlist.item icon="clipboard-document-list" :href="route('admin.activity-logs.index')" :current="request()->routeIs('admin.activity-logs.*')" wire:navigate>{{ __('Bitácora de Actividades') }}</flux:navlist.item>
                </flux:navlist.group>
            </flux:navlist>

            <flux:spacer />

            <flux:navlist variant="outline">
                <flux:navlist.item icon="book-open" :href="route('help.index')" :current="request()->routeIs('help.*')" wire:navigate>
                {{ __('Documentación') }}
                </flux:navlist.item>
            </flux:navlist>

            <!-- Desktop User Menu -->
            <flux:dropdown class="hidden lg:block" position="bottom" align="start">
                    <flux:profile
                        :name="auth()->user()->name"
                        :initials="auth()->user()->initials()"
                        icon:trailing="chevrons-up-down"
                    />

                <flux:menu class="w-[220px]">
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                                    <span
                                        class="flex h-full w-full items-center justify-center rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white"
                                    >
                                        {{ auth()->user()->initials() }}
                                    </span>
                                </span>

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <span class="truncate font-semibold">{{ auth()->user()->name }}</span>
                                    <span class="truncate text-xs">{{ auth()->user()->email }}</span>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>{{ __('Configuración') }}</flux:menu.item>
                        <flux:menu.item :href="route('notifications.index')" icon="bell" wire:navigate>{{ __('Notificaciones') }}</flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full" data-test="logout-button">
                            {{ __('Cerrar Sesión') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:sidebar>

        <!-- Mobile User Menu -->
        <flux:header class="lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <flux:spacer />

            <!-- Mobile Notification Bell -->
            <livewire:notifications.dropdown />

            <flux:dropdown position="top" align="end">
                <flux:profile
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevron-down"
                />

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                                    <span
                                        class="flex h-full w-full items-center justify-center rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white"
                                    >
                                        {{ auth()->user()->initials() }}
                                    </span>
                                </span>

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <span class="truncate font-semibold">{{ auth()->user()->name }}</span>
                                    <span class="truncate text-xs">{{ auth()->user()->email }}</span>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>{{ __('Configuración') }}</flux:menu.item>
                        <flux:menu.item :href="route('notifications.index')" icon="bell" wire:navigate>{{ __('Notificaciones') }}</flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full" data-test="logout-button">
                            {{ __('Cerrar Sesión') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        {{ $slot }}

        @fluxScripts
        @persist('toast')
            <flux:toast />
        @endpersist
    </body>
</html>
