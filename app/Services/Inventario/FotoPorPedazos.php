<?php

namespace App\Services\Inventario;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * Las fotos de la entrada se suben en pedazos, igual que el video.
 *
 * Así no les pega el límite de subida del servidor: una foto de 30 MB de un
 * celular o una cámara pasa igual que una de 1 MB, y se acepta cualquier
 * formato de imagen (HEIC, TIFF, RAW, WEBP, AVIF...). Las HEIC del iPhone el
 * navegador las convierte a JPG antes de subirlas para que se puedan ver en
 * cualquier pantalla; lo demás se guarda tal como llegó.
 *
 * Cada foto se sube en cuanto se elige, y el formulario solo manda su ruta.
 * Por eso tampoco se pierden si el servidor regresa el formulario con error.
 */
class FotoPorPedazos
{
    public const PEDAZO_MAX_KB = 5120; // 5MB por pedazo

    public const CARPETA = 'inventario/fotos';

    /** Formatos de imagen que el navegador no siempre reconoce como image/*, pero lo son. */
    private const RAW = ['heic', 'heif', 'dng', 'cr2', 'cr3', 'nef', 'arw', 'orf', 'rw2', 'raf', 'srw', 'pef'];

    /** Nunca se guardan, aunque digan ser imagen: se podrían ejecutar o abrir como página. */
    private const PROHIBIDAS = ['php', 'phtml', 'phar', 'html', 'htm', 'svg', 'svgz', 'js', 'xml', 'exe', 'sh'];

    public static function reglas(): array
    {
        return [
            'chunk' => ['required', 'file', 'max:'.self::PEDAZO_MAX_KB],
            'upload_id' => ['required', 'string', 'regex:/^[a-zA-Z0-9\-]{8,64}$/'],
            'index' => ['required', 'integer', 'min:0'],
            'total' => ['required', 'integer', 'min:1'],
            'extension' => ['required', 'string', 'regex:/^[a-z0-9]{2,5}$/'],
        ];
    }

    /**
     * Guarda un pedazo y, si es el último, arma la foto.
     *
     * @return array{status: string, foto_path?: string, url?: string}
     *
     * @throws ValidationException si faltan pedazos o el archivo no es una imagen
     */
    public function recibir(UploadedFile $pedazo, string $uploadId, int $index, int $total, string $extension): array
    {
        if (in_array($extension, self::PROHIBIDAS, true)) {
            throw ValidationException::withMessages(['chunk' => 'Ese tipo de archivo no se acepta como foto.']);
        }

        $disco = config('filesystems.fotos_disk', 'public');
        $temporal = "inventario/tmp_fotos/{$uploadId}";

        Storage::disk($disco)->put("{$temporal}/{$index}.part", $pedazo->get());

        if ($index < $total - 1) {
            return ['status' => 'chunk_recibido'];
        }

        for ($i = 0; $i < $total; $i++) {
            if (! Storage::disk($disco)->exists("{$temporal}/{$i}.part")) {
                throw ValidationException::withMessages(['chunk' => 'Faltan pedazos de la foto, vuelve a subirla.']);
            }
        }

        $path = self::CARPETA.'/'.uniqid('foto_', true).'.'.$extension;
        $absoluta = Storage::disk($disco)->path($path);

        if (! is_dir(dirname($absoluta))) {
            mkdir(dirname($absoluta), 0755, true);
        }

        $destino = fopen($absoluta, 'wb');
        for ($i = 0; $i < $total; $i++) {
            $origen = fopen(Storage::disk($disco)->path("{$temporal}/{$i}.part"), 'rb');
            stream_copy_to_stream($origen, $destino);
            fclose($origen);
        }
        fclose($destino);

        Storage::disk($disco)->deleteDirectory($temporal);

        if (! $this->esImagen($absoluta, $extension)) {
            Storage::disk($disco)->delete($path);

            throw ValidationException::withMessages(['chunk' => 'Ese archivo no es una imagen.']);
        }

        return ['status' => 'listo', 'foto_path' => $path, 'url' => Storage::disk($disco)->url($path)];
    }

    /** Se revisa el contenido, no solo el nombre. */
    private function esImagen(string $absoluta, string $extension): bool
    {
        $mime = (string) (new \finfo(FILEINFO_MIME_TYPE))->file($absoluta);

        if (str_starts_with($mime, 'image/')) {
            return $mime !== 'image/svg+xml';
        }

        // Los RAW y HEIC a veces no los reconoce el sistema: basta con que no
        // sean texto (una página o un script disfrazado).
        return in_array($extension, self::RAW, true) && ! str_starts_with($mime, 'text/');
    }

    /**
     * Confirma que la ruta que mandó el formulario sea una foto ya armada por
     * recibir(), y no cualquier ruta del disco.
     */
    public function resolver(string $path, ?string $disco = null): ?string
    {
        $disco ??= config('filesystems.fotos_disk', 'public');

        if (! preg_match('#^'.preg_quote(self::CARPETA, '#').'/foto_[a-zA-Z0-9.]+\.[a-z0-9]{2,5}$#', $path)) {
            return null;
        }

        return Storage::disk($disco)->exists($path) ? $path : null;
    }
}
