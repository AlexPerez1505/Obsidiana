<?php

namespace App\Services\Ventas;

use App\Models\Cobro;
use App\Models\ComisionPago;
use App\Models\Equipo;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Control de comisiones.
 *
 * Responde, mes por mes: quién vendió más, cuánto se le cobró a sus
 * clientes, cuánto le toca de comisión según su porcentaje, y cuánto de
 * eso ya se le pagó.
 *
 * La comisión se puede calcular sobre lo vendido o sobre lo cobrado en el
 * mes; lo normal es sobre lo cobrado (no se paga comisión de dinero que
 * todavía no entra), pero se deja elegir.
 *
 * Vivía en ComisionController; lo usan la pantalla y la API.
 */
class Comisiones
{
    public const BASES = [
        'vendido' => 'Sobre lo vendido',
        'cobrado' => 'Sobre lo cobrado',
    ];

    public const PATRON_PERIODO = '/^\d{4}-(0[1-9]|1[0-2])$/';

    public const REGLAS_PORCENTAJE = [
        'porcentaje' => ['nullable', 'numeric', 'min:0', 'max:100'],
    ];

    public const MENSAJES_PAGO = [
        'monto.min' => 'El monto del pago debe ser mayor a cero.',
    ];

    public static function reglasPago(): array
    {
        return [
            'user_id' => ['required', 'exists:users,id'],
            'periodo' => ['required', 'regex:'.self::PATRON_PERIODO],
            'monto' => ['required', 'numeric', 'min:0.01'],
            'fecha' => ['required', 'date'],
            'base' => ['required', Rule::in(array_keys(self::BASES))],
            'nota' => ['nullable', 'string', 'max:255'],
        ];
    }

    /** "2026-09" válido, o el mes en curso si vino algo raro. */
    public static function periodo(?string $valor): string
    {
        return $valor && preg_match(self::PATRON_PERIODO, $valor) ? $valor : now()->format('Y-m');
    }

    /** La base pedida, o "vendido" (la regla: la comisión es sobre lo que se vende). */
    public static function base(?string $valor): string
    {
        return array_key_exists((string) $valor, self::BASES) ? $valor : 'vendido';
    }

    /** Primer y último momento del mes "2026-09". */
    public static function limites(string $periodo): array
    {
        $inicio = Carbon::createFromFormat('Y-m', $periodo)->startOfMonth();

        return [$inicio, (clone $inicio)->endOfMonth()];
    }

    /**
     * Una fila por asesor con todo lo del mes, con las reglas de
     * config/comisiones.php:
     *
     *   • Venta normal → al vendedor: endoscopia % de lo de endoscopia y
     *     general % de lo demás.
     *   • Venta de congreso (la venta trae congreso o su cliente se registró
     *     en uno) → congreso % del total, en partes iguales entre los
     *     usuarios asignados a ese congreso. Si el congreso no tiene a nadie
     *     asignado, se le queda al vendedor (y se avisa en el desglose).
     *
     * Siempre sobre el importe sin IVA (después de descuentos y cupón). Con
     * base "cobrado" se toma la parte proporcional a lo que entró en el mes.
     */
    public function filasDelPeriodo(Carbon $inicio, Carbon $fin, string $periodo, string $base): Collection
    {
        $tasas = config('comisiones');

        // Lo vendido en el mes, sin las canceladas.
        $vendido = Venta::where('estado', '!=', 'cancelada')
            ->whereBetween('created_at', [$inicio, $fin])
            ->selectRaw('seller_id, COUNT(*) as ventas, SUM(total) as monto')
            ->groupBy('seller_id')
            ->get()
            ->keyBy('seller_id');

        // Lo que entró en el mes por venta (sin importar cuándo se vendió).
        $cobradoPorVenta = Cobro::join('ventas', 'cobros.venta_id', '=', 'ventas.id')
            ->where('ventas.estado', '!=', 'cancelada')
            ->whereBetween('cobros.fecha', [$inicio->toDateString(), $fin->toDateString()])
            ->selectRaw('cobros.venta_id, SUM(cobros.monto) as monto')
            ->groupBy('cobros.venta_id')
            ->pluck('monto', 'venta_id');

        $ventas = Venta::with(['items.producto', 'items.paquete.productos', 'items.paquete.equipos', 'customer'])
            ->where('estado', '!=', 'cancelada')
            ->when($base === 'vendido',
                fn ($q) => $q->whereBetween('created_at', [$inicio, $fin]),
                fn ($q) => $q->whereIn('id', $cobradoPorVenta->keys()->all() ?: [0]))
            ->get();

        $cobradoPorAsesor = [];
        $acum = [];
        $vacio = ['endoscopia' => 0.0, 'otros' => 0.0, 'congreso' => 0.0, 'ventas_congreso' => 0, 'avisos' => []];
        $asistentesDe = [];

        foreach ($ventas as $v) {
            $cobradoPorAsesor[$v->seller_id] = ($cobradoPorAsesor[$v->seller_id] ?? 0) + (float) ($cobradoPorVenta[$v->id] ?? 0);

            $fraccion = $base === 'vendido' ? 1.0
                : ((float) $v->total > 0 ? min(1.0, (float) ($cobradoPorVenta[$v->id] ?? 0) / (float) $v->total) : 0.0);
            if ($fraccion <= 0) {
                continue;
            }

            $d = $this->desgloseVenta($v);
            $congresoId = $v->congreso_id ?: $v->customer?->congreso_id;

            if ($congresoId) {
                $asistentesDe[$congresoId] ??= DB::table('evento_congreso_usuario')->where('congress_event_id', $congresoId)->pluck('user_id')->all();
                $asistentes = $asistentesDe[$congresoId];
                $bolsa = ($d['endoscopia'] + $d['otros']) * $fraccion * $tasas['congreso'] / 100;

                if (empty($asistentes)) {
                    $acum[$v->seller_id] ??= $vacio;
                    $acum[$v->seller_id]['congreso'] += $bolsa;
                    $acum[$v->seller_id]['ventas_congreso']++;
                    $acum[$v->seller_id]['avisos'][] = "{$v->folio}: el congreso no tiene usuarios asignados; el {$tasas['congreso']} % se queda al vendedor.";

                    continue;
                }

                foreach ($asistentes as $uid) {
                    $acum[$uid] ??= $vacio;
                    $acum[$uid]['congreso'] += $bolsa / count($asistentes);
                    $acum[$uid]['ventas_congreso']++;
                }

                continue;
            }

            $acum[$v->seller_id] ??= $vacio;
            $acum[$v->seller_id]['endoscopia'] += $d['endoscopia'] * $fraccion;
            $acum[$v->seller_id]['otros'] += $d['otros'] * $fraccion;
        }

        // Lo cobrado por asesor (para la tabla), aunque la base sea vendido.
        $cobrado = Cobro::join('ventas', 'cobros.venta_id', '=', 'ventas.id')
            ->where('ventas.estado', '!=', 'cancelada')
            ->whereBetween('cobros.fecha', [$inicio->toDateString(), $fin->toDateString()])
            ->selectRaw('ventas.seller_id, SUM(cobros.monto) as monto')
            ->groupBy('ventas.seller_id')
            ->pluck('monto', 'seller_id');

        $pagado = ComisionPago::where('periodo', $periodo)
            ->selectRaw('user_id, SUM(monto) as monto')
            ->groupBy('user_id')
            ->pluck('monto', 'user_id');

        $ids = $vendido->keys()->merge($cobrado->keys())->merge($pagado->keys())->merge(array_keys($acum))
            ->filter()->unique();

        $usuarios = User::whereIn('id', $ids)->get()->keyBy('id');

        return $usuarios->map(function (User $u) use ($vendido, $cobrado, $pagado, $acum, $tasas, $vacio) {
            $a = $acum[$u->id] ?? $vacio;
            $cEndo = round($a['endoscopia'] * $tasas['endoscopia'] / 100, 2);
            $cOtros = round($a['otros'] * $tasas['general'] / 100, 2);
            $cCongreso = round($a['congreso'], 2);
            $comision = round($cEndo + $cOtros + $cCongreso, 2);
            $yaPagado = (float) ($pagado[$u->id] ?? 0);

            return [
                'asesor' => $u,
                'ventas' => (int) ($vendido[$u->id]->ventas ?? 0),
                'vendido' => (float) ($vendido[$u->id]->monto ?? 0),
                'cobrado' => (float) ($cobrado[$u->id] ?? 0),
                // Ya no hay porcentaje por asesor: rigen las reglas de config/comisiones.php.
                'porcentaje' => null,
                'base_monto' => round($a['endoscopia'] + $a['otros'], 2),
                'base_endoscopia' => round($a['endoscopia'], 2),
                'base_otros' => round($a['otros'], 2),
                'comision_endoscopia' => $cEndo,
                'comision_otros' => $cOtros,
                'comision_congreso' => $cCongreso,
                'ventas_congreso' => $a['ventas_congreso'],
                'avisos' => $a['avisos'],
                'comision' => $comision,
                'pagado' => $yaPagado,
                'pendiente' => round($comision - $yaPagado, 2),
            ];
        })
            ->sortByDesc(fn ($f) => [$f['comision'], $f['vendido']])
            ->values();
    }

    /**
     * Lo comisionable de una venta, sin IVA y ya con descuentos y cupón,
     * separado en endoscopia y lo demás. Los regalos no cuentan.
     *
     * @return array{endoscopia: float, otros: float}
     */
    public function desgloseVenta(Venta $v): array
    {
        $palabra = mb_strtoupper((string) config('comisiones.palabra_endoscopia', 'ENDOSCOP'));
        $subtotal = (float) $v->subtotal;
        $factor = $subtotal > 0
            ? max(0.0, $subtotal - (float) $v->descuento_monto - (float) ($v->cupon_descuento ?? 0)) / $subtotal
            : 0.0;

        $r = ['endoscopia' => 0.0, 'otros' => 0.0];
        foreach ($v->items as $it) {
            if ($it->es_regalo) {
                continue;
            }
            $importe = ((float) $it->precio_unitario + (float) $it->sobreprecio) * (int) $it->cantidad * $factor;
            $tipo = match ($it->tipo_item) {
                'producto' => $it->producto?->tipo_equipo,
                'paquete' => $it->paquete?->tipoPrincipal(),
                'equipo' => Equipo::whereKey($it->equipo_id)->value('tipo'),
                default => null,
            } ?? $it->nombre;

            $esEndoscopia = $palabra !== '' && str_contains(mb_strtoupper((string) $tipo), $palabra);
            $r[$esEndoscopia ? 'endoscopia' : 'otros'] += $importe;
        }

        return $r;
    }

    /** Los totales del mes (la fila de abajo de la tabla). */
    public function resumen(Collection $filas): array
    {
        return [
            'vendido' => (float) $filas->sum('vendido'),
            'cobrado' => (float) $filas->sum('cobrado'),
            'comision' => (float) $filas->sum('comision'),
            'comision_endoscopia' => (float) $filas->sum('comision_endoscopia'),
            'comision_otros' => (float) $filas->sum('comision_otros'),
            'comision_congreso' => (float) $filas->sum('comision_congreso'),
            'pagado' => (float) $filas->sum('pagado'),
            'pendiente' => (float) $filas->sum('pendiente'),
            'ventas' => (int) $filas->sum('ventas'),
        ];
    }

    /** Pagos de comisión registrados para ese mes, los más recientes primero. */
    public function pagosDelPeriodo(string $periodo): Collection
    {
        return ComisionPago::with(['asesor', 'registradoPor'])
            ->where('periodo', $periodo)
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->get();
    }

    /** Define (o quita, con null o vacío) el porcentaje de comisión de un asesor. */
    public function definirPorcentaje(User $asesor, $porcentaje): void
    {
        $asesor->porcentaje_comision = $porcentaje === null || $porcentaje === '' ? null : $porcentaje;
        $asesor->save();
    }

    /** Registra un pago de comisión a un asesor por un mes. */
    public function registrarPago(array $data, User $registra): ComisionPago
    {
        $data['registrado_por'] = $registra->id;

        return ComisionPago::create($data);
    }
}
