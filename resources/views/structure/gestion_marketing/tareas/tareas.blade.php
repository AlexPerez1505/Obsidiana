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
    .task-field label {
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
                            $firstTag = ($task->tags[0] ?? null);
                            $badge = $badgeColors[strtolower($firstTag ?? '')] ?? $badgeDefault;
                            $category = $task->tags[1] ?? null;
                            $categoryColor = $categoryColors[(crc32(strtolower($category ?? '')) % count($categoryColors))] ?? 'var(--primary)';
                            $priority = $priorityColors[$task->priority] ?? $priorityColors['media'];
                            $borderLeft = '3px solid ' . $priority['border'];
                            $initial = $task->user ? mb_substr($task->user->name, 0, 1) : '?';
                            $userColor = $userColors[($task->user->id ?? 0) % count($userColors)];
                            $fecha = $task->due_date
                                ? $task->due_date->day . ' de ' . strtolower($meses[$task->due_date->month - 1])
                                : 'Sin fecha';
                        @endphp

                        <div class="kanban-card" style="border-left:{{ $borderLeft }};" data-json="{{ json_encode(['id' => $task->id, 'title' => $task->title, 'firstTag' => $firstTag, 'category' => $task->category, 'reviewer' => $task->reviewer?->name, 'due_date' => $task->due_date?->format('d/m/Y'), 'user' => $task->user?->name, 'linked_piece' => $task->linked_piece, 'delivery_link' => $task->delivery_link, 'platform' => $task->platform, 'has_video' => $task->has_video, 'rejection_comment' => $task->rejection_comment, 'task_description' => $task->task_description, 'description' => $task->description]) }}" onclick="openTaskModal(this)">
                            <h3 class="kanban-card-title">{{ $task->title }}</h3>

                            <div class="kanban-card-line">
                                <span class="kanban-badge" style="background:{{ $badge['bg'] }}; color:{{ $badge['color'] }};">
                                    <span class="kanban-badge-dot" style="background:{{ $badge['dot'] }}"></span>
                                    {{ $firstTag ?? 'Tarea' }}
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
    <div class="task-modal-card">
        <div class="task-modal-head">
            <div>
                <div class="task-modal-eyebrow">Detalle de tarea</div>
                <h1 class="task-modal-title" id="modalTitle">Tarea</h1>
            </div>
            <button type="button" class="task-modal-close" onclick="closeTaskModal()" title="Cerrar">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="18" height="18"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>

        <div class="task-modal-scroll">
            <div class="task-field">
                <label>Pieza / Título</label>
                <div class="task-value" id="modalTitleValue">—</div>
            </div>

            <div class="task-row">
                <div class="task-field">
                    <label>Categoría</label>
                    <div class="task-value empty" id="modalCategory">—</div>
                </div>
                <div class="task-field">
                    <label>Estado</label>
                    <div class="task-value" id="modalStatus">—</div>
                </div>
            </div>

            <div class="task-row">
                <div class="task-field">
                    <label>Fecha</label>
                    <div class="task-value" id="modalDate">—</div>
                </div>
                <div class="task-field">
                    <label>Responsable</label>
                    <div class="task-value" id="modalUser">—</div>
                </div>
            </div>

            <div class="task-row">
                <div class="task-field">
                    <label>Revisor</label>
                    <div class="task-value empty" id="modalReviewer">—</div>
                </div>
                <div class="task-field">
                    <label>Producto / Equipo</label>
                    <div class="task-value" id="modalProduct">—</div>
                </div>
            </div>

            <div class="task-row">
                <div class="task-field">
                    <label>Plataforma destino</label>
                    <div class="task-value empty" id="modalPlatform">—</div>
                </div>
                <div class="task-field">
                    <label>Video</label>
                    <div class="task-value empty" id="modalVideo">—</div>
                </div>
            </div>

            <div class="task-field">
                <label>Enlace (Canva / Drive)</label>
                <div class="task-value" id="modalLink">—</div>
            </div>

            <div class="task-field">
                <label>Comentarios de revisión</label>
                <div class="task-value" id="modalComments">—</div>
            </div>

            <div class="task-field">
                <label>Descripción</label>
                <div class="task-value empty" id="modalDesc">—</div>
            </div>

            <div class="task-field">
                <label>Copy / Texto del post</label>
                <div class="task-value" id="modalCopy">—</div>
            </div>

            <div class="task-field">
                <label>Entrega — Imagen o video (vista previa del enlace)</label>
                <div class="task-preview">
                    Aún no hay imagen/video. Pega el enlace arriba y aquí lo verá todo el equipo.
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function openTaskModal(card) {
        const data = JSON.parse(card.dataset.json);
        document.getElementById('modalTitle').textContent = data.title || 'Tarea';
        setText('modalTitleValue', data.title);
        setText('modalCategory', data.category);
        setText('modalStatus', data.firstTag);
        setText('modalDate', data.due_date);
        setText('modalUser', data.user);
        setText('modalReviewer', data.reviewer);
        setText('modalProduct', data.linked_piece);
        setText('modalPlatform', data.platform && data.platform.length ? data.platform.join(', ') : null);
        setText('modalVideo', data.has_video ? 'Sí' : null);
        setLink('modalLink', data.delivery_link);
        setText('modalComments', data.rejection_comment);
        setText('modalDesc', data.task_description);
        setText('modalCopy', data.description);
        document.getElementById('taskModal').classList.add('is-open');
    }

    function closeTaskModal() {
        document.getElementById('taskModal').classList.remove('is-open');
    }

    function setText(id, value) {
        const el = document.getElementById(id);
        if (!value) {
            el.textContent = '—';
            el.classList.add('empty');
            return;
        }
        el.textContent = value;
        el.classList.remove('empty');
    }

    function setLink(id, url) {
        const el = document.getElementById(id);
        if (!url) {
            el.textContent = '—';
            el.classList.add('empty');
            return;
        }
        el.innerHTML = '<a href="' + url + '" target="_blank" rel="noopener">' + url + '</a>';
        el.classList.remove('empty');
    }

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeTaskModal();
    });
</script>
@endsection
