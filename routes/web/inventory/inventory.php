<?php

use App\Http\Controllers\EquipmentController;
use App\Http\Controllers\PaqueteController;
use App\Http\Controllers\ProductoController;
use App\Models\InventoryMovement;
use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Gestión de Inventario
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified', 'approved'])->group(function () {
    Route::get('/gestion-inventario/entrada-salida', function () {
        $movements = InventoryMovement::with('creator')->latest()->get();

        return view('structure.gestion_Inventario.entrada_salida.index', [
            'movements' => $movements,
        ]);
    })->name('inventory.movimientos.index');

    Route::get('/gestion-inventario/entrada-salida/crear', function (Request $request) {
        $productos = Producto::orderBy('tipo_equipo')->get();
        $defaultType = in_array($request->query('type'), ['entrada', 'salida', 'transferencia'], true)
            ? $request->query('type')
            : '';

        return view('structure.gestion_Inventario.entrada_salida.create', [
            'productos' => $productos,
            'defaultType' => $defaultType,
        ]);
    })->name('inventory.movimientos.create');

    Route::post('/gestion-inventario/entrada-salida', function (Request $request) {
        $data = $request->validate([
            'movement_type' => ['required', 'in:entrada,salida,transferencia'],
            'movement_date' => ['required', 'date'],
            'warehouse' => ['required', 'string', 'max:100'],
            'item_id' => ['required', 'exists:productos,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'unit' => ['required', 'string', 'max:20'],
            'unit_cost' => ['nullable', 'numeric', 'min:0'],
            'payment_method' => ['nullable', 'string', 'max:50'],
            'reference' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ]);

        $producto = Producto::findOrFail($data['item_id']);
        $stockBefore = $producto->stock;
        $quantity = (int) $data['quantity'];

        $stockAfter = match ($data['movement_type']) {
            'entrada' => $stockBefore + $quantity,
            'salida' => max(0, $stockBefore - $quantity),
            'transferencia' => $stockBefore,
            default => $stockBefore,
        };

        $next = ((int) InventoryMovement::withTrashed()->max('id')) + 1;
        $folio = 'MOV-' . str_pad((string) $next, 4, '0', STR_PAD_LEFT);

        InventoryMovement::create([
            'folio' => $folio,
            'movement_type' => $data['movement_type'],
            'item_type' => InventoryMovement::ITEM_PRODUCT,
            'item_id' => $producto->id,
            'item_code' => $producto->no_serie ?: (string) $producto->id,
            'item_name' => $producto->tipo_equipo,
            'warehouse' => $data['warehouse'],
            'quantity' => $quantity,
            'unit' => $data['unit'],
            'stock_before' => $stockBefore,
            'stock_after' => $stockAfter,
            'reference' => $data['reference'] ?? $data['description'] ?? null,
            'supplier' => $data['reference'] ?? null,
            'movement_date' => $data['movement_date'],
            'notes' => $data['notes'],
            'metadata' => [
                'payment_method' => $data['payment_method'] ?? null,
                'unit_cost' => $data['unit_cost'] ?? null,
            ],
            'created_by' => auth()->id(),
        ]);

        if (in_array($data['movement_type'], ['entrada', 'salida'], true)) {
            $producto->update(['stock' => $stockAfter]);
        }

        return redirect()->route('inventory.movimientos.index')->with('status', 'Movimiento guardado correctamente.');
    })->name('inventory.movimientos.store');

    Route::get('/gestion-inventario/entrada-salida/{movimiento}', function (InventoryMovement $movimiento) {
        return view('structure.gestion_Inventario.entrada_salida.show', [
            'movement' => $movimiento,
        ]);
    })->name('inventory.movimientos.show');

    Route::post('/gestion-inventario/entrada-salida/{movimiento}/verificar-edicion', function (Request $request, InventoryMovement $movimiento) {
        $request->validate([
            'password' => ['required', 'string'],
        ]);

        if (! \Illuminate\Support\Facades\Hash::check($request->input('password'), auth()->user()->password)) {
            return back()->with('error', 'El PIN no es correcto.')->withInput();
        }

        return redirect()->route('inventory.movimientos.edit', $movimiento);
    })->name('inventory.movimientos.verify-edit');

    Route::get('/gestion-inventario/entrada-salida/{movimiento}/editar', function (InventoryMovement $movimiento) {
        $productos = Producto::orderBy('tipo_equipo')->get();

        return view('structure.gestion_Inventario.entrada_salida.edit', [
            'movement' => $movimiento,
            'productos' => $productos,
        ]);
    })->name('inventory.movimientos.edit');

    Route::put('/gestion-inventario/entrada-salida/{movimiento}', function (Request $request, InventoryMovement $movimiento) {
        $data = $request->validate([
            'movement_type' => ['required', 'in:entrada,salida,transferencia'],
            'movement_date' => ['required', 'date'],
            'warehouse' => ['required', 'string', 'max:100'],
            'quantity' => ['required', 'integer', 'min:1'],
            'unit' => ['required', 'string', 'max:20'],
            'unit_cost' => ['nullable', 'numeric', 'min:0'],
            'payment_method' => ['nullable', 'string', 'max:50'],
            'reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        $movimiento->update([
            'movement_type' => $data['movement_type'],
            'warehouse' => $data['warehouse'],
            'quantity' => $data['quantity'],
            'unit' => $data['unit'],
            'reference' => $data['reference'] ?? null,
            'movement_date' => $data['movement_date'],
            'notes' => $data['notes'],
            'metadata' => array_merge($movimiento->metadata ?? [], [
                'payment_method' => $data['payment_method'] ?? null,
                'unit_cost' => $data['unit_cost'] ?? null,
            ]),
        ]);

        return redirect()->route('inventory.movimientos.index')->with('status', 'Movimiento actualizado correctamente.');
    })->name('inventory.movimientos.update');

    Route::delete('/gestion-inventario/entrada-salida/{movimiento}', function (Request $request, InventoryMovement $movimiento) {
        $request->validate([
            'password' => ['required', 'string'],
        ]);

        if (! \Illuminate\Support\Facades\Hash::check($request->input('password'), auth()->user()->password)) {
            return back()->with('error', 'El PIN no es correcto. No se eliminó el movimiento.');
        }

        $movimiento->delete();

        return redirect()->route('inventory.movimientos.index')->with('status', 'Movimiento eliminado correctamente.');
    })->name('inventory.movimientos.destroy');

    Route::get('/gestion-inventario/equipos', [EquipmentController::class, 'index'])
        ->name('inventory.equipos.index');
    Route::get('/gestion-inventario/equipos/crear', [EquipmentController::class, 'create'])
        ->name('inventory.equipos.create');
    Route::post('/gestion-inventario/equipos', [EquipmentController::class, 'store'])
        ->name('inventory.equipos.store');
    Route::get('/gestion-inventario/equipos/{equipo}/editar', [EquipmentController::class, 'edit'])
        ->name('inventory.equipos.edit');
    Route::put('/gestion-inventario/equipos/{equipo}', [EquipmentController::class, 'update'])
        ->name('inventory.equipos.update');
    Route::delete('/gestion-inventario/equipos/{equipo}', [EquipmentController::class, 'destroy'])
        ->name('inventory.equipos.destroy');
    Route::get('/gestion-inventario/equipos/{equipo}/detalle', [EquipmentController::class, 'show'])
        ->name('inventory.equipos.show');
    Route::get('/gestion-inventario/equipos/{equipo}/descargar', [EquipmentController::class, 'download'])
        ->name('inventory.equipos.download');

    // Productos (stock real, contra base de datos)
    Route::get('/gestion-inventario/productos', [ProductoController::class, 'index'])
        ->name('inventory.productos.index');
    Route::get('/gestion-inventario/productos/crear', [ProductoController::class, 'create'])
        ->name('inventory.productos.create');
    Route::post('/gestion-inventario/productos', [ProductoController::class, 'store'])
        ->name('inventory.productos.store');
    Route::post('/gestion-inventario/productos/sincronizar', [ProductoController::class, 'sync'])
        ->name('inventory.productos.sync');
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
