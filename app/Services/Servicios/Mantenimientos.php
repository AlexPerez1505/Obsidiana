<?php

namespace App\Services\Servicios;

use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * El tablero de mantenimiento (qué trae cada técnico) y la hoja de
 * reporte de cada servicio. Lo comparten la pantalla web y la API.
 */
class Mantenimientos
{
    /**
     * Técnicos internos con cuántos servicios en progreso traen, más una
     * opción "externos" que agrupa todo lo que salió a taller foráneo
     * (ahí no hay un técnico nuestro a quien cargárselo).
     *
     * @return Collection<int, object{id: int|string, name: string, email: string, initials: string, count: int, count_label: string, is_external: bool}>
     */
    public function tecnicos(): Collection
    {
        $internalTechnicians = User::where('status', User::STATUS_APPROVED)
            ->where(function ($q) {
                $q->whereRaw('LOWER(name) LIKE ?', ['%joel%'])
                    ->orWhereRaw('LOWER(name) LIKE ?', ['%icelda%']);
            })
            ->orderBy('name')
            ->get()
            ->map(function (User $technician) {
                $activeCount = Service::where('internal_technician_id', $technician->id)
                    ->where('status', 'en_progreso')
                    ->count();

                return (object) [
                    'id' => $technician->id,
                    'name' => $technician->name,
                    'email' => $technician->email,
                    'initials' => collect(explode(' ', $technician->name))->map(fn ($w) => strtoupper(substr($w, 0, 1)))->take(2)->join(''),
                    'count' => $activeCount,
                    'count_label' => 'activo',
                    'is_external' => false,
                ];
            });

        $externalTotal = Service::where('service_type', 'externo')->count();

        $externalOption = (object) [
            'id' => 'externo',
            'name' => 'Mantenimientos externos',
            'email' => 'Todos los servicios externos',
            'initials' => 'EXT',
            'count' => $externalTotal,
            'count_label' => 'servicio',
            'is_external' => true,
        ];

        return $internalTechnicians->toBase()->push($externalOption)->values();
    }

    /**
     * Cuál mostrar: ?tipo=externo manda; si no, el ?tecnico= pedido, y si
     * no existe, el primero de la lista.
     *
     * @param  Collection<int, object>  $tecnicos
     */
    public function seleccionar(Collection $tecnicos, ?string $tipo, mixed $tecnico): ?object
    {
        if ($tipo === 'externo') {
            return $tecnicos->firstWhere('is_external', true);
        }

        return $tecnicos->firstWhere('id', (int) $tecnico) ?? $tecnicos->first();
    }

    /**
     * Servicios del seleccionado: de un técnico interno, solo los que trae
     * en progreso (lo que tiene en la mesa); de externos, todos.
     */
    public function servicios(object $seleccionado): Builder
    {
        $query = $seleccionado->is_external
            ? Service::where('service_type', 'externo')
            : Service::where('internal_technician_id', $seleccionado->id)->where('status', 'en_progreso');

        return $query->with(['customer', 'currentStep'])->latest();
    }

    /**
     * Lo que la hoja de reporte trae prellenado de la orden. La marca cae
     * en Fujinon porque la hoja es la de Fujinon y casi todo lo que entra
     * es de esa marca.
     *
     * @return array{cliente: string, folio: string, equipo: string, marca: string, modelo: string, ns: string, diagnostico: string, fecha: string, tecnico: string}
     */
    public function datosDelReporte(Service $service): array
    {
        $service->loadMissing(['customer', 'serviceEquipment', 'internalTechnician', 'externalTechnician']);

        $equipo = $service->serviceEquipment;
        $tecnico = $service->service_type === 'interno'
            ? $service->internalTechnician?->name
            : $service->externalTechnician?->name;

        return [
            'cliente' => trim(($service->customer?->nombre ?? '').' '.($service->customer?->apellido ?? '')),
            'folio' => $service->service_number ?? '',
            'equipo' => $equipo?->type_text ?? '',
            'marca' => $equipo?->brand_text ?: 'Fujinon',
            'modelo' => $equipo?->model_text ?? '',
            'ns' => $equipo?->serial_number ?? '',
            'diagnostico' => $equipo?->description ?? '',
            'fecha' => $service->created_at?->format('d/m/Y') ?? '',
            'tecnico' => $tecnico ?? '',
        ];
    }

    /** La hoja tal como se guardó la última vez (null si nunca se guardó). */
    public function reporteGuardado(Service $service): ?array
    {
        return $service->reporte_data ? json_decode($service->reporte_data, true) : null;
    }

    /**
     * Guarda la hoja completa (lo que arma el editor). Si no llega un
     * arreglo se borra: así se puede "reiniciar" un reporte.
     */
    public function guardarReporte(Service $service, mixed $reporte): void
    {
        $service->forceFill([
            'reporte_data' => is_array($reporte) ? json_encode($reporte, JSON_UNESCAPED_UNICODE) : null,
        ])->save();
    }
}
