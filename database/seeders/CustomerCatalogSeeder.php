<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Congress;
use Illuminate\Database\Seeder;

class CustomerCatalogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            ['nombre' => 'VIP'],
            ['nombre' => 'Regular'],
            ['nombre' => 'Nuevo'],
        ];

        $categoriaIds = [];
        foreach ($categories as $category) {
            $model = Category::firstOrCreate(['nombre' => $category['nombre']]);
            $categoriaIds[$category['nombre']] = $model->id;
        }

        $congresses = [
            [
                'nombre' => 'Congreso A',
                'descripcion' => 'Congreso de ejemplo A',
                'categoria_id' => $categoriaIds['VIP'],
                'fecha_inicio' => '2026-08-15',
                'fecha_finalizacion' => '2026-08-18',
                'hora_montaje' => '08:00',
                'hora_desmontaje' => '20:00',
            ],
            [
                'nombre' => 'Congreso B',
                'descripcion' => 'Congreso de ejemplo B',
                'categoria_id' => $categoriaIds['Regular'],
                'fecha_inicio' => '2026-09-10',
                'fecha_finalizacion' => '2026-09-13',
                'hora_montaje' => '09:00',
                'hora_desmontaje' => '21:00',
            ],
        ];

        foreach ($congresses as $congress) {
            Congress::firstOrCreate(
                ['nombre' => $congress['nombre']],
                $congress
            );
        }
    }
}
