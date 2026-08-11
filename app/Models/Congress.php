<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Congress extends Model
{
    /** @use HasFactory<\Database\Factories\CongressFactory> */
    use HasFactory;

    protected $table = 'congresos_eventos';

    protected $fillable = [
        'nombre',
        'descripcion',
        'path_archivo',
        'categoria_id',
        'fecha_inicio',
        'fecha_finalizacion',
        'hora_montaje',
        'hora_desmontaje',
        'descarga_acceso',
        'descarga_texto',
        'acceso_subir',
        'subir_texto',
        'latitude',
        'longitud',
        'direccion',
        'comments',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_finalizacion' => 'date',
        'hora_montaje' => 'datetime:H:i',
        'hora_desmontaje' => 'datetime:H:i',
        'descarga_acceso' => 'boolean',
        'acceso_subir' => 'boolean',
        'latitude' => 'decimal:7',
        'longitud' => 'decimal:7',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'categoria_id');
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class, 'congress_id');
    }

    public function notifiedUsers(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'evento_congreso_usuario',
            'congress_event_id',
            'user_id'
        )
        ->withPivot(['notified', 'notified_at'])
        ->withTimestamps();
    }
}