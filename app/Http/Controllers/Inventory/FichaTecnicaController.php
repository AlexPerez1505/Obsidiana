<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Equipo;
use App\Models\FichaTecnica;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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
        $fichas = FichaTecnica::with('equipo')->orderBy('titulo')->get();

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
            'equipos' => $this->equipos(),
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
            'equipos' => $this->equipos(),
        ]);
    }

    public function update(Request $request, FichaTecnica $ficha): RedirectResponse
    {
        // Al editar, el PDF es opcional: si no mandan uno nuevo se conserva.
        $data = $this->validado($request, obligaArchivo: false);

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

    private function validado(Request $request, bool $obligaArchivo): array
    {
        $reglaArchivo = $obligaArchivo ? ['required'] : ['nullable'];

        $data = $request->validate([
            'titulo' => ['required', 'string', 'max:255'],
            'equipo_id' => ['nullable', 'exists:equipos,id'],
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

        // El archivo se resuelve aparte, segun sea alta o edicion.
        unset($data['archivo']);

        return $data;
    }

    private function borrarArchivo(FichaTecnica $ficha): void
    {
        if ($ficha->archivo && Storage::disk('public')->exists($ficha->archivo)) {
            Storage::disk('public')->delete($ficha->archivo);
        }
    }

    /** @return \Illuminate\Support\Collection<int, Equipo> */
    private function equipos()
    {
        return Equipo::orderBy('marca')->orderBy('modelo')->get();
    }
}
