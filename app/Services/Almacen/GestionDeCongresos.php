<?php

namespace App\Services\Almacen;

use App\Models\Congress;
use App\Models\Producto;
use App\Models\ProductoSerial;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

/**
 * Alta y edición de congresos, y qué piezas se llevan y regresan.
 *
 * Llevar una pieza a un congreso es solo una etiqueta sobre ella
 * (congress_id): no toca su estado ni su disponibilidad, porque allá
 * mismo se puede vender. Regresarla es quitar la etiqueta. Las vendidas
 * conservan la marca: es el rastro de qué se vendió en cada evento.
 *
 * Lo usan la web y la API, para que las dos acepten y rechacen lo mismo.
 */
class GestionDeCongresos
{
    private const CARPETA = 'congresos';

    /** Reglas extra del alta: piezas y usuarios que van desde el principio. */
    public const REGLAS_ALTA_INICIAL = [
        'serial_ids' => ['nullable', 'array'],
        'serial_ids.*' => ['integer', 'exists:producto_seriales,id'],
        'user_ids' => ['nullable', 'array'],
        'user_ids.*' => ['integer', 'exists:users,id'],
    ];

    /** Reglas comunes de alta y edición. */
    public const REGLAS = [
        'nombre' => ['required', 'string', 'max:255'],
        'descripcion' => ['nullable', 'string', 'max:5000'],
        'categoria_id' => ['required', 'exists:categorias,id'],
        'fecha_inicio' => ['required', 'date'],
        'fecha_finalizacion' => ['required', 'date', 'after_or_equal:fecha_inicio'],
        'fecha_montaje' => ['nullable', 'date', 'before_or_equal:fecha_inicio'],
        'hora_montaje' => ['required', 'date_format:H:i'],
        'hora_desmontaje' => ['required', 'date_format:H:i'],
        'num_stand' => ['nullable', 'string', 'max:50'],
        'costo' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
        'pagado' => ['nullable', 'numeric', 'min:0', 'max:99999999.99', 'lte:costo'],
        'descarga_acceso' => ['nullable', 'boolean'],
        'descarga_texto' => ['nullable', 'string', 'max:255'],
        'acceso_subir' => ['nullable', 'boolean'],
        'subir_texto' => ['nullable', 'string', 'max:255'],
        'direccion' => ['nullable', 'string', 'max:255'],
        'comments' => ['nullable', 'string', 'max:5000'],
        'archivos' => ['nullable', 'array', 'max:10'],
        'archivos.*' => ['file', 'mimes:jpg,jpeg,png,webp,gif,pdf,doc,docx,xls,xlsx,ppt,pptx'],
        'quitar_archivos' => ['nullable', 'array'],
    ];

    public const MENSAJES = [
        'archivos.max' => 'Puedes subir hasta 10 archivos a la vez.',
        'archivos.*.mimes' => 'Solo se aceptan imágenes, PDF o documentos de Office.',
        'fecha_montaje.before_or_equal' => 'La fecha de montaje debe ser el mismo día en que inicia el congreso o antes (el stand se arma antes de que arranque).',
        'pagado.lte' => 'Lo pagado no puede ser mayor que el costo del congreso.',
    ];

    /**
     * Lo validado con REGLAS, ya como lo guarda la tabla: casillas como
     * booleanos y sin los campos de archivos (esos van aparte).
     */
    public function normalizar(Request $request, array $data): array
    {
        $data['descarga_acceso'] = $request->boolean('descarga_acceso');
        $data['acceso_subir'] = $request->boolean('acceso_subir');

        // Sin monto capturado, lo pagado es 0 (la columna no admite null).
        $data['pagado'] = $data['pagado'] ?? 0;

        unset($data['archivos'], $data['quitar_archivos']);

        return $data;
    }

    /**
     * Desde la propia pantalla de "Nuevo congreso" ya se pueden elegir las
     * unidades (viendo su foto) y los usuarios que van, sin tener que
     * guardar el congreso primero y entrar luego a agregarlos. Las piezas
     * que ya no se pueden llevar se ignoran sin avisar.
     *
     * @param  array<int, UploadedFile>  $archivos
     */
    public function crear(array $data, array $archivos = [], array $serialIds = [], array $userIds = []): Congress
    {
        $data['path_archivo'] = $this->guardarArchivos($archivos);

        $congress = Congress::create($data);

        $serialIds = array_filter($serialIds);

        if ($serialIds) {
            $this->llevar($congress, $this->llevables($serialIds)->pluck('id'));
        }

        $userIds = array_filter($userIds);

        if ($userIds) {
            $congress->notifiedUsers()->syncWithoutDetaching(
                User::whereIn('id', $userIds)->pluck('id')
            );
        }

        return $congress;
    }

    /**
     * @param  array<int, UploadedFile>  $archivos  los nuevos
     * @param  array<int, string>  $quitar  rutas de archivos del congreso que se borran
     */
    public function actualizar(Congress $congress, array $data, array $archivos = [], array $quitar = []): void
    {
        $data['path_archivo'] = $this->guardarArchivos($archivos, (array) ($congress->path_archivo ?? []), $quitar);

        $congress->update($data);
    }

    /**
     * De estas piezas, las que de verdad se pueden llevar: siguen
     * disponibles y no están ya en otro congreso, aunque la pantalla las
     * haya mostrado así hace un rato.
     */
    public function llevables(array $serialIds): Collection
    {
        return ProductoSerial::whereIn('id', $serialIds)
            ->where('vendido', false)
            ->whereNotIn('estado', ProductoSerial::NO_VENDIBLES)
            ->whereNull('congress_id')
            ->get();
    }

    /**
     * Manda al congreso las piezas que se eligieron a mano. Devuelve
     * cuántas se llevaron; 0 si ninguna seguía disponible.
     */
    public function agregarUnidades(Congress $congress, array $serialIds): int
    {
        $unidades = $this->llevables($serialIds);

        if ($unidades->isNotEmpty()) {
            $this->llevar($congress, $unidades->pluck('id'));
        }

        return $unidades->count();
    }

    /** Regresa una pieza puntual: ya no está "en" el congreso. */
    public function quitarUnidad(ProductoSerial $serial): void
    {
        $serial->update(['congress_id' => null, 'enviado_a_congreso_en' => null]);
    }

    /**
     * Regresa de un jalón todo lo que no se vendió. Es lo que cierra el
     * ciclo: al terminar el congreso, lo que sobró vuelve al almacén. Sin
     * esto las piezas se quedaban marcadas para siempre.
     *
     * Devuelve cuántas regresaron; 0 si no había nada allá.
     */
    public function regresarTodas(Congress $congress): int
    {
        $cuantas = $congress->unidadesPresentes()->count();

        if ($cuantas === 0) {
            return 0;
        }

        // Las vendidas no se tocan: su marca es el rastro de dónde se
        // vendieron.
        ProductoSerial::where('congress_id', $congress->id)
            ->where('vendido', false)
            ->update(['congress_id' => null, 'enviado_a_congreso_en' => null]);

        return $cuantas;
    }

    /**
     * Unidades disponibles de un producto, con su foto, para elegir a mano
     * cuáles se llevan al congreso (no una cantidad a ciegas).
     *
     * @return Collection<int, array{id: int, codigo: ?string, no_serie: ?string, foto: ?string}>
     */
    public function unidadesDisponibles(Producto $producto): Collection
    {
        return $producto->serialesDisponibles()
            ->whereNull('congress_id')
            ->with('entrada')
            ->oldest('id')
            ->get()
            ->map(fn (ProductoSerial $u) => [
                'id' => $u->id,
                'codigo' => $u->codigo,
                'no_serie' => $u->no_serie,
                'foto' => $u->fotoUrl() ?: ($u->entrada?->evidenceUrls()[0] ?? null),
            ]);
    }

    /** URL pública de cada archivo del congreso. */
    public function archivosUrls(Congress $congress): array
    {
        return collect((array) ($congress->path_archivo ?? []))
            ->map(fn (string $ruta) => ['path' => $ruta, 'url' => Storage::disk('public')->url($ruta)])
            ->values()
            ->all();
    }

    private function llevar(Congress $congress, $ids): void
    {
        ProductoSerial::whereIn('id', $ids)->update([
            'congress_id' => $congress->id,
            'enviado_a_congreso_en' => now(),
        ]);
    }

    /**
     * Guarda los archivos nuevos y devuelve la lista completa.
     *
     * Solo se borran archivos que de verdad son de este congreso: la ruta
     * llega del formulario y no puede servir para borrar otra cosa del
     * disco.
     *
     * @return array<int, string>
     */
    private function guardarArchivos(array $nuevos, array $previos = [], array $quitar = []): array
    {
        foreach ($quitar as $ruta) {
            if (! is_string($ruta) || ! in_array($ruta, $previos, true)) {
                continue;
            }

            if (Storage::disk('public')->exists($ruta)) {
                Storage::disk('public')->delete($ruta);
            }

            $previos = array_values(array_diff($previos, [$ruta]));
        }

        foreach ($nuevos as $archivo) {
            $previos[] = $archivo->store(self::CARPETA, 'public');
        }

        return array_values(array_unique($previos));
    }
}
