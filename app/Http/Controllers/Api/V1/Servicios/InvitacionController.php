<?php

namespace App\Http\Controllers\Api\V1\Servicios;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Servicios\InvitacionResource;
use App\Services\Servicios\OrdenesDeServicio;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @tags Servicios
 */
class InvitacionController extends Controller
{
    /**
     * Invitar a registrar una orden.
     *
     * Genera un enlace de un solo uso, válido 24 horas, para que alguien
     * sin cuenta (un cliente, un técnico externo) llene la orden desde el
     * navegador. La orden queda registrada a nombre de quien invitó.
     */
    public function store(Request $request, OrdenesDeServicio $ordenes): JsonResponse
    {
        $invitacion = $ordenes->invitar($request->user()->id);

        return (new InvitacionResource($invitacion->refresh()))
            ->additional(['message' => 'Invitación creada. El enlace vence en 24 horas.'])
            ->response()
            ->setStatusCode(201);
    }
}
