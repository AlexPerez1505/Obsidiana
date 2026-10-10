<?php

namespace Tests\Concerns;

use App\Models\Permission;
use App\Models\TokenApi;
use App\Models\User;

/**
 * Ayudas para probar /api/v1 con tokens reales (no Sanctum::actingAs:
 * su token simulado no pasa por las revisiones de IP ni de vencimiento).
 *
 *   $user = $this->usuarioApi(['clientes.ver']);
 *   $this->conToken($user)->getJson('/api/v1/clientes')->assertOk();
 *   $this->conToken($user, ['clientes.ver'], TokenApi::TIPO_INTEGRACION)->...
 */
trait UsaApi
{
    /**
     * Usuario aprobado y verificado. Los permisos se le dan directos
     * (sin rol), igual que una concesión puntual en la web.
     *
     * @param  list<string>  $permisos
     */
    protected function usuarioApi(array $permisos = [], bool $admin = false, array $atributos = []): User
    {
        $user = User::factory()->create($atributos);

        $user->forceFill([
            'is_admin' => $admin,
            'status' => User::STATUS_APPROVED,
            'approved_at' => now(),
            'email_verified_at' => now(),
        ])->save();

        if ($permisos) {
            $ids = collect($permisos)->map(fn (string $p) => Permission::firstOrCreate(['name' => $p], ['label' => $p])->id);
            $user->permissions()->syncWithoutDetaching($ids);
        }

        return $user;
    }

    /**
     * Las siguientes peticiones van con un token nuevo de ese usuario.
     *
     * @param  list<string>  $habilidades  ['*'] = todo lo que puede el usuario
     */
    protected function conToken(User $user, array $habilidades = ['*'], string $tipo = TokenApi::TIPO_APP, ?array $ips = null): static
    {
        $nuevo = TokenApi::emitir(
            $user,
            'pruebas',
            $tipo,
            $habilidades,
            $tipo === TokenApi::TIPO_APP ? now()->addDays(30) : null,
            $ips,
        );

        // El guard recuerda al usuario de la petición anterior dentro de
        // la misma prueba; sin esto el token nuevo se ignoraría.
        $this->app['auth']->forgetGuards();

        return $this->withToken($nuevo->plainTextToken)->withHeader('Accept', 'application/json');
    }
}
