<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Una quincena cerrada: sus números quedan congelados aunque cambien las reglas. */
class PayrollRun extends Model
{
    protected $fillable = [
        'period_start', 'period_end', 'status', 'total_sueldos', 'total_bonos', 'total_descuentos',
        'total_neto', 'employees', 'closed_at', 'closed_by', 'paid_at',
    ];

    protected function casts(): array
    {
        return ['period_start' => 'date', 'period_end' => 'date', 'closed_at' => 'datetime', 'paid_at' => 'datetime'];
    }

    public function lines(): HasMany
    {
        return $this->hasMany(PayrollRunLine::class)->orderBy('user_name');
    }

    public function cerro(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function estadoLabel(): string
    {
        return $this->status === 'pagada' ? 'Pagada' : 'Cerrada';
    }
}
