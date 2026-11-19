<?php

namespace App\Services\Garantias;

use App\Models\Garantia;
use App\Models\GarantiaCobertura;
use App\Models\InventoryMovement;
use App\Models\OrdenSalida;
use App\Models\Paquete;
use App\Models\ProductoSerial;
use App\Models\Venta;
use App\Models\VentaItem;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Convierte cada venta en expedientes de garantía, uno por unidad o
 * componente cubierto.
 *
 * Es idempotente: se puede correr las veces que sea sobre la misma venta.
 * Las unidades se reconocen por su serial (o por producto/equipo y número
 * de unidad cuando no tienen), así que editar la venta conserva los folios
 * de lo que no cambió, crea lo nuevo y anula lo que ya no está.
 *
 * Reglas:
 *  - Una venta cancelada o vendida "sin garantía" (0 meses) no deja garantías vigentes.
 *  - La cobertura arranca el día de la entrega. Mientras no se entregue,
 *    se cuenta provisionalmente desde la fecha de venta.
 *  - Una garantía con fechas corregidas a mano ya no se recalcula sola.
 *  - Lo que tiene reclamaciones nunca se anula en automático.
 */
class GeneradorDeGarantias
{
    /** Ventas pendientes de sincronizar en esta petición (evita repetir). */
    private static array $pendientes = [];

    /**
     * Programa la sincronización para cuando termine la transacción en
     * curso: en ese momento la venta ya tiene sus renglones y seriales.
     * Un error aquí nunca debe tumbar una venta, solo se reporta.
     */
    public static function programar(?int $ventaId): void
    {
        if (! $ventaId || isset(self::$pendientes[$ventaId])) {
            return;
        }

        self::$pendientes[$ventaId] = true;

        DB::afterCommit(function () use ($ventaId) {
            unset(self::$pendientes[$ventaId]);

            try {
                if ($venta = Venta::find($ventaId)) {
                    app(self::class)->sincronizar($venta);
                }
            } catch (\Throwable $e) {
                report($e);
            }
        });
    }

    /** @return array{creadas:int, actualizadas:int, anuladas:int} */
    public function sincronizar(Venta $venta): array
    {
        $venta->loadMissing(['items', 'ordenSalida']);
        $resultado = ['creadas' => 0, 'actualizadas' => 0, 'anuladas' => 0];

        $existentes = Garantia::where('venta_id', $venta->id)->where('origen', 'venta')->get();

        $unidades = ($venta->estado === 'cancelada' || ! $venta->tieneGarantia())
            ? collect()
            : $this->unidades($venta);

        $entrega = $this->fechaEntrega($venta);
        $fechaVenta = $venta->created_at?->copy()->startOfDay() ?? today();
        $usadas = [];

        DB::transaction(function () use ($venta, $unidades, $existentes, $entrega, $fechaVenta, &$usadas, &$resultado) {
            foreach ($unidades as $u) {
                $garantia = $this->buscarExistente($existentes, $u, $usadas);

                if ($garantia) {
                    $usadas[] = $garantia->id;
                    if ($this->actualizar($garantia, $venta, $u, $entrega, $fechaVenta)) {
                        $resultado['actualizadas']++;
                    }

                    continue;
                }

                $nueva = $this->crear($venta, $u, $entrega, $fechaVenta);
                $usadas[] = $nueva->id;
                $resultado['creadas']++;
            }

            // Lo que ya no corresponde a ningún renglón de la venta.
            foreach ($existentes->where('estado', 'activa')->whereNotIn('id', $usadas) as $sobrante) {
                if ($sobrante->reclamaciones()->exists()) {
                    continue;
                }

                $motivo = match (true) {
                    $venta->estado === 'cancelada' => 'La venta '.$venta->folio.' se canceló.',
                    ! $venta->tieneGarantia() => 'La venta '.$venta->folio.' quedó sin garantía.',
                    default => 'El equipo ya no forma parte de la venta '.$venta->folio.'.',
                };

                $sobrante->update([
                    'estado' => 'anulada',
                    'motivo_anulacion' => $motivo,
                    'anulada_en' => now(),
                ]);
                $sobrante->registrar('anulada', 'Anulada automáticamente: '.$motivo);
                $resultado['anuladas']++;
            }
        });

        return $resultado;
    }

    /**
     * La venta se borra: sus garantías se anulan (no se borran). Las que ya
     * tienen reclamaciones se conservan activas para no perder el caso.
     */
    public function ventaEliminada(Venta $venta): void
    {
        Garantia::where('venta_id', $venta->id)->where('estado', 'activa')->get()
            ->each(function (Garantia $g) use ($venta) {
                if ($g->reclamaciones()->exists()) {
                    $g->registrar('nota', 'La venta '.$venta->folio.' se eliminó; la garantía se conserva por tener reclamaciones.');

                    return;
                }

                $g->update(['estado' => 'anulada', 'motivo_anulacion' => 'La venta '.$venta->folio.' se eliminó.', 'anulada_en' => now()]);
                $g->registrar('anulada', 'Anulada automáticamente: la venta '.$venta->folio.' se eliminó.');
            });
    }

    /** Recorre todas las ventas (alta inicial o reparación). */
    public function sincronizarTodas(?callable $avance = null): array
    {
        $total = ['ventas' => 0, 'creadas' => 0, 'actualizadas' => 0, 'anuladas' => 0];

        Venta::query()->orderBy('id')->chunkById(100, function ($ventas) use (&$total, $avance) {
            foreach ($ventas as $venta) {
                $r = $this->sincronizar($venta);
                $total['ventas']++;
                $total['creadas'] += $r['creadas'];
                $total['actualizadas'] += $r['actualizadas'];
                $total['anuladas'] += $r['anuladas'];
                $avance && $avance($venta, $r);
            }
        });

        return $total;
    }

    /**
     * Día en que el equipo llegó al cliente: la firma de la orden de salida,
     * o la salida de inventario marcada como entregada (venta de mostrador).
     */
    public function fechaEntrega(Venta $venta): ?Carbon
    {
        $orden = $venta->ordenSalida;
        if ($orden && $orden->estado === OrdenSalida::ENTREGADA && $orden->entregada_en) {
            return Carbon::parse($orden->entregada_en)->startOfDay();
        }

        $itemIds = $venta->items->pluck('id');
        if ($itemIds->isEmpty()) {
            return null;
        }

        $fecha = InventoryMovement::where('movement_type', InventoryMovement::TYPE_EXIT)
            ->whereIn('metadata->venta_item_id', $itemIds->all())
            ->whereNotNull('entregado_en')
            ->min('entregado_en');

        return $fecha ? Carbon::parse($fecha)->startOfDay() : null;
    }

    /**
     * Las unidades cubiertas de una venta, ya desglosadas.
     *
     * @return Collection<int, array>
     */
    public function unidades(Venta $venta): Collection
    {
        $unidades = collect();

        foreach ($venta->items as $item) {
            $seriales = ProductoSerial::with('producto')->where('venta_item_id', $item->id)->orderBy('id')->get();

            match ($item->tipo_item) {
                'producto' => $unidades->push(...$this->deProducto($item, $seriales)),
                'paquete' => $unidades->push(...$this->dePaquete($item, $seriales)),
                default => $unidades->push(...$this->deEquipo($item)),
            };
        }

        return $unidades;
    }

    private function deProducto(VentaItem $item, Collection $seriales): array
    {
        $precio = $item->cantidad > 0 ? $item->importe() / $item->cantidad : null;
        $base = [
            'venta_item_id' => $item->id,
            'producto_id' => $item->producto_id,
            'descripcion' => $item->nombre,
            'marca' => $item->marca,
            'modelo' => $item->modelo,
            'precio_venta' => $precio,
        ];

        $unidades = $seriales->map(fn ($s) => $base + $this->datosDeSerial($s))->all();

        // Lo que se vendió sin serial registrado (producto no serializado).
        $faltan = max(0, (int) $item->cantidad - $seriales->count());
        $seriesTexto = $this->seriesDeTexto($item->no_series)->diff($seriales->pluck('no_serie'))->values();

        for ($n = 1; $n <= $faltan; $n++) {
            $unidades[] = $base + [
                'unidad' => $n,
                'no_serie' => $seriesTexto[$n - 1] ?? null,
                'condicion' => 'nuevo',
            ];
        }

        return $unidades;
    }

    private function deEquipo(VentaItem $item): array
    {
        $series = $this->seriesDeTexto($item->no_series);
        $precio = $item->cantidad > 0 ? $item->importe() / $item->cantidad : null;
        $unidades = [];

        for ($n = 1; $n <= max(1, (int) $item->cantidad); $n++) {
            $unidades[] = [
                'venta_item_id' => $item->id,
                'equipo_id' => $item->equipo_id,
                'descripcion' => $item->nombre,
                'marca' => $item->marca,
                'modelo' => $item->modelo,
                'unidad' => $n,
                'no_serie' => $series[$n - 1] ?? null,
                'condicion' => 'nuevo',
                'precio_venta' => $precio,
            ];
        }

        return $unidades;
    }

    /**
     * Un paquete (una torre) se desglosa en sus componentes: cada uno con su
     * serie y su propia garantía, todos bajo el mismo grupo.
     */
    private function dePaquete(VentaItem $item, Collection $seriales): array
    {
        $grupo = $item->nombre;
        $paquete = Paquete::with(['productos', 'equipos'])->find($item->paquete_id);
        $veces = max(1, (int) $item->cantidad);
        $unidades = [];

        $comun = [
            'venta_item_id' => $item->id,
            'paquete_id' => $item->paquete_id,
            'grupo' => $grupo,
        ];

        foreach ($paquete?->productos ?? [] as $producto) {
            $necesarias = $veces * max(1, (int) $producto->pivot->cantidad);
            $suyas = $seriales->where('producto_id', $producto->id)->values();
            $base = $comun + [
                'producto_id' => $producto->id,
                'descripcion' => $producto->nombreVisible() ?: $item->nombre,
                'marca' => $producto->marca,
                'modelo' => $producto->modelo,
            ];

            foreach ($suyas as $s) {
                $unidades[] = $base + $this->datosDeSerial($s);
            }

            for ($n = 1; $n <= max(0, $necesarias - $suyas->count()); $n++) {
                $unidades[] = $base + ['unidad' => $n, 'no_serie' => null, 'condicion' => 'nuevo'];
            }
        }

        foreach ($paquete?->equipos ?? [] as $equipo) {
            $necesarias = $veces * max(1, (int) $equipo->pivot->cantidad);
            for ($n = 1; $n <= $necesarias; $n++) {
                $unidades[] = $comun + [
                    'equipo_id' => $equipo->id,
                    'descripcion' => trim(($equipo->subtipo ?: $equipo->tipo) ?: 'Componente'),
                    'marca' => $equipo->marca,
                    'modelo' => $equipo->modelo,
                    'unidad' => $n,
                    'no_serie' => null,
                    'condicion' => 'nuevo',
                ];
            }
        }

        // Paquete sin componentes registrados: se cubre como una sola pieza.
        if (! $unidades) {
            $unidades[] = $comun + [
                'descripcion' => $item->nombre,
                'marca' => $item->marca,
                'modelo' => $item->modelo,
                'unidad' => 1,
                'no_serie' => $this->seriesDeTexto($item->no_series)->first(),
                'condicion' => 'nuevo',
                'precio_venta' => $item->importe(),
            ];
        }

        return $unidades;
    }

    private function datosDeSerial(ProductoSerial $s): array
    {
        return [
            'producto_serial_id' => $s->id,
            'producto_id' => $s->producto_id,
            'no_serie' => $s->no_serie ?: null,
            'codigo_interno' => $s->codigo,
            'condicion' => $s->condicion === 'nuevo' ? 'nuevo' : 'reacondicionado',
            'unidad' => 1,
        ];
    }

    private function seriesDeTexto(?string $texto): Collection
    {
        return collect(preg_split('/[,;\n]+/', (string) $texto))
            ->map(fn ($s) => trim($s))
            ->filter()
            ->values();
    }

    private function buscarExistente(Collection $existentes, array $u, array $usadas): ?Garantia
    {
        $libres = $existentes->whereNotIn('id', $usadas);

        if (! empty($u['producto_serial_id'])) {
            $porSerial = $libres->firstWhere('producto_serial_id', $u['producto_serial_id']);
            if ($porSerial) {
                return $porSerial;
            }
        }

        return $libres->first(fn (Garantia $g) => ! $g->producto_serial_id
            && (int) $g->producto_id === (int) ($u['producto_id'] ?? 0)
            && (int) $g->equipo_id === (int) ($u['equipo_id'] ?? 0)
            && (string) $g->grupo === (string) ($u['grupo'] ?? '')
            && $g->descripcion === $u['descripcion']
            && (int) $g->unidad === (int) ($u['unidad'] ?? 1));
    }

    private function crear(Venta $venta, array $u, ?Carbon $entrega, Carbon $fechaVenta): Garantia
    {
        $cobertura = GarantiaCobertura::paraCondicion($u['condicion'] ?? 'nuevo');
        $meses = (int) $venta->garantia_meses;
        $inicio = $entrega ?? $fechaVenta;

        $garantia = Garantia::create($u + [
            'origen' => 'venta',
            'customer_id' => $venta->customer_id,
            'venta_id' => $venta->id,
            'cobertura_id' => $cobertura?->id,
            'tipo' => $cobertura?->tipo ?? (($u['condicion'] ?? 'nuevo') === 'nuevo' ? 'medibuy' : 'reacondicionado'),
            'meses' => $meses,
            'fecha_venta' => $fechaVenta,
            'fecha_entrega' => $entrega,
            'inicio' => $inicio,
            'vence' => Garantia::calcularVence($inicio, $meses),
            'cubre' => $cobertura?->cubre,
            'exclusiones' => $cobertura?->exclusiones,
            'condiciones' => $cobertura?->condiciones,
            'estado' => 'activa',
        ]);

        $garantia->registrar('creada', 'Garantía generada desde la venta '.$venta->folio
            .' · '.$meses.' meses'.($entrega ? ' desde la entrega' : ' (entrega por confirmar)').'.');

        return $garantia;
    }

    /** Devuelve true si algo cambió. */
    private function actualizar(Garantia $g, Venta $venta, array $u, ?Carbon $entrega, Carbon $fechaVenta): bool
    {
        $cambios = [];

        foreach (['venta_item_id', 'producto_serial_id', 'codigo_interno', 'paquete_id'] as $campo) {
            if (array_key_exists($campo, $u) && $g->{$campo} != $u[$campo]) {
                $g->{$campo} = $u[$campo];
            }
        }

        // La serie se completa si faltaba; si alguien la cambió a mano (una
        // sustitución, por ejemplo) se respeta.
        if (! $g->no_serie && ! empty($u['no_serie'])) {
            $g->no_serie = $u['no_serie'];
            $cambios[] = 'serie '.$u['no_serie'];
        }

        if ($g->customer_id !== $venta->customer_id) {
            $g->customer_id = $venta->customer_id;
            $cambios[] = 'cliente';
        }

        if ($g->estado === 'anulada' && ! $g->anulada_por) {
            // Se había anulado sola (venta editada) y el equipo volvió.
            $g->estado = 'activa';
            $g->motivo_anulacion = null;
            $g->anulada_en = null;
            $cambios[] = 'reactivada';
        }

        if (! $g->fechas_manual) {
            $meses = (int) $venta->garantia_meses;
            $inicio = $entrega ?? $fechaVenta;
            $vence = Garantia::calcularVence($inicio, $meses);

            if ((int) $g->meses !== $meses || ! $g->inicio->equalTo($inicio) || ! $g->vence->equalTo($vence)
                || optional($g->fecha_entrega)->toDateString() !== optional($entrega)->toDateString()) {
                $antes = $g->inicio->format('d/m/Y').' – '.$g->vence->format('d/m/Y');
                $g->meses = $meses;
                $g->fecha_entrega = $entrega;
                $g->inicio = $inicio;
                $g->vence = $vence;
                $cambios[] = 'cobertura '.$antes.' → '.$inicio->format('d/m/Y').' – '.$vence->format('d/m/Y');
            }
        }

        if (! $g->isDirty()) {
            return false;
        }

        $g->save();

        if ($cambios) {
            $g->registrar('actualizada', 'Actualizada desde la venta '.$venta->folio.': '.implode(', ', $cambios).'.');
        }

        return true;
    }
}
