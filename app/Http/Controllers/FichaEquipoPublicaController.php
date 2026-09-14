<?php

namespace App\Http\Controllers;

use App\Models\ProductoSerial;
use App\Support\ChecklistRecepcion;
use Illuminate\View\View;

/**
 * La ficha que se abre al escanear el QR pegado a una pieza.
 *
 * Es pública a propósito: la escanea quien tiene el equipo enfrente, sin
 * cuenta en el sistema. Por eso aquí NO sale nada comercial ni interno:
 *
 *  - Sin precio, ni de venta ni ningún otro.
 *  - Sin notas de la entrada: son apuntes internos.
 *  - Sin las fotos de la evidencia de llegada: una de ellas suele ser la
 *    factura, y ahí vienen precios.
 *
 * Sí sale lo que describe a la pieza: qué es, cómo llegó, en qué estado
 * va, su foto, el video de verificación y el checklist de recepción si es
 * equipo usado.
 */
class FichaEquipoPublicaController extends Controller
{
    public function __invoke(string $codigo): View
    {
        $pieza = ProductoSerial::where('codigo', $codigo)
            ->with(['producto', 'entrada'])
            ->firstOrFail();

        $entrada = $pieza->entrada;

        return view('publico.equipo', [
            'pieza' => $pieza,
            'producto' => $pieza->producto,
            'entrada' => $entrada,

            // El checklist se arma con sus textos para no enseñar llaves.
            'checklist' => collect($entrada?->checklist_recepcion ?? [])
                ->map(fn (array $p, string $llave) => [
                    'titulo' => ChecklistRecepcion::titulo($llave),
                    'respuesta' => ChecklistRecepcion::ETIQUETAS[$p['r']] ?? $p['r'],
                    'clave' => $p['r'],
                    'nota' => $p['nota'] ?? null,
                ])
                ->sortBy(fn (array $p) => $p['clave'] === 'no' ? 0 : 1)
                ->values(),

            'estadoGeneral' => $entrada?->estado_general
                ? (ChecklistRecepcion::ESTADOS[$entrada->estado_general] ?? null)
                : null,
        ]);
    }
}
