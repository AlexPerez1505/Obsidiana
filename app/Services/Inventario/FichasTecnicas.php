<?php

namespace App\Services\Inventario;

use App\Models\FichaObservacion;
use App\Models\FichaTecnica;
use App\Models\Producto;
use App\Models\User;
use App\Notifications\ObservacionDeFicha;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
        'equipment_type_id.required' => 'Elige el tipo de equipo.',
        'subtype_id.required' => 'Elige el subtipo.',
        'brand_id.required' => 'Elige la marca.',
        'equipment_model_id.required' => 'Elige el modelo.',
    ];

    /** Reglas de una nota u observación dirigida a un usuario. */
    public const REGLAS_OBSERVACION = [
        'para_id' => ['required', 'exists:users,id'],
        'texto' => ['required', 'string', 'max:2000'],
    ];

    public const MENSAJES_OBSERVACION = [
        'para_id.required' => 'Elige a quién le llega la observación.',
        'texto.required' => 'Escribe la observación.',
    ];

    /**
     * Al editar, el PDF es opcional: si no mandan uno nuevo se conserva.
     * En la pantalla el catálogo del equipo es obligatorio ($exigeCatalogo);
     * la API lo acepta pero no lo exige, para no romper a quien ya la usa.
     */
    public static function reglas(bool $obligaArchivo, bool $exigeCatalogo = false): array
    {
        $catalogo = $exigeCatalogo ? 'required' : 'nullable';

        return [
            'equipment_type_id' => [$catalogo, 'exists:equipment_types,id'],
            'subtype_id' => [$catalogo, 'exists:subtypes,id'],
            'brand_id' => [$catalogo, 'exists:brands,id'],
            'equipment_model_id' => [$catalogo, 'exists:equipment_models,id'],
            'paquete_id' => ['nullable', 'exists:paquetes,id'],
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
    public function exigirUnicaPorProducto(?int $productoId, ?FichaTecnica $ficha = null, string $campo = 'item', ?string $mensaje = null): void
    {
        if (! $productoId) {
            return;
        }

        $yaTieneFicha = FichaTecnica::where('producto_id', $productoId)
            ->when($ficha, fn ($q) => $q->whereKeyNot($ficha->id))
            ->exists();

        if ($yaTieneFicha) {
            throw ValidationException::withMessages([
                $campo => $mensaje ?? 'Ese producto ya tiene otra ficha técnica asignada.',
            ]);
        }
    }

    /**
     * El producto del inventario que corresponde a ese modelo (cada modelo
     * vive en un solo producto), o null si todavía no hay ninguno.
     */
    public static function productoDelModelo(?int $modeloId): ?Producto
    {
        return $modeloId ? Producto::where('equipment_model_id', $modeloId)->first() : null;
    }

    /**
     * Fichas con catálogo pero sin producto: si ya se dio de alta el
     * producto de ese modelo (y no tiene otra ficha), se ligan solas.
     */
    public function ligarPendientes(): int
    {
        $ligadas = 0;

        $pendientes = FichaTecnica::whereNull('producto_id')
            ->whereNotNull('equipment_model_id')
            ->get(['id', 'equipment_model_id']);

        if ($pendientes->isEmpty()) {
            return 0;
        }

        $modelos = $pendientes->pluck('equipment_model_id')->unique()->values();
        $productos = Producto::whereIn('equipment_model_id', $modelos)
            ->get(['id', 'equipment_model_id'])
            ->keyBy('equipment_model_id');

        $ocupados = FichaTecnica::whereIn('producto_id', $productos->pluck('id'))
            ->pluck('producto_id')
            ->flip();

        foreach ($pendientes as $ficha) {
            $producto = $productos->get($ficha->equipment_model_id);
            if (! $producto || $ocupados->has($producto->id)) {
                continue;
            }

            $ficha->update(['producto_id' => $producto->id]);
            $ocupados->put($producto->id, true);
            $ligadas++;
        }

        return $ligadas;
    }

    /** Deja la observación y avisa a quien va dirigida (campana y, si hay, correo). */
    public function observar(FichaTecnica $ficha, User $autor, array $data): FichaObservacion
    {
        $obs = $ficha->observaciones()->create([
            'autor_id' => $autor->id,
            'para_id' => (int) $data['para_id'],
            'texto' => trim($data['texto']),
        ]);

        if ($para = $obs->para) {
            try {
                $para->notify(new ObservacionDeFicha($obs->load(['ficha', 'autor'])));
            } catch (\Throwable $e) {
                // La observación ya quedó guardada; el aviso es un extra.
                Log::warning('Observación de ficha: no se pudo avisar', ['obs' => $obs->id, 'error' => $e->getMessage()]);
            }
        }

        return $obs;
    }

    /** Solo quien la recibió o quien puede editar fichas la da por atendida. */
    public function puedeAtender(FichaObservacion $obs, User $u): bool
    {
        return (int) $obs->para_id === (int) $u->id || $u->can('inventario.editar');
    }

    public function atender(FichaObservacion $obs, User $u): void
    {
        $obs->update(['atendida_en' => now(), 'atendida_por' => $u->id]);

        // La campana del aviso deja de estar pendiente.
        DB::table('notifications')->whereNull('read_at')
            ->where('data->observacion_id', $obs->id)->update(['read_at' => now()]);
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
