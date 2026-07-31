<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CongressEvent extends Model
{
    /** @use HasFactory<\Database\Factories\CongressEventFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
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
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'assembly_time' => 'datetime:H:i',
        'disassembly_time' => 'datetime:H:i',
        'download_access' => 'boolean',
        'upload_access' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
