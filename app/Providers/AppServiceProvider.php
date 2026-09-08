<?php

namespace App\Providers;

use App\Models\User;
use App\Support\CatalogoPermisos;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->registrarPermisos();
    }

    /**
     * Cada permiso del catálogo queda como un Gate.
     *
     * Así se usa igual en todos lados sin inventar helpers propios:
     *
     *   @can('clientes.eliminar')       en las vistas
     *   $this->authorize('ventas.crear') en los controladores
     *   ->middleware('can:procesos.ver') en las rutas
     */
    private function registrarPermisos(): void
    {
        /*
        | El administrador pasa antes de cualquier revisión.
        |
        | Devolver true corta la evaluación; devolver null la deja seguir
        | su curso normal. Ojo: NO devolver false aquí, eso negaría todo
        | permiso a los demás usuarios sin llegar a revisarlos.
        */
        Gate::before(fn (User $user) => $user->isAdmin() ? true : null);

        foreach (CatalogoPermisos::llaves() as $permiso) {
            Gate::define($permiso, fn (User $user) => $user->hasPermission($permiso));
        }
    }
}
