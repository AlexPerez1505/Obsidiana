<?php

namespace App\Services\Marketing;

use App\Models\Task;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

/**
 * Las tareas (piezas) de marketing: alta, edición, el flujo de revisión
 * (pendiente → revisión → completada, o de regreso con observaciones) y
 * los números de los tableros. Lo comparten las pantallas web y la API,
 * para que una pieza se apruebe o se devuelva con las mismas reglas.
 */
class TareasDeMarketing
{
    public const ESTADOS = [
        'pendiente' => 'Pendiente',
        'en_proceso' => 'En proceso',
        'revision' => 'Revisión',
        'completada' => 'Completada',
    ];

    public const PRIORIDADES = [
        'baja' => 'Baja',
        'media' => 'Media',
        'alta' => 'Alta',
    ];

    /**
     * Lo que se revisa antes de aprobar un flyer. Se guarda el índice de
     * cada punto palomeado (approval_checklist), así que el orden importa:
     * agregar puntos al final, nunca intercalar.
     */
    public const CHECKLIST = [
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

    /** Categorías del calendario de contenido, con su color. */
    public const CATEGORIAS = [
        ['name' => 'Educación', 'color' => '#facc15'],
        ['name' => 'Equipos', 'color' => '#fb923c'],
        ['name' => 'Carruseles', 'color' => '#f43f5e'],
        ['name' => 'Antes/después congreso', 'color' => '#ef4444'],
        ['name' => 'Congreso', 'color' => '#22c55e'],
        ['name' => 'Cumpleaños', 'color' => '#a855f7'],
        ['name' => 'Días conmemorativos', 'color' => '#3b82f6'],
        ['name' => 'Promociones', 'color' => '#06b6d4'],
    ];

    /**
     * Filtros del tablero: ?q= (título, descripción o etiqueta exacta),
     * ?priority=, ?user_id= y ?due=today|week|overdue.
     */
    public function filtrar(Builder $query, Request $request): Builder
    {
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

        return $query;
    }

    /**
     * Contadores del tablero sobre las tareas ya filtradas. Las vencidas
     * se cuentan sobre todas: es una alerta, no depende de lo que se esté
     * viendo.
     *
     * @param  Collection<int, Task>  $tasks
     * @return array{total: int, pendiente: int, en_proceso: int, revision: int, completada: int, overdue: int, high_priority: int}
     */
    public function estadisticas(Collection $tasks): array
    {
        return [
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
    }

    /**
     * Contadores del tablero de aprobación de flyers. "Cambios" son las
     * piezas que regresaron a pendiente con observaciones.
     *
     * @param  Collection<int, Task>  $tasks
     * @return array{por_hacer: int, en_curso: int, hecho: int, en_revision: int, cambios: int, aprobado: int}
     */
    public function estadisticasDeAprobacion(Collection $tasks): array
    {
        return [
            'por_hacer' => $tasks->where('status', 'pendiente')->count(),
            'en_curso' => $tasks->whereIn('status', ['en_proceso', 'revision'])->count(),
            'hecho' => $tasks->where('status', 'completada')->count(),
            'en_revision' => $tasks->where('status', 'revision')->count(),
            'cambios' => $tasks->where('status', 'pendiente')->whereNotNull('rejection_comment')->count(),
            'aprobado' => $tasks->where('status', 'completada')->count(),
        ];
    }

    /**
     * Números del inicio de Marketing.
     *
     * @return array{cambios_solicitados: int, en_revision: int, pendiente_por_tomar: int, areas_especializadas: int}
     */
    public function resumenInicio(): array
    {
        return [
            'cambios_solicitados' => Task::where('status', 'pendiente')
                ->whereNotNull('rejection_comment')
                ->count(),
            'en_revision' => Task::where('status', 'revision')->count(),
            'pendiente_por_tomar' => Task::where('status', 'pendiente')->count(),
            'areas_especializadas' => Task::whereNotNull('category')
                ->where('category', '!=', '')
                ->distinct()
                ->count('category'),
        ];
    }

    /** Las piezas que caen en un mes del calendario (por fecha de entrega). */
    public function delMes(int $month, int $year): Builder
    {
        return Task::with('user')
            ->whereMonth('due_date', $month)
            ->whereYear('due_date', $year)
            ->orderBy('due_date');
    }

    /** Flyers terminados con imagen: lo que se ve en la biblioteca. */
    public function flyersDeBiblioteca(): Builder
    {
        return Task::where('status', 'completada')
            ->where(function ($query) {
                $query->whereNotNull('project_image')
                    ->where('project_image', '!=', '');
            })
            ->orderByDesc('updated_at');
    }

    /** Alta de una pieza. Siempre nace pendiente. */
    public function crear(Request $request): Task
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
        $data['tags'] = $this->etiquetas($data['tags'] ?? null);
        $data['created_by'] = auth()->id();

        return Task::create($data);
    }

    /** Edición completa de una pieza (incluye cambiarla de estado a mano). */
    public function actualizar(Task $task, Request $request): Task
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

        $data['tags'] = $this->etiquetas($data['tags'] ?? null);

        $task->update($data);

        return $task;
    }

    /** Aprobada = terminada al 100 % y sin observaciones pendientes. */
    public function aprobar(Task $task): Task
    {
        $task->update([
            'status' => 'completada',
            'rejection_comment' => null,
            'progress' => 100,
        ]);

        return $task;
    }

    /**
     * Regresa la pieza a pendiente con las observaciones del revisor. Lo
     * palomeado en revisiones anteriores se conserva (se suma a lo nuevo)
     * y el avance se vuelve a calcular por puntos cumplidos.
     */
    public function devolver(Task $task, Request $request): Task
    {
        $data = $request->validate([
            'rejection_comment' => ['required', 'string'],
            'approval_checklist' => ['nullable', 'array'],
        ]);

        $previous = $task->approval_checklist ?? [];
        $submitted = array_map('intval', $data['approval_checklist'] ?? []);
        $final = array_values(array_unique(array_merge($previous, $submitted)));

        $missing = collect(self::CHECKLIST)
            ->filter(fn ($_, $index) => ! in_array($index, $final))
            ->values()
            ->all();

        $comment = trim($data['rejection_comment']);
        $missingText = $missing
            ? 'Checklist pendiente: '.implode(', ', $missing)
            : 'Checklist completo';

        $task->update([
            'status' => 'pendiente',
            'approval_checklist' => $final,
            'progress' => min(count($final) * 10, 100),
            'rejection_comment' => "Especificaciones:\n{$comment}\n\n{$missingText}",
        ]);

        return $task;
    }

    public function enviarARevision(Task $task): Task
    {
        $task->update([
            'status' => 'revision',
        ]);

        return $task;
    }

    /**
     * Descarga el flyer de una pieza terminada: la imagen subida si la
     * hay; si no, intenta bajar el enlace de entrega. Canva y Drive suelen
     * contestar con su página (HTML) en vez del archivo, y en ese caso se
     * manda al usuario al enlace original.
     */
    public function descargarFlyer(Task $task): Response
    {
        if ($task->status !== 'completada' || (empty($task->delivery_link) && empty($task->project_image))) {
            abort(404);
        }

        if (! empty($task->project_image)) {
            $extension = pathinfo($task->project_image, PATHINFO_EXTENSION) ?: 'jpg';
            $filename = preg_replace('/[^A-Za-z0-9\-]/', '_', $task->title).'_flyer.'.$extension;

            return Storage::disk('public')->download($task->project_image, $filename);
        }

        try {
            $response = Http::timeout(30)->get($task->delivery_link);
        } catch (\Exception $e) {
            return redirect()->away($task->delivery_link);
        }

        $contentType = $response->header('Content-Type') ?: 'application/octet-stream';

        if (str_contains($contentType, 'text/html')) {
            return redirect()->away($task->delivery_link);
        }

        $extension = 'file';
        if (preg_match('/\.([a-zA-Z0-9]{2,8})(?:[?#]|$)/', $task->delivery_link, $matches)) {
            $extension = $matches[1];
        }

        $filename = preg_replace('/[^A-Za-z0-9\-]/', '_', $task->title).'_flyer.'.$extension;

        return response()->streamDownload(function () use ($response) {
            echo $response->body();
        }, $filename, [
            'Content-Type' => $contentType,
        ]);
    }

    /**
     * Las etiquetas llegan como texto separado por comas (así las captura
     * el formulario) y se guardan como lista limpia.
     *
     * @return list<string>
     */
    private function etiquetas(?string $tags): array
    {
        return $tags
            ? array_values(array_filter(array_map('trim', explode(',', $tags))))
            : [];
    }
}
