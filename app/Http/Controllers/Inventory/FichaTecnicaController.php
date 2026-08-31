<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\FichaTecnica;
use App\Models\Paquete;
use App\Models\Producto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Fichas tecnicas: un nombre y su PDF.
 *
 * La tabla y el modelo ya existian, y las cotizaciones y ventas ya podian
 * adjuntar fichas, pero no habia por donde darlas de alta. Esto lo cubre.
 */
class FichaTecnicaController extends Controller
{
    private const CARPETA = 'fichas';

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
            'productos' => $this->productos(),
            'paquetes' => $this->paquetes(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validado($request, obligaArchivo: true);

        $data['archivo'] = $request->file('archivo')->store(self::CARPETA, 'public');

        FichaTecnica::create($data);

        return redirect()->route('inventory.fichas.index')
            ->with('status', 'Ficha técnica guardada correctamente.');
    }

    public function edit(FichaTecnica $ficha): View
    {
        return view('structure.gestion_Inventario.fichas.form', [
            'ficha' => $ficha,
            'productos' => $this->productos($ficha),
            'paquetes' => $this->paquetes(),
        ]);
    }

    public function update(Request $request, FichaTecnica $ficha): RedirectResponse
    {
        // Al editar, el PDF es opcional: si no mandan uno nuevo se conserva.
        $data = $this->validado($request, obligaArchivo: false, ficha: $ficha);

        if ($request->hasFile('archivo')) {
            $this->borrarArchivo($ficha);
            $data['archivo'] = $request->file('archivo')->store(self::CARPETA, 'public');
        }

        $ficha->update($data);

        return redirect()->route('inventory.fichas.index')
            ->with('status', 'Ficha técnica actualizada correctamente.');
    }

    public function destroy(FichaTecnica $ficha): RedirectResponse
    {
        $this->borrarArchivo($ficha);

        $ficha->delete();

        return redirect()->route('inventory.fichas.index')
            ->with('status', 'Ficha técnica eliminada correctamente.');
    }

    /** Descarga el PDF con un nombre legible, no con el aleatorio del disco. */
    public function download(FichaTecnica $ficha)
    {
        abort_if(! $ficha->archivo || ! Storage::disk('public')->exists($ficha->archivo), 404);

        $nombre = str($ficha->titulo)->slug()->value() . '.pdf';

        return Storage::disk('public')->download($ficha->archivo, $nombre);
    }

    private function validado(Request $request, bool $obligaArchivo, ?FichaTecnica $ficha = null): array
    {
        $reglaArchivo = $obligaArchivo ? ['required'] : ['nullable'];

        $data = $request->validate([
            'titulo' => ['required', 'string', 'max:255'],
            'item' => ['nullable', 'string'],
            'contenido' => ['nullable', 'string', 'max:5000'],
            'activo' => ['nullable', 'boolean'],
            // Sin tope de peso propio: el unico limite es el del servidor
            // (upload_max_filesize), que se avisa aparte en bootstrap/app.php.
            'archivo' => array_merge($reglaArchivo, ['file', 'mimetypes:application/pdf']),
        ], [
            'archivo.required' => 'Sube el PDF de la ficha técnica.',
            'archivo.mimetypes' => 'El archivo debe ser un PDF.',
            'archivo.uploaded' => 'El archivo no se pudo subir: pasa del límite del servidor.',
        ]);

        $data['activo'] = $request->boolean('activo');
        [$data['producto_id'], $data['paquete_id']] = $this->descomponerItem($data['item'] ?? null);
        unset($data['item']);

        // Cada producto solo puede tener una ficha técnica relacionada.
        if ($data['producto_id']) {
            $yaTieneFicha = FichaTecnica::where('producto_id', $data['producto_id'])
                ->when($ficha, fn ($q) => $q->whereKeyNot($ficha->id))
                ->exists();

            if ($yaTieneFicha) {
                throw ValidationException::withMessages([
                    'item' => 'Ese producto ya tiene otra ficha técnica asignada.',
                ]);
            }
        }

        // El archivo se resuelve aparte, segun sea alta o edicion.
        unset($data['archivo']);

        return $data;
    }

    /** El select manda "producto:5" o "paquete:3"; aquí se separa en sus dos columnas. */
    private function descomponerItem(?string $item): array
    {
        if (! $item || ! str_contains($item, ':')) {
            return [null, null];
        }

        [$tipo, $id] = explode(':', $item, 2);

        return match ($tipo) {
            'producto' => [(int) $id, null],
            'paquete' => [null, (int) $id],
            default => [null, null],
        };
    }

    private function borrarArchivo(FichaTecnica $ficha): void
    {
        if ($ficha->archivo && Storage::disk('public')->exists($ficha->archivo)) {
            Storage::disk('public')->delete($ficha->archivo);
        }
    }

    /**
     * Solo se ofrecen productos sin ficha todavía (más el que ya tiene
     * asignado esta ficha, si se está editando), ya que cada producto
     * solo puede tener una ficha técnica relacionada.
     *
     * @return \Illuminate\Support\Collection<int, Producto>
     */
    private function productos(?FichaTecnica $ficha = null)
    {
        return Producto::orderBy('marca')->orderBy('modelo')
            ->where(function ($query) use ($ficha) {
                $query->whereDoesntHave('fichaTecnica');

                if ($ficha?->producto_id) {
                    $query->orWhereKey($ficha->producto_id);
                }
            })
            ->get();
    }

    /** @return \Illuminate\Support\Collection<int, Paquete> */
    private function paquetes()
    {
        return Paquete::orderBy('nombre')->get();
    }
}
