<?php

namespace App\Services\Ventas;

use App\Models\Cobro;
use App\Models\Venta;
use App\Models\VentaBitacora;
use App\Models\VentaPago;
use App\Services\CalendarioPagos;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/**
 * Cobranza de una venta: registrar lo que entró (con su evidencia),
 * cancelar un cobro y ajustar el plan de pagos.
 *
 * Vivía dentro de CobroController; se sacó aquí para que la web y la API
 * apliquen el dinero exactamente igual. El reparto de un cobro entre las
 * parcialidades lo sigue haciendo CalendarioPagos; aquí se arman las
 * reglas, la bitácora y el mensaje que ve el usuario.
 */
class CobrosDeVenta
{
    /** Carpeta del disco public donde quedan las evidencias. */
    public const CARPETA = 'cobros';

    public const MENSAJES_COBRO = [
        'monto.min' => 'El monto debe ser mayor a cero.',
        'evidencias.*.mimes' => 'La evidencia debe ser una imagen o un PDF.',
    ];

    public const REGLAS_PARCIALIDAD_NUEVA = [
        'nombre' => ['required', 'string', 'max:255'],
        'fecha' => ['required', 'date'],
        'monto' => ['required', 'numeric', 'min:0.01'],
    ];

    public const REGLAS_PARCIALIDAD = [
        'fecha' => ['required', 'date'],
        'monto' => ['required', 'numeric', 'min:0'],
    ];

    public function __construct(private readonly CalendarioPagos $calendario)
    {
    }

    /** Lo que se pide para registrar un cobro de esta venta. */
    public static function reglasCobro(Venta $venta): array
    {
        return [
            'venta_pago_id' => ['nullable', Rule::exists('venta_pagos', 'id')->where('venta_id', $venta->id)],
            'fecha' => ['required', 'date'],
            'monto' => ['required', 'numeric', 'min:0.01'],
            'metodo' => ['required', Rule::in(array_keys(Cobro::METODOS))],
            'referencia' => ['nullable', 'string', 'max:255'],
            'nota' => ['nullable', 'string', 'max:255'],
            'evidencias' => ['nullable', 'array', 'max:5'],
            'evidencias.*' => ['file', 'mimes:jpg,jpeg,png,webp,pdf'],
        ];
    }

    /** Una venta cancelada ya no recibe cobros (se revisa antes de validar). */
    public function asegurarCobrable(Venta $venta): void
    {
        if ($venta->cancelada()) {
            throw OperacionRechazada::conflicto('Esta venta está cancelada: no se le pueden registrar cobros.', 'monto');
        }
    }

    /**
     * Registra el cobro con sus evidencias y lo aplica al calendario.
     *
     * @param  array<int, UploadedFile>  $evidencias
     * @return array{cobro: Cobro, aplicacion: array, mensaje: string}
     */
    public function registrar(Venta $venta, array $data, array $evidencias = []): array
    {
        $this->asegurarCobrable($venta);

        // No se acepta cobrar de más: sería un descuadre imposible de explicar.
        if ($data['monto'] > $venta->saldo() + 0.009) {
            throw OperacionRechazada::datoInvalido(
                'monto',
                'El monto supera el saldo pendiente ($' . number_format($venta->saldo(), 2) . ').'
            );
        }

        [$cobro, $aplicacion] = DB::transaction(function () use ($evidencias, $venta, $data) {
            $cobro = Cobro::create([
                'venta_id' => $venta->id,
                'venta_pago_id' => $data['venta_pago_id'] ?? null,
                'folio' => Cobro::siguienteFolio(),
                'fecha' => $data['fecha'],
                'monto' => $data['monto'],
                'metodo' => $data['metodo'],
                'referencia' => $data['referencia'] ?? null,
                'nota' => $data['nota'] ?? null,
                'registrado_por' => auth()->id(),
            ]);

            foreach ($evidencias as $archivo) {
                $cobro->evidencias()->create([
                    'archivo' => $archivo->store(self::CARPETA, 'public'),
                    'nombre' => $archivo->getClientOriginalName(),
                ]);
            }

            VentaBitacora::registrar(
                $venta,
                'cobro_registrado',
                "Cobro {$cobro->folio} por \$" . number_format((float) $cobro->monto, 2) . ' (' . $cobro->metodoLabel() . ')',
                ['cobro_id' => $cobro->id, 'monto' => (float) $cobro->monto]
            );

            $venta->load(['pagos.cobros', 'cobros']);

            /*
            | El cobro se queda como UN solo recibo por lo que el cliente pagó.
            | Se aplica en orden a los pagos pendientes (empezando por el que
            | se eligió, si se eligió uno) sin partir el cobro ni cambiar los
            | montos del calendario: $1,000 sobre tres pagos de $533 deja el
            | primero liquidado, el segundo con $467 abonados (restan $66) y
            | el tercero intacto. El recibo lo detalla.
            */
            return [$cobro, $this->calendario->aplicar($venta, $cobro, $data['venta_pago_id'] ?? null)];
        });

        $aviso = '';
        if (count($aplicacion['lineas']) > 1) {
            $aviso = ' Se aplicó a ' . count($aplicacion['lineas']) . ' pagos: ' . collect($aplicacion['lineas'])
                ->map(fn ($l) => $l['pago']->nombre . ' $' . number_format($l['monto'], 2) . ($l['liquida'] ? ' (liquidado)' : ' (restan $' . number_format($l['resta'], 2) . ')'))
                ->implode(', ') . '.';
        } elseif (count($aplicacion['lineas']) === 1 && ! $aplicacion['lineas'][0]['liquida']) {
            $aviso = ' Pago parcial: a ' . $aplicacion['lineas'][0]['pago']->nombre . ' le restan $' . number_format($aplicacion['lineas'][0]['resta'], 2) . '.';
        }
        if ($aplicacion['sin_aplicar'] > 0.009) {
            $aviso .= ' Quedaron $' . number_format($aplicacion['sin_aplicar'], 2) . ' sin aplicar a ningún pago del calendario.';
        }

        return [
            'cobro' => $cobro,
            'aplicacion' => $aplicacion,
            'mensaje' => 'Cobro registrado correctamente.' . $aviso,
        ];
    }

    /**
     * Cancelar un cobro. No se edita: se cancela y se vuelve a capturar,
     * para que el recibo entregado siempre corresponda con el sistema.
     */
    public function cancelarCobro(Venta $venta, Cobro $cobro): string
    {
        DB::transaction(function () use ($venta, $cobro) {
            foreach ($cobro->evidencias as $ev) {
                if (Storage::disk('public')->exists($ev->archivo)) {
                    Storage::disk('public')->delete($ev->archivo);
                }
            }

            VentaBitacora::registrar(
                $venta,
                'cobro_cancelado',
                "Se canceló el cobro {$cobro->folio} por \$" . number_format((float) $cobro->monto, 2),
                ['folio' => $cobro->folio, 'monto' => (float) $cobro->monto]
            );

            $cobro->delete();
        });

        return 'Cobro cancelado.';
    }

    /** Recorre el calendario para que empiece en otra fecha. */
    public function recorrer(Venta $venta, Carbon $nuevaFecha): string
    {
        $movidas = $this->calendario->recorrer($venta, $nuevaFecha);

        return $movidas > 0
            ? "Se recorrieron {$movidas} fecha(s). Las parcialidades ya cobradas no se movieron."
            : 'No había fechas por mover.';
    }

    /** Reparte la diferencia entre las parcialidades sin cobrar. */
    public function rebalancear(Venta $venta): string
    {
        $r = $this->calendario->rebalancear($venta);

        if (! empty($r['sin_donde'])) {
            throw OperacionRechazada::conflicto(
                'Todas las parcialidades tienen cobros; no hay dónde repartir la diferencia. Agrega una parcialidad nueva.',
                'plan'
            );
        }

        return $r['ajustadas'] > 0
            ? 'Se repartió la diferencia entre ' . $r['ajustadas'] . ' parcialidad(es).'
            : 'El plan ya cuadra con el total.';
    }

    /** Reasigna a las parcialidades pendientes el excedente de abonos sueltos o que se pasaron de lo que les tocaba. */
    public function absorberExcedente(Venta $venta): string
    {
        $r = $this->calendario->absorberExcedente($venta);

        if ($r['excedente'] < 0.01) {
            return 'No hay excedente por repartir.';
        }

        return 'Se aplicaron $'.number_format($r['excedente'], 2).' a '.$r['ajustadas'].' parcialidad(es) pendiente(s).';
    }

    /** Agrega una parcialidad al final del plan. */
    public function agregarParcialidad(Venta $venta, array $data): VentaPago
    {
        $pago = $venta->pagos()->create($data + ['orden' => (int) $venta->pagos()->max('orden') + 1]);

        VentaBitacora::registrar($venta, 'parcialidad_agregada', "Se agregó «{$data['nombre']}»", $data);

        return $pago;
    }

    /** Cambia la fecha o el monto de una parcialidad concreta. */
    public function actualizarParcialidad(Venta $venta, VentaPago $pago, array $data): string
    {
        // Lo ya cobrado marca el piso: por debajo, el recibo dejaría de cuadrar.
        $cobrado = $pago->cobrado();

        if ($data['monto'] < $cobrado - 0.009) {
            throw OperacionRechazada::datoInvalido(
                'monto',
                'No puede quedar por debajo de lo ya cobrado ($' . number_format($cobrado, 2) . ').'
            );
        }

        $antes = ['fecha' => $pago->fecha?->format('Y-m-d'), 'monto' => (float) $pago->monto];

        $pago->update($data);

        VentaBitacora::registrar(
            $venta,
            'parcialidad_editada',
            "Se ajustó «{$pago->nombre}»",
            ['antes' => $antes, 'despues' => ['fecha' => $data['fecha'], 'monto' => (float) $data['monto']]]
        );

        return 'Parcialidad actualizada.';
    }

    /** Elimina una parcialidad, solo si no tiene dinero encima. */
    public function eliminarParcialidad(Venta $venta, VentaPago $pago): string
    {
        if ($pago->tieneCobros()) {
            throw OperacionRechazada::conflicto('Esa parcialidad ya tiene cobros; cancela primero sus cobros.', 'plan');
        }

        $nombre = $pago->nombre;
        $pago->delete();

        VentaBitacora::registrar($venta, 'parcialidad_eliminada', "Se eliminó «{$nombre}»");

        return 'Parcialidad eliminada.';
    }
}
