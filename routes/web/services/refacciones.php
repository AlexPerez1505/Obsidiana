<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use App\Models\Refaccion;
use App\Models\Subtype;

Route::middleware(['auth', 'verified', 'approved'])->group(function () {
    Route::get('/gestion-servicios/refacciones', function () {
        $refacciones = Refaccion::latest()->paginate(15);
        $totalRefacciones = Refaccion::count();
        $totalStock = Refaccion::sum('stock');
        $totalSubtypes = Refaccion::distinct('subtype')->count('subtype');
        $totalCompatible = Refaccion::whereNotNull('compatible_with')->where('compatible_with', '!=', '')->count();

        return view('structure.gestion_servicios.Refacciones.Tabla', compact('refacciones', 'totalRefacciones', 'totalStock', 'totalSubtypes', 'totalCompatible'));
    })->name('gestion.servicios.refacciones.index');

    Route::get('/gestion-servicios/refacciones/crear', function () {
        return view('structure.gestion_servicios.Refacciones.Formulario', [
            'subtypes' => Subtype::orderBy('name')->pluck('name'),
        ]);
    })->name('gestion.servicios.refacciones.create');

    Route::post('/gestion-servicios/refacciones', function (Request $request) {
        $data = $request->validate([
            'subtype_name' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'stock' => ['nullable', 'integer', 'min:0'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'compatible_with' => ['nullable', 'string', 'max:255'],
            'photo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
        ]);

        if ($request->hasFile('photo')) {
            $data['photo_path'] = Storage::disk('public')->putFile('refacciones', $request->file('photo'));
        }

        $data['subtype'] = $data['subtype_name'];
        unset($data['subtype_name']);

        Refaccion::create($data);

        return redirect()->route('gestion.servicios.refacciones.index')
            ->with('success', 'Refacción guardada correctamente.');
    })->name('gestion.servicios.refacciones.store');

    Route::delete('/gestion-servicios/refacciones/{refaccion}', function (Refaccion $refaccion) {
        if ($refaccion->photo_path) {
            Storage::disk('public')->delete($refaccion->photo_path);
        }
        $refaccion->delete();

        return redirect()->route('gestion.servicios.refacciones.index')
            ->with('success', 'Refacción eliminada correctamente.');
    })->name('gestion.servicios.refacciones.destroy');
});