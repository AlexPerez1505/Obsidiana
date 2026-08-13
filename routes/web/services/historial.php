<?php

use App\Http\Controllers\Services\ServiceController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'approved'])
    ->prefix('gestion-servicios/historial-servicios')
    ->group(function () {
        // Índice del historial de servicios
        Route::get('/', [ServiceController::class, 'index'])
            ->name('gestion.servicios.historial');

        // Selección de tipo de servicio (nuevo servicio)
        Route::get('/nuevo-servicio', [ServiceController::class, 'create'])
            ->name('gestion.servicios.nuevo');

        // Formularios de registro según tipo de mantenimiento
        Route::get('/nuevo-servicio/interno', [ServiceController::class, 'createInternal'])
            ->name('gestion.servicios.nuevo.interno');
        Route::get('/nuevo-servicio/interno/equipo', [ServiceController::class, 'createInternalEquipment'])
            ->name('gestion.servicios.nuevo.interno.equipo');
        Route::post('/nuevo-servicio/interno/equipo', [ServiceController::class, 'storeInternalEquipment'])
            ->name('gestion.servicios.nuevo.interno.equipo.store');
        Route::get('/nuevo-servicio/interno/tecnico', [ServiceController::class, 'createInternalTechnician'])
            ->name('gestion.servicios.nuevo.interno.tecnico');
        Route::post('/nuevo-servicio/interno/tecnico', [ServiceController::class, 'storeInternalTechnician'])
            ->name('gestion.servicios.nuevo.interno.tecnico.store');
        Route::post('/nuevo-servicio/interno/cotizacion', [ServiceController::class, 'createInternalCotizacion'])
            ->name('gestion.servicios.nuevo.interno.cotizacion');
        Route::post('/nuevo-servicio/interno/guardar', [ServiceController::class, 'storeInternalService'])
            ->name('gestion.servicios.nuevo.interno.guardar');
        Route::get('/nuevo-servicio/interno/resumen/{service}', [ServiceController::class, 'showInternalSummary'])
            ->name('gestion.servicios.nuevo.interno.resumen');

        Route::get('/nuevo-servicio/externo', [ServiceController::class, 'createExternal'])
            ->name('gestion.servicios.nuevo.externo');

        // Paso 2: Registro del equipo
        Route::get('/nuevo-servicio/externo/equipo', [ServiceController::class, 'createEquipment'])
            ->name('gestion.servicios.nuevo.externo.equipo');
        Route::post('/nuevo-servicio/externo/equipo', [ServiceController::class, 'storeEquipment'])
            ->name('gestion.servicios.nuevo.externo.equipo.store');

        // Paso 3: Selección del técnico
        Route::get('/nuevo-servicio/externo/tecnico', [ServiceController::class, 'createTechnician'])
            ->name('gestion.servicios.nuevo.externo.tecnico');
        Route::post('/nuevo-servicio/externo/tecnico', [ServiceController::class, 'storeTechnician'])
            ->name('gestion.servicios.nuevo.externo.tecnico.store');
        Route::post('/nuevo-servicio/externo/guardar', [ServiceController::class, 'storeService'])
            ->name('gestion.servicios.nuevo.externo.guardar');

        // Resumen de orden tras guardar
        Route::get('/nuevo-servicio/externo/resumen/{service}', [ServiceController::class, 'showSummary'])
            ->name('gestion.servicios.nuevo.externo.resumen');

        // Eliminar orden de servicio
        Route::delete('/{service}', [ServiceController::class, 'destroy'])
            ->name('gestion.servicios.destroy');

        // Completar paso actual desde modal de mantenimiento
        Route::post('/{service}/complete-step', [ServiceController::class, 'completeCurrentStep'])
            ->name('gestion.servicios.completeStep');

        // Visualizar ruta de trabajo del servicio
        Route::get('/{service}/ruta-trabajo', [ServiceController::class, 'rutaTrabajo'])
            ->name('gestion.servicios.ruta');
    });

// Cartas de garantía
Route::middleware(['auth', 'verified', 'approved'])
    ->prefix('gestion-servicios')
    ->group(function () {
        Route::get('/cartas-garantia', function () {
            $cartas = \App\Models\CartaGarantia::with(['tipoEquipo', 'subtipo'])
                ->orderByDesc('created_at')
                ->get();

            return view('structure.gestion_servicios.Cartas_garantia.menu_carta', compact('cartas'));
        })->name('cartas.garantia.index');

        Route::get('/cartas-garantia/crear', function () {
            $productos = \App\Models\Producto::all();

            return view('structure.gestion_servicios.Cartas_garantia.Cartas_form', compact('productos'));
        })->name('cartas.garantia.create');

        Route::post('/cartas-garantia', function (\Illuminate\Http\Request $request) {
            $data = $request->validate([
                'id_tipo_equipo' => 'required|exists:productos,id',
                'id_subtipo' => 'required|exists:productos,id',
                'nombre' => 'required|string|max:255',
                'archivo_carta' => 'required|file|mimes:pdf,doc,docx,jpg,jpeg,png,webp',
            ]);

            $data['archivo_carta'] = $request->file('archivo_carta')->store('cartas_garantia', 'public');

            \App\Models\CartaGarantia::create($data);

            return redirect()->route('cartas.garantia.index')->with('success', 'Carta de garantía guardada correctamente.');
        })->name('cartas.garantia.store');
    });

// Refacciones
Route::middleware(['auth', 'verified', 'approved'])
    ->prefix('gestion-servicios/refacciones')
    ->group(function () {
        Route::get('/', function () {
            $refacciones = \App\Models\Refaccion::orderByDesc('created_at')->paginate(10);

            return view('structure.gestion_servicios.Refacciones.Menu_Tabla', compact('refacciones'));
        })->name('refacciones.index');

        Route::get('/crear', function () {
            return view('structure.gestion_servicios.Refacciones.Formulario');
        })->name('refacciones.create');

        Route::post('/', function (\Illuminate\Http\Request $request) {
            $data = $request->validate([
                'subtype' => ['required', 'string', 'max:255'],
                'name' => ['required', 'string', 'max:255'],
                'description' => ['nullable', 'string'],
                'stock' => ['nullable', 'integer', 'min:0'],
                'compatible_with' => ['nullable', 'string'],
                'price' => ['nullable', 'numeric', 'min:0'],
                'photo' => ['nullable', 'image', 'max:2048'],
            ]);

            if ($request->hasFile('photo')) {
                $data['photo'] = $request->file('photo')->store('refacciones', 'public');
            }

            \App\Models\Refaccion::create($data);

            return redirect()->route('refacciones.index')->with('success', 'Refacción guardada correctamente.');
        })->name('refacciones.store');

        Route::delete('/{refaccion}', function (\App\Models\Refaccion $refaccion) {
            $refaccion->delete();

            return redirect()->route('refacciones.index')->with('success', 'Refacción eliminada correctamente.');
        })->name('refacciones.destroy');
    });
