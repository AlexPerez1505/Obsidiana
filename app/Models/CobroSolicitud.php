<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * Un pago que subió un vendedor (comprobante de transferencia/depósito o
 * aviso de efectivo) esperando a que alguien con NIP o un administrador
 * confirme que el dinero se vio reflejado. Pendiente no cuenta en el saldo.
 */
class CobroSolicitud extends Model
{
    protected $table = 'cobro_solicitudes';

    public const PENDIENTE = 'pendiente';
    public const APROBADO = 'aprobado';
    public const RECHAZADO = 'rechazado';

    protected $fillable = [
        'venta_id', 'venta_pago_id', 'fecha', 'monto', 'metodo', 'referencia', 'nota', 'archivos',
        'estado', 'subido_por', 'revisado_por', 'revisado_en', 'motivo_rechazo', 'cobro_id',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'monto' => 'decimal:2',
            'archivos' => 'array',
            'revisado_en' => 'datetime',
        ];
    }

    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class);
    }

    public function parcialidad(): BelongsTo
    {
        return $this->belongsTo(VentaPago::class, 'venta_pago_id');
    }

    public function subidoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'subido_por');
    }

    public function revisadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revisado_por');
    }

    public function cobro(): BelongsTo
    {
        return $this->belongsTo(Cobro::class);
    }

    public function metodoLabel(): string
    {
        return Cobro::METODOS[$this->metodo] ?? ucfirst((string) $this->metodo);
    }

    public function estadoLabel(): string
    {
        return [self::PENDIENTE => 'Por aprobar', self::APROBADO => 'Aprobado', self::RECHAZADO => 'Rechazado'][$this->estado] ?? $this->estado;
    }

    public function tono(): string
    {
        return [self::PENDIENTE => 'warn', self::APROBADO => 'ok', self::RECHAZADO => 'danger'][$this->estado] ?? 'neutral';
    }

    /** @return array<int, array{url: string, nombre: string, imagen: bool}> */
    public function archivosUrls(): array
    {
        return collect($this->archivos ?? [])->map(fn ($a) => [
            'url' => Storage::disk('public')->url($a['archivo']),
            'nombre' => $a['nombre'] ?? basename($a['archivo']),
            'imagen' => in_array(strtolower(pathinfo($a['archivo'], PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'webp', 'gif'], true),
        ])->all();
    }
}
