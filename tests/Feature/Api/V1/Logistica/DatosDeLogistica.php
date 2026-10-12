<?php

namespace Tests\Feature\Api\V1\Logistica;

use App\Models\Ruta;
use App\Models\User;
use App\Models\Vehicle;

/**
 * Datos de prueba de logística: vehículos y rutas armadas directo en la
 * base (sin pasar por la API) para probar cada endpoint por separado.
 * Las coordenadas son de Toluca, cerca del origen por defecto.
 */
trait DatosDeLogistica
{
    /** PNG de 1x1: sirve como firma válida. */
    protected string $firmaBase64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=';

    protected function vehiculo(array $atributos = []): Vehicle
    {
        return Vehicle::create($atributos + [
            'plate_number' => 'LOG-'.strtoupper(substr(uniqid(), -6)),
            'brand' => 'Nissan',
            'model' => 'NP300',
            'year' => 2022,
            'status' => Vehicle::STATUS_ACTIVE,
        ]);
    }

    /**
     * Ruta con dos paradas ubicadas. Por defecto en curso, con chofer y
     * vehículo.
     */
    protected function ruta(?User $chofer = null, string $estado = Ruta::EN_RUTA, array $atributos = []): Ruta
    {
        $ruta = Ruta::create($atributos + [
            'folio' => Ruta::siguienteFolio(),
            'estado' => $estado,
            'fecha_programada' => today(),
            'hora_salida' => '09:00',
            'vehicle_id' => $this->vehiculo()->id,
            'chofer_id' => $chofer?->id,
            'origen_nombre' => 'Bodega',
            'origen_lat' => 19.2826,
            'origen_lng' => -99.6557,
            'regresa_a_origen' => true,
            'exigir_evidencia' => true,
            'iniciada_en' => $estado === Ruta::EN_RUTA ? now() : null,
        ]);

        $ruta->paradas()->create(['orden' => 1, 'nombre' => 'Hospital Norte', 'lat' => 19.2900, 'lng' => -99.6500, 'radio_m' => 100,
            'estado' => $estado === Ruta::EN_RUTA ? 'en_camino' : 'pendiente']);
        $ruta->paradas()->create(['orden' => 2, 'nombre' => 'Clínica Sur', 'lat' => 19.2500, 'lng' => -99.6000, 'radio_m' => 100]);

        return $ruta->fresh('paradas');
    }

    /** Lo mínimo para crear una ruta por la API. */
    protected function datosRuta(array $extra = []): array
    {
        return $extra + [
            'fecha_programada' => today()->addDay()->toDateString(),
            'hora_salida' => '08:30',
            'origen_nombre' => 'Bodega',
            'origen_lat' => 19.2826,
            'origen_lng' => -99.6557,
            'regresa_a_origen' => true,
            'exigir_evidencia' => true,
            'paradas' => [
                ['nombre' => 'Hospital Norte', 'lat' => 19.2900, 'lng' => -99.6500, 'mercancia' => "2 Monitor Philips\nVentilador x3"],
                ['nombre' => 'Clínica Sur', 'lat' => 19.2500, 'lng' => -99.6000, 'urgente' => true, 'ventana_hasta' => '13:00'],
                ['nombre' => 'Consultorio Centro', 'lat' => 19.2880, 'lng' => -99.6600],
            ],
        ];
    }
}
