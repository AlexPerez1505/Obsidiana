<?php

namespace App\Http\Controllers\Api\V1\Marketing;

use App\Http\Controllers\Api\V1\Concerns\ListadoApi;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Marketing\TareaResource;
use App\Http\Resources\Api\V1\UsuarioResource;
use App\Models\Task;
use App\Models\User;
use App\Services\Marketing\PreviewDeEnlaces;
use App\Services\Marketing\TareasDeMarketing;
use Dedoc\Scramble\Attributes\BodyParameter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @tags Marketing
 */
class TareaController extends Controller
{
    use ListadoApi;

    private const RELACIONES = ['user', 'creator', 'reviewer'];

    public function __construct(private TareasDeMarketing $tareas)
    {
    }

    /**
     * Tareas de marketing.
     *
     * Las piezas del tablero, por fecha de entrega. Filtros como en la
     * web: `q` (título, descripción o etiqueta exacta), `priority`
     * (baja|media|alta), `user_id` (responsable), `due` (today|week|overdue)
     * y además `status`. En `stats` vienen los contadores del tablero sobre
     * lo filtrado. Orden por `due_date`, `created_at`, `updated_at`,
     * `priority`, `status`, `title` o `id`.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $request->validate([
            'status' => ['nullable', 'in:'.implode(',', array_keys(TareasDeMarketing::ESTADOS))],
            'priority' => ['nullable', 'in:'.implode(',', array_keys(TareasDeMarketing::PRIORIDADES))],
            'due' => ['nullable', 'in:today,week,overdue'],
            'user_id' => ['nullable', 'integer'],
            'q' => ['nullable', 'string', 'max:255'],
        ]);

        $query = $this->tareas->filtrar(Task::with(self::RELACIONES), $request);

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        $stats = $this->tareas->estadisticas((clone $query)->setEagerLoads([])->get(['id', 'status', 'priority']));

        $tareas = $this->ordenar($query, $request, ['due_date', 'created_at', 'updated_at', 'priority', 'status', 'title', 'id'], 'due_date')
            ->paginate($this->porPagina($request));

        return TareaResource::collection($tareas)->additional(['stats' => $stats]);
    }

    /**
     * Ver una tarea.
     */
    public function show(Task $task): TareaResource
    {
        return new TareaResource($task->load(self::RELACIONES));
    }

    /**
     * Opciones para capturar tareas.
     *
     * Usuarios a quienes se puede asignar o poner de revisor, estados,
     * prioridades, categorías del calendario y los puntos del checklist de
     * aprobación (el índice de cada punto es lo que se guarda en
     * `approval_checklist`).
     */
    public function opciones(): JsonResponse
    {
        return response()->json(['data' => [
            'usuarios' => UsuarioResource::collection(User::orderBy('name')->get()),
            'estados' => TareasDeMarketing::ESTADOS,
            'prioridades' => TareasDeMarketing::PRIORIDADES,
            'categorias' => TareasDeMarketing::CATEGORIAS,
            'checklist' => TareasDeMarketing::CHECKLIST,
        ]]);
    }

    /**
     * Crear una tarea.
     *
     * Nace en `pendiente`. `tags` puede ir como texto separado por comas
     * (como en la web) o como lista. `project_image` es una imagen opcional
     * de hasta 5 MB (multipart).
     */
    #[BodyParameter('title', 'Título de la pieza.', required: true, type: 'string')]
    #[BodyParameter('priority', 'baja, media o alta.', required: true, type: 'string', example: 'media')]
    #[BodyParameter('progress', 'Avance 0-100.', required: true, type: 'integer', example: 0)]
    #[BodyParameter('user_id', 'Responsable.', required: true, type: 'integer')]
    #[BodyParameter('reviewer_id', 'Revisor.', type: 'integer')]
    #[BodyParameter('description', 'Descripción corta.', type: 'string')]
    #[BodyParameter('task_description', 'Brief completo.', type: 'string')]
    #[BodyParameter('delivery_link', 'Enlace de entrega (Canva, Drive, YouTube…).', type: 'string', format: 'uri')]
    #[BodyParameter('due_date', 'Fecha de entrega (Y-m-d).', type: 'string', format: 'date')]
    #[BodyParameter('review_date', 'Fecha de revisión (Y-m-d).', type: 'string', format: 'date')]
    #[BodyParameter('tags', 'Etiquetas: "a, b" o ["a", "b"].', type: 'string')]
    #[BodyParameter('category', 'Categoría del calendario.', type: 'string')]
    #[BodyParameter('platform', 'Redes donde se publica.', type: 'array')]
    #[BodyParameter('has_video', 'Lleva video.', type: 'boolean')]
    #[BodyParameter('linked_piece', 'Pieza relacionada.', type: 'string')]
    #[BodyParameter('project_image', 'Imagen de la pieza (jpg, png, gif, webp; máx. 5 MB).', type: 'string', format: 'binary')]
    public function store(Request $request): JsonResponse
    {
        $this->etiquetasComoTexto($request);

        $tarea = $this->tareas->crear($request);

        return (new TareaResource($tarea->refresh()->load(self::RELACIONES)))
            ->additional(['message' => 'Tarea creada correctamente.'])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Actualizar una tarea.
     *
     * Edición completa, como el modal del tablero: `title`, `status`,
     * `priority`, `progress` y `user_id` son obligatorios. Para cambiar
     * `project_image` manda multipart por POST con `_method=PUT` (PHP no
     * lee archivos en un PUT).
     */
    #[BodyParameter('title', 'Título de la pieza.', required: true, type: 'string')]
    #[BodyParameter('status', 'pendiente, en_proceso, revision o completada.', required: true, type: 'string')]
    #[BodyParameter('priority', 'baja, media o alta.', required: true, type: 'string')]
    #[BodyParameter('progress', 'Avance 0-100.', required: true, type: 'integer')]
    #[BodyParameter('user_id', 'Responsable.', required: true, type: 'integer')]
    #[BodyParameter('reviewer_id', 'Revisor.', type: 'integer')]
    #[BodyParameter('description', 'Descripción corta.', type: 'string')]
    #[BodyParameter('task_description', 'Brief completo.', type: 'string')]
    #[BodyParameter('delivery_link', 'Enlace de entrega.', type: 'string', format: 'uri')]
    #[BodyParameter('due_date', 'Fecha de entrega (Y-m-d).', type: 'string', format: 'date')]
    #[BodyParameter('review_date', 'Fecha de revisión (Y-m-d).', type: 'string', format: 'date')]
    #[BodyParameter('tags', 'Etiquetas: "a, b" o ["a", "b"]. Si no se mandan, se vacían.', type: 'string')]
    #[BodyParameter('category', 'Categoría del calendario.', type: 'string')]
    #[BodyParameter('platform', 'Redes donde se publica.', type: 'array')]
    #[BodyParameter('has_video', 'Lleva video.', type: 'boolean')]
    #[BodyParameter('linked_piece', 'Pieza relacionada.', type: 'string')]
    #[BodyParameter('rejection_comment', 'Observaciones pendientes.', type: 'string')]
    #[BodyParameter('project_image', 'Imagen nueva de la pieza (máx. 5 MB).', type: 'string', format: 'binary')]
    public function update(Request $request, Task $task): TareaResource
    {
        $this->etiquetasComoTexto($request);

        $this->tareas->actualizar($task, $request);

        return (new TareaResource($task->refresh()->load(self::RELACIONES)))
            ->additional(['message' => 'Tarea actualizada correctamente.']);
    }

    /**
     * Eliminar una tarea.
     */
    public function destroy(Task $task): JsonResponse
    {
        $task->delete();

        return response()->json(['message' => 'Tarea eliminada correctamente.']);
    }

    /**
     * Enviar a revisión.
     *
     * Pasa la pieza a `revision` para que la vea quien aprueba flyers.
     */
    public function enviarRevision(Task $task): TareaResource
    {
        $this->tareas->enviarARevision($task);

        return (new TareaResource($task->load(self::RELACIONES)))
            ->additional(['message' => 'Pieza enviada a revisión.']);
    }

    /**
     * Aprobar una pieza.
     *
     * La deja `completada` al 100 % y borra las observaciones pendientes.
     * Requiere el permiso de aprobar flyers.
     */
    public function aprobar(Task $task): TareaResource
    {
        $this->tareas->aprobar($task);

        return (new TareaResource($task->load(self::RELACIONES)))
            ->additional(['message' => 'Tarea aprobada correctamente.']);
    }

    /**
     * Devolver una pieza con observaciones.
     *
     * La regresa a `pendiente`. `rejection_comment` (obligatorio) explica
     * qué corregir; `approval_checklist` son los índices del checklist que
     * sí se cumplen (se suman a los de revisiones anteriores). El avance se
     * recalcula a 10 % por punto cumplido.
     */
    #[BodyParameter('rejection_comment', 'Qué corregir.', required: true, type: 'string')]
    #[BodyParameter('approval_checklist', 'Índices del checklist que sí se cumplen.', type: 'array', example: [0, 1, 4])]
    public function devolver(Request $request, Task $task): TareaResource
    {
        $this->tareas->devolver($task, $request);

        return (new TareaResource($task->load(self::RELACIONES)))
            ->additional(['message' => 'Pieza devuelta con observaciones.']);
    }

    /**
     * Previsualizar un enlace de entrega.
     *
     * Para mostrar la pieza sin salir de la app: con YouTube, Vimeo, Canva
     * y Drive devuelve `embed_url` (o `embed_html` en Canva); con una
     * imagen, `image`; con cualquier otra página, la tarjeta OpenGraph
     * (`title`, `description`, `image`). `type` dice cuál fue. Se guarda
     * en caché 7 días por URL.
     */
    public function previewLink(Request $request, PreviewDeEnlaces $previews): JsonResponse
    {
        $data = $request->validate([
            'url' => ['required', 'url', 'max:2000'],
        ]);

        return response()->json(['data' => $previews->obtener($data['url'])]);
    }

    /**
     * La web captura las etiquetas como "a, b, c"; a una app le es más
     * natural mandar una lista, así que se aceptan las dos.
     */
    private function etiquetasComoTexto(Request $request): void
    {
        if (is_array($request->input('tags'))) {
            $request->merge(['tags' => implode(',', array_filter($request->input('tags'), 'is_string'))]);
        }
    }
}
