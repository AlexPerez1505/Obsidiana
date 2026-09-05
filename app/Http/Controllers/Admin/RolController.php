<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use App\Support\CatalogoPermisos;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Roles y lo que puede hacer cada uno.
 *
 * El catálogo de permisos vive en código (CatalogoPermisos); aquí solo se
 * decide cuáles tiene cada rol.
 */
class RolController extends Controller
{
    /** El rol de administrador no se toca: siempre puede todo. */
    private const INTOCABLE = 'admin';

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
        abort_if($role->name === self::INTOCABLE, 403, 'El administrador siempre puede todo.');

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
        abort_if($role->name === self::INTOCABLE, 403, 'El administrador siempre puede todo.');

        $data = $request->validate([
            'label' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
            'permisos' => ['nullable', 'array'],
            'permisos.*' => ['string'],
        ]);

        // Se filtra contra el catálogo: una llave inventada no se guarda.
        $validos = collect($data['permisos'] ?? [])
            ->filter(fn (string $p) => CatalogoPermisos::existe($p))
            ->values();

        $ids = Permission::whereIn('name', $validos)->pluck('id');

        $role->update([
            'label' => $data['label'],
            'description' => $data['description'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        $role->permissions()->sync($ids);

        return redirect()->route('configuracion.roles.index')
            ->with('status', "Se guardó lo que puede hacer «{$role->label}»: {$validos->count()} permiso(s).");
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => [
                'required', 'string', 'max:60',
                'regex:/^[a-z][a-z0-9_]*$/',
                Rule::unique('roles', 'name'),
            ],
            'label' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:255'],
        ], [
            'name.regex' => 'La clave va en minúsculas, sin espacios ni acentos (ej. jefe_almacen).',
            'name.unique' => 'Ya existe un rol con esa clave.',
        ]);

        $role = Role::create($data + ['is_active' => true]);

        return redirect()->route('configuracion.roles.edit', $role)
            ->with('status', "Rol «{$role->label}» creado. Ahora elige qué puede hacer.");
    }

    /**
     * Elimina un rol. No se puede si hay gente usándolo: primero hay que
     * moverla a otro, si no se quedarían sin permisos de golpe.
     */
    public function destroy(Role $role): RedirectResponse
    {
        abort_if($role->name === self::INTOCABLE, 403, 'El administrador no se puede eliminar.');

        $cuantos = $role->users()->count();

        if ($cuantos > 0) {
            return back()->withErrors([
                'rol' => "«{$role->label}» lo tienen {$cuantos} usuario(s). Cámbialos de rol antes de eliminarlo.",
            ]);
        }

        $etiqueta = $role->label;
        $role->permissions()->detach();
        $role->delete();

        return redirect()->route('configuracion.roles.index')
            ->with('status', "Rol «{$etiqueta}» eliminado.");
    }
}
