<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

/**
 * Un componente de una pieza: el procesador o la fuente de un sistema, o el
 * pigtail o la interfaz que trae cada uno. Puede colgar de otro componente
 * (padre_id) y puede estar "por llegar" hasta que se recibe.
 */
class PiezaComponente extends Model
{
    protected $table = 'pieza_componentes';

    public const ESTADOS = [
        'recibido' => 'Recibido',
        'por_llegar' => 'Por llegar',
    ];

    protected $fillable = [
        'producto_serial_id',
        'padre_id',
        'nombre',
        'no_serie',
        'estado',
        'recibido_en',
        'fotos',
        'notas',
        'capturado_por',
        'recibido_por',
    ];

    protected function casts(): array
    {
        return [
            'fotos' => 'array',
            'recibido_en' => 'datetime',
        ];
    }

    public function pieza(): BelongsTo
    {
        return $this->belongsTo(ProductoSerial::class, 'producto_serial_id');
    }

    public function padre(): BelongsTo
    {
        return $this->belongsTo(self::class, 'padre_id');
    }

    public function hijos(): HasMany
    {
        return $this->hasMany(self::class, 'padre_id');
    }

    public function capturadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'capturado_por');
    }

    public function recibidoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recibido_por');
    }

    public function porLlegar(): bool
    {
        return $this->estado === 'por_llegar';
    }

    /** @return array<int, string> */
    public function fotoUrls(): array
    {
        $disco = Storage::disk(config('filesystems.fotos_disk', 'public'));

        return collect($this->fotos ?: [])->filter()->map(fn (string $p) => $disco->url($p))->values()->all();
    }

    /**
     * Cuántos componentes tiene cada pieza y cuántos faltan por llegar, en
     * una sola consulta para toda la lista.
     *
     * @param  iterable<int>  $piezaIds
     * @return array<int, array{total: int, por_llegar: int}>
     */
    public static function resumenPara(iterable $piezaIds): array
    {
        $ids = collect($piezaIds)->filter()->unique()->values();
        if ($ids->isEmpty()) {
            return [];
        }

        return static::query()
            ->whereIn('producto_serial_id', $ids)
            ->selectRaw("producto_serial_id, COUNT(*) as total, SUM(CASE WHEN estado = 'por_llegar' THEN 1 ELSE 0 END) as por_llegar")
            ->groupBy('producto_serial_id')
            ->get()
            ->mapWithKeys(fn ($f) => [(int) $f->producto_serial_id => ['total' => (int) $f->total, 'por_llegar' => (int) $f->por_llegar]])
            ->all();
    }
}
