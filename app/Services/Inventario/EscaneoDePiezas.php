<?php

namespace App\Services\Inventario;

use App\Models\ProductoSerial;

/**
 * Qué pieza es la que se escaneó.
 *
 * Una pistola no es una cámara: se comporta como un teclado que teclea muy
 * rápido y termina con Enter. Teclea lo que trae el QR, que es la liga
 * pública de la pieza; de lo que sea que llegue (la liga completa o el puro
 * código) se saca la etiqueta interna.
 */
class EscaneoDePiezas
{
    /** Patrón de una etiqueta interna: MB-000147. */
    private const PATRON = '/([A-Za-z]{2,6}-\d{4,10})/';

    public const NO_ES_ETIQUETA = 'Eso no parece una etiqueta del sistema.';

    /** La etiqueta (en mayúsculas) dentro de lo leído, o null si no trae una. */
    public static function codigo(string $leido): ?string
    {
        return preg_match(self::PATRON, $leido, $m) ? strtoupper($m[1]) : null;
    }

    public static function noRegistrada(string $codigo): string
    {
        return "La etiqueta {$codigo} no está registrada.";
    }

    /**
     * El status del equipo de un vistazo, para la pistola en Entrada y
     * salida: dónde está, si se puede vender, qué le falta, si ya se vendió
     * (a quién y cómo va su salida) o si está en un congreso.
     *
     * @return array<string, mixed>
     */
    public function estatus(ProductoSerial $pieza): array
    {
        $pieza->loadMissing(['producto', 'congress', 'procesos', 'entrada', 'ventaItem.venta.customer', 'ventaItem.venta.ordenSalida.asignadaA']);
        $producto = $pieza->producto;
        $venta = $pieza->ventaItem?->venta;
        $orden = $venta?->ordenSalida;
        $cliente = $venta ? trim(($venta->customer?->nombre ?? '').' '.($venta->customer?->apellido ?? '')) : '';
        $almacen = $pieza->entrada?->warehouse;
        $falta = $pieza->faltaTexto();
        $user = auth()->user();

        [$tono, $resumen] = match (true) {
            $pieza->estado === 'baja' => ['danger', 'Dado de baja: ya no forma parte del inventario.'],
            $pieza->vendido || $pieza->estado === 'vendido' => ['warn', 'Vendido'.($venta ? " en {$venta->folio}" : '').($cliente ? " a {$cliente}" : '')
                .($orden ? '. Salida: '.mb_strtolower($orden->estadoLabel()).($orden->asignadaA ? " (prepara {$orden->asignadaA->name})" : '') : '').'.'],
            $pieza->enCongreso() => ['info', 'Está en el congreso '.$pieza->congress?->nombre.($pieza->enviado_a_congreso_en ? ' desde el '.$pieza->enviado_a_congreso_en->format('d/m/Y') : '').': no está en el almacén.'],
            $falta !== null => ['warn', "No se puede vender todavía: le falta {$falta}."],
            ! $pieza->vendible() => ['warn', 'Todavía no se puede vender ('.mb_strtolower($pieza->estadoLabel()).').'],
            default => ['ok', 'Disponible para venta'.($almacen ? " en {$almacen}" : '').'.'],
        };

        $pendiente = $pieza->procesos->first(fn ($p) => in_array($p->estado, ['pendiente', 'en_curso'], true));

        return [
            'encontrado' => true,
            'codigo' => $pieza->codigo,
            'equipo' => trim(collect([$producto?->tipo_equipo, $producto?->subtipo])->filter()->implode(' · ')) ?: 'Equipo',
            'marca_modelo' => trim(collect([$producto?->marca, $producto?->modelo])->filter()->implode(' ')),
            'no_serie' => $pieza->no_serie,
            'condicion' => $pieza->condicion,
            'foto' => $pieza->fotoUrl() ?: ($producto?->imagen_path ? asset('storage/'.$producto->imagen_path) : null),
            'estado' => $pieza->estado,
            'estado_label' => $pieza->vendido ? 'Vendido' : $pieza->estadoLabel(),
            'tono' => $tono,
            'resumen' => $resumen,
            'vendible' => $pieza->vendible(),
            'almacen' => $almacen,
            'procesos' => $pieza->procesos->map(fn ($p) => ['nombre' => $p->nombre(), 'estado' => $p->estado])->values(),
            'entrada' => $pieza->entrada ? [
                'folio' => $pieza->entrada->folio,
                'fecha' => $pieza->entrada->movement_date?->format('d/m/Y'),
                'url' => route('inventory.movimientos.show', $pieza->entrada),
            ] : null,
            'venta' => $venta ? [
                'folio' => $venta->folio,
                'cliente' => $cliente,
                'url' => $user?->can('ventas.ver') ? route('commercial.ventas.show', $venta) : null,
            ] : null,
            'salida' => $orden ? [
                'folio' => $orden->folio,
                'estado' => $orden->estadoLabel(),
                'prepara' => $orden->asignadaA?->name,
                'url' => $user?->can('salidas.ver') ? route('inventory.salidas.show', $orden) : null,
            ] : null,
            'proceso_url' => $pendiente && $user?->can('procesos.ver') ? route('inventory.procesos.show', $pendiente) : null,
            'congreso' => $pieza->congress?->nombre,
            'ficha' => $pieza->urlPublica(),
        ];
    }

    /** La pieza con su producto y, si se fue a uno, su congreso. */
    public function pieza(string $codigo): ?ProductoSerial
    {
        return ProductoSerial::where('codigo', $codigo)
            ->with(['producto', 'congress'])
            ->first();
    }
}
