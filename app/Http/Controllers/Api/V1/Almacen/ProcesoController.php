<?php

namespace App\Http\Controllers\Api\V1\Almacen;

use App\Http\Controllers\Api\V1\Concerns\ListadoApi;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Almacen\PiezaProcesoResource;
use App\Http\Resources\Api\V1\Almacen\PiezaResource;
use App\Models\PiezaProceso;
use App\Models\ProductoSerial;
use App\Services\Almacen\ColasDeProcesos;
use App\Services\RutaDeProcesos;
use App\Support\ChecklistProceso;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Procesos: las colas de hojalatería, mantenimiento y limpieza.
 *
 * Cada pieza trae su propia ruta de pasos y aparece en la cola del paso
 * donde está parada. Se toma (iniciar), se trabaja y se cierra con el
 * checklist de salida y al menos una foto; al cerrar el último paso la
 * pieza queda disponible para venta. Un paso que no hacía falta se
 * descarta con su motivo.
 *
 * @tags Almacén · Procesos
 */
class ProcesoController extends Controller
{
    use ListadoApi;

    public function __construct(
        private readonly RutaDeProcesos $ruta,
        private readonly ColasDeProcesos $colas,
    ) {
    }

    /**
     * Listar las colas de trabajo.
     *
     * Los pasos en cola (pendientes y en curso), con su pieza. `resumen`
     * trae los números de la pantalla (en cola, en curso, atoradas de 7
     * días o más, listas) sobre todas las colas, sin filtros. `listas` son
     * las últimas piezas que salieron de sus procesos y ya están
     * disponibles.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $filtros = $request->validate([
            'proceso' => ['nullable', Rule::in(array_keys(PiezaProceso::PROCESOS))],
            'estado' => ['nullable', Rule::in(['pendiente', 'en_curso'])],
            // Etiqueta de la pieza, número de serie, marca o modelo.
            'buscar' => ['nullable', 'string', 'max:100'],
            'responsable_id' => ['nullable', 'integer'],
        ]);

        $query = $this->colas->pasosEnCola()
            ->with(['pieza.producto', 'pieza.procesos', 'responsable'])
            ->when($filtros['proceso'] ?? null, fn ($q, $p) => $q->where('proceso', $p))
            ->when($filtros['estado'] ?? null, fn ($q, $e) => $q->where('estado', $e))
            ->when($filtros['responsable_id'] ?? null, fn ($q, $id) => $q->where('responsable_id', $id))
            ->when($filtros['buscar'] ?? null, fn ($q, string $texto) => $q->whereHas('pieza', fn ($p) => $p
                ->where('codigo', 'like', "%{$texto}%")
                ->orWhere('no_serie', 'like', "%{$texto}%")
                ->orWhereHas('producto', fn ($pr) => $pr
                    ->where('marca', 'like', "%{$texto}%")
                    ->orWhere('modelo', 'like', "%{$texto}%"))));

        $todas = $this->colas->pasosEnCola()->get();
        $listas = $this->colas->listas();

        return PiezaProcesoResource::collection($query->paginate($this->porPagina($request)))
            ->additional([
                'resumen' => $this->colas->resumen($todas, $listas),
                'listas' => PiezaResource::collection($listas),
            ]);
    }

    /**
     * Ver un paso.
     *
     * La pantalla de trabajo: la pieza con toda su ruta, quién la trabaja,
     * el `checklist` con el que se cierra (lo que la mandó aquí desde la
     * recepción más la revisión propia del proceso) y los `siguientes`
     * pasos que le quedan.
     */
    public function show(PiezaProceso $paso): PiezaProcesoResource
    {
        $paso->load(['pieza.producto', 'pieza.procesos', 'pieza.entrada', 'responsable', 'cerradoPor']);

        return (new PiezaProcesoResource($paso))->additional([
            'checklist' => $this->checklist($paso),
            'siguientes' => PiezaProcesoResource::collection(
                $paso->pieza->procesos->where('estado', 'pendiente')->where('id', '!=', $paso->id)->values()
            ),
        ]);
    }

    /**
     * Tomar la pieza.
     *
     * El paso pasa a "en curso" a nombre de quien lo toma. Solo se toma un
     * paso pendiente.
     */
    public function iniciar(Request $request, PiezaProceso $paso): PiezaProcesoResource
    {
        if ($razon = $this->colas->razonParaNoIniciar($paso)) {
            abort(409, $razon);
        }

        $this->ruta->iniciar($paso, $request->user()->id);

        return $this->respuesta($paso, "{$paso->pieza->codigo}: {$paso->nombre()} en curso, a tu nombre.");
    }

    /**
     * Cerrar el paso.
     *
     * Multipart: `checklist[<llave>][r]` = `si` | `no` (y opcional
     * `checklist[<llave>][nota]`) por cada punto del checklist del paso,
     * `evidencias[]` con hasta 5 fotos (al menos una entre las que ya tenía
     * y las nuevas) y `trabajo_realizado`. Todos los puntos tienen que ir
     * en "si": si algo sigue mal, el paso no se cierra y regresa 422 en
     * `errors.cierre` con el porqué. Solo se cierra un paso en curso.
     */
    public function terminar(Request $request, PiezaProceso $paso): PiezaProcesoResource
    {
        if ($razon = $this->colas->razonParaNoCerrar($paso)) {
            abort(409, $razon);
        }

        $data = $request->validate(ColasDeProcesos::REGLAS_CIERRE, ColasDeProcesos::MENSAJES_CIERRE);

        $razones = $this->colas->cerrar(
            $paso,
            $data['checklist'] ?? [],
            $request->file('evidencias') ?? [],
            $data['trabajo_realizado'] ?? null
        );

        if ($razones) {
            throw ValidationException::withMessages(['cierre' => implode(' ', $razones)]);
        }

        return $this->respuesta($paso, $this->avance($paso, 'terminado'));
    }

    /**
     * Descartar el paso.
     *
     * Para un proceso que al ver la pieza de cerca no hacía falta. No pide
     * checklist ni fotos, pero sí el motivo (mínimo 10 letras): queda quién
     * lo decidió y por qué. Solo se descarta un paso en curso.
     */
    public function omitir(Request $request, PiezaProceso $paso): PiezaProcesoResource
    {
        if ($razon = $this->colas->razonParaNoCerrar($paso)) {
            abort(409, $razon);
        }

        $data = $request->validate(ColasDeProcesos::REGLAS_OMITIR, ColasDeProcesos::MENSAJES_OMITIR);

        $this->ruta->omitir($paso, $data['motivo']);

        return $this->respuesta($paso, $this->avance($paso, 'se descartó'));
    }

    /**
     * Mandar una pieza a otro proceso.
     *
     * Agrega a la ruta de la pieza un proceso que no traía (si ya lo
     * tenía, no se duplica). La pieza se mueve sola a donde le toque.
     */
    public function agregar(Request $request, ProductoSerial $pieza): PiezaResource
    {
        $data = $request->validate(ColasDeProcesos::reglasAgregar());

        $this->colas->agregar($pieza, $data['proceso'], $data['motivo'] ?? null);

        $nombre = PiezaProceso::PROCESOS[$data['proceso']];

        return (new PiezaResource($pieza->refresh()->load(['producto', 'procesos'])))
            ->additional(['message' => "{$pieza->codigo} se mandó a {$nombre}."]);
    }

    /**
     * Corregir la ruta de una pieza.
     *
     * Deja exactamente los procesos de `procesos` (vacío = ninguno, queda
     * disponible). Los pasos ya terminados u omitidos se conservan aunque
     * no vengan: son historia. No aplica a piezas vendidas o dadas de baja
     * (409). Pide el mismo permiso que registrar entradas, porque es para
     * corregir lo que se marcó mal al recibir.
     */
    public function definir(Request $request, ProductoSerial $pieza): PiezaResource
    {
        $data = $request->validate(ColasDeProcesos::reglasDefinir());

        if ($razon = $this->colas->razonParaNoRedefinir($pieza)) {
            abort(409, $razon);
        }

        $this->colas->redefinir($pieza, $data['procesos'] ?? []);

        $pieza->refresh()->load(['producto', 'procesos']);
        $falta = $pieza->faltaTexto();

        $mensaje = "{$pieza->codigo}: procesos actualizados.";
        $mensaje .= $falta ? " Ahora falta: {$falta}." : ' Queda disponible para venta.';

        return (new PiezaResource($pieza))->additional(['message' => $mensaje]);
    }

    /**
     * El checklist del paso como lista: así el orden de los puntos no
     * depende de cómo cada cliente lea un objeto JSON.
     */
    private function checklist(PiezaProceso $paso): array
    {
        return collect(ChecklistProceso::para($paso))
            ->map(fn (array $seccion, string $llave) => [
                'seccion' => $llave,
                'titulo' => $seccion['titulo'],
                'puntos' => collect($seccion['puntos'])
                    ->map(fn (string $texto, string $punto) => ['llave' => $punto, 'texto' => $texto])
                    ->values()
                    ->all(),
            ])
            ->values()
            ->all();
    }

    /** "MB-000123: Hojalatería terminado. Sigue en Limpieza." */
    private function avance(PiezaProceso $paso, string $hecho): string
    {
        $pieza = $paso->pieza->refresh()->load('procesos');
        $falta = $pieza->faltaTexto();

        return "{$pieza->codigo}: {$paso->nombre()} {$hecho}."
            .($falta ? " Sigue en {$falta}." : ' Ya está disponible para venta.');
    }

    private function respuesta(PiezaProceso $paso, string $mensaje): PiezaProcesoResource
    {
        return (new PiezaProcesoResource($paso->refresh()->load(['pieza.producto', 'pieza.procesos', 'responsable', 'cerradoPor'])))
            ->additional(['message' => $mensaje]);
    }
}
