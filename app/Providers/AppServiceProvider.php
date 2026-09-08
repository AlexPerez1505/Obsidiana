<?php

namespace App\Providers;

<<<<<<< Updated upstream
=======
use App\Contracts\WhatsAppSender;
use App\Models\User;
use App\Services\WhatsApp\LogWhatsAppSender;
use App\Support\CatalogoPermisos;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
>>>>>>> Stashed changes
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->registrarWhatsApp();
    }

    /**
     * Qué implementación de WhatsAppSender se usa según
     * config('services.whatsapp.driver'). Hoy solo existe "log"; cuando se
     * conecte una cuenta real se agrega su driver aquí (ej. 'meta' =>
     * MetaWhatsAppSender::class) sin tocar nada del flujo de promociones.
     */
    private function registrarWhatsApp(): void
    {
        $this->app->bind(WhatsAppSender::class, function () {
            return match (config('services.whatsapp.driver', 'log')) {
                default => new LogWhatsAppSender(),
            };
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
<<<<<<< Updated upstream
        //
=======
        $this->registrarPermisos();
        $this->registrarLimitesDeEnvio();
    }

    /**
     * Ritmo de envío de WhatsApp: 1 mensaje por segundo. Lo respetan tanto
     * las confirmaciones como las campañas (EnviarConfirmacionPromocionJob
     * y EnviarMensajeCampanaJob), para no rebasar el límite de la cuenta
     * cuando se conecte una API real.
     */
    private function registrarLimitesDeEnvio(): void
    {
        RateLimiter::for('whatsapp', fn () => Limit::perSecond(1));
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
>>>>>>> Stashed changes
    }
}
