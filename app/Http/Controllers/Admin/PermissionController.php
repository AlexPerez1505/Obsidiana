<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\User;
use App\Services\Administracion\RolesYPermisos;
use App\Support\CatalogoPermisos;
use Illuminate\Support\Facades\Gate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PermissionController extends Controller
{
    /**
     * Lista todos los permisos del sistema.
     */
    public function index(): View
    {
        $permissions = Permission::withCount('users')->orderBy('label')->get();

        return view('admin.permissions.index', [
            'permissions' => $permissions,
        ]);
    }

    /**
     * Formulario para crear un permiso.
     */
    public function create(): View
    {
        return view('admin.permissions.create');
    }

    /**
     * Guarda un nuevo permiso.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:permissions,name', 'regex:/^[a-z_]+$/'],
            'label' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        Permission::create($data);

        return redirect()->route('admin.permissions.index')->with('status', 'Permiso creado correctamente.');
    }

    /**
     * Formulario para editar un permiso.
     */
    public function edit(Permission $permission): View
    {
        return view('admin.permissions.edit', [
            'permission' => $permission,
        ]);
    }

    /**
     * Actualiza un permiso.
     */
    public function update(Request $request, Permission $permission): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:permissions,name,'.$permission->id, 'regex:/^[a-z_]+$/'],
            'label' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $permission->update($data);

        return redirect()->route('admin.permissions.index')->with('status', 'Permiso actualizado correctamente.');
    }

    /**
     * Elimina un permiso.
     */
    public function destroy(Permission $permission): RedirectResponse
    {
        $permission->delete();

        return redirect()->route('admin.permissions.index')->with('status', 'Permiso eliminado correctamente.');
    }

    /**
     * Muestra la pantalla para asignar permisos a un usuario.
     */
    public function userPermissions(User $user): View
    {
        Gate::authorize('roles.gestionar');
        abort_if($user->isAdmin(), 403, RolesYPermisos::MENSAJE_INTOCABLE);

        return view('admin.users.permissions', [
            'user' => $user,
            'grupos' => CatalogoPermisos::grupos(),
            'concedidos' => $user->permissions()->pluck('name')->all(),
            'heredados' => $user->roles()->where('is_active', true)->with('permissions')->get()
                ->flatMap(fn ($role) => $role->permissions->pluck('name'))->unique()->values()->all(),
        ]);
    }

    /**
     * Sincroniza los permisos de un usuario.
     */
    public function updateUserPermissions(Request $request, User $user, RolesYPermisos $roles): RedirectResponse
    {
        Gate::authorize('roles.gestionar');
        abort_if($user->isAdmin(), 403, RolesYPermisos::MENSAJE_INTOCABLE);
        $data = $request->validate([
            'permisos' => ['nullable', 'array'],
            'permisos.*' => ['string'],
        ]);

        $roles->sincronizarDeUsuario($user, $data['permisos'] ?? []);

        return back()->with('status', 'Permisos de '.$user->name.' actualizados.');
    }
}
