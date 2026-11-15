<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Concerns\ConstruyeCatalogoEquipo;
use App\Http\Controllers\Controller;
use App\Models\FichaObservacion;
use App\Models\FichaTecnica;
use App\Models\Paquete;
use App\Models\Producto;
use App\Models\User;
use App\Services\Inventario\FichasTecnicas;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Fichas tecnicas: un nombre y su PDF.
 *
 * Se clasifican con el catálogo del equipo (tipo, subtipo, marca y modelo,
 * obligatorios) y con eso se ligan solas al producto que coincide. Desde
 * la lista se les dejan notas y observaciones dirigidas a un usuario, que
 * le llegan a su campana. Las reglas viven en FichasTecnicas, compartidas
 * con la API.
 */
class FichaTecnicaController extends Controller
{
    use ConstruyeCatalogoEquipo;

    public function __construct(private FichasTecnicas $fichas)
    {
    }

    public function index(Request $request): View
    {
        // Si ya se dio de alta el producto de su modelo, la ficha se liga sola.
        $this->fichas->ligarPendientes();

        // El filtrado va del lado del navegador, igual que en Clientes: la
        // búsqueda, los filtros y el cambio de vista trabajan sobre la misma
        // lista sin recargar.
        $fichas = FichaTecnica::with([
            'producto', 'paquete', 'tipoEquipo', 'subtipo', 'marca', 'modelo',
            'observaciones.autor', 'observaciones.para', 'observaciones.atendio',
        ])->orderBy('titulo')->get();

        $yo = $request->user();

        return view('structure.gestion_Inventario.fichas.index', [
            'fichas' => $fichas,
            'total' => $fichas->count(),
            'conPdf' => $fichas->whereNotNull('archivo')->count(),
            'usuarios' => User::where('status', User::STATUS_APPROVED)->orderBy('name')->get(['id', 'name']),
            'abrirObservaciones' => $request->integer('observaciones') ?: null,
            'observaciones' => $fichas->mapWithKeys(fn (FichaTecnica $f) => [
                $f->id => $f->observaciones->map(fn (FichaObservacion $o) => [
                    'id' => $o->id,
                    'texto' => $o->texto,
                    'autor' => $o->autor?->name ?? 'Usuario eliminado',
                    'para' => $o->para?->name ?? 'Usuario eliminado',
                    'fecha' => $o->created_at?->format('d/m/Y H:i'),
                    'atendida' => $o->atendida_en?->format('d/m/Y H:i'),
                    'atendio' => $o->atendio?->name,
                    'puede_atender' => ! $o->atendida() && $this->fichas->puedeAtender($o, $yo),
                    'url_atender' => route('inventory.fichas.observacion.atender', $o),
                ])->values(),
            ]),
        ]);
    }

    public function create(): View
    {
        return $this->formulario(new FichaTecnica(['activo' => true]));
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
        return $this->formulario($ficha);
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

    /** Nota u observación para un usuario: queda en la ficha y le llega a su campana. */
    public function observar(Request $request, FichaTecnica $ficha): RedirectResponse
    {
        $data = $request->validate(FichasTecnicas::REGLAS_OBSERVACION, FichasTecnicas::MENSAJES_OBSERVACION);

        $obs = $this->fichas->observar($ficha, $request->user(), $data);

        return redirect()->route('inventory.fichas.index', ['observaciones' => $ficha->id])
            ->with('status', 'Observación enviada a '.($obs->para?->name ?? 'el usuario').'.');
    }

    public function atender(Request $request, FichaObservacion $observacion): RedirectResponse
    {
        abort_unless($this->fichas->puedeAtender($observacion, $request->user()), 403);

        $this->fichas->atender($observacion, $request->user());

        return redirect()->route('inventory.fichas.index', ['observaciones' => $observacion->ficha_tecnica_id])
            ->with('status', 'Observación marcada como atendida.');
    }

    private function formulario(FichaTecnica $ficha): View
    {
        // Fichas de antes sin catálogo: se proponen los datos del producto ligado.
        if (! $ficha->equipment_model_id && $ficha->producto) {
            $ficha->fill($ficha->producto->only(['equipment_type_id', 'subtype_id', 'brand_id', 'equipment_model_id']));
        }

        return view('structure.gestion_Inventario.fichas.form', [
            'ficha' => $ficha,
            'catalogo' => $this->catalogoEquipo(),
            'paquetes' => Paquete::orderBy('nombre')->get(),
            // Qué producto le toca a cada modelo, para decirlo antes de guardar.
            'productosPorModelo' => Producto::whereNotNull('equipment_model_id')->get(['id', 'equipment_model_id', 'marca', 'modelo'])
                ->mapWithKeys(fn (Producto $p) => [$p->equipment_model_id => trim($p->marca.' '.$p->modelo) ?: 'Producto #'.$p->id]),
        ]);
    }

    private function validado(Request $request, bool $obligaArchivo, ?FichaTecnica $ficha = null): array
    {
        $data = $request->validate(FichasTecnicas::reglas($obligaArchivo, exigeCatalogo: true), FichasTecnicas::MENSAJES);

        $data['activo'] = $request->boolean('activo');

        // El producto sale del modelo elegido: es el que coincide en el inventario.
        $data['producto_id'] = FichasTecnicas::productoDelModelo((int) $data['equipment_model_id'])?->id;
        $data['paquete_id'] = $data['paquete_id'] ?? null;
        unset($data['item']);

        // Cada producto solo puede tener una ficha técnica relacionada.
        $this->fichas->exigirUnicaPorProducto($data['producto_id'], $ficha, 'equipment_model_id',
            'El producto de ese modelo ya tiene otra ficha técnica.');

        // El archivo se resuelve aparte, segun sea alta o edicion.
        unset($data['archivo']);

        return $data;
    }
}
