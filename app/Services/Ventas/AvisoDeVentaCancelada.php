<?php

namespace App\Services\Ventas;

use App\Models\OrdenSalida;
use App\Models\User;
use App\Models\Venta;
use App\Notifications\EtapaSalidaActualizada;
use App\Notifications\EtapaSalidaAsignada;
use App\Notifications\OrdenSalidaAsignada;
use App\Notifications\VentaCancelada;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Cuando una venta se cancela o se elimina, a la gente que ya estaba
 * trabajando en ella (quien vendió y quienes preparan el equipo o tienen
 * una etapa) le llega el aviso de que ya no va, con la lista de equipos y
 * series que hay que regresar a su lugar en el almacén.
 *
 * Se toma la "foto" ANTES de liberar las piezas o borrar la orden (después
 * ya no se sabe qué serie iba en qué venta ni quién estaba asignado), y el
 * aviso sale después de responder: un correo que falla no tumba nada.
 */
class AvisoDeVentaCancelada
{
    /**
     * @param  string  $accion  'cancelada' o 'eliminada'
     * @return array{datos: array, destinatarios: Collection<int, User>}
     */
    public function preparar(Venta $venta, ?User $quien, string $accion, ?string $motivo = null): array
    {
        $venta->loadMissing(['customer', 'seller', 'items.seriales', 'ordenSalida.items', 'ordenSalida.etapas']);
        $orden = $venta->ordenSalida;

        $seriesPorItem = $venta->items->mapWithKeys(fn ($i) => [
            $i->id => $i->seriales->map(fn ($s) => trim(($s->codigo ?? '').($s->no_serie ? ' · '.$s->no_serie : ''), ' ·'))->filter()->values()->all(),
        ]);

        // Los equipos: los de la orden de salida (dicen si ya se prepararon);
        // si no hay orden, los renglones de la venta.
        $equipos = $orden && $orden->items->isNotEmpty()
            ? $orden->items->map(fn ($it) => [
                'nombre' => $it->nombre,
                'cantidad' => (int) $it->cantidad,
                'series' => $seriesPorItem[$it->venta_item_id] ?? array_values(array_filter(array_map('trim', explode(',', (string) $it->no_series)))),
                'preparado' => (bool) $it->preparado,
            ])->values()->all()
            : $venta->items->map(fn ($i) => [
                'nombre' => $i->nombre ?? $i->descripcion ?? 'Equipo',
                'cantidad' => (int) ($i->cantidad ?? 1),
                'series' => $seriesPorItem[$i->id] ?? [],
                'preparado' => false,
            ])->values()->all();

        $ids = collect([$venta->seller_id]);
        if ($orden) {
            $ids = $ids->push($orden->asignada_a)
                ->concat($orden->items->pluck('asignado_a'))
                ->concat($orden->etapas->pluck('asignada_a'))
                ->concat($orden->etapas->pluck('iniciada_por'));
        }

        $destinatarios = User::whereIn('id', $ids->filter()->unique()->reject(fn ($id) => $quien && (int) $id === (int) $quien->id)->values())
            ->where('status', User::STATUS_APPROVED)
            ->get();

        $cliente = trim(($venta->customer?->nombre ?? '').' '.($venta->customer?->apellido ?? ''));

        return [
            'datos' => [
                'accion' => $accion,
                'venta_id' => $venta->id,
                'folio' => $venta->folio,
                'cliente' => $cliente !== '' ? $cliente : null,
                'motivo' => $motivo,
                'quien' => $quien?->name,
                'orden_salida_id' => $orden?->id,
                'orden_folio' => $orden?->folio,
                'equipos' => $equipos,
                // Solo hay algo que regresar físicamente si alguien ya lo apartó.
                'hay_que_regresar' => $orden && ($orden->estado !== OrdenSalida::PENDIENTE
                    || $orden->items->contains(fn ($it) => $it->preparado || $it->emplayado)
                    || $orden->etapas->contains(fn ($e) => $e->estado !== \App\Models\OrdenSalidaEtapa::PENDIENTE)),
            ],
            'destinatarios' => $destinatarios,
        ];
    }

    /** Manda los avisos (después de responder) y apaga los "te toca preparar" de esa orden. */
    public function enviar(array $foto): int
    {
        $datos = $foto['datos'];
        $destinatarios = $foto['destinatarios'];

        if ($datos['orden_salida_id']) {
            try {
                DB::table('notifications')
                    ->whereNull('read_at')
                    ->whereIn('type', [OrdenSalidaAsignada::class, EtapaSalidaAsignada::class, EtapaSalidaActualizada::class])
                    ->where('data->orden_salida_id', $datos['orden_salida_id'])
                    ->update(['read_at' => now()]);
            } catch (\Throwable $e) {
                Log::warning('[Ventas] No se apagaron los avisos de la orden', ['orden' => $datos['orden_salida_id'], 'error' => $e->getMessage()]);
            }
        }

        if ($destinatarios->isEmpty()) {
            return 0;
        }

        \Illuminate\Support\defer(function () use ($datos, $destinatarios) {
            foreach ($destinatarios as $persona) {
                try {
                    $persona->notify(new VentaCancelada($datos));
                } catch (\Throwable $e) {
                    Log::warning('[Ventas] No salió el aviso de venta cancelada', ['venta' => $datos['venta_id'], 'user_id' => $persona->id, 'error' => $e->getMessage()]);
                }
            }
        });

        return $destinatarios->count();
    }
}
