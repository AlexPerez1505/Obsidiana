<?php
// Detecta migraciones que tocan una tabla antes de que exista.
$dir = __DIR__.'/../database/migrations';
$files = glob($dir.'/*.php');
sort($files);

$created = [];   // tabla => file orden
$problems = [];

foreach ($files as $i => $f) {
    $code = file_get_contents($f);
    $name = basename($f);

    // tablas que crea
    if (preg_match_all("/Schema::create\('([a-zA-Z0-9_]+)'/", $code, $m)) {
        foreach ($m[1] as $t) $created[$t] = $i;
    }

    // tablas que altera (Schema::table)
    if (preg_match_all("/Schema::table\('([a-zA-Z0-9_]+)'/", $code, $m)) {
        foreach ($m[1] as $t) {
            if (!isset($created[$t])) {
                // puede haberse creado en ESTA misma migracion antes del alter
                $problems[] = "$name :: Schema::table('$t') antes de create (pos $i vs ".(isset($created[$t])?$created[$t]:'nunca').")";
            }
        }
    }

    // FKs a tablas
    if (preg_match_all("/constrained\('([a-zA-Z0-9_]+)'\)/", $code, $m)) {
        foreach ($m[1] as $t) {
            if (!isset($created[$t])) {
                $problems[] = "$name :: FK a '$t' que aun no existe (pos $i)";
            }
        }
    }
}

// Tablas base conocidas que existen desde siempre (creadas fuera de estas migraciones o muy al inicio)
$base = ['users','clientes','productos','equipos','inventory_movements','venta_items','refacciones','equipment_types','subtypes','brands','equipment_models','customers','service_steps','services','external_technicians','agenda_events','vehicles','tasks','congresses','cotizaciones','ventas','facturas','paquetes','garantia_documentos','material_requests','migrations','sessions','cache','jobs','personal_access_tokens','password_reset_tokens','failed_jobs','job_batches','notifications'];
foreach ($problems as $p) {
    $skip = false;
    foreach ($base as $b) { if (str_contains($p, "'$b'")) { $skip = true; break; } }
    if (!$skip) echo $p, "\n";
}
echo "---- done ----\n";
