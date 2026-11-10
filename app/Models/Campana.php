<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

/**
 * Una campaña de promoción por WhatsApp: el mensaje, la oferta (productos,
 * cupón, vigencia), a quién va (filtros) y lo que pasó con cada envío.
 *
 * borrador → programada → enviando ⇄ pausada → completada
 *         ↘ (enviar ahora) ↗          cualquiera sin terminar → cancelada
 */
class Campana extends Model
{
    protected $table = 'campanas';

    public const BORRADOR = 'borrador';
    public const PROGRAMADA = 'programada';
    public const ENVIANDO = 'enviando';
    public const PAUSADA = 'pausada';
    public const COMPLETADA = 'completada';
    public const CANCELADA = 'cancelada';

    public const ESTADOS = [
        self::BORRADOR => 'Borrador',
        self::PROGRAMADA => 'Programada',
        self::ENVIANDO => 'Enviando',
        self::PAUSADA => 'Pausada',
        self::COMPLETADA => 'Completada',
        self::CANCELADA => 'Cancelada',
    ];

    protected $fillable = [
        'nombre', 'titulo_oferta', 'mensaje', 'plantilla', 'imagen_path', 'cupon_id', 'cupon_exclusivo',
        'filtros', 'creado_por', 'actualizado_por', 'enviado_por', 'cancelado_por',
        'programada_para', 'vigencia_inicio', 'vigencia_fin', 'estado',
        'iniciada_en', 'pausada_en', 'terminada_en', 'cancelada_en', 'prueba_enviada_en',
    ];

    protected function casts(): array
    {
        return [
            'filtros' => 'array',
            'cupon_exclusivo' => 'boolean',
            'programada_para' => 'datetime',
            'vigencia_inicio' => 'datetime',
            'vigencia_fin' => 'datetime',
            'iniciada_en' => 'datetime',
            'pausada_en' => 'datetime',
            'terminada_en' => 'datetime',
            'cancelada_en' => 'datetime',
            'prueba_enviada_en' => 'datetime',
        ];
    }

    public function creador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actualizado_por');
    }

    public function cupon(): BelongsTo
    {
        return $this->belongsTo(Cupon::class, 'cupon_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(CampanaItem::class)->orderBy('orden');
    }

    public function destinatarios(): HasMany
    {
        return $this->hasMany(CampanaDestinatario::class);
    }

    public function eventos(): HasMany
    {
        return $this->hasMany(MktEvento::class)->latest('id');
    }

    public function estadoLabel(): string
    {
        return self::ESTADOS[$this->estado] ?? ucfirst((string) $this->estado);
    }

    public function tonoEstado(): string
    {
        return [
            self::BORRADOR => 'neutral', self::PROGRAMADA => 'info', self::ENVIANDO => 'info',
            self::PAUSADA => 'warn', self::COMPLETADA => 'ok', self::CANCELADA => 'danger',
        ][$this->estado] ?? 'neutral';
    }

    public function editable(): bool
    {
        return in_array($this->estado, [self::BORRADOR, self::PROGRAMADA], true);
    }

    public function puedeEnviarse(): bool
    {
        return in_array($this->estado, [self::BORRADOR, self::PROGRAMADA], true);
    }

    public function puedePausarse(): bool
    {
        return in_array($this->estado, [self::ENVIANDO, self::PROGRAMADA], true);
    }

    public function puedeReanudarse(): bool
    {
        return $this->estado === self::PAUSADA;
    }

    public function puedeCancelarse(): bool
    {
        return in_array($this->estado, [self::BORRADOR, self::PROGRAMADA, self::ENVIANDO, self::PAUSADA], true);
    }

    /** ¿La oferta sigue abierta para que el cliente arme su pedido? */
    public function ofertaVigente(): bool
    {
        if ($this->estado === self::CANCELADA) {
            return false;
        }
        if ($this->vigencia_inicio && $this->vigencia_inicio->isFuture()) {
            return false;
        }

        return ! ($this->vigencia_fin && $this->vigencia_fin->isPast());
    }

    public function imagenUrl(): ?string
    {
        return $this->imagen_path ? Storage::disk('public')->url($this->imagen_path) : null;
    }

    public function plantillaWhatsapp(): string
    {
        return $this->plantilla ?: (string) config('promociones.whatsapp.plantilla');
    }
}
