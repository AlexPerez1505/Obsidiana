<?php

namespace App\Providers;

use App\Models\Cotizacion;
use App\Models\User;
use App\Models\Venta;
use App\Support\CatalogoPermisos;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
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
        $this->limitarDocumentosPorAsesor();
    }

    /**
     * Una cotización o venta de otro asesor no se abre tecleando su id.
     *
     * Se resuelve aquí, en el binding de la ruta, para que aplique a todo
     * lo que recibe {cotizacion} o {venta}: detalle, edición, PDF, contrato,
     * cobranza... sin tener que repetir la revisión en cada método. Las
     * consultas públicas (QR) usan {token}, así que no pasan por aquí.
     */
    private function limitarDocumentosPorAsesor(): void
    {
        foreach (['cotizacion' => Cotizacion::class, 'venta' => Venta::class] as $parametro => $modelo) {
            Route::bind($parametro, function (string $valor) use ($modelo) {
                $documento = $modelo::findOrFail($valor);
                $usuario = auth()->user();

                abort_if($usuario && ! $documento->visiblePara($usuario), 403, 'Este documento lo hizo otro asesor.');

                return $documento;
            });
        }
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
