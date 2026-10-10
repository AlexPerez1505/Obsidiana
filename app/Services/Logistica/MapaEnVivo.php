<?php

namespace App\Services\Logistica;

use App\Models\Ruta;
use App\Models\Vehicle;
use Illuminate\Support\Collection;

/**
 * Qué se ve en el mapa en vivo de la flota: las rutas en curso con su
 * última posición y los vehículos activos que no van en ninguna.
 */
class MapaEnVivo
{
    /** @return Collection<int, Ruta> */
    public function rutasEnCurso(): Collection
    {
        return Ruta::with(['vehicle', 'chofer', 'paradas', 'ultimaUbicacion'])
            ->where('estado', Ruta::EN_RUTA)
            ->get();
    }

    /**
     * Los vehículos disponibles también aparecen en el tablero.
     *
     * @param  Collection<int, Ruta>  $enCurso
     * @return Collection<int, Vehicle>
     */
    public function vehiculosLibres(Collection $enCurso): Collection
    {
        return Vehicle::where('status', Vehicle::STATUS_ACTIVE)
            ->whereNotIn('id', $enCurso->pluck('vehicle_id')->filter()->all())
            ->orderBy('plate_number')->get();
    }
}
