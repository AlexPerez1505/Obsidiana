<?php

namespace App\Http\Middleware;

use App\Models\TokenApi;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Lo mismo que piden las pantallas (verified + approved), pero para la API.
 *
 * Va después de auth:sanctum. Un token sigue sirviendo solo mientras su
 * dueño tenga acceso: si lo banean o queda pendiente, la API lo rechaza
 * aunque el token no haya vencido. También revisa la lista de IP de las
 * llaves de integración y deja anotado de dónde se usó.
 */
class CuentaActivaApi
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $token = $user?->currentAccessToken();

        if ($user->isBanned()) {
            return $this->rechazo('cuenta_desactivada', 'Tu cuenta fue desactivada. Contacta al administrador.');
        }

        if (! $user->hasVerifiedEmail()) {
            return $this->rechazo('correo_no_verificado', 'Falta verificar el correo de la cuenta.');
        }

        if ($user->isPending()) {
            return $this->rechazo('cuenta_pendiente', 'La cuenta todavía no la aprueba un administrador.');
        }

        if ($token instanceof TokenApi) {
            if (! $token->ipPermitida($request->ip())) {
                return $this->rechazo('ip_no_permitida', 'Esta llave no se puede usar desde la IP '.$request->ip().'.');
            }

            $this->anotarUso($token, $request);
        }

        return $next($request);
    }

    /**
     * Guarda la IP y, en los tokens de app, recorre el vencimiento: el
     * plazo cuenta desde el último uso, no desde que se inició sesión.
     */
    private function anotarUso(TokenApi $token, Request $request): void
    {
        $cambios = [];

        if ($token->last_used_ip !== $request->ip()) {
            $cambios['last_used_ip'] = $request->ip();
        }

        if ($token->tipo === TokenApi::TIPO_APP) {
            $vence = now()->addDays(config('api.dias_token_app'));

            // Una escritura al día basta; no en cada petición.
            if (! $token->expires_at || $token->expires_at->lt($vence->copy()->subDay())) {
                $cambios['expires_at'] = $vence;
            }
        }

        if ($cambios) {
            $token->forceFill($cambios)->save();
        }
    }

    private function rechazo(string $codigo, string $mensaje): Response
    {
        return response()->json(['message' => $mensaje, 'codigo' => $codigo], 403);
    }
}
