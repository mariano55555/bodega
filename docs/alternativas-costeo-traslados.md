# Costo unitario en traslados: alternativas de costeo de inventario

Documento para consultar con contabilidad y con los usuarios de bodega antes de decidir un cambio en la forma de valorar el inventario.

## 1. Situación actual (ya implementado)

El sistema guarda **una sola fila de inventario por producto y bodega**, con una cantidad total y un solo costo unitario. Ese costo es siempre el de la **última compra recibida** y sobrescribe al anterior. No existen "unidades viejas" y "unidades nuevas" con costos distintos dentro del sistema.

Ejemplo real de la base de datos:

| Producto | Compras recibidas (costo) | Costo actual en inventario |
|---|---|---|
| Cilindro 100 lbs | 37.98, 41.58, 41.58, 42.81 | 42.81 |
| Codo PVC 2" 45 | 1.77, 1.30 | 1.30 |
| Gas licuado | 2.29, 2.29, 2.29, 2.45 | 2.45 |

En términos contables este método se llama **"costo según última compra"** (en inglés *last purchase cost*). Es uno de los métodos que permite el Código Tributario en El Salvador, pero conviene confirmar con el contador cuál método tiene declarada la empresa.

**Lo que se acaba de implementar (opción 1):** al crear o editar un traslado, el campo *Costo Unit.* ahora es editable. Se llena por defecto con el costo del inventario y el usuario puede cambiarlo. El costo que se guarda en el traslado es el que se usa al enviarlo y al recibirlo en la bodega destino. Es el mismo comportamiento que ya tenían los despachos.

Esto resuelve el caso del usuario ($1.00 vs $1.05) de forma manual, pero **no cambia la valoración del inventario**: el inventario sigue con el costo de la última compra.

## 2. Opción 2: Costo Promedio Ponderado

**Nombres con los que se conoce:** Costo Promedio Ponderado (CPP), Promedio Ponderado Móvil, Costo Promedio. En inglés *Weighted Average Cost* (WAC) o *Moving Average Cost*.

**Cómo funciona:** cada vez que entra una compra, el costo del inventario se recalcula mezclando lo que ya había con lo que entra.

Ejemplo: quedan 50 unidades a $1.50 y entran 100 unidades a $1.00.

```
Costo promedio = (50 x 1.50 + 100 x 1.00) / (50 + 100) = 175 / 150 = $1.1667
```

A partir de ese momento, todo traslado o despacho sale a $1.1667 hasta la próxima compra.

**Qué cambiaría en el sistema:**

- La recepción de compras dejaría de sobrescribir el costo y pasaría a calcular el promedio. La lógica de promedio ya existe en el job que actualiza niveles de inventario, pero hoy las compras no la usan.
- Las recepciones de traslados en bodega destino también promediarían con lo que ya hay en esa bodega.
- Los reportes de valoración (Kardex, cierres de inventario) mostrarían el costo promedio.
- Habría que decidir qué hacer con los saldos actuales: recalcular desde el historial de movimientos o arrancar con el costo actual como base.

**Ventajas:**

- Es el método más usado en El Salvador y el más simple de explicar.
- El costo unitario nunca "salta" de golpe con una compra cara o barata.
- No requiere manejar lotes ni capas. El cambio es mediano.
- El campo manual de la opción 1 sigue funcionando como excepción.

**Desventajas:**

- El costo que ve el usuario no coincide con ninguna factura en particular. Puede generar preguntas del tipo "¿de dónde salió $1.1667?".
- Si la empresa tiene declarado otro método ante Hacienda, cambiar requiere autorización o al menos consistencia con los libros.

**Esfuerzo estimado:** 2 a 3 días de desarrollo más pruebas, sin contar la migración de saldos históricos.

## 3. Opción 3: PEPS con control por lotes (capas de inventario)

**Nombres con los que se conoce:** PEPS (Primeras Entradas, Primeras Salidas). En inglés *FIFO* (First In, First Out). También se le llama "costeo por capas" o "por lotes".

**Cómo funciona:** el sistema guarda cada entrada como una capa separada con su cantidad y su costo. Al trasladar o despachar, consume primero la capa más antigua y, cuando se agota, pasa a la siguiente. Un mismo traslado puede salir con dos costos distintos.

Ejemplo: quedan 50 unidades a $1.50 (compra de hace dos semanas) y entran 100 a $1.00 (compra de esta semana). Se trasladan 80 unidades.

```
50 unidades a $1.50 = $75.00   (se agota la capa vieja)
30 unidades a $1.00 = $30.00   (empieza la capa nueva)
Total del traslado    = $105.00 (costo promedio efectivo $1.3125)
```

Esto es lo que los usuarios **creen** que el sistema hace hoy. No es así.

**Qué cambiaría en el sistema:**

- La tabla de inventario tendría varias filas por producto y bodega (una por lote o por compra). Hoy la estructura ya permite un número de lote por fila, pero las compras no lo usan de esa forma.
- Compras, traslados, despachos, producción interna y ajustes tendrían que consumir y crear capas. Todo módulo que descuenta stock cambia.
- La pantalla de traslado mostraría el desglose por lote o lo calcularía automáticamente al enviar.
- El Kardex y los reportes de valoración tendrían que reflejar el saldo por capas.
- La recepción en bodega destino crearía capas nuevas con el costo de origen.
- Migración de datos: los saldos actuales tendrían que convertirse en una capa inicial por producto y bodega.

**Ventajas:**

- El costo de cada salida corresponde a facturas reales.
- Es el método más preciso y también aceptado por Hacienda.
- Permite trazabilidad por lote (útil para vencimientos y gas).

**Desventajas:**

- Es el cambio más grande. Afecta todos los módulos que mueven inventario.
- Los usuarios verían traslados con dos costos para el mismo producto, lo que hoy les confunde.
- Los ajustes de inventario y las devoluciones se vuelven más complejos (¿a qué capa regresa?).
- Mayor riesgo de errores durante la migración de saldos.

**Esfuerzo estimado:** 3 a 5 semanas de desarrollo más pruebas y migración.

## 4. Otros métodos que existen (solo para referencia)

- **UEPS / LIFO** (Últimas Entradas, Primeras Salidas): no está permitido bajo NIIF (NIC 2) ni por el Código Tributario salvadoreño. Descartado.
- **Identificación específica:** cada unidad con su propio costo. Solo tiene sentido para productos únicos (vehículos, maquinaria). No aplica a bodega.
- **Costo estándar:** un costo fijo definido por la empresa que se revisa periódicamente. Requiere que contabilidad registre las variaciones. Poco común en este tipo de negocio.

## 5. Comparación rápida

| Criterio | Actual (última compra) | Opción 2: Promedio | Opción 3: PEPS por lotes |
|---|---|---|---|
| Costo del traslado | Última compra, editable a mano | Promedio automático, editable a mano | Costo real de cada capa |
| Cambio en el sistema | Ya hecho | Mediano | Grande |
| Afecta valoración del inventario | No | Sí | Sí |
| Migración de saldos | No | Recomendable | Obligatoria |
| Aceptado por Hacienda (El Salvador) | Sí | Sí | Sí |
| Facilidad de explicar al usuario | Alta | Media | Baja |

## 6. Preguntas para contabilidad

1. ¿Qué método de valuación de inventario tiene declarado la empresa ante el Ministerio de Hacienda? (Última compra, promedio o PEPS).
2. ¿Los estados financieros y el inventario físico anual se hacen con ese mismo método?
3. Si el sistema cambia de método, ¿se necesita autorización previa o basta con aplicarlo a partir de un cierre de mes?
4. ¿Cómo quieren que se valúe el saldo inicial al momento del cambio: al costo actual del sistema o recalculado desde el historial?

## 7. Preguntas para los usuarios de bodega

1. Cuando trasladan un producto que tiene compras a distintos precios, ¿qué costo esperan ver: el de la última compra, un promedio o el de la compra más antigua?
2. ¿Necesitan que el sistema decida solo el costo o les basta con poder escribirlo a mano (opción 1, ya disponible)?
3. ¿Usan el costo del traslado para algo más que la valoración (por ejemplo, para cobrar entre sucursales)? Esto define qué tan exacto debe ser.

## 8. Recomendación

Usar la opción 1 (costo manual, ya implementada) mientras contabilidad confirma el método declarado. Si confirman **promedio**, implementar la opción 2. Si confirman **PEPS**, planificar la opción 3 como proyecto aparte con migración de saldos. Si confirman **última compra**, no hace falta ningún cambio adicional.
