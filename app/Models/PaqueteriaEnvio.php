<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class PaqueteriaEnvio extends Model
{
    protected $table = 'paqueteria_envios';

    protected $fillable = [
        'cotizacion_id', 'remision_folio', 'cliente', 'destino', 'codigo_postal',
        'direccion', 'telefono_receptor', 'paqueteria', 'paqueteria_personalizada',
        'tipo_guia', 'numero_guia', 'capacidad_guia', 'fecha_envio',
        'fecha_entrega_estimada', 'costo', 'costo_pendiente', 'peso_real',
        'peso_volumetrico', 'peso_cobro', 'paquetes_count', 'estado', 'articulos',
        'paquetes', 'responsables', 'notas', 'ticket_path', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'fecha_envio' => 'date',
            'fecha_entrega_estimada' => 'date',
            'costo' => 'decimal:2',
            'capacidad_guia' => 'decimal:2',
            'peso_real' => 'decimal:2',
            'peso_volumetrico' => 'decimal:2',
            'peso_cobro' => 'decimal:2',
            'costo_pendiente' => 'boolean',
            'articulos' => 'array',
            'paquetes' => 'array',
            'responsables' => 'array',
        ];
    }

    public function cotizacion(): BelongsTo
    {
        return $this->belongsTo(Cotizacion::class);
    }

    public function creador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function ticketUrl(): ?string
    {
        return $this->ticket_path
            ? Storage::disk(config('filesystems.fotos_disk', 'public'))->url($this->ticket_path)
            : null;
    }

    public function paqueteriaNombre(): string
    {
        return $this->paqueteria_personalizada ?: $this->paqueteria;
    }

    public function trackingUrl(): ?string
    {
        if (!$this->numero_guia) {
            return null;
        }

        return match ($this->paqueteriaNombre()) {
            'FedEx' => 'https://www.fedex.com/fedextrack/?trknbr='.urlencode($this->numero_guia),
            'DHL' => 'https://www.dhl.com/mx-es/home/rastreo.html?tracking-id='.urlencode($this->numero_guia),
            default => null,
        };
    }
}
