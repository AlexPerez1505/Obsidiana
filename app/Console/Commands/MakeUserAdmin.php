<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class MakeUserAdmin extends Command
{
    protected $signature = 'user:make-admin {user_id? : ID del usuario}';
    protected $description = 'Hace un usuario administrador';

    public function handle()
    {
        $userId = $this->argument('user_id');

        if (!$userId) {
            // Si no se especifica ID, usar el usuario más reciente
            $user = User::orderByDesc('created_at')->first();
            if (!$user) {
                $this->error('No hay usuarios en la base de datos.');
                return 1;
            }
        } else {
            $user = User::find($userId);
            if (!$user) {
                $this->error("Usuario con ID {$userId} no encontrado.");
                return 1;
            }
        }

        $this->info("Usuario encontrado: {$user->name} (ID: {$user->id})");
        $this->info("Estado actual: " . ($user->is_admin ? "Admin ✓" : "No es admin"));

        if ($user->is_admin) {
            $this->warn('Este usuario ya es administrador.');
            return 0;
        }

        $user->update(['is_admin' => true]);

        $this->info("✓ Usuario {$user->name} ahora es administrador.");
        return 0;
    }
}
