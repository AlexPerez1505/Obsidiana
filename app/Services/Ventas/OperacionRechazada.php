<?php

namespace App\Services\Ventas;

use App\Support\ErroresApi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Una regla del negocio dijo que no: venta cancelada, equipo ya entregado,
 * cobro mayor al saldo, PIN incorrecto…
 *
 * El servicio solo dice qué regla se rompió y sobre qué campo; cada
 * pantalla decide cómo enseñarlo. La web la atrapa y regresa al formulario
 * con el error en ese campo, como siempre. En /api nadie la atrapa y se
 * convierte sola en el JSON de errores de la API: 422 cuando es un dato que
 * el usuario puede corregir, 409 cuando choca con el estado del registro.
 *
 * Extiende RuntimeException a propósito: la venta rápida de la web ya
 * atrapaba esas excepciones para enseñar el mensaje, y así lo sigue haciendo.
 */
class OperacionRechazada extends RuntimeException
{
    public function __construct(
        string $mensaje,
        public readonly string $campo = 'venta',
        public readonly int $status = 409,
    ) {
        parent::__construct($mensaje);
    }

    /** El registro no está en un estado que permita esto (409). */
    public static function conflicto(string $mensaje, string $campo = 'venta'): self
    {
        return new self($mensaje, $campo, 409);
    }

    /** Un dato que el usuario capturó no cuadra (422). */
    public static function datoInvalido(string $campo, string $mensaje): self
    {
        return new self($mensaje, $campo, 422);
    }

    public function esConflicto(): bool
    {
        return $this->status === 409;
    }

    /**
     * No es una falla del sistema: no va al log ni a Sentry. Devolver algo
     * distinto de false le dice a Laravel que ya se reportó.
     */
    public function report(): bool
    {
        return true;
    }

    /**
     * Solo para /api. En la web regresa false y Laravel sigue su manejo
     * normal (aunque ahí los controladores la atrapan antes de llegar aquí).
     */
    public function render(Request $request): JsonResponse|false
    {
        if (! $request->is('api/*')) {
            return false;
        }

        $equivalente = $this->status === 422
            ? ValidationException::withMessages([$this->campo => $this->getMessage()])
            : new HttpException($this->status, $this->getMessage());

        return ErroresApi::render($equivalente, $request) ?? false;
    }
}
