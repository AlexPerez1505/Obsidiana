<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Factura;
use App\Models\Venta;
use App\Services\Ventas\BorradoresDeFactura;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FacturaController extends Controller
{
    public function __construct(private readonly BorradoresDeFactura $borradores)
    {
    }

    public function index(): View
    {
        $facturas = Factura::with(['customer', 'venta'])->latest()->get();

        return view('structure.commercial_management.facturas.index', [
            'facturas' => $facturas,
        ]);
    }

    /**
     * Formulario de nuevo borrador. Con ?venta=ID precarga desde la venta.
     */
    public function create(Request $request): View
    {
        $venta = null;
        $cliente = null;

        if ($request->filled('venta')) {
            $venta = Venta::with(['customer', 'items'])->find($request->integer('venta'));
            $cliente = $venta?->customer;
        } elseif ($request->filled('cliente')) {
            $cliente = Customer::find($request->integer('cliente'));
        }

        return view('structure.commercial_management.facturas.form', [
            'venta' => $venta,
            'cliente' => $cliente,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(BorradoresDeFactura::reglas());

        $factura = $this->borradores->crear($data);

        return redirect()->route('commercial.facturas.show', $factura)
            ->with('status', "Borrador de factura {$factura->folio} generado.");
    }

    public function show(Factura $factura): View
    {
        $factura->load(['customer', 'venta', 'items']);

        return view('structure.commercial_management.facturas.show', [
            'factura' => $factura,
        ]);
    }

    public function destroy(Factura $factura): RedirectResponse
    {
        $folio = $factura->folio;
        $factura->delete();

        return redirect()->route('commercial.facturas.index')
            ->with('status', "Borrador {$folio} eliminado.");
    }

    public function pdf(Factura $factura)
    {
        return $this->borradores->pdf($factura);
    }
}
