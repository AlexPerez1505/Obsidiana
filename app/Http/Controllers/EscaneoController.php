<?php

namespace App\Http\Controllers;

use App\Services\Inventario\EscaneoDePiezas;
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
 * sin ir buscando pieza por pieza en el listado. Cómo se reconoce la
 * etiqueta vive en EscaneoDePiezas, compartido con la API.
 */
class EscaneoController extends Controller
{
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
    public function buscar(Request $request, EscaneoDePiezas $escaneo): JsonResponse
    {
        $data = $request->validate([
            'codigo' => ['required', 'string', 'max:255'],
        ]);

        $codigo = EscaneoDePiezas::codigo($data['codigo']);

        if ($codigo === null) {
            return response()->json([
                'encontrado' => false,
                'mensaje' => EscaneoDePiezas::NO_ES_ETIQUETA,
            ], 200);
        }

        $pieza = $escaneo->pieza($codigo);

        if (! $pieza) {
            return response()->json([
                'encontrado' => false,
                'codigo' => $codigo,
                'mensaje' => EscaneoDePiezas::noRegistrada($codigo),
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

            /*
            | Si la pieza se fue a un congreso, es lo primero que hay que
            | saber: no está en el almacén. Se sigue pudiendo vender (allá
            | mismo la venden), pero quien la esté buscando en el anaquel
            | necesita enterarse aquí y no después de dar la vuelta.
            */
            'congreso' => $pieza->congress?->nombre,
            'congreso_desde' => $pieza->enviado_a_congreso_en?->format('d/m/Y'),
        ]);
    }
}
