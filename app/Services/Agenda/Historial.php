<?php

namespace App\Services\Agenda;

use App\Models\AgendaHistorial;
use Illuminate\Database\Eloquent\Model;

/** Escribe la bitácora de cambios importantes (nunca se edita ni se borra). */
class Historial
{
    public static function registrar(Model $item, string $accion, ?int $userId, ?array $antes = null, ?array $despues = null, ?string $motivo = null): void
    {
        AgendaHistorial::create([
            'item_type' => $item->getMorphClass(),
            'item_id' => $item->getKey(),
            'user_id' => $userId,
            'accion' => $accion,
            'antes' => $antes ?: null,
            'despues' => $despues ?: null,
            'motivo' => $motivo ? mb_substr($motivo, 0, 255) : null,
        ]);
    }

    /** Solo los campos que cambiaron: [antes, después]. */
    public static function diferencias(array $antes, array $despues): array
    {
        $a = [];
        $d = [];

        foreach ($despues as $campo => $valor) {
            $previo = $antes[$campo] ?? null;
            if ((string) json_encode($previo) !== (string) json_encode($valor)) {
                $a[$campo] = $previo;
                $d[$campo] = $valor;
            }
        }

        return [$a, $d];
    }
}
