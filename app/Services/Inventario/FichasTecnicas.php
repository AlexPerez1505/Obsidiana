<?php

namespace App\Services\Inventario;

use App\Models\FichaTecnica;
use App\Models\Producto;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Fichas técnicas: un nombre y su PDF, ligadas a un producto o a un paquete.
 *
 * Las reglas (cada producto tiene a lo más una ficha, el PDF vive en el
 * disco público, al reemplazarlo se borra el viejo) están aquí para que la
 * web y la API digan lo mismo.
 */
class FichasTecnicas
{
    public const CARPETA = 'fichas';

    public const MENSAJES = [
        'archivo.required' => 'Sube el PDF de la ficha técnica.',
        'archivo.mimetypes' => 'El archivo debe ser un PDF.',
        'archivo.uploaded' => 'El archivo no se pudo subir: pasa del límite del servidor.',
    ];

    /** Al editar, el PDF es opcional: si no mandan uno nuevo se conserva. */
    public static function reglas(bool $obligaArchivo): array
    {
        return [
            'titulo' => ['required', 'string', 'max:255'],
            'item' => ['nullable', 'string'],
            'contenido' => ['nullable', 'string', 'max:5000'],
            'activo' => ['nullable', 'boolean'],
            // Sin tope de peso propio: el unico limite es el del servidor
            // (upload_max_filesize), que se avisa aparte en bootstrap/app.php.
            'archivo' => [$obligaArchivo ? 'required' : 'nullable', 'file', 'mimetypes:application/pdf'],
        ];
    }

    /** El select manda "producto:5" o "paquete:3"; aquí se separa en sus dos columnas. */
    public static function descomponerItem(?string $item): array
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

    /**
     * Cada producto solo puede tener una ficha técnica relacionada.
     *
     * @throws ValidationException sobre $campo si ese producto ya tiene otra
     */
    public function exigirUnicaPorProducto(?int $productoId, ?FichaTecnica $ficha = null, string $campo = 'item'): void
    {
        if (! $productoId) {
            return;
        }

        $yaTieneFicha = FichaTecnica::where('producto_id', $productoId)
            ->when($ficha, fn ($q) => $q->whereKeyNot($ficha->id))
            ->exists();

        if ($yaTieneFicha) {
            throw ValidationException::withMessages([
                $campo => 'Ese producto ya tiene otra ficha técnica asignada.',
            ]);
        }
    }

    public function crear(array $data, UploadedFile $archivo): FichaTecnica
    {
        $data['archivo'] = $archivo->store(self::CARPETA, 'public');

        return FichaTecnica::create($data);
    }

    public function actualizar(FichaTecnica $ficha, array $data, ?UploadedFile $archivo): FichaTecnica
    {
        if ($archivo) {
            $this->borrarArchivo($ficha);
            $data['archivo'] = $archivo->store(self::CARPETA, 'public');
        }

        $ficha->update($data);

        return $ficha;
    }

    public function eliminar(FichaTecnica $ficha): void
    {
        $this->borrarArchivo($ficha);

        $ficha->delete();
    }

    public function tienePdf(FichaTecnica $ficha): bool
    {
        return $ficha->archivo && Storage::disk('public')->exists($ficha->archivo);
    }

    /** Descarga el PDF con un nombre legible, no con el aleatorio del disco. */
    public function descargar(FichaTecnica $ficha): StreamedResponse
    {
        abort_if(! $this->tienePdf($ficha), 404);

        $nombre = str($ficha->titulo)->slug()->value().'.pdf';

        return Storage::disk('public')->download($ficha->archivo, $nombre);
    }

    /**
     * Solo se ofrecen productos sin ficha todavía (más el que ya tiene
     * asignado esta ficha, si se está editando), ya que cada producto
     * solo puede tener una ficha técnica relacionada.
     *
     * @return Collection<int, Producto>
     */
    public function productosDisponibles(?FichaTecnica $ficha = null): Collection
    {
        return Producto::orderBy('marca')->orderBy('modelo')
            ->where(function ($query) use ($ficha) {
                $query->whereDoesntHave('fichaTecnica');

                // orWhereKey() no existe en el builder: editar una ficha ya
                // ligada a un producto tronaba con 500.
                if ($ficha?->producto_id) {
                    $query->orWhere($query->getModel()->getQualifiedKeyName(), $ficha->producto_id);
                }
            })
            ->get();
    }

    private function borrarArchivo(FichaTecnica $ficha): void
    {
        if ($ficha->archivo && Storage::disk('public')->exists($ficha->archivo)) {
            Storage::disk('public')->delete($ficha->archivo);
        }
    }
}
