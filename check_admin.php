<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;

// Obtener el usuario autenticado (si lo hay)
$user = auth()->user();

if (!$user) {
    echo "No hay usuario autenticado.\n";
    echo "\nUsuarios en la base de datos:\n";
    $users = User::all();
    foreach ($users as $u) {
        echo "- ID: {$u->id}, Nombre: {$u->name}, Admin: " . ($u->is_admin ? 'SÍ' : 'NO') . "\n";
    }
} else {
    echo "Usuario actual: {$user->name}\n";
    echo "Es admin: " . ($user->isAdmin() ? 'SÍ' : 'NO') . "\n";
    echo "is_admin field: " . ($user->is_admin ? 'true' : 'false') . "\n";
}
