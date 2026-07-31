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
            ['name' => 'congreso_a', 'label' => 'Congreso A'],
            ['name' => 'congreso_b', 'label' => 'Congreso B'],
        ];

        foreach ($congresses as $congress) {
            Congress::firstOrCreate(
                ['name' => $congress['name']],
                ['label' => $congress['label']]
            );
        }
    }
}
