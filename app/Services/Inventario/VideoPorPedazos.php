<?php

namespace App\Services\Inventario;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * El video de verificación de una pieza se sube en pedazos chicos para no
 * mandar un archivo pesado de golpe (evita timeouts y fallos por
 * conexiones lentas). Cuando llega el último pedazo se ensamblan todos en
 * un solo archivo y se regresa su ruta; el alta de la entrada solo manda
 * esa ruta como texto, no el video completo otra vez.
 *
 * Lo usan la pantalla de Entrada y la API, para que las dos acepten
 * exactamente lo mismo.
 */
class VideoPorPedazos
{
    public const EXTENSIONES = ['mp4', 'mov', 'm4v', 'webm'];

    public const PEDAZO_MAX_KB = 5120; // 5MB por pedazo

    public const MAX_BYTES = 150 * 1024 * 1024; // 150MB ya ensamblado

    public static function reglas(): array
    {
        return [
            'chunk' => ['required', 'file', 'max:'.self::PEDAZO_MAX_KB],
            'upload_id' => ['required', 'string', 'regex:/^[a-zA-Z0-9\-]{8,64}$/'],
            'index' => ['required', 'integer', 'min:0'],
            'total' => ['required', 'integer', 'min:1'],
            'extension' => ['required', 'string', Rule::in(self::EXTENSIONES)],
        ];
    }

    /**
     * Guarda un pedazo y, si es el último, ensambla el video.
     *
     * @return array{status: string, video_path?: string}
     *
     * @throws ValidationException si faltan pedazos o el video pasa del límite
     */
    public function recibir(UploadedFile $pedazo, string $uploadId, int $index, int $total, string $extension): array
    {
        $disco = config('filesystems.fotos_disk', 'public');
        $carpetaTemporal = "inventario/tmp_videos/{$uploadId}";

        Storage::disk($disco)->put("{$carpetaTemporal}/{$index}.part", $pedazo->get());

        if ($index < $total - 1) {
            return ['status' => 'chunk_recibido'];
        }

        // Llegó el último pedazo: revisa que no falte ninguno y los ensambla
        // en orden, en streaming (sin cargar el video completo a memoria).
        for ($i = 0; $i < $total; $i++) {
            if (! Storage::disk($disco)->exists("{$carpetaTemporal}/{$i}.part")) {
                throw ValidationException::withMessages(['chunk' => 'Faltan pedazos del video, vuelve a subirlo.']);
            }
        }

        $pathFinal = 'inventario/entradas/'.uniqid('video_').'.'.$extension;
        $rutaAbsoluta = Storage::disk($disco)->path($pathFinal);

        if (! is_dir(dirname($rutaAbsoluta))) {
            mkdir(dirname($rutaAbsoluta), 0755, true);
        }

        $destino = fopen($rutaAbsoluta, 'wb');
        for ($i = 0; $i < $total; $i++) {
            $origen = fopen(Storage::disk($disco)->path("{$carpetaTemporal}/{$i}.part"), 'rb');
            stream_copy_to_stream($origen, $destino);
            fclose($origen);
        }
        fclose($destino);

        Storage::disk($disco)->deleteDirectory($carpetaTemporal);

        if (filesize($rutaAbsoluta) > self::MAX_BYTES) {
            Storage::disk($disco)->delete($pathFinal);

            throw ValidationException::withMessages(['chunk' => 'El video no debe pesar más de 150MB en total.']);
        }

        return ['status' => 'listo', 'video_path' => $pathFinal];
    }

    /**
     * Confirma que la ruta que mandó el formulario sea efectivamente un
     * video ya ensamblado por recibir() (evita que manden cualquier ruta
     * arbitraria del disco).
     */
    public function resolver(string $path, ?string $disco = null): ?string
    {
        $disco ??= config('filesystems.fotos_disk', 'public');
        $extensiones = implode('|', self::EXTENSIONES);

        if (! preg_match('#^inventario/entradas/video_[a-zA-Z0-9.]+\.('.$extensiones.')$#', $path)) {
            return null;
        }

        return Storage::disk($disco)->exists($path) ? $path : null;
    }
}
