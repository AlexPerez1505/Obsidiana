<?php

namespace App\Support;

use App\Models\InventoryMovement;
use App\Models\Producto;
use App\Models\ProductoSerial;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * Quién puede corregir o borrar lo que entra al inventario.
 *
 * Siempre puede quien tiene el permiso (inventario.editar para corregir,
 * inventario.eliminar para borrar). Además, quien registró algo tiene un
 * plazo de HORAS horas para arreglar sus propios errores de captura sin
 * pedírselo a nadie; pasado ese plazo queda como cualquier otro registro.
 *
 * El plazo se cuenta desde que se creó el registro y solo aplica a lo que
 * tiene autor guardado: los productos de antes de que existiera esta regla
 * no tienen created_by y para ellos solo cuenta el permiso.
 *
 * Todo pasa por $user->can(), así que respeta al administrador y el recorte
 * de los tokens de la API (una llave sin inventario.registrar no usa el
 * plazo propio aunque su dueño sí registre).
 */
class EdicionPropia
{
    public const HORAS = 8;

    public static function puedeEditarProducto(?User $user, Producto $producto): bool
    {
        return static::puede($user, 'inventario.editar', $producto->created_by, $producto->created_at);
    }

    public static function puedeEliminarProducto(?User $user, Producto $producto): bool
    {
        return static::puede($user, 'inventario.eliminar', $producto->created_by, $producto->created_at);
    }

    /**
     * Agregarle piezas a un producto es editarlo: lo puede quien edita
     * inventario o quien lo dio de alta, dentro de su plazo.
     */
    public static function puedeAgregarPiezas(?User $user, Producto $producto): bool
    {
        return static::puedeEditarProducto($user, $producto);
    }

    public static function puedeEditarPieza(?User $user, ProductoSerial $pieza): bool
    {
        return static::puede($user, 'inventario.editar', $pieza->capturado_por, $pieza->created_at);
    }

    public static function puedeEliminarPieza(?User $user, ProductoSerial $pieza): bool
    {
        return static::puede($user, 'inventario.eliminar', $pieza->capturado_por, $pieza->created_at);
    }

    public static function puedeEliminarMovimiento(?User $user, InventoryMovement $movimiento): bool
    {
        return static::puede($user, 'inventario.eliminar', $movimiento->created_by, $movimiento->created_at);
    }

    /** ¿Sigue corriendo el plazo propio de este usuario sobre ese registro? */
    public static function enPlazo(?User $user, ?int $autorId, ?Carbon $creado): bool
    {
        return $user !== null
            && $autorId !== null
            && (int) $autorId === (int) $user->id
            && $creado !== null
            && $creado->gt(now()->subHours(static::HORAS))
            // Quien registró lo hizo con este permiso; una llave de la API
            // que no lo trae tampoco hereda el plazo.
            && $user->can('inventario.registrar');
    }

    /** Cuándo se acaba el plazo, o null si ya no hay plazo para este usuario. */
    public static function venceEn(?User $user, ?int $autorId, ?Carbon $creado): ?Carbon
    {
        return static::enPlazo($user, $autorId, $creado)
            ? $creado->copy()->addHours(static::HORAS)
            : null;
    }

    /** "5 h 20 min" que le quedan, para avisarlo en pantalla. */
    public static function restanteTexto(?Carbon $vence): ?string
    {
        if (! $vence || $vence->isPast()) {
            return null;
        }

        $minutos = (int) ceil(now()->diffInMinutes($vence));
        $horas = intdiv($minutos, 60);
        $resto = $minutos % 60;

        return trim(($horas ? "{$horas} h " : '').($resto ? "{$resto} min" : ''));
    }

    /** Mensaje del 403 cuando ya no se puede. */
    public static function mensaje(string $accion = 'corregir'): string
    {
        return "Ya no puedes {$accion} esto: quien lo registra tiene ".static::HORAS
            .' horas para hacerlo y luego solo quien tiene permiso de inventario.';
    }

    private static function puede(?User $user, string $permiso, ?int $autorId, ?Carbon $creado): bool
    {
        if (! $user) {
            return false;
        }

        return $user->can($permiso) || static::enPlazo($user, $autorId, $creado);
    }
}
