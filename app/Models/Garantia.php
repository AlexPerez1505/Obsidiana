<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Expediente de garantía de UNA unidad o componente.
 *
 * Una torre vendida como paquete genera una garantía por cada componente
 * (procesador, fuente de luz, cámara...), agrupadas por `grupo`: así la falla
 * de la cámara no toca la cobertura del resto de la torre.
 *
 * Nunca se borra: se anula con motivo y queda en la bitácora.
 */
class Garantia extends Model
{
    public const TIPOS = [
        'medibuy' => 'Garantía MediBuy',
        'fabricante' => 'Garantía del fabricante',
        'reacondicionado' => 'Garantía de reacondicionado',
        'reparacion' => 'Garantía por reparación',
        'refacciones' => 'Garantía de refacciones',
        'extendida' => 'Garantía extendida',
        'instalacion' => 'Garantía de instalación',
    ];

    public const CONDICIONES = [
        'nuevo' => 'Nuevo',
        'seminuevo' => 'Seminuevo',
        'reacondicionado' => 'Reacondicionado',
    ];

    /** Semáforo: clave => [texto, clase de badge]. */
    public const SEMAFORO = [
        'vigente' => ['Vigente', 'badge--ok'],
        'por_vencer' => ['Próxima a vencer', 'badge--warn'],
        'vencida' => ['Vencida', ''],
        'reclamacion' => ['Reclamación abierta', 'badge--info'],
        'en_reparacion' => ['En reparación', 'badge--warn'],
        'anulada' => ['Anulada', 'badge--danger'],
        'sustituida' => ['Sustituida', ''],
    ];

    protected $fillable = [
        'folio', 'public_token', 'origen', 'customer_id', 'venta_id', 'venta_item_id',
        'producto_serial_id', 'producto_id', 'equipo_id', 'paquete_id', 'grupo', 'unidad',
        'cobertura_id', 'descripcion', 'marca', 'modelo', 'no_serie', 'codigo_interno',
        'condicion', 'tipo', 'proveedor', 'meses', 'fecha_venta', 'fecha_entrega',
        'inicio', 'vence', 'fechas_manual', 'cubre', 'exclusiones', 'condiciones',
        'precio_venta', 'costo_adquisicion', 'responsable_id', 'estado',
        'motivo_anulacion', 'anulada_por', 'anulada_en', 'notas', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'fecha_venta' => 'date',
            'fecha_entrega' => 'date',
            'inicio' => 'date',
            'vence' => 'date',
            'anulada_en' => 'datetime',
            'fechas_manual' => 'boolean',
            'meses' => 'integer',
            'unidad' => 'integer',
            'precio_venta' => 'decimal:2',
            'costo_adquisicion' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Garantia $g) {
            $g->public_token = $g->public_token ?: (string) Str::uuid();
            $g->folio = $g->folio ?: static::siguienteFolio();
        });
    }

    public static function siguienteFolio(): string
    {
        $anio = now()->year;
        $ultimo = static::where('folio', 'like', "GAR-{$anio}-%")->count();

        return sprintf('GAR-%d-%04d', $anio, $ultimo + 1);
    }

    /* ----------------------------- Relaciones ----------------------------- */

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class);
    }

    public function ventaItem(): BelongsTo
    {
        return $this->belongsTo(VentaItem::class);
    }

    public function serial(): BelongsTo
    {
        return $this->belongsTo(ProductoSerial::class, 'producto_serial_id');
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    public function cobertura(): BelongsTo
    {
        return $this->belongsTo(GarantiaCobertura::class, 'cobertura_id');
    }

    public function responsable(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }

    public function anuladaPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'anulada_por');
    }

    public function reclamaciones(): HasMany
    {
        return $this->hasMany(GarantiaReclamacion::class)->latest('recibida_en');
    }

    public function reclamacionesAbiertas(): HasMany
    {
        return $this->hasMany(GarantiaReclamacion::class)->where('etapa', '!=', GarantiaReclamacion::CERRADA);
    }

    public function costos(): HasMany
    {
        return $this->hasMany(GarantiaCosto::class);
    }

    public function archivos(): HasMany
    {
        return $this->hasMany(GarantiaArchivo::class)->latest();
    }

    public function eventos(): HasMany
    {
        return $this->hasMany(GarantiaEvento::class)->latest('created_at')->latest('id');
    }

    /* ------------------------------- Scopes ------------------------------- */

    public function scopeActivas(Builder $q): Builder
    {
        return $q->where('estado', 'activa');
    }

    public function scopeVigentes(Builder $q): Builder
    {
        return $q->activas()->whereDate('vence', '>=', today());
    }

    public function scopePorVencer(Builder $q, ?int $dias = null): Builder
    {
        $dias ??= (int) config('garantias.dias_por_vencer', 30);

        return $q->activas()->whereDate('vence', '>=', today())->whereDate('vence', '<=', today()->addDays($dias));
    }

    /** Búsqueda por serie, etiqueta interna, folio, equipo o cliente. */
    public function scopeBuscar(Builder $q, ?string $texto): Builder
    {
        $texto = trim((string) $texto);
        if ($texto === '') {
            return $q;
        }

        return $q->where(function ($w) use ($texto) {
            $like = '%'.$texto.'%';
            $w->where('no_serie', 'like', $like)
                ->orWhere('codigo_interno', 'like', $like)
                ->orWhere('folio', 'like', $like)
                ->orWhere('descripcion', 'like', $like)
                ->orWhere('marca', 'like', $like)
                ->orWhere('modelo', 'like', $like)
                ->orWhereHas('customer', fn ($c) => $c->whereRaw("CONCAT_WS(' ', nombre, apellido) like ?", [$like]))
                ->orWhereHas('venta', fn ($v) => $v->where('folio', 'like', $like));
        });
    }

    /* ------------------------------- Estado ------------------------------- */

    public function anulada(): bool
    {
        return $this->estado === 'anulada';
    }

    public function vigente(): bool
    {
        return $this->estado === 'activa' && $this->vence && $this->vence->gte(today());
    }

    public function diasRestantes(): int
    {
        return (int) today()->diffInDays($this->vence, false);
    }

    /**
     * Semáforo para pantallas. Una reclamación abierta pesa más que la
     * fecha: es lo que alguien tiene que atender.
     */
    public function semaforo(): string
    {
        if ($this->estado === 'anulada' || $this->estado === 'sustituida') {
            return $this->estado;
        }

        $abiertas = $this->relationLoaded('reclamacionesAbiertas')
            ? $this->reclamacionesAbiertas
            : $this->reclamacionesAbiertas()->get(['id', 'etapa', 'resolucion']);

        if ($abiertas->isNotEmpty()) {
            return $abiertas->contains(fn ($r) => $r->etapa === 'atencion') ? 'en_reparacion' : 'reclamacion';
        }

        if (! $this->vigente()) {
            return 'vencida';
        }

        return $this->diasRestantes() <= (int) config('garantias.dias_por_vencer', 30) ? 'por_vencer' : 'vigente';
    }

    public function semaforoTexto(): string
    {
        return self::SEMAFORO[$this->semaforo()][0];
    }

    public function semaforoBadge(): string
    {
        return self::SEMAFORO[$this->semaforo()][1];
    }

    public function tipoLabel(): string
    {
        return self::TIPOS[$this->tipo] ?? ucfirst((string) $this->tipo);
    }

    public function condicionLabel(): string
    {
        return self::CONDICIONES[$this->condicion] ?? ucfirst((string) $this->condicion);
    }

    public function clienteNombre(): string
    {
        return trim(($this->customer->nombre ?? '').' '.($this->customer->apellido ?? '')) ?: 'Sin cliente';
    }

    public function equipoNombre(): string
    {
        // El nombre del renglón de venta muchas veces ya trae marca y modelo.
        $extra = collect([$this->marca, $this->modelo])
            ->filter(fn ($p) => filled($p) && ! str_contains(mb_strtolower($this->descripcion), mb_strtolower($p)));

        return trim($this->descripcion.' '.$extra->implode(' '));
    }

    /** Recalcula el vencimiento a partir del inicio y los meses. */
    public static function calcularVence(Carbon $inicio, int $meses): Carbon
    {
        return $inicio->copy()->addMonthsNoOverflow($meses)->subDay();
    }

    /** Costo total de garantía (lo aprobado) y lo recuperado. */
    public function costoTotal(): float
    {
        return (float) $this->costos()->where('estado', 'aprobado')->sum('monto');
    }

    public function urlPublica(): string
    {
        return route('publico.garantia', $this->public_token);
    }

    public function registrar(string $tipo, string $descripcion, array $datos = [], ?GarantiaReclamacion $reclamacion = null): GarantiaEvento
    {
        return GarantiaEvento::anotar($tipo, $descripcion, $datos, $this, $reclamacion);
    }
}
