# Catálogo de Productos

Este documento describe la gestión del catálogo de productos en el sistema de bodega, incluyendo categorías, unidades de medida, lotes y control de inventario.

## ¿Qué es el Catálogo de Productos?

El catálogo de productos es el registro maestro de todos los artículos que maneja la organización. Cada producto contiene información esencial como nombre, código SKU, categoría, unidad de medida, costos, precios y configuraciones de inventario.

## Estructura del Catálogo

### 1. Categorías de Productos

Las categorías permiten organizar los productos en grupos lógicos para facilitar su gestión y búsqueda.

| Campo | Descripción | Ejemplo |
|-------|-------------|---------|
| **Nombre** | Nombre de la categoría | "Fertilizantes" |
| **Código** | Código corto identificador | "FERT" |
| **Descripción** | Descripción detallada | "Productos para fertilización de cultivos" |
| **Estado** | Activa o Inactiva | Activa |

### 2. Unidades de Medida

Las unidades de medida definen cómo se cuantifican los productos.

| Campo | Descripción | Ejemplo |
|-------|-------------|---------|
| **Nombre** | Nombre completo | "Quintal" |
| **Símbolo** | Abreviatura | "qq" |
| **Tipo** | Tipo de medida | "Peso", "Volumen", "Unidad" |
| **Ratio Base** | Factor de conversión | 1 qq = 100 lb |

**Tipos de Unidades Comunes:**
- **Peso**: Kilogramo (kg), Libra (lb), Quintal (qq), Tonelada (ton)
- **Volumen**: Litro (lt), Galón (gal), Mililitro (ml)
- **Unidad**: Unidad (und), Docena (doc), Caja (cja), Saco (sco)
- **Longitud**: Metro (m), Pie (ft), Pulgada (in)

### 3. Producto

El producto es la entidad central del catálogo:

| Campo | Descripción | Requerido | Ejemplo |
|-------|-------------|-----------|---------|
| **Nombre** | Nombre del producto | Sí | "Fertilizante NPK 15-15-15" |
| **SKU** | Código único de identificación | No | "FERT-NPK-1515" |
| **Código de Barras** | Código para escaneo | No | "7501234567890" |
| **Categoría** | Categoría asignada | No | "Fertilizantes" |
| **Unidad de Medida** | Unidad principal | Sí | "Quintal" |
| **Descripción** | Detalles del producto | No | "Fertilizante granulado..." |
| **Costo** | Costo de adquisición | No | $45.00 |
| **Precio** | Precio de venta/despacho | No | $55.00 |
| **Stock Mínimo** | Cantidad mínima de alerta | No | 10 qq |
| **Stock Máximo** | Cantidad máxima permitida | No | 500 qq |
| **Controlar Inventario** | Si se rastrea el inventario | Sí | Sí |
| **Método de Valuación** | FIFO, LIFO, Promedio | No | "FIFO" |
| **Imagen** | Foto del producto | No | (archivo) |
| **Atributos** | Características adicionales | No | {"color": "gris"} |

### 4. Lotes de Producto

Los lotes permiten rastrear productos por fecha de producción y vencimiento:

| Campo | Descripción | Ejemplo |
|-------|-------------|---------|
| **Número de Lote** | Identificador único del lote | "LOT-2024-1105" |
| **Producto** | Producto asociado | "Semilla de Maíz H-59" |
| **Proveedor** | Proveedor de origen | "Agroinsumos S.A." |
| **Fecha de Manufactura** | Cuándo se fabricó | 01/10/2024 |
| **Fecha de Vencimiento** | Cuándo expira | 01/10/2025 |
| **Cantidad Producida** | Cantidad inicial del lote | 100 qq |
| **Cantidad Restante** | Cantidad disponible | 75 qq |
| **Costo Unitario** | Costo por unidad del lote | $85.00 |
| **Estado** | active, expired, depleted | "active" |

## Diagrama de Relaciones

```
┌─────────────────────┐
│    CATEGORÍA        │
│  (ProductCategory)  │
└──────────┬──────────┘
           │ 1:N
           ▼
┌─────────────────────┐       ┌─────────────────────┐
│     PRODUCTO        │◄──────│  UNIDAD DE MEDIDA   │
│     (Product)       │  N:1  │  (UnitOfMeasure)    │
└──────────┬──────────┘       └─────────────────────┘
           │
           │ 1:N
           ▼
┌─────────────────────┐       ┌─────────────────────┐
│       LOTE          │       │     INVENTARIO      │
│   (ProductLot)      │       │    (Inventory)      │
└─────────────────────┘       └─────────────────────┘
           │                           │
           │ 1:N                       │ 1:N
           ▼                           ▼
┌─────────────────────────────────────────────────────┐
│              MOVIMIENTO DE INVENTARIO               │
│               (InventoryMovement)                   │
└─────────────────────────────────────────────────────┘
```

## Flujo de Trabajo

### Crear una Nueva Categoría

```
┌─────────────────┐
│ Ir a Catálogos  │
│  > Categorías   │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ Clic en "Nueva  │
│   Categoría"    │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ Completar datos │
│ Nombre, Código, │
│   Descripción   │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│    Guardar      │
└─────────────────┘
```

### Crear un Nuevo Producto

```
┌─────────────────┐
│ Ir a Catálogos  │
│   > Productos   │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ Clic en "Nuevo  │
│    Producto"    │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ Datos Básicos:  │
│ - Nombre        │
│ - SKU           │
│ - Categoría     │
│ - Unidad Medida │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ Costos y Stock: │
│ - Costo         │
│ - Precio        │
│ - Stock Mínimo  │
│ - Stock Máximo  │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ Configuración:  │
│ - Método Valuac.│
│ - Controlar Inv.│
│ - Imagen        │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│    Guardar      │
└─────────────────┘
```

## Métodos de Valuación

El sistema soporta diferentes métodos para calcular el costo del inventario:

### FIFO (First In, First Out)
- **Descripción**: Los primeros productos en entrar son los primeros en salir
- **Uso**: Ideal para productos con fecha de vencimiento
- **Ejemplo**: Se despacha primero el lote más antiguo

### FEFO (First Expired, First Out)
- **Descripción**: Los productos próximos a vencer salen primero
- **Uso**: Productos perecederos con diferentes fechas de vencimiento
- **Ejemplo**: Se despacha primero el lote que vence antes

### Promedio Ponderado
- **Descripción**: Se calcula un costo promedio de todas las entradas
- **Uso**: Productos con precios fluctuantes
- **Ejemplo**: (50 qq × $45 + 30 qq × $48) / 80 qq = $46.125

## Control de Stock

### Alertas de Inventario

El sistema genera alertas automáticas cuando:

| Condición | Tipo de Alerta | Acción Recomendada |
|-----------|----------------|-------------------|
| Stock < Mínimo | **Stock Bajo** | Generar orden de compra |
| Stock > Máximo | **Sobrestock** | Revisar almacenamiento |
| Lote por vencer | **Próximo a Vencer** | Priorizar despacho |
| Lote vencido | **Producto Vencido** | Ajuste por vencimiento |

### Trazabilidad

Cada producto mantiene trazabilidad completa:
- Quién lo creó
- Quién lo modificó por última vez
- Historial de movimientos
- Lotes asociados
- Proveedores de origen

---

## Caso Práctico: Configuración del Catálogo en la ENA

### Escenario

La Escuela Nacional de Agricultura (ENA) está implementando el sistema de bodega y necesita configurar su catálogo de productos para el área de Insumos Agrícolas.

### Actores Involucrados
- **Ana Rodríguez** - Administradora del Sistema (Super Admin)
- **María García** - Encargada de Bodega Central (Gerente de Bodega)

### Paso 1: Configuración de Categorías (Ana Rodríguez)

Ana accede al sistema y crea la estructura de categorías:

```
Catálogo de Insumos Agrícolas ENA
├── FERT - Fertilizantes
│   ├── Descripción: Productos para nutrición de plantas
│   └── Estado: Activa
├── SEM - Semillas
│   ├── Descripción: Semillas certificadas para siembra
│   └── Estado: Activa
├── PEST - Pesticidas
│   ├── Descripción: Productos para control de plagas
│   └── Estado: Activa
├── HERB - Herbicidas
│   ├── Descripción: Productos para control de malezas
│   └── Estado: Activa
└── EQUI - Equipos y Herramientas
    ├── Descripción: Herramientas agrícolas
    └── Estado: Activa
```

### Paso 2: Configuración de Unidades de Medida (Ana Rodríguez)

Ana configura las unidades que usará la institución:

```
Unidades de Medida Configuradas:
┌────────────┬────────┬────────────┬─────────────────┐
│ Nombre     │ Símbolo│ Tipo       │ Ratio Base      │
├────────────┼────────┼────────────┼─────────────────┤
│ Quintal    │ qq     │ Peso       │ 1 (base)        │
│ Libra      │ lb     │ Peso       │ 0.01 qq         │
│ Kilogramo  │ kg     │ Peso       │ 0.022 qq        │
│ Litro      │ lt     │ Volumen    │ 1 (base)        │
│ Galón      │ gal    │ Volumen    │ 3.785 lt        │
│ Unidad     │ und    │ Unidad     │ 1 (base)        │
│ Saco       │ sco    │ Unidad     │ 1 und           │
│ Caja       │ cja    │ Unidad     │ 1 und           │
└────────────┴────────┴────────────┴─────────────────┘
```

### Paso 3: Registro de Productos (María García)

María registra los productos principales del inventario:

#### Producto 1: Fertilizante NPK 15-15-15

```
DATOS DEL PRODUCTO
──────────────────────────────────────────────
Nombre:           Fertilizante NPK 15-15-15
SKU:              FERT-NPK-1515
Código de Barras: 7501234567001
Categoría:        Fertilizantes (FERT)
Unidad de Medida: Quintal (qq)
Descripción:      Fertilizante granulado triple 15 para
                  uso agrícola general. Contiene 15%
                  Nitrógeno, 15% Fósforo, 15% Potasio.

COSTOS Y PRECIOS
──────────────────────────────────────────────
Costo Unitario:   $45.00
Precio de Ref:    $55.00

CONTROL DE INVENTARIO
──────────────────────────────────────────────
Controlar Stock:  ✓ Sí
Método Valuación: FIFO
Stock Mínimo:     20 qq
Stock Máximo:     200 qq

ATRIBUTOS ADICIONALES
──────────────────────────────────────────────
- Presentación: Saco 1 qq
- Color: Gris/Blanco
- Fabricante: Fertica
```

#### Producto 2: Semilla de Maíz H-59

```
DATOS DEL PRODUCTO
──────────────────────────────────────────────
Nombre:           Semilla de Maíz H-59
SKU:              SEM-MAIZ-H59
Código de Barras: 7501234567002
Categoría:        Semillas (SEM)
Unidad de Medida: Quintal (qq)
Descripción:      Semilla de maíz híbrido H-59,
                  certificada para zona tropical.
                  Alto rendimiento, tolerante a sequía.

COSTOS Y PRECIOS
──────────────────────────────────────────────
Costo Unitario:   $85.00
Precio de Ref:    $100.00

CONTROL DE INVENTARIO
──────────────────────────────────────────────
Controlar Stock:  ✓ Sí
Método Valuación: FEFO (por fecha de vencimiento)
Stock Mínimo:     10 qq
Stock Máximo:     100 qq

ATRIBUTOS ADICIONALES
──────────────────────────────────────────────
- Presentación: Saco 1 qq
- Certificación: CENTA
- Ciclo: 120 días
```

#### Producto 3: Insecticida Cipermetrina 25%

```
DATOS DEL PRODUCTO
──────────────────────────────────────────────
Nombre:           Insecticida Cipermetrina 25%
SKU:              PEST-CIPER-25
Código de Barras: 7501234567003
Categoría:        Pesticidas (PEST)
Unidad de Medida: Litro (lt)
Descripción:      Insecticida piretroide de amplio
                  espectro para control de plagas
                  en cultivos agrícolas.

COSTOS Y PRECIOS
──────────────────────────────────────────────
Costo Unitario:   $35.00
Precio de Ref:    $45.00

CONTROL DE INVENTARIO
──────────────────────────────────────────────
Controlar Stock:  ✓ Sí
Método Valuación: FEFO
Stock Mínimo:     5 lt
Stock Máximo:     50 lt

ATRIBUTOS ADICIONALES
──────────────────────────────────────────────
- Presentación: Envase 1 lt
- Toxicidad: Moderada (banda amarilla)
- Registro MAG: 2345-A
```

### Paso 4: Recepción y Creación de Lotes

Cuando llega una compra de fertilizante, el sistema crea automáticamente un lote:

```
LOTE CREADO AL RECIBIR COMPRA
──────────────────────────────────────────────
Número de Lote:      LOT-2024-1122-001
Producto:            Fertilizante NPK 15-15-15
Proveedor:           Agroinsumos S.A. de C.V.
Fecha Manufactura:   15/09/2024
Fecha Vencimiento:   15/09/2026
Cantidad Recibida:   50 qq
Costo Unitario:      $45.00
Estado:              Activo

Referencia Compra:   PUR-20241122-ABC123
```

### Paso 5: Verificación de Alertas

Después de varias operaciones, el sistema muestra:

```
ALERTAS DE INVENTARIO - BODEGA CENTRAL ENA
──────────────────────────────────────────────

⚠️  STOCK BAJO
   Producto: Insecticida Cipermetrina 25%
   Stock Actual: 3 lt
   Stock Mínimo: 5 lt
   Acción: Generar orden de compra

⚠️  PRÓXIMO A VENCER (30 días)
   Producto: Semilla de Maíz H-59
   Lote: LOT-2024-0315-002
   Fecha Vencimiento: 20/12/2024
   Cantidad: 8 qq
   Acción: Priorizar despacho

✓  Stock Normal: 45 productos
```

### Resultado Final

María ahora puede:

1. **Buscar productos** por nombre, SKU, código de barras o categoría
2. **Ver stock** de cada producto en cada bodega
3. **Recibir alertas** automáticas de stock bajo o productos por vencer
4. **Rastrear lotes** con su trazabilidad completa
5. **Generar reportes** de inventario por categoría

---

## Caso Alternativo: Producto sin Control de Inventario

Algunos productos pueden no requerir control de inventario:

```
DATOS DEL PRODUCTO
──────────────────────────────────────────────
Nombre:           Manual de Agricultura Orgánica
SKU:              DOC-MANUAL-001
Categoría:        Documentación
Unidad de Medida: Unidad (und)

CONTROL DE INVENTARIO
──────────────────────────────────────────────
Controlar Stock:  ✗ No
```

Este producto existe en el catálogo pero no genera movimientos de inventario ni alertas de stock.

---

## Buenas Prácticas

### Al Crear Categorías
- Usar códigos cortos y significativos (máx. 4-5 caracteres)
- Evitar categorías demasiado genéricas o demasiado específicas
- Revisar si ya existe una categoría similar antes de crear una nueva

### Al Crear Productos
- Usar nombres descriptivos pero concisos
- Asignar SKU únicos y consistentes con un patrón
- Siempre indicar la unidad de medida correcta
- Configurar stock mínimo basado en consumo histórico
- Elegir el método de valuación según el tipo de producto

### Al Gestionar Lotes
- Registrar siempre la fecha de vencimiento cuando aplique
- Usar FEFO para productos perecederos
- Revisar regularmente productos próximos a vencer

---

## Preguntas Frecuentes

### ¿Puedo cambiar la categoría de un producto después de crearlo?
Sí, la categoría puede modificarse en cualquier momento sin afectar el inventario.

### ¿Qué pasa si desactivo un producto?
El producto ya no aparece en las listas de selección para nuevas operaciones, pero el historial y stock existente se mantiene.

### ¿Puedo eliminar un producto?
Solo si no tiene movimientos de inventario asociados. Los productos con historial deben desactivarse, no eliminarse.

### ¿Cómo manejo productos con múltiples presentaciones?
Cree productos separados para cada presentación (ej: "Fertilizante NPK 1qq", "Fertilizante NPK 25lb") o use atributos para distinguirlos.

### ¿Qué método de valuación debo usar?
- **FIFO**: Productos no perecederos con precios estables
- **FEFO**: Productos con fecha de vencimiento
- **Promedio**: Productos con precios muy variables

---

*Última actualización: Noviembre 2024*
