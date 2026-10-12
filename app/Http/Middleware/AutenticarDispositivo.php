<?php

namespace App\Http\Middleware;

use App\Models\BiometricDevice;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Autentica al agente del lector por su llave de dispositivo
 * (Authorization: Bearer bk_… o cabecera X-Device-Key).
 * Cada llamada cuenta como señal de vida del dispositivo.
 */
class AutenticarDispositivo
{
    public function handle(Request $request, Closure $next): Response
    {
        $llave = $request->bearerToken() ?: $request->header('X-Device-Key');
        $dispositivo = BiometricDevice::porLlave($llave);

        if (! $dispositivo) {
            return response()->json(['ok' => false, 'message' => 'Dispositivo no autorizado.'], 401);
        }

        $dispositivo->forceFill([
            'last_seen_at' => now(),
            'last_ip' => $request->ip(),
            'agent_version' => substr((string) $request->header('X-Agent-Version', $dispositivo->agent_version), 0, 40),
        ])->save();

        $request->attributes->set('dispositivo', $dispositivo);

        return $next($request);
    }
}
