<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class RutaIncidencia extends Model
{
    protected $table = 'ruta_incidencias';

    public const TIPOS = [
        'retraso' => 'Retraso',
        'trafico' => 'Tráfico / bloqueo',
        'falla_mecanica' => 'Falla mecánica',
        'accidente' => 'Accidente',
        'cliente_ausente' => 'Cliente ausente',
        'mercancia_danada' => 'Mercancía dañada',
        'otro' => 'Otro',
    ];

    protected $fillable = [
        'ruta_id', 'ruta_parada_id', 'user_id', 'tipo', 'descripcion', 'foto', 'lat', 'lng',
        'resuelta_en', 'resuelta_por', 'resolucion',
    ];

    protected function casts(): array
    {
        return ['resuelta_en' => 'datetime'];
    }

    public function ruta(): BelongsTo
    {
        return $this->belongsTo(Ruta::class);
    }

    public function parada(): BelongsTo
    {
        return $this->belongsTo(RutaParada::class, 'ruta_parada_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function tipoLabel(): string
    {
        return self::TIPOS[$this->tipo] ?? ucfirst($this->tipo);
    }

    public function fotoUrl(): ?string
    {
        return $this->foto ? Storage::disk(config('filesystems.fotos_disk', 'public'))->url($this->foto) : null;
    }
}
