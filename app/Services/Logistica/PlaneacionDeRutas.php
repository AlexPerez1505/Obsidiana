<?php

namespace App\Services\Logistica;

use App\Models\OrdenSalida;
use App\Models\Ruta;
use App\Models\RutaParada;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Alta y edición de rutas desde la oficina: qué datos se aceptan, cómo se
 * guardan las paradas con su mercancía y cuándo se puede recalcular el
 * orden. La web y la API pasan por aquí para que una ruta quede igual sin
 * importar por dónde se capturó.
 */
class PlaneacionDeRutas
{
    public function __construct(private OptimizadorDeRutas $optimizador) {}

    /** Reglas de validación de una ruta con todas sus paradas. */
    public function reglas(): array
    {
        return [
            'fecha_programada' => ['nullable', 'date'],
            'hora_salida' => ['nullable', 'date_format:H:i'],
            'vehicle_id' => ['nullable', 'exists:vehicles,id'],
            'chofer_id' => ['nullable', 'exists:users,id'],
            'origen_nombre' => ['nullable', 'string', 'max:150'],
            'origen_direccion' => ['nullable', 'string', 'max:255'],
            'origen_lat' => ['nullable', 'numeric', 'between:-90,90'],
            'origen_lng' => ['nullable', 'numeric', 'between:-180,180'],
            'regresa_a_origen' => ['nullable', 'boolean'],
            'exigir_evidencia' => ['nullable', 'boolean'],
            'notas' => ['nullable', 'string', 'max:2000'],

            'paradas' => ['required', 'array', 'min:1', 'max:60'],
            'paradas.*.nombre' => ['required', 'string', 'max:150'],
            'paradas.*.direccion' => ['nullable', 'string', 'max:255'],
            'paradas.*.lat' => ['nullable', 'numeric', 'between:-90,90'],
            'paradas.*.lng' => ['nullable', 'numeric', 'between:-180,180'],
            'paradas.*.radio_m' => ['nullable', 'integer', 'between:30,2000'],
            'paradas.*.contacto_nombre' => ['nullable', 'string', 'max:120'],
            'paradas.*.contacto_telefono' => ['nullable', 'string', 'max:30'],
            'paradas.*.ventana_desde' => ['nullable', 'date_format:H:i'],
            'paradas.*.ventana_hasta' => ['nullable', 'date_format:H:i'],
            'paradas.*.urgente' => ['nullable', 'boolean'],
            'paradas.*.instrucciones' => ['nullable', 'string', 'max:1000'],
            'paradas.*.mercancia' => ['nullable', 'string', 'max:4000'],
            'paradas.*.orden_salida_id' => ['nullable', 'exists:ordenes_salida,id'],
        ];
    }

    public function mensajes(): array
    {
        return [
            'paradas.required' => 'Agrega al menos una parada.',
            'paradas.*.nombre.required' => 'Cada parada necesita un nombre.',
        ];
    }

    /**
     * Un vehículo y un chofer no pueden estar en dos rutas en curso a la vez.
     * Cada quien responde el choque a su manera (la web regresa al
     * formulario, la API da 422), por eso aquí solo se detecta.
     *
     * @return array{0: string, 1: string}|null  [campo, mensaje]
     */
    public function choque(array $data, ?Ruta $ruta = null): ?array
    {
        foreach (['vehicle_id' => 'vehículo', 'chofer_id' => 'chofer'] as $campo => $texto) {
            if (! empty($data[$campo]) && Ruta::where('estado', Ruta::EN_RUTA)
                ->where($campo, $data[$campo])
                ->when($ruta, fn ($q, $r) => $q->where('id', '!=', $r->id))
                ->exists()) {
                return [$campo, "Ese {$texto} ya va en una ruta en curso."];
            }
        }

        return null;
    }

    public function crear(array $data, int $userId): Ruta
    {
        return DB::transaction(function () use ($data, $userId) {
            $ruta = Ruta::create($this->camposRuta($data) + [
                'folio' => Ruta::siguienteFolio(),
                'estado' => $this->estadoSegunAsignacion($data),
                'created_by' => $userId,
            ]);

            $this->guardarParadas($ruta, $data['paradas']);
            $ruta->registrarEvento('creada', 'Ruta creada', null, $userId);

            return $ruta;
        });
    }

    /** Reemplaza datos y paradas; el cálculo anterior se descarta porque cambió el recorrido. */
    public function actualizar(Ruta $ruta, array $data, int $userId): void
    {
        if (! $ruta->editable()) {
            throw new \DomainException('Una ruta en curso o cerrada ya no se edita.');
        }

        DB::transaction(function () use ($ruta, $data, $userId) {
            $ruta->update($this->camposRuta($data) + [
                'estado' => $this->estadoSegunAsignacion($data),
                'distancia_m' => null, 'duracion_s' => null, 'trazo_planeado' => null,
                'optimizada_con' => null, 'optimizada_en' => null,
            ]);

            $ruta->paradas()->delete();
            $this->guardarParadas($ruta, $data['paradas']);
            $ruta->registrarEvento('editada', 'Ruta editada', null, $userId);
        });
    }

    /**
     * Calcula el mejor orden de las paradas. Si la ruta va en curso solo
     * reordena lo pendiente desde donde va el vehículo. Devuelve true
     * cuando fue una reoptimización.
     */
    public function optimizar(Ruta $ruta): bool
    {
        if ($ruta->cerrada()) {
            throw new \DomainException('La ruta ya está cerrada.');
        }

        $desde = null;

        if ($ruta->activa()) {
            $u = $ruta->ultimaUbicacion;
            if (! $u) {
                throw new \DomainException('Todavía no hay posición del vehículo para reoptimizar.');
            }
            $desde = ['lat' => $u->lat, 'lng' => $u->lng];
        }

        $this->optimizador->optimizar($ruta, true, $desde);

        $ruta->refresh();
        $ruta->registrarEvento($desde ? 'reoptimizada' : 'optimizada', 'Orden calculado con '.($ruta->optimizada_con === 'google' ? 'Google' : 'cálculo local'));

        return $desde !== null;
    }

    /**
     * Órdenes de salida listas para firma que todavía no van en una ruta
     * abierta: con ellas se arma una ruta "desde órdenes listas".
     *
     * @return Collection<int, OrdenSalida>
     */
    public function ordenesListas(int $limite = 30): Collection
    {
        $enRuta = RutaParada::whereNotNull('orden_salida_id')
            ->whereHas('ruta', fn ($q) => $q->whereNotIn('estado', [Ruta::FINALIZADA, Ruta::CANCELADA]))
            ->pluck('orden_salida_id');

        return OrdenSalida::with(['venta.customer', 'items'])
            ->where('estado', OrdenSalida::LISTA)->whereNotIn('id', $enRuta)
            ->oldest()->limit($limite)->get();
    }

    /** Una orden de salida convertida en parada, con los campos del formulario. */
    public function paradaDesdeOrden(OrdenSalida $o): array
    {
        return [
            'nombre' => $this->nombreCliente($o) ?: $o->folio,
            'direccion' => $o->venta?->customer?->direccion,
            'contacto_telefono' => $o->venta?->customer?->telefono,
            'orden_salida_id' => $o->id,
            'radio_m' => 100,
            'mercancia' => $this->mercanciaDe($o),
        ];
    }

    /** Órdenes que se pueden ligar a una parada (las abiertas más recientes). */
    public function ordenesDisponibles(int $limite = 100): array
    {
        return OrdenSalida::with(['venta.customer', 'items'])
            ->whereIn('estado', [OrdenSalida::LISTA, OrdenSalida::PENDIENTE, OrdenSalida::EN_PREPARACION])
            ->latest()->limit($limite)->get()
            ->map(fn (OrdenSalida $o) => [
                'id' => $o->id,
                'folio' => $o->folio,
                'cliente' => $this->nombreCliente($o),
                'direccion' => $o->venta?->customer?->direccion,
                'telefono' => $o->venta?->customer?->telefono,
                'mercancia' => $this->mercanciaDe($o),
            ])->values()->all();
    }

    // ------------------------------------------------------------------

    /** Con vehículo y chofer ya puede salir; si falta alguno se queda en borrador. */
    private function estadoSegunAsignacion(array $d): string
    {
        return ! empty($d['vehicle_id']) && ! empty($d['chofer_id']) ? Ruta::PROGRAMADA : Ruta::BORRADOR;
    }

    private function camposRuta(array $d): array
    {
        return [
            'fecha_programada' => $d['fecha_programada'] ?? null,
            'hora_salida' => $d['hora_salida'] ?? null,
            'vehicle_id' => $d['vehicle_id'] ?? null,
            'chofer_id' => $d['chofer_id'] ?? null,
            'origen_nombre' => $d['origen_nombre'] ?? null,
            'origen_direccion' => $d['origen_direccion'] ?? null,
            'origen_lat' => $d['origen_lat'] ?? null,
            'origen_lng' => $d['origen_lng'] ?? null,
            'regresa_a_origen' => (bool) ($d['regresa_a_origen'] ?? false),
            'exigir_evidencia' => (bool) ($d['exigir_evidencia'] ?? false),
            'notas' => $d['notas'] ?? null,
        ];
    }

    private function guardarParadas(Ruta $ruta, array $paradas): void
    {
        foreach (array_values($paradas) as $i => $p) {
            $orden = ! empty($p['orden_salida_id'])
                ? OrdenSalida::with(['venta', 'items'])->find($p['orden_salida_id'])
                : null;

            $parada = $ruta->paradas()->create([
                'orden' => $i + 1,
                'nombre' => $p['nombre'],
                'direccion' => $p['direccion'] ?? null,
                'lat' => $p['lat'] ?? null,
                'lng' => $p['lng'] ?? null,
                'radio_m' => $p['radio_m'] ?? 100,
                'customer_id' => $orden?->venta?->customer_id,
                'orden_salida_id' => $orden?->id,
                'contacto_nombre' => $p['contacto_nombre'] ?? null,
                'contacto_telefono' => $p['contacto_telefono'] ?? null,
                'ventana_desde' => $p['ventana_desde'] ?? null,
                'ventana_hasta' => $p['ventana_hasta'] ?? null,
                'urgente' => (bool) ($p['urgente'] ?? false),
                'instrucciones' => $p['instrucciones'] ?? null,
            ]);

            $items = $this->interpretarMercancia((string) ($p['mercancia'] ?? ''));

            // Si no escribieron mercancía pero ligaron una orden de salida, se trae de ahí
            if (! $items && $orden) {
                $items = $orden->items->map(fn ($it) => ['descripcion' => $it->nombre, 'cantidad' => max(1, (int) $it->cantidad)])->all();
            }

            foreach ($items as $n => $item) {
                $parada->items()->create($item + ['orden' => $n + 1]);
            }
        }
    }

    /** "2 Monitor Philips" / "Ventilador x3" / "Consumibles" → renglones. */
    private function interpretarMercancia(string $texto): array
    {
        $items = [];

        foreach (preg_split('/\R/u', $texto) as $linea) {
            $linea = trim($linea);
            if ($linea === '') {
                continue;
            }

            if (preg_match('/^(\d{1,5})\s*(?:x|×|pz|pza|pzas)?\s+(.+)$/iu', $linea, $m)) {
                $items[] = ['cantidad' => (int) $m[1], 'descripcion' => trim($m[2])];
            } elseif (preg_match('/^(.+?)\s*(?:x|×)\s*(\d{1,5})$/iu', $linea, $m)) {
                $items[] = ['cantidad' => (int) $m[2], 'descripcion' => trim($m[1])];
            } else {
                $items[] = ['cantidad' => 1, 'descripcion' => $linea];
            }
        }

        return $items;
    }

    private function nombreCliente(OrdenSalida $o): string
    {
        return trim(($o->venta?->customer?->nombre ?? '').' '.($o->venta?->customer?->apellido ?? ''));
    }

    private function mercanciaDe(OrdenSalida $o): string
    {
        return $o->items->map(fn ($i) => max(1, (int) $i->cantidad).' '.$i->nombre)->implode("\n");
    }
}
