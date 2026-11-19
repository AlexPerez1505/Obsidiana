<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Número de serie que el generador ya entregó a una captura que aún no se guarda. */
class SerieReserva extends Model
{
    protected $table = 'serie_reservas';

    protected $fillable = ['serie', 'prefijo', 'user_id', 'expira_en'];

    protected function casts(): array
    {
        return ['expira_en' => 'datetime'];
    }
}
