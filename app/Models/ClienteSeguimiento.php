<?php

namespace App\Models;

use App\Models\Concerns\EsItemDeAgenda;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Un seguimiento programado con un cliente o prospecto: qué hay que hacer
 * y cuándo. El día que toca, el responsable recibe el aviso.
 *
 * Es la ÚNICA fuente del seguimiento: la ficha del cliente y la agenda del
 * responsable leen y escriben esta misma fila (por SeguimientosCliente), así
 * completarlo, moverlo o reasignarlo en un lado se ve igual en el otro.
 */
class ClienteSeguimiento extends Model
{
    use EsItemDeAgenda;

    protected $table = 'cliente_seguimientos';

    /** Tipo de contacto. */
    public const TIPOS = [
        'llamada' => 'Llamarle',
        'cotizacion' => 'Hacerle cotización',
        'visita' => 'Visitarlo',
        'correo' => 'Escribirle',
        'demo' => 'Demostración de equipo',
        'otro' => 'Otro',
    ];

    public const ESTADOS = [
        'pendiente' => 'Pendiente',
        'en_proceso' => 'En proceso',
        'completado' => 'Completado',
        'cancelado' => 'Cancelado',
    ];

    public const ABIERTOS = ['pendiente', 'en_proceso'];

    public const PRIORIDADES = ['baja' => 'Baja', 'normal' => 'Normal', 'alta' => 'Alta'];

    protected $fillable = [
        'customer_id', 'user_id', 'tipo', 'prioridad', 'estado', 'origen', 'fecha', 'hora', 'recordatorios', 'nota',
        'notificado_en', 'hecho_en', 'hecho_por', 'resultado',
        'cancelado_en', 'cancelado_por', 'motivo_cancelacion', 'anterior_id', 'created_by',
    ];

    protected $attributes = [
        'estado' => 'pendiente',
        'prioridad' => 'normal',
        'origen' => 'cliente',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'recordatorios' => 'array',
            'notificado_en' => 'datetime',
            'hecho_en' => 'datetime',
            'cancelado_en' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function responsable(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function hechoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'hecho_por');
    }

    public function creador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function anterior(): BelongsTo
    {
        return $this->belongsTo(self::class, 'anterior_id');
    }

    /** Los que siguen abiertos (ni completados ni cancelados). */
    public function scopePendientes(Builder $q): Builder
    {
        return $q->whereIn('estado', self::ABIERTOS);
    }

    /** Los que ya tocan (hoy o antes) y todavía no se han avisado. */
    public function scopePorAvisar(Builder $q): Builder
    {
        return $q->pendientes()->whereNull('notificado_en')->whereDate('fecha', '<=', self::hoy()->toDateString());
    }

    public static function hoy(): CarbonImmutable
    {
        return CarbonImmutable::now(config('agenda.timezone'))->startOfDay();
    }

    public function tipoLabel(): string
    {
        return self::TIPOS[$this->tipo] ?? ucfirst($this->tipo);
    }

    public function estadoLabel(): string
    {
        return self::ESTADOS[$this->estado] ?? ucfirst((string) $this->estado);
    }

    public function prioridadLabel(): string
    {
        return self::PRIORIDADES[$this->prioridad] ?? 'Normal';
    }

    public function hecho(): bool
    {
        return $this->estado === 'completado' || ($this->hecho_en !== null && $this->estado !== 'cancelado');
    }

    public function cancelado(): bool
    {
        return $this->estado === 'cancelado';
    }

    public function abierto(): bool
    {
        return in_array($this->estado, self::ABIERTOS, true);
    }

    /** La hora en "HH:MM", o null si es pendiente del día sin hora. */
    public function horaCorta(): ?string
    {
        return $this->hora ? substr($this->hora, 0, 5) : null;
    }

    /** Momento en hora de la empresa: con hora, a esa hora; sin hora, el inicio del día. */
    public function momento(): ?CarbonImmutable
    {
        if (! $this->fecha) {
            return null;
        }

        return CarbonImmutable::parse($this->fecha->toDateString().' '.($this->horaCorta() ?? '00:00'), config('agenda.timezone'));
    }

    public function vencido(): bool
    {
        if (! $this->abierto() || ! $this->fecha) {
            return false;
        }

        $ahora = CarbonImmutable::now(config('agenda.timezone'));

        return $this->horaCorta()
            ? $this->momento()->lt($ahora)
            : $this->fecha->toDateString() < $ahora->toDateString();
    }

    public function esHoy(): bool
    {
        return $this->abierto() && $this->fecha && $this->fecha->toDateString() === self::hoy()->toDateString();
    }

    public function nombreCliente(): string
    {
        return trim(($this->customer->nombre ?? '').' '.($this->customer->apellido ?? '')) ?: 'Cliente';
    }

    /** "Hoy", "Mañana", "Vencido hace 3 días", "En 12 días"... */
    public function cuando(): string
    {
        if ($this->cancelado()) {
            return 'Cancelado'.($this->cancelado_en ? ' el '.$this->cancelado_en->format('d/m/Y') : '');
        }

        if ($this->hecho()) {
            return 'Hecho el '.$this->hecho_en?->format('d/m/Y');
        }

        if (! $this->fecha) {
            return 'Sin fecha';
        }

        $dias = (int) self::hoy()->diffInDays(CarbonImmutable::parse($this->fecha->toDateString(), config('agenda.timezone'))->startOfDay(), false);

        $texto = match (true) {
            $dias === 0 => 'Hoy',
            $dias === 1 => 'Mañana',
            $dias === -1 => 'Vencido desde ayer',
            $dias < 0 => 'Vencido hace '.abs($dias).' días',
            default => 'En '.$dias.' días',
        };

        return $texto.($this->horaCorta() ? ' a las '.$this->horaCorta() : '');
    }
}
