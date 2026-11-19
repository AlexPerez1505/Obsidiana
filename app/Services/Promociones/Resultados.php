<?php

namespace App\Services\Promociones;

use App\Models\Campana;
use App\Models\CampanaDestinatario as D;
use App\Models\Cotizacion;
use App\Models\CuponUso;
use App\Models\Venta;

/**
 * Lo que de verdad se puede medir de una campaña. Las pruebas nunca cuentan.
 *
 *   • Enviados / entregados / leídos / fallidos: lo que respondió la API
 *     y los avisos del webhook (si no está configurado, "entregado" y
 *     "leído" se quedan en cero: no se inventan). WhatsApp no reporta
 *     "leído" a quien desactivó las confirmaciones de lectura.
 *   • Clics: aperturas del enlace personal (/p/{token}) contadas por el sistema.
 *   • ATRIBUCIÓN: una venta cuenta para la campaña solo si quedó ligada a
 *     ella (ventas.campana_id): porque usó un cupón de la campaña que el
 *     cliente sí recibió, o porque nació del pedido hecho en el enlace.
 *     Cada venta tiene una sola campaña, así que nunca se cuenta dos veces.
 *     Las ventas canceladas no suman.
 */
class Resultados
{
    public function deCampana(Campana $c): array
    {
        $porEstado = D::where('campana_id', $c->id)->where('es_prueba', false)
            ->selectRaw('estado, count(*) as n')->groupBy('estado')->pluck('n', 'estado');
        $n = fn (string ...$e) => (int) collect($e)->sum(fn ($x) => $porEstado->get($x, 0));

        $clics = D::where('campana_id', $c->id)->where('es_prueba', false)
            ->selectRaw('COALESCE(SUM(clics),0) as total, SUM(CASE WHEN clics > 0 THEN 1 ELSE 0 END) as unicos')->first();

        $ventas = Venta::where('campana_id', $c->id)->where('estado', '!=', 'cancelada');
        $usos = CuponUso::where('campana_id', $c->id);

        $enviados = $n(D::ENVIADO, D::ENTREGADO, D::LEIDO);

        return [
            'destinatarios' => (int) $porEstado->sum(),
            'pendientes' => $n(D::PENDIENTE, D::ENVIANDO),
            'enviados' => $enviados,
            'entregados' => $n(D::ENTREGADO, D::LEIDO),
            'leidos' => $n(D::LEIDO),
            'fallidos' => $n(D::FALLO),
            'simulados' => $n(D::SIMULADO),
            'omitidos' => $n(D::OMITIDO_CONSENTIMIENTO, D::OMITIDO_FRECUENCIA),
            'cancelados' => $n(D::CANCELADO),
            'clics' => (int) ($clics->total ?? 0),
            'clics_unicos' => (int) ($clics->unicos ?? 0),
            'pedidos' => Cotizacion::where('campana_id', $c->id)->whereNotNull('campana_destinatario_id')->count(),
            'cupones_reservados' => (clone $usos)->where('estado', CuponUso::RESERVADO)->where('expira_en', '>', now())->count(),
            'cupones_usados' => (clone $usos)->where('estado', CuponUso::CONSUMIDO)->count(),
            'ventas' => (clone $ventas)->count(),
            'ingresos' => (float) (clone $ventas)->sum('total'),
            'descontado' => (float) (clone $usos)->where('estado', CuponUso::CONSUMIDO)->sum('descuento'),
            'tasa_entrega' => $enviados > 0 ? round($n(D::ENTREGADO, D::LEIDO) / $enviados * 100, 1) : null,
            'tasa_clic' => ($enviados + $n(D::SIMULADO)) > 0 ? round(($clics->unicos ?? 0) / ($enviados + $n(D::SIMULADO)) * 100, 1) : null,
        ];
    }
}
