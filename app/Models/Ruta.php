<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Una ruta de reparto: un vehículo y un chofer recorren varias paradas
 * en orden, y el sistema registra por dónde pasaron y qué entregaron.
 */
class Ruta extends Model
{
    protected $table = 'rutas';

    public const BORRADOR = 'borrador';
    public const PROGRAMADA = 'programada';
    public const EN_RUTA = 'en_ruta';
    public const FINALIZADA = 'finalizada';
    public const CANCELADA = 'cancelada';

    public const ESTADOS = [
        self::BORRADOR => 'Borrador',
        self::PROGRAMADA => 'Programada',
        self::EN_RUTA => 'En ruta',
        self::FINALIZADA => 'Finalizada',
        self::CANCELADA => 'Cancelada',
    ];

    protected $fillable = [
        'folio', 'estado', 'fecha_programada', 'hora_salida',
        'vehicle_id', 'chofer_id',
        'origen_nombre', 'origen_direccion', 'origen_lat', 'origen_lng', 'regresa_a_origen',
        'distancia_m', 'duracion_s', 'optimizada_con', 'optimizada_en', 'trazo_planeado',
        'exigir_evidencia', 'notas', 'km_recorridos', 'iniciada_en', 'finalizada_en', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'fecha_programada' => 'date',
            'regresa_a_origen' => 'boolean',
            'exigir_evidencia' => 'boolean',
            'optimizada_en' => 'datetime',
            'iniciada_en' => 'datetime',
            'finalizada_en' => 'datetime',
            'origen_lat' => 'float',
            'origen_lng' => 'float',
        ];
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function chofer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'chofer_id');
    }

    public function creador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function paradas(): HasMany
    {
        return $this->hasMany(RutaParada::class)->orderBy('orden');
    }

    public function eventos(): HasMany
    {
        return $this->hasMany(RutaEvento::class)->orderBy('ocurrio_en');
    }

    public function ubicaciones(): HasMany
    {
        return $this->hasMany(RutaUbicacion::class)->orderBy('registrada_en');
    }

    public function desviaciones(): HasMany
    {
        return $this->hasMany(RutaDesviacion::class)->orderBy('inicio_en');
    }

    public function incidencias(): HasMany
    {
        return $this->hasMany(RutaIncidencia::class)->latest();
    }

    /** Kilómetros realmente recorridos, sumando los puntos GPS en orden. */
    public function calcularKm(): float
    {
        $total = 0.0;
        $prev = null;

        foreach ($this->ubicaciones()->select('lat', 'lng', 'precision_m')->cursor() as $u) {
            // Lecturas muy imprecisas meten ruido: se ignoran
            if ($u->precision_m !== null && $u->precision_m > 100) {
                continue;
            }
            if ($prev) {
                $d = \App\Support\Geo::distanciaM($prev->lat, $prev->lng, $u->lat, $u->lng);
                // Menos de 15 m es ruido del GPS parado; más de 5 km entre lecturas es un salto
                if ($d >= 15 && $d <= 5000) {
                    $total += $d;
                }
            }
            $prev = $u;
        }

        return round($total / 1000, 1);
    }

    public function ultimaUbicacion(): HasOne
    {
        return $this->hasOne(RutaUbicacion::class)->latestOfMany('registrada_en');
    }

    public static function siguienteFolio(): string
    {
        $anio = now()->year;
        $ultimo = static::where('folio', 'like', "RUTA-{$anio}-%")->count();

        do {
            $folio = sprintf('RUTA-%d-%04d', $anio, ++$ultimo);
        } while (static::where('folio', $folio)->exists());

        return $folio;
    }

    public function estadoLabel(): string
    {
        return self::ESTADOS[$this->estado] ?? ucfirst($this->estado);
    }

    public function estadoTono(): string
    {
        return match ($this->estado) {
            self::PROGRAMADA => 'badge--info',
            self::EN_RUTA => 'badge--warn',
            self::FINALIZADA => 'badge--ok',
            self::CANCELADA => 'badge--danger',
            default => '',
        };
    }

    public function editable(): bool
    {
        return in_array($this->estado, [self::BORRADOR, self::PROGRAMADA], true);
    }

    public function activa(): bool
    {
        return $this->estado === self::EN_RUTA;
    }

    public function cerrada(): bool
    {
        return in_array($this->estado, [self::FINALIZADA, self::CANCELADA], true);
    }

    public function tieneOrigen(): bool
    {
        return $this->origen_lat !== null && $this->origen_lng !== null;
    }

    /** Paradas ya resueltas (entregadas, no entregadas o canceladas). */
    public function avance(): array
    {
        $paradas = $this->relationLoaded('paradas') ? $this->paradas : $this->paradas()->get();
        $total = $paradas->count();
        $hechas = $paradas->filter(fn (RutaParada $p) => $p->resuelta())->count();

        return [
            'total' => $total,
            'hechas' => $hechas,
            'pct' => $total ? (int) round($hechas * 100 / $total) : 0,
        ];
    }

    public function distanciaKm(): ?float
    {
        return $this->distancia_m !== null ? round($this->distancia_m / 1000, 1) : null;
    }

    /** "2 h 36 min" */
    public function duracionTexto(): ?string
    {
        if ($this->duracion_s === null) {
            return null;
        }

        $min = (int) round($this->duracion_s / 60);

        return intdiv($min, 60) > 0
            ? intdiv($min, 60).' h '.str_pad((string) ($min % 60), 2, '0', STR_PAD_LEFT).' min'
            : $min.' min';
    }

    public function trazo(): array
    {
        return $this->trazo_planeado ? (json_decode($this->trazo_planeado, true) ?: []) : [];
    }

    public function registrarEvento(string $tipo, ?string $detalle = null, ?RutaParada $parada = null, ?int $userId = null, ?float $lat = null, ?float $lng = null): RutaEvento
    {
        return $this->eventos()->create([
            'ruta_parada_id' => $parada?->id,
            'user_id' => $userId ?? auth()->id(),
            'tipo' => $tipo,
            'detalle' => $detalle,
            'lat' => $lat,
            'lng' => $lng,
            'ocurrio_en' => now(),
        ]);
    }
}
