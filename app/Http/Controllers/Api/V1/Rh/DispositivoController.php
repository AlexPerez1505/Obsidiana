<?php

namespace App\Http\Controllers\Api\V1\Rh;

use App\Http\Controllers\Api\V1\Concerns\ListadoApi;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Rh\DispositivoResource;
use App\Models\BiometricDevice;
use App\Services\Asistencia\RegistroDeHuellas;
use App\Services\Asistencia\ServicioDeAsistencia;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Lectores de huella: la PC con el agente que lee los dedos y la pantalla
 * del checador. La llave del agente (`api_key`, empieza con `bk_`) y la
 * URL de la pantalla solo se entregan al crear el lector o al regenerar su
 * llave: guárdalas en ese momento, después no se pueden volver a ver.
 *
 * @tags RH · Asistencia
 */
class DispositivoController extends Controller
{
    use ListadoApi;

    /**
     * Listar lectores.
     *
     * Con cuántas checadas lleva cada uno hoy y si está en línea
     * (reportó en los últimos 2 minutos).
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $request->validate([
            // active | inactive
            'status' => ['nullable', 'in:active,inactive'],
        ]);

        $q = $this->conChecadasDeHoy(BiometricDevice::query())
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->query('status')));

        $this->ordenar($q, $request, ['name', 'code', 'id', 'last_seen_at'], 'name');

        return DispositivoResource::collection($q->paginate($this->porPagina($request)));
    }

    /**
     * Ver un lector.
     */
    public function show(int $dispositivo): DispositivoResource
    {
        return new DispositivoResource($this->conChecadasDeHoy(BiometricDevice::query())->findOrFail($dispositivo));
    }

    /**
     * Crear lector.
     *
     * Devuelve, una sola vez, `api_key` (para configurar el agente) y
     * `pantalla_url` (la pantalla del checador en modo kiosco).
     */
    public function store(Request $request): DispositivoResource
    {
        $data = $request->validate(RegistroDeHuellas::reglasDispositivo());

        [$d, $llave] = BiometricDevice::crear($data + ['status' => 'active']);

        /** @status 201 */
        return (new DispositivoResource($d))->additional([
            'api_key' => $llave,
            'pantalla_url' => $d->urlPantalla(),
            'message' => "Lector «{$d->name}» creado.",
        ]);
    }

    /**
     * Regenerar la llave del lector.
     *
     * La anterior deja de servir en ese momento: el agente instalado no
     * funcionará hasta que se le ponga la nueva. Devuelve la llave nueva
     * una sola vez.
     */
    public function llave(BiometricDevice $dispositivo): DispositivoResource
    {
        $llave = $dispositivo->regenerarLlave();

        return (new DispositivoResource($dispositivo))->additional([
            'api_key' => $llave,
            'pantalla_url' => $dispositivo->urlPantalla(),
            'message' => 'Llave regenerada: el agente instalado dejará de funcionar hasta que se actualice.',
        ]);
    }

    /**
     * Activar o desactivar un lector.
     *
     * Un lector inactivo no puede checar ni descargar plantillas.
     */
    public function alternar(BiometricDevice $dispositivo): DispositivoResource
    {
        $dispositivo->update(['status' => $dispositivo->status === 'active' ? 'inactive' : 'active']);

        return (new DispositivoResource($dispositivo))
            ->additional(['message' => 'Lector '.($dispositivo->status === 'active' ? 'activado' : 'desactivado').'.']);
    }

    /** Checadas de hoy por lector (día local; como en la web, cuenta también las anuladas). */
    private function conChecadasDeHoy($query)
    {
        $hoy = ServicioDeAsistencia::diaLocal(now())->toDateString();

        return $query->withCount(['punches as checadas_hoy' => fn ($q) => $q->whereDate('work_date', $hoy)]);
    }
}
