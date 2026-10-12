<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Una campaña de promoción: el mensaje, a quién se le manda (por
 * filtro), y el registro de qué pasó con cada destinatario.
 */
class Campana extends Model
{
    protected $table = 'campanas';

    public const ESTADO_BORRADOR = 'borrador';
    public const ESTADO_EN_COLA = 'en_cola';
    public const ESTADO_ENVIANDO = 'enviando';
    public const ESTADO_COMPLETADA = 'completada';
    public const ESTADO_CANCELADA = 'cancelada';

    public const ESTADOS = [
        self::ESTADO_BORRADOR => 'Borrador',
        self::ESTADO_EN_COLA => 'En cola',
        self::ESTADO_ENVIANDO => 'Enviando',
        self::ESTADO_COMPLETADA => 'Completada',
        self::ESTADO_CANCELADA => 'Cancelada',
    ];

    protected $fillable = [
        'nombre',
        'mensaje',
        'filtros',
        'creado_por',
        'programada_para',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'filtros' => 'array',
            'programada_para' => 'datetime',
        ];
    }

    public function creador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    public function destinatarios(): HasMany
    {
        return $this->hasMany(CampanaDestinatario::class);
    }

    public function clientes(): BelongsToMany
    {
        return $this->belongsToMany(Customer::class, 'campana_destinatarios', 'campana_id', 'cliente_id')
            ->withPivot(['estado', 'whatsapp_message_id', 'enviado_en', 'entregado_en', 'leido_en', 'error'])
            ->withTimestamps();
    }

    public function estadoLabel(): string
    {
        return self::ESTADOS[$this->estado] ?? $this->estado;
    }

    public function puedeLanzarse(): bool
    {
        return $this->estado === self::ESTADO_BORRADOR;
    }

    /** Resumen rápido de en qué van los envíos, para el listado y el detalle. */
    public function resumenEnvios(): array
    {
        $conteos = $this->destinatarios()
            ->selectRaw('estado, count(*) as total')
            ->groupBy('estado')
            ->pluck('total', 'estado');

        return [
            'total' => $conteos->sum(),
            'pendiente' => $conteos->get(CampanaDestinatario::ESTADO_PENDIENTE, 0),
            'enviado' => $conteos->get(CampanaDestinatario::ESTADO_ENVIADO, 0),
            'entregado' => $conteos->get(CampanaDestinatario::ESTADO_ENTREGADO, 0),
            'leido' => $conteos->get(CampanaDestinatario::ESTADO_LEIDO, 0),
            'fallo' => $conteos->get(CampanaDestinatario::ESTADO_FALLO, 0),
            'excluido' => $conteos->get(CampanaDestinatario::ESTADO_EXCLUIDO, 0),
        ];
    }
}
