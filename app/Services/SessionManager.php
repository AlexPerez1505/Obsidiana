<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SessionManager
{
    /**
     * Umbral (timestamp unix) a partir del cual una sesión se considera activa.
     */
    protected function threshold(): int
    {
        $lifetime = (int) config('session.lifetime', 120); // minutos
        return Carbon::now()->subMinutes($lifetime)->getTimestamp();
    }

    /**
     * Número de sesiones activas de un usuario.
     */
    public function activeCountFor(int $userId): int
    {
        return DB::table('sessions')
            ->where('user_id', $userId)
            ->where('last_activity', '>=', $this->threshold())
            ->count();
    }

    /**
     * Lista de sesiones activas de un usuario (marca la actual).
     */
    public function activeSessionsFor(int $userId, ?string $currentId = null): Collection
    {
        return DB::table('sessions')
            ->where('user_id', $userId)
            ->where('last_activity', '>=', $this->threshold())
            ->orderByDesc('last_activity')
            ->get()
            ->map(function ($s) use ($currentId) {
                return (object) [
                    'id'            => $s->id,
                    'ip_address'    => $s->ip_address,
                    'user_agent'    => $s->user_agent,
                    'last_activity' => Carbon::createFromTimestamp($s->last_activity),
                    'is_current'    => $s->id === $currentId,
                ];
            });
    }

    /**
     * Conteo de sesiones activas para varios usuarios a la vez (para el panel admin).
     *
     * @return array<int,int>  [user_id => count]
     */
    public function activeCountsForAll(): array
    {
        return DB::table('sessions')
            ->whereNotNull('user_id')
            ->where('last_activity', '>=', $this->threshold())
            ->groupBy('user_id')
            ->selectRaw('user_id, COUNT(*) as total')
            ->pluck('total', 'user_id')
            ->toArray();
    }

    /**
     * Cierra todas las demás sesiones del usuario (deja solo la actual).
     */
    public function destroyOthersFor(int $userId, string $currentId): int
    {
        return DB::table('sessions')
            ->where('user_id', $userId)
            ->where('id', '!=', $currentId)
            ->delete();
    }
}
