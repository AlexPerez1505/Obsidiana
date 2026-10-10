<?php

namespace App\Services\Administracion;

use App\Models\Trip;
use App\Models\TripExpense;
use App\Models\Viatic;
use App\Models\ViaticExpense;
use Illuminate\Database\Eloquent\Model;

/**
 * Los gastos sueltos (caseta, gasolina, comida, hospedaje, otro) de un
 * viático o de un viaje. Se capturan igual en los dos, así que viven en
 * un solo lugar; lo usan la web y la API.
 */
class GastosDeViaje
{
    /** Tipo => cómo se llama cuando se crea a partir de los totales del viático. */
    public const TIPOS = [
        'toll' => 'Caseta',
        'fuel' => 'Gasolina',
        'meal' => 'Viático',
        'lodging' => 'Hospedaje',
        'other' => 'Adicional',
    ];

    public const ICONOS = [
        'toll' => 'toll',
        'fuel' => 'fuel',
        'meal' => 'receipt',
        'lodging' => 'lodging',
        'other' => 'receipt',
    ];

    public const REGLAS = [
        'type' => ['required', 'string', 'in:toll,fuel,meal,lodging,other'],
        'label' => ['nullable', 'string', 'max:255'],
        'amount' => ['required', 'numeric', 'min:0'],
    ];

    public function agregar(Viatic|Trip $padre, array $data): ViaticExpense|TripExpense
    {
        return $padre->expenses()->create(self::atributos($data));
    }

    public function actualizar(ViaticExpense|TripExpense $gasto, array $data): void
    {
        $gasto->update(self::atributos($data));
    }

    /** ¿Este gasto es de ese viático o viaje? (si no, es 404). */
    public static function pertenece(Viatic|Trip $padre, ViaticExpense|TripExpense $gasto): bool
    {
        $llave = $padre instanceof Viatic ? 'viatic_id' : 'trip_id';

        return (int) $gasto->{$llave} === (int) $padre->id;
    }

    /** Lo que espera la pantalla de captura de gastos (JSON de la web). */
    public static function paraPantalla(Model $gasto): array
    {
        return [
            'id' => $gasto->id,
            'type' => $gasto->type,
            'label' => $gasto->label,
            'amount' => (float) $gasto->amount,
            'created_at' => $gasto->created_at->toISOString(),
            'time_label' => $gasto->created_at->isToday()
                ? 'Hoy, '.$gasto->created_at->format('g:i A')
                : $gasto->created_at->format('d/m/Y, g:i A'),
        ];
    }

    private static function atributos(array $data): array
    {
        return [
            'type' => $data['type'],
            'label' => ($data['label'] ?? null) ?: 'Sin descripción',
            'amount' => $data['amount'],
            'icon' => self::ICONOS[$data['type']] ?? 'receipt',
        ];
    }
}
