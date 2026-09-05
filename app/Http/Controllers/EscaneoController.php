<?php

namespace App\Http\Controllers;

use App\Models\ProductoSerial;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Pantalla de escaneo con pistola lectora.
 *
 * Una pistola no es una cámara: se comporta como un teclado que teclea
 * muy rápido y termina con Enter. Así que aquí no se "lee" nada, se
 * escuchan las teclas y de lo que sea que haya tecleado (la liga completa
 * o el puro código) se saca la etiqueta.
 *
 * Sirve para conteo físico, para armar un préstamo y para revisar un lote
 * sin ir buscando pieza por pieza en el listado.
 */
class EscaneoController extends Controller
{
    /** Patrón de una etiqueta interna: MB-000147. */
    private const PATRON = '/([A-Za-z]{2,6}-\d{4,10})/';

    public function index(): View
    {
        return view('structure.gestion_Inventario.escaneo.index');
    }

    /**
     * Devuelve la pieza que corresponde a lo escaneado.
     *
     * Acepta tanto el código pelón como la liga completa: la pistola
     * teclea lo que trae el QR, que es la URL pública.
     */
    public function buscar(Request $request): JsonResponse
    {
        $data = $request->validate([
            'codigo' => ['required', 'string', 'max:255'],
        ]);

        if (! preg_match(self::PATRON, $data['codigo'], $m)) {
            return response()->json([
                'encontrado' => false,
                'mensaje' => 'Eso no parece una etiqueta del sistema.',
            ], 200);
        }

        $codigo = strtoupper($m[1]);

        $pieza = ProductoSerial::where('codigo', $codigo)
            ->with('producto')
            ->first();

        if (! $pieza) {
            return response()->json([
                'encontrado' => false,
                'codigo' => $codigo,
                'mensaje' => "La etiqueta {$codigo} no está registrada.",
            ], 200);
        }

        $producto = $pieza->producto;

        /*
        | Se manda lo justo para la lista. Nada de precios: esta pantalla
        | la usa cualquiera con la pistola en la mano.
        */
        return response()->json([
            'encontrado' => true,
            'codigo' => $pieza->codigo,
            'equipo' => trim(collect([$producto?->tipo_equipo, $producto?->subtipo])->filter()->implode(' · ')) ?: 'Equipo',
            'marca_modelo' => trim(collect([$producto?->marca, $producto?->modelo])->filter()->implode(' ')),
            'no_serie' => $pieza->no_serie,
            'estado' => $pieza->estado,
            'estado_label' => $pieza->estadoLabel(),
            'condicion' => $pieza->condicion,
            'vendible' => $pieza->vendible(),
            'foto' => $pieza->fotoUrl()
                ?: ($producto?->imagen_path ? asset('storage/'.$producto->imagen_path) : null),
            'ficha' => $pieza->urlPublica(),
        ]);
    }
}
