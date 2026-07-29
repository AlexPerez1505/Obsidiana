<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class MakeAdmin extends Command
{
    protected $signature = 'user:make-admin {email}';

    protected $description = 'Convierte a un usuario en administrador por su correo';

    public function handle(): int
    {
        $user = User::where('email', $this->argument('email'))->first();

        if (! $user) {
            $this->error("No existe un usuario con el correo {$this->argument('email')}.");
            return self::FAILURE;
        }

        $user->forceFill([
            'is_admin'    => true,
            'status'      => User::STATUS_APPROVED,
            'approved_at' => now(),
        ])->save();

        $this->info("{$user->name} ({$user->email}) ahora es administrador (con acceso aprobado).");
        return self::SUCCESS;
    }
}
