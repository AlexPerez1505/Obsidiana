<?php

namespace App\Http\Controllers\Promociones;

use App\Http\Controllers\Controller;
use App\Models\Cupon;
use App\Models\CuponUso;
use App\Models\Customer;
use App\Models\EquipmentType;
use App\Models\MktEvento;
use App\Services\Promociones\CatalogoOferta;
use App\Services\Promociones\MotorDeCupones;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CuponController extends Controller
{
    public function __construct(
        private readonly MotorDeCupones $motor,
        private readonly CatalogoOferta $catalogo,
    ) {
    }

    /** Pantalla de cupones: tarjetas/tabla, filtros, panel lateral y asistente (datos por CuponesApiController). */
    public function index(): View
    {
        return view('structure.promociones.cupones.index', [
            'tiposEquipo' => EquipmentType::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function create(): RedirectResponse
    {
        return redirect()->route('promociones.cupones.index', ['nuevo' => 1]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $data = $this->validar($request);

        $cupon = DB::transaction(function () use ($data) {
            $c = Cupon::create($this->atributos($data) + ['creado_por' => auth()->id()]);
            $this->guardarAlcance($c, $data);
            MktEvento::registrar('cupon_creado', 'Creó el cupón '.$c->codigo, null, $c);

            return $c;
        });

        if ($request->expectsJson()) {
            return response()->json(['id' => $cupon->id, 'codigo' => $cupon->codigo, 'mensaje' => "Cupón {$cupon->codigo} creado."], 201);
        }

        return redirect()->route('promociones.cupones.show', $cupon)->with('status', "Cupón {$cupon->codigo} creado.");
    }

    public function show(Cupon $cupon): RedirectResponse
    {
        return redirect()->route('promociones.cupones.index', ['ver' => $cupon->id]);
    }

    public function edit(Cupon $cupon): RedirectResponse
    {
        return redirect()->route('promociones.cupones.index', ['editar' => $cupon->id]);
    }

    public function update(Request $request, Cupon $cupon): RedirectResponse|JsonResponse
    {
        $data = $this->validar($request, $cupon);

        DB::transaction(function () use ($cupon, $data) {
            $cupon->update($this->atributos($data, $cupon) + ['actualizado_por' => auth()->id()]);
            $this->guardarAlcance($cupon, $data);
            MktEvento::registrar('cupon_editado', 'Editó el cupón '.$cupon->codigo, null, $cupon, ['cambios' => array_keys($cupon->getChanges())]);
        });

        if ($request->expectsJson()) {
            return response()->json(['id' => $cupon->id, 'codigo' => $cupon->codigo, 'mensaje' => 'Cupón actualizado. Los documentos ya guardados conservan su descuento.']);
        }

        return redirect()->route('promociones.cupones.show', $cupon)->with('status', 'Cupón actualizado. Los documentos ya guardados conservan su descuento.');
    }

    public function estado(Request $request, Cupon $cupon): RedirectResponse|JsonResponse
    {
        $nuevo = $cupon->estado === 'pausado' ? 'activo' : 'pausado';
        $cupon->update(['estado' => $nuevo, 'actualizado_por' => auth()->id()]);
        MktEvento::registrar('cupon_'.$nuevo, ($nuevo === 'pausado' ? 'Pausó' : 'Reactivó').' el cupón '.$cupon->codigo, null, $cupon);

        $msg = $nuevo === 'pausado' ? 'Cupón pausado: no se podrá aplicar a compras nuevas.' : 'Cupón activo de nuevo.';

        return $request->expectsJson() ? response()->json(['estado' => $nuevo, 'mensaje' => $msg]) : back()->with('status', $msg);
    }

    /**
     * Para el cotizador: ¿este código aplica a este cliente y estos renglones?
     * Solo informa; al guardar, el servidor lo vuelve a revisar con candado.
     */
    public function comprobar(Request $request): JsonResponse
    {
        $data = $request->validate([
            'codigo' => ['required', 'string', 'max:40'],
            'customer_id' => ['required', 'exists:clientes,id'],
            'descuento_tipo' => ['nullable', 'in:porcentaje,monto'],
            'descuento_valor' => ['nullable', 'numeric', 'min:0'],
            'cotizacion_id' => ['nullable', 'integer'],
            'venta_id' => ['nullable', 'integer'],
            'items' => ['array'],
            'items.*.tipo_item' => ['required', 'in:equipo,paquete,producto'],
            'items.*.producto_id' => ['nullable', 'integer'],
            'items.*.paquete_id' => ['nullable', 'integer'],
            'items.*.equipo_id' => ['nullable', 'integer'],
            'items.*.cantidad' => ['required', 'integer', 'min:1'],
            'items.*.precio_unitario' => ['required', 'numeric', 'min:0'],
            'items.*.sobreprecio' => ['nullable', 'numeric', 'min:0'],
            'items.*.es_regalo' => ['nullable', 'boolean'],
        ]);

        $cupon = $this->motor->buscar($data['codigo']);
        if (! $cupon) {
            return response()->json(['valido' => false, 'mensaje' => 'Ese cupón no existe.', 'descuento' => 0]);
        }

        $ctx = array_filter([
            'cotizacion_id' => $data['cotizacion_id'] ?? null,
            'venta_id' => $data['venta_id'] ?? null,
            'origen_cotizacion_id' => isset($data['venta_id']) ? \App\Models\Venta::whereKey($data['venta_id'])->value('cotizacion_id') : null,
        ]);

        $r = $this->motor->evaluar(
            $cupon,
            Customer::findOrFail($data['customer_id']),
            $data['items'] ?? [],
            $this->motor->descuentoManualDe($data),
            $ctx
        );

        return response()->json($r->toArray() + ['condiciones' => $cupon->condicionesLegibles()]);
    }

    public function sugerirCodigo(Request $request): JsonResponse
    {
        return response()->json(['codigo' => Cupon::codigoAleatorio($request->get('prefijo') ?: 'MB')]);
    }

    // ------------------------------------------------------------------

    private function validar(Request $request, ?Cupon $cupon = null): array
    {
        $request->merge(['codigo' => Cupon::normalizarCodigo($request->input('codigo'))]);

        return $request->validate([
            'codigo' => ['nullable', 'string', 'min:3', 'max:40', Rule::unique('mkt_cupones', 'codigo')->ignore($cupon?->id)],
            'nombre' => ['required', 'string', 'max:255'],
            'tipo' => ['required', 'in:general,exclusivo'],
            'cliente_id' => ['nullable', 'exists:clientes,id'],
            'descuento_tipo' => ['required', 'in:porcentaje,monto'],
            'descuento_valor' => array_merge(['required', 'numeric', 'gt:0'], $request->input('descuento_tipo') === 'porcentaje' ? ['max:100'] : ['max:9999999']),
            'compra_minima' => ['nullable', 'numeric', 'min:0'],
            'tope_descuento' => ['nullable', 'numeric', 'gt:0'],
            'inicia_en' => ['nullable', 'date'],
            'vence_en' => array_merge(['nullable', 'date'], $request->filled('inicia_en') ? ['after:inicia_en'] : []),
            'usos_max' => ['nullable', 'integer', 'min:1'],
            'usos_por_cliente' => ['required', 'integer', 'min:1', 'max:1000'],
            'aplica_a' => ['required', 'in:todo,seleccion'],
            'alcance' => ['nullable', 'array', 'required_if:aplica_a,seleccion'],
            'alcance.*' => ['string', 'regex:/^(producto|paquete|tipo_equipo)-\d+$/'],
            'acumulable' => ['nullable', 'boolean'],
            'solo_primera_compra' => ['nullable', 'boolean'],
            'estado' => ['required', 'in:activo,pausado'],
            'condiciones' => ['nullable', 'string', 'max:500'],
        ], [
            'alcance.required_if' => 'Elige los productos, paquetes o tipos de equipo a los que aplica.',
            'descuento_valor.max' => 'Un porcentaje no puede pasar de 100.',
            'vence_en.after' => 'El vencimiento debe ser después del inicio.',
            'codigo.unique' => 'Ya existe un cupón con ese código.',
        ]);
    }

    private function atributos(array $data, ?Cupon $cupon = null): array
    {
        return [
            'codigo' => $data['codigo'] ?: ($cupon?->codigo ?? Cupon::codigoAleatorio('MB')),
            'nombre' => $data['nombre'],
            'tipo' => $data['tipo'],
            'cliente_id' => $data['tipo'] === Cupon::TIPO_EXCLUSIVO ? ($data['cliente_id'] ?? null) : null,
            'descuento_tipo' => $data['descuento_tipo'],
            'descuento_valor' => $data['descuento_valor'],
            'compra_minima' => $data['compra_minima'] ?? 0,
            'tope_descuento' => $data['descuento_tipo'] === 'porcentaje' ? ($data['tope_descuento'] ?? null) : null,
            'inicia_en' => $data['inicia_en'] ?? null,
            'vence_en' => $data['vence_en'] ?? null,
            'usos_max' => $data['usos_max'] ?? null,
            'usos_por_cliente' => $data['usos_por_cliente'],
            'aplica_a' => $data['aplica_a'],
            'acumulable' => (bool) ($data['acumulable'] ?? false),
            'solo_primera_compra' => (bool) ($data['solo_primera_compra'] ?? false),
            'estado' => $data['estado'],
            'condiciones' => $data['condiciones'] ?? null,
        ];
    }

    private function guardarAlcance(Cupon $c, array $data): void
    {
        $c->alcances()->delete();
        if ($data['aplica_a'] !== 'seleccion') {
            return;
        }
        foreach (array_unique((array) ($data['alcance'] ?? [])) as $clave) {
            [$tipo, $id] = explode('-', $clave, 2);
            $c->alcances()->create(['tipo' => $tipo, 'ref_id' => (int) $id]);
        }
    }
}
