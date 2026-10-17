<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class GarantiaArchivo extends Model
{
    protected $table = 'garantia_archivos';

    public const TIPOS = [
        'evidencia' => 'Evidencia de la falla',
        'diagnostico' => 'Reporte técnico',
        'factura' => 'Factura o comprobante',
        'acta' => 'Acta o acuse firmado',
        'otro' => 'Otro',
    ];

    protected $fillable = ['garantia_id', 'reclamacion_id', 'tipo', 'nombre', 'path', 'mime', 'size', 'user_id'];

    public static function disco(): string
    {
        return config('filesystems.fotos_disk', 'public');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function url(): string
    {
        return Storage::disk(static::disco())->url($this->path);
    }

    public function esImagen(): bool
    {
        return str_starts_with((string) $this->mime, 'image/');
    }

    public function tipoLabel(): string
    {
        return self::TIPOS[$this->tipo] ?? ucfirst($this->tipo);
    }
}
