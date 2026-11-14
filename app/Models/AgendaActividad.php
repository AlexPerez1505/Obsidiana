<?php

namespace App\Models;

use App\Models\Concerns\EsItemDeAgenda;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Actividad o evento de la agenda que no es un seguimiento de Clientes:
 * reuniones, visitas, capacitaciones, tareas, eventos de la empresa...
 *
 * Un evento compartido es UNA fila; quienes lo ven lo ven por sus
 * participantes y destinatarios (departamentos, equipos o toda la
 * empresa), no por copias.
 */
class AgendaActividad extends Model
{
    use EsItemDeAgenda;
    use SoftDeletes;

    protected $table = 'agenda_actividades';

    /** tipo => [etiqueta, clase, ¿ocupa horario por omisión?] */
    public const TIPOS = [
        'reunion' => ['Reunión interna', 'evento', true],
        'llamada' => ['Llamada', 'tarea', true],
        'seguimiento' => ['Seguimiento comercial', 'tarea', false],
        'visita' => ['Visita a cliente', 'evento', true],
        'demo' => ['Demostración de equipo', 'evento', true],
        'entrega' => ['Entrega o instalación', 'evento', true],
        'capacitacion' => ['Capacitación', 'evento', true],
        'servicio' => ['Servicio técnico', 'evento', true],
        'tarea_admin' => ['Tarea administrativa', 'tarea', false],
        'evento_empresa' => ['Evento general de la empresa', 'evento', false],
        'bloqueo' => ['Bloqueo de disponibilidad', 'evento', true],
        'otro' => ['Otro', 'tarea', false],
    ];

    public const ESTADOS_TAREA = ['pendiente' => 'Pendiente', 'en_proceso' => 'En proceso', 'completado' => 'Completado', 'cancelado' => 'Cancelado'];

    public const ESTADOS_EVENTO = ['programado' => 'Programado', 'realizado' => 'Realizado', 'cancelado' => 'Cancelado'];

    public const PRIORIDADES = ['baja' => 'Baja', 'normal' => 'Normal', 'alta' => 'Alta'];

    public const VISIBILIDADES = [
        'privado' => 'Privado',
        'participantes' => 'Compartido con participantes',
        'grupo' => 'Departamento o equipo',
        'empresa' => 'Toda la empresa',
    ];

    public const RESPUESTAS = ['pendiente' => 'Pendiente', 'aceptada' => 'Aceptada', 'rechazada' => 'Rechazada'];

    protected $fillable = [
        'titulo', 'descripcion', 'tipo', 'clase', 'estado',
        'fecha_inicio', 'hora_inicio', 'fecha_fin', 'hora_fin', 'todo_el_dia',
        'responsable_id', 'customer_id', 'ubicacion', 'enlace', 'prioridad', 'visibilidad',
        'ocupa_horario', 'recordatorios', 'serie_id', 'recurrencia',
        'resultado', 'completado_en', 'completado_por', 'cancelado_en', 'motivo_cancelacion',
        'legado_id', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'fecha_inicio' => 'date',
            'fecha_fin' => 'date',
            'todo_el_dia' => 'boolean',
            'ocupa_horario' => 'boolean',
            'recordatorios' => 'array',
            'recurrencia' => 'array',
            'completado_en' => 'datetime',
            'cancelado_en' => 'datetime',
        ];
    }

    public static function claseDe(string $tipo): string
    {
        return self::TIPOS[$tipo][1] ?? 'tarea';
    }

    public static function estadoInicial(string $tipo): string
    {
        return self::claseDe($tipo) === 'evento' ? 'programado' : 'pendiente';
    }

    public static function estadosDe(string $clase): array
    {
        return $clase === 'evento' ? self::ESTADOS_EVENTO : self::ESTADOS_TAREA;
    }

    public function responsable(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }

    public function creador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function participantes(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'agenda_actividad_usuario', 'actividad_id', 'user_id')
            ->withPivot(['respuesta', 'respondido_en', 'comentario'])->withTimestamps();
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'agenda_actividad_rol', 'actividad_id', 'role_id');
    }

    public function equipos(): BelongsToMany
    {
        return $this->belongsToMany(AgendaEquipo::class, 'agenda_actividad_equipo', 'actividad_id', 'equipo_id');
    }

    public function ocurrencias(): HasMany
    {
        return $this->hasMany(self::class, 'serie_id', 'serie_id');
    }

    public function tipoLabel(): string
    {
        return self::TIPOS[$this->tipo][0] ?? ucfirst((string) $this->tipo);
    }

    public function estadoLabel(): string
    {
        return self::estadosDe($this->clase)[$this->estado] ?? ucfirst((string) $this->estado);
    }

    public function esTarea(): bool
    {
        return $this->clase === 'tarea';
    }

    public function cerrada(): bool
    {
        return in_array($this->estado, ['completado', 'realizado', 'cancelado'], true);
    }

    /** Momento de inicio en hora de la empresa (inicio del día si no tiene hora). */
    public function inicio(): CarbonImmutable
    {
        $tz = config('agenda.timezone');

        return CarbonImmutable::parse($this->fecha_inicio->toDateString().' '.($this->todo_el_dia || ! $this->hora_inicio ? '00:00' : substr($this->hora_inicio, 0, 5)), $tz);
    }

    public function fin(): CarbonImmutable
    {
        $tz = config('agenda.timezone');

        if ($this->todo_el_dia || ! $this->hora_inicio) {
            return CarbonImmutable::parse($this->fecha_fin->toDateString().' 23:59', $tz);
        }

        if ($this->hora_fin) {
            return CarbonImmutable::parse($this->fecha_fin->toDateString().' '.substr($this->hora_fin, 0, 5), $tz);
        }

        return $this->inicio()->addMinutes((int) config('agenda.duracion_default_min'));
    }

    /**
     * Vencido solo aplica a tareas: su fecha ya pasó y siguen abiertas.
     * Una reunión que ya pasó no es una tarea incumplida.
     */
    public function vencida(): bool
    {
        if (! $this->esTarea() || $this->cerrada()) {
            return false;
        }

        $ahora = CarbonImmutable::now(config('agenda.timezone'));

        return $this->hora_inicio && ! $this->todo_el_dia
            ? $this->fin()->lt($ahora)
            : $this->fecha_fin->toDateString() < $ahora->toDateString();
    }
}
