<?php

namespace App\Services\Cotizaciones;

use App\Models\Cotizacion;
use App\Models\User;
use App\Models\Venta;
use App\Services\CalculadoraCotizacion;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Crear y editar cotizaciones, igual desde la web que desde la API.
 *
 * Recibe los datos ya validados (con reglas()) y SIEMPRE recalcula los
 * montos y el plan de pagos con la calculadora: lo que mande el cliente
 * en subtotal, total o montos de pagos libres no se toma en cuenta.
 */
class GuardarCotizacion
{
    public function __construct(private readonly CalculadoraCotizacion $calc)
    {
    }

    public function reglas(): array
    {
        return [
            'customer_id' => ['required', 'exists:clientes,id'],
            'congreso_id' => ['nullable', 'exists:congresos_eventos,id'],
            'nota_cliente' => ['nullable', 'string'],
            'modalidad' => ['required', 'in:contado,financiamiento'],
            'aplica_iva' => ['nullable', 'boolean'],
            'descuento_tipo' => ['nullable', 'in:porcentaje,monto'],
            'descuento_valor' => ['nullable', 'numeric', 'min:0'],
            'envio' => ['nullable', 'numeric', 'min:0'],
            'valor_a_cuenta' => ['nullable', 'numeric', 'min:0'],
            'plan_nombre' => ['nullable', 'string', 'max:255'],
            'num_meses' => ['nullable', 'integer', 'min:0', 'max:60'],
            'garantia_meses' => ['nullable', 'integer', Rule::in(Venta::GARANTIAS)],

            'items' => ['required', 'array', 'min:1'],
            'items.*.tipo_item' => ['required', 'in:equipo,paquete,producto'],
            'items.*.equipo_id' => ['nullable', 'integer'],
            'items.*.paquete_id' => ['nullable', 'integer'],
            'items.*.producto_id' => ['nullable', 'integer'],
            'items.*.nombre' => ['required', 'string', 'max:255'],
            'items.*.modelo' => ['nullable', 'string', 'max:255'],
            'items.*.marca' => ['nullable', 'string', 'max:255'],
            'items.*.imagen' => ['nullable', 'string', 'max:1024'],
            'items.*.cantidad' => ['required', 'integer', 'min:1'],
            'items.*.precio_unitario' => ['required', 'numeric', 'min:0'],
            'items.*.sobreprecio' => ['nullable', 'numeric', 'min:0'],
            'items.*.es_regalo' => ['nullable', 'boolean'],

            'pagos' => ['nullable', 'array'],
            'pagos.*.nombre' => ['required_with:pagos', 'string', 'max:255'],
            'pagos.*.fecha' => ['nullable', 'date'],
            'pagos.*.porcentaje' => ['nullable', 'numeric', 'min:0'],
            'pagos.*.monto' => ['nullable', 'numeric', 'min:0'],
            'pagos.*.bloqueado' => ['nullable', 'boolean'],

            'fichas' => ['nullable', 'array'],
            'fichas.*' => ['integer', 'exists:fichas_tecnicas,id'],
        ];
    }

    /**
     * Lo mínimo para calcular montos y plan sin guardar: las mismas reglas
     * de arriba, sin cliente ni descripción de los renglones.
     */
    public function reglasCalculo(): array
    {
        return Arr::only($this->reglas(), [
            'modalidad', 'aplica_iva', 'descuento_tipo', 'descuento_valor', 'envio', 'valor_a_cuenta', 'num_meses',
            'items', 'items.*.cantidad', 'items.*.precio_unitario', 'items.*.sobreprecio', 'items.*.es_regalo',
            'pagos', 'pagos.*.nombre', 'pagos.*.fecha', 'pagos.*.porcentaje', 'pagos.*.monto', 'pagos.*.bloqueado',
        ]);
    }

    /** Cotización nueva con el siguiente folio COT-AAAA-####, a nombre de quien la hace. */
    public function crear(array $data, User $vendedor): Cotizacion
    {
        return DB::transaction(function () use ($data, $vendedor) {
            $cot = new Cotizacion();
            $cot->folio = Cotizacion::siguienteFolio();
            $cot->seller_id = $vendedor->id;
            $this->llenarDesde($cot, $data);
            $cot->save();

            $this->guardarItems($cot, $data['items']);
            $this->guardarPagos($cot, $data);
            $this->guardarFichas($cot, $data['fichas'] ?? []);

            return $cot;
        });
    }

    /** Reemplaza cabecera, renglones, plan y fichas; folio y vendedor se conservan. */
    public function actualizar(Cotizacion $cotizacion, array $data): Cotizacion
    {
        DB::transaction(function () use ($cotizacion, $data) {
            $this->llenarDesde($cotizacion, $data);
            $cotizacion->save();

            $cotizacion->items()->delete();
            $cotizacion->pagos()->delete();
            $this->guardarItems($cotizacion, $data['items']);
            $this->guardarPagos($cotizacion, $data);
            $cotizacion->fichas()->sync($data['fichas'] ?? []);
        });

        return $cotizacion;
    }

    /**
     * Los montos que saldrían con estos datos, sin guardar nada. Es la
     * misma cuenta que hace llenarDesde(); la usa la API para que una app
     * enseñe el total antes de guardar sin copiar la fórmula.
     *
     * @return array{subtotal: float, descuento: float, base: float, iva: float, total: float, total_contrato: float}
     */
    public function desglose(array $data): array
    {
        $items = array_map(fn ($i) => [
            'precio_unitario' => (float) $i['precio_unitario'],
            'sobreprecio' => (float) ($i['sobreprecio'] ?? 0),
            'cantidad' => (int) $i['cantidad'],
            'es_regalo' => (bool) ($i['es_regalo'] ?? false),
        ], $data['items']);

        return $this->calc->desglose(
            $items,
            $data['descuento_tipo'] ?? null,
            (float) ($data['descuento_valor'] ?? 0),
            (float) ($data['envio'] ?? 0),
            (bool) ($data['aplica_iva'] ?? false),
            (float) ($data['valor_a_cuenta'] ?? 0),
        );
    }

    /**
     * El plan de pagos que se guardaría (vacío si es de contado).
     *
     * @return array<int, array{nombre: string, fecha: string, porcentaje: float, monto: float, bloqueado: bool, orden: int}>
     */
    public function plan(array $data, float $totalContrato): array
    {
        if ($data['modalidad'] !== 'financiamiento') {
            return [];
        }

        $previos = array_map(fn ($p) => [
            'nombre' => $p['nombre'] ?? null,
            'fecha' => $p['fecha'] ?? null,
            'monto' => (float) ($p['monto'] ?? 0),
            'porcentaje' => (float) ($p['porcentaje'] ?? 0),
            'bloqueado' => (bool) ($p['bloqueado'] ?? false),
        ], $data['pagos'] ?? []);

        return $this->calc->planPagos(
            $totalContrato,
            (int) ($data['num_meses'] ?? 0),
            $previos,
        );
    }

    /**
     * Rellena la cabecera recalculando SIEMPRE los montos en el servidor.
     */
    private function llenarDesde(Cotizacion $cot, array $data): void
    {
        $descTipo = $data['descuento_tipo'] ?? null;
        $descValor = (float) ($data['descuento_valor'] ?? 0);
        $envio = (float) ($data['envio'] ?? 0);
        $aplicaIva = (bool) ($data['aplica_iva'] ?? false);
        $valorACuenta = (float) ($data['valor_a_cuenta'] ?? 0);

        $d = $this->desglose($data);

        $cot->customer_id = $data['customer_id'];
        // El nombre del congreso lo escribe el modelo en lugar_propuesta.
        $cot->congreso_id = $data['congreso_id'] ?? null;
        $cot->nota_cliente = $data['nota_cliente'] ?? null;
        $cot->modalidad = $data['modalidad'];
        $cot->aplica_iva = $aplicaIva;
        $cot->subtotal = $d['subtotal'];
        $cot->descuento_tipo = $descValor > 0 ? $descTipo : null;
        $cot->descuento_valor = $descValor;
        $cot->descuento_monto = $d['descuento'];
        $cot->envio = $envio;
        $cot->iva_monto = $d['iva'];
        $cot->valor_a_cuenta = $valorACuenta;
        $cot->total = $d['total'];
        $cot->total_contrato = $d['total_contrato'];
        $cot->plan_nombre = $data['modalidad'] === 'financiamiento' ? ($data['plan_nombre'] ?? 'Plan Personalizado') : null;
        $cot->num_meses = $data['modalidad'] === 'financiamiento' ? (int) ($data['num_meses'] ?? 0) : 0;
        $cot->garantia_meses = (int) ($data['garantia_meses'] ?? 6);
    }

    private function guardarItems(Cotizacion $cot, array $items): void
    {
        foreach (array_values($items) as $orden => $i) {
            $cot->items()->create([
                'equipo_id' => $i['tipo_item'] === 'equipo' ? ($i['equipo_id'] ?? null) : null,
                'paquete_id' => $i['tipo_item'] === 'paquete' ? ($i['paquete_id'] ?? null) : null,
                'producto_id' => $i['tipo_item'] === 'producto' ? ($i['producto_id'] ?? null) : null,
                'tipo_item' => $i['tipo_item'],
                'nombre' => $i['nombre'],
                'modelo' => $i['modelo'] ?? null,
                'marca' => $i['marca'] ?? null,
                'imagen' => $i['imagen'] ?? null,
                'cantidad' => (int) $i['cantidad'],
                'precio_unitario' => (float) $i['precio_unitario'],
                'sobreprecio' => (float) ($i['sobreprecio'] ?? 0),
                'es_regalo' => (bool) ($i['es_regalo'] ?? false),
                'orden' => $orden,
            ]);
        }
    }

    /**
     * Recalcula y guarda el plan de pagos con la calculadora (no confía en el cliente).
     */
    private function guardarPagos(Cotizacion $cot, array $data): void
    {
        // Mismo plazo que quedó en la cabecera (0 si es de contado).
        $plan = $this->plan(['num_meses' => $cot->num_meses] + $data, (float) $cot->total_contrato);

        foreach ($plan as $p) {
            $cot->pagos()->create($p);
        }
    }

    private function guardarFichas(Cotizacion $cot, array $fichas): void
    {
        if (! empty($fichas)) {
            $cot->fichas()->sync($fichas);
        }
    }
}
