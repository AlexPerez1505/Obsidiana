<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Un producto, equipo, paquete o tipo de equipo al que aplica un cupón. */
class CuponAlcance extends Model
{
    protected $table = 'mkt_cupon_alcances';

    public $timestamps = false;

    protected $fillable = ['cupon_id', 'tipo', 'ref_id'];

    public const TIPOS = ['producto', 'equipo', 'paquete', 'tipo_equipo'];
}
