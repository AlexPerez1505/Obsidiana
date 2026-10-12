<?php

namespace App\Http\Controllers\Api\V1\Logistica\Concerns;

use App\Models\Ruta;
use App\Models\RutaParada;
use App\Services\Logistica\EvidenciaIncompleta;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Los servicios de logística avisan sus reglas ("la ruta no está en curso",
 * "ya está cerrada"…) con DomainException, que la web convierte en un
 * mensaje de error. En la API esa misma regla es un 409 con el mismo texto;
 * lo que falta para cerrar una entrega es un 422 (dato faltante).
 */
trait ReglasDeRuta
{
    /**
     * @template T
     *
     * @param  callable(): T  $operacion
     * @return T
     */
    protected function operar(callable $operacion): mixed
    {
        try {
            return $operacion();
        } catch (EvidenciaIncompleta $e) {
            throw ValidationException::withMessages(['evidencia' => $e->getMessage()]);
        } catch (\DomainException $e) {
            abort(409, $e->getMessage());
        }
    }

    /** El chofer solo opera sus rutas (ni un admin opera la ruta de otro, igual que en la web). */
    protected function propia(Request $request, Ruta $ruta, ?RutaParada $parada = null): void
    {
        abort_unless($ruta->chofer_id === $request->user()->id, 403, 'Esta ruta no está asignada a ti.');

        if ($parada) {
            abort_unless($parada->ruta_id === $ruta->id, 404, 'Esa parada no es de esta ruta.');
        }
    }
}
