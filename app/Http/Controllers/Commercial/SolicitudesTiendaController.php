<?php

namespace App\Http\Controllers\Commercial;

use App\Http\Controllers\Controller;
use App\Models\TiendaSolicitud;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Gestión Comercial → Solicitudes web: lo que piden los clientes desde la
 * tienda en línea. Aquí se revisan, se marcan y se pasan a cotización.
 */
class SolicitudesTiendaController extends Controller
{
    public function index(): View
    {
        $solicitudes = TiendaSolicitud::with(['cliente', 'atendidaPor'])->latest()->get();

        return view('commercial.solicitudes-web.index', compact('solicitudes'));
    }

    public function show(TiendaSolicitud $solicitud): View
    {
        $solicitud->load(['cliente', 'atendidaPor']);

        return view('commercial.solicitudes-web.show', compact('solicitud'));
    }

    public function update(Request $request, TiendaSolicitud $solicitud): RedirectResponse
    {
        $datos = $request->validate([
            'estado' => ['required', Rule::in(array_keys(TiendaSolicitud::ESTADOS))],
            'nota_interna' => ['nullable', 'string', 'max:2000'],
        ]);

        $solicitud->fill($datos);

        if ($solicitud->isDirty('estado') && $solicitud->estado !== 'nueva') {
            $solicitud->atendida_por = $request->user()->id;
            $solicitud->atendida_en = now();
        }

        $solicitud->save();

        return back()->with('status', 'Solicitud '.$solicitud->folio.' actualizada.');
    }
}
