<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Ticket de reclamación. Lleva folio aunque al final no proceda la garantía:
 * todo lo que el cliente reporta queda registrado.
 */
class GarantiaReclamacion extends Model
{
    protected $table = 'garantia_reclamaciones';

    public const CERRADA = 'cerrada';

    /** Flujo de atención, en orden. */
    public const ETAPAS = [
        'recibida' => ['Solicitud recibida', 'El cliente reporta la falla y adjunta evidencia.'],
        'revision' => ['Revisión de cobertura', 'Se confirma la serie, la vigencia y las condiciones de la póliza.'],
        'diagnostico' => ['Diagnóstico técnico', 'Servicio biomédico revisa el equipo y registra pruebas y causa.'],
        'atencion' => ['Reparación o sustitución', 'Se repara, se cambia el componente o se entrega un equipo sustituto.'],
        'entrega' => ['Entrega al cliente', 'El equipo regresa al cliente con su acta de entrega.'],
        'cerrada' => ['Cierre', 'Se documenta la resolución y se registra la satisfacción.'],
    ];

    public const CANALES = [
        'telefono' => 'Teléfono',
        'whatsapp' => 'WhatsApp',
        'correo' => 'Correo',
        'visita' => 'Visita en sitio',
        'portal' => 'Portal del cliente',
        'otro' => 'Otro',
    ];

    public const PRIORIDADES = [
        'critica' => ['Crítica', 'badge--danger'],
        'alta' => ['Alta', 'badge--warn'],
        'media' => ['Media', 'badge--info'],
        'baja' => ['Baja', ''],
    ];

    public const DICTAMENES = [
        'pendiente' => ['Por dictaminar', ''],
        'procede' => ['Procede', 'badge--ok'],
        'parcial' => ['Procede parcialmente', 'badge--warn'],
        'no_procede' => ['No procede', 'badge--danger'],
    ];

    public const RESOLUCIONES = [
        'reparacion' => 'Reparación',
        'sustitucion' => 'Sustitución de componente o equipo',
        'ajuste' => 'Ajuste o calibración',
        'sin_falla' => 'Sin falla encontrada',
        'reembolso' => 'Reembolso o nota de crédito',
        'otra' => 'Otra',
    ];

    protected $fillable = [
        'folio', 'garantia_id', 'customer_id', 'no_serie', 'equipo', 'canal',
        'contacto_nombre', 'contacto_telefono', 'contacto_correo', 'sintomas',
        'prioridad', 'es_seguridad', 'etapa', 'diagnostico', 'pruebas', 'causa',
        'dictamen', 'dictamen_motivo', 'dictamen_fundamento', 'dictamen_por', 'dictamen_en',
        'resolucion', 'resolucion_detalle', 'service_id', 'entregado_a', 'responsable_id',
        'recibida_en', 'primera_respuesta_en', 'limite_en', 'cerrada_en',
        'satisfaccion', 'comentario_cliente', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'es_seguridad' => 'boolean',
            'dictamen_en' => 'datetime',
            'recibida_en' => 'datetime',
            'primera_respuesta_en' => 'datetime',
            'limite_en' => 'datetime',
            'cerrada_en' => 'datetime',
            'satisfaccion' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (GarantiaReclamacion $r) {
            $r->folio = $r->folio ?: static::siguienteFolio();
        });
    }

    public static function siguienteFolio(): string
    {
        $anio = now()->year;
        $ultimo = static::where('folio', 'like', "REC-{$anio}-%")->count();

        return sprintf('REC-%d-%04d', $anio, $ultimo + 1);
    }

    public function garantia(): BelongsTo
    {
        return $this->belongsTo(Garantia::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function responsable(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }

    public function dictaminadaPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dictamen_por');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function costos(): HasMany
    {
        return $this->hasMany(GarantiaCosto::class, 'reclamacion_id')->latest();
    }

    public function archivos(): HasMany
    {
        return $this->hasMany(GarantiaArchivo::class, 'reclamacion_id')->latest();
    }

    public function sustituciones(): HasMany
    {
        return $this->hasMany(GarantiaSustitucion::class, 'reclamacion_id')->latest();
    }

    public function eventos(): HasMany
    {
        return $this->hasMany(GarantiaEvento::class, 'reclamacion_id')->latest('created_at')->latest('id');
    }

    public function scopeAbiertas(Builder $q): Builder
    {
        return $q->where('etapa', '!=', self::CERRADA);
    }

    /** Abiertas que ya pasaron su fecha límite o siguen sin primera respuesta a tiempo. */
    public function scopeAtrasadas(Builder $q): Builder
    {
        $horas = (int) config('garantias.horas_primera_respuesta', 24);

        return $q->abiertas()->where(function ($w) use ($horas) {
            $w->where('limite_en', '<', now())
                ->orWhere(fn ($x) => $x->whereNull('primera_respuesta_en')->where('recibida_en', '<', now()->subHours($horas)));
        });
    }

    public function abierta(): bool
    {
        return $this->etapa !== self::CERRADA;
    }

    public function atrasada(): bool
    {
        if (! $this->abierta()) {
            return false;
        }

        $horas = (int) config('garantias.horas_primera_respuesta', 24);

        return ($this->limite_en && $this->limite_en->isPast())
            || (! $this->primera_respuesta_en && $this->recibida_en->copy()->addHours($horas)->isPast());
    }

    public function etapaLabel(): string
    {
        return self::ETAPAS[$this->etapa][0] ?? ucfirst($this->etapa);
    }

    public function etapaNumero(): int
    {
        return array_search($this->etapa, array_keys(self::ETAPAS), true) + 1;
    }

    public function prioridadLabel(): string
    {
        return self::PRIORIDADES[$this->prioridad][0] ?? ucfirst($this->prioridad);
    }

    public function prioridadBadge(): string
    {
        return self::PRIORIDADES[$this->prioridad][1] ?? '';
    }

    public function dictamenLabel(): string
    {
        return self::DICTAMENES[$this->dictamen][0] ?? ucfirst($this->dictamen);
    }

    public function dictamenBadge(): string
    {
        return self::DICTAMENES[$this->dictamen][1] ?? '';
    }

    public function canalLabel(): string
    {
        return self::CANALES[$this->canal] ?? ucfirst($this->canal);
    }

    public function clienteNombre(): string
    {
        return trim(($this->customer->nombre ?? '').' '.($this->customer->apellido ?? ''))
            ?: ($this->contacto_nombre ?: 'Sin cliente');
    }

    public function equipoNombre(): string
    {
        return $this->garantia?->equipoNombre() ?: ($this->equipo ?: 'Equipo sin identificar');
    }

    /** Días desde que se recibió hasta el cierre (o hasta hoy si sigue abierta). */
    public function diasAbierta(): int
    {
        return (int) $this->recibida_en->diffInDays($this->cerrada_en ?? now());
    }

    public function costoTotal(): float
    {
        return (float) $this->costos->where('estado', 'aprobado')->sum('monto');
    }

    public function registrar(string $tipo, string $descripcion, array $datos = []): GarantiaEvento
    {
        return GarantiaEvento::anotar($tipo, $descripcion, $datos, $this->garantia, $this);
    }
}
