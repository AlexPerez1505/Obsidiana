<?php

namespace App\Services\Almacen;

use App\Models\OrdenSalida;
use App\Models\OrdenSalidaEvidencia;
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
    public const FOTO_MAX_KB = 10240; // 10MB por foto

    /** Una foto por petición: lo que manda la pantalla. */
    public const REGLAS_FOTO = [
        'foto' => ['required', 'image', 'max:'.self::FOTO_MAX_KB],
    ];

    /** Una o varias por petición: la API. */
    public const REGLAS_FOTOS = [
        'fotos' => ['required', 'array', 'min:1'],
        'fotos.*' => ['required', 'image', 'max:'.self::FOTO_MAX_KB],
    ];

    /** Lo que acompaña a los pedazos del video, además de VideoPorPedazos::reglas(). */
    public const REGLAS_VIDEO = [
        'nombre' => ['nullable', 'string', 'max:255'],
    ];

    public const MENSAJES = [
        'foto.required' => 'Elige una foto.',
        'foto.image' => 'El archivo no es una imagen (JPG, PNG o WEBP).',
        'foto.max' => 'Cada foto puede pesar hasta 10MB.',
        'fotos.required' => 'Manda al menos una foto en fotos[].',
        'fotos.array' => 'Las fotos van en fotos[] (una o varias).',
        'fotos.*.image' => 'Alguno de los archivos no es una imagen (JPG, PNG o WEBP).',
        'fotos.*.max' => 'Cada foto puede pesar hasta 10MB.',
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

    public function guardarFoto(OrdenSalida $orden, UploadedFile $foto, ?int $usuarioId): OrdenSalidaEvidencia
    {
        $extension = strtolower($foto->guessExtension() ?: $foto->getClientOriginalExtension() ?: 'jpg');
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
