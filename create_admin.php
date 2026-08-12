<?php

// Cargar el autoloader de Composer
require __DIR__ . '/vendor/autoload.php';

// Cargar la aplicación Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';

// Hacer que la aplicación esté lista
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;

echo "=== Crear Usuario Administrador ===\n\n";

try {
    // Verificar si el usuario ya existe
    $userExists = User::where('email', 'admin@obsidiana.local')->exists();

    if ($userExists) {
        echo "⚠️  El usuario admin@obsidiana.local ya existe.\n";
        exit(0);
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

    echo "✓ Usuario administrador creado exitosamente\n\n";
    echo "Credenciales:\n";
    echo "  Email: admin@obsidiana.local\n";
    echo "  Contraseña: password\n\n";
    echo "Ahora puedes iniciar sesión y acceder a aprobaciones.\n";

} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
