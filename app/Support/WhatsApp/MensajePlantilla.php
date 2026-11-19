<?php

namespace App\Support\WhatsApp;

/**
 * Un mensaje de plantilla aprobada, ya armado y sin depender del proveedor.
 *
 * Plantilla esperada (categoría Marketing, ver Promociones → Configuración):
 *   encabezado: imagen (opcional)
 *   cuerpo: {{1}} nombre, {{2}} oferta, {{3}} código, {{4}} vencimiento
 *   botón 0: URL dinámica  https://<dominio>/p/{{1}}  → sufijo = token
 *   botón 1: respuesta rápida "Ya no quiero promociones" (opcional)
 */
class MensajePlantilla
{
    /** @param  array<int, string>  $variables  en orden {{1}}, {{2}}… */
    public function __construct(
        public readonly string $telefono,
        public readonly string $plantilla,
        public readonly string $idioma,
        public readonly array $variables,
        public readonly ?string $imagenUrl = null,
        public readonly ?string $botonUrlSufijo = null,
        public readonly bool $botonBaja = false,
        public readonly ?string $referencia = null,
    ) {
    }

    /**
     * Meta rechaza variables vacías o con saltos de línea, tabuladores o más
     * de 4 espacios seguidos. Se limpian aquí, en un solo lugar.
     */
    public static function limpiarVariable(?string $texto, int $max = 900): string
    {
        $t = trim(preg_replace('/\s+/u', ' ', (string) $texto) ?? '');

        return $t === '' ? '-' : mb_substr($t, 0, $max);
    }
}
