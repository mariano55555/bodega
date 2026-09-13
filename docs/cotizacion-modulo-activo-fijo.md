# Oferta Técnica y Económica

## Módulo de Gestión de Activo Fijo integrado al Sistema de Gestión y Control de Bodegas

**Cliente:** Escuela Nacional de Agricultura "Roberto Quiñónez" (ENA)
**Oferente:** Jorge Mariano Paz Flores — Global Development Solutions
**Fecha:** 26 de agosto de 2026
**Referencia:** Sistema de Inventario de Bodegas adjudicado mediante RES.ENA/ADJ/117/2025 (proceso CP-20250024)
**Vigencia de la oferta:** 45 días calendario

---

## 1. Resumen ejecutivo

La ENA cuenta hoy con el **Sistema de Gestión y Control de Bodegas** en operación. Se requiere ahora un
**Sistema de Gestión de Activo Fijo**, y surge un requisito explícito de la institución: **evitar la
duplicidad de cuentas de usuario**, ya que un mismo empleado (jefaturas, Unidad de Activo Fijo, auditoría,
encargados de bodega) necesitará acceder a **ambos ámbitos**.

Existen dos caminos para satisfacerlo:

| | **Opción A — Módulo integrado** *(recomendada)* | **Opción B — Sistema independiente** |
|---|---|---|
| Arquitectura | Un solo sistema, una sola base de datos, un solo servidor | Dos sistemas, dos bases de datos, dos despliegues |
| Cuentas de usuario | **Únicas**, con permisos por módulo | Duplicadas, o sincronizadas mediante una interfaz adicional |
| Trazabilidad compra → bodega → activo | **Nativa** (integridad referencial en base de datos) | Por archivo o API; sujeta a desfases y errores de conciliación |
| Actualizaciones del framework | **Un solo ciclo** | Dos ciclos independientes, con riesgo de divergencia |
| Costo recurrente | **Una sola cuota de soporte e infraestructura** | Dos contratos de soporte + infraestructura duplicada |
| Migración futura a la nube | **Un solo traslado** | Dos traslados coordinados, más la interfaz entre ambos |
| Crecimiento futuro (nuevos módulos) | Se incorporan al mismo sistema | Cada módulo nace aislado |

Esta oferta desarrolla la **Opción A** y la cotiza; al final se incluye la comparación económica con la
Opción B para que la institución disponga de ambos escenarios.

A lo anterior se suma una segunda dimensión, tan importante como la arquitectura: el módulo se
entrega **adecuado al marco normativo salvadoreño del sector público** — régimen de depreciación del
Manual Técnico del SAFI, cuenta contable de Contabilidad Gubernamental, conciliación con
contabilidad, constatación física anual y descargo con flujo de autorización. Es la diferencia entre
un sistema que **registra** bienes y uno que **resiste una auditoría de la Corte de Cuentas**
(sección 4.8).

---

## 2. Situación actual (diagnóstico técnico)

Se realizó un análisis del código de ambas plataformas. Estos son los datos objetivos:

### 2.1 Sistema de Bodegas (en producción — ENA)

| Indicador | Valor |
|---|---|
| Modelos de datos | 47 |
| Tablas / migraciones | 111 |
| Pantallas interactivas | 128 |
| Volumen de código | ≈ 110,000 líneas |
| Módulos funcionales | Productos, Inventario, Compras, Despachos, Traslados, Ajustes, Cierres mensuales, Donaciones, Producción interna, Trazabilidad, Kardex, Reportes, Importaciones DTE, Alertas, Auditoría |
| Control de accesos | Roles y permisos granulares (108 permisos), jerarquía de roles, acceso por bodega |

### 2.2 Módulo de Activo Fijo a entregar

El oferente cuenta con una **base funcional ya desarrollada y probada** del dominio de activo fijo,
que se completa dentro de esta oferta con la **adecuación al marco normativo salvadoreño** y el
**bloque de cumplimiento y auditoría** descritos en la sección 4. Estas son las magnitudes del
módulo tal como se entregará:

| Indicador | Base ya construida | Desarrollo incluido en esta oferta | **Módulo entregado** |
|---|---:|---:|---:|
| Modelos de datos | 21 | +12 | **33** |
| Tablas | 24 | +12 | **36** |
| Pantallas interactivas | 19 | +15 | **34** |
| Servicios de dominio | 5 | +4 | **9** |
| Reportes y documentos | 13 | +15 | **28** |
| Volumen de código | ≈ 11,900 líneas | ≈ +9,400 | **≈ 21,300 líneas** |

**Base funcional ya construida y probada** (22 archivos de pruebas automatizadas): bienes
materiales, catálogos de mantenimiento, movimientos (adquisición, asignación, traslado, préstamo,
devolución, descargo), motor de depreciación, revaluaciones, adjuntos digitales, **módulo de bienes
animales/biológicos** (tipos, unidades productivas, lotes, movimientos y reportes mensuales),
umbral de US$900 para clasificación mayor/menor, código único de inventario no reutilizable,
etiquetas, actas y formatos en PDF, importación masiva desde Excel y bitácora de auditoría.

**Desarrollo incluido en esta oferta** — lo que convierte el módulo en un sistema **auditable** bajo
la normativa salvadoreña:

- **Depreciación multi-régimen**: régimen gubernamental conforme al Manual Técnico del SAFI
  (método lineal, valor residual del 10%, tabla oficial de vida útil), con los regímenes NIIF y
  fiscal disponibles de forma independiente.
- **Cuenta contable por categoría de bien**, alineada al catálogo de Contabilidad Gubernamental.
- **Conciliación con contabilidad**: informe financiero semestral por clase de bien y **acta de
  conciliación anual** firmada.
- **Constatación física de bienes**: programación, levantamiento total por dependencia, reporte
  para firma de la jefatura, control del plazo de observaciones y gestión de faltantes.
- **Descargo con flujo de autorización**: solicitud, justificación técnica, memorándum de remisión
  y autorización del titular, con registro de valor en libros y ganancia o pérdida en la baja.
- **Activos intangibles y amortización**: software, licencias perpetuas y creaciones
  institucionales.
- **Expediente de robo, hurto o extravío**, con descargo provisional y descargo definitivo.
- **Mantenimientos y revaluaciones** con pantallas propias de gestión.
- **Política de resguardo documental** de cinco años y autorización verificada en el servidor sobre
  todas las operaciones de escritura.

---

## 3. Justificación técnica: por qué integrar como módulo y no operar dos sistemas

### 3.1 Identidad única de usuario — resuelve el requisito de origen

Con dos sistemas, cada empleado necesita **dos cuentas, dos contraseñas y dos procesos de alta y baja**.
El riesgo no es sólo administrativo, es **de control interno**: cuando un empleado cesa funciones, debe
recordarse desactivarlo en dos lugares. Un solo olvido deja un acceso vivo a información patrimonial.

En la opción integrada existe **un solo registro de usuario**, un solo inicio de sesión y una sola baja.
La asignación de módulos se resuelve por **permisos**, no por cuentas separadas.

### 3.2 Un solo modelo de permisos, no dos árboles paralelos

El sistema de bodegas ya opera un esquema granular de roles y permisos con jerarquía. Integrar el activo
fijo como módulo significa **extender ese mismo árbol** con los permisos del nuevo dominio (consulta de
activos, registro, ejecución de depreciación, exportación de reportes, etc.). La Unidad de Informática
administra **una sola matriz de accesos**, y un mismo rol puede combinar atribuciones de bodega y de
activo fijo sin duplicar configuración.

Con dos sistemas, la misma persona se configura dos veces, con dos criterios y dos auditorías distintas.

### 3.3 Integridad referencial real, no sincronización

Éste es el argumento de mayor peso técnico. Con **una sola base de datos**, la relación entre un bien
recibido en bodega y su ficha de activo fijo es una **llave foránea garantizada por el motor de base de
datos**: es imposible que exista un activo apuntando a una recepción inexistente, o que se borre una
compra que sustenta un activo capitalizado.

Con dos bases de datos separadas, esa garantía **desaparece**. Se sustituye por un proceso de
sincronización (archivo Excel periódico o llamadas entre sistemas) que introduce:

- **Desfase temporal** — el activo fijo trabaja con una foto desactualizada de bodega.
- **Fallos parciales** — una sincronización interrumpida deja los sistemas en estados inconsistentes.
- **Registros huérfanos y duplicados** — que sólo se detectan al conciliar manualmente.
- **Un componente adicional que mantener** — la propia interfaz de sincronización es software que se
  desarrolla, se prueba, falla y se actualiza.

### 3.4 Catálogos compartidos: hoy están literalmente duplicados

El análisis comparado de esquemas encontró **colisiones y duplicaciones reales** entre ambos sistemas:

| Concepto | En Bodegas | En Activo Fijo | Situación |
|---|---|---|---|
| Proveedores | `suppliers` | `suppliers` | **Mismo nombre, dos esquemas distintos** |
| Fuentes de financiamiento | `fund_sources` | `funding_sources` | **Mismo concepto, dos tablas** |
| Unidades organizativas | `areas` | `departments` | Mismo concepto, dos catálogos |
| Personas responsables | `employees` | `custodians` | Solapamiento parcial |
| Ubicaciones físicas | `warehouses`, `storage_locations` | `locations` | Jerarquías paralelas |
| Bitácora de auditoría | `activity_log` | `activity_log` | Dos bitácoras separadas |

Mantener dos sistemas obliga a **dar de alta cada proveedor dos veces**, con dos NIT posiblemente
mal digitados, y a que un reporte de "compras a proveedor X" nunca cuadre con "activos adquiridos al
proveedor X". La integración **unifica estos catálogos una sola vez** y elimina de raíz la doble captura.

### 3.5 Trazabilidad completa del ciclo de vida del bien

La Especificación Técnica de la ENA exige conservar el **historial completo de cada bien**. Integrado,
ese historial es continuo y consultable en una sola pantalla:

```
Compra / Donación / Convenio  →  Recepción en bodega  →  Clasificación como activo fijo
   →  Asignación a custodio  →  Traslados y préstamos  →  Depreciación mensual  →  Descargo
```

Cada eslabón vive en la misma base de datos, con la misma bitácora y el mismo sello de usuario, fecha
y hora. Con sistemas separados, la cadena **se corta en el punto de recepción** y se reconstruye
manualmente durante las auditorías.

### 3.6 Un solo ciclo de actualización y mantenimiento

Ambos sistemas se construyen sobre el mismo marco de trabajo (Laravel/PHP). Cada versión mayor del
framework, cada parche de seguridad de PHP y cada actualización de las librerías de terceros debe
aplicarse, **probarse y validarse**. Con dos sistemas ese trabajo **se hace dos veces**, y a partir del
segundo año aparece un problema adicional: los sistemas **divergen** — uno queda en una versión, el otro
en otra — y la interfaz que los comunica empieza a romperse.

Hoy mismo esa divergencia ya es medible: el sistema de bodegas corre sobre PHP 8.2 / Laravel 12, y el de
activo fijo sobre PHP 8.3 / Laravel 13. **La integración cierra esa brecha de manera definitiva**, en un
solo esfuerzo, y deja a la ENA con una única línea base tecnológica que actualizar en adelante.

### 3.7 Rendimiento y consultas transversales

Un reporte que cruce información de ambos ámbitos — por ejemplo *"bienes adquiridos con fondos GOES en
2026, recibidos en bodega y ya capitalizados como activo fijo, por unidad organizativa"* — en un sistema
integrado es **una sola consulta a la base de datos**, resuelta en milisegundos con los índices existentes.

En sistemas separados, ese mismo reporte requiere extraer datos de un sistema, transportarlos al otro y
cruzarlos en memoria: es más lento, consume más recursos y **es frágil ante cualquier cambio de esquema**.

### 3.8 Infraestructura, respaldos y continuidad

Un solo sistema implica: **un servidor**, **una base de datos**, **un esquema de respaldo**, **un
certificado SSL**, **un plan de recuperación ante desastres** y **un procedimiento de restauración**.

Con dos sistemas, además de duplicar todo lo anterior, aparece un riesgo específico: al restaurar un
respaldo, **ambas bases deben restaurarse al mismo punto en el tiempo**. Si se restauran a momentos
distintos, la información queda inconsistente entre bodega y activo fijo, y la inconsistencia puede
pasar inadvertida durante meses.

### 3.9 El día que la ENA migre a hosting en línea, se mueve un sistema y no dos

Si la institución decide más adelante trasladar sus sistemas a un servidor en la nube —por
continuidad, por acceso remoto o por política institucional— la diferencia entre una y otra opción
es considerable:

| | Un solo sistema | Dos sistemas |
|---|---|---|
| Despliegues a migrar | 1 | 2 |
| Bases de datos a trasladar | 1 | 2 |
| Certificados y dominios a reconfigurar | 1 | 2 |
| Ventanas de indisponibilidad | 1 | 2, y deben coordinarse entre sí |
| Interfaz de sincronización | No existe | **Debe seguir funcionando durante y después de la migración** |
| Pruebas de verificación posteriores | Un solo juego | Dos, más las pruebas de la interfaz entre ambos |

Migrar dos sistemas conectados entre sí **no cuesta el doble: cuesta más del doble**, porque además
de mover cada uno hay que garantizar que sigan comunicándose correctamente en el nuevo entorno —y
que lo hagan con la misma latencia y las mismas credenciales. Es un costo diferido que la
institución asume hoy, sin verlo, al elegir dos sistemas separados.

### 3.10 Plataforma institucional, no una colección de sistemas sueltos

Una vez integrado el activo fijo, la ENA no tiene "dos sistemas": tiene **una plataforma institucional de
gestión** con módulos. Cualquier necesidad futura —control de combustible, mantenimiento vehicular,
gestión documental, requisiciones, control de proyectos— **se incorpora como un módulo más**,
reutilizando de inmediato:

- el registro de usuarios, roles y permisos ya existente;
- los catálogos de proveedores, unidades organizativas y empleados;
- la bitácora de auditoría;
- el motor de reportes PDF/Excel;
- la infraestructura, los respaldos y el despliegue.

Es decir, **cada módulo nuevo cuesta menos que el anterior**. En el escenario de sistemas separados,
cada módulo nuevo vuelve a pagar todo ese andamiaje desde cero.

---

## 4. Análisis de complejidad

El trabajo de esta oferta tiene dos componentes. El primero es la **integración**: fusionar dos
aplicaciones construidas de forma independiente, lo que no es una simple copia de archivos
(secciones 4.1 a 4.7). El segundo es la **adecuación normativa**: completar el módulo para que
cumpla lo que la normativa salvadoreña exige de una unidad de activo fijo del sector público
(sección 4.8). A continuación se detalla el trabajo real identificado en el análisis de código y en
la revisión del marco normativo aplicable.

### 4.1 Homologación de plataforma

Los dos sistemas están en versiones distintas del marco de trabajo y del lenguaje:

| | Bodegas | Activo Fijo |
|---|---|---|
| PHP | 8.2 | 8.3 |
| Laravel | 12 | 13 |
| Capa de interfaz | Volt 1 | Componentes de página Livewire 4 |
| Autenticación | Implementación propia | Laravel Fortify (con 2FA y claves de acceso) |

**Complejidad: media-alta.** Requiere elevar el sistema de bodegas a la línea base superior y **verificar
que sus 128 pantallas y 111 tablas sigan operando sin regresiones**. Es trabajo que la ENA tendría que
pagar tarde o temprano de todos modos; aquí se ejecuta una sola vez y beneficia a ambos módulos.

### 4.2 Unificación del esquema de base de datos

- Incorporación de **24 tablas** del dominio de activo fijo al esquema existente.
- Resolución de **6 colisiones de catálogo** (sección 3.4): fusión de esquemas de proveedores, unificación
  de fuentes de financiamiento, reconciliación de unidades organizativas, custodios contra empleados,
  ubicaciones contra bodegas/ubicaciones de almacenamiento, y consolidación de la bitácora.
- Incorporación del **modelo multiempresa** del sistema de bodegas a las tablas del nuevo módulo, con sus
  índices y restricciones.
- Adopción de las convenciones del sistema receptor: campos de auditoría (creado, modificado y eliminado
  por), borrado lógico, estado y slug.

**Complejidad: alta.** Es la fase más delicada: un error aquí compromete la integridad de datos que ya
están en producción. Requiere migraciones reversibles y validación con datos reales.

### 4.3 Migración de la capa de dominio

- **21 modelos** (≈ 1,600 líneas) reescritos a las convenciones y relaciones del sistema receptor.
- **5 servicios de dominio** — incluida la **lógica de depreciación dual (contable NIIF + fiscal Art. 30
  LISR)** y el motor de saldos de bienes biológicos — que deben preservarse **sin alterar un solo
  resultado de cálculo**.
- Políticas de autorización, observadores de bitácora y reglas de validación.

**Complejidad: media.** El código es de buena calidad y está probado; el riesgo se controla con las
22 pruebas automatizadas existentes, que se migran junto con el código y sirven de red de seguridad.

### 4.4 Migración de la interfaz de usuario

- **19 pantallas interactivas** (≈ 5,200 líneas) convertidas de componentes de página Livewire 4 a la
  convención vigente del sistema de bodegas.
- **17 plantillas** de reportes, documentos y componentes visuales (≈ 1,030 líneas).
- Integración al menú lateral, la navegación y el sistema de diseño del sistema anfitrión, para que el
  usuario perciba **un solo producto** y no dos aplicaciones pegadas.

**Complejidad: media-alta por volumen.** Es la partida de mayor cantidad de horas: el trabajo es
mecánico pero extenso, y cada pantalla debe verificarse funcionalmente una por una.

### 4.5 Permisos, roles y auditoría unificados

- Incorporación de los permisos del módulo de activo fijo a la matriz existente de 108 permisos.
- Definición de los nuevos roles institucionales (Unidad de Activo Fijo, Custodio, Auditoría) y de los
  **roles mixtos** que acceden a ambos módulos.
- Fusión de las dos bitácoras en una sola línea de tiempo auditable.

**Complejidad: media.**

### 4.6 Integración funcional Bodega → Activo Fijo

El valor diferencial de la integración: al registrarse una **recepción de compra o donación** en bodega,
el sistema identifica los bienes que califican como activo fijo y permite **capitalizarlos con un clic**,
arrastrando proveedor, documento de respaldo, valor unitario y fuente de financiamiento, y generando el
código de inventario y su etiqueta. Sin re-digitación y con trazabilidad completa.

**Complejidad: media.** Requiere definir con la Unidad de Activo Fijo el criterio de clasificación y la
regla de multiplicidad (una recepción de N unidades genera N activos individuales).

### 4.7 Migración de datos históricos

Carga de los inventarios de activo fijo que hoy la institución mantiene en hojas de cálculo y archivos
físicos, mediante el importador Excel ya construido: plantilla descargable, validación fila por fila,
reconocimiento de catálogos por nombre o código y reporte de errores.

**Complejidad: media, dependiente de la calidad de los datos de origen.**

### 4.8 Adecuación al marco normativo salvadoreño

Un sistema de activo fijo para una institución del sector público no se agota en registrar bienes:
debe **resistir una auditoría de la Corte de Cuentas**. Esta oferta incorpora el trabajo necesario
para ello, agrupado en dos bloques.

**Bloque N1 — Régimen contable correcto (150 h).** La ENA, como institución descentralizada no
empresarial, lleva su contabilidad bajo el marco de la **Ley AFI y el Manual Técnico del SAFI**,
con la adopción de **NICSP** en curso para este tipo de instituciones desde 2023. Los parámetros de
depreciación del sector público difieren de los del sector privado:

| Parámetro | Sector privado (LISR / NIIF PYMES) | **Sector público (SAFI)** |
|---|---|---|
| Periodicidad | Mensual | **Anual** |
| Valor residual | Libre | **10% del costo de adquisición** |
| Edificaciones | 20 años | **40 años** (factor 0.025) |
| Maquinaria y equipo de transporte | 4 a 5 años | **10 años** (factor 0.10) |
| Mobiliario y equipo informático | 2 años | **5 años** (factor 0.20) |

Aplicar los parámetros equivocados produce una depreciación acumulada que **no concilia con los
estados financieros institucionales** — exactamente el punto que revisa la auditoría. El desarrollo
convierte el motor de depreciación en **multi-régimen** (gubernamental, NIIF y fiscal), incorpora la
**cuenta contable** del catálogo de Contabilidad Gubernamental a cada categoría de bien y completa
las pantallas de gestión de mantenimientos, revaluaciones y descargos.

**Complejidad: media-alta.** El refactor del motor de cálculo se valida con pruebas comparativas
antes y después, de modo que ningún resultado cambie por accidente.

**Bloque N2 — Cumplimiento y auditoría (230 h).** Procesos que la normativa exige y que hoy no
existen en ningún sistema comercial genérico:

- **Conciliación con contabilidad** — informe financiero semestral por clase de bien y acta de
  conciliación anual firmada por el contador y el jefe de activo fijo.
- **Constatación física** — obligatoria al menos una vez al año, **total y sin muestreo**, con
  reporte por dependencia, firma de la jefatura, plazo de diez días hábiles para observaciones y
  procedimiento para bienes no localizados.
- **Descargo con autorización** — el descargo es el retiro físico y registral del bien, y sólo
  procede tras un proceso autorizado por el titular, con su respaldo documental.
- **Activos intangibles y amortización** — software y licencias con vida útil mayor a un año.
- **Robo, hurto o extravío** — expediente con denuncia, descargo provisional y descargo definitivo
  al cerrar el proceso legal o al finiquitar la póliza de seguro.
- **Resguardo documental** — retención mínima de cinco años, con bloqueo de purga.

**Complejidad: media-alta.** Es el bloque de mayor valor diferencial de toda la oferta.

### 4.9 Resumen de esfuerzo

| # | Fase | Complejidad | Horas |
|---|---|---|---:|
| 0 | Homologación de plataforma (PHP/Laravel/Livewire) y regresión del sistema en producción | Media-alta | 40 |
| 1 | Unificación de esquema, catálogos y modelo multiempresa | **Alta** | 56 |
| 2 | Migración de modelos, servicios de dominio y políticas | Media | 48 |
| 3 | Migración de las pantallas y del sistema de diseño | Media-alta | 80 |
| 4 | Reportes, documentos y etiquetas | Media | 40 |
| 5 | Permisos, roles y bitácora unificada | Media | 24 |
| 6 | Integración funcional Recepción de Bodega → Capitalización de Activo | Media | 32 |
| 7 | Migración de datos históricos desde hojas de cálculo | Media | 24 |
| | *Subtotal integración* | | *344* |
| N1 | **Régimen contable correcto** (depreciación multi-régimen SAFI, cuenta contable, pantallas de mantenimiento, revaluación y descargo) | Media-alta | 150 |
| N2 | **Cumplimiento y auditoría** (conciliación contable, constatación física, descargo autorizado, intangibles, extravíos, retención documental) | Media-alta | 230 |
| | *Subtotal normativo* | | *380* |
| 8 | Pruebas automatizadas, control de calidad y pruebas de aceptación con la ENA | Media | 40 |
| 9 | Despliegue en producción, capacitación y manuales en español | Baja | 32 |
| | **Total** | | **796 h** |

### 4.10 Riesgos identificados y medidas de mitigación

| Riesgo | Impacto | Mitigación |
|---|---|---|
| Regresión en el sistema de bodegas en producción al elevar la plataforma | Alto | Trabajo sobre copia; batería de pruebas automatizadas; ventana de despliegue coordinada; plan de reversión |
| Pérdida o distorsión de datos al fusionar catálogos duplicados | Alto | Migraciones reversibles, respaldo previo obligatorio y validación de conteos y sumas antes/después |
| Alteración involuntaria de los cálculos de depreciación | Alto | Pruebas de cálculo comparadas contra los resultados actuales antes de dar por cerrada la fase |
| Parámetros de depreciación distintos a los que aplica la ENA | Alto | **Validación del régimen contable con la Unidad Financiera Institucional de la ENA en la primera semana del proyecto**, antes de configurar el motor multi-régimen |
| Baja calidad de los datos históricos en hojas de cálculo | Medio | Importador con validación fila por fila y reporte de errores; ciclos de depuración con la Unidad de Activo Fijo |
| Indisponibilidad del sistema durante el despliegue | Medio | Despliegue en horario no laboral, con ventana acordada previamente |

---

## 5. Alcance de la entrega

### 5.1 Incluido

**Módulo de Bienes Materiales**
- Registro completo del activo: código único no reutilizable, descripción, marca, modelo, serie,
  categoría, estado, ubicación, centro de costo, unidad organizativa, custodio, proveedor y fuente de
  financiamiento.
- Clasificación automática **mayor/menor** según el umbral configurable de US$900.
- Movimientos con cambio automático de estado: adquisición, asignación, traslado, préstamo, devolución
  y descargo, con historial completo por bien.
- Adjuntos digitales por activo (facturas, actas, fotografías, garantías).
- Generación e impresión de **etiquetas de código de inventario**.

**Módulo de Depreciación y Amortización**
- Motor **multi-régimen** con perspectivas independientes: **gubernamental (Manual Técnico del
  SAFI)** — método lineal, valor residual del 10% y tabla oficial de vida útil —, contable (NIIF) y
  fiscal (Art. 30 LISR).
- Aplicación de la regla ENA: **sólo se deprecian los activos mayores**.
- Valor en libros, depreciación acumulada, revaluaciones y deterioro.
- **Amortización de activos intangibles** (software, licencias perpetuas, creaciones
  institucionales).
- Proceso idempotente: no duplica períodos ya calculados.

**Módulo de Cumplimiento y Auditoría**
- **Conciliación con contabilidad**: informes financieros semestrales por clase de bien (mobiliario
  y equipo, equipo de transporte, inmuebles, software) y **acta de conciliación anual** firmada por
  el contador y el jefe de activo fijo.
- **Constatación física de bienes**: programación anual, levantamiento total por dependencia,
  reporte para firma de la jefatura, control del plazo de observaciones, y registro y seguimiento de
  faltantes y sobrantes.
- **Descargo con flujo de autorización**: solicitud de la dependencia, justificación técnica,
  memorándum de remisión, autorización del titular y descargo definitivo, con registro del valor en
  libros a la fecha de baja y de la ganancia o pérdida resultante. Motivos: permuta, subasta,
  donación, venta, sustitución y destrucción.
- **Expediente de robo, hurto o extravío**: denuncia, descargo provisional y descargo definitivo al
  cerrar el proceso legal o al finiquitar la póliza.
- **Cuenta contable por categoría**, alineada al catálogo de Contabilidad Gubernamental.
- **Resguardo documental** con retención mínima de cinco años.

**Módulo de Bienes Animales / Biológicos**
- Tipos de bien biológico, unidades productivas y lotes.
- Movimientos (nacimientos, compras, ventas, traslados, muertes, descartes) y **reportes mensuales con
  arrastre de saldos**.

**Catálogos de mantenimiento**
- Categorías, ubicaciones jerárquicas, estados, centros de costo, unidades organizativas, fuentes de
  financiamiento, proveedores y custodios — **compartidos con el módulo de bodegas donde corresponda**.

**Reportes y consultas (exportables a PDF y Excel)**
- Inventario general de activos, depreciación por período, movimientos, activos por responsable,
  reporte de bienes animales y reporte anual consolidado. Panel de indicadores con gráficos.

**Generación documental**
- Actas de adquisición y asignación, formatos de traslado, préstamo y descargo, y etiquetas.

**Seguridad y auditoría**
- Cuentas de usuario únicas para toda la plataforma, roles y permisos granulares por módulo, y
  **bitácora completa** (usuario, fecha, hora y acción) sobre todas las transacciones.

**Servicios**
- Migración de datos históricos, despliegue en producción, **capacitación al personal**, manual de
  usuario y manual técnico **en español**, y **12 meses de soporte y mantenimiento**.

### 5.2 No incluido (cotizable por separado)

- Infraestructura de servidor, hospedaje, dominio y certificados.
- Digitalización o depuración manual de archivos físicos por parte del oferente.
- **Interfaz técnica con el sistema SAFI del Ministerio de Hacienda u otros sistemas contables de
  terceros.** El módulo aplica los **criterios** de depreciación y el catálogo de cuentas de
  Contabilidad Gubernamental y genera los informes financieros para la conciliación; no se conecta
  por medios electrónicos con esos sistemas.
- Desarrollos personalizados adicionales no descritos en la sección 5.1.
- Ejecución material del levantamiento físico en sitio por parte del oferente. El sistema
  **soporta y documenta** la constatación física; el conteo lo realiza el personal de la ENA.
- Las fases futuras descritas en la sección 5.3.

### 5.3 Fases futuras (precio indicativo, no incluidas)

Identificadas durante el análisis y disponibles como ampliación posterior. Se listan para que la
institución conozca la hoja de ruta completa del módulo:

| Fase | Contenido | Esfuerzo | Precio indicativo |
|---|---|---:|---:|
| **N3 — Gestión patrimonial completa** | Bienes inmuebles como dominio propio (escritura, inscripción registral, avalúos y mejoras); proyectos de inversión con liquidación contable; comodatos y bienes de terceros; planes de mantenimiento preventivo y control de garantías; seguros, pólizas y reclamos; datos específicos de vehículos; bodega de bienes en desuso | 180 h | US$ 4,100.00 |
| **N4 — Movilidad y transparencia** | Aplicación móvil de constatación física con escaneo de código QR y **operación sin conexión**; portal del custodio ("mis bienes asignados") con aceptación en línea del acta de responsabilidad; alertas de garantías, pólizas y mantenimientos por vencer; firma electrónica en actas | 90 h | US$ 2,050.00 |

> El módulo de **proyectos de inversión** de la fase N3 es de interés inmediato para la ENA, dado
> que administra bienes bajo el Proyecto de Inversión 6882.

---

## 6. Oferta económica

### Opción A — Módulo de Activo Fijo integrado *(recomendada)*

| # | Concepto | Monto (US$) |
|---|---|---:|
| 1 | **Licencia de uso** del Módulo de Gestión de Activo Fijo (perpetua, institucional, usuarios ilimitados) | 4,600.00 |
| 2 | **Adecuación al marco normativo salvadoreño** — motor de depreciación multi-régimen conforme al Manual Técnico del SAFI, cuenta contable por categoría, gestión de mantenimientos, revaluaciones y descargos | 1,450.00 |
| 3 | **Módulo de Cumplimiento y Auditoría** — conciliación contable, constatación física anual, descargo con flujo de autorización, activos intangibles y amortización, expediente de extravíos, resguardo documental | 2,050.00 |
| 4 | **Servicios de integración**: homologación de plataforma, unificación de base de datos y catálogos, identidad única de usuario y matriz de permisos unificada | 900.00 |
| 5 | **Migración de datos históricos, capacitación y manuales** en español | 450.00 |
| 6 | **Soporte y mantenimiento — 12 meses** | Incluido |
| | **TOTAL** | **US$ 9,450.00** |

> Monto en dólares de los Estados Unidos de América. Los impuestos aplicables se detallarán conforme al
> régimen tributario del oferente al momento de la facturación.

**Plazo de ejecución:** 26 semanas calendario a partir de la orden de inicio.

**Forma de pago propuesta**

| Hito | % | Monto (US$) |
|---|---:|---:|
| Orden de inicio | 30% | 2,835.00 |
| Fases 0 a 2 concluidas (plataforma homologada, esquema unificado y dominio migrado) | 25% | 2,362.50 |
| Entrega para pruebas de aceptación (fases 3 a 7 y bloques N1 y N2 concluidos) | 30% | 2,835.00 |
| Recepción definitiva, capacitación y puesta en producción | 15% | 1,417.50 |

**Cuota anual de soporte a partir del segundo año**

Al incorporarse un segundo módulo, **la cuota anual de soporte de la plataforma aumenta**: se
mantiene el doble de funcionalidad. Ese incremento **es equivalente —y de hecho menor— a lo que
costaría sostener dos sistemas por separado**, y se paga sobre una sola infraestructura, un solo
respaldo y un solo ciclo de actualización. Calculada como el 15% del valor de las licencias vigentes:

| Escenario | Cuota anual |
|---|---:|
| Hoy — sólo el módulo de Bodegas | US$ 1,339.00 |
| **Opción A — plataforma con ambos módulos** | **US$ 2,757.00** |
| Opción B — dos sistemas mantenidos por separado | US$ 3,124.00 |

**Cronograma referencial**

| Semanas | Actividad |
|---|---|
| 1 | Validación del régimen contable con la Unidad Financiera Institucional de la ENA |
| 1 – 2 | Fase 0: homologación de plataforma y regresión del sistema en producción |
| 3 – 5 | Fase 1: unificación de esquema y catálogos |
| 5 – 7 | Fase 2: migración de modelos y servicios de dominio |
| 6 – 10 | Fase 3: migración de pantallas e integración visual |
| 9 – 11 | Fases 4 y 5: reportes, documentos, permisos y bitácora |
| 11 – 12 | Fase 6: integración funcional Bodega → Activo Fijo |
| 12 – 16 | Bloque N1: depreciación multi-régimen SAFI, cuenta contable y pantallas de gestión |
| 15 – 22 | Bloque N2: conciliación contable, constatación física, descargo autorizado, intangibles y extravíos |
| 22 – 23 | Fase 7: migración de datos históricos |
| 23 – 25 | Fase 8: control de calidad y pruebas de aceptación con la ENA |
| 26 | Fase 9: despliegue, capacitación y entrega de manuales |

### Opción B — Sistema independiente (referencia comparativa)

Mismo alcance funcional, entregado como sistema separado del de bodegas:

| # | Concepto | Monto (US$) |
|---|---|---:|
| 1 | Licencia de uso del Sistema de Gestión de Activo Fijo **independiente** — incluye su propio registro de usuarios, autenticación, despliegue y administración, que en la Opción A se heredan sin costo | 8,400.00 |
| 2 | Adecuación normativa y módulo de cumplimiento y auditoría | 3,500.00 |
| 3 | Interfaz de sincronización de usuarios e intercambio de datos con el sistema de bodegas | 1,780.00 |
| 4 | Migración de datos históricos, capacitación y manuales | Incluido |
| 5 | Soporte y mantenimiento — 12 meses (segundo contrato, independiente) | Incluido |
| | **TOTAL** | **US$ 13,680.00** |

### Comparación de costo total de propiedad (3 años)

Soporte anual calculado como el 15% del valor de las licencias vigentes. En la Opción B se suman dos
contratos de soporte independientes y el mantenimiento de la interfaz de sincronización, que en la
Opción A no existe.

| Concepto | Opción A — Integrado | Opción B — Independiente |
|---|---:|---:|
| Inversión inicial | 9,450.00 | 13,680.00 |
| Soporte años 2 y 3 | 5,514.00 | 6,248.00 |
| Infraestructura adicional (servidor/base de datos separada, 3 años) | 0.00 | ~1,080.00 |
| **Total 3 años** | **US$ 14,964.00** | **US$ 21,008.00** |
| **Diferencia** | — | **+ US$ 6,044.00 (40% más)** |

> A lo anterior se suman los costos **no monetarios** de la Opción B: doble administración de usuarios,
> doble captura de catálogos, conciliaciones manuales entre sistemas y riesgo de inconsistencia en los
> respaldos.

---

## 7. Soporte, garantía y condiciones

- **Garantía de funcionamiento:** 12 meses desde la recepción definitiva. Cubre la corrección **sin costo**
  de cualquier defecto atribuible al desarrollo.
- **Soporte incluido (12 meses):** atención por correo electrónico y canal remoto en horario hábil,
  actualizaciones correctivas, respaldo de la base de datos y acompañamiento en cierres de período.
- **Tiempos de respuesta:** incidencia crítica (sistema fuera de servicio) 4 horas hábiles; incidencia
  mayor 1 día hábil; consulta o mejora menor 3 días hábiles.
- **Renovación de soporte a partir del segundo año:** opcional, 15% del valor de las licencias vigentes.
  Al sumarse el segundo módulo la cuota anual pasa de US$ 1,339 a US$ 2,757 — incremento equivalente,
  y de hecho menor, a sostener dos sistemas por separado (US$ 3,124). Ver sección 6.
- **Propiedad de la información:** toda la información registrada es **propiedad exclusiva de la ENA**.
  A solicitud, se entrega respaldo completo de la base de datos en formato estándar.
- **Capacitación:** dos jornadas presenciales o virtuales — una para usuarios operativos y otra para la
  Unidad de Informática (administración, respaldos y gestión de usuarios).
- **Adecuaciones futuras:** el sistema admite desarrollos personalizados para funciones no incluidas,
  cotizables por separado.
- **Sistema web:** accesible desde navegador, con interfaz y manuales **íntegramente en español**.

---

## 8. Supuestos de la oferta

1. El sistema de bodegas continúa en operación y el oferente mantiene acceso a su código fuente y a su
   entorno de despliegue.
2. La ENA provee un entorno de pruebas equivalente al de producción para validar la homologación de
   plataforma antes del despliegue.
3. La ENA designa una contraparte técnica y una contraparte funcional (Unidad de Activo Fijo) para la
   definición de criterios de clasificación y las pruebas de aceptación.
4. La **Unidad Financiera Institucional de la ENA** confirma, durante la primera semana del proyecto,
   el régimen contable aplicable (Contabilidad Gubernamental / SAFI, con la adopción de NICSP en
   curso) y la tabla de vida útil vigente para la institución. El motor de depreciación es
   multi-régimen y admite cualquiera de los tres marcos, por lo que esta confirmación **no afecta el
   plazo ni el precio**; determina únicamente la configuración por defecto.
5. Los datos históricos a migrar se entregan en formato digital (hoja de cálculo) con estructura
   consistente. La depuración de datos de origen es responsabilidad de la ENA, con acompañamiento del
   oferente.
6. Se acuerda una ventana de despliegue en horario no laboral para el paso a producción.

---

**Jorge Mariano Paz Flores**
Global Development Solutions
