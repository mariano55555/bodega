# Fix de Márgenes - Kardex PDF (DomPDF)

## Problema

El PDF generado con DomPDF no respeta los márgenes correctamente:

- El **header** se corta o no se muestra completo
- El **margen derecho** no funciona, cortando "VALOR TOTAL"
- El contenido llega hasta el borde de la página

## Causa Raíz

DomPDF maneja los elementos `position: fixed` de forma especial. Los márgenes de `@page` definen el área de contenido, pero los elementos fixed se posicionan **relativos al borde de la página**, no al área de contenido. Por eso hay que usar valores negativos en `top`/`bottom` para colocarlos dentro del espacio del margen.

**Regla clave:** El valor negativo de `top` en el header debe coincidir exactamente con el `margin-top` de `@page`.

---

## Solución

### 1. Estilos `@page` y `body`

```css
@page {
    margin-top: 130px;
    margin-right: 20mm;
    margin-bottom: 25mm;
    margin-left: 20mm;
}

body {
    font-family: 'DejaVu Sans', sans-serif;
    font-size: 9px;
    color: #333;
    line-height: 1.4;
    margin: 0;
    padding: 0;
    /* NO usar padding-top, @page margin-top se encarga */
}
```

### 2. Header fijo

```css
.header {
    position: fixed;
    top: -130px;    /* Negativo = mismo valor que margin-top de @page */
    left: 0px;
    right: 0px;
    height: 110px;
    border-bottom: 2px solid #1e3a5f;
    padding-bottom: 10px;
}
```

### 3. Footer fijo

```css
.footer {
    position: fixed;
    bottom: -25mm;  /* Negativo = mismo valor que margin-bottom de @page */
    left: 0px;
    right: 0px;
    text-align: center;
    font-size: 8px;
    color: #666;
    border-top: 1px solid #ddd;
    padding-top: 5px;
}
```

### 4. Fix del margen derecho (tabla cortada)

Las columnas son demasiado anchas para el espacio disponible con los márgenes laterales de 20mm. Hay dos opciones:

#### Opción A: Reducir font-size de la tabla

```css
table.data-table th {
    font-size: 7px;  /* era 8px */
}

table.data-table td {
    font-size: 7px;  /* era 8px */
}
```

#### Opción B: Ajustar anchos de columnas

```html
<th style="width: 9%;">Fecha</th>
<th style="width: 12%;">Documento</th>
<th style="width: 17%;">Transacción</th>
<th class="right" style="width: 8%;">Saldo Ini.</th>
<th class="right" style="width: 8%;">Entrada</th>
<th class="right" style="width: 8%;">Salida</th>
<th class="right" style="width: 9%;">Saldo Final</th>
<th class="right" style="width: 9%;">Costo Unit.</th>
<th class="right" style="width: 10%;">Valor Total</th>
```

---

## Resumen Visual

```
┌─────────────────────────────────┐
│         @page margin-top        │  ← Header vive aquí (top: -130px)
│  ┌───────────────────────────┐  │
│  │        HEADER FIJO        │  │
│  └───────────────────────────┘  │
├─────────────────────────────────┤
│ ┌─┐                         ┌─┐ │
│ │m│                         │m│ │
│ │a│    CONTENIDO DEL BODY   │a│ │
│ │r│    (fluye con márgenes  │r│ │
│ │g│     izq/der de @page)   │g│ │
│ │ │                         │ │ │
│ │L│                         │R│ │
│ └─┘                         └─┘ │
├─────────────────────────────────┤
│  ┌───────────────────────────┐  │
│  │       FOOTER FIJO         │  │  ← Footer vive aquí (bottom: -25mm)
│  └───────────────────────────┘  │
│        @page margin-bottom      │
└─────────────────────────────────┘
```

## Notas Importantes

- **No mezclar** `padding-top` en el body con `margin-top` en `@page` — usar solo `@page`
- Los valores de `top` y `bottom` en elementos fixed deben ser **negativos** e **iguales** a los márgenes correspondientes de `@page`
- Si el header se corta, aumentar `margin-top` de `@page` y ajustar `top` del header
- DomPDF no soporta todas las propiedades CSS — evitar `flexbox`, `grid`, `calc()`
