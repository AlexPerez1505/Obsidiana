<?php

namespace App\Http\Controllers\Configuracion;

use App\Http\Controllers\Controller;
use App\Models\Refaccion;
use App\Services\Almacen\Refacciones;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Refacciones (Configuración). Antes vivía en closures del archivo de
 * rutas; se movió aquí para que la API use la misma lógica.
 */
class RefaccionController extends Controller
{
    public function __construct(private readonly Refacciones $refacciones)
    {
    }

    public function index(): View
    {
        return view('structure.Configuracion.refaciones.menu_refaciones', [
            'refacciones' => Refaccion::latest()->paginate(10),
        ] + $this->refacciones->resumen());
    }

    public function create(): View
    {
        return view('structure.Configuracion.refaciones.c_refaciones', [
            'subtypes' => $this->refacciones->subtipos(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(Refacciones::reglas());

        $data['subtype'] = $data['subtype_name'];
        $this->refacciones->crear($data);

        return redirect()->route('configuracion.refaciones.index')
            ->with('status', 'Refacción guardada correctamente.');
    }

    public function destroy(Refaccion $refaccion): RedirectResponse
    {
        $refaccion->delete();

        return redirect()->route('configuracion.refaciones.index')
            ->with('status', 'Refacción eliminada correctamente.');
    }
}
