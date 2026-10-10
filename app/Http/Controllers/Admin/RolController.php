<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Services\Administracion\RolesYPermisos;
use App\Support\CatalogoPermisos;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Roles y lo que puede hacer cada uno.
 *
 * El catálogo de permisos vive en código (CatalogoPermisos); aquí solo se
 * decide cuáles tiene cada rol. Las reglas viven en RolesYPermisos, que
 * comparte con la API.
 */
class RolController extends Controller
{
    public function __construct(private RolesYPermisos $roles)
    {
    }

    public function index(): View
    {
        $roles = Role::with(['permissions', 'users'])->orderBy('id')->get();

        return view('structure.Configuracion.roles.index', [
            'roles' => $roles,
            'grupos' => CatalogoPermisos::grupos(),
            'totalPermisos' => count(CatalogoPermisos::llaves()),
        ]);
    }

    public function edit(Role $role): View
    {
        abort_if($this->roles->esIntocable($role), 403, RolesYPermisos::MENSAJE_INTOCABLE);

        $role->load(['permissions', 'users']);

        return view('structure.Configuracion.roles.edit', [
            'role' => $role,
            'grupos' => CatalogoPermisos::grupos(),
            'concedidos' => $role->permissions->pluck('name')->all(),
        ]);
    }

    /** Guarda exactamente los permisos marcados. */
    public function update(Request $request, Role $role): RedirectResponse
    {
        abort_if($this->roles->esIntocable($role), 403, RolesYPermisos::MENSAJE_INTOCABLE);

        $data = $request->validate(RolesYPermisos::REGLAS_EDICION);

        // Se filtra contra el catálogo: una llave inventada no se guarda.
        $validos = $this->roles->actualizar($role, $data, $request->boolean('is_active', true));

        return redirect()->route('configuracion.roles.index')
            ->with('status', "Se guardó lo que puede hacer «{$role->label}»: {$validos->count()} permiso(s).");
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(RolesYPermisos::reglasAlta(), RolesYPermisos::MENSAJES_ALTA);

        $role = $this->roles->crear($data);

        return redirect()->route('configuracion.roles.edit', $role)
            ->with('status', "Rol «{$role->label}» creado. Ahora elige qué puede hacer.");
    }

    /**
     * Elimina un rol. No se puede si hay gente usándolo: primero hay que
     * moverla a otro, si no se quedarían sin permisos de golpe.
     */
    public function destroy(Role $role): RedirectResponse
    {
        abort_if($this->roles->esIntocable($role), 403, RolesYPermisos::MENSAJE_NO_SE_ELIMINA);

        if ($rechazo = $this->roles->rechazoAlEliminar($role)) {
            return back()->withErrors(['rol' => $rechazo]);
        }

        $etiqueta = $role->label;
        $this->roles->eliminar($role);

        return redirect()->route('configuracion.roles.index')
            ->with('status', "Rol «{$etiqueta}» eliminado.");
    }
}
