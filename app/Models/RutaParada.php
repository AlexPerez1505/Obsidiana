<?php

namespace App\Models;

use App\Support\Geo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class RutaParada extends Model
{
    protected $table = 'ruta_paradas';

    public const PENDIENTE = 'pendiente';
    public const EN_CAMINO = 'en_camino';
    public const ARRIBO = 'arribo';
    public const COMPLETADA = 'completada';
    public const NO_ENTREGADA = 'no_entregada';
    public const CANCELADA = 'cancelada';

    public const ESTADOS = [
        self::PENDIENTE => 'Pendiente',
        self::EN_CAMINO => 'En camino',
        self::ARRIBO => 'En el sitio',
        self::COMPLETADA => 'Completada',
        self::NO_ENTREGADA => 'No entregada',
        self::CANCELADA => 'Cancelada',
    ];

    protected $fillable = [
        'ruta_id', 'orden', 'nombre', 'direccion', 'lat', 'lng', 'radio_m',
        'customer_id', 'orden_salida_id', 'contacto_nombre', 'contacto_telefono',
        'ventana_desde', 'ventana_hasta', 'urgente', 'instrucciones',
        'estado', 'distancia_desde_anterior_m', 'duracion_desde_anterior_s', 'eta',
        'llegada_en', 'llegada_origen', 'salida_en', 'completada_en',
        'receptor_nombre', 'firma_path', 'motivo_no_entrega', 'cierre_lat', 'cierre_lng',
    ];

    protected function casts(): array
    {
        return [
            'lat' => 'float',
            'lng' => 'float',
            'cierre_lat' => 'float',
            'cierre_lng' => 'float',
            'urgente' => 'boolean',
            'eta' => 'datetime',
            'llegada_en' => 'datetime',
            'salida_en' => 'datetime',
            'completada_en' => 'datetime',
        ];
    }

    public function ruta(): BelongsTo
    {
        return $this->belongsTo(Ruta::class);
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function ordenSalida(): BelongsTo
    {
        return $this->belongsTo(OrdenSalida::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(RutaParadaItem::class)->orderBy('orden');
    }

    public function evidencias(): HasMany
    {
        return $this->hasMany(RutaEvidencia::class);
    }

    public function estadoLabel(): string
    {
        return self::ESTADOS[$this->estado] ?? ucfirst($this->estado);
    }

    public function estadoTono(): string
    {
        return match ($this->estado) {
            self::EN_CAMINO => 'badge--warn',
            self::ARRIBO => 'badge--info',
            self::COMPLETADA => 'badge--ok',
            self::NO_ENTREGADA, self::CANCELADA => 'badge--danger',
            default => '',
        };
    }

    public function resuelta(): bool
    {
        return in_array($this->estado, [self::COMPLETADA, self::NO_ENTREGADA, self::CANCELADA], true);
    }

    public function tieneCoordenadas(): bool
    {
        return $this->lat !== null && $this->lng !== null;
    }

    /** Metros desde un punto hasta esta parada (null si no tiene coordenadas). */
    public function distanciaA(float $lat, float $lng): ?float
    {
        return $this->tieneCoordenadas() ? Geo::distanciaM($lat, $lng, $this->lat, $this->lng) : null;
    }

    public function firmaUrl(): ?string
    {
        return $this->firma_path
            ? Storage::disk(config('filesystems.fotos_disk', 'public'))->url($this->firma_path)
            : null;
    }

    /** "Monitor Philips x2, Ventilador x1" */
    public function resumenMercancia(): string
    {
        return $this->items->map(fn (RutaParadaItem $i) => "{$i->descripcion} x{$i->cantidad}")->implode(', ');
    }
}
