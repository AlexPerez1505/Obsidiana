<?php

namespace App\Services;

use App\Models\Equipo;
use App\Models\OrdenSalida;
use App\Models\Paquete;
use App\Models\Producto;
use App\Models\Venta;
use App\Models\VentaItem;

/**
 * Genera y mantiene la orden de salida de cada venta.
 *
 * Se crea al registrar la venta, con un renglón por partida física. Si la
 * venta se edita antes de que salga, la orden se rehace con lo nuevo (lo
 * ya marcado se conserva cuando la partida sigue siendo la misma). Una
 * vez entregada, ya no se toca.
 */
class OrdenesDeSalida
{
    /** Tipos de partida que son cosas físicas que hay que preparar. */
    private const FISICOS = ['equipo', 'producto', 'paquete'];

    public function generarPara(Venta $venta): ?OrdenSalida
    {
        // Se recargan siempre: al editar, los renglones viejos ya se
        // borraron y los que trae la venta en memoria pueden ser esos.
        $venta->load('items');

        $partidas = $venta->items->filter(fn (VentaItem $i) => in_array($i->tipo_item, self::FISICOS, true));

        // Solo si aplica: una venta sin equipo físico no tiene nada que preparar.
        if ($partidas->isEmpty()) {
            return null;
        }

        $orden = OrdenSalida::create([
            'folio' => OrdenSalida::siguienteFolio(),
            'venta_id' => $venta->id,
            'estado' => OrdenSalida::PENDIENTE,
            'created_by' => auth()->id(),
        ]);

        $this->escribirRenglones($orden, $partidas);

        return $orden;
    }

    /**
     * Tras editar la venta: si la orden ya salió no se toca; si no, se
     * vuelve a armar con las partidas actuales conservando lo marcado en
     * las que no cambiaron (mismo nombre y cantidad).
     */
    public function sincronizar(Venta $venta): ?OrdenSalida
    {
        $orden = OrdenSalida::where('venta_id', $venta->id)->first();

        if (! $orden) {
            return $this->generarPara($venta);
        }

        if ($orden->cerrada()) {
            return $orden;
        }

        $venta->load('items');
        $partidas = $venta->items->filter(fn (VentaItem $i) => in_array($i->tipo_item, self::FISICOS, true));

        if ($partidas->isEmpty()) {
            $orden->delete();

            return null;
        }

        $previos = $orden->items->keyBy(fn ($i) => $this->huella($i->nombre, $i->descripcion, $i->cantidad));

        $orden->items()->delete();
        $this->escribirRenglones($orden, $partidas, $previos);
        $orden->actualizarEstado();

        return $orden;
    }

    /** Al cancelar la venta, la orden se va con ella. */
    public function cancelarDe(Venta $venta): void
    {
        OrdenSalida::where('venta_id', $venta->id)->delete();
    }

    private function escribirRenglones(OrdenSalida $orden, $partidas, $previos = null): void
    {
        foreach ($partidas->values() as $n => $item) {
            $nombre = trim(($item->marca ?? '').' '.($item->modelo ?? '')) ?: ($item->nombre ?? 'Partida');
            $descripcion = $item->nombre && $nombre !== $item->nombre ? $item->nombre : null;
            $previo = $previos?->get($this->huella($nombre, $descripcion, (int) $item->cantidad));

            $orden->items()->create([
                'venta_item_id' => $item->id,
                'nombre' => $nombre,
                'descripcion' => $descripcion,
                'cantidad' => (int) $item->cantidad,
                'no_series' => $item->no_series,
                'requiere_emplayado' => $previo?->requiere_emplayado ?? $this->requiereEmplayado($item),
                'preparado' => (bool) ($previo?->preparado ?? false),
                'preparado_en' => $previo?->preparado_en,
                'emplayado' => (bool) ($previo?->emplayado ?? false),
                'emplayado_en' => $previo?->emplayado_en,
                'observaciones' => $previo?->observaciones,
                'orden' => $n,
            ]);
        }
    }

    /**
     * ¿Esta partida se emplaya? Lo dice el tipo de equipo del catálogo.
     * Un paquete se emplaya si alguna de sus piezas lo requiere. Si no se
     * puede saber, se asume que sí: es más fácil desmarcarlo que olvidarlo.
     */
    private function requiereEmplayado(VentaItem $item): bool
    {
        return match ($item->tipo_item) {
            'producto' => (bool) (Producto::with('equipmentType')->find($item->producto_id)?->equipmentType?->requiere_emplayado ?? true),
            'equipo' => (bool) (Equipo::with('tipoEquipo')->find($item->equipo_id)?->tipoEquipo?->requiere_emplayado ?? true),
            'paquete' => $this->paqueteRequiereEmplayado($item->paquete_id),
            default => true,
        };
    }

    private function paqueteRequiereEmplayado(?int $paqueteId): bool
    {
        $paquete = $paqueteId ? Paquete::with(['productos.equipmentType', 'equipos.tipoEquipo'])->find($paqueteId) : null;

        if (! $paquete) {
            return true;
        }

        $tipos = $paquete->productos->map(fn ($p) => $p->equipmentType)
            ->merge($paquete->equipos->map(fn ($e) => $e->tipoEquipo))
            ->filter();

        return $tipos->isEmpty() || $tipos->contains(fn ($t) => (bool) $t->requiere_emplayado);
    }

    private function huella(string $nombre, ?string $descripcion, int $cantidad): string
    {
        return mb_strtolower($nombre.'|'.($descripcion ?? '').'|'.$cantidad);
    }
}
