<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        ]);

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
        ]);

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
     * Muestra el calendario de contenido del plan editorial.
     */
    public function agenda(): View
    {
        return view('structure.gestion_marketing.agenda.index', [
            'users' => User::orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * Muestra la biblioteca y catálogo de áreas.
     */
    public function bibliotecaCatalogo(): View
    {
        return view('structure.gestion_marketing.catalogo.biblioteca_catalogo');
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
}
