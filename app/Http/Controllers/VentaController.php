<?php

namespace App\Http\Controllers;

use App\Models\Congress;
use App\Models\Cotizacion;
use App\Models\Customer;
use App\Models\Venta;
use App\Services\Ventas\DocumentosDeVenta;
use App\Services\Ventas\OperacionRechazada;
use App\Services\Ventas\RegistroDeVentas;
use App\Support\DocumentoInitial;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Pantallas de ventas. Las reglas (montos, stock, plan de pagos, qué se
 * puede editar o cancelar) viven en RegistroDeVentas, que también usa la
 * API: aquí solo se valida, se llama y se regresa a la pantalla.
 */
class VentaController extends Controller
{
    public function __construct(
        private readonly RegistroDeVentas $registro,
        private readonly DocumentosDeVenta $documentos,
    ) {
    }

    public function index(): View
    {
        // items: para mostrar qué se vendió en cada fila sin abrirla.
        // Cada asesor ve las suyas, salvo que tenga permiso de ver todas.
        $ventas = Venta::visiblesPara(auth()->user())
            ->with(['customer', 'seller', 'items'])
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('structure.commercial_management.ventas.index', [
            'ventas' => $ventas,
            'total' => Venta::count(),
            'confirmadas' => Venta::where('estado', 'confirmada')->count(),
            'facturadas' => Venta::where('estado', 'facturada')->count(),
            'montoTotal' => (float) Venta::sum('total'),
        ]);
    }

    /**
     * Formulario de nueva venta. Con ?cotizacion=ID precarga desde la cotización.
     */
    public function create(Request $request): View
    {
        $origen = null;
        $clientePre = null;

        if ($request->filled('cotizacion')) {
            // Solo se puede convertir una cotización que el usuario pueda ver.
            $origen = Cotizacion::visiblesPara($request->user())
                ->with(['customer', 'items.producto', 'pagos', 'fichas'])
                ->find($request->integer('cotizacion'));
            $clientePre = $origen?->customer;
        } elseif ($request->filled('cliente')) {
            $clientePre = Customer::find($request->integer('cliente'));
        }

        return view('structure.commercial_management.ventas.form', [
            'venta' => null,
            'initial' => $this->registro->inicial($origen, $clientePre),
            'congresos' => Congress::orderBy('nombre')->get(),
            'origenId' => $origen?->id,
            'avisosStock' => $origen ? $this->registro->avisosStock($origen->items) : [],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validar($request);

        $venta = $this->registro->crear($data, $request->integer('cotizacion') ?: null);

        return redirect()->route('commercial.ventas.show', $venta)
            ->with('status', "Venta {$venta->folio} registrada correctamente.");
    }

    public function show(Venta $venta): View
    {
        $venta->load(['customer', 'seller', 'items', 'pagos', 'fichas', 'cotizacion', 'bitacora.user', 'ordenSalida']);

        return view('structure.commercial_management.ventas.show', [
            'venta' => $venta,
        ]);
    }

    public function edit(Venta $venta): View
    {
        $venta->load(['customer', 'items', 'pagos', 'fichas']);

        if ($venta->cancelada()) {
            return redirect()->route('commercial.ventas.show', $venta)
                ->withErrors(['venta' => 'Una venta cancelada no se edita. Si hace falta, regístrala de nuevo.']);
        }

        return view('structure.commercial_management.ventas.form', [
            'venta' => $venta,
            'initial' => DocumentoInitial::build($venta, $venta->customer),
            'congresos' => Congress::orderBy('nombre')->get(),
            'origenId' => null,
            // Con dinero cobrado sobre alguna parcialidad, el plan se congela
            // en el formulario: se ajusta desde Cobranza, no desde aquí.
            'planBloqueado' => $venta->planBloqueado(),
        ]);
    }

    public function update(Request $request, Venta $venta): RedirectResponse
    {
        // Antes de validar: a una venta que ya no se puede tocar no se le
        // piden datos.
        try {
            $this->registro->asegurarEditable($venta);
        } catch (OperacionRechazada $e) {
            return redirect()->route('commercial.ventas.show', $venta)
                ->withErrors([$e->campo => $e->getMessage()]);
        }

        $data = $this->validar($request);

        try {
            $mensaje = $this->registro->actualizar($venta, $data);
        } catch (OperacionRechazada $e) {
            return back()->withInput()->withErrors([$e->campo => $e->getMessage()]);
        }

        return redirect()->route('commercial.ventas.show', $venta)
            ->with('status', $mensaje);
    }

    /**
     * Cancelar una venta (ver RegistroDeVentas::cancelar). Pide el PIN de
     * aprobación o la contraseña.
     */
    public function cancelar(Request $request, Venta $venta): RedirectResponse
    {
        try {
            $this->registro->asegurarCancelable($venta);
        } catch (OperacionRechazada $e) {
            return back()->withErrors([$e->campo => $e->getMessage()]);
        }

        $data = $request->validate(RegistroDeVentas::REGLAS_CANCELAR, RegistroDeVentas::MENSAJES_CANCELAR);

        try {
            $mensaje = $this->registro->cancelar($venta, $data['motivo'], $data['password'], $request->user());
        } catch (OperacionRechazada $e) {
            return back()->withInput()->withErrors([$e->campo => $e->getMessage()]);
        }

        return redirect()->route('commercial.ventas.show', $venta)
            ->with('status', $mensaje);
    }

    /**
     * Eliminar del todo solo se permite cuando la venta no tiene ningún
     * cobro: si ya entró dinero, se cancela (y el historial se queda).
     */
    public function destroy(Venta $venta): RedirectResponse
    {
        try {
            $mensaje = $this->registro->eliminar($venta);
        } catch (OperacionRechazada $e) {
            return redirect()->route('commercial.ventas.show', $venta)
                ->withErrors([$e->campo => $e->getMessage()]);
        }

        return redirect()->route('commercial.ventas.index')
            ->with('status', $mensaje);
    }

    public function pdf(Venta $venta)
    {
        return $this->documentos->venta($venta);
    }

    /**
     * Contrato de compraventa a plazos.
     *
     * Solo tiene sentido cuando se paga en parcialidades: en una venta de
     * contado no hay nada que garantizar en el tiempo.
     */
    public function contrato(Venta $venta)
    {
        abort_unless($venta->requiereContrato(), 404, 'El contrato solo aplica a ventas a plazos.');

        return $this->documentos->contrato($venta);
    }

    /** Carta garantía del equipo. Aplica a toda venta. */
    public function garantia(Venta $venta)
    {
        if (! $venta->tieneGarantia()) {
            return redirect()->route('commercial.ventas.show', $venta)
                ->withErrors(['venta' => 'Esta venta se registró sin garantía: no hay carta garantía que generar.']);
        }

        return $this->documentos->garantia($venta);
    }

    private function validar(Request $request): array
    {
        return $request->validate(RegistroDeVentas::reglas());
    }
}
