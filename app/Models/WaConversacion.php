<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Una conversación de WhatsApp: un cliente en una bandeja (número de la empresa). */
class WaConversacion extends Model
{
    protected $table = 'wa_conversaciones';

    /** Horas que dura la ventana de atención después del último mensaje del cliente. */
    public const VENTANA_HORAS = 24;

    protected $fillable = [
        'phone_number_id', 'telefono', 'nombre_perfil', 'customer_id', 'no_leidos',
        'ultima_actividad_en', 'ultimo_entrante_en', 'ultimo_mensaje_id',
    ];

    protected function casts(): array
    {
        return [
            'no_leidos' => 'integer',
            'ultima_actividad_en' => 'datetime',
            'ultimo_entrante_en' => 'datetime',
        ];
    }

    public function mensajes(): HasMany
    {
        return $this->hasMany(WaMensaje::class, 'conversacion_id');
    }

    public function ultimoMensaje(): BelongsTo
    {
        return $this->belongsTo(WaMensaje::class, 'ultimo_mensaje_id');
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    /** Hasta cuándo se pueden mandar mensajes libres (null = cerrada). */
    public function ventanaHasta(): ?\Illuminate\Support\Carbon
    {
        $hasta = $this->ultimo_entrante_en?->copy()->addHours(self::VENTANA_HORAS);

        return $hasta && $hasta->isFuture() ? $hasta : null;
    }

    public function nombre(): string
    {
        $c = $this->cliente;
        $nombre = $c ? trim(($c->nombre ?? '').' '.($c->apellido ?? '')) : '';

        return $nombre !== '' ? $nombre : ($this->nombre_perfil ?: \App\Services\Mensajeria\Mensajeria::telefonoBonito($this->telefono));
    }
}
