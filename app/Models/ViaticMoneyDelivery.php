<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ViaticMoneyDelivery extends Model
{
    use HasFactory;

    protected $fillable = ['viatic_id', 'source', 'recipient_user_id', 'amount'];

    protected $casts = ['amount' => 'decimal:2'];

    public function viatic(): BelongsTo
    {
        return $this->belongsTo(Viatic::class);
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_user_id');
    }
}
