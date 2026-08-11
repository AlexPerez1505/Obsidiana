<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\User;
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
        $permissions = Permission::orderBy('label')->get();
        $userPermissions = $user->permissions->pluck('pivot.level', 'id')->toArray();

        return view('admin.users.permissions', [
            'user'            => $user,
            'permissions'     => $permissions,
            'userPermissions' => $userPermissions,
        ]);
    }

    /**
     * Sincroniza los permisos de un usuario.
     */
    public function updateUserPermissions(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'permissions'         => ['array'],
            'permissions.*.id'    => ['required', 'exists:permissions,id'],
            'permissions.*.level' => ['required', 'in:enabled,read_only,edit,admin'],
        ]);

        $sync = [];
        foreach ($data['permissions'] ?? [] as $perm) {
            $sync[$perm['id']] = ['level' => $perm['level']];
        }
        $user->permissions()->sync($sync);

        return back()->with('status', 'Permisos de '.$user->name.' actualizados.');
    }
}
