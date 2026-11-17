<?php

namespace App\Services\Almacen;

use App\Models\OrdenSalida;
use App\Models\OrdenSalidaEvidencia;
use App\Services\Inventario\FotoPorPedazos;
use App\Services\Inventario\VideoPorPedazos;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Fotos y videos de evidencia de una orden de salida.
 *
 * No hay tope de cuántas fotos junta una orden: cada foto viaja en su
 * propia petición (la pantalla las manda una por una) y el video en
 * pedazos, así ninguna petición rebasa el límite del servidor por más
 * evidencia que se acumule. Lo nuevo siempre se suma a lo que ya había.
 *
 * Vive aquí porque lo usan la web y la API, y las dos tienen que aceptar
 * lo mismo y decir que no por las mismas razones.
 */
class EvidenciaDeSalida
{
    /** Tope por archivo cuando la foto llega entera (la API). La pantalla la manda por pedazos, sin tope. */
    public const FOTO_MAX_KB = 51200; // 50MB

    /**
     * Una foto por petición: lo que manda la pantalla. Llega ya armada por
     * pedazos (foto_path) en cualquier formato (HEIC, TIFF, RAW, AVIF…), o
     * como archivo entero. Solo se rechaza lo que no es foto (texto,
     * ejecutables, SVG…).
     */
    /**
     * ¿Es una foto? Todo lo que el servidor reconoce como imagen, más lo que
     * no reconoce (RAW de cámara, algunos HEIC): un PDF o un Word no pasan.
     */
    public static function esFoto(string $mime): bool
    {
        return str_starts_with($mime, 'image/') && $mime !== 'image/svg+xml'
            || in_array($mime, ['application/octet-stream', ''], true);
    }

    private static function reglaEsFoto(): \Closure
    {
        return function (string $campo, $archivo, \Closure $falla) {
            if (! self::esFoto((string) $archivo?->getMimeType())) {
                $falla('Ese archivo no es una foto.');
            }
        };
    }

    public static function reglasFoto(): array
    {
        return [
            'foto' => ['required_without:foto_path', 'nullable', 'file', 'max:'.self::FOTO_MAX_KB, FotoPorPedazos::reglaArchivo(), self::reglaEsFoto()],
            'foto_path' => ['required_without:foto', 'nullable', 'string', 'max:255'],
            'nombre' => ['nullable', 'string', 'max:255'],
        ];
    }

    /** Una o varias por petición: la API. Mismos formatos que la pantalla. */
    public static function reglasFotos(): array
    {
        return [
            'fotos' => ['required', 'array', 'min:1'],
            'fotos.*' => ['required', 'file', 'max:'.self::FOTO_MAX_KB, FotoPorPedazos::reglaArchivo(), self::reglaEsFoto()],
        ];
    }

    /** Lo que acompaña a los pedazos del video, además de VideoPorPedazos::reglas(). */
    public const REGLAS_VIDEO = [
        'nombre' => ['nullable', 'string', 'max:255'],
    ];

    public const MENSAJES = [
        'foto.required_without' => 'Elige una foto.',
        'foto.max' => 'Cada foto puede pesar hasta 50MB.',
        'fotos.required' => 'Manda al menos una foto en fotos[].',
        'fotos.array' => 'Las fotos van en fotos[] (una o varias).',
        'fotos.*.max' => 'Cada foto puede pesar hasta 50MB.',
    ];

    public function __construct(private readonly VideoPorPedazos $videos)
    {
    }

    /**
     * A una orden entregada todavía se le sube evidencia: la foto de la
     * entrega a veces llega después. A una cancelada ya no.
     */
    public function razonParaNoSubir(OrdenSalida $orden): ?string
    {
        return $orden->estado === OrdenSalida::CANCELADA
            ? 'Esta orden está cancelada: ya no se le agrega evidencia.'
            : null;
    }

    /** La evidencia de una orden cancelada se queda como estaba: es el rastro. */
    public function razonParaNoBorrar(OrdenSalida $orden): ?string
    {
        return $orden->estado === OrdenSalida::CANCELADA
            ? 'Esta orden está cancelada: su evidencia ya no se modifica.'
            : null;
    }

    /**
     * Una foto que ya se armó por pedazos (FotoPorPedazos): se pasa a la
     * carpeta de la orden y queda como evidencia.
     */
    public function guardarFotoArmada(OrdenSalida $orden, string $ruta, ?string $nombreOriginal, ?int $usuarioId): OrdenSalidaEvidencia
    {
        $ruta = app(FotoPorPedazos::class)->resolver($ruta, $this->disco());
        if (! $ruta) {
            throw ValidationException::withMessages(['foto' => 'La foto no terminó de subir. Vuelve a intentarlo.']);
        }

        $disco = Storage::disk($this->disco());
        if (! self::esFoto((string) (new \finfo(FILEINFO_MIME_TYPE))->file($disco->path($ruta)))) {
            $disco->delete($ruta);
            throw ValidationException::withMessages(['foto' => 'Ese archivo no es una foto.']);
        }
        $extension = strtolower(pathinfo($ruta, PATHINFO_EXTENSION)) ?: 'jpg';
        $path = $this->carpeta($orden).'/foto_'.Str::lower((string) Str::ulid()).'.'.$extension;
        $tamano = $disco->size($ruta);

        if (! $disco->move($ruta, $path)) {
            throw ValidationException::withMessages(['foto' => 'No se pudo guardar la foto. Vuelve a intentarlo.']);
        }

        return $orden->evidencias()->create([
            'tipo' => OrdenSalidaEvidencia::FOTO,
            'path' => $path,
            'nombre_original' => Str::limit((string) ($nombreOriginal ?: basename($path)), 250, ''),
            'tamano' => $tamano ?: null,
            'subido_por' => $usuarioId,
        ]);
    }

    public function guardarFoto(OrdenSalida $orden, UploadedFile $foto, ?int $usuarioId): OrdenSalidaEvidencia
    {
        // La extensión del nombre manda: el servidor no reconoce HEIC, RAW y otros.
        $extension = strtolower(preg_replace('/[^a-z0-9]/i', '', $foto->getClientOriginalExtension()) ?: $foto->guessExtension() ?: 'jpg');
        $nombre = 'foto_'.Str::lower((string) Str::ulid()).'.'.$extension;

        $path = $foto->storeAs($this->carpeta($orden), $nombre, $this->disco());

        if (! $path) {
            throw ValidationException::withMessages(['foto' => 'No se pudo guardar la foto. Vuelve a intentarlo.']);
        }

        return $orden->evidencias()->create([
            'tipo' => OrdenSalidaEvidencia::FOTO,
            'path' => $path,
            'nombre_original' => Str::limit($foto->getClientOriginalName(), 250, ''),
            'tamano' => $foto->getSize() ?: null,
            'subido_por' => $usuarioId,
        ]);
    }

    /**
     * Un pedazo del video. VideoPorPedazos guarda cada pedazo y, con el
     * último, arma el video; entonces se pasa a la carpeta de la orden y
     * queda registrado como evidencia.
     *
     * @param  array{upload_id: string, index: int|string, total: int|string, extension: string, nombre?: ?string}  $datos
     * @return array{status: string, evidencia?: OrdenSalidaEvidencia}
     *
     * @throws ValidationException si faltan pedazos o el video pasa del límite
     */
    public function recibirPedazoDeVideo(OrdenSalida $orden, UploadedFile $pedazo, array $datos, ?int $usuarioId): array
    {
        $resultado = $this->videos->recibir($pedazo, $datos['upload_id'], (int) $datos['index'], (int) $datos['total'], $datos['extension']);

        if ($resultado['status'] !== 'listo') {
            return ['status' => $resultado['status']];
        }

        $disco = $this->disco();
        $armado = $this->videos->resolver($resultado['video_path'], $disco);

        if (! $armado) {
            throw ValidationException::withMessages(['chunk' => 'No se pudo armar el video. Vuelve a subirlo.']);
        }

        // VideoPorPedazos lo deja con los de las entradas; se mueve a la
        // carpeta de la orden para que su evidencia quede junta.
        $destino = $this->carpeta($orden).'/'.basename($armado);
        Storage::disk($disco)->move($armado, $destino);

        $evidencia = $orden->evidencias()->create([
            'tipo' => OrdenSalidaEvidencia::VIDEO,
            'path' => $destino,
            'nombre_original' => isset($datos['nombre']) ? Str::limit($datos['nombre'], 250, '') : null,
            'tamano' => Storage::disk($disco)->size($destino),
            'subido_por' => $usuarioId,
        ]);

        return ['status' => 'listo', 'evidencia' => $evidencia];
    }

    private function carpeta(OrdenSalida $orden): string
    {
        return 'inventario/salidas/evidencias/'.$orden->id;
    }

    private function disco(): string
    {
        return config('filesystems.fotos_disk', 'public');
    }
}
