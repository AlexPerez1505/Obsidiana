<?php

namespace App\Http\Controllers\Api\V1\Administracion;

use App\Http\Controllers\Controller;
use App\Support\CatalogoPermisos;
use Illuminate\Http\JsonResponse;

/**
 * El catálogo de permisos, en solo lectura.
 *
 * Los permisos viven en código (CatalogoPermisos): un permiso solo
 * significa algo si hay código que lo revisa. Por eso aquí no se crean,
 * renombran ni borran; se reparten en `/roles/{role}` y, por excepción,
 * en `/usuarios/{user}/permisos`.
 *
 * @tags Administración · Roles y permisos
 */
class PermisoController extends Controller
{
    /**
     * Catálogo de permisos.
     *
     * Agrupados por módulo, como en la pantalla de roles: cada grupo trae
     * su título, descripción y sus llaves con el texto que se muestra.
     * Las llaves son las que aceptan `/roles/{role}` y
     * `/usuarios/{user}/permisos`.
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => self::catalogo(),
            'total' => count(CatalogoPermisos::llaves()),
        ]);
    }

    /** @return list<array{clave: string, titulo: string, descripcion: string, permisos: list<array{llave: string, etiqueta: string}>}> */
    public static function catalogo(): array
    {
        return collect(CatalogoPermisos::grupos())
            ->map(fn (array $g, string $clave) => [
                'clave' => $clave,
                'titulo' => $g['titulo'],
                'descripcion' => $g['descripcion'],
                'permisos' => collect($g['permisos'])
                    ->map(fn (string $etiqueta, string $llave) => ['llave' => $llave, 'etiqueta' => $etiqueta])
                    ->values()
                    ->all(),
            ])
            ->values()
            ->all();
    }
}
