<?php

namespace App\Support;

/**
 * Cuentas para transferencia según el IVA del documento.
 *
 * La regla del negocio: si el documento lleva IVA (requiere factura) el pago
 * va a la cuenta de Anahí Téllez; si no lleva IVA va a las cuentas de Gabriela
 * Díaz García. Se decide con el mismo flag aplica_iva que ya trae la
 * cotización o la venta, así que cotización y venta imprimen lo mismo.
 *
 * Los números viven en config/medibuy.php (que a su vez los lee del .env).
 */
class DatosBancarios
{
    /**
     * Cuentas no vacías del bloque que corresponde al IVA.
     *
     * @return array<int, array<string, string>>
     */
    public static function cuentas(bool $aplicaIva): array
    {
        $bloque = $aplicaIva ? 'con_iva' : 'sin_iva';

        return collect(config("medibuy.bancos.{$bloque}", []))
            ->filter(fn ($cuenta) => count(array_filter($cuenta)) > 0)
            ->values()
            ->all();
    }

    /**
     * Titular principal según el IVA, para el nombre del vendedor/firmante
     * en el contrato y la carta de garantía.
     */
    public static function beneficiario(bool $aplicaIva): string
    {
        return static::cuentas($aplicaIva)[0]['beneficiario'] ?? '';
    }
}
