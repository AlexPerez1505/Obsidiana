<?php

namespace App\Providers;

use App\Models\Cotizacion;
use App\Models\TokenApi;
use App\Models\User;
use App\Models\Venta;
use App\Support\CatalogoPermisos;
use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use Dedoc\Scramble\Support\RouteInfo;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

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
        $this->configurarApi();
        $this->sincronizarGarantias();
    }

    /**
     * Las garantías siguen a la venta: al registrarla, editarla, cancelarla
     * o entregar su equipo se recalculan sus expedientes. Corre al terminar
     * la transacción (ya con renglones y seriales) y nunca tumba la venta.
     * Respaldo diario: `garantias:sincronizar`.
     */
    private function sincronizarGarantias(): void
    {
        $programar = fn (?int $ventaId) => \App\Services\Garantias\GeneradorDeGarantias::programar($ventaId);

        Venta::saved(fn (Venta $v) => $programar($v->id));
        Venta::deleting(function (Venta $v) {
            try {
                app(\App\Services\Garantias\GeneradorDeGarantias::class)->ventaEliminada($v);
            } catch (\Throwable $e) {
                report($e);
            }
        });
        \App\Models\VentaItem::saved(fn ($i) => $programar($i->venta_id));
        \App\Models\VentaItem::deleted(fn ($i) => $programar($i->venta_id));
        \App\Models\OrdenSalida::saved(function ($o) use ($programar) {
            if ($o->wasChanged('estado') || $o->wasRecentlyCreated) {
                $programar($o->venta_id);
            }
        });
        \App\Models\ProductoSerial::saved(function ($s) use ($programar) {
            foreach (array_filter([$s->venta_item_id, $s->getOriginal('venta_item_id')]) as $itemId) {
                $programar(\App\Models\VentaItem::whereKey($itemId)->value('venta_id'));
            }
        });
    }

    /**
     * Tokens, límites de peticiones y documentación de /api/v1.
     */
    private function configurarApi(): void
    {
        Sanctum::usePersonalAccessTokenModel(TokenApi::class);

        // Por token (cada app o integración tiene su propio cupo); sin
        // token, por IP.
        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(config('api.limite_por_minuto'))
            ->by('api:'.($request->user()?->currentAccessToken()?->getKey() ?? $request->ip())));

        RateLimiter::for('api-login', fn (Request $request) => Limit::perMinute(config('api.limite_login_por_minuto'))
            ->by('api-login:'.Str::lower((string) $request->input('email')).'|'.$request->ip()));

        // Tienda en línea (pública): cada límite con su propia cuenta por IP.
        RateLimiter::for('tienda', fn (Request $request) => Limit::perMinute(120)->by('tienda:'.$request->ip()));
        RateLimiter::for('tienda-solicitudes', fn (Request $request) => Limit::perMinutes(10, 5)->by('tienda-sol:'.$request->ip()));

        // La documentación (/docs/api) la ve cualquier usuario con acceso.
        Gate::define('viewApiDocs', fn (?User $user = null) => (bool) $user?->isApproved());

        Scramble::configure()
            ->withDocumentTransformers(function (OpenApi $openApi) {
                $openApi->secure(SecurityScheme::http('bearer'));
            });

        // Un solo grupo por endpoint: el `@tags` del controlador si lo trae
        // (Scramble, por su cuenta, le sumaría además el nombre de la clase).
        Scramble::resolveTagsUsing(function (RouteInfo $ruta) {
            $doc = $ruta->isClassBased() ? (string) $ruta->reflectionMethod()?->getDeclaringClass()->getDocComment() : '';

            if (preg_match('/@tags\s+(.+)/', $doc, $m)) {
                return array_map('trim', explode(',', trim($m[1])));
            }

            return [Str::of(class_basename((string) $ruta->className()))->replaceLast('Controller', '')->headline()->toString() ?: 'API'];
        });
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
        /*
        | Por la API, el token recorta: una llave de integración solo puede
        | los permisos que se le marcaron, aunque su dueño pueda más (incluso
        | si el dueño es administrador). Va antes que la regla del admin.
        | En la web no hay token y esto no hace nada.
        */
        Gate::before(function (User $user, string $permiso) {
            $token = $user->currentAccessToken();

            if ($token && CatalogoPermisos::existe($permiso) && ! $token->can($permiso)) {
                return false;
            }

            return null;
        });

        Gate::before(fn (User $user) => $user->isAdmin() ? true : null);

        foreach (CatalogoPermisos::llaves() as $permiso) {
            Gate::define($permiso, fn (User $user) => $user->hasPermission($permiso));
        }
    }
}
