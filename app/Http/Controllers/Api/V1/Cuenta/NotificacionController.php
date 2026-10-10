<?php

namespace App\Http\Controllers\Api\V1\Cuenta;

use App\Http\Controllers\Api\V1\Concerns\ListadoApi;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Cuenta\NotificacionResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * La campana: avisos del propio usuario (citas de agenda, seguimientos,
 * piezas en congresos…).
 *
 * @tags Mi cuenta
 */
class NotificacionController extends Controller
{
    use ListadoApi;

    /**
     * Mis notificaciones.
     *
     * De la más reciente a la más vieja. `no_leidas=1` deja solo las
     * pendientes. `meta.no_leidas` es el número de la campana.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $request->validate(['no_leidas' => ['nullable', 'boolean']]);

        $query = $request->boolean('no_leidas')
            ? $request->user()->unreadNotifications()
            : $request->user()->notifications();

        return NotificacionResource::collection($query->latest()->paginate($this->porPagina($request)))
            ->additional(['meta' => ['no_leidas' => $request->user()->unreadNotifications()->count()]]);
    }

    /**
     * Marcar como leída.
     *
     * Lo que en la web pasa al abrirla desde la campana.
     */
    public function leer(Request $request, string $id): NotificacionResource
    {
        $notificacion = $request->user()->notifications()->whereKey($id)->firstOrFail();

        $notificacion->markAsRead();

        return (new NotificacionResource($notificacion))
            ->additional(['meta' => ['no_leidas' => $request->user()->unreadNotifications()->count()]]);
    }

    /**
     * Marcar todas como leídas.
     */
    public function leerTodas(Request $request): JsonResponse
    {
        $marcadas = $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return response()->json([
            'message' => 'Notificaciones marcadas como leídas.',
            'marcadas' => $marcadas,
        ]);
    }
}
