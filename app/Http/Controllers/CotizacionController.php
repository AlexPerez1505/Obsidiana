<?php

namespace App\Http\Controllers;

use App\Models\Congress;
use App\Models\Cotizacion;
use App\Models\Customer;
use App\Models\FichaTecnica;
use App\Models\Paquete;
use App\Models\Producto;
use App\Services\Cotizaciones\BuscadorCotizador;
use App\Services\Cotizaciones\GuardarCotizacion;
use App\Services\Cotizaciones\PdfCotizacion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Pantallas de cotizaciones. Guardar, el PDF y los buscadores viven en
 * App\Services\Cotizaciones, que también usa la API: aquí solo queda
 * validar, llamar y responder.
 */
class CotizacionController extends Controller
{
    public function __construct(
        private readonly GuardarCotizacion $guardar,
        private readonly BuscadorCotizador $buscador,
    ) {
    }

    public function index(): View
    {
        // items: para mostrar qué se cotizó en cada fila sin abrirla.
        // Cada asesor ve las suyas, salvo que tenga permiso de ver todas.
        $cotizaciones = Cotizacion::visiblesPara(auth()->user())
            ->with(['customer', 'seller', 'items'])
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('structure.commercial_management.cotizaciones.index', [
            'cotizaciones' => $cotizaciones,
            'total' => Cotizacion::count(),
            'borradores' => Cotizacion::where('estado', 'borrador')->count(),
            'aceptadas' => Cotizacion::whereIn('estado', ['aceptada', 'convertida'])->count(),
            'montoTotal' => (float) Cotizacion::sum('total'),
        ]);
    }

    public function create(Request $request): View
    {
        return view('structure.commercial_management.cotizaciones.form', [
            'cotizacion' => null,
            'congresos' => Congress::orderBy('nombre')->get(),
            'clientePre' => $request->filled('cliente')
                ? Customer::find($request->integer('cliente'))
                : null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate($this->guardar->reglas());

        $cotizacion = $this->guardar->crear($data, $request->user());

        return redirect()->route('commercial.cotizaciones.show', $cotizacion)
            ->with('status', "Cotización {$cotizacion->folio} guardada correctamente.");
    }

    public function show(Cotizacion $cotizacion): View
    {
        $cotizacion->load(['customer', 'seller', 'items', 'pagos', 'fichas']);

        return view('structure.commercial_management.cotizaciones.show', [
            'cotizacion' => $cotizacion,
        ]);
    }

    public function edit(Cotizacion $cotizacion): View
    {
        $cotizacion->load(['customer', 'items', 'pagos', 'fichas']);

        return view('structure.commercial_management.cotizaciones.form', [
            'cotizacion' => $cotizacion,
            'congresos' => Congress::orderBy('nombre')->get(),
            'clientePre' => $cotizacion->customer,
        ]);
    }

    public function update(Request $request, Cotizacion $cotizacion): RedirectResponse
    {
        $data = $request->validate($this->guardar->reglas());

        $this->guardar->actualizar($cotizacion, $data);

        return redirect()->route('commercial.cotizaciones.show', $cotizacion)
            ->with('status', "Cotización {$cotizacion->folio} actualizada.");
    }

    public function destroy(Cotizacion $cotizacion): RedirectResponse
    {
        $folio = $cotizacion->folio;
        $cotizacion->delete();

        return redirect()->route('commercial.cotizaciones.index')
            ->with('status', "Cotización {$folio} eliminada.");
    }

    public function pdf(Cotizacion $cotizacion, PdfCotizacion $pdf)
    {
        return response($pdf->generar($cotizacion), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$pdf->nombreArchivo($cotizacion).'"',
        ]);
    }

    /* ===================== Endpoints JSON ===================== */

    public function buscarClientes(Request $request): JsonResponse
    {
        $q = trim($request->get('q', ''));

        $clientes = $this->buscador->clientes($request->user(), $q)
            ->map(function (Customer $c) {
                $historial = $this->buscador->historial($c);

                return [
                    'id' => $c->id,
                    'nombre' => trim($c->nombre.' '.$c->apellido),
                    'correo' => $c->gmail,
                    'telefono' => $c->telefono,
                    'rfc' => $c->rfc,
                    'direccion' => $c->direccion,
                    'categoria' => $c->category?->nombre,
                    'asesor' => $c->asesor?->name,
                    'conocido' => $c->comoConocio(),
                    'activo' => (bool) $c->activo,
                    'comentarios' => $c->comentarios,

                    // Historial: para saber con quién se está tratando antes
                    // de armarle otra venta.
                    'compras' => $historial['compras'],
                    'cotizaciones' => $historial['cotizaciones'],
                    'saldo' => $historial['saldo'],
                    'ultima_compra' => $historial['ultima_compra']?->format('d/m/Y'),
                ];
            });

        return response()->json($clientes);
    }

    public function buscarProductos(Request $request): JsonResponse
    {
        $q = trim($request->get('q', ''));

        $productos = $this->buscador->productos($q)
            ->map(fn (Producto $p) => [
                'id' => $p->id,
                'tipo_item' => 'producto',
                'nombre' => $p->nombreVisible(),
                'modelo' => $p->modelo,
                'marca' => $p->marca,
                'precio' => (float) $p->precio,
                'imagen' => $p->imagen_path ? asset('storage/'.$p->imagen_path) : null,

                /*
                | Cuánto hay y cuáles son. El stock ya excluye lo vendido y
                | lo que sigue en hojalatería o mantenimiento, así que aquí
                | solo aparecen piezas que de verdad se pueden entregar.
                */
                'stock' => (int) $p->stock,
                'piezas' => $p->serialesDisponibles->map(fn ($s) => [
                    'id' => $s->id,
                    'codigo' => $s->codigo,
                    'no_serie' => $s->no_serie,
                    'condicion' => $s->condicion,
                    // Se puede vender, pero no está en el almacén: quien
                    // arma la venta necesita saberlo antes de elegirla.
                    'congreso' => $s->congress?->nombre,
                ])->values(),

                // Si el producto tiene su ficha técnica, se adjunta sola al agregarlo.
                'ficha' => $p->fichaTecnica ? ['id' => $p->fichaTecnica->id, 'titulo' => $p->fichaTecnica->titulo] : null,
            ]);

        $paquetes = $this->buscador->paquetes($q)
            ->map(fn (Paquete $p) => [
                'id' => $p->id,
                'tipo_item' => 'paquete',
                'nombre' => $p->nombre,
                'modelo' => 'PAQUETE',
                'marca' => $this->buscador->marcasDe($p),
                'precio' => $p->precioCalculado(),
                'imagen' => $p->imagen ? asset('storage/'.$p->imagen) : null,
            ]);

        return response()->json($paquetes->concat($productos)->values());
    }

    public function buscarFichas(Request $request): JsonResponse
    {
        $q = trim($request->get('q', ''));

        $fichas = $this->buscador->fichas($q)
            ->map(fn (FichaTecnica $f) => [
                'id' => $f->id,
                'titulo' => $f->titulo,
            ]);

        return response()->json($fichas);
    }
}
