<?php

namespace App\Http\Controllers\Asistencia;

use App\Http\Controllers\Controller;
use App\Models\BiometricDevice;
use App\Services\Asistencia\EventosDelChecador;
use App\Services\Asistencia\ServicioDeAsistencia;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * La pantalla fija del checador (modo kiosko). Sin sesión: se abre con el
 * token del dispositivo en la URL, solo muestra y no puede modificar nada.
 * Muestra lo que el lector acaba de leer; el lector habla con la API, no con
 * esta página.
 */
class PantallaChecadorController extends Controller
{
    public function pantalla(string $token): View
    {
        $d = $this->dispositivo($token);

        return view('asistencia.pantalla', [
            'dispositivo' => $d,
            'tz' => ServicioDeAsistencia::tz(),
            'segundos' => (int) config('asistencia.pantalla_segundos', 3),
            'empresa' => config('app.name'),
            'urlFeed' => route('checador.feed', $token),
        ]);
    }

    public function feed(Request $request, string $token): JsonResponse
    {
        $d = $this->dispositivo($token);
        $despues = (int) $request->query('after', -1);

        // Primera consulta: solo se entera del último id, sin repetir el pasado
        $cola = EventosDelChecador::desde($d->id, max(0, $despues));
        $eventos = $despues < 0 ? [] : $cola['eventos'];

        return response()->json([
            'now' => now()->getTimestampMs(),
            'ultimo' => $cola['ultimo'],
            'eventos' => $eventos,
            // Sin señal del agente en 2 minutos: la pantalla avisa que el lector no responde
            'lector_en_linea' => $d->fresh()->enLinea(),
        ]);
    }

    private function dispositivo(string $token): BiometricDevice
    {
        return BiometricDevice::where('kiosk_token', $token)->where('status', 'active')->firstOrFail();
    }
}
