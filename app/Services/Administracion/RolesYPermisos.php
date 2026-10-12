<?php

namespace App\Services\Administracion;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Support\CatalogoPermisos;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

/**
 * Qué puede hacer cada rol (y, por excepción, cada usuario).
 *
 * El catálogo vive en código (CatalogoPermisos): aquí no se inventan ni se
 * borran permisos, solo se reparten. Toda llave que llega se filtra contra
 * el catálogo antes de guardarse, venga de la web o de la API.
 */
class RolesYPermisos
{
    /** El rol de administrador no se toca: siempre puede todo. */
    public const INTOCABLE = User::ROL_ADMIN;

    public const MENSAJE_INTOCABLE = 'El administrador siempre puede todo.';
    public const MENSAJE_NO_SE_ELIMINA = 'El administrador no se puede eliminar.';

    public static function reglasAlta(): array
    {
        return [
            'name' => [
                'required', 'string', 'max:60',
                'regex:/^[a-z][a-z0-9_]*$/',
                Rule::unique('roles', 'name'),
            ],
            'label' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:255'],
        ];
    }

    public const MENSAJES_ALTA = [
        'name.regex' => 'La clave va en minúsculas, sin espacios ni acentos (ej. jefe_almacen).',
        'name.unique' => 'Ya existe un rol con esa clave.',
    ];

    public const REGLAS_EDICION = [
        'label' => ['required', 'string', 'max:120'],
        'description' => ['nullable', 'string', 'max:255'],
        'is_active' => ['nullable', 'boolean'],
        'permisos' => ['nullable', 'array'],
        'permisos.*' => ['string'],
    ];

    public function esIntocable(Role $role): bool
    {
        return $role->name === self::INTOCABLE;
    }

    public function crear(array $datos): Role
    {
        return Role::create([
            'name' => $datos['name'],
            'label' => $datos['label'],
            'description' => $datos['description'] ?? null,
            'is_active' => true,
        ]);
    }

    /**
     * Guarda nombre, descripción, si está activo y exactamente los permisos
     * marcados. Devuelve las llaves que sí se guardaron.
     *
     * @return Collection<int, string>
     */
    public function actualizar(Role $role, array $datos, bool $activo): Collection
    {
        $validos = self::filtrar($datos['permisos'] ?? []);

        $role->update([
            'label' => $datos['label'],
            'description' => $datos['description'] ?? null,
            'is_active' => $activo,
        ]);

        $role->permissions()->sync($this->idsDe($validos));

        return $validos;
    }

    /**
     * Por qué no se puede eliminar (null = sí se puede). Si hay gente con
     * el rol, primero hay que moverla a otro: si no, se quedarían sin
     * permisos de golpe.
     */
    public function rechazoAlEliminar(Role $role): ?string
    {
        $cuantos = $role->users()->count();

        return $cuantos > 0
            ? "«{$role->label}» lo tienen {$cuantos} usuario(s). Cámbialos de rol antes de eliminarlo."
            : null;
    }

    public function eliminar(Role $role): void
    {
        $role->permissions()->detach();
        $role->delete();
    }

    /**
     * Deja al usuario exactamente con estos permisos directos (los que no
     * le llegan por rol). Devuelve las llaves que sí se guardaron.
     *
     * @return Collection<int, string>
     */
    public function sincronizarDeUsuario(User $user, array $llaves): Collection
    {
        $validos = self::filtrar($llaves);

        $user->permissions()->sync($this->idsDe($validos));
        $user->olvidarPermisos();

        return $validos;
    }

    /**
     * Solo las llaves que existen en el catálogo: una inventada no se
     * guarda.
     *
     * @return Collection<int, string>
     */
    public static function filtrar(iterable $llaves): Collection
    {
        return collect($llaves)
            ->filter(fn ($p) => is_string($p) && CatalogoPermisos::existe($p))
            ->unique()
            ->values();
    }

    /** Ids en la tabla de permisos (la llena `permisos:sincronizar`). */
    private function idsDe(Collection $llaves): Collection
    {
        return Permission::whereIn('name', $llaves)->pluck('id');
    }
}
