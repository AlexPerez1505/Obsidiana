<?php

namespace App\Services\Promociones;

use App\Models\CampanaDestinatario;
use App\Models\Cotizacion;
use App\Models\MktEvento;
use App\Models\User;
use App\Services\CalculadoraCotizacion;
use App\Services\Cotizaciones\GuardarCotizacion;
use Illuminate\Validation\ValidationException;

/**
 * Lo que vive detrás del enlace de WhatsApp (/p/{token}).
 *
 * El sistema no tiene pago en línea: la "compra" del cliente se registra
 * como una COTIZACIÓN real (estado aceptada) a su nombre, con el cupón ya
 * aplicado y RESERVADO. El vendedor la convierte en venta con el flujo de
 * siempre y en ese momento el cupón se consume.
 *
 * Precios, descuento y total los calcula el servidor; del navegador solo
 * se toman las cantidades de los productos que la oferta incluye.
 */
class OfertaPublica
{
    public function __construct(
        private readonly CatalogoOferta $catalogo,
        private readonly MotorDeCupones $cupones,
        private readonly CalculadoraCotizacion $calc,
        private readonly GuardarCotizacion $guardar,
    ) {
    }

    public function registrarClic(CampanaDestinatario $d): void
    {
        CampanaDestinatario::whereKey($d->id)->update([
            'clics' => \Illuminate\Support\Facades\DB::raw('clics + 1'),
            'primer_clic_en' => $d->primer_clic_en ?? now(),
            'ultimo_clic_en' => now(),
        ]);
    }

    /** Subtotal, cupón, IVA y total de lo que eligió el cliente. */
    public function calcular(CampanaDestinatario $d, array $cantidades): array
    {
        $catalogo = $this->catalogo->itemsDe($d->campana);
        $items = $this->catalogo->renglones($catalogo, $cantidades);
        $subtotal = $this->calc->subtotal($items);

        $cupon = $d->cuponAplicable();
        $estadoCupon = null;
        $descuento = 0.0;

        if ($cupon && $d->cliente && $items) {
            $r = $this->cupones->evaluar($cupon->load('alcances'), $d->cliente, $items);
            $estadoCupon = ['codigo' => $cupon->codigo, 'valido' => $r->valido(), 'mensaje' => $r->mensaje()];
            $descuento = $r->valido() ? $r->descuento : 0.0;
        } elseif ($cupon) {
            $estadoCupon = ['codigo' => $cupon->codigo, 'valido' => false, 'mensaje' => $items ? 'El cupón se aplica a la cuenta que recibió la promoción.' : 'Elige al menos un producto.'];
        }

        $aplicaIva = (bool) config('promociones.oferta.aplica_iva');
        $d2 = $this->calc->desglose($items, null, 0, 0, $aplicaIva, 0, $descuento);

        return [
            'items' => array_map(fn ($i) => [
                'nombre' => $i['nombre'], 'cantidad' => $i['cantidad'],
                'precio' => $i['precio_unitario'], 'importe' => round($i['precio_unitario'] * $i['cantidad'], 2),
            ], $items),
            'subtotal' => $subtotal,
            'descuento' => $d2['descuento_cupon'],
            'iva' => $d2['iva'],
            'total' => $d2['total'],
            'aplica_iva' => $aplicaIva,
            'cupon' => $estadoCupon,
        ];
    }

    /**
     * El cliente confirma: se crea su cotización con el cupón reservado.
     * Pide los últimos 4 dígitos de su teléfono para que un enlace reenviado
     * no sirva para comprar a nombre de otro (y su cupón personal no se use).
     */
    public function confirmar(CampanaDestinatario $d, array $cantidades, string $ultimos4, bool $sinCupon = false): Cotizacion
    {
        $campana = $d->campana;
        $cliente = $d->cliente;

        $falla = fn (string $campo, string $m) => throw ValidationException::withMessages([$campo => $m]);

        if (! $cliente) {
            $falla('oferta', 'Esta es una vista de prueba: no está ligada a ningún cliente.');
        }
        if (! $campana->ofertaVigente()) {
            $falla('oferta', 'Esta oferta ya no está disponible.');
        }
        $tel = preg_replace('/\D+/', '', (string) $cliente->telefono) ?? '';
        if (strlen($tel) < 4 || ! hash_equals(substr($tel, -4), preg_replace('/\D+/', '', $ultimos4) ?? '')) {
            MktEvento::create(['campana_id' => $campana->id, 'accion' => 'verificacion_fallida', 'detalle' => 'Intento de confirmar pedido con dígitos incorrectos (destinatario #'.$d->id.')']);
            $falla('ultimos4', 'Los dígitos no coinciden con el teléfono al que enviamos la promoción.');
        }

        $items = $this->catalogo->renglones($this->catalogo->itemsDe($campana), $cantidades);
        if (! $items) {
            $falla('cantidades', 'Elige al menos un producto.');
        }

        $cupon = $sinCupon ? null : $d->cuponAplicable();
        $vendedor = ($cliente->asesor_id ? User::find($cliente->asesor_id) : null) ?? $campana->creador;

        $cot = $this->guardar->crear([
            'customer_id' => $cliente->id,
            'nota_cliente' => 'Pedido hecho por el cliente desde la promoción "'.$campana->nombre.'" (WhatsApp). Confirmar disponibilidad, envío y forma de pago.',
            'modalidad' => 'contado',
            'aplica_iva' => (bool) config('promociones.oferta.aplica_iva'),
            'descuento_tipo' => null,
            'descuento_valor' => 0,
            'envio' => 0,
            'valor_a_cuenta' => 0,
            'garantia_meses' => 6,
            'cupon_codigo' => $cupon?->codigo,
            'items' => $items,
            '_destinatario' => $d->es_prueba ? null : $d,
        ], $vendedor);

        Cotizacion::whereKey($cot->id)->update([
            'estado' => 'aceptada',
            // Las pruebas no se atribuyen a la campaña.
            'campana_id' => $d->es_prueba ? null : $campana->id,
            'campana_destinatario_id' => $d->es_prueba ? null : $d->id,
        ]);

        MktEvento::create([
            'campana_id' => $campana->id,
            'accion' => 'pedido',
            'detalle' => 'El cliente confirmó el pedido '.$cot->folio.' por $'.number_format((float) $cot->total, 2).($d->es_prueba ? ' (prueba)' : ''),
            'datos' => ['cotizacion_id' => $cot->id, 'destinatario_id' => $d->id],
        ]);

        return $cot->refresh();
    }
}
