# Gestión de Donantes

Este documento describe el flujo de trabajo completo para la gestión de donantes en el sistema de bodega.

## Descripción General

Los donantes son entidades o personas que contribuyen productos, equipos o recursos a la organización sin fines de lucro o contraprestación comercial directa. Una correcta gestión de donantes permite:

- Mantener un registro actualizado de todas las fuentes de donación
- Clasificar donantes por tipo (individuales, organizaciones, gobierno, ONGs, internacionales)
- Gestionar la relación con cada donante
- Generar reportes de donaciones para rendición de cuentas
- Cumplir con requisitos de transparencia y auditoría

## Tipos de Donantes

| Tipo | Descripción | Color |
|------|-------------|-------|
| **Persona Individual** | Donantes particulares, ciudadanos | Gris (zinc) |
| **Organización** | Empresas privadas, fundaciones | Azul |
| **Gobierno** | Entidades gubernamentales, ministerios | Amarillo (amber) |
| **ONG** | Organizaciones No Gubernamentales | Verde |
| **Organización Internacional** | Agencias internacionales (ONU, USAID, etc.) | Morado (purple) |

## Campos del Donante

| Campo | Descripción | Requerido |
|-------|-------------|-----------|
| **Nombre** | Nombre del donante | Sí |
| **Nombre Legal** | Razón social o nombre legal | No |
| **Tipo de Donante** | Clasificación del donante | Sí |
| **NIT/DUI** | Número de identificación | No |
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
| **Calificación** | Evaluación del 1 al 5 | No |
| **Notas** | Observaciones adicionales | No |
| **Estado** | Activo/Inactivo | Sí |

## Estados del Donante

| Estado | Descripción | Color |
|--------|-------------|-------|
| **Activo** | Donante disponible para recibir donaciones | Verde |
| **Inactivo** | Donante temporalmente no disponible | Rojo |

## Flujo de Trabajo

```
┌─────────────────────┐
│  CREAR DONANTE      │
└──────────┬──────────┘
           │
           ▼
┌─────────────────────┐
│   DONANTE ACTIVO    │ ◄── Disponible para donaciones
└──────────┬──────────┘
           │
           │ Desactivar (si es necesario)
           ▼
┌─────────────────────┐
│  DONANTE INACTIVO   │ ◄── No aparece en donaciones
└──────────┬──────────┘
           │
           │ Reactivar
           ▼
┌─────────────────────┐
│   DONANTE ACTIVO    │
└─────────────────────┘

* Un donante solo puede eliminarse si no tiene donaciones asociadas
```

## Actores del Sistema

### 1. Super Administrador
- **Permisos**:
  - Crear donantes para cualquier empresa
  - Ver todos los donantes
  - Editar cualquier donante
  - Activar/Desactivar donantes
  - Eliminar donantes sin donaciones

### 2. Administrador de Empresa
- **Permisos**:
  - Crear donantes para su empresa
  - Ver donantes de su empresa
  - Editar donantes de su empresa
  - Activar/Desactivar donantes de su empresa
  - Eliminar donantes sin donaciones de su empresa

### 3. Gerente de Bodega
- **Permisos**:
  - Ver donantes de su empresa
  - Crear donantes para su empresa
  - Editar donantes de su empresa

### 4. Operador de Bodega
- **Permisos**:
  - Ver donantes de su empresa (solo lectura)

## Relaciones con Otros Módulos

### Donaciones
- Cada donación debe estar asociada a un donante
- Un donante puede tener múltiples donaciones
- Al seleccionar un donante en una donación, se muestran solo los donantes activos

### Movimientos de Inventario
- Los movimientos de entrada por donación referencian al donante
- Permite trazabilidad del origen de los productos donados

### Reportes
- Generación de reportes por donante
- Informes para rendición de cuentas
- Certificados de recepción de donaciones

## Buenas Prácticas

1. **Clasificación Correcta**: Asignar el tipo de donante apropiado
2. **Información Completa**: Registrar toda la información disponible
3. **Contactos Actualizados**: Mantener los datos de contacto siempre vigentes
4. **Documentación**: Adjuntar convenios o acuerdos cuando existan
5. **Trazabilidad**: Registrar todas las donaciones recibidas

## Restricciones de Eliminación

Un donante **NO** puede ser eliminado si:
- Tiene donaciones asociadas (en cualquier estado)
- Tiene movimientos de inventario relacionados

En estos casos, la opción recomendada es **desactivar** el donante.

---

## Caso Práctico: Gestión de Donantes en la ENA

### Escenario
La Escuela Nacional de Agricultura (ENA) recibe donaciones de múltiples fuentes: organizaciones internacionales, el gobierno, ONGs locales, empresas privadas y personas individuales.

### Actores Involucrados
- **Carmen Álvarez** - Coordinadora de Cooperación (Administrador de Empresa)
- **María García** - Encargada de Bodega Central (Gerente de Bodega)
- **Pedro Ramírez** - Director Ejecutivo (Super Administrador)

### Paso 1: Registro de Donante Internacional (Carmen Álvarez)

Carmen registra a USAID como nuevo donante después de firmar un convenio de cooperación:

```
DATOS DEL DONANTE:

Información General:
├── Nombre: USAID El Salvador
├── Nombre Legal: United States Agency for International Development
├── Tipo de Donante: Organización Internacional [Morado]
├── Email: elsalvador@usaid.gov
├── Teléfono: 2501-2999
└── Sitio Web: https://www.usaid.gov/el-salvador

Contacto Principal:
├── Persona: Jennifer Thompson
├── Cargo: Oficial de Programas Agrícolas
├── Email: jthompson@usaid.gov
└── Teléfono: 2501-2950

Dirección:
├── Dirección: Boulevard Santa Elena, Edificio Embassy
├── Ciudad: Antiguo Cuscatlán
├── Departamento: La Libertad
└── País: El Salvador

Calificación: ★★★★★ (5/5)

Notas:
"Convenio de Cooperación USAID-ENA 2024-2027
Enfoque: Fortalecimiento de capacidades agrícolas
Contacto emergencia: Embajada USA 2501-2999"

Estado: Activo ✓
```

### Paso 2: Registro de Donante Gubernamental (Carmen Álvarez)

Registro del Ministerio de Agricultura y Ganadería:

```
DATOS DEL DONANTE:

Información General:
├── Nombre: Ministerio de Agricultura y Ganadería
├── Nombre Legal: MAG - Ministerio de Agricultura y Ganadería de El Salvador
├── Tipo de Donante: Gobierno [Amarillo]
├── Email: info@mag.gob.sv
├── Teléfono: 2210-1700
└── Sitio Web: https://www.mag.gob.sv

Contacto Principal:
├── Persona: Ing. Roberto Méndez
├── Cargo: Director de Extensión Agrícola
├── Email: rmendez@mag.gob.sv
└── Teléfono: 2210-1750

Dirección:
├── Dirección: Final 1a Av. Norte y 13 C. Pte.
├── Ciudad: Santa Tecla
├── Departamento: La Libertad
└── País: El Salvador

Calificación: ★★★★★ (5/5)

Notas:
"Convenio interinstitucional MAG-ENA vigente.
Donaciones periódicas de semillas certificadas y equipos."

Estado: Activo ✓
```

### Paso 3: Registro de ONG Local (Carmen Álvarez)

```
DATOS DEL DONANTE:

Información General:
├── Nombre: Fundación Agrícola Salvadoreña
├── Nombre Legal: FUNDASAL - Fundación Agrícola Salvadoreña
├── Tipo de Donante: ONG [Verde]
├── NIT: 0614-150390-001-2
├── Email: contacto@fundasal.org.sv
├── Teléfono: 2225-8100
└── Sitio Web: https://fundasal.org.sv

Contacto Principal:
├── Persona: Lic. María Elena Castro
├── Cargo: Directora de Proyectos
├── Email: mcastro@fundasal.org.sv
└── Teléfono: 7890-4567

Dirección:
├── Dirección: 25 Av. Norte #1080
├── Ciudad: San Salvador
├── Departamento: San Salvador
└── País: El Salvador

Calificación: ★★★★☆ (4/5)

Notas:
"ONG local con experiencia en desarrollo rural.
Donaciones principalmente de herramientas y equipos menores."

Estado: Activo ✓
```

### Paso 4: Registro de Empresa Privada (Carmen Álvarez)

```
DATOS DEL DONANTE:

Información General:
├── Nombre: Agroindustrias del Pacífico
├── Nombre Legal: Agroindustrias del Pacífico, S.A. de C.V.
├── Tipo de Donante: Organización [Azul]
├── NIT: 0614-230585-104-8
├── Email: rse@agropac.com.sv
└── Teléfono: 2278-5500

Contacto Principal:
├── Persona: Ing. Carlos Mendoza
├── Cargo: Gerente de RSE
├── Email: cmendoza@agropac.com.sv
└── Teléfono: 7654-8901

Dirección:
├── Dirección: Km 11 Carretera al Puerto de La Libertad
├── Ciudad: La Libertad
├── Departamento: La Libertad
└── País: El Salvador

Calificación: ★★★★☆ (4/5)

Notas:
"Programa de Responsabilidad Social Empresarial.
Donaciones anuales de productos agroindustriales y equipos."

Estado: Activo ✓
```

### Paso 5: Registro de Donante Individual (María García)

Un ex-alumno desea donar herramientas agrícolas:

```
DATOS DEL DONANTE:

Información General:
├── Nombre: José Antonio Martínez Hernández
├── Tipo de Donante: Persona Individual [Gris]
├── DUI: 00123456-7
├── Email: jamartinez@gmail.com
└── Teléfono: 7123-4567

Dirección:
├── Dirección: Residencial Las Colinas, Pje. 3, #15
├── Ciudad: San Miguel
├── Departamento: San Miguel
└── País: El Salvador

Calificación: ★★★★★ (5/5)

Notas:
"Ex-alumno promoción 1998.
Empresario agrícola exitoso.
Interés en apoyar la formación de nuevas generaciones."

Estado: Activo ✓
```

### Lista de Donantes de la ENA por Tipo

```
DONANTES ACTIVOS POR TIPO:

ORGANIZACIONES INTERNACIONALES [Morado]:
┌─────────────────────────────┬──────────────────────┬──────────┐
│ Nombre                      │ Contacto             │ Rating   │
├─────────────────────────────┼──────────────────────┼──────────┤
│ USAID El Salvador           │ Jennifer Thompson    │ ★★★★★    │
│ FAO El Salvador             │ Dr. Roberto Flores   │ ★★★★★    │
│ Programa Mundial Alimentos  │ Ana María Santos     │ ★★★★★    │
└─────────────────────────────┴──────────────────────┴──────────┘

GOBIERNO [Amarillo]:
┌─────────────────────────────┬──────────────────────┬──────────┐
│ Nombre                      │ Contacto             │ Rating   │
├─────────────────────────────┼──────────────────────┼──────────┤
│ MAG - Ministerio Agricultura│ Ing. Roberto Méndez  │ ★★★★★    │
│ CENTA                       │ Ing. Luis Guardado   │ ★★★★☆    │
│ Ministerio de Educación     │ Lic. Carmen Portillo │ ★★★★☆    │
└─────────────────────────────┴──────────────────────┴──────────┘

ONGs [Verde]:
┌─────────────────────────────┬──────────────────────┬──────────┐
│ Nombre                      │ Contacto             │ Rating   │
├─────────────────────────────┼──────────────────────┼──────────┤
│ FUNDASAL                    │ Lic. María E. Castro │ ★★★★☆    │
│ FUNDE                       │ Lic. Oscar Pérez     │ ★★★★☆    │
│ PRISMA                      │ Dr. Herman Rosa      │ ★★★★★    │
└─────────────────────────────┴──────────────────────┴──────────┘

ORGANIZACIONES PRIVADAS [Azul]:
┌─────────────────────────────┬──────────────────────┬──────────┐
│ Nombre                      │ Contacto             │ Rating   │
├─────────────────────────────┼──────────────────────┼──────────┤
│ Agroindustrias del Pacífico │ Ing. Carlos Mendoza  │ ★★★★☆    │
│ Banco Agrícola S.A.         │ Lic. José Portillo   │ ★★★★★    │
│ Ingenio El Ángel            │ Ing. Pedro Castillo  │ ★★★★☆    │
└─────────────────────────────┴──────────────────────┴──────────┘

PERSONAS INDIVIDUALES [Gris]:
┌─────────────────────────────┬──────────────────────┬──────────┐
│ Nombre                      │ Contacto             │ Rating   │
├─────────────────────────────┼──────────────────────┼──────────┤
│ José Antonio Martínez       │ 7123-4567            │ ★★★★★    │
│ María del Carmen Vega       │ 7234-5678            │ ★★★★☆    │
└─────────────────────────────┴──────────────────────┴──────────┘
```

### Recepción de Donación

Cuando llega una donación de USAID:

```
NUEVA DONACIÓN:

1. Seleccionar Bodega: Bodega Central - ENA
2. Seleccionar Donante: USAID El Salvador [Org. Internacional]
3. Tipo de Documento: Acta de Donación
4. Número: DON-USAID-2024-015

Productos Donados:
┌─────────────────────────┬──────────┬──────────────┬─────────────┐
│ Producto                │ Cantidad │ Valor Unit.  │ Valor Total │
├─────────────────────────┼──────────┼──────────────┼─────────────┤
│ Kit de Riego por Goteo  │ 50 kits  │ $150.00      │ $7,500.00   │
│ Bomba de Agua Solar     │ 10 unid. │ $500.00      │ $5,000.00   │
│ Tanques de 1000 Lt      │ 20 unid. │ $200.00      │ $4,000.00   │
└─────────────────────────┴──────────┴──────────────┴─────────────┘
                                          TOTAL:      $16,500.00

Proyecto: "Fortalecimiento de Sistemas de Riego - ENA 2024"
Convenio: CONV-USAID-ENA-2024-003
```

### Reportes por Donante

```
REPORTE ANUAL DE DONANTE: USAID El Salvador

Período: Enero - Noviembre 2024

Resumen de Donaciones:
├── Total de donaciones: 5
├── Valor total estimado: $85,000.00
├── Donaciones recibidas: 4
├── Donaciones pendientes: 1
└── Promedio por donación: $17,000.00

Categorías de Productos Donados:
┌───────────────────────────┬──────────────┐
│ Categoría                 │ Valor        │
├───────────────────────────┼──────────────┤
│ Equipos de Riego          │ $45,000.00   │
│ Maquinaria Agrícola       │ $25,000.00   │
│ Insumos y Semillas        │ $15,000.00   │
└───────────────────────────┴──────────────┘

Convenios Activos:
├── CONV-USAID-ENA-2024-001: Fortalecimiento Institucional
├── CONV-USAID-ENA-2024-002: Laboratorio de Suelos
└── CONV-USAID-ENA-2024-003: Sistemas de Riego
```

### Certificado de Donación

El sistema permite generar certificados para los donantes:

```
─────────────────────────────────────────────────────────────────
                    CERTIFICADO DE DONACIÓN
─────────────────────────────────────────────────────────────────

La Escuela Nacional de Agricultura "Roberto Quiñónez"

                        CERTIFICA:

Que ha recibido de: USAID El Salvador

La donación de los siguientes bienes:
- 50 Kits de Riego por Goteo .............. $7,500.00
- 10 Bombas de Agua Solar ................. $5,000.00
- 20 Tanques de Almacenamiento 1000 Lt .... $4,000.00

Valor Total de la Donación: $16,500.00

Fecha de Recepción: 15 de Noviembre de 2024
Referencia: DON-USAID-2024-015
Convenio: CONV-USAID-ENA-2024-003

Los bienes donados serán utilizados para el proyecto
"Fortalecimiento de Sistemas de Riego - ENA 2024"

─────────────────────────────────────────────────────────────────
[Firma Director ENA]           [Sello Institucional]
Pedro Ramírez
Director Ejecutivo
─────────────────────────────────────────────────────────────────
```

### Trazabilidad y Rendición de Cuentas

El sistema mantiene trazabilidad completa para:

- Auditorías de cooperantes internacionales
- Informes al Ministerio de Hacienda
- Rendición de cuentas a donantes
- Cumplimiento de convenios
- Control interno de bienes donados

```
TRAZABILIDAD DE DONACIÓN:

Donación: DON-USAID-2024-015
├── Donante: USAID El Salvador
├── Fecha Recepción: 15/11/2024
├── Recibido por: María García
├── Bodega: Bodega Central - ENA
└── Estado: Recibida

Movimientos Generados:
├── MOV-2024-1150: Entrada Kit Riego x50
├── MOV-2024-1151: Entrada Bomba Solar x10
└── MOV-2024-1152: Entrada Tanque 1000Lt x20

Ubicación Actual:
├── Kit Riego: Almacén B - Estante 3
├── Bomba Solar: Almacén C - Zona Equipos
└── Tanques: Patio - Área de Riego
```

---

*Última actualización: Noviembre 2024*
