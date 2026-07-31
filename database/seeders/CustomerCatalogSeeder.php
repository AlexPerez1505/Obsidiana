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
            ['name' => 'VIP'],
            ['name' => 'Regular'],
            ['name' => 'Nuevo'],
        ];

        foreach ($categories as $category) {
            Category::firstOrCreate(['name' => $category['name']]);
        }

        $congresses = [
            [
                'name' => 'congreso_a',
                'label' => 'Congreso A',
                'description' => 'Congreso de ejemplo A',
                'start_date' => '2026-08-15',
                'end_date' => '2026-08-18',
                'assembly_time' => '08:00',
                'disassembly_time' => '20:00',
            ],
            [
                'name' => 'congreso_b',
                'label' => 'Congreso B',
                'description' => 'Congreso de ejemplo B',
                'start_date' => '2026-09-10',
                'end_date' => '2026-09-13',
                'assembly_time' => '09:00',
                'disassembly_time' => '21:00',
            ],
        ];

        foreach ($congresses as $congress) {
            Congress::firstOrCreate(
                ['name' => $congress['name']],
                $congress
            );
        }
    }
}
