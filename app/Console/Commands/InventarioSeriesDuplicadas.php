<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Lista los números de serie repetidos del inventario (sin cambiar nada) y,
 * cuando ya no hay, crea el índice único que impide que vuelva a pasar.
 */
class InventarioSeriesDuplicadas extends Command
{
    protected $signature = 'inventario:series-duplicadas {--indice : Crea el índice único si ya no hay repetidas}';

    protected $description = 'Lista los números de serie repetidos y crea el índice único cuando ya no hay';

    private const INDICE = 'producto_seriales_no_serie_unico_unique';

    public function handle(): int
    {
        $grupos = DB::table('producto_seriales')->whereNotNull('no_serie_unico')
            ->select('no_serie_unico', DB::raw('COUNT(*) n'))->groupBy('no_serie_unico')->havingRaw('COUNT(*) > 1')->get();

        if ($grupos->isNotEmpty()) {
            $this->warn('Números de serie repetidos: '.$grupos->count());
            $filas = DB::table('producto_seriales as s')->join('productos as p', 'p.id', '=', 's.producto_id')
                ->whereIn('s.no_serie_unico', $grupos->pluck('no_serie_unico'))
                ->orderBy('s.no_serie_unico')->orderBy('s.id')
                ->get(['s.no_serie', 's.id', 's.codigo', 's.vendido', 'p.id as producto', 'p.subtipo', 'p.marca', 'p.modelo']);
            $this->table(['Serie', 'Pieza', 'Etiqueta', 'Vendida', 'Producto', 'Equipo'], $filas->map(fn ($f) => [
                $f->no_serie, $f->id, $f->codigo, $f->vendido ? 'sí' : 'no', $f->producto, trim($f->subtipo.' '.$f->marca.' '.$f->modelo),
            ])->all());
            $this->line('Corrige la serie de las piezas sobrantes desde Inventario y vuelve a correr este comando con --indice.');

            return self::FAILURE;
        }

        $this->info('No hay números de serie repetidos.');

        $existe = collect(DB::select('SHOW INDEX FROM producto_seriales WHERE Key_name = ?', [self::INDICE]))->isNotEmpty();
        if ($existe) {
            $this->info('El índice único ya está activo.');
        } elseif ($this->option('indice')) {
            Schema::table('producto_seriales', fn ($t) => $t->unique('no_serie_unico', self::INDICE));
            $this->info('Índice único creado: ya no se pueden repetir series.');
        } else {
            $this->line('Falta el índice único: corre este comando con --indice para crearlo.');
        }

        return self::SUCCESS;
    }
}
