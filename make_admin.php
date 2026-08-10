<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\DB;

echo "=== Hacer usuario administrador ===\n\n";

// Obtener el primer usuario (o el más reciente)
$user = User::orderByDesc('created_at')->first();

if (!$user) {
    echo "❌ No hay usuarios en la base de datos.\n";
    exit(1);
}

echo "Usuario encontrado: {$user->name} (ID: {$user->id})\n";
echo "Estado actual: " . ($user->is_admin ? "Admin ✓" : "No es admin") . "\n\n";

// Hacer admin
$user->update(['is_admin' => true]);

echo "✓ Usuario actualizado a administrador.\n";
echo "Ahora {$user->name} puede acceder a las aprobaciones.\n";
