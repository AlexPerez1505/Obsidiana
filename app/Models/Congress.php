<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Congress extends Model
{
    /** @use HasFactory<\Database\Factories\CongressFactory> */
    use HasFactory;

    protected $table = 'congresos_eventos';

    protected $fillable = [
        'nombre',
        'descripcion',
        'path_archivo',
        'categoria_id',
        'fecha_inicio',
        'fecha_finalizacion',
        'hora_montaje',
        'hora_desmontaje',
        'descarga_acceso',
        'descarga_texto',
        'acceso_subir',
        'subir_texto',
        'latitude',
        'longitud',
        'direccion',
        'comments',
    ];

    /**
     * aviso_regreso_en queda fuera de $fillable a propósito: lo mueve solo
     * el servicio que manda el aviso, no un formulario.
     */
    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_finalizacion' => 'date',
        'hora_montaje' => 'datetime:H:i',
        'hora_desmontaje' => 'datetime:H:i',
        'path_archivo' => 'array',
        'descarga_acceso' => 'boolean',
        'acceso_subir' => 'boolean',
        'latitude' => 'decimal:7',
        'longitud' => 'decimal:7',
        'aviso_regreso_en' => 'datetime',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'categoria_id');
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class, 'congress_id');
    }

    public function notifiedUsers(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'evento_congreso_usuario',
            'congress_event_id',
            'user_id'
        )
        ->withPivot(['notified', 'notified_at'])
        ->withTimestamps();
    }

    /**
     * Todas las piezas que en algún momento se llevaron a este congreso,
     * vendidas o no. Es el historial del evento.
     */
    public function unidadesEnCongreso(): HasMany
    {
        return $this->hasMany(ProductoSerial::class, 'congress_id')->with('producto');
    }

    /** Las que siguen allá: no se han vendido ni se han regresado. */
    public function unidadesPresentes(): HasMany
    {
        return $this->unidadesEnCongreso()->where('vendido', false);
    }

    /**
     * Las que se vendieron estando en el congreso.
     *
     * Vender no quita la marca del congreso a propósito: así queda el
     * rastro de qué se vendió en cada evento, que es justo lo que
     * justifica el gasto de ir.
     */
    public function unidadesVendidas(): HasMany
    {
        return $this->unidadesEnCongreso()->where('vendido', true);
    }

    /** Quién asistió: ponentes, distribuidores, asistentes... */
    public function participantes(): HasMany
    {
        return $this->hasMany(CongresoParticipante::class);
    }

    /**
     * Las piezas que están en el congreso, agrupadas por producto: para la
     * tabla de "Productos del congreso" no importa la pieza suelta, importa
     * cuántas de cada modelo se llevaron.
     */
    public function productosResumen()
    {
        return $this->unidadesPresentes()->get()
            ->groupBy('producto_id')
            ->map(function ($unidades) {
                $producto = $unidades->first()->producto;

                return [
                    'producto' => $producto,
                    'cantidad' => $unidades->count(),
                    'unidades' => $unidades,
                ];
            })
            ->values();
    }

    /**
     * El congreso ya terminó y todavía hay piezas marcadas como que están
     * allá. Nadie las regresó: el dato deja de ser confiable y hay que
     * avisarlo.
     */
    public function tienePiezasSinRegresar(): bool
    {
        return $this->estado() === 'finished' && $this->unidadesPresentes()->exists();
    }

    /** upcoming | active | finished, según hoy contra las fechas del congreso. */
    public function estado(): string
    {
        $hoy = now()->startOfDay();

        if ($hoy->lt($this->fecha_inicio->copy()->startOfDay())) {
            return 'upcoming';
        }

        if ($hoy->gt($this->fecha_finalizacion->copy()->startOfDay())) {
            return 'finished';
        }

        return 'active';
    }

    public function estadoLabel(): string
    {
        return match ($this->estado()) {
            'upcoming' => 'Próximo',
            'finished' => 'Finalizado',
            default => 'Activo',
        };
    }

    /** "Lugar" es el nombre que usa la pantalla; el dato es "direccion". */
    public function getLugarAttribute(): ?string
    {
        return $this->direccion;
    }
}
