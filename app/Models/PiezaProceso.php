<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Un paso de la ruta de una pieza.
 *
 * La ruta no es una secuencia fija: una pieza puede llevar solo
 * mantenimiento, solo hojalatería, los dos, o ninguno. Cada paso es una
 * fila aquí, y la pieza está disponible cuando no le queda ninguno
 * pendiente.
 */
class PiezaProceso extends Model
{
    protected $table = 'pieza_procesos';

    /**
     * Los procesos por los que puede pasar una pieza.
     *
     * El orden de este arreglo es el orden natural en que se hacen: primero
     * se endereza y se pinta, luego se repara lo eléctrico, y al final se
     * limpia. Se usa como orden por omisión al armar la ruta.
     */
    public const PROCESOS = [
        'hojalateria' => 'Hojalatería',
        'mantenimiento' => 'Mantenimiento',
        'limpieza' => 'Limpieza',
    ];

    /** Cómo se pinta cada proceso en las pantallas. */
    public const COLORES = [
        'hojalateria' => 'ambar',
        'mantenimiento' => 'azul',
        'limpieza' => 'verde',
    ];

    protected $fillable = [
        'producto_serial_id',
        'proceso',
        'orden',
        'estado',
        'iniciado_en',
        'terminado_en',
        'responsable_id',
        'cerrado_por',
        'notas',
        'motivo',
        'checklist_salida',
        'evidencias',
        'trabajo_realizado',
    ];

    protected function casts(): array
    {
        return [
            'orden' => 'integer',
            'iniciado_en' => 'datetime',
            'terminado_en' => 'datetime',
            'checklist_salida' => 'array',
            'evidencias' => 'array',
        ];
    }

    public function pieza(): BelongsTo
    {
        return $this->belongsTo(ProductoSerial::class, 'producto_serial_id');
    }

    public function responsable(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }

    public function cerradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cerrado_por');
    }

    /** URLs de las fotos de que quedó funcionando. */
    public function evidenciaUrls(): array
    {
        return collect($this->evidencias ?? [])
            ->map(fn (string $p) => \Illuminate\Support\Facades\Storage::disk('public')->url($p))
            ->all();
    }

    /** Cuánto lleva parada aquí, para saber qué se está atorando. */
    public function diasDetenida(): int
    {
        return (int) ($this->iniciado_en ?? $this->created_at)?->diffInDays(now());
    }

    public function nombre(): string
    {
        return self::PROCESOS[$this->proceso] ?? $this->proceso;
    }

    /** Un paso cuenta como resuelto tanto si se hizo como si se omitió. */
    public function resuelto(): bool
    {
        return in_array($this->estado, ['terminado', 'omitido'], true);
    }

    /** Orden natural de un proceso dentro de la ruta. */
    public static function ordenDe(string $proceso): int
    {
        $orden = array_search($proceso, array_keys(self::PROCESOS), true);

        return $orden === false ? 99 : $orden;
    }
}
