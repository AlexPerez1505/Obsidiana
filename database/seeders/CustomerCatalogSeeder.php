<?php

namespace Database\Seeders;

use App\Models\Congress;
use App\Models\CustomerCategory;
use Illuminate\Database\Seeder;

class CustomerCatalogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            ['name' => 'vip', 'label' => 'VIP'],
            ['name' => 'regular', 'label' => 'Regular'],
            ['name' => 'nuevo', 'label' => 'Nuevo'],
        ];

        foreach ($categories as $category) {
            CustomerCategory::firstOrCreate(
                ['name' => $category['name']],
                ['label' => $category['label']]
            );
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
