<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;

use App\Models\Task;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class TaskController extends Controller
{
    /**
     * Muestra el tablero Kanban de tareas de marketing con filtros y estadísticas.
     */
    public function index(Request $request): View
    {
        $query = Task::with(['user', 'creator', 'reviewer']);

        if ($request->filled('q')) {
            $q = $request->input('q');
            $query->where(function ($builder) use ($q) {
                $builder->where('title', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%")
                    ->orWhereJsonContains('tags', $q);
            });
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->input('priority'));
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        if ($request->filled('due')) {
            match ($request->input('due')) {
                'today' => $query->whereDate('due_date', today()),
                'week' => $query->whereBetween('due_date', [today(), today()->addWeek()]),
                'overdue' => $query->whereDate('due_date', '<', today())->where('status', '!=', 'completada'),
                default => null,
            };
        }

        $tasks = $query->orderBy('due_date')->get();

        $columns = [
            [
                'id' => 'pendiente',
                'title' => 'Por hacer',
                'color' => 'amber',
                'tasks' => $tasks->where('status', 'pendiente')->values(),
            ],
            [
                'id' => 'en_proceso',
                'title' => 'En curso',
                'color' => 'blue',
                'tasks' => $tasks->whereIn('status', ['en_proceso', 'revision'])->values(),
            ],
            [
                'id' => 'completada',
                'title' => 'Hecho',
                'color' => 'emerald',
                'tasks' => $tasks->where('status', 'completada')->values(),
            ],
        ];

        $stats = [
            'total' => $tasks->count(),
            'pendiente' => $tasks->where('status', 'pendiente')->count(),
            'en_proceso' => $tasks->where('status', 'en_proceso')->count(),
            'revision' => $tasks->where('status', 'revision')->count(),
            'completada' => $tasks->where('status', 'completada')->count(),
            'overdue' => Task::whereDate('due_date', '<', today())
                ->where('status', '!=', 'completada')
                ->count(),
            'high_priority' => $tasks->where('priority', 'alta')->count(),
        ];

        $users = User::orderBy('name')->get(['id', 'name', 'email', 'is_admin']);

        return view('structure.gestion_marketing.tareas.tareas', [
            'columns' => $columns,
            'stats' => $stats,
            'users' => $users,
            'filters' => $request->only(['q', 'priority', 'user_id', 'due']),
        ]);
    }

    /**
     * Muestra el formulario para crear una nueva tarea.
     */
    public function create(): View
    {
        return view('structure.gestion_marketing.tareas.crear_tarea', [
            'users' => User::orderBy('name')->get(),
            'statuses' => [
                'pendiente' => 'Pendiente',
                'en_proceso' => 'En proceso',
                'revision' => 'Revisión',
                'completada' => 'Completada',
            ],
            'priorities' => [
                'baja' => 'Baja',
                'media' => 'Media',
                'alta' => 'Alta',
            ],
        ]);
    }

    /**
     * Guarda una nueva tarea en el sistema.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'task_description' => ['nullable', 'string'],
            'delivery_link' => ['nullable', 'url'],
            'priority' => ['required', 'in:baja,media,alta'],
            'due_date' => ['nullable', 'date'],
            'review_date' => ['nullable', 'date'],
            'progress' => ['required', 'integer', 'min:0', 'max:100'],
            'user_id' => ['required', 'exists:users,id'],
            'reviewer_id' => ['nullable', 'exists:users,id'],
            'tags' => ['nullable', 'string'],
            'category' => ['nullable', 'string', 'max:255'],
            'platform' => ['nullable', 'array'],
            'platform.*' => ['string', 'max:255'],
            'has_video' => ['nullable', 'boolean'],
            'linked_piece' => ['nullable', 'string', 'max:255'],
            'project_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,gif,webp', 'max:5120'],
        ]);

        if ($request->hasFile('project_image')) {
            $data['project_image'] = $request->file('project_image')->store('project_images', 'public');
        }

        $data['status'] = 'pendiente';
        $tags = $data['tags'] ?? null;
        $data['tags'] = $tags
            ? array_values(array_filter(array_map('trim', explode(',', $tags))))
            : [];

        $data['created_by'] = auth()->id();

        Task::create($data);

        return redirect()->route('marketing.tareas.index')
            ->with('status', 'Tarea creada correctamente.');
    }

    /**
     * Actualiza una tarea existente desde el modal del tablero.
     */
    public function update(Task $task, Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'task_description' => ['nullable', 'string'],
            'delivery_link' => ['nullable', 'url'],
            'status' => ['required', 'in:pendiente,en_proceso,revision,completada'],
            'priority' => ['required', 'in:baja,media,alta'],
            'due_date' => ['nullable', 'date'],
            'review_date' => ['nullable', 'date'],
            'progress' => ['required', 'integer', 'min:0', 'max:100'],
            'user_id' => ['required', 'exists:users,id'],
            'reviewer_id' => ['nullable', 'exists:users,id'],
            'tags' => ['nullable', 'string'],
            'category' => ['nullable', 'string', 'max:255'],
            'platform' => ['nullable', 'array'],
            'platform.*' => ['string', 'max:255'],
            'has_video' => ['nullable', 'boolean'],
            'linked_piece' => ['nullable', 'string', 'max:255'],
            'rejection_comment' => ['nullable', 'string'],
            'project_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,gif,webp', 'max:5120'],
        ]);

        if ($request->hasFile('project_image')) {
            $data['project_image'] = $request->file('project_image')->store('project_images', 'public');
        }

        $tags = $data['tags'] ?? null;
        $data['tags'] = $tags
            ? array_values(array_filter(array_map('trim', explode(',', $tags))))
            : [];

        $task->update($data);

        return redirect()->back()
            ->with('status', 'Tarea actualizada correctamente.');
    }

    /**
     * Muestra el tablero de aprobación de flyers.
     */
    /**
 * Muestra el tablero de aprobación de flyers.
 */
public function aprobacionFlyers(): View
{
    $tasks = Task::with(['user', 'reviewer'])->orderBy('due_date')->get();

    return view('structure.gestion_marketing.aprobacion_flyers.index', [
        'tasks' => $tasks,
        'stats' => [
            'por_hacer' => $tasks->where('status', 'pendiente')->count(),
            'en_curso' => $tasks->whereIn('status', ['en_proceso', 'revision'])->count(),
            'hecho' => $tasks->where('status', 'completada')->count(),
            'en_revision' => $tasks->where('status', 'revision')->count(),
            'cambios' => $tasks->where('status', 'pendiente')->whereNotNull('rejection_comment')->count(),
            'aprobado' => $tasks->where('status', 'completada')->count(),
        ],
    ]);
}

    /**
     * Muestra el inicio del módulo de marketing con estadísticas en vivo.
     */
    public function inicio(): View
    {
        $cambiosSolicitados = Task::where('status', 'pendiente')
            ->whereNotNull('rejection_comment')
            ->count();

        $enRevision = Task::where('status', 'revision')->count();

        $pendientePorTomar = Task::where('status', 'pendiente')->count();

        $areasEspecializadas = Task::whereNotNull('category')
            ->where('category', '!=', '')
            ->distinct()
            ->count('category');

        return view('structure.gestion_marketing.inicio.menu_marketing', [
            'inicioStats' => [
                'cambios_solicitados' => $cambiosSolicitados,
                'en_revision' => $enRevision,
                'pendiente_por_tomar' => $pendientePorTomar,
                'areas_especializadas' => $areasEspecializadas,
            ],
        ]);
    }

    /**
     * Ruta antigua del calendario de contenido: la vista real vive en
     * marketing.calendario.index, aquí solo se redirige por si queda algún
     * enlace guardado a esta URL.
     */
    public function agenda(): RedirectResponse
    {
        return redirect()->route('marketing.calendario.index');
    }

    /**
     * Muestra la biblioteca y catálogo de áreas.
     */
    public function bibliotecaCatalogo(): View
    {
        $flyers = Task::where('status', 'completada')
            ->where(function ($query) {
                $query->whereNotNull('project_image')
                    ->where('project_image', '!=', '');
            })
            ->orderByDesc('updated_at')
            ->get();

        return view('structure.gestion_marketing.catalogo.biblioteca_catalogo', [
            'flyers' => $flyers,
        ]);
    }

    /**
     * Descarga el flyer asociado a una tarea completada.
     */
    public function descargarFlyer(Task $task): RedirectResponse|\Symfony\Component\HttpFoundation\StreamedResponse
    {
        if ($task->status !== 'completada' || (empty($task->delivery_link) && empty($task->project_image))) {
            abort(404);
        }

        if (!empty($task->project_image)) {
            $extension = pathinfo($task->project_image, PATHINFO_EXTENSION) ?: 'jpg';
            $filename = preg_replace('/[^A-Za-z0-9\-]/', '_', $task->title) . '_flyer.' . $extension;
            return Storage::disk('public')->download($task->project_image, $filename);
        }

        try {
            $response = Http::timeout(30)->get($task->delivery_link);
        } catch (\Exception $e) {
            return redirect()->away($task->delivery_link);
        }

        $contentType = $response->header('Content-Type') ?: 'application/octet-stream';

        // Si Canva/Google devuelve HTML, lo mejor es abrir la URL original.
        if (str_contains($contentType, 'text/html')) {
            return redirect()->away($task->delivery_link);
        }

        $extension = 'file';
        if (preg_match('/\.([a-zA-Z0-9]{2,8})(?:[?#]|$)/', $task->delivery_link, $matches)) {
            $extension = $matches[1];
        }

        $filename = preg_replace('/[^A-Za-z0-9\-]/', '_', $task->title) . '_flyer.' . $extension;

        return response()->streamDownload(function () use ($response) {
            echo $response->body();
        }, $filename, [
            'Content-Type' => $contentType,
        ]);
    }

    /**
     * Muestra la guía de marca del equipo de marketing.
     */
    public function guiaDeMarca(): View
    {
        return view('structure.gestion_marketing.guia_de_marca.index');
    }

    /**
     * Elimina una tarea del sistema.
     */
    public function destroy(Task $task): RedirectResponse
    {
        $task->delete();

        return redirect()->back()
            ->with('status', 'Tarea eliminada correctamente.');
    }

    /**
     * Aprueba una tarea en revisión.
     */
    public function aprobar(Task $task): RedirectResponse
    {
        $task->update([
            'status' => 'completada',
            'rejection_comment' => null,
            'progress' => 100,
        ]);

        return redirect()->back()
            ->with('status', 'Tarea aprobada correctamente.');
    }

    /**
     * Devuelve una tarea con comentario de corrección.
     */
    public function devolver(Task $task, Request $request): RedirectResponse
    {
        $data = $request->validate([
            'rejection_comment' => ['required', 'string'],
            'approval_checklist' => ['nullable', 'array'],
        ]);

        $items = [
            'Nombre/modelo',
            'Specs verificados',
            'Marca/logo',
            'Precio/política',
            'Ortografía',
            'Datos de contacto',
            'Sin claims indebidos',
            'Formato de red',
            'Imagen nítida',
            'Leyenda salud',
        ];

        $previous = $task->approval_checklist ?? [];
        $submitted = array_map('intval', $data['approval_checklist'] ?? []);
        $final = array_values(array_unique(array_merge($previous, $submitted)));

        $missing = collect($items)
            ->filter(fn ($_, $index) => !in_array($index, $final))
            ->values()
            ->all();

        $comment = trim($data['rejection_comment']);
        $missingText = $missing
            ? 'Checklist pendiente: ' . implode(', ', $missing)
            : 'Checklist completo';

        $task->update([
            'status' => 'pendiente',
            'approval_checklist' => $final,
            'progress' => min(count($final) * 10, 100),
            'rejection_comment' => "Especificaciones:\n{$comment}\n\n{$missingText}",
        ]);

        return redirect()->back()
            ->with('status', 'Pieza devuelta con observaciones.');
    }

    /**
     * Envía una tarea pendiente a revisión (aprobación de flyers).
     */
    public function enviarRevision(Task $task): RedirectResponse
    {
        $task->update([
            'status' => 'revision',
        ]);

        return redirect()->back()
            ->with('status', 'Pieza enviada a revisión.');
    }
    /**
     * Devuelve una previsualizacion hibrida de un link de entrega:
     *  - YouTube / Shorts / youtu.be  -> embed de video
     *  - Canva                         -> embed de diseno
     *  - Vimeo                         -> embed de video
     *  - Google Drive (file/d/...)     -> preview de Drive
     *  - Imagen directa (.jpg/.png/...) -> <img>
     *  - Cualquier otro link           -> tarjeta OpenGraph (og:image/title/description)
     *
     * El resultado se cachea 7 dias por URL para no refetchear en cada apertura de modal.
     */
    public function previewLink(Request $request): JsonResponse
    {
        $data = $request->validate([
            'url' => ['required', 'url', 'max:2000'],
        ]);

        $url = $data['url'];
        $cacheKey = 'link_preview:' . md5($url);

        $preview = Cache::remember($cacheKey, now()->addDays(7), function () use ($url) {
            return $this->resolverPreview($url);
        });

        return response()->json($preview);
    }

    /**
     * Resuelve el tipo de preview y sus datos para una URL dada.
     *
     * @return array{type: string, url: string, embed_url?: string, title?: string, description?: string, image?: string}
     */
    private function resolverPreview(string $url): array
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        $path = (string) parse_url($url, PHP_URL_PATH);
        $query = (string) parse_url($url, PHP_URL_QUERY);
        parse_str($query, $queryParams);

        // 1) YouTube: youtube.com/watch?v=ID, youtu.be/ID, youtube.com/shorts/ID, youtube.com/embed/ID
        if ($host === 'www.youtube.com' || $host === 'youtube.com' || $host === 'm.youtube.com' || $host === 'youtu.be') {
            $videoId = null;
            if ($host === 'youtu.be') {
                $videoId = ltrim($path, '/');
            } elseif (isset($queryParams['v'])) {
                $videoId = $queryParams['v'];
            } elseif (preg_match('#/(?:shorts|embed)/([A-Za-z0-9_-]{6,})#', $path, $m)) {
                $videoId = $m[1];
            }
            if ($videoId) {
                return [
                    'type' => 'youtube',
                    'url' => $url,
                    'embed_url' => 'https://www.youtube.com/embed/' . $videoId,
                    'title' => 'Video de YouTube',
                ];
            }
        }

        // 2) Canva: canva.com/design/.../edit o /view
        if (str_contains($host, 'canva.com')) {
            // Canva soporta oEmbed: https://www.canva.com/_oembed?url=...
            try {
                $oembed = Http::timeout(8)->get('https://www.canva.com/_oembed', ['url' => $url]);
                if ($oembed->ok()) {
                    $data = $oembed->json();
                    $html = $data['html'] ?? null;
                    if ($html) {
                        return [
                            'type' => 'canva',
                            'url' => $url,
                            'embed_html' => $html,
                            'title' => $data['title'] ?? 'Diseno de Canva',
                            'image' => $data['thumbnail_url'] ?? null,
                        ];
                    }
                }
            } catch (\Throwable $e) {
                // fallback abajo
            }
            // Fallback: usar el link de embed de Canva
            if (preg_match('#https://([a-z0-9.-]*canva\.com)/design/([A-Za-z0-9_-]+)/#', $url, $m)) {
                $embed = 'https://' . $m[1] . '/design/' . $m[2] . '/watch?embed';
                return [
                    'type' => 'canva',
                    'url' => $url,
                    'embed_url' => $embed,
                    'title' => 'Diseno de Canva',
                ];
            }
        }

        // 3) Vimeo: vimeo.com/ID
        if (($host === 'vimeo.com' || $host === 'www.vimeo.com') && preg_match('#/(\d{5,})#', $path, $m)) {
            return [
                'type' => 'vimeo',
                'url' => $url,
                'embed_url' => 'https://player.vimeo.com/video/' . $m[1],
                'title' => 'Video de Vimeo',
            ];
        }

        // 4) Google Drive: drive.google.com/file/d/ID/view o /preview
        if (str_contains($host, 'drive.google.com') && preg_match('#/file/d/([A-Za-z0-9_-]{10,})#', $path, $m)) {
            return [
                'type' => 'drive',
                'url' => $url,
                'embed_url' => 'https://drive.google.com/file/d/' . $m[1] . '/preview',
                'title' => 'Archivo de Google Drive',
            ];
        }

        // 5) Imagen directa por extension
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp'], true)) {
            return [
                'type' => 'image',
                'url' => $url,
                'image' => $url,
            ];
        }

        // 6) OpenGraph: fetchear HTML y parsear meta tags
        return $this->resolverOpenGraph($url);
    }

    /**
     * Hace fetch del HTML y extrae metadatos OpenGraph / Twitter / <title>.
     *
     * @return array{type: string, url: string, title?: string, description?: string, image?: string}
     */
    private function resolverOpenGraph(string $url): array
    {
        $preview = ['type' => 'og', 'url' => $url];

        try {
            $response = Http::timeout(8)
                ->withHeaders(['User-Agent' => 'Mozilla/5.0 (compatible; ObsidianaBot/1.0; +link-preview)'])
                ->get($url);
        } catch (\Throwable $e) {
            $preview['type'] = 'link';
            $preview['title'] = parse_url($url, PHP_URL_HOST) ?: $url;
            return $preview;
        }

        if (!$response->ok()) {
            $preview['type'] = 'link';
            $preview['title'] = parse_url($url, PHP_URL_HOST) ?: $url;
            return $preview;
        }

        $html = $response->body();
        if (empty($html)) {
            $preview['type'] = 'link';
            $preview['title'] = parse_url($url, PHP_URL_HOST) ?: $url;
            return $preview;
        }

        // Extraer <meta property="og:..." content="...">
        $metas = [];
        if (preg_match_all('#<meta[^>]+(?:property|name)=["\']([^"\']+)["\'][^>]+content=["\']([^"\']*)["\']#i', $html, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $m) {
                $key = strtolower($m[1]);
                if (!isset($metas[$key])) {
                    $metas[$key] = html_entity_decode($m[2], ENT_QUOTES | ENT_HTML5);
                }
            }
        }

        $preview['title'] = $metas['og:title']
            ?? $metas['twitter:title']
            ?? $this->extraerTitleTag($html)
            ?? parse_url($url, PHP_URL_HOST)
            ?: $url;

        $preview['description'] = $metas['og:description']
            ?? $metas['twitter:description']
            ?? $metas['description']
            ?? null;

        $preview['image'] = $metas['og:image']
            ?? $metas['og:image:secure_url']
            ?? $metas['twitter:image']
            ?? null;

        // Si no hay imagen ni titulo util, fallback a link simple
        if (empty($preview['image']) && ($preview['title'] === $url || empty($preview['title']))) {
            $preview['type'] = 'link';
        }

        return $preview;
    }

    private function extraerTitleTag(string $html): ?string
    {
        if (preg_match('#<title[^>]*>([^<]+)</title>#i', $html, $m)) {
            $title = trim(html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5));
            return $title !== '' ? $title : null;
        }
        return null;
    }
}
