<?php

namespace App\Services\Promociones;

use App\Models\CampanaDestinatario;
use App\Models\Cotizacion;
use App\Models\Cupon;
use App\Models\CuponUso;
use App\Models\Customer;
use App\Models\Equipo;
use App\Models\Producto;
use App\Models\Venta;
use App\Services\CalculadoraCotizacion;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * La única fuente de verdad de los cupones. Del navegador solo se toma el
 * código: precios, montos y reglas salen de la base.
 *
 * CICLO DE VIDA DE UN USO
 *   • Se RESERVA cuando el cupón entra en una cotización (desde la oferta
 *     de WhatsApp o desde el cotizador). Aparta el uso durante
 *     config('promociones.reserva_horas'); si en ese plazo no hay venta, la
 *     reserva vence sola y el uso vuelve a estar disponible.
 *   • Se CONSUME cuando se registra la venta (convertida de esa cotización
 *     o capturada directo). Desde ahí cuenta para siempre.
 *   • Se LIBERA si se quita el cupón, se borra la cotización o vence la reserva.
 *   • Se REVIERTE si la venta se cancela o se elimina (incluye el caso de un
 *     pago que nunca llegó: el sistema no cobra en línea, la venta se
 *     cancela con su PIN y el cupón regresa).
 *
 * CONCURRENCIA
 *   preparar() corre DENTRO de la transacción que guarda el documento y
 *   bloquea la fila del cupón (SELECT … FOR UPDATE). Dos compras simultáneas
 *   con el mismo cupón se forman: la segunda espera a que la primera
 *   termine y cuenta los usos ya con la primera incluida, así que nunca se
 *   rebasa el límite total ni el de cada cliente.
 */
class MotorDeCupones
{
    public function buscar(?string $codigo): ?Cupon
    {
        $codigo = Cupon::normalizarCodigo($codigo);

        return $codigo === '' ? null : Cupon::with('alcances')->where('codigo', $codigo)->first();
    }

    /**
     * Revisa todas las reglas sin reservar nada (para enseñar el descuento
     * antes de guardar). preparar() repite la revisión con el cupón bloqueado.
     *
     * @param  array<int, array>  $items  renglones con la forma del cotizador
     * @param  array{cotizacion_id?: int|null, venta_id?: int|null, origen_cotizacion_id?: int|null}  $ctx
     */
    public function evaluar(Cupon $cupon, Customer $cliente, array $items, float $descuentoManual = 0.0, array $ctx = [], bool $bloquear = false): ResultadoCupon
    {
        $errores = [];
        $honrar = $this->usoPropio($cupon, $ctx) !== null;

        if ($cupon->esPlantilla()) {
            return new ResultadoCupon($cupon, ['Este código es una plantilla de campaña y no se puede usar directamente.']);
        }

        // Exclusivo: solo su dueño. Conocer el código o reenviar el enlace no basta.
        if ($cupon->tipo === Cupon::TIPO_EXCLUSIVO && (int) $cupon->cliente_id !== (int) $cliente->id) {
            return new ResultadoCupon($cupon, ['Este cupón es personal y pertenece a otro cliente.']);
        }

        // Lo ya reservado o usado en este mismo documento se respeta aunque
        // el cupón haya vencido o se haya pausado después.
        if (! $honrar) {
            if ($cupon->estado === 'pausado') {
                $errores[] = 'El cupón está pausado por el momento.';
            }
            if ($cupon->inicia_en && $cupon->inicia_en->isFuture()) {
                $errores[] = 'El cupón todavía no está vigente: empieza el '.$cupon->inicia_en->format('d/m/Y H:i').'.';
            }
            if ($cupon->vence_en && $cupon->vence_en->isPast()) {
                $errores[] = 'El cupón venció el '.$cupon->vence_en->format('d/m/Y H:i').'.';
            }
        }

        if ($cupon->solo_primera_compra) {
            $yaCompro = Venta::where('customer_id', $cliente->id)
                ->where('estado', '!=', 'cancelada')
                ->when($ctx['venta_id'] ?? null, fn ($q, $id) => $q->whereKeyNot($id))
                ->exists();
            if ($yaCompro) {
                $errores[] = 'El cupón es solo para la primera compra y este cliente ya tiene compras.';
            }
        }

        if (! $cupon->acumulable && $descuentoManual > 0.004) {
            $errores[] = 'Este cupón no se combina con otros descuentos: quita el descuento manual para aplicarlo.';
        }

        $elegible = $this->subtotalElegible($cupon, $items);

        if ($elegible <= 0) {
            $errores[] = $cupon->aplica_a === 'seleccion'
                ? 'Ninguno de los productos de la compra participa en este cupón.'
                : 'La compra no tiene productos con precio.';
        } elseif ($elegible + 0.004 < (float) $cupon->compra_minima) {
            $errores[] = 'La compra mínima para este cupón es de $'.number_format((float) $cupon->compra_minima, 2)
                .' en productos participantes (llevas $'.number_format($elegible, 2).').';
        }

        if (! $honrar) {
            if ($cupon->usos_max !== null && $this->usosVigentes($cupon, null, $ctx, $bloquear) >= $cupon->usos_max) {
                $errores[] = 'El cupón ya se agotó.';
            }
            if ($this->usosVigentes($cupon, $cliente->id, $ctx, $bloquear) >= max(1, (int) $cupon->usos_por_cliente)) {
                $errores[] = $cupon->usos_por_cliente > 1
                    ? "Este cliente ya usó el cupón las {$cupon->usos_por_cliente} veces permitidas."
                    : 'Este cliente ya usó este cupón.';
            }
        }

        $descuento = $errores ? 0.0 : $this->montoDescuento($cupon, $elegible);

        return new ResultadoCupon($cupon, $errores, $elegible, $descuento);
    }

    /**
     * Valida con el cupón bloqueado. Llamar dentro de DB::transaction(),
     * ANTES de guardar el documento. Lanza ValidationException (campo
     * cupon_codigo) si algo no se cumple.
     *
     * @return array{cupon: Cupon, resultado: ResultadoCupon}|null
     */
    public function preparar(?string $codigo, int $clienteId, array $items, float $descuentoManual, array $ctx = []): ?array
    {
        $codigo = Cupon::normalizarCodigo($codigo);
        if ($codigo === '') {
            return null;
        }

        if (DB::transactionLevel() === 0) {
            throw new \LogicException('MotorDeCupones::preparar() debe correr dentro de una transacción.');
        }

        // FOR UPDATE: aquí se forman las compras simultáneas del mismo cupón.
        $cupon = Cupon::where('codigo', $codigo)->lockForUpdate()->first();

        if (! $cupon) {
            throw ValidationException::withMessages(['cupon_codigo' => "El cupón {$codigo} no existe."]);
        }

        $cupon->load('alcances');
        $cliente = Customer::findOrFail($clienteId);
        $r = $this->evaluar($cupon, $cliente, $items, $descuentoManual, $ctx, true);

        if (! $r->valido()) {
            throw ValidationException::withMessages(['cupon_codigo' => $r->mensaje()]);
        }

        return ['cupon' => $cupon, 'resultado' => $r];
    }

    /**
     * Atajo para cotizaciones y ventas: toma el código, el cliente, los
     * renglones y el descuento manual de los datos ya validados del documento.
     */
    public function prepararDocumento(array $data, array $ctx = []): ?array
    {
        return $this->preparar($data['cupon_codigo'] ?? null, (int) $data['customer_id'], $data['items'], $this->descuentoManualDe($data), $ctx);
    }

    /** Igual que prepararDocumento() pero sin bloquear: para revisar antes de abrir la transacción. */
    public function montoParaDocumento(array $data, array $ctx = []): float
    {
        if (! filled($data['cupon_codigo'] ?? null)) {
            return 0.0;
        }
        $cupon = $this->buscar($data['cupon_codigo']);
        if (! $cupon) {
            throw ValidationException::withMessages(['cupon_codigo' => 'El cupón '.Cupon::normalizarCodigo($data['cupon_codigo']).' no existe.']);
        }
        $r = $this->evaluar($cupon, Customer::findOrFail($data['customer_id']), $data['items'], $this->descuentoManualDe($data), $ctx);
        if (! $r->valido()) {
            throw ValidationException::withMessages(['cupon_codigo' => $r->mensaje()]);
        }

        return $r->descuento;
    }

    /** El descuento manual del documento, en pesos (para la regla de acumulación). */
    public function descuentoManualDe(array $data): float
    {
        $calc = app(CalculadoraCotizacion::class);

        return $calc->descuento(
            $calc->subtotal($data['items'] ?? []),
            $data['descuento_tipo'] ?? null,
            (float) ($data['descuento_valor'] ?? 0)
        );
    }

    /**
     * Deja la reserva de la cotización (o la quita si ya no lleva cupón).
     * Llamar en la misma transacción, después de guardarla.
     */
    public function vincularCotizacion(Cotizacion $cot, ?array $prep, ?CampanaDestinatario $destinatario = null): void
    {
        $actual = CuponUso::where('cotizacion_id', $cot->id)->whereNull('venta_id')
            ->where('estado', CuponUso::RESERVADO)->latest('id')->first();

        if (! $prep) {
            $actual && $this->cerrar($actual, CuponUso::LIBERADO, 'Se quitó el cupón de la cotización');
            Cotizacion::whereKey($cot->id)->update(['cupon_id' => null, 'cupon_codigo' => null, 'cupon_descuento' => 0]);

            return;
        }

        /** @var Cupon $cupon */
        $cupon = $prep['cupon'];
        $r = $prep['resultado'];

        if ($actual && (int) $actual->cupon_id !== (int) $cupon->id) {
            $this->cerrar($actual, CuponUso::LIBERADO, 'Se cambió por otro cupón');
            $actual = null;
        }

        [$campanaId, $destinatarioId] = $this->atribucion($cupon, (int) $cot->customer_id, $destinatario);

        $datos = [
            'cupon_id' => $cupon->id,
            'cliente_id' => $cot->customer_id,
            'campana_id' => $campanaId,
            'destinatario_id' => $destinatarioId,
            'cotizacion_id' => $cot->id,
            'estado' => CuponUso::RESERVADO,
            'subtotal_elegible' => $r->subtotalElegible,
            'descuento' => $r->descuento,
        ];

        if ($actual) {
            // Editar la cotización no alarga la reserva.
            $actual->update($datos);
        } else {
            CuponUso::create($datos + [
                'reservado_en' => now(),
                'expira_en' => now()->addHours(max(1, (int) config('promociones.reserva_horas'))),
                'registrado_por' => auth()->id(),
            ]);
        }

        Cotizacion::whereKey($cot->id)->update([
            'cupon_id' => $cupon->id,
            'cupon_codigo' => $cupon->codigo,
            'cupon_descuento' => $r->descuento,
            'campana_id' => $campanaId ?? $cot->campana_id,
        ]);
    }

    /**
     * Consume el cupón con la venta. Si viene de una cotización con el
     * mismo cupón reservado, esa reserva se convierte (no se cuenta doble).
     */
    public function vincularVenta(Venta $venta, ?array $prep): void
    {
        $actual = CuponUso::where('venta_id', $venta->id)->where('estado', CuponUso::CONSUMIDO)->latest('id')->first();
        $cotizacion = $venta->cotizacion_id ? Cotizacion::find($venta->cotizacion_id) : null;

        if (! $prep) {
            $actual && $this->cerrar($actual, CuponUso::REVERTIDO, 'Se quitó el cupón de la venta');
            Venta::whereKey($venta->id)->update([
                'cupon_id' => null, 'cupon_codigo' => null, 'cupon_descuento' => 0,
                // La campaña se conserva si la venta nació de un pedido de la oferta.
                'campana_id' => $cotizacion?->campana_id,
            ]);

            return;
        }

        /** @var Cupon $cupon */
        $cupon = $prep['cupon'];
        $r = $prep['resultado'];

        if ($actual && (int) $actual->cupon_id !== (int) $cupon->id) {
            $this->cerrar($actual, CuponUso::REVERTIDO, 'Se cambió por otro cupón');
            $actual = null;
        }

        // ¿Hay una reserva de la cotización de origen? Se reutiliza.
        if (! $actual && $cotizacion) {
            $actual = CuponUso::where('cotizacion_id', $cotizacion->id)->whereNull('venta_id')
                ->where('cupon_id', $cupon->id)
                ->whereIn('estado', [CuponUso::RESERVADO, CuponUso::LIBERADO])
                ->latest('id')->first();
        }

        $destinatario = $actual?->destinatario_id ? CampanaDestinatario::find($actual->destinatario_id) : null;
        [$campanaId, $destinatarioId] = $this->atribucion($cupon, (int) $venta->customer_id, $destinatario);
        $campanaId ??= $cotizacion?->campana_id;

        $datos = [
            'cupon_id' => $cupon->id,
            'cliente_id' => $venta->customer_id,
            'campana_id' => $campanaId,
            'destinatario_id' => $destinatarioId ?? $actual?->destinatario_id,
            'cotizacion_id' => $cotizacion?->id ?? $actual?->cotizacion_id,
            'venta_id' => $venta->id,
            'estado' => CuponUso::CONSUMIDO,
            'subtotal_elegible' => $r->subtotalElegible,
            'descuento' => $r->descuento,
            'consumido_en' => $actual?->consumido_en ?? now(),
            'cerrado_en' => null,
            'motivo_cierre' => null,
        ];

        if ($actual) {
            $actual->update($datos);
        } else {
            CuponUso::create($datos + ['reservado_en' => now(), 'registrado_por' => auth()->id()]);
        }

        Venta::whereKey($venta->id)->update([
            'cupon_id' => $cupon->id,
            'cupon_codigo' => $cupon->codigo,
            'cupon_descuento' => $r->descuento,
            'campana_id' => $campanaId,
        ]);
    }

    /** Venta cancelada o eliminada: el uso se revierte y vuelve a estar disponible. */
    public function revertirDeVenta(Venta $venta, string $motivo): void
    {
        CuponUso::where('venta_id', $venta->id)->where('estado', CuponUso::CONSUMIDO)
            ->get()->each(fn (CuponUso $u) => $this->cerrar($u, CuponUso::REVERTIDO, $motivo));
    }

    public function liberarDeCotizacion(Cotizacion $cot, string $motivo): void
    {
        CuponUso::where('cotizacion_id', $cot->id)->where('estado', CuponUso::RESERVADO)
            ->get()->each(fn (CuponUso $u) => $this->cerrar($u, CuponUso::LIBERADO, $motivo));
    }

    /** Las reservas que vencieron sin venta. Las corre promociones:despachar. */
    public function liberarReservasVencidas(): int
    {
        return CuponUso::where('estado', CuponUso::RESERVADO)
            ->where('expira_en', '<=', now())
            ->update(['estado' => CuponUso::LIBERADO, 'cerrado_en' => now(), 'motivo_cierre' => 'La reserva venció sin venta']);
    }

    /** Monto de descuento de un subtotal elegible: % o monto fijo, con tope y sin pasarse del subtotal. */
    public function montoDescuento(Cupon $cupon, float $elegible): float
    {
        $d = $cupon->descuento_tipo === 'porcentaje'
            ? $elegible * min(100, (float) $cupon->descuento_valor) / 100
            : (float) $cupon->descuento_valor;

        if ($cupon->tope_descuento !== null && (float) $cupon->tope_descuento > 0) {
            $d = min($d, (float) $cupon->tope_descuento);
        }

        return round(max(0, min($d, $elegible)), 2);
    }

    /** Suma de los renglones que participan (sin regalos). */
    public function subtotalElegible(Cupon $cupon, array $items): float
    {
        $alc = $cupon->relationLoaded('alcances') ? $cupon->alcances : $cupon->alcances()->get();
        $porTipo = fn (string $t) => $alc->where('tipo', $t)->pluck('ref_id')->map(fn ($v) => (int) $v)->all();
        $productos = $porTipo('producto');
        $equipos = $porTipo('equipo');
        $paquetes = $porTipo('paquete');
        $tiposEquipo = $porTipo('tipo_equipo');

        $tipoDe = [];
        if ($tiposEquipo) {
            $pids = collect($items)->where('tipo_item', 'producto')->pluck('producto_id')->filter()->all();
            $eids = collect($items)->where('tipo_item', 'equipo')->pluck('equipo_id')->filter()->all();
            foreach (Producto::whereIn('id', $pids)->pluck('equipment_type_id', 'id') as $id => $t) {
                $tipoDe['producto-'.$id] = (int) $t;
            }
            foreach (Equipo::whereIn('id', $eids)->pluck('equipment_type_id', 'id') as $id => $t) {
                $tipoDe['equipo-'.$id] = (int) $t;
            }
        }

        $total = 0.0;
        foreach ($items as $i) {
            if (! empty($i['es_regalo'])) {
                continue;
            }
            $tipo = $i['tipo_item'] ?? null;
            $id = (int) ($i[$tipo.'_id'] ?? 0);

            $participa = $cupon->aplica_a !== 'seleccion' || match ($tipo) {
                'producto' => in_array($id, $productos, true) || in_array($tipoDe['producto-'.$id] ?? -1, $tiposEquipo, true),
                'equipo' => in_array($id, $equipos, true) || in_array($tipoDe['equipo-'.$id] ?? -1, $tiposEquipo, true),
                'paquete' => in_array($id, $paquetes, true),
                default => false,
            };

            if ($participa) {
                $total += ((float) ($i['precio_unitario'] ?? 0) + (float) ($i['sobreprecio'] ?? 0)) * (int) ($i['cantidad'] ?? 0);
            }
        }

        return round($total, 2);
    }

    // ------------------------------------------------------------------

    /** Usos vigentes, sin contar los del propio documento (editar no debe chocar consigo mismo). */
    private function usosVigentes(Cupon $cupon, ?int $clienteId, array $ctx, bool $bloquear): int
    {
        $q = CuponUso::where('cupon_id', $cupon->id)
            ->when($clienteId, fn ($w) => $w->where('cliente_id', $clienteId))
            ->vigentes();

        foreach ($this->idsPropios($ctx) as $col => $id) {
            $q->where(fn ($w) => $w->whereNull($col)->orWhere($col, '!=', $id));
        }

        // Lectura "actual" (no de la foto de la transacción): ve lo que otra
        // compra acaba de confirmar mientras esta esperaba el candado.
        if ($bloquear) {
            $q->lockForUpdate();
        }

        return $q->count();
    }

    private function idsPropios(array $ctx): array
    {
        return array_filter([
            'venta_id' => $ctx['venta_id'] ?? null,
            'cotizacion_id' => $ctx['cotizacion_id'] ?? ($ctx['origen_cotizacion_id'] ?? null),
        ]);
    }

    private function usoPropio(Cupon $cupon, array $ctx): ?CuponUso
    {
        $ids = $this->idsPropios($ctx);
        if (! $ids || ! $cupon->exists) {
            return null;
        }

        return CuponUso::where('cupon_id', $cupon->id)->vigentes()
            ->where(function ($w) use ($ids) {
                foreach ($ids as $col => $id) {
                    $w->orWhere($col, $id);
                }
            })->first();
    }

    /**
     * Una compra cuenta para una campaña solo si el cupón es de esa
     * campaña y el cliente sí la recibió (no por una prueba). Así la venta
     * se atribuye a una sola campaña y nunca dos veces.
     *
     * @return array{0: int|null, 1: int|null}
     */
    private function atribucion(Cupon $cupon, int $clienteId, ?CampanaDestinatario $dest): array
    {
        if ($dest && ! $dest->es_prueba && (int) $dest->cliente_id === $clienteId) {
            return [$dest->campana_id, $dest->id];
        }

        if ($cupon->campana_id) {
            $d = CampanaDestinatario::where('campana_id', $cupon->campana_id)
                ->where('cliente_id', $clienteId)->where('es_prueba', false)->first();
            if ($d) {
                return [$d->campana_id, $d->id];
            }
        }

        return [null, null];
    }

    private function cerrar(CuponUso $uso, string $estado, string $motivo): void
    {
        $uso->update(['estado' => $estado, 'cerrado_en' => now(), 'motivo_cierre' => mb_substr($motivo, 0, 250)]);
    }
}
