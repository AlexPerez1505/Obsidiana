<?php

namespace App\Services\Administracion;

use App\Models\Trip;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Builder;

/**
 * Viajes en curso: se inician, se les van cargando gastos (GastosDeViaje)
 * y se finalizan. Lo usan la web y la API.
 */
class Viajes
{
    public const REGLAS_INICIO = [
        'vehicle_id' => ['nullable', 'exists:vehicles,id'],
        'place' => ['nullable', 'string', 'max:255'],
    ];

    public const ESTADOS = [
        Trip::STATUS_IN_PROGRESS => 'En curso',
        Trip::STATUS_COMPLETED => 'Finalizado',
    ];

    /** El administrador ve todos; los demás, solo los suyos. */
    public function visiblesPara(User $user): Builder
    {
        $query = Trip::query()->latest('started_at')->latest('id');

        if (! Administrador::pleno($user)) {
            $query->where('user_id', $user->id);
        }

        return $query;
    }

    public function puedeVer(Trip $trip, ?User $user): bool
    {
        return $user && (Administrador::pleno($user) || (int) $trip->user_id === (int) $user->id);
    }

    public function iniciar(array $data, User $quien): Trip
    {
        $data['user_id'] = $quien->id;
        $data['status'] = Trip::STATUS_IN_PROGRESS;
        $data['started_at'] = now();

        if (isset($data['vehicle_id']) && $data['vehicle_id']) {
            $vehicle = Vehicle::find($data['vehicle_id']);
            $data['vehicle_name'] = $vehicle ? "{$vehicle->brand} {$vehicle->model}" : null;
        }

        return Trip::create($data);
    }

    public function finalizar(Trip $trip): void
    {
        $trip->update([
            'status' => Trip::STATUS_COMPLETED,
            'ended_at' => now(),
        ]);
    }
}
