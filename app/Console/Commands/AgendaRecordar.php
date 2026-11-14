<?php

namespace App\Console\Commands;

use App\Services\Agenda\Recordatorios;
use Illuminate\Console\Command;

/** Manda los recordatorios de la agenda que ya tocan (corre cada minuto). */
class AgendaRecordar extends Command
{
    protected $signature = 'agenda:recordar';

    protected $description = 'Envía los recordatorios y avisos de vencidos de la agenda';

    public function handle(Recordatorios $recordatorios): int
    {
        $n = $recordatorios->despachar();
        $this->info("Recordatorios enviados: {$n}");

        return self::SUCCESS;
    }
}
