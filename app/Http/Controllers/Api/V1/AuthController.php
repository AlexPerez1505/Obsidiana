<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\PerfilResource;
use App\Models\TokenApi;
use App\Models\User;
use App\Services\LoginLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * @tags Autenticación
 */
class AuthController extends Controller
{
    /**
     * Iniciar sesión.
     *
     * Para apps: con correo y contraseña de Obsidiana devuelve un token
     * tipo Bearer que puede lo mismo que el usuario en la web. Mándalo en
     * cada petición como `Authorization: Bearer <token>`.
     *
     * El token vence tras `API_DIAS_TOKEN_APP` días (30 por defecto) sin
     * usarse; cada uso lo renueva. Las cuentas pendientes, sin verificar o
     * desactivadas reciben 403 con un `codigo` para que la app sepa qué
     * mostrar.
     *
     * @unauthenticated
     */
    public function login(Request $request, LoginLogger $logger): JsonResponse
    {
        $datos = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            // Cómo se verá esta sesión en la lista de dispositivos.
            'dispositivo' => ['nullable', 'string', 'max:120'],
        ]);

        $user = User::where('email', $datos['email'])->first();

        if (! $user || ! Hash::check($datos['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => 'Las credenciales no coinciden con nuestros registros.',
            ]);
        }

        $rechazo = match (true) {
            $user->isBanned() => ['cuenta_desactivada', 'Tu cuenta fue desactivada. Contacta al administrador.'],
            ! $user->hasVerifiedEmail() => ['correo_no_verificado', 'Falta verificar tu correo. Entra una vez desde la web para hacerlo.'],
            $user->isPending() => ['cuenta_pendiente', 'Tu cuenta todavía no la aprueba un administrador.'],
            default => null,
        };

        if ($rechazo) {
            return response()->json(['message' => $rechazo[1], 'codigo' => $rechazo[0]], 403);
        }

        $nuevo = TokenApi::emitir(
            $user,
            $datos['dispositivo'] ?: $this->nombreDispositivo($request),
            TokenApi::TIPO_APP,
            expira: now()->addDays(config('api.dias_token_app')),
        );

        $logger->record($user, $request);

        // Para que el perfil de la respuesta salga con los permisos del token.
        $user->withAccessToken($nuevo->accessToken);

        return response()->json([
            'token' => $nuevo->plainTextToken,
            'token_type' => 'Bearer',
            'expires_at' => $nuevo->accessToken->expires_at,
            'usuario' => new PerfilResource($user),
        ]);
    }

    /**
     * Mi perfil.
     *
     * El usuario dueño del token, sus roles y la lista de permisos que
     * puede usar con este token (útil para mostrar u ocultar botones).
     */
    public function yo(Request $request): PerfilResource
    {
        return new PerfilResource($request->user());
    }

    /**
     * Cerrar sesión.
     *
     * Revoca el token con el que se hace la petición.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Sesión cerrada.']);
    }

    /**
     * Mis sesiones de app.
     *
     * Los dispositivos donde el usuario tiene sesión abierta por la API.
     * Las llaves de integración no aparecen aquí.
     */
    public function sesiones(Request $request): JsonResponse
    {
        $actual = $request->user()->currentAccessToken()->getKey();

        $sesiones = $request->user()->tokens()
            ->where('tipo', TokenApi::TIPO_APP)
            ->latest('last_used_at')
            ->get()
            ->map(fn (TokenApi $t) => [
                'id' => $t->id,
                'name' => $t->name,
                'actual' => $t->id === $actual,
                'last_used_at' => $t->last_used_at,
                'last_used_ip' => $t->last_used_ip,
                'created_at' => $t->created_at,
                'expires_at' => $t->expires_at,
            ]);

        return response()->json(['data' => $sesiones]);
    }

    /**
     * Cerrar una sesión de app.
     *
     * Revoca otro dispositivo del propio usuario (por su `id` en la lista
     * de sesiones).
     */
    public function cerrarSesion(Request $request, int $sesion): JsonResponse
    {
        $token = $request->user()->tokens()
            ->where('tipo', TokenApi::TIPO_APP)
            ->findOrFail($sesion);

        $token->delete();

        return response()->json(['message' => 'Sesión cerrada en ese dispositivo.']);
    }

    /**
     * Cerrar las demás sesiones de app.
     *
     * Deja solo la del token actual.
     */
    public function cerrarOtras(Request $request): JsonResponse
    {
        $cerradas = $request->user()->tokens()
            ->where('tipo', TokenApi::TIPO_APP)
            ->whereKeyNot($request->user()->currentAccessToken()->getKey())
            ->delete();

        return response()->json(['message' => "Se cerraron {$cerradas} sesión(es) en otros dispositivos.", 'cerradas' => $cerradas]);
    }

    /** "Android · okhttp" a partir del User-Agent, si la app no manda nombre. */
    private function nombreDispositivo(Request $request): string
    {
        $agente = (string) $request->userAgent();

        $sistema = match (true) {
            str_contains($agente, 'Android') => 'Android',
            str_contains($agente, 'iPhone') || str_contains($agente, 'iPad') || str_contains($agente, 'Darwin') => 'iOS',
            str_contains($agente, 'Windows') => 'Windows',
            str_contains($agente, 'Mac') => 'macOS',
            default => null,
        };

        return trim(($sistema ?? 'App').' · '.mb_substr(strtok($agente, ' ') ?: 'sin nombre', 0, 60));
    }
}
