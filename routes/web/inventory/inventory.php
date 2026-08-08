<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Gestión de Inventario
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified', 'approved'])->group(function () {
    $equipmentCatalog = static function (): array {
        return [
            'PRO-0001' => [
                'code' => 'PRO-0001',
                'name' => 'Torre de endoscopia',
                'category' => 'Endoscopia',
                'serial_number' => 'TE-2026-001',
                'brand' => 'Olimpus',
                'model' => 'EVIS EXERA III',
                'description' => 'Torre de endoscopia para procedimientos diagnosticos y terapeuticos',
                'stock_current' => 1,
                'stock_max' => 2,
                'stock_min' => 1,
                'warehouse' => 'Quirofano 1',
                'assigned_to' => 'Ing. Joel Diaz',
                'department' => 'Endoscopia',
                'service_date' => '2026-07-27',
                'next_maintenance' => '2026-10-27',
                'notes' => 'Equipo activo en quirofano 1.',
                'voltage' => '127 V',
                'frequency' => '60 Hz',
                'power' => '800 W',
                'weight' => '38',
                'dimensions' => '70 x 60 x 140',
                'color' => 'Blanco',
                'technical_specs' => 'Monitor, procesador, fuente de luz y carro movil.',
                'supplier' => 'Olimpus Mexico S.A. de C.V',
                'contact' => 'Soporte tecnico',
                'phone' => '555-0101',
                'email' => 'soporte@olimpus.mx',
                'invoice_number' => 'FAC-000125',
                'invoice_date' => '2026-07-27',
                'thumb' => 'tower',
            ],
            'PRO-0002' => [
                'code' => 'PRO-0002',
                'name' => 'Torre de endoscopia',
                'category' => 'Endoscopia',
                'serial_number' => 'TE-2026-002',
                'brand' => 'Olimpus',
                'model' => 'EVIS LUCERA',
                'description' => 'Equipo programado para mantenimiento preventivo',
                'stock_current' => 1,
                'stock_max' => 2,
                'stock_min' => 1,
                'warehouse' => 'Quirofano 1',
                'assigned_to' => 'Ing. Joel Diaz',
                'department' => 'Endoscopia',
                'service_date' => '2026-06-18',
                'next_maintenance' => '2026-09-18',
                'notes' => 'Requiere revision de monitor.',
                'voltage' => '127 V',
                'frequency' => '60 Hz',
                'power' => '750 W',
                'weight' => '35',
                'dimensions' => '68 x 58 x 138',
                'color' => 'Blanco',
                'technical_specs' => 'Monitor y procesador con accesorios principales.',
                'supplier' => 'Olimpus Mexico S.A. de C.V',
                'contact' => 'Mesa de ayuda',
                'phone' => '555-0102',
                'email' => 'servicio@olimpus.mx',
                'invoice_number' => 'FAC-000126',
                'invoice_date' => '2026-06-18',
                'thumb' => 'monitor',
            ],
        ];
    };

    $findEquipment = static function (string $equipo) use ($equipmentCatalog): array {
        $equipmentRows = $equipmentCatalog();

        return $equipmentRows[$equipo] ?? array_merge($equipmentRows['PRO-0001'], [
            'code' => $equipo,
        ]);
    };

    $productCatalog = static function (): array {
        return [
            'PRO-0001' => [
                'code' => 'PRO-0001',
                'name' => 'Endoscopia flexible',
                'category' => 'Endoscopia',
                'unit' => 'Pza',
                'brand' => 'Olimpus',
                'model' => 'GIF-HQ190',
                'description' => 'Endoscopia flexible de alta definicion para procedimientos diagnosticos',
                'stock_current' => 8,
                'stock_max' => 3,
                'stock_min' => 1,
                'warehouse' => 'Almacen Central',
                'type' => 'Endoscopio',
                'technical_category' => 'Diagnostico',
                'specifications' => 'Alta definicion, canal de trabajo',
                'supplier' => 'Olimpus Mexico S.A. de C.V',
                'supplier_code' => 'OLY-GIF-HQ190',
                'location' => 'Almacen Central',
                'warranty' => '12 meses',
                'notes' => 'Producto activo para procedimientos diagnosticos.',
                'status' => 'Activo',
                'thumb' => 'scope',
            ],
            'PRO-0002' => [
                'code' => 'PRO-0002',
                'name' => 'Endoscopia flexible',
                'category' => 'Endoscopia',
                'unit' => 'Pza',
                'brand' => 'Olimpus',
                'model' => 'GIF-XP190',
                'description' => 'Equipo en mantenimiento preventivo',
                'stock_current' => 2,
                'stock_max' => 4,
                'stock_min' => 1,
                'warehouse' => 'Almacen Central',
                'type' => 'Endoscopio',
                'technical_category' => 'Diagnostico',
                'specifications' => 'Formato flexible',
                'supplier' => 'Olimpus Mexico S.A. de C.V',
                'supplier_code' => 'OLY-XP190',
                'location' => 'Quirofano 1',
                'warranty' => '6 meses',
                'notes' => 'Pendiente de revision tecnica.',
                'status' => 'Mantenimiento',
                'thumb' => 'probe',
            ],
        ];
    };

    $findProduct = static function (string $producto) use ($productCatalog): array {
        $products = $productCatalog();

        return $products[$producto] ?? array_merge($products['PRO-0001'], [
            'code' => $producto,
        ]);
    };

    Route::get('/gestion-inventario/entrada-salida', function () {
        return view('structure.gestion_Inventario.entrada_salida.index');
    })->name('inventory.movimientos.index');

    Route::get('/gestion-inventario/entrada-salida/crear', function () {
        return view('structure.gestion_Inventario.entrada_salida.create');
    })->name('inventory.movimientos.create');

    Route::get('/gestion-inventario/equipos', function () {
        return view('structure.gestion_Inventario.equipos.menu_equipos');
    })->name('inventory.equipos.index');

    Route::get('/gestion-inventario/equipos/crear', function () {
        return view('structure.gestion_Inventario.equipos.c_equipos');
    })->name('inventory.equipos.create');

    Route::get('/gestion-inventario/equipos/{equipo}/editar', function (string $equipo) use ($findEquipment) {
        return view('structure.gestion_Inventario.equipos.c_equipos', [
            'mode' => 'edit',
            'equipment' => $findEquipment($equipo),
        ]);
    })->name('inventory.equipos.edit');

    Route::get('/gestion-inventario/equipos/{equipo}/detalle', function (string $equipo) use ($findEquipment) {
        return view('structure.gestion_Inventario.equipos.detalle_equipo', [
            'equipment' => $findEquipment($equipo),
        ]);
    })->name('inventory.equipos.show');

    Route::get('/gestion-inventario/productos', function () {
        return view('structure.gestion_Inventario.equipos.menu_productos');
    })->name('inventory.productos.index');

    Route::get('/gestion-inventario/productos/crear', function () {
        return view('structure.gestion_Inventario.equipos.c_productos');
    })->name('inventory.productos.create');

    Route::get('/gestion-inventario/productos/{producto}/editar', function (string $producto) use ($findProduct) {
        return view('structure.gestion_Inventario.equipos.c_productos', [
            'mode' => 'edit',
            'product' => $findProduct($producto),
        ]);
    })->name('inventory.productos.edit');

    Route::get('/gestion-inventario/productos/{producto}/detalle', function (string $producto) use ($findProduct) {
        return view('structure.gestion_Inventario.equipos.detalle_producto', [
            'product' => $findProduct($producto),
        ]);
    })->name('inventory.productos.show');

    Route::get('/gestion-inventario/stock', function () {
        return view('structure.gestion_Inventario.stock.index');
    })->name('inventory.stock.index');
});
