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

    /** La pieza con su producto y, si se fue a uno, su congreso. */
    public function pieza(string $codigo): ?ProductoSerial
    {
        return ProductoSerial::where('codigo', $codigo)
            ->with(['producto', 'congress'])
            ->first();
    }
}
