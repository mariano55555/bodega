<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Inventory Management Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used for inventory management
    | throughout the application including products, stock movements,
    | alerts, and transfer operations.
    |
    */

    // General Terms
    'inventory' => 'Inventario',
    'management' => 'Gestión',
    'stock' => 'Existencias',
    'product' => 'Producto',
    'products' => 'Productos',
    'item' => 'Artículo',
    'items' => 'Artículos',
    'sku' => 'SKU',
    'barcode' => 'Código de Barras',
    'qr_code' => 'Código QR',

    // Navigation & Overview
    'inventory_management' => 'Gestión de Inventario',
    'inventory_overview' => 'Resumen de Inventario',
    'dashboard' => 'Panel de Control',
    'overview' => 'Resumen',

    // Products Management
    'product_management' => 'Gestión de Productos',
    'product_catalog' => 'Catálogo de Productos',
    'product_list' => 'Lista de Productos',
    'product_details' => 'Detalles del Producto',
    'product_information' => 'Información del Producto',
    'add_product' => 'Agregar Producto',
    'new_product' => 'Nuevo Producto',
    'edit_product' => 'Editar Producto',
    'delete_product' => 'Eliminar Producto',
    'create_product' => 'Crear Producto',
    'product_created' => 'Producto creado exitosamente',
    'product_updated' => 'Producto actualizado exitosamente',
    'product_deleted' => 'Producto eliminado exitosamente',
    'no_products' => 'No hay productos registrados',
    'manage_inventory_description' => 'Gestiona el inventario de productos en almacenes y ubicaciones',
    'search_products_placeholder' => 'Buscar productos, SKU, lotes...',

    // Product Fields
    'product_name' => 'Nombre del Producto',
    'product_code' => 'Código del Producto',
    'product_description' => 'Descripción del Producto',
    'product_category' => 'Categoría del Producto',
    'product_brand' => 'Marca del Producto',
    'unit_price' => 'Precio Unitario',
    'cost_price' => 'Precio de Costo',
    'selling_price' => 'Precio de Venta',
    'unit_of_measure' => 'Unidad de Medida',
    'weight' => 'Peso',
    'dimensions' => 'Dimensiones',
    'color' => 'Color',
    'size' => 'Tamaño',
    'model' => 'Modelo',
    'manufacturer' => 'Fabricante',
    'supplier' => 'Proveedor',

    // Stock & Quantities
    'stock_level' => 'Nivel de Stock',
    'current_stock' => 'Existencia Actual',
    'available_stock' => 'Existencia Disponible',
    'reserved_stock' => 'Existencia Reservada',
    'in_stock' => 'En Existencia',
    'out_of_stock' => 'Agotado',
    'low_stock' => 'Stock Bajo',
    'minimum_stock' => 'Existencia Mínima',
    'maximum_stock' => 'Existencia Máxima',
    'reorder_point' => 'Punto de Reorden',
    'reorder_quantity' => 'Cantidad de Reorden',
    'quantity' => 'Cantidad',
    'quantity_on_hand' => 'Cantidad en Mano',
    'quantity_available' => 'Cantidad Disponible',
    'quantity_reserved' => 'Cantidad Reservada',
    'with_stock' => 'Con Stock',
    'with_reservations' => 'Con Reservas',
    'no_stock' => 'Sin Stock',
    'show_low_stock_only' => 'Mostrar solo stock bajo',
    'show_expiring_only' => 'Mostrar solo productos por vencer',

    // Movement & Transactions
    'movement' => 'Movimiento',
    'movements' => 'Movimientos',
    'movement_history' => 'Historial de Movimientos',
    'stock_movements' => 'Movimientos de Existencias',
    'inventory_movement' => 'Movimiento de Inventario',
    'transaction' => 'Transacción',
    'transactions' => 'Transacciones',
    'movement_type' => 'Tipo de Movimiento',
    'movement_reason' => 'Razón del Movimiento',
    'movement_date' => 'Fecha del Movimiento',
    'reference_number' => 'Número de Referencia',

    // Movement Types
    'inbound' => 'Entrada',
    'outbound' => 'Salida',
    'transfer' => 'Transferencia',
    'adjustment' => 'Ajuste',
    'purchase' => 'Compra',
    'sale' => 'Venta',
    'return' => 'Devolución',
    'loss' => 'Pérdida',
    'damage' => 'Daño',
    'expiry' => 'Vencimiento',

    // Transfers
    'transfer_management' => 'Gestión de Transferencias',
    'transfers' => 'Transferencias',
    'create_transfer' => 'Crear Transferencia',
    'transfer_request' => 'Solicitud de Transferencia',
    'transfer_status' => 'Estado de Transferencia',
    'source_location' => 'Ubicación de Origen',
    'destination_location' => 'Ubicación de Destino',
    'transfer_date' => 'Fecha de Transferencia',
    'transfer_quantity' => 'Cantidad a Transferir',
    'pending_transfer' => 'Transferencia Pendiente',
    'approved_transfer' => 'Transferencia Aprobada',
    'completed_transfer' => 'Transferencia Completada',
    'cancelled_transfer' => 'Transferencia Cancelada',

    // Scanner & Barcode
    'barcode_scanner' => 'Escáner de Códigos de Barras',
    'scan_barcode' => 'Escanear Código de Barras',
    'scan_product' => 'Escanear Producto',
    'scanner' => 'Escáner',
    'scan_result' => 'Resultado del Escaneo',
    'scan_again' => 'Escanear Nuevamente',
    'no_product_found' => 'No se encontró el producto',
    'invalid_barcode' => 'Código de barras inválido',

    // Alerts & Notifications
    'stock_alerts' => 'Alertas de Existencias',
    'alerts' => 'Alertas',
    'alert' => 'Alerta',
    'low_stock_alert' => 'Alerta de Existencia Baja',
    'out_of_stock_alert' => 'Alerta de Agotamiento',
    'expiry_alert' => 'Alerta de Vencimiento',
    'alert_type' => 'Tipo de Alerta',
    'alert_status' => 'Estado de Alerta',
    'alert_message' => 'Mensaje de Alerta',
    'active_alerts' => 'Alertas Activas',
    'resolved_alerts' => 'Alertas Resueltas',
    'critical_alert' => 'Alerta Crítica',
    'warning_alert' => 'Alerta de Advertencia',

    // Categories
    'category' => 'Categoría',
    'categories' => 'Categorías',
    'product_categories' => 'Categorías de Productos',
    'category_name' => 'Nombre de la Categoría',
    'parent_category' => 'Categoría Padre',
    'subcategory' => 'Subcategoría',
    'subcategories' => 'Subcategorías',
    'all_categories' => 'Todas las Categorías',

    // Locations & Storage
    'location' => 'Ubicación',
    'locations' => 'Ubicaciones',
    'storage_location' => 'Ubicación de Almacenamiento',
    'storage_locations' => 'Ubicaciones de Almacenamiento',
    'bin_location' => 'Ubicación de Casillero',
    'shelf' => 'Estante',
    'rack' => 'Estantería',
    'aisle' => 'Pasillo',
    'zone' => 'Zona',
    'section' => 'Sección',

    // Reports & Analytics
    'reports' => 'Reportes',
    'analytics' => 'Análisis',
    'inventory_report' => 'Reporte de Inventario',
    'stock_report' => 'Reporte de Existencias',
    'movement_report' => 'Reporte de Movimientos',
    'valuation_report' => 'Reporte de Valoración',
    'aging_report' => 'Reporte de Antigüedad',
    'turnover_report' => 'Reporte de Rotación',

    // Status & Conditions
    'active' => 'Activo',
    'inactive' => 'Inactivo',
    'available' => 'Disponible',
    'unavailable' => 'No Disponible',
    'reserved' => 'reservado',
    'damaged' => 'Dañado',
    'expired' => 'Vencido',
    'expiring_soon' => 'Por Vencer',
    'expires' => 'Vence',
    'obsolete' => 'Obsoleto',
    'discontinued' => 'Descontinuado',

    // Actions
    'add_stock' => 'Agregar Existencias',
    'remove_stock' => 'Remover Existencias',
    'adjust_stock' => 'Ajustar Existencias',
    'count_stock' => 'Contar Existencias',
    'reserve_stock' => 'Reservar Existencias',
    'release_stock' => 'Liberar Existencias',
    'update_stock' => 'Actualizar Existencias',

    // Units of Measure
    'unit' => 'Unidad',
    'units' => 'Unidades',
    'piece' => 'Pieza',
    'pieces' => 'Piezas',
    'kilogram' => 'Kilogramo',
    'gram' => 'Gramo',
    'pound' => 'Libra',
    'liter' => 'Litro',
    'milliliter' => 'Mililitro',
    'meter' => 'Metro',
    'centimeter' => 'Centímetro',
    'box' => 'Caja',
    'case' => 'Caso',
    'package' => 'Paquete',

    // Additional Fields & Terms
    'total_items' => 'Total Elementos',
    'total_value' => 'Valor Total',
    'cost' => 'Costo',
    'lot_expiration' => 'Lote / Vencimiento',
    'last_count' => 'Último Conteo',
    'no_lot' => 'Sin lote',
    'no_count' => 'Sin conteo',
    'perform_count' => 'Realizar Conteo',
    'no_inventory_items_found' => 'No se encontraron elementos de inventario',
    'try_changing_filters_or_add_products' => 'Prueba a cambiar los filtros o agregar productos al inventario',

    // Messages
    'stock_updated' => 'Existencias actualizadas',
    'movement_recorded' => 'Movimiento registrado',
    'transfer_completed' => 'Transferencia completada',
    'alert_resolved' => 'Alerta resuelta',
    'inventory_synchronized' => 'Inventario sincronizado',
    'no_movements' => 'No hay movimientos registrados',
    'no_alerts' => 'No hay alertas activas',
    'insufficient_stock' => 'Existencias insuficientes',
    'invalid_quantity' => 'Cantidad inválida',
    'operation_successful' => 'Operación exitosa',

];
