<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    public function up(): void
    {
        // Crear usuario administrador si no existe
        $adminExists = DB::table('users')
            ->where('email', 'admin@obsidiana.local')
            ->exists();

        if (!$adminExists) {
            DB::table('users')->insert([
                'name' => 'Admin',
                'email' => 'admin@obsidiana.local',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'is_admin' => true,
                'status' => 'approved',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // Eliminar el usuario administrador si se revierte la migración
        DB::table('users')
            ->where('email', 'admin@obsidiana.local')
            ->delete();
    }
};
