<?php

namespace App\Http\Controllers\Api\V1\Logistica;

use App\Http\Controllers\Api\V1\Concerns\ListadoApi;
use App\Http\Controllers\Api\V1\Logistica\Concerns\ReglasDeRuta;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Logistica\CombustibleResource;
use App\Http\Resources\Api\V1\Logistica\IncidenciaResource;
use App\Http\Resources\Api\V1\Logistica\ParadaResource;
use App\Http\Resources\Api\V1\Logistica\RutaEnVivoResource;
use App\Http\Resources\Api\V1\Logistica\RutaResource;
use App\Models\Ruta;
use App\Models\RutaIncidencia;
use App\Models\RutaParada;
use App\Services\Logistica\OperacionDeRutas;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

/**
 * La app del chofer: sus rutas, el envío de GPS y el cierre de cada
 * entrega con evidencias. Solo opera rutas asignadas a quien hace la
 * petición; con la ruta de otro chofer responde 403 (aunque sea admin).
 *
 * @tags Logística
 */
class ChoferController extends Controller
{
    use ListadoApi, ReglasDeRuta;

    /**
     * Mis rutas.
     *
     * `estado=activas` (por defecto): las programadas y en curso, primero la
     * que va en ruta. `estado=finalizadas`: las ya cerradas, de la más
     * reciente a la más antigua.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $f = $request->validate(['estado' => ['nullable', 'in:activas,finalizadas']]);

        $rutas = Ruta::with(['vehicle', 'paradas'])
            ->where('chofer_id', $request->user()->id)
            ->when(($f['estado'] ?? 'activas') === 'activas',
                fn ($q) => $q->whereIn('estado', [Ruta::PROGRAMADA, Ruta::EN_RUTA])
                    ->orderByRaw("estado = 'en_ruta' desc")
                    ->orderBy('fecha_programada'),
                fn ($q) => $q->where('estado', Ruta::FINALIZADA)->latest('finalizada_en'))
            ->orderBy('id')
            ->paginate($this->porPagina($request));

        return RutaResource::collection($rutas);
    }

    /**
     * Ver mi ruta.
     *
     * Paradas en orden con mercancía y evidencias. En `meta`:
     * `siguiente_parada_id`, cada cuántos segundos mandar GPS
     * (`intervalo_gps_s`) y los tipos de incidencia que se pueden reportar.
     */
    public function show(Request $request, Ruta $ruta): RutaResource
    {
        $this->propia($request, $ruta);

        $ruta->load(['vehicle', 'paradas.items', 'paradas.evidencias']);

        return (new RutaResource($ruta))->additional(['meta' => [
            'siguiente_parada_id' => $ruta->paradas->first(fn (RutaParada $p) => ! $p->resuelta())?->id,
            'intervalo_gps_s' => (int) config('logistica.intervalo_gps_s'),
            'tipos_incidencia' => RutaIncidencia::TIPOS,
        ]]);
    }

    /**
     * Mandar ubicación GPS.
     *
     * Una lectura suelta (`lat`, `lng`, …) o un lote en `lecturas` con las
     * posiciones que el teléfono guardó sin señal (cada una con su
     * `registrada_en`; máx. 500); si vienen las dos se procesan juntas.
     * Velocidad en m/s (`velocidad_ms`, como la da el GPS) o en km/h
     * (`velocidad_kmh`). Se procesan en orden de hora: guardan el
     * recorrido, detectan desviaciones del trazo y la llegada automática a
     * la geocerca de cada parada (y la salida, ya entregada). Un lote se
     * guarda completo o nada, así que puede reenviarse si falla.
     *
     * La respuesta trae el estado vivo de la ruta, `avisos` para mostrar al
     * chofer ("Llegaste a…") y `procesadas`. Con la ruta ya cerrada no se
     * guarda nada y `data.activa` viene en false.
     */
    public function ubicacion(Request $request, Ruta $ruta, OperacionDeRutas $op): RutaEnVivoResource
    {
        $this->propia($request, $ruta);

        $d = $request->validate([
            // Lectura suelta
            'lat' => ['required_without:lecturas', 'numeric', 'between:-90,90'],
            'lng' => ['required_without:lecturas', 'numeric', 'between:-180,180'],
            'velocidad_ms' => ['nullable', 'numeric'],
            'velocidad_kmh' => ['nullable', 'numeric'],
            'precision' => ['nullable', 'numeric'],
            'rumbo' => ['nullable', 'numeric'],
            'registrada_en' => ['nullable', 'date'],

            // Lote guardado sin señal: cada lectura con la hora en que se tomó
            'lecturas' => ['sometimes', 'array', 'min:1', 'max:500'],
            'lecturas.*.lat' => ['required', 'numeric', 'between:-90,90'],
            'lecturas.*.lng' => ['required', 'numeric', 'between:-180,180'],
            'lecturas.*.velocidad_ms' => ['nullable', 'numeric'],
            'lecturas.*.velocidad_kmh' => ['nullable', 'numeric'],
            'lecturas.*.precision' => ['nullable', 'numeric'],
            'lecturas.*.rumbo' => ['nullable', 'numeric'],
            'lecturas.*.registrada_en' => ['required', 'date'],
        ]);

        $lecturas = $d['lecturas'] ?? [];

        if (isset($d['lat'])) {
            $lecturas[] = Arr::only($d, ['lat', 'lng', 'velocidad_ms', 'velocidad_kmh', 'precision', 'rumbo', 'registrada_en']);
        }

        $r = $op->registrarUbicaciones($ruta, $lecturas);

        $ruta->refresh()->load(['paradas', 'ultimaUbicacion']);

        return (new RutaEnVivoResource($ruta))->additional([
            'procesadas' => $r['procesadas'],
            'avisos' => $r['avisos'],
        ]);
    }

    /**
     * Marcar llegada a una parada.
     *
     * Para cuando el GPS no alcanzó a detectarla. Si la parada ya estaba en
     * el sitio o resuelta no cambia nada.
     */
    public function llegada(Request $request, Ruta $ruta, RutaParada $parada, OperacionDeRutas $op): ParadaResource
    {
        $this->propia($request, $ruta, $parada);

        $d = $request->validate([
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
        ]);

        $this->operar(fn () => $op->marcarLlegada($parada, $request->user()->id, isset($d['lat']) ? (float) $d['lat'] : null, isset($d['lng']) ? (float) $d['lng'] : null));

        return (new ParadaResource($parada->fresh(['items', 'evidencias'])))
            ->additional(['message' => 'Llegada registrada.']);
    }

    /**
     * Cerrar una entrega.
     *
     * Multipart con `fotos[]` (hasta 8 imágenes), `remision` opcional (foto o
     * PDF), `receptor_nombre`, `lat`/`lng` y la firma de quien recibe. La
     * firma puede ir como archivo de imagen en `firma` o como texto: data
     * URL (`data:image/png;base64,…`, igual que la web) o solo el base64 del
     * PNG. Si la ruta exige evidencia, faltar foto, firma, receptor o
     * ubicación da 422 con `errors.evidencia`.
     *
     * Si la parada viene de una orden de salida lista para firma, la orden
     * se cierra como entregada. Al resolver la última parada la ruta se
     * finaliza sola (`data.ruta.estado`).
     */
    public function completar(Request $request, Ruta $ruta, RutaParada $parada, OperacionDeRutas $op): ParadaResource
    {
        $this->propia($request, $ruta, $parada);

        $d = $request->validate([
            'receptor_nombre' => ['nullable', 'string', 'max:150'],
            'firma' => $request->hasFile('firma')
                ? ['file', 'mimes:png,jpg,jpeg', 'max:4096']
                : ['nullable', 'string'],
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
            'fotos' => ['nullable', 'array', 'max:8'],
            'fotos.*' => ['file', 'image', 'max:8192'],
            'remision' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:10240'],
        ]);

        $d['firma'] = $this->firmaComoDataUrl($request->file('firma') ?? ($d['firma'] ?? null));

        $this->operar(fn () => $op->completar($parada, $d, $request->file('fotos', []), $request->file('remision'), $request->user()->id));

        return (new ParadaResource($parada->fresh(['items', 'evidencias', 'ordenSalida', 'ruta'])))
            ->additional(['message' => 'Entrega registrada.']);
    }

    /**
     * Marcar parada como no entregada.
     *
     * Con el motivo (cliente ausente, dirección equivocada…). Cuenta como
     * resuelta para el avance de la ruta.
     */
    public function noEntregada(Request $request, Ruta $ruta, RutaParada $parada, OperacionDeRutas $op): ParadaResource
    {
        $this->propia($request, $ruta, $parada);

        $d = $request->validate([
            'motivo' => ['required', 'string', 'max:255'],
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
        ], ['motivo.required' => 'Escribe por qué no se pudo entregar.']);

        $this->operar(fn () => $op->noEntregada($parada, $d['motivo'], $request->user()->id, isset($d['lat']) ? (float) $d['lat'] : null, isset($d['lng']) ? (float) $d['lng'] : null));

        return (new ParadaResource($parada->fresh(['items', 'ruta'])))
            ->additional(['message' => 'Parada marcada como no entregada.']);
    }

    /**
     * Reportar una incidencia.
     *
     * Retraso, tráfico, falla mecánica, accidente… (ver `tipos_incidencia`
     * en el detalle de la ruta). Puede ligarse a una parada y llevar una
     * `foto` (multipart). Aparece al instante en el tablero de incidencias.
     */
    public function incidencia(Request $request, Ruta $ruta, OperacionDeRutas $op): JsonResponse
    {
        $this->propia($request, $ruta);

        $d = $request->validate([
            'tipo' => ['required', 'in:'.implode(',', array_keys(RutaIncidencia::TIPOS))],
            'descripcion' => ['nullable', 'string', 'max:1000'],
            'ruta_parada_id' => ['nullable', 'integer'],
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
            'foto' => ['nullable', 'file', 'image', 'max:8192'],
        ], ['tipo.required' => 'Elige qué pasó.']);

        $incidencia = $op->reportarIncidencia($ruta, $d, $request->file('foto'), $request->user()->id);

        return (new IncidenciaResource($incidencia))
            ->additional(['message' => 'Incidencia reportada. La oficina ya la puede ver.'])
            ->response()->setStatusCode(201);
    }

    /**
     * Registrar carga de combustible.
     *
     * Hecha en el camino: se guarda con fecha de hoy, ligada al vehículo y a
     * la ruta. `ticket` opcional (foto o PDF, multipart).
     */
    public function combustible(Request $request, Ruta $ruta, OperacionDeRutas $op): JsonResponse
    {
        $this->propia($request, $ruta);

        abort_unless($ruta->vehicle, 422, 'La ruta no tiene vehículo asignado.');

        $d = $request->validate([
            'litros' => ['required', 'numeric', 'min:0.1', 'max:500'],
            'monto' => ['required', 'numeric', 'min:0', 'max:100000'],
            'odometro_km' => ['nullable', 'integer', 'min:0', 'max:3000000'],
            'tanque_lleno' => ['nullable', 'boolean'],
            'estacion' => ['nullable', 'string', 'max:120'],
            'ticket' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:8192'],
        ]);

        $carga = $this->operar(fn () => $op->registrarCombustible($ruta, $d, $request->file('ticket'), $request->user()->id));

        return (new CombustibleResource($carga))
            ->additional(['message' => 'Carga de combustible registrada.'])
            ->response()->setStatusCode(201);
    }

    // ------------------------------------------------------------------

    /**
     * El servicio guarda la firma desde un data URL (así la manda el lienzo
     * de la web). Una app puede mandar el archivo o el base64 suelto: se
     * normaliza aquí y se rechaza lo que no sea una imagen.
     */
    private function firmaComoDataUrl(UploadedFile|string|null $firma): ?string
    {
        if ($firma === null || $firma === '') {
            return null;
        }

        if ($firma instanceof UploadedFile) {
            $contenido = (string) file_get_contents($firma->getRealPath());
        } else {
            $base64 = preg_match('/^data:image\/(png|jpe?g);base64,(.+)$/s', $firma, $m) ? $m[2] : $firma;
            $contenido = (string) base64_decode(preg_replace('/\s+/', '', $base64), true);
        }

        $info = $contenido !== '' ? @getimagesizefromstring($contenido) : false;

        if (! $info || ! in_array($info['mime'] ?? null, ['image/png', 'image/jpeg'], true)) {
            throw ValidationException::withMessages(['firma' => 'La firma debe ser una imagen PNG o JPG (archivo, data URL o base64).']);
        }

        $tipo = $info['mime'] === 'image/png' ? 'png' : 'jpeg';

        return "data:image/{$tipo};base64,".base64_encode($contenido);
    }
}
