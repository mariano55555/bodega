# Proceso de Gestión de Almacenes

## Descripción General

La **Gestión de Almacenes** es el módulo central del sistema que permite administrar las instalaciones físicas donde se almacena el inventario. Este módulo proporciona una visión completa de la capacidad, utilización y actividad de cada almacén, facilitando la toma de decisiones operativas.

## Estructura Jerárquica

El sistema organiza el almacenamiento en una estructura jerárquica de tres niveles:

```
Empresa (Company)
└── Sucursal (Branch)
    └── Almacén/Bodega (Warehouse)
        └── Ubicaciones de Almacenamiento (Storage Locations)
```

### Tipos de Almacén

1. **General**: Almacén principal que puede contener múltiples tipos de productos
2. **Fraccionado**: Sub-almacén especializado dentro de un almacén general

## Panel de Control (Dashboard)

### Métricas Principales

El dashboard de almacenes muestra cuatro métricas clave:

| Métrica | Descripción | Cálculo |
|---------|-------------|---------|
| **Empresas** | Total de empresas activas | Conteo de empresas con estado activo |
| **Sucursales** | Sucursales de la empresa actual | Conteo de branches activas |
| **Almacenes** | Almacenes activos | Conteo de warehouses activos |
| **Utilización** | Porcentaje de capacidad usada | (Capacidad Usada / Capacidad Total) × 100 |

### Cálculo de Capacidad

La capacidad se mide de la siguiente manera:

- **Capacidad Total**: Suma del campo `total_capacity` de todos los almacenes activos
- **Capacidad Usada**: Suma de la capacidad de las ubicaciones de almacenamiento activas
- **Unidad de Medida**: Configurable por almacén (m³, pies³, pallets, etc.)

### Indicadores de Utilización

| Color | Rango | Significado |
|-------|-------|-------------|
| Verde | 0-74% | Capacidad disponible adecuada |
| Amarillo | 75-89% | Capacidad limitada, considerar reorganización |
| Rojo | 90-100% | Capacidad crítica, acción requerida |

## Información de Sucursales

El panel muestra un resumen de cada sucursal incluyendo:

- Nombre y código de la sucursal
- Ubicación (ciudad, departamento)
- Indicador de sucursal principal (estrella)
- Cantidad de almacenes asociados
- Nombre del gerente asignado

## Actividades Recientes

El sistema registra y muestra los últimos 5 movimientos de inventario con:

### Tipos de Movimiento

| Tipo | Descripción | Icono | Color |
|------|-------------|-------|-------|
| `in` | Entrada de inventario | arrow-down-circle | Verde |
| `out` | Salida de inventario | arrow-up-circle | Rojo |
| `transfer` | Transferencia entre almacenes | arrows-right-left | Azul |
| `transfer_in` | Transferencia recibida | arrow-down-circle | Azul |
| `transfer_out` | Transferencia enviada | arrow-up-circle | Azul |
| `adjustment` | Ajuste de inventario | adjustments-horizontal | Amarillo |
| `receipt` | Recepción de mercancía | clipboard-document-check | Verde |
| `purchase` | Compra | shopping-cart | Verde |
| `sale` | Venta | currency-dollar | Rojo |
| `shipment` | Envío | truck | Rojo |
| `return_customer` | Devolución de cliente | arrow-uturn-left | Púrpura |
| `return_supplier` | Devolución a proveedor | arrow-uturn-right | Naranja |
| `expiry` | Vencimiento | clock | Rojo |
| `damage` | Daño | exclamation-triangle | Rojo |
| `loss` | Pérdida | minus-circle | Rojo |
| `donation_in` | Donación recibida | gift | Verde |
| `donation_out` | Donación entregada | gift | Púrpura |

## Capacidad por Almacén

La tabla de capacidad muestra para cada almacén:

- **Almacén**: Nombre y código
- **Sucursal**: Sucursal a la que pertenece
- **Ubicación**: Ciudad del almacén
- **Capacidad Total**: Capacidad máxima configurada
- **Capacidad Usada**: Suma de capacidades de ubicaciones activas
- **Utilización**: Barra de progreso con porcentaje

## Escenarios de Uso - ENA

### Escenario 1: Monitoreo Diario

El encargado de bodega revisa el dashboard cada mañana para:
1. Verificar la utilización de capacidad
2. Identificar almacenes con alta ocupación
3. Revisar las actividades del día anterior
4. Planificar redistribución si es necesario

### Escenario 2: Planificación de Compras

Antes de realizar una compra grande:
1. Consultar la capacidad disponible por almacén
2. Identificar almacenes con espacio suficiente
3. Coordinar la recepción con el almacén destino

### Escenario 3: Auditoría de Movimientos

Para control interno:
1. Revisar las actividades recientes
2. Verificar que los movimientos correspondan a operaciones autorizadas
3. Identificar patrones inusuales (muchos ajustes, pérdidas frecuentes)

## Campos del Almacén

### Información Básica

| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| `name` | VARCHAR | Sí | Nombre descriptivo del almacén |
| `slug` | VARCHAR | Auto | URL amigable generada automáticamente |
| `code` | VARCHAR | Sí | Código único de identificación |
| `warehouse_type` | ENUM | Sí | 'general' o 'fractional' |
| `description` | TEXT | No | Descripción detallada |

### Ubicación

| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| `address` | VARCHAR | No | Dirección completa |
| `city` | VARCHAR | No | Ciudad |
| `state` | VARCHAR | No | Departamento/Estado |
| `country` | VARCHAR | No | País (default: El Salvador) |
| `postal_code` | VARCHAR | No | Código postal |
| `latitude` | DECIMAL | No | Coordenada para mapas |
| `longitude` | DECIMAL | No | Coordenada para mapas |

### Capacidad

| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| `total_capacity` | DECIMAL | No | Capacidad máxima |
| `capacity_unit` | VARCHAR | No | Unidad de medida (m³, pallets, etc.) |

### Administración

| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| `manager_id` | FK | No | Usuario responsable del almacén |
| `operating_hours` | JSON | No | Horarios de operación |
| `settings` | JSON | No | Configuraciones adicionales |
| `is_active` | BOOLEAN | Sí | Estado activo/inactivo |

## Relaciones

```
Warehouse
├── belongsTo Company (empresa)
├── belongsTo Branch (sucursal)
├── belongsTo User (manager - gerente)
├── hasMany StorageLocation (ubicaciones)
├── hasMany Inventory (registros de inventario)
├── hasMany InventoryMovement (movimientos)
├── hasMany InventoryAlert (alertas)
├── hasMany InventoryTransfer (transferencias origen/destino)
└── hasMany Warehouse (sub-almacenes para tipo fractional)
```

## Acciones Rápidas del Dashboard

El dashboard proporciona acceso directo a:

1. **Agregar Empresa**: Crear nueva empresa en el sistema
2. **Agregar Sucursal**: Crear nueva sucursal
3. **Agregar Almacén**: Crear nuevo almacén
4. **Gestión de Capacidad**: Ver análisis detallado de capacidad

## Cambio de Empresa

Para usuarios con permisos de super administrador:
- Selector de empresa en la parte superior
- Cambio dinámico de contexto
- Todos los datos se actualizan según la empresa seleccionada

## Mejores Prácticas

1. **Configurar Capacidad**: Siempre establecer `total_capacity` para un monitoreo preciso
2. **Usar Ubicaciones**: Crear ubicaciones de almacenamiento para tracking granular
3. **Asignar Gerentes**: Definir responsables para cada almacén
4. **Revisar Utilización**: Monitorear regularmente para evitar saturación
5. **Documentar Movimientos**: Registrar notas en cada operación

## Integración con Otros Módulos

- **Compras**: Los productos comprados se reciben en almacenes específicos
- **Donaciones**: Los bienes donados se registran en el almacén designado
- **Transferencias**: Movimiento de productos entre almacenes
- **Despachos**: Salidas programadas desde almacenes
- **Ajustes**: Correcciones de inventario por almacén
- **Cierres**: Consolidación mensual por almacén
