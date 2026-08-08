<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Congress extends Model
{
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
        return $this->hasMany(Customer::class, 'congreso_id');
    }
}
