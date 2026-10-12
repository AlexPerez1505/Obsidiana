<?php

namespace App\Http\Controllers\Api\V1\Inventario;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Inventario\PiezaEscaneadaResource;
use App\Services\Inventario\EscaneoDePiezas;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Escaneo con pistola lectora: conteo físico, armar un préstamo, revisar
 * un lote sin buscar pieza por pieza.
 *
 * @tags Inventario · Escaneo
 */
class EscaneoController extends Controller
{
    /**
     * Identificar una pieza escaneada.
     *
     * Manda en `codigo` lo que tecleó la pistola: el código pelón
     * (MB-000147) o la liga completa del QR. Devuelve la pieza, si se puede
     * vender y si está en un congreso (no en el almacén). Sin precios.
     * Responde 422 si eso no es una etiqueta del sistema y 404 si la
     * etiqueta no está registrada.
     */
    public function buscar(Request $request, EscaneoDePiezas $escaneo): PiezaEscaneadaResource
    {
        $data = $request->validate([
            'codigo' => ['required', 'string', 'max:255'],
        ]);

        $codigo = EscaneoDePiezas::codigo($data['codigo']);

        if ($codigo === null) {
            throw ValidationException::withMessages(['codigo' => EscaneoDePiezas::NO_ES_ETIQUETA]);
        }

        $pieza = $escaneo->pieza($codigo);

        if (! $pieza) {
            throw new HttpException(404, EscaneoDePiezas::noRegistrada($codigo));
        }

        return new PiezaEscaneadaResource($pieza);
    }
}
