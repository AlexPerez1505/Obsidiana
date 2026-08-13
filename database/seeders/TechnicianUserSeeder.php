<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class TechnicianUserSeeder extends Seeder
{
    public function run(): void
    {
        $role = Role::where('name', 'tecnico')->first();

        $user = User::firstOrCreate(
            ['email' => 'olimamaz@obsidiana.test'],
            [
                'name' => 'OLimamaz',
                'password' => bcrypt('password123'),
                'status' => 'approved',
                'is_admin' => false,
                'approved_at' => now(),
                'phone' => '0000000000',
            ]
        );

        if ($role && ! $user->hasRole('tecnico')) {
            $user->roles()->attach($role->id);
        }
    }
}
