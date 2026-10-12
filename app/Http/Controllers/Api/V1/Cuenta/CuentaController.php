<?php

namespace App\Http\Controllers\Api\V1\Cuenta;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Administracion\ConexionResource;
use App\Services\Cuenta\MiPerfil;
use App\Services\SessionManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Mi cuenta: historial de conexiones, sesiones del navegador y cerrar la
 * cuenta. Las sesiones de la app están en `/auth/sesiones`.
 *
 * @tags Mi cuenta
 */
class CuentaController extends Controller
{
    /**
     * Mi cuenta.
     *
     * Las últimas 30 conexiones (web y app) y las sesiones abiertas en el
     * navegador ahora mismo.
     */
    public function show(Request $request, SessionManager $sesiones): JsonResponse
    {
        $user = $request->user();

        return response()->json(['data' => [
            'conexiones' => ConexionResource::collection($user->loginLogs()->limit(30)->get()),
            'sesiones_web' => $sesiones->activeSessionsFor($user->id)->map(fn ($s) => [
                'ip_address' => $s->ip_address,
                'user_agent' => $s->user_agent,
                'last_activity' => $s->last_activity->toIso8601String(),
            ])->values(),
        ]]);
    }

    /**
     * Cerrar sesiones del navegador.
     *
     * Cierra todas las sesiones web de la cuenta (pide la contraseña). Los
     * tokens de la app no se tocan: esos se cierran en `/auth/sesiones`.
     */
    public function cerrarSesionesWeb(Request $request, SessionManager $sesiones): JsonResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        // Por la API no hay sesión web "actual" que conservar.
        $cerradas = $sesiones->destroyOthersFor($request->user()->id, '');

        return response()->json([
            'message' => "Se cerraron {$cerradas} sesión(es) en otros dispositivos.",
            'cerradas' => $cerradas,
        ]);
    }

    /**
     * Eliminar mi cuenta.
     *
     * Definitivo: pide la contraseña y `confirm` con el texto `ELIMINAR`.
     * Se borran también su historial de conexiones y todos sus tokens.
     */
    public function destroy(Request $request): JsonResponse
    {
        $request->validate(MiPerfil::reglasEliminarCuenta(), MiPerfil::MENSAJES_ELIMINAR_CUENTA);

        // Los login_logs se van en cascada y los tokens con el modelo.
        $request->user()->delete();

        return response()->json(['message' => 'Tu cuenta fue eliminada.']);
    }
}
