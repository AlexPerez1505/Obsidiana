<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class VehiculoCombustible extends Model
{
    protected $table = 'vehiculo_combustible';

    protected $fillable = ['vehicle_id', 'ruta_id', 'user_id', 'fecha', 'litros', 'monto', 'odometro_km', 'tanque_lleno', 'estacion', 'ticket'];

    protected function casts(): array
    {
        return ['fecha' => 'date', 'litros' => 'float', 'monto' => 'float', 'tanque_lleno' => 'boolean'];
    }

    /** Alta de una carga, desde la oficina o desde el teléfono del chofer. */
    public static function registrar(Vehicle $vehicle, array $data, ?\Illuminate\Http\UploadedFile $ticket, int $userId, ?int $rutaId = null): self
    {
        return static::create([
            'vehicle_id' => $vehicle->id,
            'ruta_id' => $rutaId,
            'user_id' => $userId,
            'fecha' => $data['fecha'],
            'litros' => $data['litros'],
            'monto' => $data['monto'],
            'odometro_km' => $data['odometro_km'] ?? null,
            'tanque_lleno' => (bool) ($data['tanque_lleno'] ?? false),
            'estacion' => $data['estacion'] ?? null,
            'ticket' => $ticket?->store('logistica/combustible', config('filesystems.fotos_disk', 'public')),
        ]);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function ticketUrl(): ?string
    {
        return $this->ticket ? Storage::disk(config('filesystems.fotos_disk', 'public'))->url($this->ticket) : null;
    }
}
