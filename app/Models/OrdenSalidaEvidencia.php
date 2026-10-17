<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Una foto o un video de evidencia de la orden de salida.
 *
 * El archivo vive en el disco de fotos y se va con el registro: borrar la
 * evidencia (o la orden entera) también borra el archivo.
 */
class OrdenSalidaEvidencia extends Model
{
    protected $table = 'orden_salida_evidencias';

    public const FOTO = 'foto';
    public const VIDEO = 'video';

    public const TIPOS = [self::FOTO, self::VIDEO];

    protected $fillable = [
        'orden_salida_id', 'tipo', 'path', 'nombre_original', 'tamano', 'subido_por',
    ];

    protected function casts(): array
    {
        return [
            'tamano' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        /*
        | El archivo se borra hasta que la transacción se confirma: si algo
        | falla y se revierte, el registro sigue ahí y no puede quedar
        | apuntando a un archivo que ya no existe.
        */
        static::deleted(function (OrdenSalidaEvidencia $evidencia) {
            $disco = config('filesystems.fotos_disk', 'public');
            $path = $evidencia->path;

            DB::afterCommit(fn () => Storage::disk($disco)->delete($path));
        });
    }

    public function orden(): BelongsTo
    {
        return $this->belongsTo(OrdenSalida::class, 'orden_salida_id');
    }

    public function subidoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'subido_por');
    }

    public function esVideo(): bool
    {
        return $this->tipo === self::VIDEO;
    }

    /** URL tal como la da el disco (relativa en el público). */
    public function url(): string
    {
        return Storage::disk(config('filesystems.fotos_disk', 'public'))->url($this->path);
    }

    /**
     * Quién puede quitarla: quien prepara salidas, o quien la subió.
     *
     * Por la API, a quien la subió también lo recorta su token: una llave
     * que solo puede ver salidas no borra nada, aunque su dueño sí pueda.
     */
    public function puedeBorrarla(User $user): bool
    {
        if ($user->can('salidas.preparar')) {
            return true;
        }

        $token = $user->currentAccessToken();

        return $this->subido_por === $user->id && (! $token || $token->can('salidas.preparar'));
    }
}
