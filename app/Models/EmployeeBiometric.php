<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

/** Plantilla biométrica de un dedo. El template se guarda cifrado. */
class EmployeeBiometric extends Model
{
    public const DEDOS = [
        0 => 'Pulgar derecho', 1 => 'Índice derecho', 2 => 'Medio derecho', 3 => 'Anular derecho', 4 => 'Meñique derecho',
        5 => 'Pulgar izquierdo', 6 => 'Índice izquierdo', 7 => 'Medio izquierdo', 8 => 'Anular izquierdo', 9 => 'Meñique izquierdo',
    ];

    protected $fillable = ['user_id', 'finger_index', 'template', 'template_format', 'device_id', 'status', 'registered_at', 'registered_by'];

    protected $hidden = ['template'];

    protected function casts(): array
    {
        return ['registered_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(BiometricDevice::class, 'device_id');
    }

    public function setTemplateAttribute(string $valor): void
    {
        $this->attributes['template'] = Crypt::encryptString($valor);
    }

    /** El template en claro (base64 tal como lo entregó el SDK). */
    public function plantilla(): string
    {
        return Crypt::decryptString($this->attributes['template']);
    }

    public function dedoLabel(): string
    {
        return self::DEDOS[$this->finger_index] ?? 'Dedo '.$this->finger_index;
    }
}
