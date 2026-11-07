<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Un envío de una campaña: a un cliente (o a un número de prueba). Es el
 * registro real: qué se mandó, qué contestó WhatsApp, si abrió el enlace.
 *
 * pendiente → enviando → enviado → entregado → leido
 *                      ↘ fallo        (o simulado / omitido / cancelado)
 */
class CampanaDestinatario extends Model
{
    protected $table = 'campana_destinatarios';

    public const PENDIENTE = 'pendiente';
    public const ENVIANDO = 'enviando';
    public const ENVIADO = 'enviado';
    public const ENTREGADO = 'entregado';
    public const LEIDO = 'leido';
    public const FALLO = 'fallo';
    public const SIMULADO = 'simulado';
    public const OMITIDO_CONSENTIMIENTO = 'omitido_consentimiento';
    public const OMITIDO_FRECUENCIA = 'omitido_frecuencia';
    public const CANCELADO = 'cancelado';

    /** Orden en que avanza un mensaje, para no retroceder si los avisos llegan desordenados. */
    public const RANGO = [self::ENVIADO => 1, self::ENTREGADO => 2, self::LEIDO => 3];

    protected $fillable = [
        'campana_id', 'cliente_id', 'clave', 'es_prueba', 'token', 'telefono', 'cupon_id',
        'estado', 'simulado', 'intentos', 'whatsapp_message_id',
        'enviado_en', 'entregado_en', 'leido_en', 'fallo_en', 'error', 'error_codigo',
        'clics', 'primer_clic_en', 'ultimo_clic_en',
    ];

    protected function casts(): array
    {
        return [
            'es_prueba' => 'boolean',
            'simulado' => 'boolean',
            'enviado_en' => 'datetime',
            'entregado_en' => 'datetime',
            'leido_en' => 'datetime',
            'fallo_en' => 'datetime',
            'primer_clic_en' => 'datetime',
            'ultimo_clic_en' => 'datetime',
        ];
    }

    public static function nuevoToken(): string
    {
        return Str::random(32);
    }

    public function campana(): BelongsTo
    {
        return $this->belongsTo(Campana::class);
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'cliente_id');
    }

    public function cupon(): BelongsTo
    {
        return $this->belongsTo(Cupon::class, 'cupon_id');
    }

    /** El cupón que le toca: el suyo (exclusivo) o el general de la campaña. */
    public function cuponAplicable(): ?Cupon
    {
        return $this->cupon ?? ($this->campana?->cupon_exclusivo ? null : $this->campana?->cupon);
    }

    public function enlace(): string
    {
        return route('promo.oferta', $this->token);
    }

    /** Teléfono con solo los últimos 4 dígitos visibles (pantallas y logs). */
    public function telefonoOculto(): string
    {
        $t = (string) $this->telefono;

        return $t === '' ? '—' : str_repeat('•', max(0, strlen($t) - 4)).substr($t, -4);
    }

    public function estadoLabel(): string
    {
        return [
            self::PENDIENTE => 'Pendiente',
            self::ENVIANDO => 'Enviando',
            self::ENVIADO => 'Enviado',
            self::ENTREGADO => 'Entregado',
            self::LEIDO => 'Leído',
            self::FALLO => 'Falló',
            self::SIMULADO => 'Simulado',
            self::OMITIDO_CONSENTIMIENTO => 'Omitido: sin consentimiento',
            self::OMITIDO_FRECUENCIA => 'Omitido: recibió otra hace poco',
            self::CANCELADO => 'Cancelado',
        ][$this->estado] ?? $this->estado;
    }

    public function tono(): string
    {
        return match ($this->estado) {
            self::ENTREGADO, self::LEIDO => 'ok',
            self::ENVIADO, self::ENVIANDO => 'info',
            self::FALLO => 'danger',
            self::OMITIDO_CONSENTIMIENTO, self::OMITIDO_FRECUENCIA, self::SIMULADO => 'warn',
            default => 'neutral',
        };
    }
}
