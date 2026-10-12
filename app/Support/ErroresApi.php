<?php

namespace App\Support;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

/**
 * Cómo se ven los errores en /api: siempre JSON, siempre en español.
 *
 *   { "message": "…", "codigo": "…" }                 401, 403, 404, 429…
 *   { "message": "…", "errors": { "campo": ["…"] } }  422 de validación
 *
 * Los 500 se dejan a Laravel: en local enseñan el detalle y en producción
 * solo "Server Error", sin filtrar nada.
 */
class ErroresApi
{
    private const GENERICOS = [
        400 => 'La petición está mal formada.',
        401 => 'No autenticado: falta el token o ya no es válido.',
        403 => 'No tienes permiso para hacer esto.',
        404 => 'No existe lo que buscas.',
        405 => 'Este método no se acepta en esta ruta.',
        409 => 'La operación choca con el estado actual del registro.',
        413 => 'El archivo pasa del límite del servidor.',
        419 => 'La sesión expiró.',
        422 => 'Los datos enviados no son válidos.',
        429 => 'Demasiadas peticiones. Espera un momento y vuelve a intentar.',
        503 => 'El sistema está en mantenimiento.',
    ];

    /** Mensajes en inglés que Laravel/Symfony ponen solos. */
    private const EN_INGLES = [
        '', 'This action is unauthorized.', 'Unauthenticated.', 'Not found.', 'Bad request.',
        'Too Many Attempts.', 'Forbidden', 'Not Found', 'Service Unavailable',
    ];

    public static function render(Throwable $e, Request $request): ?JsonResponse
    {
        if (! $request->is('api/*')) {
            return null;
        }

        if ($e instanceof ValidationException) {
            return self::validacion($e);
        }

        if ($e instanceof AuthenticationException) {
            return self::json(401, self::GENERICOS[401], 'no_autenticado');
        }

        if ($e instanceof PostTooLargeException) {
            return self::json(413, 'El archivo pasa del límite del servidor ('.ini_get('upload_max_filesize').').', 'archivo_muy_grande');
        }

        if ($e instanceof NotFoundHttpException) {
            if ($e->getPrevious() instanceof ModelNotFoundException) {
                return self::json(404, 'No existe el registro solicitado.', 'no_encontrado');
            }

            // Sin ruta, Symfony pone "The route … could not be found."; un
            // abort(404, '…') del código trae su propio mensaje y se respeta.
            $mensaje = $e->getMessage();
            if ($mensaje === '' || in_array($mensaje, self::EN_INGLES, true) || str_starts_with($mensaje, 'The route ')) {
                return self::json(404, 'Esta ruta no existe en la API.', 'ruta_no_encontrada');
            }

            return self::json(404, $mensaje, 'no_encontrado');
        }

        if ($e instanceof HttpExceptionInterface) {
            $status = $e->getStatusCode();
            $mensaje = in_array($e->getMessage(), self::EN_INGLES, true)
                ? (self::GENERICOS[$status] ?? 'Error.')
                : $e->getMessage();

            return self::json($status, $mensaje, self::codigo($status), $e->getHeaders());
        }

        return null;
    }

    private static function validacion(ValidationException $e): JsonResponse
    {
        $errores = $e->errors();
        $todos = collect($errores)->flatten();
        $extra = $todos->count() - 1;

        $mensaje = $todos->first() ?? self::GENERICOS[422];
        if ($extra > 0) {
            $mensaje .= " (y {$extra} más)";
        }

        return response()->json(['message' => $mensaje, 'errors' => $errores], $e->status);
    }

    private static function codigo(int $status): string
    {
        return match ($status) {
            401 => 'no_autenticado',
            403 => 'sin_permiso',
            404 => 'no_encontrado',
            405 => 'metodo_no_permitido',
            409 => 'conflicto',
            429 => 'demasiadas_peticiones',
            default => 'error_'.$status,
        };
    }

    private static function json(int $status, string $mensaje, string $codigo, array $headers = []): JsonResponse
    {
        return response()->json(['message' => $mensaje, 'codigo' => $codigo], $status, $headers);
    }
}
