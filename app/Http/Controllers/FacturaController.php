<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Factura;
use App\Models\Venta;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class FacturaController extends Controller
{
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
        $data = $request->validate([
            'venta_id' => ['nullable', 'exists:ventas,id'],
            'customer_id' => ['required', 'exists:customers,id'],
            'rfc' => ['nullable', 'string', 'max:20'],
            'razon_social' => ['nullable', 'string', 'max:255'],
            'uso_cfdi' => ['nullable', 'string', 'max:100'],
            'forma_pago' => ['nullable', 'string', 'max:100'],
            'metodo_pago' => ['nullable', 'string', 'max:100'],
            'observaciones' => ['nullable', 'string'],
        ]);

        $ventaId = $data['venta_id'] ?? null;

        $factura = DB::transaction(function () use ($data, $ventaId) {
            $factura = new Factura($data);
            $factura->folio = Factura::siguienteFolio();
            $factura->estado = 'borrador';

            // Snapshot de montos e items desde la venta origen (si existe).
            if ($ventaId) {
                $venta = Venta::with('items')->findOrFail($ventaId);
                $factura->subtotal = $venta->subtotal;
                $factura->descuento_monto = $venta->descuento_monto;
                $factura->envio = $venta->envio;
                $factura->aplica_iva = $venta->aplica_iva;
                $factura->iva_monto = $venta->iva_monto;
                $factura->total = $venta->total;
                $factura->save();

                foreach ($venta->items as $orden => $it) {
                    $factura->items()->create([
                        'nombre' => $it->nombre,
                        'modelo' => $it->modelo,
                        'marca' => $it->marca,
                        'cantidad' => $it->cantidad,
                        'precio_unitario' => $it->precio_unitario,
                        'sobreprecio' => $it->sobreprecio,
                        'es_regalo' => $it->es_regalo,
                        'orden' => $orden,
                    ]);
                }

                $venta->update(['estado' => 'facturada']);
            } else {
                $factura->save();
            }

            return $factura;
        });

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
        $factura->load(['customer', 'venta', 'items']);

        $pdf = Pdf::loadView('structure.commercial_management.facturas.pdf', [
            'factura' => $factura,
        ])->setPaper('letter');

        return $pdf->stream("{$factura->folio}.pdf");
    }
}
