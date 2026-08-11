<?php

namespace App\Http\Controllers;

use App\Models\PlanPagoPlantilla;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PlanPagoPlantillaController extends Controller
{
    /**
     * Lista las plantillas de plan de pago disponibles (ej. Mensual, 4 pagos, 5 pagos).
     */
    public function index(): View
    {
        return view('structure.commercial_management.planes_pago.index', [
            'planes' => PlanPagoPlantilla::orderBy('nombre')->get(),
        ]);
    }

    /**
     * Muestra el formulario de creación de una plantilla de plan de pago.
     */
    public function create(): View
    {
        return view('structure.commercial_management.planes_pago.create');
    }

    /**
     * Guarda una nueva plantilla de plan de pago.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'numero_pagos' => ['required', 'integer', 'min:1', 'max:60'],
            'dias_entre_pagos' => ['required', 'integer', 'min:1'],
            'metodo_pago' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string', 'max:255'],
        ]);

        PlanPagoPlantilla::create($data);

        return redirect()->route('commercial.planesPago.index')
            ->with('status', 'Plan de pago guardado correctamente.');
    }

    /**
     * Muestra el formulario de edición de una plantilla de plan de pago.
     */
    public function edit(PlanPagoPlantilla $planPago): View
    {
        return view('structure.commercial_management.planes_pago.edit', [
            'plan' => $planPago,
        ]);
    }

    /**
     * Actualiza una plantilla de plan de pago existente.
     */
    public function update(Request $request, PlanPagoPlantilla $planPago): RedirectResponse
    {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'numero_pagos' => ['required', 'integer', 'min:1', 'max:60'],
            'dias_entre_pagos' => ['required', 'integer', 'min:1'],
            'metodo_pago' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string', 'max:255'],
        ]);

        $planPago->update($data);

        return redirect()->route('commercial.planesPago.index')
            ->with('status', 'Plan de pago actualizado correctamente.');
    }

    /**
     * Elimina una plantilla de plan de pago.
     */
    public function destroy(PlanPagoPlantilla $planPago): RedirectResponse
    {
        $planPago->delete();

        return redirect()->route('commercial.planesPago.index')
            ->with('status', 'Plan de pago eliminado correctamente.');
    }
}
