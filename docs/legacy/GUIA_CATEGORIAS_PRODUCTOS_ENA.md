# Guía de Categorías de Productos - ENA

## Resumen

El sistema utiliza una estructura jerárquica de categorías con dos niveles:
- **Categorías Padre**: Agrupaciones principales (sin `parent_id`)
- **Subcategorías**: Clasificaciones específicas donde se asignan los productos (con `parent_id`)

> **Importante**: Los productos SOLO se asignan a subcategorías, nunca a categorías padre.

---

## Estructura de la Tabla `product_categories`

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | bigint | ID autoincremental |
| `company_id` | bigint | FK a companies |
| `parent_id` | bigint (nullable) | FK auto-referencial (NULL = categoría padre) |
| `name` | string | Nombre de la categoría |
| `slug` | string | Slug para URLs |
| `code` | string | Código interno (CAT-XX o SUB-XXXXX) |
| `legacy_code` | string | Código del sistema anterior del cliente |
| `is_active` | boolean | Estado activo/inactivo |

---

## Categorías Padre (12)

| ID | Código | Legacy Code | Nombre |
|----|--------|-------------|--------|
| 6 | CAT-54 | 54 | Adquisición de Bienes |
| 7 | CAT-61 | 61 | Activos |
| 8 | CAT-70 | 70 | Producción Interna |
| 9 | CAT-71 | 71 | Donaciones |
| 10 | CAT-72 | 72 | Convenios |
| 11 | CAT-73 | 73 | Autoconsumo |
| 12 | CAT-74 | 74 | Consignaciones |
| 13 | CAT-75 | 75 | Proyectos |
| 14 | CAT-76 | 76 | Permutas |
| 15 | CAT-77 | 77 | Reingresos a Bodega General |
| 16 | CAT-78 | 78 | Fondo de Mantenimiento de Estudiantes |
| 17 | CAT-80 | 80 | Entrada Producto Terminado |

---

## Subcategorías por Categoría Padre

### 54 - Adquisición de Bienes (20 subcategorías)

| Legacy Code | Nombre |
|-------------|--------|
| 54101 | Materiales e Insumos |
| 54102 | Productos Agropecuarios |
| 54103 | Productos Veterinarios |
| 54104 | Productos Alimenticios para Personas |
| 54105 | Productos de Papel y Cartón |
| 54106 | Productos de Cuero y Caucho |
| 54107 | Productos Químicos |
| 54108 | Productos Farmacéuticos y Medicinales |
| 54109 | Llantas y Neumáticos |
| 54110 | Combustibles y Lubricantes |
| 54111 | Minerales No Metálicos y Productos Derivados |
| 54112 | Minerales Metálicos y Productos Derivados |
| 54113 | Materiales e Instrumental de Lab y Uso Médico |
| 54114 | Materiales de Oficina |
| 54115 | Materiales Informáticos |
| 54116 | Libros, Textos, Útiles de Enseñanza y Publicaciones |
| 54117 | Materiales de Defensa y Seguridad Pública |
| 54118 | Herramientas, Repuestos y Accesorios |
| 54119 | Materiales Eléctricos |
| 54199 | Bienes de Uso y Consumo Diversos |

### 61 - Activos (10 subcategorías)

| Legacy Code | Nombre |
|-------------|--------|
| 61101 | Mobiliario y Equipo de Oficina |
| 61102 | Mobiliario y Equipo Educacional y Recreativo |
| 61103 | Equipo de Computación |
| 61104 | Equipo Médico y de Laboratorio |
| 61105 | Equipo de Transporte, Tracción y Elevación |
| 61106 | Equipo de Comunicación y Señalamiento |
| 61107 | Equipo de Defensa y Seguridad |
| 61108 | Maquinaria y Equipo de Producción |
| 61109 | Equipo para Instalaciones |
| 61199 | Bienes Muebles Diversos |

### 70 - Producción Interna (4 subcategorías)

| Legacy Code | Nombre |
|-------------|--------|
| 70101 | Producción Agrícola |
| 70102 | Producción Pecuaria |
| 70103 | Producción Agroindustrial |
| 70199 | Otra Producción Interna |

### 71 - Donaciones (3 subcategorías)

| Legacy Code | Nombre |
|-------------|--------|
| 71101 | Donaciones de Materiales e Insumos |
| 71102 | Donaciones de Equipo |
| 71199 | Otras Donaciones |

### 72 - Convenios (2 subcategorías)

| Legacy Code | Nombre |
|-------------|--------|
| 72101 | Bienes por Convenio Institucional |
| 72199 | Otros Bienes por Convenio |

### 73 - Autoconsumo (3 subcategorías)

| Legacy Code | Nombre |
|-------------|--------|
| 73101 | Autoconsumo de Producción Agrícola |
| 73102 | Autoconsumo de Producción Pecuaria |
| 73199 | Otro Autoconsumo |

### 74 - Consignaciones (2 subcategorías)

| Legacy Code | Nombre |
|-------------|--------|
| 74101 | Bienes en Consignación |
| 74199 | Otras Consignaciones |

### 75 - Proyectos (3 subcategorías)

| Legacy Code | Nombre |
|-------------|--------|
| 75101 | Materiales para Proyectos |
| 75102 | Equipos para Proyectos |
| 75199 | Otros Bienes para Proyectos |

### 76 - Permutas (2 subcategorías)

| Legacy Code | Nombre |
|-------------|--------|
| 76101 | Bienes Recibidos por Permuta |
| 76199 | Otras Permutas |

### 77 - Reingresos a Bodega General (3 subcategorías)

| Legacy Code | Nombre |
|-------------|--------|
| 77101 | Reingreso de Materiales |
| 77102 | Reingreso de Equipos |
| 77199 | Otros Reingresos |

### 78 - Fondo de Mantenimiento de Estudiantes (2 subcategorías)

| Legacy Code | Nombre |
|-------------|--------|
| 78101 | Materiales Fondo Estudiantes |
| 78199 | Otros Fondo Estudiantes |

### 80 - Entrada Producto Terminado (4 subcategorías)

| Legacy Code | Nombre |
|-------------|--------|
| 80101 | Producto Terminado Agrícola |
| 80102 | Producto Terminado Pecuario |
| 80103 | Producto Terminado Agroindustrial |
| 80199 | Otro Producto Terminado |

---

## Uso en el Código

### Modelo ProductCategory

```php
// Relaciones
$category->parent;      // Obtener categoría padre
$category->children;    // Obtener subcategorías

// Scopes
ProductCategory::parents()->get();        // Solo categorías padre
ProductCategory::subcategories()->get();  // Solo subcategorías

// Atributos
$category->full_name;   // "Adquisición de Bienes > Materiales e Insumos"

// Métodos
$category->isParent();       // true si es categoría padre
$category->isSubcategory();  // true si es subcategoría
```

### Obtener Subcategorías para Dropdown de Productos

```php
// En controlador o componente Livewire
$categories = ProductCategory::active()
    ->subcategories()
    ->with('parent')
    ->orderBy('name')
    ->get();
```

### En Blade (Flux UI)

```blade
<flux:select wire:model="category_id" label="Subcategoría">
    <flux:select.option value="">Seleccione subcategoría</flux:select.option>
    @foreach($categories as $category)
        <flux:select.option value="{{ $category->id }}">
            {{ $category->full_name }}
        </flux:select.option>
    @endforeach
</flux:select>
```

### Buscar por Legacy Code (para importación)

```php
// Buscar subcategoría por código legacy del cliente
$category = ProductCategory::where('legacy_code', '54101')->first();

// Buscar categoría padre
$parent = ProductCategory::where('legacy_code', '54')
    ->whereNull('parent_id')
    ->first();
```

---

## Seeder

Para repoblar las categorías:

```bash
php artisan db:seed --class=ENACategoriesSeeder
```

> **Nota**: El seeder elimina (soft delete) las categorías existentes antes de crear las nuevas.

---

## Resumen de Totales

| Tipo | Cantidad |
|------|----------|
| Categorías Padre | 12 |
| Subcategorías | 58 |
| **Total** | **70** |
