<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ProductoSerial extends Model
{
    protected $table = 'producto_seriales';

    /** Prefijo de la etiqueta interna que va impresa en el QR. */
    public const PREFIJO = 'MB';

    /**
     * Por dónde va la pieza.
     *
     * No es una secuencia: el estado sale de la ruta de la pieza (ver
     * RutaDeProcesos). Los estados que coinciden con un proceso significan
     * "ahí es donde está parada ahorita".
     */
    public const ESTADOS = [
        'recibido' => 'Recibido',
        'en_revision' => 'En revisión',
        'hojalateria' => 'Hojalatería',
        'mantenimiento' => 'Mantenimiento',
        'limpieza' => 'Limpieza',
        'disponible' => 'Disponible',
        'vendido' => 'Vendido',
        'baja' => 'Baja',
    ];

    /** Estados desde los que la pieza todavía no se puede vender. */
    public const NO_VENDIBLES = ['recibido', 'en_revision', 'hojalateria', 'mantenimiento', 'limpieza', 'baja'];

    protected $fillable = [
        'producto_id',
        'codigo',
        'condicion',
        'estado',
        'no_serie',
        'foto_path',
        'vendido',
        'vendido_en',
        'venta_item_id',
        'inventory_movement_id',
        'capturado_por',
        'editado_por',
    ];

    protected function casts(): array
    {
        return [
            'vendido' => 'boolean',
            'vendido_en' => 'datetime',
        ];
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    public function ventaItem(): BelongsTo
    {
        return $this->belongsTo(VentaItem::class);
    }

    /** La entrada de inventario (con su evidencia) que trajo esta unidad. */
    public function entrada(): BelongsTo
    {
        return $this->belongsTo(InventoryMovement::class, 'inventory_movement_id');
    }

    /** Quién capturó esta unidad al momento de la entrada. */
    public function capturadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'capturado_por');
    }

    /** Quién hizo la última corrección al serial o la foto de esta unidad. */
    public function editadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'editado_por');
    }

    /** Los pasos por los que tiene que pasar esta pieza, en orden. */
    public function procesos(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PiezaProceso::class)->orderBy('orden')->orderBy('id');
    }

    /** Lo que le falta para poder venderse. */
    public function procesosPendientes(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->procesos()->whereIn('estado', ['pendiente', 'en_curso']);
    }

    public function estadoLabel(): string
    {
        return self::ESTADOS[$this->estado] ?? $this->estado;
    }

    /** Lo que falta, en palabras: "Hojalatería y mantenimiento". */
    public function faltaTexto(): ?string
    {
        $faltan = $this->procesos->whereIn('estado', ['pendiente', 'en_curso'])
            ->map(fn (PiezaProceso $p) => $p->nombre());

        if ($faltan->isEmpty()) {
            return null;
        }

        if ($faltan->count() === 1) {
            return $faltan->first();
        }

        return $faltan->slice(0, -1)->implode(', ').' y '.$faltan->last();
    }

    /**
     * A dónde lleva el QR pegado a esta pieza.
     *
     * Es una liga pública: la abre quien tenga la pieza enfrente, sin
     * cuenta en el sistema. Por eso la ficha no enseña precios ni notas
     * internas.
     */
    public function urlPublica(): ?string
    {
        return $this->codigo ? route('publico.equipo', $this->codigo) : null;
    }

    /** Una pieza en hojalatería o mantenimiento todavía no se puede vender. */
    public function vendible(): bool
    {
        return ! $this->vendido && ! in_array($this->estado, self::NO_VENDIBLES, true);
    }

    /**
     * Reserva de golpe los siguientes N códigos de etiqueta.
     *
     * Se calcula el consecutivo una sola vez y se reparte: dar de alta 100
     * accesorios no puede significar 100 consultas para saber qué número
     * sigue.
     *
     * @return array<int, string>
     */
    public static function generarCodigos(int $cuantos): array
    {
        $ultimo = static::withoutGlobalScopes()
            ->where('codigo', 'like', self::PREFIJO.'-%')
            ->orderByRaw('LENGTH(codigo) DESC, codigo DESC')
            ->value('codigo');

        $desde = $ultimo ? (int) substr($ultimo, strlen(self::PREFIJO) + 1) : 0;

        return collect(range(1, max(1, $cuantos)))
            ->map(fn (int $i) => self::PREFIJO.'-'.str_pad((string) ($desde + $i), 6, '0', STR_PAD_LEFT))
            ->all();
    }

    /** URL pública de la foto individual de esta unidad, lista para <img>. */
    public function fotoUrl(): ?string
    {
        return $this->foto_path
            ? Storage::disk(config('filesystems.fotos_disk', 'public'))->url($this->foto_path)
            : null;
    }
}
