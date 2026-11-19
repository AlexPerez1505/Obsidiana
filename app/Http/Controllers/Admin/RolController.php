<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Services\Administracion\RolesYPermisos;
use App\Support\CatalogoPermisos;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
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
            'usuarios' => \App\Models\User::where('is_admin', false)
                ->whereDoesntHave('roles', fn ($query) => $query->where('name', \App\Models\User::ROL_ADMIN)->where('is_active', true))
                ->orderBy('name')->get(['id', 'name']),
            'grupos' => CatalogoPermisos::grupos(),
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
        // La clave siempre se guarda en minúsculas: se acepta que la
        // escriban en mayúsculas y aquí se normaliza antes de validar.
        $request->merge(['name' => Str::lower(trim((string) $request->input('name')))]);

        $data = $request->validate(RolesYPermisos::reglasAlta(), RolesYPermisos::MENSAJES_ALTA);

        $role = $this->roles->crear($data);

        return redirect()->route('configuracion.roles.edit', $role)
            ->with('status', "Rol «{$role->label}» creado. Ahora elige qué puede hacer.");
    }

    /**
     * Permisos de UNA cuenta dentro de un rol: se puede dar un permiso
     * que el rol no tenga o quitarle uno que sí, sin tocar al rol.
     */
    public function editUsuario(Role $role, \App\Models\User $user): View
    {
        abort_unless($role->users()->where('users.id', $user->id)->exists(), 404, 'Esa cuenta no tiene este rol.');
        abort_if($user->isAdmin(), 403, 'El administrador siempre tiene todo; no hay nada que ajustar.');

        $delRol = $role->permissions->pluck('name');
        $otorgados = $user->permissions()->wherePivot('concedido', true)->pluck('name');
        $quitados = $user->permissions()->wherePivot('concedido', false)->pluck('name');
        $activos = $delRol->merge($otorgados)->diff($quitados)->unique()->values()->all();

        return view('structure.Configuracion.roles.usuario', [
            'role' => $role->load('permissions'),
            'usuario' => $user,
            'grupos' => CatalogoPermisos::grupos(),
            // Lo que el rol da como base y lo que se le ajustó a él.
            'activos' => $activos,
            'delRol' => $delRol->all(),
            'otorgados' => $otorgados->all(),
            'quitados' => $quitados->all(),
        ]);
    }

    /** Guarda los ajustes individuales: lo que difiera del rol se anota en permission_user. */
    public function updateUsuario(Request $request, Role $role, \App\Models\User $user): RedirectResponse
    {
        abort_unless($role->users()->where('users.id', $user->id)->exists(), 404, 'Esa cuenta no tiene este rol.');
        abort_if($user->isAdmin(), 403, 'El administrador siempre tiene todo; no hay nada que ajustar.');

        $data = $request->validate([
            'permisos' => ['nullable', 'array'],
            'permisos.*' => ['string'],
        ]);

        $catalogo = collect(CatalogoPermisos::grupos())->flatMap(fn ($g) => array_keys($g['permisos']))->all();
        $deseados = array_intersect($data['permisos'] ?? [], $catalogo);

        $delRol = $role->permissions->pluck('name')->all();
        $ids = \Illuminate\Support\Facades\DB::table('permissions')->whereIn('name', $catalogo)->pluck('id', 'name');

        \Illuminate\Support\Facades\DB::transaction(function () use ($user, $ids, $delRol, $deseados) {
            $ahora = now();
            foreach ($ids as $nombre => $id) {
                $activo = in_array($nombre, $deseados, true);
                $heredado = in_array($nombre, $delRol, true);

                if ($activo === $heredado) {
                    \Illuminate\Support\Facades\DB::table('permission_user')
                        ->where('user_id', $user->id)->where('permission_id', $id)->delete();
                    continue;
                }
                \Illuminate\Support\Facades\DB::table('permission_user')->updateOrInsert(
                    ['user_id' => $user->id, 'permission_id' => $id],
                    ['concedido' => $activo, 'updated_at' => $ahora, 'created_at' => $ahora]
                );
            }
        });

        return redirect()->route('configuracion.roles.usuario.edit', [$role, $user])
            ->with('status', 'Permisos individuales de '.$user->name.' actualizados.');
    }

    /** Elimina las excepciones de la cuenta para que vuelva a heredar el rol. */
    public function restablecerUsuario(Role $role, \App\Models\User $user): RedirectResponse
    {
        abort_unless($role->users()->where('users.id', $user->id)->exists(), 404, 'Esa cuenta no tiene este rol.');
        abort_if($user->isAdmin(), 403, 'El administrador siempre tiene todo; no hay nada que ajustar.');

        \Illuminate\Support\Facades\DB::table('permission_user')
            ->where('user_id', $user->id)
            ->delete();

        return redirect()->route('configuracion.roles.usuario.edit', [$role, $user])
            ->with('status', $user->name.' volvió a heredar todos los permisos de '.$role->label.'.');
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
