<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;

use App\Models\Task;
use App\Models\User;
use App\Services\Marketing\PreviewDeEnlaces;
use App\Services\Marketing\TareasDeMarketing;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class TaskController extends Controller
{
    public function __construct(private TareasDeMarketing $tareas)
    {
    }

    /**
     * Muestra el tablero Kanban de tareas de marketing con filtros y estadísticas.
     */
    public function index(Request $request): View
    {
        $query = $this->tareas->filtrar(Task::with(['user', 'creator', 'reviewer']), $request);

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

        $stats = $this->tareas->estadisticas($tasks);

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
            'statuses' => TareasDeMarketing::ESTADOS,
            'priorities' => TareasDeMarketing::PRIORIDADES,
        ]);
    }

    /**
     * Guarda una nueva tarea en el sistema.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->tareas->crear($request);

        return redirect()->route('marketing.tareas.index')
            ->with('status', 'Tarea creada correctamente.');
    }

    /**
     * Actualiza una tarea existente desde el modal del tablero.
     */
    public function update(Task $task, Request $request): RedirectResponse
    {
        $this->tareas->actualizar($task, $request);

        return redirect()->back()
            ->with('status', 'Tarea actualizada correctamente.');
    }

    /**
     * Muestra el tablero de aprobación de flyers.
     */
    public function aprobacionFlyers(): View
    {
        $tasks = Task::with(['user', 'reviewer'])->orderBy('due_date')->get();

        return view('structure.gestion_marketing.aprobacion_flyers.index', [
            'tasks' => $tasks,
            'stats' => $this->tareas->estadisticasDeAprobacion($tasks),
        ]);
    }

    /**
     * Muestra el inicio del módulo de marketing con estadísticas en vivo.
     */
    public function inicio(): View
    {
        return view('structure.gestion_marketing.inicio.menu_marketing', [
            'inicioStats' => $this->tareas->resumenInicio(),
        ]);
    }

    /**
     * Muestra el calendario de contenido de marketing.
     */
    public function agenda(): View
    {
        return view('structure.gestion_marketing.calendario.calendario', [
            'users' => User::orderBy('name')->get(['id', 'name']),
        ]);
    }

    
    /**
     * Muestra la biblioteca y catálogo de áreas.
     */
    public function bibliotecaCatalogo(): View
    {
        return view('structure.gestion_marketing.catalogo.biblioteca_catalogo', [
            'flyers' => $this->tareas->flyersDeBiblioteca()->get(),
        ]);
    }

    /**
     * Descarga el flyer asociado a una tarea completada.
     */
    public function descargarFlyer(Task $task): Response
    {
        return $this->tareas->descargarFlyer($task);
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
        $this->tareas->aprobar($task);

        return redirect()->back()
            ->with('status', 'Tarea aprobada correctamente.');
    }

    /**
     * Devuelve una tarea con comentario de corrección.
     */
    public function devolver(Task $task, Request $request): RedirectResponse
    {
        $this->tareas->devolver($task, $request);

        return redirect()->back()
            ->with('status', 'Pieza devuelta con observaciones.');
    }

    /**
     * Envía una tarea pendiente a revisión (aprobación de flyers).
     */
    public function enviarRevision(Task $task): RedirectResponse
    {
        $this->tareas->enviarARevision($task);

        return redirect()->back()
            ->with('status', 'Pieza enviada a revisión.');
    }

    /**
     * Previsualización del link de entrega (YouTube, Canva, Vimeo, Drive,
     * imagen u OpenGraph). Ver PreviewDeEnlaces.
     */
    public function previewLink(Request $request, PreviewDeEnlaces $previews): JsonResponse
    {
        $data = $request->validate([
            'url' => ['required', 'url', 'max:2000'],
        ]);

        return response()->json($previews->obtener($data['url']));
    }
}
