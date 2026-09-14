<?php

namespace App\Http\Controllers;

use App\Models\OrdenSalida;
use App\Models\OrdenSalidaItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * Órdenes de salida: la lista de lo que almacén tiene que preparar y la
 * hoja de cada orden con su checklist y la firma de salida.
 */
class OrdenSalidaController extends Controller
{
    public function index(Request $request): View
    {
        $estado = array_key_exists($request->get('estado'), OrdenSalida::ESTADOS) ? $request->get('estado') : 'abiertas';

        $ordenes = OrdenSalida::with(['venta.customer', 'venta.seller', 'items'])
            ->when($estado === 'abiertas', fn ($q) => $q->whereNotIn('estado', [OrdenSalida::ENTREGADA, OrdenSalida::CANCELADA]))
            ->when($estado !== 'abiertas', fn ($q) => $q->where('estado', $estado))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $conteos = OrdenSalida::selectRaw('estado, COUNT(*) as n')->groupBy('estado')->pluck('n', 'estado');

        return view('structure.gestion_Inventario.ordenes_salida.index', [
            'ordenes' => $ordenes,
            'estado' => $estado,
            'conteos' => [
                'abiertas' => (int) ($conteos[OrdenSalida::PENDIENTE] ?? 0) + (int) ($conteos[OrdenSalida::EN_PREPARACION] ?? 0) + (int) ($conteos[OrdenSalida::LISTA] ?? 0),
                OrdenSalida::LISTA => (int) ($conteos[OrdenSalida::LISTA] ?? 0),
                OrdenSalida::ENTREGADA => (int) ($conteos[OrdenSalida::ENTREGADA] ?? 0),
            ],
        ]);
    }

    public function show(OrdenSalida $orden): View
    {
        $orden->load(['venta.customer', 'venta.seller', 'items', 'preparadaPor', 'entregadaPor']);

        return view('structure.gestion_Inventario.ordenes_salida.show', [
            'orden' => $orden,
            'puedePreparar' => auth()->user()->can('salidas.preparar'),
        ]);
    }

    /**
     * Hoja impresa de la orden: qué salió, cuándo, quién preparó, quién
     * autorizó la salida y quién recibió, con las firmas. Se puede sacar
     * en cualquier momento; antes de firmar se marca como avance.
     */
    public function pdf(OrdenSalida $orden)
    {
        $orden->load(['venta.customer', 'venta.seller', 'items', 'preparadaPor', 'entregadaPor', 'creator']);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('structure.gestion_Inventario.ordenes_salida.pdf', [
            'orden' => $orden,
            'firmaEntrega' => $this->firmaDataUri($orden->firma_entrega_path),
            'firmaRecibe' => $this->firmaDataUri($orden->firma_recibe_path),
        ])->setPaper('letter');

        return $pdf->stream("{$orden->folio}.pdf");
    }

    /** La firma guardada en disco, embebida para que dompdf la pinte. */
    private function firmaDataUri(?string $path): ?string
    {
        $disco = config('filesystems.fotos_disk', 'public');

        if (! $path || ! Storage::disk($disco)->exists($path)) {
            return null;
        }

        return 'data:image/png;base64,'.base64_encode(Storage::disk($disco)->get($path));
    }

    /**
     * Marca o desmarca un paso de una partida (preparado / emplayado),
     * cambia si se emplaya o no, o guarda una observación.
     */
    public function item(Request $request, OrdenSalida $orden, OrdenSalidaItem $item): RedirectResponse
    {
        abort_if($item->orden_salida_id !== $orden->id, 404);

        if ($orden->cerrada()) {
            return back()->withErrors(['orden' => 'Esta orden ya salió: no se puede modificar.']);
        }

        $data = $request->validate([
            'preparado' => ['nullable', 'boolean'],
            'emplayado' => ['nullable', 'boolean'],
            'requiere_emplayado' => ['nullable', 'boolean'],
            'observaciones' => ['nullable', 'string', 'max:500'],
        ]);

        if ($request->has('preparado')) {
            $item->preparado = $request->boolean('preparado');
            $item->preparado_en = $item->preparado ? now() : null;
        }

        if ($request->has('requiere_emplayado')) {
            $item->requiere_emplayado = $request->boolean('requiere_emplayado');
            // Si ya no se emplaya, lo marcado de emplayado deja de contar.
            if (! $item->requiere_emplayado) {
                $item->emplayado = false;
                $item->emplayado_en = null;
            }
        }

        if ($request->has('emplayado') && $item->requiere_emplayado) {
            $item->emplayado = $request->boolean('emplayado');
            $item->emplayado_en = $item->emplayado ? now() : null;
        }

        if ($request->has('observaciones')) {
            $item->observaciones = trim((string) $data['observaciones']) ?: null;
        }

        $item->save();
        $orden->actualizarEstado();

        return back();
    }

    /** Notas generales de la orden (instrucciones para almacén, transporte...). */
    public function notas(Request $request, OrdenSalida $orden): RedirectResponse
    {
        $data = $request->validate(['notas' => ['nullable', 'string', 'max:2000']]);

        $orden->notas = trim((string) $data['notas']) ?: null;
        $orden->save();

        return back()->with('status', 'Notas guardadas.');
    }

    /**
     * Firma de salida: solo cuando todo está preparado. Firma quien entrega
     * por almacén y quien se lleva el equipo (chofer, cliente, paquetería).
     */
    public function entregar(Request $request, OrdenSalida $orden): RedirectResponse
    {
        if ($orden->cerrada()) {
            return back()->withErrors(['orden' => 'Esta orden ya está cerrada.']);
        }

        if (! $orden->todoListo()) {
            return back()->withErrors(['orden' => 'Todavía hay partidas sin preparar o sin emplayar. Termina el checklist antes de firmar la salida.']);
        }

        $data = $request->validate([
            'recibe_nombre' => ['required', 'string', 'max:255'],
            'firma_entrega' => ['required', 'string'],
            'firma_recibe' => ['required', 'string'],
        ], [
            'recibe_nombre.required' => 'Escribe el nombre de quien se lleva el equipo.',
            'firma_entrega.required' => 'Falta la firma de quien entrega.',
            'firma_recibe.required' => 'Falta la firma de quien recibe.',
        ]);

        $disco = config('filesystems.fotos_disk', 'public');
        $firmaEntrega = $this->guardarFirma($data['firma_entrega'], $disco);
        $firmaRecibe = $this->guardarFirma($data['firma_recibe'], $disco);

        if (! $firmaEntrega || ! $firmaRecibe) {
            foreach (array_filter([$firmaEntrega, $firmaRecibe]) as $p) {
                Storage::disk($disco)->delete($p);
            }

            return back()->withInput()->withErrors(['firma_recibe' => 'Alguna firma no es válida. Vuelve a firmar e intenta de nuevo.']);
        }

        $orden->update([
            'estado' => OrdenSalida::ENTREGADA,
            'entregada_por' => $request->user()->id,
            'entregada_en' => now(),
            'recibe_nombre' => $data['recibe_nombre'],
            'firma_entrega_path' => $firmaEntrega,
            'firma_recibe_path' => $firmaRecibe,
        ]);

        return redirect()->route('inventory.salidas.show', $orden)
            ->with('status', "Salida {$orden->folio} firmada. El equipo ya salió.");
    }

    /** Igual que la firma de las entradas: data URL base64 a PNG en disco. */
    private function guardarFirma(string $firmaDataUrl, string $disco): ?string
    {
        if (! preg_match('/^data:image\/(png|jpe?g);base64,(.+)$/', $firmaDataUrl, $match)) {
            return null;
        }

        $contenido = base64_decode($match[2], true);

        if ($contenido === false || $contenido === '') {
            return null;
        }

        $path = 'inventario/salidas/'.uniqid('firma_').'.png';
        Storage::disk($disco)->put($path, $contenido);

        return $path;
    }
}
