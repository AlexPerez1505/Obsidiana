<?php

namespace App\Http\Controllers\Api\V1\Administracion;

use App\Http\Controllers\Api\V1\Concerns\ListadoApi;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Administracion\RolResource;
use App\Models\Role;
use App\Services\Administracion\RolesYPermisos;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Roles y lo que puede hacer cada uno (Configuración → Roles y permisos).
 *
 * Todo pide `roles.gestionar`, igual que la pantalla. El rol de
 * administrador siempre puede todo: no se edita ni se borra.
 *
 * @tags Administración · Roles y permisos
 */
class RolController extends Controller
{
    use ListadoApi;

    public function __construct(private RolesYPermisos $roles)
    {
    }

    /**
     * Listar roles.
     *
     * Con sus permisos (llaves del catálogo) y cuántos usuarios lo tienen.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Role::with('permissions')->withCount('users');

        return RolResource::collection(
            $this->ordenar($query, $request, ['id', 'name', 'label'], 'id')->paginate($this->porPagina($request))
        );
    }

    /**
     * Ver un rol.
     *
     * Sus permisos y los usuarios que lo tienen.
     */
    public function show(Role $role): RolResource
    {
        return new RolResource($role->load(['permissions', 'users'])->loadCount('users'));
    }

    /**
     * Crear rol.
     *
     * `name` es la clave (minúsculas, sin espacios ni acentos, ej.
     * `jefe_almacen`). Nace activo y sin permisos: se le dan con
     * `PUT /roles/{role}`.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate(RolesYPermisos::reglasAlta(), RolesYPermisos::MENSAJES_ALTA);

        $role = $this->roles->crear($data);

        return (new RolResource($role->load('permissions')->loadCount('users')))
            ->additional(['message' => "Rol «{$role->label}» creado. Ahora elige qué puede hacer."])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Editar rol y sus permisos.
     *
     * Guarda nombre, descripción, si está activo y EXACTAMENTE las llaves
     * de `permisos` (las que no estén se quitan; las que no existan en el
     * catálogo se ignoran). Un rol inactivo deja de otorgar sus permisos.
     */
    public function update(Request $request, Role $role): RolResource
    {
        abort_if($this->roles->esIntocable($role), 403, RolesYPermisos::MENSAJE_INTOCABLE);

        $data = $request->validate(RolesYPermisos::REGLAS_EDICION);

        $validos = $this->roles->actualizar($role, $data, $request->boolean('is_active', true));

        return (new RolResource($role->fresh()->load('permissions')->loadCount('users')))
            ->additional(['message' => "Se guardó lo que puede hacer «{$role->label}»: {$validos->count()} permiso(s)."]);
    }

    /**
     * Eliminar rol.
     *
     * No se puede si alguien lo tiene (409): primero hay que cambiarlos de
     * rol, si no se quedarían sin permisos de golpe.
     */
    public function destroy(Role $role): JsonResponse
    {
        abort_if($this->roles->esIntocable($role), 403, RolesYPermisos::MENSAJE_NO_SE_ELIMINA);

        if ($rechazo = $this->roles->rechazoAlEliminar($role)) {
            abort(409, $rechazo);
        }

        $etiqueta = $role->label;
        $this->roles->eliminar($role);

        return response()->json(['message' => "Rol «{$etiqueta}» eliminado."]);
    }
}
