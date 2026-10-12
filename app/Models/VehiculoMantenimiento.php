<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class VehiculoMantenimiento extends Model
{
    protected $table = 'vehiculo_mantenimientos';

    public const TIPOS = [
        'preventivo' => 'Preventivo (servicio)',
        'correctivo' => 'Correctivo (reparación)',
        'verificacion' => 'Verificación',
        'seguro' => 'Seguro / póliza',
        'llantas' => 'Llantas',
        'otro' => 'Otro',
    ];

    protected $fillable = [
        'vehicle_id', 'user_id', 'tipo', 'fecha', 'odometro_km', 'costo', 'proveedor',
        'descripcion', 'proximo_fecha', 'proximo_km', 'comprobante',
    ];

    protected function casts(): array
    {
        return ['fecha' => 'date', 'proximo_fecha' => 'date', 'costo' => 'float'];
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function tipoLabel(): string
    {
        return self::TIPOS[$this->tipo] ?? ucfirst($this->tipo);
    }

    public function comprobanteUrl(): ?string
    {
        return $this->comprobante ? Storage::disk(config('filesystems.fotos_disk', 'public'))->url($this->comprobante) : null;
    }
}
