# Gestión de Proveedores

Este documento describe el flujo de trabajo completo para la gestión de proveedores en el sistema de bodega.

## Descripción General

Los proveedores son las entidades externas que suministran productos a la organización. Una correcta gestión de proveedores permite:

- Mantener un registro actualizado de todas las fuentes de suministro
- Evaluar el desempeño de los proveedores mediante calificaciones
- Gestionar términos de pago y límites de crédito
- Tener información de contacto siempre disponible
- Asociar compras con los proveedores correspondientes

## Campos del Proveedor

| Campo | Descripción | Requerido |
|-------|-------------|-----------|
| **Nombre** | Nombre comercial del proveedor | Sí |
| **Nombre Legal** | Razón social o nombre legal | No |
| **NIT/DUI** | Número de identificación tributaria | No |
| **Email** | Correo electrónico principal | No |
| **Teléfono** | Número telefónico principal | No |
| **Sitio Web** | URL del sitio web | No |
| **Persona de Contacto** | Nombre del contacto principal | No |
| **Email de Contacto** | Correo del contacto | No |
| **Teléfono de Contacto** | Teléfono del contacto | No |
| **Dirección** | Dirección física | No |
| **Ciudad** | Ciudad | No |
| **Departamento/Estado** | División administrativa | No |
| **Código Postal** | Código postal | No |
| **País** | País | No |
| **Términos de Pago** | Condiciones de pago acordadas | No |
| **Límite de Crédito** | Monto máximo de crédito otorgado | No |
| **Calificación** | Evaluación del 1 al 5 | No |
| **Notas** | Observaciones adicionales | No |
| **Estado** | Activo/Inactivo | Sí |

## Estados del Proveedor

| Estado | Descripción | Color |
|--------|-------------|-------|
| **Activo** | Proveedor disponible para operaciones | Verde |
| **Inactivo** | Proveedor temporalmente no disponible | Rojo |

## Flujo de Trabajo

```
┌─────────────────────┐
│  CREAR PROVEEDOR    │
└──────────┬──────────┘
           │
           ▼
┌─────────────────────┐
│  PROVEEDOR ACTIVO   │ ◄── Disponible para compras
└──────────┬──────────┘
           │
           │ Desactivar (si es necesario)
           ▼
┌─────────────────────┐
│ PROVEEDOR INACTIVO  │ ◄── No aparece en compras
└──────────┬──────────┘
           │
           │ Reactivar
           ▼
┌─────────────────────┐
│  PROVEEDOR ACTIVO   │
└─────────────────────┘

* Un proveedor solo puede eliminarse si no tiene compras asociadas
```

## Actores del Sistema

### 1. Super Administrador
- **Permisos**:
  - Crear proveedores para cualquier empresa
  - Ver todos los proveedores
  - Editar cualquier proveedor
  - Activar/Desactivar proveedores
  - Eliminar proveedores sin compras

### 2. Administrador de Empresa
- **Permisos**:
  - Crear proveedores para su empresa
  - Ver proveedores de su empresa
  - Editar proveedores de su empresa
  - Activar/Desactivar proveedores de su empresa
  - Eliminar proveedores sin compras de su empresa

### 3. Gerente de Bodega
- **Permisos**:
  - Ver proveedores de su empresa
  - Crear proveedores para su empresa
  - Editar proveedores de su empresa

### 4. Operador de Bodega
- **Permisos**:
  - Ver proveedores de su empresa (solo lectura)

## Relaciones con Otros Módulos

### Compras
- Cada compra debe estar asociada a un proveedor
- Un proveedor puede tener múltiples compras
- Al seleccionar un proveedor en una compra, se muestran solo los proveedores activos

### Movimientos de Inventario
- Los movimientos de entrada por compra referencian al proveedor
- Permite trazabilidad del origen de los productos

## Buenas Prácticas

1. **Información Completa**: Registrar toda la información disponible del proveedor
2. **Contactos Actualizados**: Mantener los datos de contacto siempre vigentes
3. **Calificaciones**: Evaluar regularmente el desempeño de los proveedores
4. **Términos Claros**: Documentar los términos de pago acordados
5. **Desactivar vs Eliminar**: Preferir desactivar un proveedor antes que eliminarlo

## Restricciones de Eliminación

Un proveedor **NO** puede ser eliminado si:
- Tiene compras asociadas (en cualquier estado)
- Tiene movimientos de inventario relacionados

En estos casos, la opción recomendada es **desactivar** el proveedor.

---

## Caso Práctico: Gestión de Proveedores en la ENA

### Escenario
La Escuela Nacional de Agricultura (ENA) necesita gestionar sus proveedores de insumos agrícolas, semillas y equipos.

### Actores Involucrados
- **Carlos López** - Jefe de Compras (Administrador de Empresa)
- **María García** - Encargada de Bodega Central (Gerente de Bodega)

### Paso 1: Registro de Nuevo Proveedor (Carlos López)

Carlos necesita registrar un nuevo proveedor de fertilizantes que ha sido evaluado y aprobado:

```
DATOS DEL PROVEEDOR:

Información General:
├── Nombre: Agroinsumos El Salvador, S.A. de C.V.
├── Nombre Legal: Agroinsumos El Salvador, Sociedad Anónima de Capital Variable
├── NIT: 0614-280595-101-5
├── Email: ventas@agroinsumos.com.sv
├── Teléfono: 2221-5500
└── Sitio Web: https://agroinsumos.com.sv

Contacto Principal:
├── Persona: Roberto Hernández
├── Cargo: Ejecutivo de Ventas Institucionales
├── Email: rhernandez@agroinsumos.com.sv
└── Teléfono: 7890-1234

Dirección:
├── Dirección: Km 10.5 Carretera a Santa Ana
├── Ciudad: Santa Ana
├── Departamento: Santa Ana
├── Código Postal: 01101
└── País: El Salvador

Términos Comerciales:
├── Términos de Pago: 30 días
├── Límite de Crédito: $25,000.00
└── Calificación: ★★★★★ (5/5)

Notas:
"Proveedor autorizado por el Ministerio de Agricultura.
Especializado en fertilizantes y agroquímicos de alta calidad.
Entregas programadas los martes y jueves."

Estado: Activo ✓
```

### Paso 2: Registro de Proveedor de Semillas (Carlos López)

Siguiendo el mismo proceso, Carlos registra otro proveedor:

```
DATOS DEL PROVEEDOR:

Información General:
├── Nombre: SEMILLAS MEJORADAS, S.A.
├── NIT: 0614-150687-001-3
├── Email: pedidos@semillasmejoradas.com
└── Teléfono: 2235-4400

Contacto Principal:
├── Persona: Ana María Portillo
├── Email: aportillo@semillasmejoradas.com
└── Teléfono: 7654-3210

Dirección:
├── Dirección: Blvd. del Ejército Nacional, Local 45
├── Ciudad: Soyapango
├── Departamento: San Salvador
└── País: El Salvador

Términos Comerciales:
├── Términos de Pago: 15 días
├── Límite de Crédito: $10,000.00
└── Calificación: ★★★★☆ (4/5)

Notas:
"Certificados por CENTA para semillas mejoradas.
Importante: Requieren orden de compra previa."

Estado: Activo ✓
```

### Paso 3: Proveedor con Problemas (María García reporta)

María reporta que el proveedor "Insumos Centroamericanos" ha tenido problemas de calidad y entregas:

```
HISTORIAL DE INCIDENTES:
├── 15/10/2024: Entrega retrasada 5 días
├── 22/10/2024: Productos con fecha de vencimiento próxima
└── 05/11/2024: Factura con diferencias de precio

ACCIONES TOMADAS:
1. Carlos actualiza la calificación de ★★★★☆ a ★★☆☆☆
2. Añade nota: "PRECAUCIÓN: Verificar calidad de productos al recibir"
3. Reduce límite de crédito de $15,000 a $5,000
4. Mantiene estado ACTIVO para honrar compromisos pendientes
```

### Paso 4: Desactivación de Proveedor

Después de múltiples incidentes, Carlos decide desactivar el proveedor:

```
DESACTIVACIÓN:

Proveedor: Insumos Centroamericanos
Motivo: Incumplimiento reiterado en calidad y tiempos de entrega

Acciones:
1. Cambiar estado de "Activo" a "Inactivo"
2. El sistema registra la fecha de desactivación
3. El proveedor ya no aparece en el selector de nuevas compras
4. Las compras existentes mantienen la referencia al proveedor
5. Añadir nota final: "Desactivado por incumplimiento.
   Contactar a Jefe de Compras para reactivar."
```

### Lista de Proveedores de la ENA

```
PROVEEDORES ACTIVOS:
┌────────────────────────────────┬───────────────┬────────────┬──────────┐
│ Nombre                         │ NIT           │ Crédito    │ Rating   │
├────────────────────────────────┼───────────────┼────────────┼──────────┤
│ Agroinsumos El Salvador        │ 0614-280595-1 │ $25,000.00 │ ★★★★★    │
│ SEMILLAS MEJORADAS             │ 0614-150687-0 │ $10,000.00 │ ★★★★☆    │
│ Ferretería Industrial S.A.     │ 0614-050292-1 │ $8,000.00  │ ★★★★☆    │
│ Repuestos Agrícolas del Centro │ 0614-180599-1 │ $5,000.00  │ ★★★☆☆    │
└────────────────────────────────┴───────────────┴────────────┴──────────┘

PROVEEDORES INACTIVOS:
┌────────────────────────────────┬───────────────┬────────────┬──────────┐
│ Nombre                         │ NIT           │ Crédito    │ Rating   │
├────────────────────────────────┼───────────────┼────────────┼──────────┤
│ Insumos Centroamericanos       │ 0614-230488-1 │ $5,000.00  │ ★★☆☆☆    │
└────────────────────────────────┴───────────────┴────────────┴──────────┘
```

### Flujo de Compra con Proveedor

Cuando María crea una nueva compra:

```
NUEVA COMPRA:

1. Seleccionar Bodega: Bodega Central - ENA
2. Seleccionar Proveedor: [Lista solo muestra proveedores ACTIVOS]
   ├── Agroinsumos El Salvador ★★★★★
   ├── SEMILLAS MEJORADAS ★★★★☆
   ├── Ferretería Industrial S.A. ★★★★☆
   └── Repuestos Agrícolas del Centro ★★★☆☆

3. Al seleccionar "Agroinsumos El Salvador":
   └── Se muestra información del proveedor:
       ├── Contacto: Roberto Hernández (7890-1234)
       ├── Términos: 30 días
       └── Crédito disponible: $25,000.00
```

### Reportes de Proveedores

El sistema permite generar reportes por proveedor:

```
REPORTE DE PROVEEDOR: Agroinsumos El Salvador

Período: Enero - Noviembre 2024

Resumen de Compras:
├── Total de compras: 15
├── Monto total: $45,750.00
├── Compras recibidas: 14
├── Compras pendientes: 1
└── Promedio por compra: $3,050.00

Productos más comprados:
┌───────────────────────────┬──────────┬────────────┐
│ Producto                  │ Cantidad │ Valor      │
├───────────────────────────┼──────────┼────────────┤
│ Fertilizante NPK 15-15-15 │ 250 qq   │ $11,250.00 │
│ Urea Granular 46-0-0      │ 150 qq   │ $6,750.00  │
│ Insecticida Cipermetrina  │ 45 lt    │ $1,575.00  │
└───────────────────────────┴──────────┴────────────┘

Cumplimiento:
├── Entregas a tiempo: 93%
├── Calidad de productos: 98%
└── Precisión en facturación: 100%
```

### Evaluación Periódica de Proveedores

Cada trimestre, Carlos evalúa a los proveedores:

```
CRITERIOS DE EVALUACIÓN:

1. Calidad de Productos (30%)
   └── Conformidad con especificaciones

2. Tiempo de Entrega (25%)
   └── Cumplimiento de fechas acordadas

3. Precio Competitivo (20%)
   └── Comparación con mercado

4. Servicio al Cliente (15%)
   └── Respuesta a consultas y reclamos

5. Documentación (10%)
   └── Facturas correctas y completas

RESULTADO AGROINSUMOS EL SALVADOR:
├── Calidad: 30/30
├── Entrega: 23/25
├── Precio: 18/20
├── Servicio: 14/15
├── Documentación: 10/10
└── TOTAL: 95/100 → ★★★★★
```

### Trazabilidad Completa

En cualquier momento, se puede consultar:

- Todas las compras realizadas a un proveedor
- Productos suministrados por proveedor
- Historial de precios por proveedor
- Movimientos de inventario por proveedor
- Lotes de productos por proveedor

Esta información es vital para:
- Negociaciones de precios
- Decisiones de renovación de contratos
- Auditorías internas y externas
- Cumplimiento de normativas de adquisiciones

---

*Última actualización: Noviembre 2024*
