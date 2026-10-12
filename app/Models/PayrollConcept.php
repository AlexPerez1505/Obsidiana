<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/** Un bono o descuento propio de una persona: fijo cada quincena o de una sola vez. */
class PayrollConcept extends Model
{
    protected $fillable = ['user_id', 'kind', 'name', 'amount', 'recurrence', 'period_start', 'valid_from', 'valid_until', 'note', 'created_by'];

    protected function casts(): array
    {
        return ['amount' => 'float', 'period_start' => 'date', 'valid_from' => 'date', 'valid_until' => 'date'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function esBono(): bool
    {
        return $this->kind === 'bono';
    }

    /** ¿Aplica en la quincena [desde, hasta]? */
    public function aplicaEn(Carbon $desde, Carbon $hasta): bool
    {
        if ($this->recurrence === 'unica') {
            return $this->period_start && $this->period_start->isSameDay($desde);
        }

        if ($this->valid_from && $this->valid_from->greaterThan($hasta)) {
            return false;
        }

        if ($this->valid_until && $this->valid_until->lessThan($desde)) {
            return false;
        }

        return true;
    }

    public function vigenciaTexto(): string
    {
        if ($this->recurrence === 'unica') {
            return 'Solo la quincena del '.$this->period_start?->format('d/m/Y');
        }

        $t = 'Cada quincena';
        if ($this->valid_from) {
            $t .= ' desde '.$this->valid_from->format('d/m/Y');
        }
        if ($this->valid_until) {
            $t .= ' hasta '.$this->valid_until->format('d/m/Y');
        }

        return $t;
    }
}
