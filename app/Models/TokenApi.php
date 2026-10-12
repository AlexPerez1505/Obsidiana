<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laravel\Sanctum\NewAccessToken;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Un token de la API: la sesión de una app o una llave de integración.
 *
 * Se guarda solo el hash; el texto completo se muestra una vez al crearlo.
 * Lo que puede hacer es lo que puede su dueño, recortado a las
 * habilidades del token ('*' = todo lo del dueño). Ver AppServiceProvider.
 */
class TokenApi extends PersonalAccessToken
{
    public const TIPO_APP = 'app';
    public const TIPO_INTEGRACION = 'integracion';

    protected $table = 'personal_access_tokens';

    protected $fillable = [
        'name',
        'tipo',
        'token',
        'abilities',
        'ip_permitidas',
        'creado_por_id',
        'expires_at',
    ];

    protected $hidden = ['token'];

    protected function casts(): array
    {
        return [
            'abilities' => 'json',
            'ip_permitidas' => 'array',
            'last_used_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    /**
     * Emite un token nuevo para el usuario y devuelve el texto en claro.
     *
     * Es lo mismo que createToken() de Sanctum, pero con las columnas
     * propias (tipo, IP permitidas, quién lo emitió).
     */
    public static function emitir(
        User $dueno,
        string $nombre,
        string $tipo,
        array $habilidades = ['*'],
        ?\DateTimeInterface $expira = null,
        ?array $ips = null,
        ?User $emisor = null,
    ): NewAccessToken {
        $secreto = $tipo === self::TIPO_INTEGRACION
            ? config('api.prefijo_llave').$dueno->generateTokenString()
            : $dueno->generateTokenString();

        $token = $dueno->tokens()->create([
            'name' => $nombre,
            'tipo' => $tipo,
            'token' => hash('sha256', $secreto),
            'abilities' => array_values($habilidades),
            'ip_permitidas' => $ips ?: null,
            'creado_por_id' => $emisor?->id,
            'expires_at' => $expira,
        ]);

        return new NewAccessToken($token, $token->getKey().'|'.$secreto);
    }

    public function creadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creado_por_id');
    }

    public function esIntegracion(): bool
    {
        return $this->tipo === self::TIPO_INTEGRACION;
    }

    public function accesoTotal(): bool
    {
        return in_array('*', $this->abilities ?? [], true);
    }

    /** Sin lista de IP la llave sirve desde cualquier lado. */
    public function ipPermitida(?string $ip): bool
    {
        $lista = array_filter($this->ip_permitidas ?? []);

        return $lista === [] || in_array($ip, $lista, true);
    }

    public function vencido(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }
}
