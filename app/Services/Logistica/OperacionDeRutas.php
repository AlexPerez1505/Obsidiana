<?php

namespace App\Services\Logistica;

use App\Models\Ruta;
use App\Models\RutaEvidencia;
use App\Models\RutaIncidencia;
use App\Models\RutaParada;
use App\Models\RutaUbicacion;
use App\Support\Geo;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Lo que pasa mientras la ruta se recorre: salida, posición GPS, llegada
 * automática por geocerca, entrega con evidencias y cierre.
 */
class OperacionDeRutas
{
    /** Cuántos metros más allá del radio se considera que el vehículo ya salió. */
    private const HOLGURA_SALIDA = 1.5;

    public function iniciar(Ruta $ruta, int $userId): void
    {
        if ($ruta->estado !== Ruta::PROGRAMADA) {
            throw new \DomainException('Solo se puede iniciar una ruta programada.');
        }

        if (! $ruta->chofer_id || ! $ruta->vehicle_id) {
            throw new \DomainException('Asigna chofer y vehículo antes de iniciar.');
        }

        DB::transaction(function () use ($ruta, $userId) {
            $ruta->update(['estado' => Ruta::EN_RUTA, 'iniciada_en' => now()]);
            $ruta->registrarEvento('salida', 'La ruta salió', null, $userId);
            $this->marcarSiguienteEnCamino($ruta);
        });
    }

    /**
     * Guarda un punto GPS y revisa las geocercas. Devuelve los eventos
     * automáticos que se dispararon (para que el chofer los vea al instante).
     *
     * @return array<int, string>
     */
    public function registrarUbicacion(Ruta $ruta, array $d): array
    {
        if (! $ruta->activa()) {
            return [];
        }

        $momento = isset($d['registrada_en']) ? \Illuminate\Support\Carbon::parse($d['registrada_en']) : now();

        RutaUbicacion::create([
            'ruta_id' => $ruta->id,
            'vehicle_id' => $ruta->vehicle_id,
            'lat' => $d['lat'],
            'lng' => $d['lng'],
            'velocidad_kmh' => isset($d['velocidad_ms']) ? round(max(0, $d['velocidad_ms']) * 3.6, 1) : ($d['velocidad_kmh'] ?? null),
            'precision_m' => $d['precision'] ?? null,
            'rumbo' => $d['rumbo'] ?? null,
            'registrada_en' => $momento->greaterThan(now()) ? now() : $momento,
        ]);

        $avisos = [];

        if ($this->vigilarDesviacion($ruta, $d['lat'], $d['lng'])) {
            $avisos[] = 'Te saliste de la ruta planeada';
        }

        foreach ($ruta->paradas()->get() as $p) {
            $dist = $p->distanciaA($d['lat'], $d['lng']);

            if ($dist === null) {
                continue;
            }

            // Arribo automático: entra al radio de una parada pendiente
            if (in_array($p->estado, [RutaParada::PENDIENTE, RutaParada::EN_CAMINO], true) && $dist <= $p->radio_m) {
                $p->update([
                    'estado' => RutaParada::ARRIBO,
                    'llegada_en' => now(),
                    'llegada_origen' => 'auto',
                ]);
                $ruta->registrarEvento('arribo', "Arribo automático a {$p->nombre}", $p, $ruta->chofer_id, $d['lat'], $d['lng']);
                $avisos[] = "Llegaste a {$p->nombre}";
                continue;
            }

            // Salida automática: ya cerró la entrega y se alejó de la geocerca
            if ($p->estado === RutaParada::COMPLETADA && $p->llegada_en && ! $p->salida_en
                && $dist > $p->radio_m * self::HOLGURA_SALIDA) {
                $p->update(['salida_en' => now()]);
                $ruta->registrarEvento('salida_parada', "Salió de {$p->nombre}", $p, $ruta->chofer_id, $d['lat'], $d['lng']);
            }
        }

        $this->marcarSiguienteEnCamino($ruta);

        return $avisos;
    }

    /** Llegada marcada a mano (cuando el GPS no alcanzó a detectarla). */
    public function marcarLlegada(RutaParada $parada, int $userId, ?float $lat = null, ?float $lng = null): void
    {
        $this->exigirActiva($parada->ruta);

        if ($parada->resuelta() || $parada->estado === RutaParada::ARRIBO) {
            return;
        }

        $parada->update(['estado' => RutaParada::ARRIBO, 'llegada_en' => now(), 'llegada_origen' => 'manual']);
        $parada->ruta->registrarEvento('arribo', "Llegada marcada en {$parada->nombre}", $parada, $userId, $lat, $lng);
    }

    /**
     * Cierra una entrega con sus evidencias.
     *
     * @param  array{receptor_nombre?: ?string, firma?: ?string, lat?: ?float, lng?: ?float, entregados?: array}  $d
     * @param  UploadedFile[]  $fotos
     */
    public function completar(RutaParada $parada, array $d, array $fotos, ?UploadedFile $remision, int $userId): void
    {
        $ruta = $parada->ruta;
        $this->exigirActiva($ruta);

        if ($parada->resuelta()) {
            throw new \DomainException('Esta parada ya está cerrada.');
        }

        if ($ruta->exigir_evidencia) {
            $faltan = [];
            if (empty($d['lat']) || empty($d['lng'])) {
                $faltan[] = 'la ubicación (activa el GPS)';
            }
            if (! count($fotos)) {
                $faltan[] = 'al menos una foto';
            }
            if (blank($d['receptor_nombre'] ?? null)) {
                $faltan[] = 'el nombre de quien recibe';
            }
            if (blank($d['firma'] ?? null)) {
                $faltan[] = 'la firma';
            }
            if ($faltan) {
                throw new \DomainException('Para cerrar la entrega falta: '.implode(', ', $faltan).'.');
            }
        }

        $disco = config('filesystems.fotos_disk', 'public');

        DB::transaction(function () use ($parada, $ruta, $d, $fotos, $remision, $userId, $disco) {
            foreach ($fotos as $foto) {
                RutaEvidencia::create([
                    'ruta_parada_id' => $parada->id,
                    'tipo' => 'foto',
                    'archivo' => $foto->store('logistica/evidencias', $disco),
                    'lat' => $d['lat'] ?? null,
                    'lng' => $d['lng'] ?? null,
                    'subida_por' => $userId,
                ]);
            }

            if ($remision) {
                RutaEvidencia::create([
                    'ruta_parada_id' => $parada->id,
                    'tipo' => 'remision',
                    'archivo' => $remision->store('logistica/evidencias', $disco),
                    'lat' => $d['lat'] ?? null,
                    'lng' => $d['lng'] ?? null,
                    'subida_por' => $userId,
                ]);
            }

            $parada->update([
                'estado' => RutaParada::COMPLETADA,
                'llegada_en' => $parada->llegada_en ?? now(),
                'llegada_origen' => $parada->llegada_origen ?? 'manual',
                'completada_en' => now(),
                'receptor_nombre' => $d['receptor_nombre'] ?? null,
                'firma_path' => $this->guardarFirma($d['firma'] ?? null, $disco),
                'cierre_lat' => $d['lat'] ?? null,
                'cierre_lng' => $d['lng'] ?? null,
            ]);

            $ruta->registrarEvento('entrega', "Entrega completada en {$parada->nombre}", $parada, $userId, $d['lat'] ?? null, $d['lng'] ?? null);

            // La orden de salida ligada se cierra con la firma de quien recibió
            if ($orden = $parada->ordenSalida) {
                $cerro = $orden->entregarPorRuta($userId, $d['receptor_nombre'] ?? null, $parada->firma_path, now());
                $ruta->registrarEvento(
                    'orden_salida',
                    $cerro
                        ? "Orden de salida {$orden->folio} cerrada como entregada"
                        : "La orden {$orden->folio} aún no estaba lista para firma: ciérrala desde Órdenes de salida",
                    $parada, $userId
                );
            }

            $this->cerrarSiTermino($ruta, $userId);
            $this->marcarSiguienteEnCamino($ruta);
        });
    }

    public function noEntregada(RutaParada $parada, string $motivo, int $userId, ?float $lat = null, ?float $lng = null): void
    {
        $ruta = $parada->ruta;
        $this->exigirActiva($ruta);

        if ($parada->resuelta()) {
            throw new \DomainException('Esta parada ya está cerrada.');
        }

        DB::transaction(function () use ($parada, $ruta, $motivo, $userId, $lat, $lng) {
            $parada->update([
                'estado' => RutaParada::NO_ENTREGADA,
                'motivo_no_entrega' => $motivo,
                'completada_en' => now(),
                'salida_en' => now(),
                'cierre_lat' => $lat,
                'cierre_lng' => $lng,
            ]);

            $ruta->registrarEvento('no_entregada', "No entregada en {$parada->nombre}: {$motivo}", $parada, $userId, $lat, $lng);

            $this->cerrarSiTermino($ruta, $userId);
            $this->marcarSiguienteEnCamino($ruta);
        });
    }

    public function cancelar(Ruta $ruta, int $userId): void
    {
        if ($ruta->cerrada()) {
            throw new \DomainException('La ruta ya está cerrada.');
        }

        $ruta->update(['estado' => Ruta::CANCELADA, 'finalizada_en' => now()]);
        $this->cerrarRegistro($ruta);
        $ruta->registrarEvento('cancelada', 'Ruta cancelada', null, $userId);
    }

    public function finalizar(Ruta $ruta, int $userId): void
    {
        if (! $ruta->activa()) {
            throw new \DomainException('Solo se puede finalizar una ruta en curso.');
        }

        $ruta->update(['estado' => Ruta::FINALIZADA, 'finalizada_en' => now()]);
        $this->cerrarRegistro($ruta);
        $ruta->registrarEvento('finalizada', 'Ruta finalizada', null, $userId);
    }

    /**
     * El chofer reporta un problema en el camino. Queda ligado a la ruta (y
     * a la parada, si aplica) y aparece en el tablero de incidencias.
     */
    public function reportarIncidencia(Ruta $ruta, array $d, ?UploadedFile $foto, int $userId): RutaIncidencia
    {
        $parada = ! empty($d['ruta_parada_id']) ? $ruta->paradas()->find($d['ruta_parada_id']) : null;

        $incidencia = $ruta->incidencias()->create([
            'ruta_parada_id' => $parada?->id,
            'user_id' => $userId,
            'tipo' => $d['tipo'],
            'descripcion' => $d['descripcion'] ?? null,
            'foto' => $foto?->store('logistica/incidencias', config('filesystems.fotos_disk', 'public')),
            'lat' => $d['lat'] ?? null,
            'lng' => $d['lng'] ?? null,
        ]);

        $ruta->registrarEvento('incidencia', 'Incidencia: '.$incidencia->tipoLabel().($incidencia->descripcion ? ' — '.$incidencia->descripcion : ''), $parada, $userId, $d['lat'] ?? null, $d['lng'] ?? null);

        return $incidencia;
    }

    // ------------------------------------------------------------------

    private function exigirActiva(Ruta $ruta): void
    {
        if (! $ruta->activa()) {
            throw new \DomainException('La ruta no está en curso.');
        }
    }

    /** Si todas las paradas ya se resolvieron, la ruta se cierra sola. */
    private function cerrarSiTermino(Ruta $ruta, int $userId): void
    {
        if ($ruta->paradas()->get()->every(fn (RutaParada $p) => $p->resuelta())) {
            $ruta->update(['estado' => Ruta::FINALIZADA, 'finalizada_en' => now()]);
            $this->cerrarRegistro($ruta);
            $ruta->registrarEvento('finalizada', 'Todas las paradas resueltas: ruta finalizada', null, $userId);
        }
    }

    /** Al cerrar la ruta: se fijan los kilómetros reales y se cierra cualquier desviación abierta. */
    private function cerrarRegistro(Ruta $ruta): void
    {
        $ruta->update(['km_recorridos' => $ruta->calcularKm()]);
        $ruta->desviaciones()->whereNull('fin_en')->update(['fin_en' => now()]);
    }

    /**
     * Compara la posición con el trazo planeado. Abre una desviación tras
     * varias lecturas seguidas fuera del margen (un solo punto malo del GPS
     * no cuenta) y la cierra cuando el vehículo vuelve. Devuelve true solo
     * en el momento en que se abre.
     */
    private function vigilarDesviacion(Ruta $ruta, float $lat, float $lng): bool
    {
        // El trazo local son líneas rectas entre paradas: no sirve para vigilar
        if ($ruta->optimizada_con !== 'google') {
            return false;
        }

        $trazo = $ruta->trazo();
        $dist = Geo::distanciaAPolilineaM($lat, $lng, $trazo);

        if ($dist === null) {
            return false;
        }

        $umbral = config('logistica.desviacion_m', 400);
        $abierta = $ruta->desviaciones()->whereNull('fin_en')->first();

        if ($dist <= $umbral) {
            if ($abierta) {
                $abierta->update(['fin_en' => now()]);
                $ruta->registrarEvento('desviacion_fin', 'Volvió a la ruta planeada', null, $ruta->chofer_id, $lat, $lng);
            }
            Cache::forget("ruta-fuera-{$ruta->id}");

            return false;
        }

        if ($abierta) {
            $abierta->update(['max_distancia_m' => max($abierta->max_distancia_m, (int) $dist)]);

            return false;
        }

        // Lecturas seguidas fuera del margen
        $n = (int) Cache::get("ruta-fuera-{$ruta->id}", 0) + 1;
        Cache::put("ruta-fuera-{$ruta->id}", $n, now()->addMinutes(10));

        if ($n < config('logistica.desviacion_lecturas', 3)) {
            return false;
        }

        Cache::forget("ruta-fuera-{$ruta->id}");
        $ruta->desviaciones()->create(['lat' => $lat, 'lng' => $lng, 'max_distancia_m' => (int) $dist, 'inicio_en' => now()]);
        $ruta->registrarEvento('desviacion', 'Se desvió '.round($dist).' m de la ruta planeada', null, $ruta->chofer_id, $lat, $lng);

        return true;
    }

    /** La primera parada pendiente pasa a "en camino" si no hay ninguna activa. */
    private function marcarSiguienteEnCamino(Ruta $ruta): void
    {
        if (! $ruta->refresh()->activa()) {
            return;
        }

        $paradas = $ruta->paradas()->get();

        if ($paradas->contains(fn (RutaParada $p) => in_array($p->estado, [RutaParada::EN_CAMINO, RutaParada::ARRIBO], true))) {
            return;
        }

        $siguiente = $paradas->first(fn (RutaParada $p) => $p->estado === RutaParada::PENDIENTE);
        $siguiente?->update(['estado' => RutaParada::EN_CAMINO]);
    }

    private function guardarFirma(?string $dataUrl, string $disco): ?string
    {
        if (! $dataUrl || ! preg_match('/^data:image\/(png|jpe?g);base64,(.+)$/', $dataUrl, $m)) {
            return null;
        }

        $contenido = base64_decode($m[2], true);

        if (! $contenido) {
            return null;
        }

        $path = 'logistica/firmas/'.uniqid('firma_').'.png';
        Storage::disk($disco)->put($path, $contenido);

        return $path;
    }
}
