<?php

namespace App\Support;

use App\Models\Venta;
use Barryvdh\DomPDF\Facade\Pdf;

/**
 * Documentos que viajan junto a la venta cuando se descarga su PDF.
 *
 * La regla vive aquí y no en cada controlador: el PDF que baja el asesor y el
 * que baja el cliente desde su liga pública deben traer exactamente lo mismo.
 */
class AnexosVenta
{
    /**
     * @return array<int, array{titulo: string, contenido: string}>
     */
    public static function para(Venta $venta): array
    {
        $venta->loadMissing(['customer', 'seller', 'items', 'pagos']);

        $anexos = [];

        // El contrato solo existe cuando se paga a plazos.
        if ($venta->requiereContrato()) {
            $anexos[] = [
                'titulo' => 'Contrato de compraventa',
                'contenido' => self::render('contrato', $venta),
            ];
        }

        // La carta garantía ampara el equipo de cualquier venta.
        $anexos[] = [
            'titulo' => 'Carta garantía',
            'contenido' => self::render('garantia', $venta),
        ];

        return $anexos;
    }

    private static function render(string $vista, Venta $venta): string
    {
        return Pdf::loadView("structure.commercial_management.ventas.{$vista}", ['venta' => $venta])
            ->setPaper('letter')
            ->output();
    }
}
