<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        Role::firstOrCreate(
            ['name' => 'tecnico'],
            [
                'label' => 'Técnico',
                'description' => 'Técnico responsable de servicios internos',
                'is_active' => true,
            ]
        );
    }
}
