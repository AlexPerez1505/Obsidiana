<?php

namespace App\Http\Controllers;

use App\Models\OrdenSalida;
use App\Models\OrdenSalidaItem;
use App\Services\Almacen\PreparacionDeSalida;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Órdenes de salida: la lista de lo que almacén tiene que preparar y la
 * hoja de cada orden con su checklist y la firma de salida.
 *
 * Lo que se le hace a la orden vive en PreparacionDeSalida, que comparte
 * con la API; aquí solo se decide qué pantalla y qué mensaje enseñar.
 */
class OrdenSalidaController extends Controller
{
    public function __construct(private readonly PreparacionDeSalida $salidas)
    {
    }

    public function index(Request $request): View
    {
        $estado = array_key_exists($request->get('estado'), OrdenSalida::ESTADOS) ? $request->get('estado') : PreparacionDeSalida::ABIERTAS;

        $ordenes = $this->salidas->filtrarPorEstado(OrdenSalida::with(['venta.customer', 'venta.seller', 'items']), $estado)
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('structure.gestion_Inventario.ordenes_salida.index', [
            'ordenes' => $ordenes,
            'estado' => $estado,
            'conteos' => $this->salidas->conteos(),
        ]);
    }

    public function show(OrdenSalida $orden): View
    {
        // Las piezas de cada partida vienen con su congreso: si el equipo
        // se vendió en uno, no está en el almacén y no hay que buscarlo.
        $orden->load([
            'venta.customer', 'venta.seller', 'preparadaPor', 'entregadaPor',
            'items.ventaItem.seriales.congress',
        ]);

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
        return $this->salidas->pdf($orden)->stream("{$orden->folio}.pdf");
    }

    /**
     * Marca o desmarca un paso de una partida (preparado / emplayado),
     * cambia si se emplaya o no, o guarda una observación.
     */
    public function item(Request $request, OrdenSalida $orden, OrdenSalidaItem $item): RedirectResponse
    {
        abort_if($item->orden_salida_id !== $orden->id, 404);

        if ($razon = $this->salidas->razonParaNoMarcar($orden)) {
            return back()->withErrors(['orden' => $razon]);
        }

        $data = $request->validate(PreparacionDeSalida::REGLAS_PARTIDA);

        $this->salidas->marcarPartida($orden, $item, $this->salidas->cambiosDePartida($request, $data));

        return back();
    }

    /**
     * Cierra la preparación: la orden pasa a "Lista para salir" y se queda
     * esperando que alguien firme la salida.
     *
     * Es el relevo entre almacén y quien entrega: el que prepara termina su
     * parte aquí y queda registrado que fue él, aunque la firma la haga otra
     * persona horas o días después.
     */
    public function preparada(Request $request, OrdenSalida $orden): RedirectResponse
    {
        if ($razon = $this->salidas->razonParaNoConfirmar($orden)) {
            return back()->withErrors(['orden' => $razon]);
        }

        $orden->confirmarPreparacion($request->user()->id);

        return back()->with('status', "Orden {$orden->folio} lista para salir. Ya solo falta que alguien firme la salida.");
    }

    /** Notas generales de la orden (instrucciones para almacén, transporte...). */
    public function notas(Request $request, OrdenSalida $orden): RedirectResponse
    {
        $data = $request->validate(PreparacionDeSalida::REGLAS_NOTAS);

        $this->salidas->guardarNotas($orden, $data['notas'] ?? null);

        return back()->with('status', 'Notas guardadas.');
    }

    /**
     * Firma de salida: solo cuando todo está preparado. Firma quien entrega
     * por almacén y quien se lleva el equipo (chofer, cliente, paquetería).
     */
    public function entregar(Request $request, OrdenSalida $orden): RedirectResponse
    {
        if ($razon = $this->salidas->razonParaNoEntregar($orden)) {
            return back()->withErrors(['orden' => $razon]);
        }

        $data = $request->validate(PreparacionDeSalida::REGLAS_ENTREGA, PreparacionDeSalida::MENSAJES_ENTREGA);

        $firmado = $this->salidas->entregar($orden, $request->user()->id, $data['recibe_nombre'], $data['firma_entrega'], $data['firma_recibe']);

        if (! $firmado) {
            return back()->withInput()->withErrors(['firma_recibe' => PreparacionDeSalida::FIRMA_INVALIDA]);
        }

        return redirect()->route('inventory.salidas.show', $orden)
            ->with('status', "Salida {$orden->folio} firmada. El equipo ya salió.");
    }
}
