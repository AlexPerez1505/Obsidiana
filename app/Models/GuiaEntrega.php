<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Storage;

class GuiaEntrega extends Model
{
    protected $table = 'guia_entregas';

    protected $fillable = [
        'numero_rastreo', 'peso_total', 'fecha_entrega', 'contenido',
        'destinatario', 'entregado_por', 'observaciones', 'foto_path',
        'firma_path', 'estado', 'created_by',
    ];

    protected function casts(): array
    {
        return ['peso_total' => 'decimal:2', 'fecha_entrega' => 'date'];
    }

    public function creador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function seriales(): BelongsToMany
    {
        return $this->belongsToMany(ProductoSerial::class, 'guia_entrega_producto_serial');
    }

    public function fotoUrl(): ?string
    {
        return $this->foto_path ? Storage::disk(config('filesystems.fotos_disk', 'public'))->url($this->foto_path) : null;
    }

    public function firmaUrl(): ?string
    {
        return $this->firma_path ? Storage::disk(config('filesystems.fotos_disk', 'public'))->url($this->firma_path) : null;
    }
}
