# Guía de Tipos de Transacciones - ENA

## Resumen

Este documento describe qué código de transacción usar para cada operación en el sistema de inventario de la Escuela Nacional de Agricultura (ENA).

---

## Estructura de Campos

| Campo | Descripción |
|-------|-------------|
| `code` | Código del sistema nuevo (PURCH_LOCAL, SALE_CREDIT, etc.) |
| `legacy_code` | Código del sistema anterior (E0, S1, etc.) |
| `legacy_name` | Nombre original del sistema anterior (COMPRAS LOCALES, etc.) |
| `affects_cost` | Si afecta el costo promedio en el Kardex |

---

## Movimientos Automáticos vs Manuales

### Resumen de Códigos

| Total en tabla | Automáticos | Manuales/Disponibles |
|----------------|-------------|----------------------|
| 41 códigos | **6** (15%) | **35** (85%) |

Los 35 códigos "manuales" están disponibles porque:
1. Algunos se usan en **Ajustes de Inventario** (el usuario los selecciona)
2. Otros están disponibles para **futuras funcionalidades** (ventas, devoluciones, requisiciones, etc.)
3. Algunos son para **compatibilidad con el sistema legacy de ENA** (para migración de datos históricos)

---

## MOVIMIENTOS AUTOMÁTICOS (El sistema los selecciona)

Estos son los **ÚNICOS 6 códigos** que el sistema usa automáticamente cuando procesas operaciones:

| Módulo | Código Sistema | Legacy | Nombre ENA | Cuándo se usa |
|--------|---------------|--------|------------|---------------|
| **COMPRAS** | `PURCH_LOCAL` | E0 | COMPRAS LOCALES | Al recibir una compra |
| **DONACIONES** | `DONATION_IN` | EN | ENTRADA POR DONACION | Al recibir una donación |
| **TRASLADOS** | `TRANSFER_OUT` | ST | SALIDA POR TRASLADO / BODEGA | Al enviar (ship) |
| **TRASLADOS** | `TRANSFER_IN` | ET | ENTRADA POR TRASLADO/BODEGA | Al recibir (receive) |
| **DESPACHOS** | `DISPATCH` | SB | DESPACHO DE BODEGA | Despacho interno |
| **DESPACHOS** | `SALE_CREDIT` / `SALE_FINAL` | S0/S1 | VENTAS | Despacho por venta |

---

## MOVIMIENTOS MANUALES (El usuario selecciona)

### En AJUSTES DE INVENTARIO:

| Código | Legacy | Nombre ENA | Uso típico |
|--------|--------|------------|------------|
| `ADJ_POS` | EJ | ENTRADAS POR AJUSTE DE INV. | Sobrante encontrado |
| `ADJ_NEG` | SJ | SALIDA POR AJUSTE DE INV. | Faltante, robo, pérdida |
| `MEASURE_IN` | EM | AJUSTE POR MEDICIONES | Diferencia física (entrada) |
| `MEASURE_OUT` | SN | AJUSTE POR MEDICIONES | Diferencia física (salida) |
| `DISCARD` | S4 | SALIDA POR DESCARTE | Productos dañados |
| `OBSOL_OUT` | S5 | SALIDA POR OBSOLESCENCIA | Productos vencidos |

---

## Diagrama: Flujo Automático vs Manual

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                        MOVIMIENTOS AUTOMÁTICOS                               │
│                    (El sistema selecciona el código)                         │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                              │
│  COMPRAS                    DONACIONES                 TRASLADOS            │
│  ────────                   ──────────                 ─────────            │
│  Purchase::receive()        Donation::receive()        Transfer::ship()     │
│       │                          │                          │               │
│       ▼                          ▼                          ▼               │
│  ┌─────────────┐           ┌─────────────┐           ┌─────────────┐        │
│  │ PURCH_LOCAL │           │ DONATION_IN │           │ TRANSFER_OUT│        │
│  │ (E0)        │           │ (EN)        │           │ (ST)        │        │
│  └─────────────┘           └─────────────┘           └─────────────┘        │
│                                                             │               │
│                                                      Transfer::receive()    │
│                                                             │               │
│                                                             ▼               │
│                                                      ┌─────────────┐        │
│                                                      │ TRANSFER_IN │        │
│                                                      │ (ET)        │        │
│                                                      └─────────────┘        │
│                                                                              │
│  DESPACHOS                                                                   │
│  ─────────                                                                   │
│  Dispatch::process()                                                         │
│       │                                                                      │
│       ├── tipo='interno' ──▶ DISPATCH (SB)                                  │
│       ├── tipo='venta'   ──▶ SALE_CREDIT (S0) o SALE_FINAL (S1)            │
│       └── tipo='donacion'──▶ DISPATCH (SB)                                  │
│                                                                              │
└─────────────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────────────┐
│                         MOVIMIENTOS MANUALES                                 │
│                    (El usuario selecciona el código)                         │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                              │
│  AJUSTES DE INVENTARIO                                                       │
│  ─────────────────────                                                       │
│  El usuario ve un dropdown con opciones:                                     │
│                                                                              │
│  ┌─ ENTRADAS (cuando hay sobrante) ─────────────────────────────────────┐   │
│  │  • ADJ_POS (EJ) - Ajuste positivo                                     │   │
│  │  • MEASURE_IN (EM) - Ajuste por medición                              │   │
│  └───────────────────────────────────────────────────────────────────────┘   │
│                                                                              │
│  ┌─ SALIDAS (cuando hay faltante) ──────────────────────────────────────┐   │
│  │  • ADJ_NEG (SJ) - Ajuste negativo (robo, pérdida, mal conteo)        │   │
│  │  • MEASURE_OUT (SN) - Ajuste por medición                             │   │
│  │  • DISCARD (S4) - Descarte de productos dañados                       │   │
│  │  • OBSOL_OUT (S5) - Obsolescencia/vencimiento                         │   │
│  └───────────────────────────────────────────────────────────────────────┘   │
│                                                                              │
└─────────────────────────────────────────────────────────────────────────────┘
```

---

## Ejemplos Prácticos con Códigos Legacy

### EJEMPLO 1: AJUSTES DE INVENTARIO (Manuales)

#### Escenario: Conteo físico en Bodega Central

Durante un inventario físico encontraron diferencias en dos productos:

#### Caso A: Sobrante de Fertilizante (encontraron MÁS de lo esperado)
- Sistema decía: 100 unidades
- Conteo físico: 108 unidades
- **Diferencia: +8 unidades**

| Campo | Valor |
|-------|-------|
| **Código Nuevo** | `ADJ_POS` |
| **Código Legacy** | `EJ` |
| **Nombre Legacy** | ENTRADAS POR AJUSTE DE INV. |
| **Tipo Movimiento** | ENTRADA (in) |
| **Valorizado** | S (SÍ afecta costo) |

**Kardex Bodega Central - Fertilizante NPK:**

| Fecha | Código | Tipo Legacy | Descripción | Entrada | Salida | Saldo |
|-------|--------|-------------|-------------|---------|--------|-------|
| 27/11 | - | - | Saldo anterior | - | - | 100 |
| 28/11 | **EJ** | ENTRADAS POR AJUSTE DE INV. | Ajuste por sobrante en conteo físico | **8** | - | **108** |

---

#### Caso B: Faltante de Semillas (encontraron MENOS - posible robo)
- Sistema decía: 50 bolsas
- Conteo físico: 45 bolsas
- **Diferencia: -5 bolsas**

| Campo | Valor |
|-------|-------|
| **Código Nuevo** | `ADJ_NEG` |
| **Código Legacy** | `SJ` |
| **Nombre Legacy** | SALIDA POR AJUSTE DE INV. |
| **Tipo Movimiento** | SALIDA (out) |
| **Valorizado** | N (NO afecta costo) |

**Kardex Bodega Central - Semillas de Maíz:**

| Fecha | Código | Tipo Legacy | Descripción | Entrada | Salida | Saldo |
|-------|--------|-------------|-------------|---------|--------|-------|
| 27/11 | - | - | Saldo anterior | - | - | 50 |
| 28/11 | **SJ** | SALIDA POR AJUSTE DE INV. | Faltante detectado - posible robo | - | **5** | **45** |

---

### EJEMPLO 2: DESPACHOS (Salidas por consumo interno)

#### Escenario: Departamento de Mantenimiento solicita materiales

El Departamento de Mantenimiento necesita 20 galones de pintura y 5 brochas.

#### Despacho con Requisición Interna:

| Campo | Valor |
|-------|-------|
| **Código Nuevo** | `REQUISITION` |
| **Código Legacy** | `SR` |
| **Nombre Legacy** | REQUISICIONES |
| **Tipo Movimiento** | SALIDA (out) |
| **Valorizado** | N (NO afecta costo) |

**Kardex Bodega Central - Pintura Blanca:**

| Fecha | Código | Tipo Legacy | Descripción | Entrada | Salida | Saldo |
|-------|--------|-------------|-------------|---------|--------|-------|
| 25/11 | - | - | Saldo anterior | - | - | 100 |
| 28/11 | **SR** | REQUISICIONES | Req. #45 - Depto. Mantenimiento | - | **20** | **80** |

**Kardex Bodega Central - Brochas 4":**

| Fecha | Código | Tipo Legacy | Descripción | Entrada | Salida | Saldo |
|-------|--------|-------------|-------------|---------|--------|-------|
| 25/11 | - | - | Saldo anterior | - | - | 30 |
| 28/11 | **SR** | REQUISICIONES | Req. #45 - Depto. Mantenimiento | - | **5** | **25** |

---

#### Despacho para Mantenimiento de Maquinaria:

| Campo | Valor |
|-------|-------|
| **Código Nuevo** | `REQ_MAINT` |
| **Código Legacy** | `SM` |
| **Nombre Legacy** | REQUISICIONES / MANTENIMIENTO |
| **Tipo Movimiento** | SALIDA (out) |
| **Valorizado** | S (SÍ afecta costo) |

**Kardex Bodega Central - Aceite Motor 15W40:**

| Fecha | Código | Tipo Legacy | Descripción | Entrada | Salida | Saldo |
|-------|--------|-------------|-------------|---------|--------|-------|
| 25/11 | - | - | Saldo anterior | - | - | 50 |
| 28/11 | **SM** | REQUISICIONES / MANTENIMIENTO | Mant. Tractor John Deere #3 | - | **4** | **46** |

---

### EJEMPLO 3: TRASLADOS ENTRE BODEGAS (Automáticos)

#### Escenario: Trasladar 25 sacos de abono de Bodega Central a Bodega Norte

**Traslado: TRF-20241128-ABC123**
- Origen: Bodega Central
- Destino: Bodega Norte
- Producto: Abono Orgánico
- Cantidad: 25 sacos

---

#### PASO 1: Al ENVIAR (ship) - Se crea movimiento en ORIGEN

| Campo | Valor |
|-------|-------|
| **Código Nuevo** | `TRANSFER_OUT` |
| **Código Legacy** | `ST` |
| **Nombre Legacy** | SALIDA POR TRASLADO / BODEGA |
| **Tipo Movimiento** | SALIDA (transfer_out) |
| **Valorizado** | N (NO afecta costo) |

**Kardex BODEGA CENTRAL - Abono Orgánico:**

| Fecha | Código | Tipo Legacy | Descripción | Entrada | Salida | Saldo |
|-------|--------|-------------|-------------|---------|--------|-------|
| 26/11 | - | - | Saldo anterior | - | - | 200 |
| 28/11 | **ST** | SALIDA POR TRASLADO / BODEGA | Envío traslado TRF-20241128-ABC123 → Bodega Norte | - | **25** | **175** |

---

#### PASO 2: Al RECIBIR (receive) - Se crea movimiento en DESTINO

| Campo | Valor |
|-------|-------|
| **Código Nuevo** | `TRANSFER_IN` |
| **Código Legacy** | `ET` |
| **Nombre Legacy** | ENTRADA POR TRASLADO/BODEGA |
| **Tipo Movimiento** | ENTRADA (transfer_in) |
| **Valorizado** | N (NO afecta costo) |

**Kardex BODEGA NORTE - Abono Orgánico:**

| Fecha | Código | Tipo Legacy | Descripción | Entrada | Salida | Saldo |
|-------|--------|-------------|-------------|---------|--------|-------|
| 26/11 | - | - | Saldo anterior | - | - | 50 |
| 28/11 | **ET** | ENTRADA POR TRASLADO/BODEGA | Recepción traslado TRF-20241128-ABC123 ← Bodega Central | **25** | - | **75** |

---

#### Visualización del Flujo Completo de Traslado

```
BODEGA CENTRAL                              BODEGA NORTE
═══════════════                              ═══════════════

Saldo: 200 sacos                             Saldo: 50 sacos
      │                                            │
      │  [Usuario crea traslado]                   │
      │  [Supervisor aprueba]                      │
      │                                            │
      ▼                                            │
┌─────────────────────┐                            │
│ ENVIAR (ship)       │                            │
│                     │                            │
│ Código: ST          │                            │
│ SALIDA POR TRASLADO │                            │
│ -25 sacos           │                            │
│                     │                            │
│ Nuevo Saldo: 175    │                            │
└─────────────────────┘                            │
      │                                            │
      │  ═══════ EN TRÁNSITO ═══════              │
      │          25 sacos                          │
      │                                            │
      └────────────────────────────────────────────┤
                                                   ▼
                                    ┌─────────────────────┐
                                    │ RECIBIR (receive)   │
                                    │                     │
                                    │ Código: ET          │
                                    │ ENTRADA POR TRASLADO│
                                    │ +25 sacos           │
                                    │                     │
                                    │ Nuevo Saldo: 75     │
                                    └─────────────────────┘
```

---

### ¿Por qué los Traslados NO afectan el costo promedio?

Los traslados tienen `affects_cost = false` (columna "VALORIZADO" = N en el PDF) porque:

1. **El producto ya tiene un costo establecido** - simplemente se mueve de un lugar a otro
2. **No hay compra nueva** - no entra dinero nuevo que modifique el promedio
3. **Es la misma empresa** - el inventario total de la empresa no cambia, solo su ubicación

---

## Resumen de Códigos Legacy por Operación

| Operación | Código Legacy | Nombre ENA | Código Sistema | Auto/Manual |
|-----------|---------------|------------|----------------|-------------|
| Ajuste positivo | **EJ** | ENTRADAS POR AJUSTE DE INV. | ADJ_POS | Manual |
| Ajuste negativo | **SJ** | SALIDA POR AJUSTE DE INV. | ADJ_NEG | Manual |
| Requisición interna | **SR** | REQUISICIONES | REQUISITION | Manual |
| Requisición mantenimiento | **SM** | REQUISICIONES / MANTENIMIENTO | REQ_MAINT | Manual |
| Traslado salida | **ST** | SALIDA POR TRASLADO / BODEGA | TRANSFER_OUT | **Automático** |
| Traslado entrada | **ET** | ENTRADA POR TRASLADO/BODEGA | TRANSFER_IN | **Automático** |
| Compra local | **E0** | COMPRAS LOCALES | PURCH_LOCAL | **Automático** |
| Donación entrada | **EN** | ENTRADA POR DONACION | DONATION_IN | **Automático** |
| Despacho bodega | **SB** | DESPACHO DE BODEGA | DISPATCH | **Automático** |
| Venta crédito fiscal | **S0** | VENTAS-CREDITO FISCAL | SALE_CREDIT | **Automático** |
| Venta consumidor final | **S1** | VENTA A CONSUMIDOR FINAL | SALE_FINAL | **Automático** |

---

## Mapeo de Operaciones del Sistema

### COMPRAS

| Operación | Código | Legacy | Nombre Legacy | Afecta Costo |
|-----------|--------|--------|---------------|--------------|
| Recepción de compra a proveedor | `PURCH_LOCAL` | E0 | COMPRAS LOCALES | SÍ |
| Compra para proyectos | `PROJECT_IN` | E7 | ENTRADA POR PROYECTOS | SÍ |
| Compra por convenio institucional | `AGREEMENT_IN` | EI | INGRESOS POR CONVENIO | SÍ |

**Uso en código:**
```php
// Al recibir una compra
$reason = MovementReason::where('code', 'PURCH_LOCAL')->first();

// Buscar por código legacy (para migración)
$reason = MovementReason::where('legacy_code', 'E0')->first();
```

---

### VENTAS

| Operación | Código | Legacy | Nombre Legacy | Afecta Costo |
|-----------|--------|--------|---------------|--------------|
| Venta con Crédito Fiscal | `SALE_CREDIT` | S0 | VENTAS-CREDITO FISCAL | NO |
| Venta a Consumidor Final | `SALE_FINAL` | S1 | VENTA A CONSUMIDOR FINAL | NO |
| Venta con Ticket | `SALE_TICKET` | S2 | TICKETS | NO |
| Venta de Kit | `KIT_SALE` | SP | SALIDA POR VTA DE KIT | NO |
| Remisión a clientes | `REMISSION` | SV | REMISIONES A CLIENTES | NO |

---

### TRASLADOS ENTRE BODEGAS

| Operación | Código | Legacy | Nombre Legacy | Afecta Costo |
|-----------|--------|--------|---------------|--------------|
| Salida por traslado (origen) | `TRANSFER_OUT` | ST | SALIDA POR TRASLADO / BODEGA | NO |
| Entrada por traslado (destino) | `TRANSFER_IN` | ET | ENTRADA POR TRASLADO/BODEGA | NO |

**Flujo de traslado:**
1. Bodega origen: Crear movimiento con `TRANSFER_OUT`
2. Bodega destino: Crear movimiento con `TRANSFER_IN`

**Nota:** Los traslados NO afectan el costo promedio porque el producto mantiene su valor.

---

### AJUSTES DE INVENTARIO

| Operación | Código | Legacy | Nombre Legacy | Afecta Costo |
|-----------|--------|--------|---------------|--------------|
| Ajuste positivo (sobrante) | `ADJ_POS` | EJ | ENTRADAS POR AJUSTE DE INV. | SÍ |
| Ajuste negativo (faltante) | `ADJ_NEG` | SJ | SALIDA POR AJUSTE DE INV. | NO |
| Ajuste por medición (entrada) | `MEASURE_IN` | EM | AJUSTE POR MEDICIONES | NO |
| Ajuste por medición (salida) | `MEASURE_OUT` | SN | AJUSTE POR MEDICIONES | NO |

**Nota:** Los ajustes negativos NO afectan costo porque representan pérdida.

---

### DEVOLUCIONES

| Operación | Código | Legacy | Nombre Legacy | Afecta Costo |
|-----------|--------|--------|---------------|--------------|
| Devolución de cliente (contado) | `RETURN_CASH` | E3 | DEV. DE CLIENTES (VTA.CONTADO) | SÍ |
| Devolución por venta | `RETURN_SALE` | E6 | DEVOLUCION POR VENTA | NO |
| Devolución a proveedor | `RETURN_SUPP` | SD | DEVOLUCIONES A PROVEEDORES | NO |
| Devolución de repuestos | `PARTS_RETURN` | ER | DEVOLUCION DE REPUESTOS | SÍ |

---

### PRODUCCIÓN

| Operación | Código | Legacy | Nombre Legacy | Afecta Costo |
|-----------|--------|--------|---------------|--------------|
| Ingreso de producción | `PROD_IN` | EP | INGRESOS DE PRODUCCION | SÍ |
| Producto terminado | `FINISHED_PROD` | EY | ENTRADA POR PRODUCTO TERMINADO | SÍ |

---

### REQUISICIONES / DESPACHOS INTERNOS

| Operación | Código | Legacy | Nombre Legacy | Afecta Costo |
|-----------|--------|--------|---------------|--------------|
| Requisición interna | `REQUISITION` | SR | REQUISICIONES | NO |
| Requisición mantenimiento | `REQ_MAINT` | SM | REQUISICIONES / MANTENIMIENTO | SÍ |
| Despacho de bodega | `DISPATCH` | SB | DESPACHO DE BODEGA | NO |
| Consumo combustible | `FUEL_CONSUME` | SC | CONSUMO COMBUSTIBLE/LUBR | NO |

---

### DONACIONES

| Operación | Código | Legacy | Nombre Legacy | Afecta Costo |
|-----------|--------|--------|---------------|--------------|
| Recepción de donación | `DONATION_IN` | EN | ENTRADA POR DONACION | SÍ |

---

### BAJAS / DESCARTES

| Operación | Código | Legacy | Nombre Legacy | Afecta Costo |
|-----------|--------|--------|---------------|--------------|
| Descarte de productos | `DISCARD` | S4 | SALIDA POR DESCARTE DE PROD. | SÍ |
| Salida por obsolescencia | `OBSOL_OUT` | S5 | SALIDA POR OBSOLECENCIA | NO |
| Salida por garantía | `WARRANTY_OUT` | SG | SALIDA POR GARANTIA | NO |

---

### OTROS

| Operación | Código | Legacy | Nombre Legacy | Afecta Costo |
|-----------|--------|--------|---------------|--------------|
| Inventario inicial | `INITIAL_STOCK` | EZ | INVENTARIO INICIAL | SÍ |
| Entrada por bonificación | `BONUS_IN` | EB | ENTRADA POR BONIFICACION | NO |
| Entrada por consignación | `CONSIGN_IN` | EC | ENTRADA POR CONSIGNACION | NO |
| Notas de crédito | `CREDIT_NOTE` | ED | NOTAS DE CREDITO | NO |
| Cambio de productos (entrada) | `CHANGE_IN` | E2 | INGRESOS A BODEGA POR CAMBIO | SÍ |
| Cambio de productos (salida) | `CHANGE_OUT` | S3 | SALIDA POR CAMBIO DE PDTOS. | NO |
| Reingreso a bodega | `REENTRY` | E8 | ENTRADA POR REINGRESO A BODEGA | SÍ |
| Permutas | `EXCHANGE_IN` | E9 | INGRESO POR PERMUTAS | SÍ |
| Entrada por avería | `DAMAGE_IN` | EA | ENTRADA POR AVERIA | NO |
| Fondo mantenimiento estudiantes | `STUDENT_FUND` | E4 | C/FONDO DE MTTO DE ESTUDIANTES | SÍ |
| Autoconsumo | `SELF_CONSUME_IN` | EU | ENTRADA POR AUTOCONSUMO | SÍ |
| Descuentos | `DISCOUNT_IN` | EX | DESCUENTOS | SÍ |
| Entrada obsolescencia | `OBSOL_IN` | E5 | ENTRADA POR OBSOLENCIA | SÍ |

---

## Tabla Completa de Referencia

### ENTRADAS (24 tipos)

| Legacy | Nombre Legacy | Código Nuevo | Categoría | Afecta Costo |
|--------|---------------|--------------|-----------|--------------|
| E0 | COMPRAS LOCALES | PURCH_LOCAL | inbound | SÍ |
| E2 | INGRESOS A BODEGA POR CAMBIO | CHANGE_IN | inbound | SÍ |
| E3 | DEV. DE CLIENTES (VTA.CONTADO) | RETURN_CASH | inbound | SÍ |
| E4 | C/FONDO DE MTTO DE ESTUDIANTES | STUDENT_FUND | inbound | SÍ |
| E5 | ENTRADA POR OBSOLENCIA | OBSOL_IN | inbound | SÍ |
| E6 | DEVOLUCION POR VENTA | RETURN_SALE | inbound | NO |
| E7 | ENTRADA POR PROYECTOS | PROJECT_IN | inbound | SÍ |
| E8 | ENTRADA POR REINGRESO A BODEGA | REENTRY | inbound | SÍ |
| E9 | INGRESO POR PERMUTAS | EXCHANGE_IN | inbound | SÍ |
| EA | ENTRADA POR AVERIA | DAMAGE_IN | inbound | NO |
| EB | ENTRADA POR BONIFICACION | BONUS_IN | inbound | NO |
| EC | ENTRADA POR CONSIGNACION | CONSIGN_IN | inbound | NO |
| ED | NOTAS DE CREDITO | CREDIT_NOTE | inbound | NO |
| EI | INGRESOS POR CONVENIO | AGREEMENT_IN | inbound | SÍ |
| EJ | ENTRADAS POR AJUSTE DE INV. | ADJ_POS | adjustment | SÍ |
| EM | AJUSTE POR MEDICIONES | MEASURE_IN | adjustment | NO |
| EN | ENTRADA POR DONACION | DONATION_IN | inbound | SÍ |
| EP | INGRESOS DE PRODUCCION | PROD_IN | inbound | SÍ |
| ER | DEVOLUCION DE REPUESTOS | PARTS_RETURN | inbound | SÍ |
| ET | ENTRADA POR TRASLADO/BODEGA | TRANSFER_IN | transfer | NO |
| EU | ENTRADA POR AUTOCONSUMO | SELF_CONSUME_IN | inbound | SÍ |
| EX | DESCUENTOS | DISCOUNT_IN | inbound | SÍ |
| EY | ENTRADA POR PRODUCTO TERMINADO | FINISHED_PROD | inbound | SÍ |
| EZ | INVENTARIO INICIAL | INITIAL_STOCK | inbound | SÍ |

### SALIDAS (17 tipos)

| Legacy | Nombre Legacy | Código Nuevo | Categoría | Afecta Costo |
|--------|---------------|--------------|-----------|--------------|
| S0 | VENTAS-CREDITO FISCAL | SALE_CREDIT | outbound | NO |
| S1 | VENTA A CONSUMIDOR FINAL | SALE_FINAL | outbound | NO |
| S2 | TICKETS | SALE_TICKET | outbound | NO |
| S3 | SALIDA POR CAMBIO DE PDTOS. | CHANGE_OUT | outbound | NO |
| S4 | SALIDA POR DESCARTE DE PROD. | DISCARD | disposal | SÍ |
| S5 | SALIDA POR OBSOLECENCIA | OBSOL_OUT | disposal | NO |
| SB | DESPACHO DE BODEGA | DISPATCH | outbound | NO |
| SC | CONSUMO COMBUSTIBLE/LUBR | FUEL_CONSUME | outbound | NO |
| SD | DEVOLUCIONES A PROVEEDORES | RETURN_SUPP | outbound | NO |
| SG | SALIDA POR GARANTIA | WARRANTY_OUT | outbound | NO |
| SJ | SALIDA POR AJUSTE DE INV. | ADJ_NEG | adjustment | NO |
| SM | REQUISICIONES / MANTENIMIENTO | REQ_MAINT | outbound | SÍ |
| SN | AJUSTE POR MEDICIONES | MEASURE_OUT | adjustment | NO |
| SP | SALIDA POR VTA DE KIT | KIT_SALE | outbound | NO |
| SR | REQUISICIONES | REQUISITION | outbound | NO |
| ST | SALIDA POR TRASLADO / BODEGA | TRANSFER_OUT | transfer | NO |
| SV | REMISIONES A CLIENTES | REMISSION | outbound | NO |

---

## Regla del Campo `affects_cost`

| Valor | Significado | Efecto en Kardex |
|-------|-------------|------------------|
| `true` (1) | SÍ afecta costo | Recalcula costo promedio ponderado |
| `false` (0) | NO afecta costo | Solo mueve cantidad, mantiene costo actual |

### Ejemplo de cálculo:

**Cuando `affects_cost = true`:**
```
Nuevo Costo Promedio = (Cantidad_Actual × Costo_Actual + Cantidad_Nueva × Costo_Nuevo) / (Cantidad_Actual + Cantidad_Nueva)
```

**Cuando `affects_cost = false`:**
```
El costo promedio NO se modifica, solo cambia la cantidad.
```

---

## Uso en Código PHP

```php
use App\Models\MovementReason;

// Obtener razón por código nuevo
$reason = MovementReason::where('code', 'PURCH_LOCAL')->first();

// Obtener razón por código legacy (para migración de datos)
$reason = MovementReason::where('legacy_code', 'E0')->first();

// Obtener razón por nombre legacy (para búsquedas)
$reason = MovementReason::where('legacy_name', 'COMPRAS LOCALES')->first();

// Obtener todas las entradas
$entradas = MovementReason::where('movement_type', 'in')->get();

// Obtener todas las que afectan costo
$affectsCost = MovementReason::where('affects_cost', true)->get();

// Verificar si afecta costo antes de calcular
if ($reason->affects_cost) {
    // Recalcular costo promedio
} else {
    // Solo actualizar cantidad
}

// Mostrar información legacy para reportes
echo "Código: {$reason->legacy_code} - {$reason->legacy_name}";
```

---

## Códigos Duplicados (Pendientes de Confirmación)

| Legacy | Nombre Legacy | Nota |
|--------|---------------|------|
| E0 / ES | COMPRAS LOCALES | Actualmente solo E0 está activo |
| E3 / E6 | Devoluciones de clientes | E3 = contado, E6 = general |

**Acción:** Confirmar con el cliente si necesitan ambos códigos o si se pueden unificar.
