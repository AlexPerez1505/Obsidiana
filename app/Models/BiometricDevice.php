<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class BiometricDevice extends Model
{
    protected $fillable = [
        'name', 'code', 'serial_number', 'location', 'api_key_hash', 'kiosk_token',
        'status', 'last_seen_at', 'last_ip', 'agent_version',
    ];

    protected $hidden = ['api_key_hash'];

    protected function casts(): array
    {
        return ['last_seen_at' => 'datetime'];
    }

    public function punches(): HasMany
    {
        return $this->hasMany(AttendancePunch::class, 'device_id');
    }

    /**
     * Crea el dispositivo y devuelve [modelo, llave en claro]. La llave solo
     * se ve esta vez: en la base queda su hash.
     */
    public static function crear(array $datos): array
    {
        $llave = 'bk_'.Str::random(48);

        $dispositivo = static::create($datos + [
            'api_key_hash' => hash('sha256', $llave),
            'kiosk_token' => Str::random(40),
        ]);

        return [$dispositivo, $llave];
    }

    /** Genera una llave nueva (la anterior deja de servir). */
    public function regenerarLlave(): string
    {
        $llave = 'bk_'.Str::random(48);
        $this->update(['api_key_hash' => hash('sha256', $llave)]);

        return $llave;
    }

    public static function porLlave(?string $llave): ?self
    {
        if (! $llave) {
            return null;
        }

        return static::where('api_key_hash', hash('sha256', $llave))->where('status', 'active')->first();
    }

    /** Se considera en línea si reportó en los últimos 2 minutos. */
    public function enLinea(): bool
    {
        return $this->last_seen_at !== null && $this->last_seen_at->gt(now()->subMinutes(2));
    }

    public function urlPantalla(): string
    {
        return route('checador.pantalla', $this->kiosk_token);
    }
}
