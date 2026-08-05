<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ViaticExpense extends Model
{
    use HasFactory;

    protected $fillable = [
        'viatic_id', 'type', 'label', 'amount', 'icon',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function viatic(): BelongsTo
    {
        return $this->belongsTo(Viatic::class);
    }
}
