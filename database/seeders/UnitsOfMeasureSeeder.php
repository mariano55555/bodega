<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UnitsOfMeasureSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Creates comprehensive units of measure for Dominican Republic warehouse system.
     */
    public function run(): void
    {
        $this->command->info('Creando unidades de medida...');

        // Base units (no conversion factor)
        $baseUnits = [
            // Peso - Base: Kilogramo
            [
                'name' => 'Kilogramo',
                'slug' => 'kilogramo',
                'abbreviation' => 'kg',
                'type' => 'weight',
                'description' => 'Unidad base de peso',
                'base_unit_id' => null,
                'conversion_factor' => 1.000000,
                'precision' => 3,
            ],
            // Volumen - Base: Litro
            [
                'name' => 'Litro',
                'slug' => 'litro',
                'abbreviation' => 'L',
                'type' => 'volume',
                'description' => 'Unidad base de volumen líquido',
                'base_unit_id' => null,
                'conversion_factor' => 1.000000,
                'precision' => 2,
            ],
            // Longitud - Base: Metro
            [
                'name' => 'Metro',
                'slug' => 'metro',
                'abbreviation' => 'm',
                'type' => 'length',
                'description' => 'Unidad base de longitud',
                'base_unit_id' => null,
                'conversion_factor' => 1.000000,
                'precision' => 2,
            ],
            // Cantidad - Base: Pieza
            [
                'name' => 'Pieza',
                'slug' => 'pieza',
                'abbreviation' => 'pz',
                'type' => 'quantity',
                'description' => 'Unidad base de cantidad individual',
                'base_unit_id' => null,
                'conversion_factor' => 1.000000,
                'precision' => 0,
            ],
            // Área - Base: Metro cuadrado
            [
                'name' => 'Metro Cuadrado',
                'slug' => 'metro-cuadrado',
                'abbreviation' => 'm²',
                'type' => 'area',
                'description' => 'Unidad base de área',
                'base_unit_id' => null,
                'conversion_factor' => 1.000000,
                'precision' => 2,
            ],
            // Volumen espacial - Base: Metro cúbico
            [
                'name' => 'Metro Cúbico',
                'slug' => 'metro-cubico',
                'abbreviation' => 'm³',
                'type' => 'volume',
                'description' => 'Unidad base de volumen espacial',
                'base_unit_id' => null,
                'conversion_factor' => 1.000000,
                'precision' => 3,
            ],
        ];

        // Insert base units first
        foreach ($baseUnits as $unit) {
            $unit['is_active'] = true;
            $unit['active_at'] = now();
            $unit['created_at'] = now();
            $unit['updated_at'] = now();

            DB::table('units_of_measure')->insert($unit);
        }

        // Get base unit IDs for conversions
        $kilogramo = DB::table('units_of_measure')->where('slug', 'kilogramo')->first();
        $litro = DB::table('units_of_measure')->where('slug', 'litro')->first();
        $metro = DB::table('units_of_measure')->where('slug', 'metro')->first();
        $pieza = DB::table('units_of_measure')->where('slug', 'pieza')->first();
        $metrosCuadrados = DB::table('units_of_measure')->where('slug', 'metro-cuadrado')->first();
        $metrosCubicos = DB::table('units_of_measure')->where('slug', 'metro-cubico')->first();

        // Derived units with conversion factors
        $derivedUnits = [
            // Unidades de peso
            [
                'name' => 'Gramo',
                'slug' => 'gramo',
                'abbreviation' => 'g',
                'type' => 'weight',
                'description' => 'Unidad de peso - 1000 gramos = 1 kilogramo',
                'base_unit_id' => $kilogramo->id,
                'conversion_factor' => 0.001000,
                'precision' => 0,
            ],
            [
                'name' => 'Libra',
                'slug' => 'libra',
                'abbreviation' => 'lb',
                'type' => 'weight',
                'description' => 'Unidad de peso anglosajona - 1 libra = 0.453592 kg',
                'base_unit_id' => $kilogramo->id,
                'conversion_factor' => 0.453592,
                'precision' => 2,
            ],
            [
                'name' => 'Onza',
                'slug' => 'onza',
                'abbreviation' => 'oz',
                'type' => 'weight',
                'description' => 'Unidad de peso - 16 onzas = 1 libra',
                'base_unit_id' => $kilogramo->id,
                'conversion_factor' => 0.028350,
                'precision' => 3,
            ],
            [
                'name' => 'Tonelada',
                'slug' => 'tonelada',
                'abbreviation' => 't',
                'type' => 'weight',
                'description' => 'Unidad de peso - 1 tonelada = 1000 kg',
                'base_unit_id' => $kilogramo->id,
                'conversion_factor' => 1000.000000,
                'precision' => 3,
            ],

            // Unidades de volumen líquido
            [
                'name' => 'Mililitro',
                'slug' => 'mililitro',
                'abbreviation' => 'ml',
                'type' => 'volume',
                'description' => 'Unidad de volumen - 1000 ml = 1 litro',
                'base_unit_id' => $litro->id,
                'conversion_factor' => 0.001000,
                'precision' => 0,
            ],
            [
                'name' => 'Galón',
                'slug' => 'galon',
                'abbreviation' => 'gal',
                'type' => 'volume',
                'description' => 'Unidad de volumen - 1 galón = 3.78541 litros',
                'base_unit_id' => $litro->id,
                'conversion_factor' => 3.785410,
                'precision' => 2,
            ],
            [
                'name' => 'Onza Líquida',
                'slug' => 'onza-liquida',
                'abbreviation' => 'fl oz',
                'type' => 'volume',
                'description' => 'Unidad de volumen - 1 fl oz = 29.5735 ml',
                'base_unit_id' => $litro->id,
                'conversion_factor' => 0.029574,
                'precision' => 3,
            ],

            // Unidades de longitud
            [
                'name' => 'Centímetro',
                'slug' => 'centimetro',
                'abbreviation' => 'cm',
                'type' => 'length',
                'description' => 'Unidad de longitud - 100 cm = 1 metro',
                'base_unit_id' => $metro->id,
                'conversion_factor' => 0.010000,
                'precision' => 1,
            ],
            [
                'name' => 'Milímetro',
                'slug' => 'milimetro',
                'abbreviation' => 'mm',
                'type' => 'length',
                'description' => 'Unidad de longitud - 1000 mm = 1 metro',
                'base_unit_id' => $metro->id,
                'conversion_factor' => 0.001000,
                'precision' => 0,
            ],
            [
                'name' => 'Pulgada',
                'slug' => 'pulgada',
                'abbreviation' => 'in',
                'type' => 'length',
                'description' => 'Unidad de longitud - 1 pulgada = 2.54 cm',
                'base_unit_id' => $metro->id,
                'conversion_factor' => 0.025400,
                'precision' => 3,
            ],
            [
                'name' => 'Pie',
                'slug' => 'pie',
                'abbreviation' => 'ft',
                'type' => 'length',
                'description' => 'Unidad de longitud - 1 pie = 12 pulgadas',
                'base_unit_id' => $metro->id,
                'conversion_factor' => 0.304800,
                'precision' => 3,
            ],

            // Unidades de cantidad
            [
                'name' => 'Docena',
                'slug' => 'docena',
                'abbreviation' => 'dz',
                'type' => 'quantity',
                'description' => 'Unidad de cantidad - 1 docena = 12 piezas',
                'base_unit_id' => $pieza->id,
                'conversion_factor' => 12.000000,
                'precision' => 0,
            ],
            [
                'name' => 'Cientos',
                'slug' => 'cientos',
                'abbreviation' => 'c',
                'type' => 'quantity',
                'description' => 'Unidad de cantidad - 1 ciento = 100 piezas',
                'base_unit_id' => $pieza->id,
                'conversion_factor' => 100.000000,
                'precision' => 0,
            ],
            [
                'name' => 'Millar',
                'slug' => 'millar',
                'abbreviation' => 'mil',
                'type' => 'quantity',
                'description' => 'Unidad de cantidad - 1 millar = 1000 piezas',
                'base_unit_id' => $pieza->id,
                'conversion_factor' => 1000.000000,
                'precision' => 0,
            ],
            [
                'name' => 'Par',
                'slug' => 'par',
                'abbreviation' => 'pr',
                'type' => 'quantity',
                'description' => 'Unidad de cantidad - 1 par = 2 piezas',
                'base_unit_id' => $pieza->id,
                'conversion_factor' => 2.000000,
                'precision' => 0,
            ],
            [
                'name' => 'Paquete',
                'slug' => 'paquete',
                'abbreviation' => 'paq',
                'type' => 'quantity',
                'description' => 'Unidad de cantidad - paquete estándar',
                'base_unit_id' => $pieza->id,
                'conversion_factor' => 1.000000,
                'precision' => 0,
            ],
            [
                'name' => 'Caja',
                'slug' => 'caja',
                'abbreviation' => 'cj',
                'type' => 'quantity',
                'description' => 'Unidad de cantidad - caja estándar',
                'base_unit_id' => $pieza->id,
                'conversion_factor' => 1.000000,
                'precision' => 0,
            ],
        ];

        // Insert derived units
        foreach ($derivedUnits as $unit) {
            $unit['is_active'] = true;
            $unit['active_at'] = now();
            $unit['created_at'] = now();
            $unit['updated_at'] = now();

            DB::table('units_of_measure')->insert($unit);
        }

        $this->command->info('✓ Unidades de medida creadas exitosamente');
        $this->command->line('  - 6 unidades base');
        $this->command->line('  - 18 unidades derivadas');
        $this->command->line('  - Total: 24 unidades de medida');
    }
}
