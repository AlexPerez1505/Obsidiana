<?php

namespace App\Services\Nomina;

use App\Models\PayrollConcept;
use App\Models\PayrollRule;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Lo que RH configura de la nómina: conceptos fijos por persona y las
 * reglas de bonos y descuentos. La web y la API validan con las mismas
 * reglas y guardan igual (una condición del mes completo obliga a aplicar
 * la regla al cierre del mes, venga de donde venga).
 */
class ConfiguracionDeNomina
{
    // ---------------- Conceptos fijos ----------------

    public static function reglasConceptoFijo(): array
    {
        return [
            'kind' => ['required', 'in:bono,descuento'],
            'name' => ['required', 'string', 'max:150'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:1000000'],
            'valid_from' => ['nullable', 'date'],
            'valid_until' => ['nullable', 'date', 'after_or_equal:valid_from'],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }

    public static function mensajesConceptoFijo(): array
    {
        return ['name.required' => 'Ponle un nombre al concepto.', 'amount.required' => 'Escribe el monto.', 'valid_until.after_or_equal' => 'La vigencia termina antes de empezar.'];
    }

    /** @param  array  $data  ya validado con reglasConceptoFijo() */
    public function crearConceptoFijo(User $user, array $data, int $creadoPor): PayrollConcept
    {
        return PayrollConcept::create($data + ['user_id' => $user->id, 'recurrence' => 'cada_periodo', 'created_by' => $creadoPor]);
    }

    // ---------------- Reglas ----------------

    public static function reglasRegla(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:500'],
            'kind' => ['required', 'in:bono,descuento'],
            'conditions' => ['required', 'array', 'min:1', 'max:5'],
            'conditions.*.metric' => ['required', 'in:'.implode(',', array_keys(PayrollRule::METRICAS))],
            'conditions.*.scope' => ['required', 'in:periodo,mes'],
            'conditions.*.op' => ['required', 'in:'.implode(',', array_keys(PayrollRule::OPERADORES))],
            'conditions.*.value' => ['required', 'numeric', 'min:0', 'max:100000'],
            'repeat_mode' => ['required', 'in:una_vez,por_cada'],
            'repeat_metric' => ['required_if:repeat_mode,por_cada', 'nullable', 'in:'.implode(',', array_keys(PayrollRule::METRICAS))],
            'repeat_every' => ['required_if:repeat_mode,por_cada', 'nullable', 'integer', 'min:1', 'max:1000'],
            'repeat_max' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'calc' => ['required', 'in:'.implode(',', array_keys(PayrollRule::CALCULOS))],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:1000000'],
            'timing' => ['required', 'in:periodo,cierre_mes'],
            'scope' => ['required', 'in:todos,incluir,excluir'],
            'user_ids' => ['required_unless:scope,todos', 'nullable', 'array'],
            'user_ids.*' => ['integer', 'exists:users,id'],
            'active' => ['nullable', 'boolean'],
        ];
    }

    public static function mensajesRegla(): array
    {
        return [
            'name.required' => 'Ponle un nombre a la regla.',
            'conditions.required' => 'Agrega al menos una condición.',
            'conditions.*.value.required' => 'Falta el valor de una condición.',
            'repeat_every.required_if' => 'Indica cada cuántas veces se repite.',
            'repeat_metric.required_if' => 'Elige qué se cuenta para repetir la regla.',
            'amount.required' => 'Escribe el monto o los días.',
            'user_ids.required_unless' => 'Marca a qué personas aplica.',
        ];
    }

    /**
     * Crea la regla o actualiza la que se pasa.
     *
     * @param  array  $data  ya validado con reglasRegla()
     * @return array{0: PayrollRule, 1: bool, 2: bool}  [regla, ¿es nueva?, ¿se movió al cierre del mes?]
     */
    public function guardarRegla(array $data, ?PayrollRule $regla, int $creadoPor): array
    {
        // Una condición que mira el mes completo solo se puede decidir cuando el mes termina
        $conds = array_values(array_map(fn ($c) => ['metric' => $c['metric'], 'scope' => $c['scope'], 'op' => $c['op'], 'value' => (float) $c['value']], $data['conditions']));
        $usaMes = collect($conds)->contains(fn ($c) => $c['scope'] === 'mes');
        $timing = $usaMes ? 'cierre_mes' : $data['timing'];

        if ($data['calc'] === 'pct_sueldo' && $data['amount'] > 100) {
            throw ValidationException::withMessages(['amount' => 'Un porcentaje no puede pasar de 100.']);
        }

        $campos = [
            'name' => $data['name'], 'description' => $data['description'] ?? null, 'kind' => $data['kind'],
            'conditions' => $conds,
            'repeat_mode' => $data['repeat_mode'],
            'repeat_metric' => $data['repeat_mode'] === 'por_cada' ? $data['repeat_metric'] : null,
            'repeat_every' => $data['repeat_mode'] === 'por_cada' ? $data['repeat_every'] : null,
            'repeat_max' => $data['repeat_mode'] === 'por_cada' ? ($data['repeat_max'] ?? null) : null,
            'calc' => $data['calc'], 'amount' => $data['amount'], 'timing' => $timing,
            'scope' => $data['scope'], 'user_ids' => $data['scope'] === 'todos' ? null : array_map('intval', $data['user_ids'] ?? []),
            'active' => (bool) ($data['active'] ?? false),
        ];

        $nueva = ! ($regla && $regla->exists);

        if ($nueva) {
            $regla = PayrollRule::create($campos + ['sort' => (int) PayrollRule::max('sort') + 1, 'created_by' => $creadoPor]);
        } else {
            $regla->update($campos);
        }

        return [$regla, $nueva, $usaMes && $data['timing'] !== 'cierre_mes'];
    }
}
