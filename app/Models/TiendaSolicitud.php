<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Una solicitud de cotización enviada desde la tienda en línea.
 *
 * El cliente no tiene cuenta: deja sus datos y la lista de equipos que le
 * interesan. Si su teléfono o correo ya existen en Clientes, se liga sola.
 */
class TiendaSolicitud extends Model
{
    protected $table = 'tienda_solicitudes';

    public const ESTADOS = [
        'nueva' => ['texto' => 'Nueva', 'badge' => 'badge--warn'],
        'en_proceso' => ['texto' => 'En proceso', 'badge' => 'badge--info'],
        'cotizada' => ['texto' => 'Cotizada', 'badge' => 'badge--ok'],
        'descartada' => ['texto' => 'Descartada', 'badge' => 'badge--danger'],
    ];

    public const TIPOS = [
        'cotizacion' => 'Cotización',
        'contacto' => 'Contacto',
    ];

    /** Motivos del formulario de Contacto de la tienda. */
    public const MOTIVOS = [
        'compra' => 'Quiero comprar equipo',
        'renta' => 'Renta de equipo',
        'mantenimiento' => 'Mantenimiento o reparación',
        'garantia' => 'Garantía de un equipo',
        'otro' => 'Otro asunto',
    ];

    protected $fillable = [
        'folio', 'tipo', 'motivo','nombre', 'empresa', 'correo', 'telefono', 'ciudad',
        'especialidad', 'mensaje', 'articulos', 'estado', 'customer_id',
        'atendida_por', 'atendida_en', 'nota_interna', 'ip',
    ];

    protected function casts(): array
    {
        return [
            'articulos' => 'array',
            'atendida_en' => 'datetime',
        ];
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function atendidaPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'atendida_por');
    }

    /** WEB-AAAA-####, consecutivo por año tomando el mayor (no el conteo). */
    public static function siguienteFolio(): string
    {
        $prefijo = 'WEB-'.now()->format('Y').'-';
        $ultimo = static::where('folio', 'like', $prefijo.'%')->max('folio');
        $n = $ultimo ? ((int) substr($ultimo, strlen($prefijo))) + 1 : 1;

        return $prefijo.str_pad((string) $n, 4, '0', STR_PAD_LEFT);
    }

    public function estadoTexto(): string
    {
        return self::ESTADOS[$this->estado]['texto'] ?? $this->estado;
    }

    public function estadoBadge(): string
    {
        return self::ESTADOS[$this->estado]['badge'] ?? '';
    }

    public function totalPiezas(): int
    {
        return (int) collect($this->articulos ?? [])->sum('cantidad');
    }

    public function esContacto(): bool
    {
        return $this->tipo === 'contacto';
    }

    public function motivoTexto(): ?string
    {
        return $this->motivo ? (self::MOTIVOS[$this->motivo] ?? $this->motivo) : null;
    }

    /** Lo que se pidió, en una línea: equipos o el motivo del mensaje. */
    public function resumen(): string
    {
        if ($this->esContacto()) {
            return 'Contacto: '.($this->motivoTexto() ?? 'mensaje');
        }

        return collect($this->articulos ?? [])->map(fn ($a) => $a['cantidad'].' × '.$a['nombre'])->implode(', ');
    }

    /** Teléfono en dígitos para armar el enlace de WhatsApp (México si son 10). */
    public function whatsapp(): string
    {
        $d = Customer::telefonoNormalizado($this->telefono);

        return strlen($d) === 10 ? '52'.$d : $d;
    }
}
