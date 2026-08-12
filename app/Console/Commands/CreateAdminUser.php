<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateAdminUser extends Command
{
    protected $signature = 'admin:create';
    protected $description = 'Crea un nuevo usuario administrador';

    public function handle()
    {
        $this->info('=== Crear Usuario Administrador ===');

        // Verificar si el usuario ya existe
        $userExists = User::where('email', 'admin@obsidiana.local')->exists();

        if ($userExists) {
            $this->warn('El usuario admin@obsidiana.local ya existe.');
            return 0;
        }

        // Crear el usuario
        $user = User::create([
            'name' => 'Admin',
            'email' => 'admin@obsidiana.local',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'is_admin' => true,
            'status' => 'approved',
        ]);

        $this->info('✓ Usuario administrador creado exitosamente');
        $this->line('');
        $this->line('Credenciales:');
        $this->line('  Email: admin@obsidiana.local');
        $this->line('  Contraseña: password');
        $this->line('');
        $this->info('Ahora puedes iniciar sesión y acceder a aprobaciones.');

        return 0;
    }
}
