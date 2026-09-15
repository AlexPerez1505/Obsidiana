<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Support\DashboardWidgets;

$user = User::where('email', 'marketing@obsidiana.com')->first();
$activas = DashboardWidgets::paraUsuario($user);
echo "widgets: " . json_encode($activas) . "\n";

foreach ($activas as $w) {
    $datos = DashboardWidgets::datos($w['id'], $user, $w['w'], $w['h']);
    echo "{$w['id']} -> OK\n";
}
