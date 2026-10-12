<?php

namespace App\Services\Servicios;

use App\Models\GarantiaDocumento;
use Illuminate\Http\Request;

/**
 * Alta de cartas de garantía (el PDF o las fotos de la carta que da el
 * fabricante por tipo de equipo). Web y API las registran igual.
 */
class CartasDeGarantia
{
    public function registrar(Request $request): GarantiaDocumento
    {
        $data = $request->validate([
            'nombre' => 'required|string|max:255',
            'tipo_equipo' => 'required|string|max:255',
            'archivos' => 'nullable|array|max:20',
            'archivos.*' => 'file|max:20480',
        ], [
            'nombre.required' => 'El nombre de la carta es obligatorio.',
            'tipo_equipo.required' => 'El tipo de equipo es obligatorio.',
            'archivos.*.file' => 'Cada archivo debe ser un archivo válido.',
            'archivos.*.max' => 'Cada archivo no debe superar los 20 MB.',
        ]);

        $paths = [];
        if ($request->hasFile('archivos')) {
            foreach ($request->file('archivos') as $archivo) {
                $paths[] = $archivo->store('garantias', 'public');
            }
        }

        return GarantiaDocumento::create([
            'folio' => GarantiaDocumento::siguienteFolio(),
            'nombre' => $data['nombre'],
            'tipo_equipo' => $data['tipo_equipo'],
            'archivos' => $paths,
        ]);
    }
}
