<?php

namespace Database\Seeders;

use App\Models\FundSource;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class FundSourceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $fundSources = [
            [
                'name' => 'Fondos Propios',
                'description' => 'Recursos propios de la organización',
            ],
            [
                'name' => 'Fondos GOES',
                'description' => 'Fondos del Gobierno de El Salvador',
            ],
        ];

        foreach ($fundSources as $source) {
            FundSource::create([
                'name' => $source['name'],
                'slug' => Str::slug($source['name']),
                'description' => $source['description'],
                'is_active' => true,
                'active_at' => now(),
            ]);
        }
    }
}
