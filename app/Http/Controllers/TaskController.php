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
        $query = Task::with(['user', 'creator']);

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
            'delivery_link' => ['nullable', 'url'],
            'status' => ['required', 'in:pendiente,en_proceso,revision,completada'],
            'priority' => ['required', 'in:baja,media,alta'],
            'due_date' => ['nullable', 'date'],
            'review_date' => ['nullable', 'date'],
            'progress' => ['required', 'integer', 'min:0', 'max:100'],
            'user_id' => ['required', 'exists:users,id'],
            'tags' => ['nullable', 'string'],
            'linked_piece' => ['nullable', 'string', 'max:255'],
            'rejection_comment' => ['nullable', 'string'],
        ]);

        $data['tags'] = $data['tags']
            ? array_values(array_filter(array_map('trim', explode(',', $data['tags']))))
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
            'delivery_link' => ['nullable', 'url'],
            'status' => ['required', 'in:pendiente,en_proceso,revision,completada'],
            'priority' => ['required', 'in:baja,media,alta'],
            'due_date' => ['nullable', 'date'],
            'review_date' => ['nullable', 'date'],
            'progress' => ['required', 'integer', 'min:0', 'max:100'],
            'user_id' => ['required', 'exists:users,id'],
            'tags' => ['nullable', 'string'],
            'linked_piece' => ['nullable', 'string', 'max:255'],
            'rejection_comment' => ['nullable', 'string'],
        ]);

        $data['tags'] = $data['tags']
            ? array_values(array_filter(array_map('trim', explode(',', $data['tags']))))
            : [];

        $task->update($data);

        return redirect()->back()
            ->with('status', 'Tarea actualizada correctamente.');
    }

    /**
     * Muestra el tablero de aprobación de flyers.
     */
    public function aprobacionFlyers(): View
    {
        return view('structure.gestion_marketing.aprobacion_flyers.index');
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
        return view('structure.gestion_marketing.biblioteca_catalogo.index');
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
        ]);

        $task->update([
            'status' => 'en_proceso',
            'rejection_comment' => $data['rejection_comment'],
        ]);

        return redirect()->back()
            ->with('status', 'Tarea devuelta correctamente.');
    }
}
