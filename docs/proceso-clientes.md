# Gestión de Clientes

Este documento describe el flujo de trabajo completo para la gestión de clientes en el sistema de bodega.

## Descripción General

Los clientes son las entidades o personas que reciben productos desde la organización, ya sea por venta, distribución o despacho. Una correcta gestión de clientes permite:

- Mantener un registro actualizado de todos los destinatarios
- Clasificar clientes por tipo (individual o empresa)
- Gestionar información de facturación y envío
- Controlar términos de pago y límites de crédito
- Asociar despachos y salidas de inventario con clientes específicos

## Tipos de Clientes

| Tipo | Descripción | Color |
|------|-------------|-------|
| **Individual** | Personas naturales, consumidores finales | Gris (zinc) |
| **Empresa** | Personas jurídicas, negocios, instituciones | Azul |

## Campos del Cliente

### Información General

| Campo | Descripción | Requerido |
|-------|-------------|-----------|
| **Nombre** | Nombre del cliente | Sí |
| **Tipo** | Individual o Empresa | Sí |
| **Nombre de Empresa** | Razón social (solo empresas) | Condicional |
| **Número de Registro** | Número de registro comercial | No |
| **NIT/DUI** | Número de identificación tributaria | No |
| **Email** | Correo electrónico principal | No |
| **Teléfono** | Número telefónico | No |
| **Celular** | Número móvil | No |
| **Sitio Web** | URL del sitio web | No |

### Información de Contacto

| Campo | Descripción | Requerido |
|-------|-------------|-----------|
| **Nombre de Contacto** | Persona de contacto | No |
| **Email de Contacto** | Correo del contacto | No |
| **Teléfono de Contacto** | Teléfono del contacto | No |
| **Cargo del Contacto** | Posición en la organización | No |

### Dirección de Facturación

| Campo | Descripción | Requerido |
|-------|-------------|-----------|
| **Dirección** | Dirección de facturación | No |
| **Ciudad** | Ciudad | No |
| **Departamento/Estado** | División administrativa | No |
| **Código Postal** | Código postal | No |
| **País** | País | No |

### Dirección de Envío

| Campo | Descripción | Requerido |
|-------|-------------|-----------|
| **Misma que Facturación** | Usar dirección de facturación | No |
| **Dirección** | Dirección de envío | Condicional |
| **Ciudad** | Ciudad de envío | No |
| **Departamento/Estado** | División administrativa | No |
| **Código Postal** | Código postal | No |
| **País** | País de envío | No |

### Información Comercial

| Campo | Descripción | Requerido |
|-------|-------------|-----------|
| **Días de Crédito** | Plazo de pago en días | No |
| **Método de Pago** | Forma de pago preferida | No |
| **Moneda** | Moneda de transacciones | No |
| **Límite de Crédito** | Monto máximo de crédito | No |
| **Estado** | Activo/Inactivo | Sí |

## Estados del Cliente

| Estado | Descripción | Color |
|--------|-------------|-------|
| **Activo** | Cliente disponible para operaciones | Verde |
| **Inactivo** | Cliente temporalmente no disponible | Rojo |

## Flujo de Trabajo

```
┌─────────────────────┐
│   CREAR CLIENTE     │
└──────────┬──────────┘
           │
           ▼
┌─────────────────────┐
│   CLIENTE ACTIVO    │ ◄── Disponible para despachos
└──────────┬──────────┘
           │
           │ Desactivar (si es necesario)
           ▼
┌─────────────────────┐
│  CLIENTE INACTIVO   │ ◄── No aparece en despachos
└──────────┬──────────┘
           │
           │ Reactivar
           ▼
┌─────────────────────┐
│   CLIENTE ACTIVO    │
└─────────────────────┘

* Un cliente solo puede eliminarse si no tiene despachos asociados
```

## Actores del Sistema

### 1. Super Administrador
- **Permisos**:
  - Crear clientes para cualquier empresa
  - Ver todos los clientes
  - Editar cualquier cliente
  - Activar/Desactivar clientes
  - Eliminar clientes sin despachos

### 2. Administrador de Empresa
- **Permisos**:
  - Crear clientes para su empresa
  - Ver clientes de su empresa
  - Editar clientes de su empresa
  - Activar/Desactivar clientes de su empresa
  - Eliminar clientes sin despachos de su empresa

### 3. Gerente de Bodega
- **Permisos**:
  - Ver clientes de su empresa
  - Crear clientes para su empresa
  - Editar clientes de su empresa

### 4. Operador de Bodega
- **Permisos**:
  - Ver clientes de su empresa (solo lectura)

## Relaciones con Otros Módulos

### Despachos
- Cada despacho debe estar asociado a un cliente
- Un cliente puede tener múltiples despachos
- Al seleccionar un cliente en un despacho, se muestran solo los clientes activos

### Movimientos de Inventario
- Los movimientos de salida por despacho referencian al cliente
- Permite trazabilidad del destino de los productos

## Buenas Prácticas

1. **Clasificación Correcta**: Asignar el tipo de cliente apropiado (Individual/Empresa)
2. **Información Completa**: Registrar toda la información disponible
3. **Direcciones Separadas**: Mantener direcciones de facturación y envío actualizadas
4. **Términos Claros**: Documentar los términos de pago acordados
5. **Límites de Crédito**: Establecer límites de crédito realistas

## Restricciones de Eliminación

Un cliente **NO** puede ser eliminado si:
- Tiene despachos asociados (en cualquier estado)
- Tiene movimientos de inventario relacionados

En estos casos, la opción recomendada es **desactivar** el cliente.

---

## Caso Práctico: Gestión de Clientes en la ENA

### Escenario
La Escuela Nacional de Agricultura (ENA) distribuye productos a diversos clientes: instituciones educativas, cooperativas agrícolas, comercializadoras y personas individuales que participan en programas de extensión.

### Actores Involucrados
- **Laura Menéndez** - Coordinadora de Distribución (Administrador de Empresa)
- **María García** - Encargada de Bodega Central (Gerente de Bodega)
- **Juan Martínez** - Bodeguero (Operador de Bodega)

### Paso 1: Registro de Cliente Institucional (Laura Menéndez)

Laura registra una cooperativa agrícola como nuevo cliente:

```
DATOS DEL CLIENTE:

Información General:
├── Nombre: Cooperativa Agrícola Los Pinos
├── Tipo: Empresa [Azul]
├── Nombre de Empresa: Cooperativa Agrícola Los Pinos de R.L.
├── Número de Registro: REG-COOP-2018-0547
├── NIT: 0614-180518-101-9
├── Email: cooperativa@lospinos.com
├── Teléfono: 2663-5500
└── Sitio Web: https://cooplospinos.com

Contacto Principal:
├── Nombre: Ing. Manuel Flores
├── Cargo: Presidente de la Cooperativa
├── Email: mflores@lospinos.com
└── Teléfono: 7890-2345

Dirección de Facturación:
├── Dirección: Km 85 Carretera a San Miguel
├── Ciudad: San Miguel
├── Departamento: San Miguel
├── Código Postal: 03101
└── País: El Salvador

Dirección de Envío:
├── Misma que Facturación: No
├── Dirección: Finca Los Pinos, Cantón El Progreso
├── Ciudad: Chinameca
├── Departamento: San Miguel
└── País: El Salvador

Información Comercial:
├── Días de Crédito: 30
├── Método de Pago: Transferencia Bancaria
├── Moneda: USD
└── Límite de Crédito: $15,000.00

Estado: Activo ✓
```

### Paso 2: Registro de Institución Educativa (Laura Menéndez)

Registro de un instituto técnico que compra semillas para su programa agrícola:

```
DATOS DEL CLIENTE:

Información General:
├── Nombre: Instituto Nacional de Santa Ana
├── Tipo: Empresa [Azul]
├── Nombre de Empresa: Instituto Nacional de Santa Ana - INSA
├── Número de Registro: MINED-SA-001
├── NIT: 0614-010160-001-0
├── Email: direccion@insa.edu.sv
└── Teléfono: 2441-0200

Contacto Principal:
├── Nombre: Lic. Patricia Rivas
├── Cargo: Coordinadora de Área Técnica
├── Email: privas@insa.edu.sv
└── Teléfono: 7654-3210

Dirección de Facturación:
├── Dirección: 4a Calle Poniente #15
├── Ciudad: Santa Ana
├── Departamento: Santa Ana
├── Código Postal: 01101
└── País: El Salvador

Dirección de Envío:
└── Misma que Facturación: Sí ✓

Información Comercial:
├── Días de Crédito: 0 (Contado)
├── Método de Pago: Cheque Institucional
├── Moneda: USD
└── Límite de Crédito: $0.00

Notas:
"Institución pública. Requiere orden de compra oficial.
Entregas solo en horario escolar (7am-3pm)."

Estado: Activo ✓
```

### Paso 3: Registro de Cliente Individual (María García)

Un agricultor participante del programa de extensión:

```
DATOS DEL CLIENTE:

Información General:
├── Nombre: Pedro Antonio Ramos González
├── Tipo: Individual [Gris]
├── DUI: 02345678-9
├── Email: pedro.ramos@gmail.com
├── Teléfono: 2635-4567
└── Celular: 7234-5678

Dirección de Facturación:
├── Dirección: Caserío El Rosario, Cantón San José
├── Ciudad: Ahuachapán
├── Departamento: Ahuachapán
└── País: El Salvador

Dirección de Envío:
└── Misma que Facturación: Sí ✓

Información Comercial:
├── Días de Crédito: 0 (Contado)
├── Método de Pago: Efectivo
├── Moneda: USD
└── Límite de Crédito: $0.00

Notas:
"Pequeño agricultor, 2 manzanas de cultivo.
Participante del Programa de Extensión ENA 2024.
Beneficiario de semillas subsidiadas."

Estado: Activo ✓
```

### Paso 4: Registro de Comercializadora (Laura Menéndez)

Una empresa que compra productos para reventa:

```
DATOS DEL CLIENTE:

Información General:
├── Nombre: Distribuidora Agrícola Nacional
├── Tipo: Empresa [Azul]
├── Nombre de Empresa: Distribuidora Agrícola Nacional, S.A. de C.V.
├── Número de Registro: REG-COM-2015-1234
├── NIT: 0614-150715-101-3
├── Email: ventas@distragricola.com.sv
├── Teléfono: 2260-8800
└── Sitio Web: https://distragricola.com.sv

Contacto Principal:
├── Nombre: Lic. Roberto Guzmán
├── Cargo: Gerente de Compras
├── Email: rguzmaan@distragricola.com.sv
└── Teléfono: 7890-1234

Dirección de Facturación:
├── Dirección: Blvd. del Ejército, Km 7.5
├── Ciudad: Soyapango
├── Departamento: San Salvador
├── Código Postal: 01101
└── País: El Salvador

Dirección de Envío:
├── Misma que Facturación: No
├── Dirección: Centro de Distribución, Km 10 Carretera a Cojutepeque
├── Ciudad: Cojutepeque
├── Departamento: Cuscatlán
└── País: El Salvador

Información Comercial:
├── Días de Crédito: 45
├── Método de Pago: Transferencia Bancaria
├── Moneda: USD
└── Límite de Crédito: $50,000.00

Notas:
"Cliente mayorista con alto volumen de compra.
Requiere certificado de calidad por lote.
Horario de recepción: Lunes a Viernes 6am-4pm."

Estado: Activo ✓
```

### Lista de Clientes de la ENA por Tipo

```
CLIENTES ACTIVOS POR TIPO:

EMPRESAS [Azul]:
┌────────────────────────────────┬───────────────┬─────────────┬──────────┐
│ Nombre                         │ NIT           │ Crédito     │ Días     │
├────────────────────────────────┼───────────────┼─────────────┼──────────┤
│ Cooperativa Agrícola Los Pinos │ 0614-180518-1 │ $15,000.00  │ 30       │
│ Distribuidora Agrícola Nacional│ 0614-150715-1 │ $50,000.00  │ 45       │
│ Instituto Nacional Santa Ana   │ 0614-010160-0 │ $0.00       │ Contado  │
│ COOP. Cafetaleros del Volcán   │ 0614-200895-1 │ $8,000.00   │ 15       │
│ Agroveterinaria El Campo       │ 0614-120510-1 │ $5,000.00   │ 15       │
└────────────────────────────────┴───────────────┴─────────────┴──────────┘

INDIVIDUALES [Gris]:
┌────────────────────────────────┬───────────────┬─────────────┬──────────┐
│ Nombre                         │ DUI           │ Crédito     │ Días     │
├────────────────────────────────┼───────────────┼─────────────┼──────────┤
│ Pedro Antonio Ramos González   │ 02345678-9    │ $0.00       │ Contado  │
│ María Elena Vásquez de López   │ 03456789-0    │ $0.00       │ Contado  │
│ José Carlos Hernández Martínez │ 01234567-8    │ $500.00     │ 7        │
└────────────────────────────────┴───────────────┴─────────────┴──────────┘
```

### Flujo de Despacho con Cliente

Cuando María crea un nuevo despacho:

```
NUEVO DESPACHO:

1. Seleccionar Bodega Origen: Bodega Central - ENA
2. Seleccionar Cliente: [Lista solo muestra clientes ACTIVOS]

   EMPRESAS:
   ├── Cooperativa Agrícola Los Pinos (Crédito 30 días)
   ├── Distribuidora Agrícola Nacional (Crédito 45 días)
   ├── Instituto Nacional Santa Ana (Contado)
   └── ...

   INDIVIDUALES:
   ├── Pedro Antonio Ramos González (Contado)
   ├── María Elena Vásquez (Contado)
   └── ...

3. Al seleccionar "Distribuidora Agrícola Nacional":
   └── Se muestra información del cliente:
       ├── Contacto: Lic. Roberto Guzmán (7890-1234)
       ├── Términos: Crédito 45 días
       ├── Límite de Crédito: $50,000.00
       ├── Crédito Utilizado: $32,500.00
       ├── Crédito Disponible: $17,500.00
       └── Dirección de Envío: Centro de Distribución, Cojutepeque
```

### Despacho Completado

```
DESPACHO: DSP-2024-0892

Cliente: Distribuidora Agrícola Nacional
Tipo: Empresa

Información de Envío:
├── Dirección: Centro de Distribución, Km 10 Carretera a Cojutepeque
├── Ciudad: Cojutepeque, Cuscatlán
├── Contacto: Lic. Roberto Guzmán
└── Teléfono: 7890-1234

Productos Despachados:
┌─────────────────────────┬──────────┬────────────┬─────────────┐
│ Producto                │ Cantidad │ Precio     │ Total       │
├─────────────────────────┼──────────┼────────────┼─────────────┤
│ Semilla de Maíz H-59    │ 100 qq   │ $95.00     │ $9,500.00   │
│ Semilla de Frijol CENTA │ 50 qq    │ $120.00    │ $6,000.00   │
│ Fertilizante 15-15-15   │ 75 qq    │ $48.00     │ $3,600.00   │
└─────────────────────────┴──────────┴────────────┴─────────────┘
                                         Subtotal: $19,100.00
                                         IVA 13%:  $2,483.00
                                         TOTAL:    $21,583.00

Términos: Crédito 45 días
Fecha de Vencimiento: 06/01/2025
```

### Control de Crédito

El sistema monitorea el crédito de cada cliente:

```
ESTADO DE CRÉDITO: Distribuidora Agrícola Nacional

Límite de Crédito: $50,000.00

Facturas Pendientes:
┌────────────┬────────────┬────────────┬─────────────┬───────────┐
│ Factura    │ Fecha      │ Vencimiento│ Monto       │ Estado    │
├────────────┼────────────┼────────────┼─────────────┼───────────┤
│ FAC-001234 │ 15/10/2024 │ 29/11/2024 │ $12,500.00  │ Por vencer│
│ FAC-001256 │ 01/11/2024 │ 16/12/2024 │ $8,500.00   │ Vigente   │
│ FAC-001278 │ 15/11/2024 │ 30/12/2024 │ $11,500.00  │ Vigente   │
└────────────┴────────────┴────────────┴─────────────┴───────────┘

Resumen:
├── Crédito Utilizado: $32,500.00
├── Crédito Disponible: $17,500.00
└── Porcentaje Utilizado: 65%

⚠️ ALERTA: FAC-001234 vence en 7 días
```

### Reportes por Cliente

```
REPORTE DE CLIENTE: Cooperativa Agrícola Los Pinos

Período: Enero - Noviembre 2024

Resumen de Despachos:
├── Total de despachos: 12
├── Monto total: $45,000.00
├── Despachos completados: 11
├── Despachos pendientes: 1
└── Promedio por despacho: $3,750.00

Productos más solicitados:
┌───────────────────────────┬──────────┬────────────┐
│ Producto                  │ Cantidad │ Valor      │
├───────────────────────────┼──────────┼────────────┤
│ Semilla de Café Pacamara  │ 200 qq   │ $24,000.00 │
│ Fertilizante 18-6-12      │ 150 qq   │ $9,000.00  │
│ Fungicida Cobre           │ 50 lt    │ $3,500.00  │
└───────────────────────────┴──────────┴────────────┘

Comportamiento de Pago:
├── Pagos a tiempo: 95%
├── Pagos con retraso: 5%
├── Días promedio de pago: 28
└── Calificación crediticia: Excelente
```

### Gestión de Direcciones Múltiples

Para clientes con varios puntos de entrega:

```
CLIENTE: Distribuidora Agrícola Nacional

Dirección de Facturación:
├── Blvd. del Ejército, Km 7.5
├── Soyapango, San Salvador
└── El Salvador

Direcciones de Envío Registradas:
┌─────────────────────────────────────────┬─────────────────┐
│ Dirección                               │ Contacto        │
├─────────────────────────────────────────┼─────────────────┤
│ Centro Distrib., Km 10 Cojutepeque      │ Juan Pérez      │
│ Sucursal Santa Ana, Av. Fray Felipe     │ María López     │
│ Bodega San Miguel, Km 140 CA-1          │ Carlos Ramos    │
└─────────────────────────────────────────┴─────────────────┘

Al crear despacho: Seleccionar dirección de envío del listado.
```

### Trazabilidad Completa

En cualquier momento, se puede consultar:

- Todos los despachos realizados a un cliente
- Productos entregados por cliente
- Historial de precios por cliente
- Estado de cuenta y pagos
- Movimientos de inventario por cliente

Esta información es vital para:
- Gestión de cartera de clientes
- Análisis de ventas
- Pronóstico de demanda
- Auditorías internas y externas
- Cumplimiento de compromisos

---

*Última actualización: Noviembre 2024*
