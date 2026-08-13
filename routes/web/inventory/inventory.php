<?php

use App\Http\Controllers\Inventory\EquipoController;
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

    Route::get('/gestion-inventario/entrada-salida', function () {
        $movements = \App\Models\InventoryMovement::with('creator')
            ->latest()
            ->get()
            ->map(fn ($movement) => [
                'date' => $movement->movement_date?->format('d/m/Y') ?? '-',
                'type' => ucfirst($movement->movement_type),
                'tone' => match ($movement->movement_type) {
                    'entrada' => 'green',
                    'salida' => 'red',
                    'transferencia' => 'blue',
                },
                'folio' => $movement->folio,
                'warehouse' => $movement->warehouse,
                'product' => $movement->item_name,
                'quantity' => $movement->quantity . ' ' . $movement->unit,
                'reference' => $movement->reference,
                'metadata' => $movement->metadata,
                'movement_type' => $movement->movement_type,
            ]);

        return view('structure.gestion_Inventario.entrada_salida.index', [
            'movements' => $movements,
            'total' => $movements->count(),
        ]);
    })->name('inventory.movimientos.index');

    Route::get('/gestion-inventario/entrada-salida/crear', function () {
        return view('structure.gestion_Inventario.entrada_salida.create', [
            'equipos' => \App\Models\Producto::orderBy('tipo_equipo')->get(['id', 'tipo_equipo', 'marca', 'modelo', 'no_serie']),
            'warehouses' => ['Almacen Central', 'Quirofano 1', 'Taller'],
        ]);
    })->name('inventory.movimientos.create');

    Route::post('/gestion-inventario/entrada-salida', function (\Illuminate\Http\Request $request) {
        $validated = $request->validate([
            'movement_type' => ['required', 'in:entrada,salida,transferencia'],
            'producto_id' => ['required', 'exists:productos,id'],
            'warehouse' => ['required', 'string', 'max:120'],
            'movement_date' => ['required', 'date'],
            'reference' => ['nullable', 'string', 'max:255'],
            'supplier' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'metadata' => ['nullable', 'array'],
        ]);

        $producto = \App\Models\Producto::findOrFail($validated['producto_id']);
        $prefix = match ($validated['movement_type']) {
            'entrada' => 'EN',
            'salida' => 'SA',
            'transferencia' => 'TR',
        };
        $folio = $prefix . '-' . now()->format('YmdHisu');

        \App\Models\InventoryMovement::create([
            'folio' => $folio,
            'movement_type' => $validated['movement_type'],
            'item_type' => 'equipo',
            'item_id' => $producto->id,
            'item_code' => $producto->no_serie,
            'item_name' => trim(($producto->tipo_equipo ?? '') . ' - ' . ($producto->marca ?? '') . ' ' . ($producto->modelo ?? '')),
            'warehouse' => $validated['warehouse'],
            'quantity' => 1,
            'unit' => 'Pza',
            'reference' => $validated['reference'],
            'supplier' => $validated['supplier'],
            'movement_date' => $validated['movement_date'],
            'notes' => $validated['notes'],
            'metadata' => $validated['metadata'] ?? [],
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('inventory.movimientos.index')->with('status', 'Movimiento registrado correctamente.');
    })->name('inventory.movimientos.store');

    Route::get('/gestion-inventario/equipos', [EquipoController::class, 'index'])
        ->name('inventory.equipos.index');
    Route::get('/gestion-inventario/equipos/crear', [EquipoController::class, 'create'])
        ->name('inventory.equipos.create');
    Route::get('/gestion-inventario/equipos/siguiente-serie-base', [EquipoController::class, 'nextBaseSerial'])
        ->name('inventory.equipos.next-base-serial');
    Route::post('/gestion-inventario/equipos', [EquipoController::class, 'store'])
        ->name('inventory.equipos.store');
    Route::get('/gestion-inventario/equipos/{equipo}/detalle', [EquipoController::class, 'show'])
        ->name('inventory.equipos.show');
    Route::get('/gestion-inventario/equipos/{equipo}/qr-imagen', [EquipoController::class, 'qrImage'])
        ->name('inventory.equipos.qrImage');
    Route::get('/gestion-inventario/equipos/{equipo}/editar', [EquipoController::class, 'edit'])
        ->name('inventory.equipos.edit');
    Route::put('/gestion-inventario/equipos/{equipo}', [EquipoController::class, 'update'])
        ->name('inventory.equipos.update');
    Route::delete('/gestion-inventario/equipos/{equipo}', [EquipoController::class, 'destroy'])
        ->name('inventory.equipos.destroy');

    // Productos (stock real, contra base de datos)
    Route::get('/gestion-inventario/productos', [ProductoController::class, 'index'])
        ->name('inventory.productos.index');
    Route::get('/gestion-inventario/productos/crear', [ProductoController::class, 'create'])
        ->name('inventory.productos.create');
    Route::post('/gestion-inventario/productos', [ProductoController::class, 'store'])
        ->name('inventory.productos.store');
    Route::get('/gestion-inventario/productos/{producto}/editar', [ProductoController::class, 'edit'])
        ->name('inventory.productos.edit');
    Route::put('/gestion-inventario/productos/{producto}', [ProductoController::class, 'update'])
        ->name('inventory.productos.update');
    Route::delete('/gestion-inventario/productos/{producto}', [ProductoController::class, 'destroy'])
        ->name('inventory.productos.destroy');

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

// Vista pública de equipo (escaneo de QR sin autenticación)
Route::get('/equipo/{equipo}', [EquipoController::class, 'publicShow'])
    ->name('inventory.equipos.public');
