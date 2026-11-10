<?php

namespace App\Services\Promociones;

use App\Models\Customer;
use App\Services\WhatsApp\Telefono;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * A quién le llega una campaña. Primero el segmento (o la lista elegida a
 * mano) y luego, SIEMPRE, las exclusiones: sin consentimiento, dados de
 * baja, sin teléfono válido y números repetidos.
 */
class Audiencia
{
    public const SEGMENTOS = [
        'todos' => 'Todos los clientes con consentimiento',
        'nuevos' => 'Clientes nuevos',
        'frecuentes' => 'Clientes frecuentes',
        'inactivos' => 'Clientes inactivos',
        'productos' => 'Compraron ciertos productos',
        'monto' => 'Por monto de compra',
    ];

    /**
     * @return array{elegibles: Collection<int, Customer>, base: int, excluidos: array<string, int>}
     */
    public function calcular(array $filtros): array
    {
        $base = $this->consulta($filtros)->get(['id', 'nombre', 'apellido', 'telefono', 'promocion_confirmada_en', 'promocion_revocada_en', 'activo']);

        $excluidos = ['sin_consentimiento' => 0, 'baja' => 0, 'sin_telefono' => 0, 'duplicado' => 0];
        $vistos = [];
        $elegibles = collect();

        foreach ($base as $c) {
            if ($c->esPublicoEnGeneral()) {
                continue;
            }
            if ($c->promocion_revocada_en) {
                $excluidos['baja']++;

                continue;
            }
            if (! $c->promocion_confirmada_en) {
                $excluidos['sin_consentimiento']++;

                continue;
            }
            $tel = Telefono::e164($c->telefono);
            if (! $tel) {
                $excluidos['sin_telefono']++;

                continue;
            }
            if (isset($vistos[$tel])) {
                $excluidos['duplicado']++;

                continue;
            }
            $vistos[$tel] = true;
            $elegibles->push($c);
        }

        return ['elegibles' => $elegibles, 'base' => $base->count(), 'excluidos' => $excluidos];
    }

    /** Los clientes del segmento, antes de las exclusiones. */
    public function consulta(array $f): Builder
    {
        $q = Customer::query()->where('activo', true)->orderBy('id');

        if (($f['modo'] ?? 'segmento') === 'manual') {
            return $q->whereIn('id', array_map('intval', (array) ($f['clientes'] ?? [])) ?: [0]);
        }

        $vigentes = fn ($v) => $v->where('estado', '!=', 'cancelada');

        switch ($f['segmento'] ?? 'todos') {
            case 'nuevos':
                $q->where('created_at', '>=', now()->subDays(max(1, (int) ($f['dias'] ?? 30))));
                break;

            case 'frecuentes':
                $q->has('ventas', '>=', max(1, (int) ($f['min_compras'] ?? 3)), 'and', $vigentes);
                break;

            case 'inactivos':
                $limite = now()->subDays(max(1, (int) ($f['dias'] ?? 180)));
                $q->whereHas('ventas', $vigentes)
                    ->whereDoesntHave('ventas', fn ($v) => $vigentes($v)->where('created_at', '>=', $limite));
                break;

            case 'productos':
                $porTipo = ['producto' => [], 'paquete' => []];
                foreach ((array) ($f['productos'] ?? []) as $clave) {
                    [$t, $id] = array_pad(explode('-', (string) $clave, 2), 2, null);
                    if (isset($porTipo[$t])) {
                        $porTipo[$t][] = (int) $id;
                    }
                }
                $q->whereHas('ventas', fn ($v) => $vigentes($v)->whereHas('items', function ($i) use ($porTipo) {
                    $i->where(function ($w) use ($porTipo) {
                        $w->whereIn('producto_id', $porTipo['producto'] ?: [0])
                            ->orWhereIn('paquete_id', $porTipo['paquete'] ?: [0]);
                    });
                }));
                break;

            case 'monto':
                $min = (float) ($f['monto_min'] ?? 0);
                $max = isset($f['monto_max']) && $f['monto_max'] !== '' && $f['monto_max'] !== null ? (float) $f['monto_max'] : null;
                $q->whereIn('id', function ($sub) use ($min, $max) {
                    $sub->from('ventas')->select('customer_id')
                        ->where('estado', '!=', 'cancelada')
                        ->groupBy('customer_id')
                        ->havingRaw('SUM(total) >= ?', [$min]);
                    if ($max !== null) {
                        $sub->havingRaw('SUM(total) <= ?', [$max]);
                    }
                });
                break;
        }

        return $q;
    }

    /** Texto corto del segmento, para listados y para la confirmación de envío. */
    public static function describir(array $f): string
    {
        if (($f['modo'] ?? 'segmento') === 'manual') {
            return count((array) ($f['clientes'] ?? [])).' cliente(s) elegidos a mano';
        }

        return match ($f['segmento'] ?? 'todos') {
            'nuevos' => 'Clientes dados de alta en los últimos '.((int) ($f['dias'] ?? 30)).' días',
            'frecuentes' => 'Clientes con '.((int) ($f['min_compras'] ?? 3)).' compras o más',
            'inactivos' => 'Clientes sin comprar en '.((int) ($f['dias'] ?? 180)).' días',
            'productos' => 'Compraron alguno de '.count((array) ($f['productos'] ?? [])).' producto(s)',
            'monto' => 'Compras acumuladas desde $'.number_format((float) ($f['monto_min'] ?? 0), 0)
                .(filled($f['monto_max'] ?? null) ? ' hasta $'.number_format((float) $f['monto_max'], 0) : ''),
            default => self::SEGMENTOS['todos'],
        };
    }
}
