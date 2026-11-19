<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * Una de las tres etapas de una orden de salida (ingeniería, empaque,
 * entrega), con su responsable, checklist, extras, evidencia y firma.
 */
class OrdenSalidaEtapa extends Model
{
    protected $table = 'orden_salida_etapas';

    public const INGENIERIA = 'ingenieria';
    public const EMPAQUE = 'empaque';
    public const ENTREGA = 'entrega';

    /** En orden: cada una empieza cuando termina la anterior. */
    public const ETAPAS = [
        self::INGENIERIA => 'Ingeniería',
        self::EMPAQUE => 'Emplayado y empaque',
        self::ENTREGA => 'Entrega',
    ];

    public const DESCRIPCION = [
        self::INGENIERIA => 'Revisa e inspecciona que todo esté bien con el equipo antes de salir.',
        self::EMPAQUE => 'Emplaya o empaqueta y confirma con qué piezas sale.',
        self::ENTREGA => 'Lo entrega en persona, en ruta o lo manda por paquetería.',
    ];

    public const PENDIENTE = 'pendiente';
    public const EN_CURSO = 'en_curso';
    public const TERMINADA = 'terminada';

    public const ESTADOS = [
        self::PENDIENTE => 'Pendiente',
        self::EN_CURSO => 'En proceso',
        self::TERMINADA => 'Terminada',
    ];

    /** Horas para corregir una etapa terminada sin NIP. */
    public const HORAS_EDICION = 72;

    public const MODALIDADES = [
        'persona' => 'Entrega en persona',
        'ruta' => 'En ruta de reparto',
        'paqueteria' => 'Por paquetería',
        'recoge' => 'El cliente recoge',
    ];

    protected $fillable = [
        'orden_salida_id', 'etapa', 'estado',
        'asignada_a', 'asignada_por', 'asignada_en',
        'checklist', 'checklist_ia', 'extras', 'datos', 'notas', 'firma_path',
        'iniciada_por', 'iniciada_en', 'terminada_por', 'terminada_en', 'editada_por', 'editada_en',
    ];

    protected function casts(): array
    {
        return [
            'checklist' => 'array',
            'checklist_ia' => 'boolean',
            'extras' => 'array',
            'datos' => 'array',
            'asignada_en' => 'datetime',
            'iniciada_en' => 'datetime',
            'terminada_en' => 'datetime',
            'editada_en' => 'datetime',
        ];
    }

    public function orden(): BelongsTo
    {
        return $this->belongsTo(OrdenSalida::class, 'orden_salida_id');
    }

    public function asignadaA(): BelongsTo
    {
        return $this->belongsTo(User::class, 'asignada_a');
    }

    public function asignadaPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'asignada_por');
    }

    public function iniciadaPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'iniciada_por');
    }

    public function terminadaPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'terminada_por');
    }

    public function editadaPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'editada_por');
    }

    public function nombre(): string
    {
        return self::ETAPAS[$this->etapa] ?? ucfirst($this->etapa);
    }

    public function numero(): int
    {
        return array_search($this->etapa, array_keys(self::ETAPAS), true) + 1;
    }

    public function estadoLabel(): string
    {
        return self::ESTADOS[$this->estado] ?? $this->estado;
    }

    public function terminada(): bool
    {
        return $this->estado === self::TERMINADA;
    }

    /** ¿Ya se puede corregir sin NIP? Las primeras 72 h desde que se terminó. */
    public function enPlazoDeEdicion(): bool
    {
        return $this->terminada_en !== null && $this->terminada_en->gt(now()->subHours(self::HORAS_EDICION));
    }

    public function horasParaEditar(): int
    {
        if (! $this->enPlazoDeEdicion()) {
            return 0;
        }

        return max(1, (int) ceil(now()->diffInMinutes($this->terminada_en->copy()->addHours(self::HORAS_EDICION)) / 60));
    }

    /**
     * Resumen del checklist: cuántos puntos, cuántos bien, cuántos mal y
     * cuántos faltan por contestar.
     *
     * @return array{total: int, si: int, no: int, na: int, faltan: int}
     */
    public function resumenChecklist(): array
    {
        $puntos = collect($this->checklist ?: []);
        $r = $puntos->countBy(fn ($p) => $p['respuesta'] ?? 'falta');

        return [
            'total' => $puntos->count(),
            'si' => (int) ($r['si'] ?? 0),
            'no' => (int) ($r['no'] ?? 0),
            'na' => (int) ($r['na'] ?? 0),
            'faltan' => (int) ($r['falta'] ?? 0),
        ];
    }

    public function firmaUrl(): ?string
    {
        return $this->firma_path ? Storage::disk(config('filesystems.fotos_disk', 'public'))->url($this->firma_path) : null;
    }

    public function firmaDataUri(): ?string
    {
        $disco = Storage::disk(config('filesystems.fotos_disk', 'public'));

        return $this->firma_path && $disco->exists($this->firma_path)
            ? 'data:image/png;base64,'.base64_encode($disco->get($this->firma_path))
            : null;
    }
}
