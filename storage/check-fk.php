<?php
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "== FKs on services (obsidiana) ==\n";
print_r(DB::select("SELECT COLUMN_NAME, REFERENCED_TABLE_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA='obsidiana' AND TABLE_NAME='services' AND REFERENCED_TABLE_NAME IS NOT NULL"));

echo "== customer table used by model ==\n";
echo (new App\Models\Customer)->getTable(), "\n";

echo "== migrations ran? create_services ==\n";
print_r(DB::table('migrations')->where('migration', 'like', '%services%')->orWhere('migration','like','%clientes%')->pluck('migration'));
