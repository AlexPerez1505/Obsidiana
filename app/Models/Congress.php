<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Congress extends Model
{
    /** @use HasFactory<\Database\Factories\CongressFactory> */
    use HasFactory;

    protected $table = 'eventos_congreso';

    protected $fillable = [
        'name',
        'label',
        'description',
        'image_path',
        'category_id',
        'start_date',
        'end_date',
        'assembly_time',
        'disassembly_time',
        'download_access',
        'download_text',
        'upload_access',
        'upload_text',
        'address',
        'comments',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'assembly_time' => 'datetime:H:i',
        'disassembly_time' => 'datetime:H:i',
        'download_access' => 'boolean',
        'upload_access' => 'boolean',
        'image_path' => 'array',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class, 'congress_id');
    }

    public function notifiedUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'evento_congreso_usuario', 'congress_event_id', 'user_id')
            ->withPivot(['notified', 'notified_at'])
            ->withTimestamps();
    }
}
