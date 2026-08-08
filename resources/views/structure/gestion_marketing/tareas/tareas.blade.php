@extends('layouts.dashboard')

@section('title', 'Tareas')
@section('page-title', 'Tareas')
@section('page-sub', 'Pendientes del equipo de marketing. Asigna responsable, fecha y prioridad; vincula la tarea a una pieza del calendario si aplica.')

@section('content')
@php
    $columnDots = [
        'pendiente' => '#f59e0b',
        'en_proceso' => '#3b82f6',
        'completada' => '#22c55e',
    ];

    $priorityColors = [
        'baja' => ['bg' => 'rgba(34,197,94,.14)', 'color' => '#22c55e', 'border' => '#22c55e'],
        'media' => ['bg' => 'rgba(245,158,11,.14)', 'color' => '#f59e0b', 'border' => '#f59e0b'],
        'alta' => ['bg' => 'rgba(239,68,68,.14)', 'color' => '#ef4444', 'border' => '#ef4444'],
    ];

    $badgeColors = [
        'aprobado' => ['dot' => '#22c55e', 'bg' => 'rgba(34,197,94,.14)', 'color' => '#22c55e'],
        'aprobada' => ['dot' => '#22c55e', 'bg' => 'rgba(34,197,94,.14)', 'color' => '#22c55e'],
        'pendiente' => ['dot' => '#f59e0b', 'bg' => 'rgba(245,158,11,.14)', 'color' => '#f59e0b'],
        'idea' => ['dot' => '#ef4444', 'bg' => 'rgba(239,68,68,.14)', 'color' => '#ef4444'],
        'revisión' => ['dot' => '#a855f7', 'bg' => 'rgba(168,85,247,.14)', 'color' => '#a855f7'],
    ];
    $badgeDefault = ['dot' => 'var(--primary)', 'bg' => 'var(--primary-soft)', 'color' => 'var(--primary)'];

    $categoryColors = [
        '#3b82f6', '#22c55e', '#a855f7', '#f59e0b', '#0ea5e9', '#f43f5e', '#06b6d4',
    ];

    $userColors = ['#3b82f6', '#22c55e', '#a855f7', '#f59e0b', '#0ea5e9', '#ef4444'];

    $meses = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
@endphp

<style>
    .kanban-wrap { display:flex; flex-direction:column; gap:28px; }
    .kanban-header { display:flex; flex-wrap:wrap; align-items:flex-start; justify-content:space-between; gap:20px; }
    .kanban-header-left { display:flex; flex-direction:column; gap:8px; max-width:640px; }
    .kanban-tag { display:inline-flex; align-items:center; gap:6px; padding:5px 12px; border-radius:999px; background:var(--green-soft); color:var(--green); font-size:10px; font-weight:800; letter-spacing:0.06em; text-transform:uppercase; width:fit-content; }
    .kanban-tag svg { width:11px; height:11px; }
    .kanban-title { margin:0; font-size:32px; font-weight:800; line-height:1.1; letter-spacing:-0.02em; }
    .kanban-sub { margin:0; color:var(--muted); font-size:15px; line-height:1.5; }
    .kanban-new { display:inline-flex; align-items:center; gap:8px; padding:12px 18px; border-radius:12px; border:none; background:var(--primary); color:#fff; font-size:15px; font-weight:700; text-decoration:none; cursor:pointer; transition:background .15s; }
    .kanban-new:hover { background:var(--primary-strong); }
    .kanban-new svg { width:18px; height:18px; }

    .kanban-board { display:grid; grid-template-columns:repeat(3, minmax(0, 1fr)); gap:20px; align-items:start; }
    @media (max-width:1024px) { .kanban-board { grid-template-columns:repeat(2, minmax(0, 1fr)); } }
    @media (max-width:720px) { .kanban-board { grid-template-columns:1fr; } }

    .kanban-col { background:var(--surface); border:1px solid var(--border); border-radius:18px; padding:18px; display:flex; flex-direction:column; gap:14px; min-width:0; }
    .kanban-col-head { display:flex; align-items:center; gap:10px; }
    .kanban-col-dot { width:8px; height:8px; border-radius:50%; }
    .kanban-col-title { flex:1; font-size:15px; font-weight:800; margin:0; }
    .kanban-col-count { min-width:22px; height:22px; padding:0 7px; border-radius:999px; background:var(--bg); border:1px solid var(--border); color:var(--text); font-size:12px; font-weight:800; display:inline-flex; align-items:center; justify-content:center; margin-left:auto; }
    .kanban-col-body { display:flex; flex-direction:column; gap:12px; }

    .kanban-card { background:var(--surface-2); border:1px solid var(--border); border-left:3px solid var(--border); border-radius:14px; padding:16px 18px; display:flex; flex-direction:column; gap:12px; transition:transform .12s, box-shadow .12s; cursor:pointer; }
    .kanban-card:hover { transform:translateY(-2px); box-shadow:var(--shadow); }
    .kanban-card-title { margin:0; font-size:16px; font-weight:700; line-height:1.3; }
    .kanban-card-line { display:flex; flex-wrap:wrap; align-items:center; gap:8px; }
    .kanban-badge { display:inline-flex; align-items:center; gap:6px; padding:4px 10px; border-radius:999px; font-size:11px; font-weight:800; }
    .kanban-badge-dot { width:6px; height:6px; border-radius:50%; }
    .kanban-cat { display:inline-flex; align-items:center; gap:6px; padding:4px 10px; border-radius:999px; font-size:11px; font-weight:700; background:var(--primary-soft); color:var(--primary); }
    .kanban-cat-dot { width:6px; height:6px; border-radius:50%; }
    .kanban-pill { display:inline-flex; align-items:center; padding:4px 10px; border-radius:999px; font-size:10px; font-weight:800; text-transform:uppercase; }
    .kanban-footer { display:flex; align-items:center; gap:10px; margin-top:auto; padding-top:4px; }
    .kanban-avatar { width:26px; height:26px; border-radius:50%; display:flex; align-items:center; justify-content:center; color:#fff; font-size:11px; font-weight:800; flex:0 0 auto; }
    .kanban-assign { font-size:13px; font-weight:700; color:var(--text); }
    .kanban-date { margin-left:auto; font-size:12px; font-weight:700; color:var(--muted); }
    .kanban-empty { color:var(--muted); font-size:13px; text-align:center; padding:18px 8px; }

    .task-modal {
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,.6);
        display: none;
        align-items: center;
        justify-content: center;
        padding: 20px;
        z-index: 1000;
    }
    .task-modal.is-open { display: flex; }
    .task-modal-card {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: 22px;
        overflow: hidden;
        max-width: 720px;
        width: 100%;
        max-height: 90vh;
        display: flex;
        flex-direction: column;
    }
    .task-modal-scroll {
        overflow: auto;
        padding: 26px;
        display: flex;
        flex-direction: column;
        gap: 22px;
    }
    .task-modal-head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 16px;
        padding: 22px 26px;
        border-bottom: 1px solid var(--border);
    }
    .task-modal-eyebrow {
        font-size: 11px;
        font-weight: 800;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        color: var(--primary);
    }
    .task-modal-title {
        margin: 4px 0 0;
        font-size: 24px;
        font-weight: 800;
        letter-spacing: -0.02em;
    }
    .task-modal-close {
        width: 34px;
        height: 34px;
        border-radius: 10px;
        border: 1px solid var(--border);
        background: var(--surface-2);
        color: var(--muted);
        display: flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        cursor: pointer;
        transition: background .15s, color .15s;
    }
    .task-modal-close:hover { background: var(--surface); color: var(--text); }
    .task-field { display: flex; flex-direction: column; gap: 7px; }
    .task-field label:not(.platform-pill):not(.video-toggle),
    .task-field .field-label {
        font-size: 11px;
        font-weight: 800;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        color: var(--muted);
    }
    .task-value {
        padding: 12px 14px;
        border-radius: 12px;
        border: 1px solid var(--border);
        background: var(--surface-2);
        color: var(--text);
        font-size: 14px;
        min-height: 44px;
        line-height: 1.5;
    }
    .task-value a { color: var(--primary); text-decoration: none; word-break: break-all; }
    .task-value a:hover { text-decoration: underline; }
    .task-value.empty { color: var(--muted); }
    .task-row { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 18px; }
    @media (max-width: 640px) { .task-row { grid-template-columns: 1fr; } }
    .task-preview {
        border: 1px dashed var(--border);
        border-radius: 14px;
        padding: 30px 20px;
        text-align: center;
        color: var(--muted);
        font-size: 14px;
        line-height: 1.5;
        background: var(--surface-2);
    }
    .task-field input,
    .task-field select,
    .task-field textarea {
        width: 100%;
        padding: 12px 14px;
        border-radius: 12px;
        border: 1px solid var(--border);
        background: var(--surface-2);
        color: var(--text);
        font-size: 14px;
        outline: none;
        transition: border-color .15s, background .15s;
        font-family: inherit;
    }
    .task-field input:focus,
    .task-field select:focus,
    .task-field textarea:focus { border-color: var(--primary); background: var(--surface); }
    .task-field input::placeholder,
    .task-field textarea::placeholder { color: var(--muted); opacity: .7; }
    .task-field textarea { min-height: 90px; resize: vertical; }

    .task-footer {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 12px;
        padding: 18px 26px;
        border-top: 1px solid var(--border);
        background: var(--surface-2);
    }
    .task-save {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 11px 22px;
        border-radius: 12px;
        border: none;
        background: #2563eb;
        color: #fff;
        font-size: 15px;
        font-weight: 700;
        cursor: pointer;
        transition: background .15s;
    }
    .task-save:hover { background: #1d4ed8; }
    .task-review {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 11px 22px;
        border-radius: 12px;
        border: none;
        background: #0ea5e9;
        color: #fff;
        font-size: 15px;
        font-weight: 700;
        cursor: pointer;
        transition: background .15s;
    }
    .task-review:hover { background: #0284c7; }

    .platform-choices {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
    }
    .platform-pill {
        position: relative;
        cursor: pointer;
    }
    .platform-pill input {
        position: absolute;
        opacity: 0;
        width: 0;
        height: 0;
    }
    .platform-pill span {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 8px 14px;
        border-radius: 22px;
        border: 1px solid var(--border);
        background: var(--surface-2);
        color: var(--text);
        font-size: 13px;
        font-weight: 600;
        letter-spacing: normal;
        text-transform: none;
        transition: all .15s;
    }
    .platform-pill input:checked + span {
        background: var(--primary);
        border-color: var(--primary);
        color: #fff;
    }
    .platform-pill input:focus + span { box-shadow: 0 0 0 2px var(--primary-soft); }

    .video-toggle {
        display: inline-flex;
        align-items: center;
        gap: 12px;
        cursor: pointer;
        font-size: 14px;
        font-weight: 600;
        letter-spacing: normal;
        text-transform: none;
        color: var(--text);
    }
    .video-toggle input {
        position: absolute;
        opacity: 0;
        width: 0;
        height: 0;
    }
    .video-knob {
        width: 44px;
        height: 24px;
        border-radius: 12px;
        background: var(--surface-2);
        border: 1px solid var(--border);
        position: relative;
        transition: background .15s, border-color .15s;
    }
    .video-knob::after {
        content: '';
        position: absolute;
        top: 2px;
        left: 2px;
        width: 18px;
        height: 18px;
        border-radius: 50%;
        background: var(--text);
        transition: transform .15s;
    }
    .video-toggle input:checked + .video-knob {
        background: var(--primary);
        border-color: var(--primary);
    }
    .video-toggle input:checked + .video-knob::after {
        transform: translateX(20px);
        background: #fff;
    }
    .video-text { color: var(--muted); }
    .video-toggle input:checked ~ .video-text { color: var(--text); }
</style>

<div class="kanban-wrap">
    <div class="kanban-header">
        <div class="kanban-header-left">
            <span class="kanban-tag">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                Operación del equipo
            </span>
            <h1 class="kanban-title">Tareas</h1>
            <p class="kanban-sub">Pendientes del equipo de marketing. Asigna responsable, fecha y prioridad; vincula la tarea a una pieza del calendario si aplica.</p>
        </div>
        <a href="{{ route('marketing.tareas.create') }}" class="kanban-new">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Nueva tarea
        </a>
    </div>

    <div class="kanban-board">
        @foreach ($columns as $column)
            <div class="kanban-col">
                <div class="kanban-col-head">
                    <span class="kanban-col-dot" style="background:{{ $columnDots[$column['id']] ?? 'var(--muted)' }}"></span>
                    <h2 class="kanban-col-title">{{ $column['title'] }}</h2>
                    <span class="kanban-col-count">{{ $column['tasks']->count() }}</span>
                </div>

                <div class="kanban-col-body">
                    @forelse ($column['tasks'] as $task)
                        @php
                            $statusBadges = [
                                'pendiente' => ['dot' => '#f59e0b', 'bg' => 'rgba(245,158,11,.14)', 'color' => '#f59e0b', 'label' => 'Por hacer'],
                                'en_proceso' => ['dot' => '#3b82f6', 'bg' => 'rgba(59,130,246,.14)', 'color' => '#3b82f6', 'label' => 'En curso'],
                                'revision' => ['dot' => '#a855f7', 'bg' => 'rgba(168,85,247,.14)', 'color' => '#a855f7', 'label' => 'En revisión'],
                                'completada' => ['dot' => '#22c55e', 'bg' => 'rgba(34,197,94,.14)', 'color' => '#22c55e', 'label' => 'Hecho'],
                            ];
                            $statusBadge = $statusBadges[$task->status] ?? $badgeDefault;
                            $category = $task->category;
                            $categoryColor = $categoryColors[(crc32(strtolower($category ?? '')) % count($categoryColors))] ?? 'var(--primary)';
                            $priority = $priorityColors[$task->priority] ?? $priorityColors['media'];
                            $borderLeft = '3px solid ' . $priority['border'];
                            $initial = $task->user ? mb_substr($task->user->name, 0, 1) : '?';
                            $userColor = $userColors[($task->user->id ?? 0) % count($userColors)];
                            $fecha = $task->due_date
                                ? $task->due_date->day . ' de ' . strtolower($meses[$task->due_date->month - 1])
                                : 'Sin fecha';
                        @endphp

                        <div class="kanban-card" style="border-left:{{ $borderLeft }};" data-json="{{ json_encode(['id' => $task->id, 'title' => $task->title, 'category' => $task->category, 'reviewer' => $task->reviewer?->name, 'reviewer_id' => $task->reviewer_id, 'due_date' => $task->due_date?->format('Y-m-d'), 'user' => $task->user?->name, 'user_id' => $task->user_id, 'status' => $task->status, 'priority' => $task->priority, 'progress' => $task->progress, 'linked_piece' => $task->linked_piece, 'delivery_link' => $task->delivery_link, 'platform' => $task->platform, 'has_video' => $task->has_video, 'approval_checklist' => $task->approval_checklist ?? [], 'rejection_comment' => $task->rejection_comment, 'task_description' => $task->task_description, 'description' => $task->description], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) }}" onclick="openTaskModal(this)">
                            <h3 class="kanban-card-title">{{ $task->title }}</h3>

                            <div class="kanban-card-line">
                                <span class="kanban-badge" style="background:{{ $statusBadge['bg'] }}; color:{{ $statusBadge['color'] }};">
                                    <span class="kanban-badge-dot" style="background:{{ $statusBadge['dot'] }}"></span>
                                    {{ $statusBadge['label'] ?? 'Tarea' }}
                                </span>

                                @if ($category)
                                    <span class="kanban-cat" style="background:{{ $categoryColor }}1a; color:{{ $categoryColor }};">
                                        <span class="kanban-cat-dot" style="background:{{ $categoryColor }}"></span>
                                        {{ $category }}
                                    </span>
                                @endif
                            </div>

                            <div class="kanban-footer">
                                <span class="kanban-pill" style="background:{{ $priority['bg'] }}; color:{{ $priority['color'] }}; border:1px solid {{ $priority['border'] }};">
                                    {{ strtoupper($task->priority) }}
                                </span>

                                @if ($task->user)
                                    <span class="kanban-avatar" style="background:{{ $userColor }};">{{ $initial }}</span>
                                    <span class="kanban-assign">{{ $task->user->name }}</span>
                                @endif

                                <span class="kanban-date">{{ $fecha }}</span>
                            </div>

                        </div>
                    @empty
                        <div class="kanban-empty">No hay tareas en esta columna.</div>
                    @endforelse
                </div>
            </div>
        @endforeach
    </div>
</div>

<div class="task-modal" id="taskModal" onclick="if(event.target === this) closeTaskModal()">
    <form method="POST" id="taskEditForm" class="task-modal-card">
        @csrf
        @method('PUT')
        <input type="hidden" id="editStatus" name="status">
        <input type="hidden" id="editPriority" name="priority">
        <input type="hidden" id="editProgress" name="progress">

        <div class="task-modal-head">
            <div>
                <div class="task-modal-eyebrow">Editar tarea</div>
                <h1 class="task-modal-title" id="modalTitle">Tarea</h1>
            </div>
            <button type="button" class="task-modal-close" onclick="closeTaskModal()" title="Cerrar">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="18" height="18"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>

        <div class="task-modal-scroll">
            <div class="task-field">
                <label for="editTitle">Pieza / Título</label>
                <input type="text" id="editTitle" name="title" placeholder="Título de la pieza..." required>
            </div>

            <div class="task-row">
                <div class="task-field">
                    <label for="editCategory">Categoría</label>
                    <select id="editCategory" name="category">
                        <option value="">—</option>
                        <option value="Antes / Después">Antes / Después</option>
                        <option value="Carruseles">Carruseles</option>
                        <option value="Congreso">Congreso</option>
                        <option value="Cumpleaños">Cumpleaños</option>
                        <option value="Días conmemorativos">Días conmemorativos</option>
                        <option value="Educación">Educación</option>
                        <option value="Equipos">Equipos</option>
                        <option value="Promociones">Promociones</option>
                    </select>
                </div>

            </div>

            <div class="task-row">
                <div class="task-field">
                    <label for="editDueDate">Fecha</label>
                    <input type="date" id="editDueDate" name="due_date">
                </div>
                <div class="task-field">
                    <label for="editUser">Responsable</label>
                    <select id="editUser" name="user_id" required>
                        <option value="">—</option>
                        @foreach ($users as $u)
                            <option value="{{ $u->id }}">{{ $u->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="task-row">
                <div class="task-field">
                    <label for="editReviewer">Revisor</label>
                    <select id="editReviewer" name="reviewer_id">
                        <option value="">—</option>
                        @foreach ($users as $u)
                            <option value="{{ $u->id }}">{{ $u->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="task-field">
                    <label for="editProduct">Producto / Equipo</label>
                    <input type="text" id="editProduct" name="linked_piece" placeholder="Ej. Endoscopia">
                </div>
            </div>

            <div class="task-row">
                <div class="task-field">
                    <span class="field-label">Plataforma destino</span>
                    <div class="platform-choices">
                        @foreach (['Facebook','Instagram','LinkedIn','TikTok','Todas','WhatsApp','YouTube'] as $platform)
                            <label class="platform-pill">
                                <input type="checkbox" name="platform[]" value="{{ $platform }}" class="edit-platform">
                                <span>{{ $platform }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
                <div class="task-field">
                    <span class="field-label">Video</span>
                    <label class="video-toggle" for="editHasVideo">
                        <input type="checkbox" id="editHasVideo" name="has_video" value="1">
                        <span class="video-knob"></span>
                        <span class="video-text">¿Incluye video?</span>
                    </label>
                </div>
            </div>

            <div class="task-field">
                <label for="editLink">Enlace (Canva / Drive)</label>
                <input type="url" id="editLink" name="delivery_link" placeholder="Pega el link de Canva o Drive del flyer">
            </div>

            <div class="task-field">
                <label for="editComments">Comentarios del revisor</label>
                <textarea id="editComments" name="rejection_comment" placeholder="Notas del revisor..."></textarea>
            </div>

            <div class="task-field" id="reviewChecklistField" style="display:none;">
                <span class="field-label">Checklist de revisión</span>
                <div id="reviewChecklist" class="task-value" style="display:flex;flex-direction:column;gap:6px;"></div>
            </div>

            <div class="task-field">
                <label for="editTaskDesc">Descripción</label>
                <textarea id="editTaskDesc" name="task_description" placeholder="Descripción de la pieza / instrucciones..."></textarea>
            </div>

            <div class="task-field">
                <label for="editCopy">Copy / Texto del post</label>
                <textarea id="editCopy" name="description" placeholder="Texto de la publicación..."></textarea>
            </div>

            <div class="task-field">
                <span class="field-label">Entrega — Imagen o video (vista previa del enlace)</span>
                <div class="task-preview">
                    Aún no hay imagen/video. Pega el enlace arriba y aquí lo verá todo el equipo.
                </div>
            </div>
        </div>

        <div class="task-footer">
            <button type="button" class="task-review" id="btnEnviarRevision" style="display:none;" onclick="enviarARevision()">
                Mandar a revisión
            </button>
            <button type="submit" class="task-save" id="btnGuardar">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="18" height="18"><polyline points="20 6 9 17 4 12"/></svg>
                Guardar cambios
            </button>
        </div>
    </form>
</div>

<script>
    var approvalLabels = [
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
    var currentTaskId = null;

    function openTaskModal(card) {
        var data = JSON.parse(card.dataset.json);
        var form = document.getElementById('taskEditForm');
        form.action = '/marketing/tareas/' + data.id;

        document.getElementById('modalTitle').textContent = data.title || 'Tarea';
        document.getElementById('editTitle').value = data.title || '';
        document.getElementById('editCategory').value = data.category || '';
        document.getElementById('editDueDate').value = data.due_date || '';
        document.getElementById('editUser').value = data.user_id || '';
        document.getElementById('editReviewer').value = data.reviewer_id || '';
        document.getElementById('editProduct').value = data.linked_piece || '';
        document.getElementById('editLink').value = data.delivery_link || '';
        document.getElementById('editComments').value = data.rejection_comment || '';
        document.getElementById('editTaskDesc').value = data.task_description || '';
        document.getElementById('editCopy').value = data.description || '';
        document.getElementById('editStatus').value = data.status || 'pendiente';
        document.getElementById('editPriority').value = data.priority || 'media';
        document.getElementById('editProgress').value = data.progress || 0;

        Array.prototype.forEach.call(document.querySelectorAll('.edit-platform'), function(cb) {
            cb.checked = data.platform && data.platform.indexOf(cb.value) !== -1;
        });
        document.getElementById('editHasVideo').checked = data.has_video === true || data.has_video === 1 || data.has_video === '1';

        var approvalList = Array.isArray(data.approval_checklist) ? data.approval_checklist : [];
        var checked = approvalList.map(function(n) { return parseInt(n, 10); });
        var reviewField = document.getElementById('reviewChecklistField');
        var reviewBox = document.getElementById('reviewChecklist');
        if (data.rejection_comment || checked.length) {
            reviewBox.innerHTML = approvalLabels.map(function(label, index) {
                var isChecked = checked.indexOf(index) !== -1;
                var icon = isChecked ? '\u2713' : '\u2717';
                var color = isChecked ? '#22c55e' : '#ef4444';
                return '<div style="color:' + color + ';font-weight:600;">' + icon + ' ' + label + '</div>';
            }).join('');
            reviewField.style.display = 'flex';
        } else {
            reviewBox.innerHTML = '';
            reviewField.style.display = 'none';
        }

        currentTaskId = data.id;
        var isPendiente = data.status === 'pendiente';
        var form = document.getElementById('taskEditForm');
        var fields = form.querySelectorAll('input:not([type="hidden"]), select, textarea');
        for (var i = 0; i < fields.length; i++) {
            fields[i].disabled = !isPendiente;
        }
        document.getElementById('btnGuardar').style.display = isPendiente ? '' : 'none';
        document.getElementById('btnEnviarRevision').style.display = isPendiente ? '' : 'none';

        document.getElementById('taskModal').classList.add('is-open');
    }

    function enviarARevision() {
        if (!currentTaskId) return;
        var token = document.querySelector('#taskEditForm input[name="_token"]').value;
        var formData = new FormData();
        formData.append('_token', token);
        formData.append('_method', 'PUT');

        fetch('/marketing/tareas/' + currentTaskId + '/enviar-revision', {
            method: 'POST',
            body: formData,
        }).then(function(response) {
            if (response.ok) {
                location.reload();
            } else {
                alert('No se pudo enviar a revisión. Intenta de nuevo.');
            }
        }).catch(function() {
            alert('No se pudo enviar a revisión. Intenta de nuevo.');
        });
    }

    function closeTaskModal() {
        document.getElementById('taskModal').classList.remove('is-open');
    }

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeTaskModal();
    });
</script>
@endsection
