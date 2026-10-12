<?php

namespace App\Services\Ventas;

use App\Models\Factura;
use App\Models\Venta;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;

/**
 * Borradores de factura. Todavía no se timbran: es la hoja con los datos
 * fiscales y los montos congelados de la venta, para que contabilidad la
 * capture en su sistema.
 *
 * Vivía en FacturaController; lo usan la pantalla y la API.
 */
class BorradoresDeFactura
{
    /** Catálogos del SAT que se ofrecen en el formulario (clave => texto). */
    public const USOS_CFDI = [
        'G01' => 'Adquisición de mercancías',
        'G03' => 'Gastos en general',
        'I04' => 'Equipo de cómputo',
        'I08' => 'Otra maquinaria y equipo',
        'P01' => 'Por definir',
    ];

    public const FORMAS_PAGO = [
        '01' => 'Efectivo',
        '03' => 'Transferencia electrónica',
        '04' => 'Tarjeta de crédito',
        '28' => 'Tarjeta de débito',
        '99' => 'Por definir',
    ];

    public const METODOS_PAGO = [
        'PUE' => 'Pago en una exhibición',
        'PPD' => 'Pago en parcialidades o diferido',
    ];

    public static function reglas(): array
    {
        return [
            'venta_id' => ['nullable', 'exists:ventas,id'],
            'customer_id' => ['required', 'exists:clientes,id'],
            'rfc' => ['nullable', 'string', 'max:20'],
            'razon_social' => ['nullable', 'string', 'max:255'],
            'uso_cfdi' => ['nullable', 'string', 'max:100'],
            'forma_pago' => ['nullable', 'string', 'max:100'],
            'metodo_pago' => ['nullable', 'string', 'max:100'],
            'observaciones' => ['nullable', 'string'],
        ];
    }

    /**
     * Crea el borrador. Si viene de una venta, copia sus montos y renglones
     * tal como están hoy y la marca como facturada.
     */
    public function crear(array $data): Factura
    {
        $ventaId = $data['venta_id'] ?? null;

        return DB::transaction(function () use ($data, $ventaId) {
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
                        'no_series' => $it->no_series,
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
    }

    /** El PDF del borrador, para verlo en el navegador. */
    public function pdf(Factura $factura)
    {
        $factura->load(['customer', 'venta', 'items']);

        $pdf = Pdf::loadView('structure.commercial_management.facturas.pdf', [
            'factura' => $factura,
        ])->setPaper('letter');

        return $pdf->stream("{$factura->folio}.pdf");
    }
}
