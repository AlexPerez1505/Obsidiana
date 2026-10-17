<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

/**
 * La orden de salida de una venta: qué hay que preparar, qué se emplaya,
 * y la firma de quien lo entrega y quien se lo lleva.
 */
class OrdenSalida extends Model
{
    protected $table = 'ordenes_salida';

    public const PENDIENTE = 'pendiente';
    public const EN_PREPARACION = 'en_preparacion';
    public const LISTA = 'lista';
    public const ENTREGADA = 'entregada';
    public const CANCELADA = 'cancelada';

    public const ESTADOS = [
        self::PENDIENTE => 'Pendiente',
        self::EN_PREPARACION => 'En preparación',
        self::LISTA => 'Lista para salir',
        self::ENTREGADA => 'Entregada',
        self::CANCELADA => 'Cancelada',
    ];

    protected $fillable = [
        'folio', 'venta_id', 'estado', 'notas',
        'preparada_por', 'preparada_en',
        'entregada_por', 'entregada_en', 'recibe_nombre',
        'firma_entrega_path', 'firma_recibe_path',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'preparada_en' => 'datetime',
            'entregada_en' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        /*
        | La llave foránea borra los registros de evidencia solos, pero no
        | sus archivos. Se borran uno por uno para que cada evidencia se
        | lleve el suyo (ver OrdenSalidaEvidencia::booted).
        */
        static::deleting(function (OrdenSalida $orden) {
            $orden->evidencias()->get()->each->delete();
        });
    }

    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrdenSalidaItem::class)->orderBy('orden');
    }

    public function preparadaPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'preparada_por');
    }

    public function entregadaPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'entregada_por');
    }

    /** Quien registró la venta que generó la orden. */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** Fotos y videos del equipo preparado, emplayado o entregado. */
    public function evidencias(): HasMany
    {
        return $this->hasMany(OrdenSalidaEvidencia::class)->orderBy('id');
    }

    /**
     * Cuántas fotos y videos tiene, con lo ya cargado si se cargó.
     *
     * @return array{fotos: int, videos: int}
     */
    public function conteoEvidencias(): array
    {
        $porTipo = $this->relationLoaded('evidencias')
            ? $this->evidencias->countBy('tipo')
            : $this->evidencias()->reorder()->selectRaw('tipo, COUNT(*) as n')->groupBy('tipo')->pluck('n', 'tipo');

        return [
            'fotos' => (int) ($porTipo[OrdenSalidaEvidencia::FOTO] ?? 0),
            'videos' => (int) ($porTipo[OrdenSalidaEvidencia::VIDEO] ?? 0),
        ];
    }

    /** "3 fotos, 1 video", para la hoja impresa y la pantalla. */
    public function resumenEvidencias(): string
    {
        ['fotos' => $fotos, 'videos' => $videos] = $this->conteoEvidencias();

        return $fotos.($fotos === 1 ? ' foto' : ' fotos').', '.$videos.($videos === 1 ? ' video' : ' videos');
    }

    public static function siguienteFolio(): string
    {
        $anio = now()->year;
        $ultimo = static::where('folio', 'like', "OS-{$anio}-%")->count();

        return sprintf('OS-%d-%04d', $anio, $ultimo + 1);
    }

    public function estadoLabel(): string
    {
        return self::ESTADOS[$this->estado] ?? ucfirst($this->estado);
    }

    /** Color del chip por estado, con las clases de badge del sistema. */
    public function estadoTono(): string
    {
        return match ($this->estado) {
            self::LISTA => 'badge--info',
            self::ENTREGADA => 'badge--ok',
            self::CANCELADA => 'badge--danger',
            default => '',
        };
    }

    /** ¿Ya salió o se canceló? Entonces ya no se toca. */
    public function cerrada(): bool
    {
        return in_array($this->estado, [self::ENTREGADA, self::CANCELADA], true);
    }

    /** Todo preparado y, lo que se emplaya, emplayado. */
    public function todoListo(): bool
    {
        $items = $this->items;

        return $items->isNotEmpty() && $items->every(fn (OrdenSalidaItem $i) => $i->completo());
    }

    /**
     * El checklist ya está completo pero nadie ha cerrado la preparación.
     *
     * Terminar de marcar y entregar son dos actos distintos: quien prepara
     * deja la orden lista y se va, y la firma la hace quien entregue, que
     * puede ser otra persona y otro día. Por eso hace falta el paso de
     * "dejarla preparada" en medio.
     */
    public function puedeConfirmarPreparacion(): bool
    {
        return ! $this->cerrada() && ! $this->preparada_en && $this->todoListo();
    }

    /** Ya se cerró la preparación: solo falta que alguien firme la salida. */
    public function listaParaFirmar(): bool
    {
        return $this->estado === self::LISTA && $this->preparada_en !== null;
    }

    /** Cuántos pasos van hechos de cuántos, para la barra de avance. */
    public function avance(): array
    {
        $total = 0;
        $hechos = 0;

        foreach ($this->items as $i) {
            $total++;
            $hechos += $i->preparado ? 1 : 0;

            if ($i->requiere_emplayado) {
                $total++;
                $hechos += $i->emplayado ? 1 : 0;
            }
        }

        return ['hechos' => $hechos, 'total' => $total, 'pct' => $total > 0 ? (int) round($hechos / $total * 100) : 0];
    }

    /**
     * Recalcula el estado a partir de los renglones.
     *
     * "Lista para salir" no se pone sola por terminar el checklist: hace
     * falta que alguien cierre la preparación (ver confirmarPreparacion).
     * Mientras eso no pase, la orden sigue en preparación aunque todo esté
     * marcado. No baja de entregada ni de cancelada.
     */
    public function actualizarEstado(): void
    {
        if ($this->cerrada()) {
            return;
        }

        $this->load('items');

        // Si se desmarcó algo, la preparación cerrada deja de valer: la
        // orden regresa a la fila de trabajo.
        if (! $this->todoListo() && $this->preparada_en) {
            $this->preparada_en = null;
            $this->preparada_por = null;
        }

        $this->estado = match (true) {
            $this->todoListo() && $this->preparada_en !== null => self::LISTA,
            $this->items->contains(fn ($i) => $i->preparado || $i->emplayado) => self::EN_PREPARACION,
            default => self::PENDIENTE,
        };

        $this->save();
    }

    /**
     * Cierra la preparación: la orden queda lista para que alguien firme su
     * salida, y aparece en la lista de "Listas para salir".
     */
    public function confirmarPreparacion(?int $usuarioId = null): void
    {
        $this->preparada_en = now();
        $this->preparada_por = $usuarioId ?? auth()->id();
        $this->save();

        $this->actualizarEstado();
    }

    /**
     * La orden sale en una ruta de reparto: la entrega del chofer, con la
     * firma y el nombre de quien recibió, cierra la orden y registra la
     * fecha real de salida en el inventario. Solo si ya estaba lista para
     * firmar; si no, no se toca y la ruta lo avisa en su bitácora.
     */
    public function entregarPorRuta(int $choferId, ?string $recibeNombre, ?string $firmaPath, \Illuminate\Support\Carbon $cuando): bool
    {
        if (! $this->listaParaFirmar()) {
            return false;
        }

        $this->update([
            'estado' => self::ENTREGADA,
            'entregada_por' => $choferId,
            'entregada_en' => $cuando,
            'recibe_nombre' => $recibeNombre ?: 'Recibido en ruta',
            'firma_recibe_path' => $firmaPath,
        ]);

        $itemIds = $this->venta?->items()->pluck('id') ?? collect();

        if ($itemIds->isNotEmpty()) {
            InventoryMovement::where('movement_type', InventoryMovement::TYPE_EXIT)
                ->whereIn('metadata->venta_item_id', $itemIds->all())
                ->whereNull('entregado_en')
                ->update(['entregado_en' => $cuando]);
        }

        return true;
    }

    public function firmaEntregaUrl(): ?string
    {
        return $this->urlDe($this->firma_entrega_path);
    }

    public function firmaRecibeUrl(): ?string
    {
        return $this->urlDe($this->firma_recibe_path);
    }

    private function urlDe(?string $path): ?string
    {
        return $path ? Storage::disk(config('filesystems.fotos_disk', 'public'))->url($path) : null;
    }
}
