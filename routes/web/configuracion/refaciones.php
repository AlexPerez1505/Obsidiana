<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Refacciones
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified', 'approved'])->group(function () {
    Route::get('/configuracion/refaciones', function () {
        return view('structure.Configuracion.refaciones.menu_refaciones', [
            'refacciones' => \App\Models\Refaccion::latest()->paginate(10),
            'totalRefacciones' => \App\Models\Refaccion::count(),
            'totalStock' => \App\Models\Refaccion::sum('stock'),
            'totalSubtypes' => \App\Models\Refaccion::distinct('subtype')->count('subtype'),
            'totalCompatible' => \App\Models\Refaccion::whereNotNull('compatible_with')->where('compatible_with', '!=', '')->count(),
        ]);
    })->name('configuracion.refaciones.index');

    Route::get('/configuracion/refaciones/crear', function () {
        return view('structure.Configuracion.refaciones.c_refaciones', [
            'subtypes' => \App\Models\Subtype::orderBy('name')->pluck('name'),
        ]);
    })->name('configuracion.refaciones.create');

    Route::post('/configuracion/refaciones', function (\Illuminate\Http\Request $request) {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'subtype_name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'stock' => ['nullable', 'integer', 'min:0'],
            'compatible_with' => ['nullable', 'string'],
        ]);

        \App\Models\Refaccion::create([
            'subtype' => $data['subtype_name'],
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'stock' => $data['stock'] ?? 0,
            'compatible_with' => $data['compatible_with'] ?? null,
        ]);

        return redirect()->route('configuracion.refaciones.index')
            ->with('status', 'Refacción guardada correctamente.');
    })->name('configuracion.refaciones.store');

    Route::delete('/configuracion/refaciones/{refaccion}', function (\App\Models\Refaccion $refaccion) {
        $refaccion->delete();

        return redirect()->route('configuracion.refaciones.index')
            ->with('status', 'Refacción eliminada correctamente.');
    })->name('configuracion.refaciones.destroy');
});
