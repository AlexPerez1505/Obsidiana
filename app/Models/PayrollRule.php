<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Una regla de nómina que define RH: "cuando pase esto, bonificar o descontar
 * tanto". Se arma en pantalla con frases, sin tocar código.
 */
class PayrollRule extends Model
{
    /** Lo que se puede medir de la asistencia de una persona. [etiqueta, unidad] */
    public const METRICAS = [
        'retardos' => ['Retardos', 'retardo(s)'],
        'faltas' => ['Faltas', 'falta(s)'],
        'minutos_retardo' => ['Minutos de retardo acumulados', 'min'],
        'dias_trabajados' => ['Días trabajados', 'día(s)'],
        'horas_trabajadas' => ['Horas trabajadas', 'h'],
        'asistencia_pct' => ['% de asistencia', '%'],
        'puntualidad_pct' => ['% de puntualidad', '%'],
        'salidas_sin_checar' => ['Días sin checar la salida', 'día(s)'],
        'vacaciones' => ['Días de vacaciones', 'día(s)'],
        'incapacidades' => ['Días de incapacidad', 'día(s)'],
        'comisiones' => ['Días en comisión', 'día(s)'],
    ];

    public const OPERADORES = ['=' => 'es igual a', '>=' => 'es al menos', '>' => 'es más de', '<=' => 'es máximo', '<' => 'es menos de'];

    public const CALCULOS = [
        'monto' => 'un monto fijo ($)',
        'dias_sueldo' => 'días de sueldo',
        'pct_sueldo' => '% del sueldo de la quincena',
    ];

    protected $fillable = [
        'name', 'description', 'kind', 'conditions', 'repeat_mode', 'repeat_metric', 'repeat_every', 'repeat_max',
        'calc', 'amount', 'timing', 'scope', 'user_ids', 'active', 'sort', 'system_key', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'conditions' => 'array', 'user_ids' => 'array', 'active' => 'boolean',
            'amount' => 'float', 'repeat_every' => 'integer', 'repeat_max' => 'integer',
        ];
    }

    public function esBono(): bool
    {
        return $this->kind === 'bono';
    }

    /** ¿Aplica a esta persona según "a quién"? */
    public function aplicaA(int $userId): bool
    {
        $ids = array_map('intval', $this->user_ids ?? []);

        return match ($this->scope) {
            'incluir' => in_array($userId, $ids, true),
            'excluir' => ! in_array($userId, $ids, true),
            default => true,
        };
    }

    /** ¿Alguna condición mira el mes completo? Entonces solo se puede aplicar al cerrar el mes. */
    public function usaMes(): bool
    {
        return collect($this->conditions ?? [])->contains(fn ($c) => ($c['scope'] ?? 'periodo') === 'mes');
    }

    /** "por cada falta" · "por cada 3 retardos" */
    public function textoPorCada(): string
    {
        $m = self::METRICAS[$this->repeat_metric] ?? [(string) $this->repeat_metric, ''];

        return $this->repeat_every == 1
            ? 'por cada '.str_replace('(s)', '', $m[1])
            : 'por cada '.$this->repeat_every.' '.strtolower($m[0]);
    }

    public static function etiquetaMetrica(string $m): string
    {
        return self::METRICAS[$m][0] ?? $m;
    }

    public function condicionTexto(array $c): string
    {
        $m = self::METRICAS[$c['metric']] ?? [$c['metric'], ''];
        $donde = ($c['scope'] ?? 'periodo') === 'mes' ? 'en el mes' : 'en la quincena';
        $v = rtrim(rtrim(number_format((float) $c['value'], 2, '.', ''), '0'), '.');

        return strtolower($m[0]).' '.$donde.' '.(self::OPERADORES[$c['op']] ?? $c['op']).' '.$v;
    }

    /** La regla en una frase, para mostrarla en la lista y en el desglose. */
    public function resumen(): string
    {
        $cuando = collect($this->conditions ?? [])->map(fn ($c) => $this->condicionTexto($c))->implode(' y ');

        $v = rtrim(rtrim(number_format($this->amount, 2, '.', ''), '0'), '.');
        $que = match ($this->calc) {
            'dias_sueldo' => $v.' '.($this->amount == 1 ? 'día' : 'días').' de sueldo',
            'pct_sueldo' => $v.'% del sueldo',
            default => '$'.number_format($this->amount, 2),
        };
        $verbo = $this->esBono() ? 'Bonificar' : 'Descontar';

        $cuanto = $this->repeat_mode === 'por_cada' && $this->repeat_every
            ? ' '.$this->textoPorCada().($this->repeat_max ? ' (máx. '.$this->repeat_max.' veces)' : '')
            : '';

        $cuando = $cuando ?: 'siempre';
        $momento = $this->timing === 'cierre_mes' ? ' · se aplica al cerrar el mes' : ' · cada quincena';

        $quien = match ($this->scope) {
            'incluir' => ' · solo a personas elegidas',
            'excluir' => ' · a todos menos algunas personas',
            default => '',
        };

        return "Cuando {$cuando}: {$verbo} {$que}{$cuanto}{$momento}{$quien}.";
    }
}
