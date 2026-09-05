<?php

namespace App\Support;

use App\Models\Producto;
use App\Models\User;

/**
 * Quién puede ver y definir el precio de un producto en Inventario.
 *
 * La regla vive aquí y no repartida entre vistas y controladores: si solo
 * se escondiera el campo en el formulario, el precio seguiría viajando en
 * la respuesta del buscador de modelos y en el HTML de los listados, que
 * es donde de verdad se ve.
 */
class PrecioVisible
{
    /**
     * ¿Puede ver precios? El administrador siempre; los demás, si su rol
     * trae `precios.ver` (Configuración → Roles y permisos).
     */
    public static function para(?User $usuario = null): bool
    {
        $usuario ??= auth()->user();

        return (bool) $usuario?->can('precios.ver');
    }

    /** ¿Puede cambiarlos? Ver no alcanza: hace falta `precios.editar`. */
    public static function editable(?User $usuario = null): bool
    {
        $usuario ??= auth()->user();

        return (bool) $usuario?->can('precios.editar');
    }

    /**
     * ¿Hay que pedir el precio en esta entrada?
     *
     * No se pide cuando el usuario no puede definirlo, ni cuando el modelo
     * ya tiene uno registrado: la segunda entrada del mismo equipo no vuelve
     * a preguntar algo que ya se sabe.
     */
    public static function sePide(?Producto $producto, ?User $usuario = null): bool
    {
        return static::editable($usuario) && $producto?->precio === null;
    }

    /** Texto del precio, o null si a este usuario no le toca verlo. */
    public static function texto(?Producto $producto, ?User $usuario = null): ?string
    {
        if (! static::para($usuario)) {
            return null;
        }

        return $producto?->precio === null
            ? 'Sin precio definido'
            : '$'.number_format((float) $producto->precio, 2);
    }
}
