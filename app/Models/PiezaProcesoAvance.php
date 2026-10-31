<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * Lo que se hizo un día en un proceso: la nota y sus fotos.
 *
 * Un proceso se va trabajando por partes; cada "Guardar avance" deja una
 * fila aquí, sin límite. Las fotos de todos los avances cuentan como
 * evidencia al cerrar el paso.
 */
class PiezaProcesoAvance extends Model
{
    protected $table = 'pieza_proceso_avances';

    protected $fillable = [
        'pieza_proceso_id',
        'user_id',
        'nota',
        'evidencias',
    ];

    protected function casts(): array
    {
        return [
            'evidencias' => 'array',
        ];
    }

    public function paso(): BelongsTo
    {
        return $this->belongsTo(PiezaProceso::class, 'pieza_proceso_id');
    }

    public function autor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** @return array<int, array{path: string, url: string}> */
    public function fotos(): array
    {
        $disco = config('filesystems.fotos_disk', 'public');

        return collect($this->evidencias ?? [])
            ->map(fn (string $p) => ['path' => $p, 'url' => Storage::disk($disco)->url($p)])
            ->all();
    }
}
