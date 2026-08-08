<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EvidenciaPago extends Model
{
    protected $table = 'evidencias_pago';

    protected $fillable = [
        'pago_id',
        'nombre',
        'archivo_path',
    ];

    public function pago(): BelongsTo
    {
        return $this->belongsTo(Pago::class, 'pago_id');
    }
}
