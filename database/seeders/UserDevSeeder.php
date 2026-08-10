<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserDevSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'name' => 'Usuario Admin',
            'email' => 'admin@obsidiana.test',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'is_admin' => true,
            'status' => 'approved',
            'approved_at' => now(),
        ]);
    }
}
