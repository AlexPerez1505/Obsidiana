<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Cartas de garantía
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified', 'approved'])->group(function () {
    Route::get('/configuracion/cartas', function () {
        return view('structure.Configuracion.Cartas.menu_cartas', [
            'cartas' => \App\Models\Carta::latest()->paginate(10),
            'totalCartas' => \App\Models\Carta::count(),
            'totalConArchivo' => \App\Models\Carta::whereNotNull('file_path')->count(),
            'totalTipos' => \App\Models\Carta::distinct('equipment_type_name')->count('equipment_type_name'),
            'totalRefacciones' => \App\Models\Carta::distinct('refaccion_name')->count('refaccion_name'),
        ]);
    })->name('configuracion.cartas.index');

    Route::get('/configuracion/cartas/crear', function () {
        return view('structure.Configuracion.Cartas.c_cartas', [
            'equipmentTypes' => \App\Models\EquipmentType::orderBy('name')->pluck('name'),
            'refacciones' => \App\Models\Refaccion::orderBy('name')->pluck('name'),
        ]);
    })->name('configuracion.cartas.create');

    Route::post('/configuracion/cartas', function (\Illuminate\Http\Request $request) {
        $data = $request->validate([
            'equipment_type_name' => ['required', 'string', 'max:255'],
            'subtype_name' => ['required', 'string', 'max:255'],
            'refaccion_name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'archivo' => ['nullable', 'file', 'mimes:jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx,txt', 'max:5120'],
        ]);

        $filePath = null;
        $fileName = null;

        if ($request->hasFile('archivo')) {
            $file = $request->file('archivo');
            $fileName = $file->getClientOriginalName();
            $filePath = $file->store('cartas', 'public');
        }

        \App\Models\Carta::create([
            'equipment_type_name' => $data['equipment_type_name'],
            'subtype_name' => $data['subtype_name'],
            'refaccion_name' => $data['refaccion_name'],
            'description' => $data['description'] ?? null,
            'file_path' => $filePath,
            'file_name' => $fileName,
        ]);

        return redirect()->route('configuracion.cartas.index')
            ->with('status', 'Carta de garantía guardada correctamente.');
    })->name('configuracion.cartas.store');

    Route::get('/configuracion/cartas/{carta}/ver', function (\App\Models\Carta $carta) {
        if (! $carta->file_path || ! \Illuminate\Support\Facades\Storage::disk('public')->exists($carta->file_path)) {
            abort(404);
        }

        return \Illuminate\Support\Facades\Storage::disk('public')->response($carta->file_path);
    })->name('configuracion.cartas.ver');

    Route::get('/configuracion/cartas/{carta}/descargar', function (\App\Models\Carta $carta) {
        if (! $carta->file_path || ! \Illuminate\Support\Facades\Storage::disk('public')->exists($carta->file_path)) {
            abort(404);
        }

        return \Illuminate\Support\Facades\Storage::disk('public')->download($carta->file_path, $carta->file_name);
    })->name('configuracion.cartas.descargar');

    Route::delete('/configuracion/cartas/{carta}', function (\App\Models\Carta $carta) {
        if ($carta->file_path) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($carta->file_path);
        }
        $carta->delete();

        return redirect()->route('configuracion.cartas.index')
            ->with('status', 'Carta de garantía eliminada correctamente.');
    })->name('configuracion.cartas.destroy');
});
