<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GarantiaDocumento extends Model
{
    protected $fillable = [
        'venta_id',
        'folio',
        'nombre',
        'tipo_equipo',
        'archivos',
    ];

    protected function casts(): array
    {
        return [
            'archivos' => 'array',
        ];
    }

    public function venta()
    {
        return $this->belongsTo(Venta::class);
    }

    public static function siguienteFolio(): string
    {
        $anio = now()->year;
        $ultimo = static::where('folio', 'like', "GAR-{$anio}-%")->count();
        return sprintf('GAR-%d-%04d', $anio, $ultimo + 1);
    }
}
