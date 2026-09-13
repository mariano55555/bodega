# Oferta — Resumen Ejecutivo

## Módulo de Gestión de Activo Fijo integrado al Sistema de Gestión y Control de Bodegas

**Cliente:** Escuela Nacional de Agricultura "Roberto Quiñónez" (ENA)
**Oferente:** Jorge Mariano Paz Flores — Global Development Solutions
**Fecha:** 26 de agosto de 2026 · **Vigencia:** 45 días calendario
**Referencia:** Sistema de Inventario de Bodegas adjudicado mediante RES.ENA/ADJ/117/2025 (proceso CP-20250024)

> Versión resumida. El detalle técnico, el análisis de complejidad y el desglose de esfuerzo están
> en la oferta completa: `cotizacion-modulo-activo-fijo.md`.

---

## 1. La decisión que hay que tomar

La ENA necesita un **Sistema de Gestión de Activo Fijo**. Puede adquirirlo de dos formas:

- **Opción A** — como **módulo del sistema de bodegas que ya está en operación**: una sola
  aplicación, una sola base de datos, un solo servidor y **cuentas de usuario únicas**.
- **Opción B** — como **sistema independiente**, con su propia base de datos, su propio despliegue y
  su propio registro de usuarios.

La institución planteó desde el inicio un requisito concreto: **evitar la duplicidad de cuentas de
usuario**, porque un mismo empleado —jefaturas, Unidad de Activo Fijo, auditoría, encargados de
bodega— necesitará acceder a ambos ámbitos.

Este documento explica por qué la Opción A no sólo resuelve ese requisito, sino que resulta
**técnicamente superior y más barata a lo largo del tiempo**.

---

## 2. Por qué conviene integrarlo como módulo

### 2.1 Una sola cuenta, una sola baja

Con dos sistemas, cada empleado necesita **dos cuentas, dos contraseñas y dos procesos de alta y
baja**. El riesgo no es administrativo, es **de control interno**: cuando un empleado cesa
funciones, hay que recordar desactivarlo en dos lugares. **Un solo olvido deja un acceso vivo a
información patrimonial** — y es exactamente el tipo de hallazgo que levanta una auditoría.

Integrado, existe un solo registro de usuario, un solo inicio de sesión y una sola baja.

### 2.2 Una sola matriz de permisos

El sistema de bodegas ya opera un esquema granular de roles y permisos. El módulo de activo fijo
**extiende ese mismo esquema**: la Unidad de Informática administra **una sola matriz de accesos**,
y un mismo rol puede combinar atribuciones de bodega y de activo fijo.

Con dos sistemas, la misma persona se configura dos veces, con dos criterios y dos auditorías.

### 2.3 Integridad garantizada por la base de datos, no por un proceso que puede fallar

Éste es el argumento de mayor peso técnico. Con **una sola base de datos**, la relación entre un bien
recibido en bodega y su ficha de activo fijo es una **llave foránea garantizada por el motor de base
de datos**: es imposible que exista un activo apuntando a una recepción inexistente.

Con dos bases separadas esa garantía **desaparece** y se sustituye por un proceso de sincronización
—archivo periódico o llamadas entre sistemas— que introduce desfases, fallos parciales, registros
huérfanos y duplicados. Y esa interfaz de sincronización **es software adicional que hay que
construir, probar, mantener y actualizar**.

### 2.4 Los catálogos hoy están literalmente duplicados

El análisis comparado de ambos esquemas encontró duplicaciones reales:

| Concepto | Sistema de Bodegas | Sistema de Activo Fijo |
|---|---|---|
| Proveedores | `suppliers` | `suppliers` *(mismo nombre, otro esquema)* |
| Fuentes de financiamiento | `fund_sources` | `funding_sources` |
| Unidades organizativas | `areas` | `departments` |
| Personas responsables | `employees` | `custodians` |
| Ubicaciones físicas | `warehouses`, `storage_locations` | `locations` |
| Bitácora de auditoría | `activity_log` | `activity_log` |

Dos sistemas obligan a **dar de alta cada proveedor dos veces**, con dos NIT posiblemente mal
digitados, y a que un reporte de "compras al proveedor X" nunca cuadre con "activos adquiridos al
proveedor X". La integración unifica estos catálogos **una sola vez**.

### 2.5 Trazabilidad continua del ciclo de vida del bien

La especificación de la ENA exige conservar el **historial completo de cada bien**. Integrado, ese
historial es continuo y consultable en una sola pantalla:

```
Compra / Donación / Convenio → Recepción en bodega → Clasificación como activo fijo
  → Asignación a custodio → Traslados y préstamos → Depreciación → Descargo
```

Con sistemas separados, **la cadena se corta en el punto de recepción** y hay que reconstruirla a
mano durante las auditorías.

### 2.6 El día que la ENA migre a hosting en línea, se mueve un sistema y no dos

Si la institución decide más adelante llevar sus sistemas a un servidor en la nube, la diferencia es
grande:

| | Un solo sistema | Dos sistemas |
|---|---|---|
| Despliegues a migrar | 1 | 2 |
| Bases de datos a trasladar | 1 | 2 |
| Certificados y dominios a reconfigurar | 1 | 2 |
| Ventanas de indisponibilidad | 1 | 2, y deben coordinarse |
| Interfaz de sincronización | No existe | **Hay que mantenerla funcionando durante y después de la migración** |
| Pruebas de verificación posteriores | Un solo juego | Dos, más las pruebas de la interfaz entre ambos |

Migrar dos sistemas conectados entre sí no cuesta el doble: **cuesta más del doble**, porque además
de mover cada uno hay que garantizar que sigan comunicándose correctamente en el nuevo entorno.

### 2.7 Un solo ciclo de actualización

Cada versión del framework y cada parche de seguridad debe aplicarse, **probarse y validarse**. Con
dos sistemas ese trabajo se hace dos veces, y con el tiempo los sistemas **divergen** —uno queda en
una versión, el otro en otra— y la interfaz que los comunica empieza a romperse.

### 2.8 Respaldos y continuidad

Un solo sistema significa **un servidor, una base de datos, un esquema de respaldo, un certificado y
un procedimiento de restauración**. Con dos, al restaurar un respaldo **ambas bases deben quedar en
el mismo punto en el tiempo**; si no, la información queda inconsistente entre bodega y activo fijo,
y la inconsistencia puede pasar inadvertida durante meses.

### 2.9 Una plataforma que crece, no sistemas sueltos

Integrado el activo fijo, la ENA no tiene "dos sistemas": tiene **una plataforma institucional con
módulos**. Cualquier necesidad futura —combustible, mantenimiento vehicular, requisiciones, gestión
documental— se incorpora reutilizando de inmediato los usuarios, los permisos, los catálogos, la
bitácora, el motor de reportes y la infraestructura. **Cada módulo nuevo cuesta menos que el
anterior.**

---

## 3. Comparación y recomendación

| | **Opción A — Módulo integrado** ⭐ | **Opción B — Sistema independiente** |
|---|---|---|
| Cuentas de usuario | **Únicas**, con permisos por módulo | Duplicadas o sincronizadas |
| Base de datos | Una sola, integridad garantizada | Dos, conciliadas manualmente |
| Trazabilidad compra → bodega → activo | **Continua** | Se corta en la recepción |
| Catálogos compartidos | **Sí** | Doble captura |
| Migración futura a la nube | **Un solo traslado** | Dos traslados coordinados + la interfaz |
| Actualizaciones y respaldos | Un solo ciclo | Duplicados, con riesgo de divergencia |
| Módulos futuros | Se suman a la misma plataforma | Cada uno nace aislado |
| **Inversión inicial** | **US$ 9,450.00** | US$ 13,680.00 |
| **Costo total a 3 años** | **US$ 14,964.00** | US$ 21,008.00 |

### Recomendación: **Opción A**

Resuelve el requisito que la propia institución planteó, elimina la doble captura de catálogos que
hoy ya existe, mantiene la trazabilidad del bien sin cortes, simplifica cualquier traslado futuro a
la nube y **cuesta US$ 6,044 menos a tres años (40%)**.

---

## 4. Precio

### Inversión inicial — Opción A

| # | Concepto | Monto (US$) |
|---|---|---:|
| 1 | Licencia de uso del Módulo de Activo Fijo (perpetua, institucional, usuarios ilimitados) | 4,600.00 |
| 2 | Adecuación al marco normativo salvadoreño (depreciación SAFI, cuenta contable, mantenimientos, revaluaciones, descargos) | 1,450.00 |
| 3 | Módulo de Cumplimiento y Auditoría (conciliación contable, constatación física, descargo autorizado, intangibles, extravíos) | 2,050.00 |
| 4 | Servicios de integración al sistema de bodegas (plataforma, base de datos, identidad y permisos únicos) | 900.00 |
| 5 | Migración de datos históricos, capacitación y manuales en español | 450.00 |
| 6 | Soporte y mantenimiento — 12 meses | **Incluido** |
| | **TOTAL** | **US$ 9,450.00** |

> Dólares de los Estados Unidos de América. Los impuestos aplicables se detallarán conforme al
> régimen tributario del oferente al momento de la facturación.

**Plazo:** 26 semanas calendario a partir de la orden de inicio.

**Forma de pago**

| Hito | % | Monto (US$) |
|---|---:|---:|
| Orden de inicio | 30% | 2,835.00 |
| Plataforma homologada, esquema unificado y dominio migrado | 25% | 2,362.50 |
| Entrega para pruebas de aceptación | 30% | 2,835.00 |
| Recepción definitiva, capacitación y puesta en producción | 15% | 1,417.50 |

### Cuota anual a partir del segundo año

> **Transparencia sobre el costo recurrente.** Al incorporarse un segundo módulo, **la cuota anual
> de soporte de la plataforma aumenta**. Es lo esperable: se está manteniendo el doble de
> funcionalidad. Lo importante es que ese incremento **es equivalente —de hecho, menor— a lo que
> costaría mantener dos sistemas por separado**, y la ENA lo paga sobre una sola infraestructura,
> un solo respaldo y un solo ciclo de actualización.

Calculada como el 15% del valor de las licencias vigentes:

| Escenario | Cuota anual de soporte |
|---|---:|
| Hoy — sólo el módulo de Bodegas | US$ 1,339.00 |
| **Opción A — plataforma con ambos módulos** | **US$ 2,757.00** |
| Opción B — dos sistemas mantenidos por separado | US$ 3,124.00 |

### Costo total de propiedad a 3 años

| Concepto | **Opción A** | Opción B |
|---|---:|---:|
| Inversión inicial | 9,450.00 | 13,680.00 |
| Soporte años 2 y 3 | 5,514.00 | 6,248.00 |
| Infraestructura adicional (servidor y base de datos separados) | 0.00 | 1,080.00 |
| **Total 3 años** | **US$ 14,964.00** | **US$ 21,008.00** |
| **Diferencia** | — | **+ US$ 6,044.00 (40% más)** |

---

## 5. Qué recibe la ENA

**Módulo entregado:** 36 tablas · 33 modelos de datos · 34 pantallas · 28 reportes y documentos.

| Bloque | Contenido |
|---|---|
| **Bienes materiales** | Código único no reutilizable, marca, modelo, serie, categoría, estado, ubicación, centro de costo, unidad organizativa, custodio, proveedor y fuente de financiamiento. Clasificación automática mayor/menor con el umbral de US$900. Adjuntos digitales. Etiquetas imprimibles |
| **Movimientos** | Adquisición, asignación, traslado, préstamo, devolución y descargo, con cambio automático de estado e historial completo por bien |
| **Depreciación y amortización** | Motor **multi-régimen**: gubernamental (Manual Técnico del SAFI), contable (NIIF) y fiscal (Art. 30 LISR). Valor en libros, revaluaciones, deterioro y amortización de intangibles |
| **Cumplimiento y auditoría** | Conciliación contable (informes semestrales y acta anual firmada), constatación física anual total, descargo con autorización del titular, expediente de robo/hurto/extravío, resguardo documental de 5 años |
| **Bienes animales / biológicos** | Tipos, unidades productivas, lotes, movimientos y reportes mensuales con arrastre de saldos |
| **Reportes** | Inventario general, depreciación por período, movimientos, activos por responsable, bienes animales, informes financieros por clase de bien y reporte anual consolidado — **todos exportables a PDF y Excel** |
| **Documentos** | Actas de adquisición y asignación, formatos de traslado, préstamo y descargo, acta de conciliación, etiquetas |
| **Seguridad** | Cuentas únicas para toda la plataforma, roles y permisos granulares por módulo, bitácora completa (usuario, fecha, hora y acción) |
| **Servicios** | Migración de datos históricos, despliegue, capacitación y manuales de usuario y técnico en español |

---

## 6. No incluido

- Infraestructura de servidor, hospedaje, dominio y certificados.
- **Interfaz técnica con el sistema SAFI del Ministerio de Hacienda** u otros sistemas contables de
  terceros. El módulo aplica los **criterios** de depreciación y el catálogo de cuentas de
  Contabilidad Gubernamental y genera los informes para la conciliación; no se conecta
  electrónicamente con esos sistemas.
- **Ejecución material del conteo físico en sitio.** El sistema soporta y documenta la constatación
  física; el levantamiento lo realiza personal de la ENA.
- Digitalización o depuración manual de archivos físicos.
- Desarrollos personalizados no descritos en la sección 5 y las fases futuras de la sección 9.

---

## 7. Soporte, garantía y condiciones

| Concepto | Condición |
|---|---|
| **Garantía** | 12 meses desde la recepción definitiva. Corrección **sin costo** de todo defecto atribuible al desarrollo |
| **Soporte incluido** | 12 meses: atención por correo y canal remoto en horario hábil, actualizaciones correctivas, respaldo de base de datos y acompañamiento en cierres de período |
| **Tiempos de respuesta** | Crítica (sistema fuera de servicio): **4 horas hábiles** · Mayor: **1 día hábil** · Consulta o mejora menor: **3 días hábiles** |
| **Renovación de soporte** | Opcional a partir del segundo año: 15% del valor de las licencias vigentes (ver sección 4) |
| **Propiedad de la información** | **Exclusiva de la ENA.** A solicitud se entrega respaldo completo de la base de datos en formato estándar |
| **Capacitación** | Dos jornadas: una para usuarios operativos y otra para la Unidad de Informática (administración, respaldos y gestión de usuarios) |
| **Adecuaciones futuras** | El sistema admite desarrollos personalizados para funciones no incluidas, cotizables por separado |
| **Acceso** | Sistema web por navegador, con interfaz y manuales **íntegramente en español** |

---

## 8. Supuestos

1. El sistema de bodegas continúa en operación y el oferente mantiene acceso a su código fuente y a
   su entorno de despliegue.
2. La ENA provee un **entorno de pruebas** equivalente al de producción para validar la homologación
   de plataforma antes del despliegue.
3. La ENA designa una **contraparte técnica** y una **contraparte funcional** (Unidad de Activo
   Fijo) para la definición de criterios y las pruebas de aceptación.
4. La **Unidad Financiera Institucional** confirma, en la primera semana, el régimen contable
   aplicable y la tabla de vida útil vigente. El motor es multi-régimen y admite cualquiera de los
   tres marcos, por lo que esta confirmación **no afecta plazo ni precio**.
5. Los datos históricos se entregan en formato digital con estructura consistente. Su depuración es
   responsabilidad de la ENA, con acompañamiento del oferente.
6. Se acuerda una **ventana de despliegue en horario no laboral** para el paso a producción.

---

## 9. Fases futuras (no incluidas, precio indicativo)

| Fase | Contenido | Precio indicativo |
|---|---|---:|
| **N3 — Gestión patrimonial completa** | Bienes inmuebles (escritura, inscripción registral, avalúos y mejoras), **proyectos de inversión con liquidación contable**, comodatos, planes de mantenimiento preventivo y garantías, seguros y pólizas, datos de vehículos, bodega de bienes en desuso | US$ 4,100.00 |
| **N4 — Movilidad y transparencia** | App móvil de constatación física con escaneo QR **sin conexión**, portal del custodio con aceptación en línea del acta de responsabilidad, alertas de vencimientos, firma electrónica en actas | US$ 2,050.00 |

> El módulo de **proyectos de inversión** es de interés inmediato, dado que la ENA administra bienes
> bajo el Proyecto de Inversión 6882.

---

## 10. Por qué este oferente

- **Ya conoce el sistema y la institución.** El sistema de bodegas en operación fue desarrollado y
  entregado por este mismo oferente; no hay curva de aprendizaje ni riesgo de integración con un
  tercero.
- **Base funcional ya construida y probada.** El dominio de activo fijo no parte de cero, lo que
  reduce plazo y riesgo.
- **Especialización en normativa salvadoreña.** Régimen SAFI, catálogo de Contabilidad
  Gubernamental, conciliación contable y constatación física — requisitos que un producto genérico
  no contempla y que son justamente los que revisa la auditoría.
- **Un solo interlocutor** para toda la plataforma institucional.

---

## 11. Próximos pasos

1. Confirmación del alcance y la modalidad (Opción A u Opción B) por parte de la ENA.
2. Reunión técnica con la Unidad Financiera Institucional y la Unidad de Activo Fijo para validar el
   régimen contable y los criterios de clasificación.
3. Emisión de la orden de inicio.

---

**Jorge Mariano Paz Flores**
Global Development Solutions
