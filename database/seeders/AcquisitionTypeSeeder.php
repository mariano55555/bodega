<?php

namespace Database\Seeders;

use App\Models\AcquisitionType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AcquisitionTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $acquisitionTypes = [
            [
                'name' => 'Compra Normal',
                'description' => 'Adquisición regular de productos',
            ],
            [
                'name' => 'Convenio',
                'description' => 'Compra realizada bajo convenio institucional',
            ],
            [
                'name' => 'Proyecto',
                'description' => 'Adquisición para proyecto específico',
            ],
            [
                'name' => 'Otro',
                'description' => 'Otros tipos de adquisición',
            ],
        ];

        foreach ($acquisitionTypes as $type) {
            AcquisitionType::create([
                'name' => $type['name'],
                'slug' => Str::slug($type['name']),
                'description' => $type['description'],
                'is_active' => true,
                'active_at' => now(),
            ]);
        }
    }
}
