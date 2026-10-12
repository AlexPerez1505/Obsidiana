<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\FichaTecnica;
use App\Models\Paquete;
use App\Services\Inventario\FichasTecnicas;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Fichas tecnicas: un nombre y su PDF.
 *
 * La tabla y el modelo ya existian, y las cotizaciones y ventas ya podian
 * adjuntar fichas, pero no habia por donde darlas de alta. Esto lo cubre.
 * Las reglas viven en FichasTecnicas, compartidas con la API.
 */
class FichaTecnicaController extends Controller
{
    public function __construct(private FichasTecnicas $fichas)
    {
    }

    public function index(): View
    {
        // El filtrado va del lado del navegador, igual que en Clientes: la
        // búsqueda, los filtros y el cambio de vista trabajan sobre la misma
        // lista sin recargar.
        $fichas = FichaTecnica::with(['producto', 'paquete'])->orderBy('titulo')->get();

        return view('structure.gestion_Inventario.fichas.index', [
            'fichas' => $fichas,
            'total' => $fichas->count(),
            'conPdf' => $fichas->whereNotNull('archivo')->count(),
        ]);
    }

    public function create(): View
    {
        return view('structure.gestion_Inventario.fichas.form', [
            'ficha' => new FichaTecnica(['activo' => true]),
            'productos' => $this->fichas->productosDisponibles(),
            'paquetes' => $this->paquetes(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validado($request, obligaArchivo: true);

        $this->fichas->crear($data, $request->file('archivo'));

        return redirect()->route('inventory.fichas.index')
            ->with('status', 'Ficha técnica guardada correctamente.');
    }

    public function edit(FichaTecnica $ficha): View
    {
        return view('structure.gestion_Inventario.fichas.form', [
            'ficha' => $ficha,
            'productos' => $this->fichas->productosDisponibles($ficha),
            'paquetes' => $this->paquetes(),
        ]);
    }

    public function update(Request $request, FichaTecnica $ficha): RedirectResponse
    {
        // Al editar, el PDF es opcional: si no mandan uno nuevo se conserva.
        $data = $this->validado($request, obligaArchivo: false, ficha: $ficha);

        $this->fichas->actualizar($ficha, $data, $request->hasFile('archivo') ? $request->file('archivo') : null);

        return redirect()->route('inventory.fichas.index')
            ->with('status', 'Ficha técnica actualizada correctamente.');
    }

    public function destroy(FichaTecnica $ficha): RedirectResponse
    {
        $this->fichas->eliminar($ficha);

        return redirect()->route('inventory.fichas.index')
            ->with('status', 'Ficha técnica eliminada correctamente.');
    }

    /** Descarga el PDF con un nombre legible, no con el aleatorio del disco. */
    public function download(FichaTecnica $ficha)
    {
        return $this->fichas->descargar($ficha);
    }

    private function validado(Request $request, bool $obligaArchivo, ?FichaTecnica $ficha = null): array
    {
        $data = $request->validate(FichasTecnicas::reglas($obligaArchivo), FichasTecnicas::MENSAJES);

        $data['activo'] = $request->boolean('activo');
        [$data['producto_id'], $data['paquete_id']] = FichasTecnicas::descomponerItem($data['item'] ?? null);
        unset($data['item']);

        // Cada producto solo puede tener una ficha técnica relacionada.
        $this->fichas->exigirUnicaPorProducto($data['producto_id'], $ficha);

        // El archivo se resuelve aparte, segun sea alta o edicion.
        unset($data['archivo']);

        return $data;
    }

    /** @return \Illuminate\Support\Collection<int, Paquete> */
    private function paquetes()
    {
        return Paquete::orderBy('nombre')->get();
    }
}
