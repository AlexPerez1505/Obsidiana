<?php

use App\Http\Controllers\Inventory\FichaTecnicaController;
use App\Http\Controllers\InventoryMovementController;
use App\Http\Controllers\PaqueteController;
use App\Http\Controllers\ProductoController;
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

    Route::get('/gestion-inventario/entrada-salida', [InventoryMovementController::class, 'index'])
        ->name('inventory.movimientos.index');
    Route::get('/gestion-inventario/entrada-salida/crear', [InventoryMovementController::class, 'create'])
        ->name('inventory.movimientos.create');
    Route::post('/gestion-inventario/entrada-salida', [InventoryMovementController::class, 'store'])
        ->name('inventory.movimientos.store');
    Route::get('/gestion-inventario/entrada-salida/{movimiento}', [InventoryMovementController::class, 'show'])
        ->name('inventory.movimientos.show');
    Route::delete('/gestion-inventario/entrada-salida/{movimiento}', [InventoryMovementController::class, 'destroy'])
        ->name('inventory.movimientos.destroy');

    // NOTA: el listado y el alta de equipos viven en routes/web/inventory.php
    // (EquipoController). Aqui solo quedan el detalle y la edicion.

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

    // Productos (stock real, contra base de datos)
    Route::get('/gestion-inventario/productos', [ProductoController::class, 'index'])
        ->name('inventory.productos.index');
    Route::get('/gestion-inventario/productos/crear', [ProductoController::class, 'create'])
        ->name('inventory.productos.create');
    Route::get('/gestion-inventario/productos/buscar-por-modelo', [ProductoController::class, 'buscarPorModelo'])
        ->name('inventory.productos.buscarPorModelo');
    Route::post('/gestion-inventario/productos', [ProductoController::class, 'store'])
        ->name('inventory.productos.store');
    Route::get('/gestion-inventario/productos/{producto}/editar', [ProductoController::class, 'edit'])
        ->name('inventory.productos.edit');
    Route::put('/gestion-inventario/productos/{producto}', [ProductoController::class, 'update'])
        ->name('inventory.productos.update');
    Route::delete('/gestion-inventario/productos/{producto}', [ProductoController::class, 'destroy'])
        ->name('inventory.productos.destroy');
    Route::post('/gestion-inventario/productos/{producto}/seriales', [ProductoController::class, 'agregarSeriales'])
        ->name('inventory.productos.seriales.store');
    Route::delete('/gestion-inventario/productos/seriales/{serial}', [ProductoController::class, 'eliminarSerial'])
        ->name('inventory.productos.seriales.destroy');

    // Fichas técnicas (nombre + PDF)
    Route::get('/gestion-inventario/fichas-tecnicas', [FichaTecnicaController::class, 'index'])
        ->name('inventory.fichas.index');
    Route::get('/gestion-inventario/fichas-tecnicas/crear', [FichaTecnicaController::class, 'create'])
        ->name('inventory.fichas.create');
    Route::post('/gestion-inventario/fichas-tecnicas', [FichaTecnicaController::class, 'store'])
        ->name('inventory.fichas.store');
    Route::get('/gestion-inventario/fichas-tecnicas/{ficha}/editar', [FichaTecnicaController::class, 'edit'])
        ->name('inventory.fichas.edit');
    Route::put('/gestion-inventario/fichas-tecnicas/{ficha}', [FichaTecnicaController::class, 'update'])
        ->name('inventory.fichas.update');
    Route::delete('/gestion-inventario/fichas-tecnicas/{ficha}', [FichaTecnicaController::class, 'destroy'])
        ->name('inventory.fichas.destroy');
    Route::get('/gestion-inventario/fichas-tecnicas/{ficha}/descargar', [FichaTecnicaController::class, 'download'])
        ->name('inventory.fichas.download');

    // Paquetes (armados desde productos)
    Route::get('/gestion-inventario/paquetes', [PaqueteController::class, 'index'])
        ->name('inventory.paquetes.index');
    Route::get('/gestion-inventario/paquetes/crear', [PaqueteController::class, 'create'])
        ->name('inventory.paquetes.create');
    Route::post('/gestion-inventario/paquetes', [PaqueteController::class, 'store'])
        ->name('inventory.paquetes.store');
    Route::get('/gestion-inventario/paquetes/{paquete}/editar', [PaqueteController::class, 'edit'])
        ->name('inventory.paquetes.edit');
    Route::put('/gestion-inventario/paquetes/{paquete}', [PaqueteController::class, 'update'])
        ->name('inventory.paquetes.update');
    Route::delete('/gestion-inventario/paquetes/{paquete}', [PaqueteController::class, 'destroy'])
        ->name('inventory.paquetes.destroy');
});
