<?php

namespace App\Services;

use App\Models\PiezaProceso;
use App\Models\ProductoSerial;
use App\Support\ChecklistProceso;
use Illuminate\Support\Facades\DB;

/**
 * Arma y hace avanzar la ruta de procesos de una pieza.
 *
 * El problema que resuelve: no todas las piezas pasan por lo mismo. Un
 * carro de curaciones puede necesitar solo hojalatería; una torre, solo
 * mantenimiento; un equipo golpeado, los dos; y uno que llegó bien,
 * ninguno. Codificar una secuencia fija obligaba a inventar pasos que
 * nadie iba a hacer.
 *
 * La solución es no tener secuencia. Cada pieza trae su lista de pasos y
 * de ahí sale todo:
 *
 *   - El estado de la pieza es "el primer paso sin resolver".
 *   - Sin pasos sin resolver, la pieza está disponible.
 *
 * Así, quitar un paso adelanta la pieza sola, y agregar uno la regresa.
 * No hay que acordarse de mover el estado a mano en ningún lado.
 */
class RutaDeProcesos
{
    /**
     * Define la ruta de una pieza: deja exactamente estos procesos.
     *
     * Los pasos que ya se hicieron no se tocan aunque no vengan en la
     * lista: son historia, no plan. Los pendientes que sobran se quitan.
     *
     * @param  array<int, string>  $procesos
     * @param  array<string, string>  $motivos  proceso => por qué
     */
    public function definir(ProductoSerial $pieza, array $procesos, array $motivos = []): void
    {
        $procesos = collect($procesos)
            ->filter(fn ($p) => isset(PiezaProceso::PROCESOS[$p]))
            ->unique()
            ->sortBy(fn ($p) => PiezaProceso::ordenDe($p))
            ->values();

        DB::transaction(function () use ($pieza, $procesos, $motivos) {
            $existentes = $pieza->procesos()->get()->keyBy('proceso');

            // Fuera los pendientes que ya no aplican. Lo hecho se conserva.
            $existentes
                ->reject(fn (PiezaProceso $p) => $p->resuelto() || $procesos->contains($p->proceso))
                ->each->delete();

            foreach ($procesos as $i => $proceso) {
                $paso = $existentes->get($proceso);

                if ($paso) {
                    // Ya estaba: solo se reacomoda en la fila.
                    $paso->update(['orden' => $i]);

                    continue;
                }

                $pieza->procesos()->create([
                    'proceso' => $proceso,
                    'orden' => $i,
                    'estado' => 'pendiente',
                    'motivo' => $motivos[$proceso] ?? null,
                ]);
            }

            $this->sincronizarEstado($pieza);
        });
    }

    /** Agrega un paso a la ruta sin tocar los demás. */
    public function agregar(ProductoSerial $pieza, string $proceso, ?string $motivo = null): void
    {
        if (! isset(PiezaProceso::PROCESOS[$proceso])) {
            return;
        }

        DB::transaction(function () use ($pieza, $proceso, $motivo) {
            $pieza->procesos()->firstOrCreate(
                ['proceso' => $proceso],
                [
                    'orden' => PiezaProceso::ordenDe($proceso),
                    'estado' => 'pendiente',
                    'motivo' => $motivo,
                ]
            );

            $this->sincronizarEstado($pieza);
        });
    }

    /** Marca un paso como empezado. */
    public function iniciar(PiezaProceso $paso, ?int $responsableId = null): void
    {
        $paso->update([
            'estado' => 'en_curso',
            'iniciado_en' => $paso->iniciado_en ?? now(),
            'responsable_id' => $responsableId ?? $paso->responsable_id ?? auth()->id(),
        ]);

        $this->sincronizarEstado($paso->pieza);
    }

    /**
     * Cierra un paso como TERMINADO.
     *
     * Es el único camino hacia stock, y por eso exige la prueba: checklist
     * de salida completo, todo en "sí", y al menos una foto. Si algo falta,
     * lanza y el paso se queda donde está. Un equipo que no funciona no
     * pasa a stock por descuido de nadie.
     *
     * @param  array<string, array>  $checklist
     * @param  array<int, string>  $evidencias  rutas ya guardadas
     *
     * @throws \RuntimeException
     */
    public function terminar(
        PiezaProceso $paso,
        array $checklist,
        array $evidencias,
        ?string $trabajoRealizado = null
    ): void {
        $razones = ChecklistProceso::razonesParaNoCerrar($paso, $checklist, count($evidencias));

        if ($razones) {
            throw new \RuntimeException(implode(' ', $razones));
        }

        DB::transaction(function () use ($paso, $checklist, $evidencias, $trabajoRealizado) {
            $paso->update([
                'estado' => 'terminado',
                'terminado_en' => now(),
                'responsable_id' => $paso->responsable_id ?? auth()->id(),
                'cerrado_por' => auth()->id(),
                'checklist_salida' => $checklist,
                'evidencias' => array_values(array_merge($paso->evidencias ?? [], $evidencias)),
                'trabajo_realizado' => $trabajoRealizado ?: $paso->trabajo_realizado,
            ]);

            $this->sincronizarEstado($paso->pieza);
        });
    }

    /**
     * Descarta un paso: al verlo de cerca no hacía falta.
     *
     * No pide checklist ni fotos porque no se hizo nada que demostrar, pero
     * sí exige el motivo por escrito: es una decisión, y tiene que quedar
     * quién la tomó y por qué.
     */
    public function omitir(PiezaProceso $paso, string $motivo): void
    {
        DB::transaction(function () use ($paso, $motivo) {
            $paso->update([
                'estado' => 'omitido',
                'terminado_en' => now(),
                'cerrado_por' => auth()->id(),
                'notas' => $motivo,
            ]);

            $this->sincronizarEstado($paso->pieza);
        });
    }

    /**
     * Pone el estado de la pieza donde le toca según su ruta.
     *
     * Es el único lugar donde se decide el estado, para que no haya dos
     * verdades. Una pieza vendida o dada de baja no se mueve: eso ya no lo
     * manda la ruta.
     */
    public function sincronizarEstado(ProductoSerial $pieza): void
    {
        if ($pieza->vendido || $pieza->estado === 'baja') {
            return;
        }

        $siguiente = $pieza->procesos()
            ->whereIn('estado', ['pendiente', 'en_curso'])
            ->orderBy('orden')
            ->orderBy('id')
            ->first();

        $nuevo = $siguiente?->proceso ?? 'disponible';

        if ($pieza->estado !== $nuevo) {
            $pieza->forceFill(['estado' => $nuevo])->save();
        }

        // El stock del producto solo cuenta lo que de verdad se puede
        // vender, así que cambiar de proceso lo mueve.
        $pieza->producto?->recalcularStock();
    }
}
