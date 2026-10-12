<?php

namespace App\Http\Controllers\Api\V1\Cuenta;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Cuenta\TarjetaResource;
use App\Models\User;
use App\Support\DashboardWidgets;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * El tablero de inicio: las tarjetas que cada quien eligió, con sus datos.
 * El acomodo se guarda por usuario y es el mismo que ve en la web.
 *
 * @tags Mi cuenta · Tablero
 */
class TableroController extends Controller
{
    /**
     * Mi tablero.
     *
     * Las tarjetas activas en su orden, con tamaño (`w` columnas de 4, `h`
     * renglones) y sus datos ya calculados; una tarjeta más grande trae
     * más desglose. Si el token no tiene el permiso del módulo de una
     * tarjeta, sale con `disponible: false` y sin datos.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        return TarjetaResource::collection($this->tarjetas($request->user()))
            ->additional(['meta' => ['personalizado' => DashboardWidgets::normalizar($request->user()->dashboard_widgets) !== []]]);
    }

    /**
     * Tarjetas disponibles.
     *
     * Todo lo que se puede poner en el tablero, con su tamaño por omisión
     * y sus límites (`w_min`, `w_max`, `h_min`, `h_max`).
     */
    public function catalogo(Request $request): JsonResponse
    {
        $tarjetas = collect(DashboardWidgets::catalogo())
            ->map(fn ($def, string $id) => ['id' => $id] + DashboardWidgets::definicion($id) + [
                'disponible' => DashboardWidgets::puedeVer($id, $request->user()),
            ])
            ->values();

        return response()->json(['data' => $tarjetas]);
    }

    /**
     * Guardar mi acomodo.
     *
     * `widgets` es la lista ordenada de tarjetas `{id, w, h}`. Las que no
     * existen o se repiten se descartan y los tamaños se recortan a sus
     * límites. Una lista vacía deja el tablero sin tarjetas personalizadas
     * (vuelve al de fábrica).
     */
    public function guardar(Request $request): AnonymousResourceCollection
    {
        $request->validate([
            'widgets' => ['present', 'array'],
            'widgets.*.id' => ['required', 'string'],
            'widgets.*.w' => ['required', 'integer'],
            'widgets.*.h' => ['required', 'integer'],
        ]);

        $request->user()->update(['dashboard_widgets' => DashboardWidgets::normalizar($request->input('widgets', []))]);

        return TarjetaResource::collection($this->tarjetas($request->user()))
            ->additional(['message' => 'Tablero actualizado.']);
    }

    /**
     * Restablecer mi tablero.
     *
     * Vuelve al acomodo de fábrica.
     */
    public function restablecer(Request $request): AnonymousResourceCollection
    {
        $request->user()->update(['dashboard_widgets' => null]);

        return TarjetaResource::collection($this->tarjetas($request->user()))
            ->additional(['message' => 'Tablero restaurado al acomodo original.']);
    }

    /** Solo se calculan los datos de lo que sí puede ver. */
    private function tarjetas(User $user): array
    {
        return collect(DashboardWidgets::paraUsuario($user))
            ->map(function (array $w) use ($user) {
                $disponible = DashboardWidgets::puedeVer($w['id'], $user);

                return [
                    'id' => $w['id'],
                    'w' => $w['w'],
                    'h' => $w['h'],
                    'definicion' => DashboardWidgets::definicion($w['id']),
                    'disponible' => $disponible,
                    'datos' => $disponible ? DashboardWidgets::datos($w['id'], $user, $w['w'], $w['h']) : null,
                ];
            })
            ->all();
    }
}
