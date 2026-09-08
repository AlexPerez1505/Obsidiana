@extends('layouts.dashboard')

@section('title', 'Calendario de contenido')
@section('page-title', 'Calendario de contenido')
@section('page-sub', 'El plan del mes por categoría y responsable. Cada pieza pasa por el tablero de aprobación antes de publicarse.')

@section('content')
@php
    use Carbon\Carbon;

    $month = (int) request()->query('month', now()->month);
    $year  = (int) request()->query('year', now()->year);
    $current = Carbon::createFromDate($year, $month, 1)->startOfDay();
    $firstOfMonth = $current->copy()->startOfMonth();
    $daysInMonth = $current->daysInMonth;
    $startOffset = ($firstOfMonth->dayOfWeek + 6) % 7;
    $today = now()->toDateString();

    $categories = [
        ['name' => 'Educación', 'color' => '#facc15'],
        ['name' => 'Equipos', 'color' => '#fb923c'],
        ['name' => 'Carruseles', 'color' => '#f43f5e'],
        ['name' => 'Antes/después congreso', 'color' => '#ef4444'],
        ['name' => 'Congreso', 'color' => '#22c55e'],
        ['name' => 'Cumpleaños', 'color' => '#a855f7'],
        ['name' => 'Días conmemorativos', 'color' => '#3b82f6'],
        ['name' => 'Promociones', 'color' => '#06b6d4'],
    ];

    $responsables = App\Models\User::orderBy('name')->get(['id', 'name']);

    $userColors = ['#3b82f6', '#22c55e', '#a855f7', '#f59e0b', '#0ea5e9', '#ef4444'];
    $users = $responsables->map(fn ($u, $i) => [
        'name' => $u->name,
        'color' => $userColors[$i % count($userColors)],
        'initial' => mb_substr($u->name, 0, 1),
    ]);
    $estados = ['pendiente' => 'Por hacer', 'en_proceso' => 'En proceso', 'revision' => 'Revisión', 'completada' => 'Completada'];
    $prioridades = ['baja' => 'Baja', 'media' => 'Media', 'alta' => 'Alta'];
    $statusColors = ['pendiente' => '#f59e0b', 'en_proceso' => '#3b82f6', 'revision' => '#a855f7', 'completada' => '#22c55e'];

    $tasks = App\Models\Task::with('user')
        ->whereMonth('due_date', $month)
        ->whereYear('due_date', $year)
        ->orderBy('due_date')
        ->get();

    $allTasks = App\Models\Task::with(['user','reviewer'])->orderBy('due_date','desc')->get();

    $tasksForJs = $allTasks->map(fn ($t) => [
        'id' => $t->id,
        'title' => $t->title,
        'category' => $t->category,
        'status' => $t->status,
        'priority' => $t->priority,
        'due_date' => $t->due_date ? $t->due_date->format('Y-m-d') : null,
        'review_date' => $t->review_date ? $t->review_date->format('Y-m-d') : null,
        'user_id' => $t->user_id,
        'user_name' => $t->user?->name,
        'reviewer_id' => $t->reviewer_id,
        'reviewer_name' => $t->reviewer?->name,
        'delivery_link' => $t->delivery_link,
        'description' => $t->description,
        'task_description' => $t->task_description,
        'linked_piece' => $t->linked_piece,
        'platform' => $t->platform,
        'has_video' => $t->has_video,
        'project_image' => $t->project_image ? asset('storage/'.$t->project_image) : null,
        'rejection_comment' => $t->rejection_comment,
        'approval_checklist' => $t->approval_checklist,
        'progress' => $t->progress,
    ]);

    $tasksByCategory = $tasksForJs->groupBy('category');

    $tasksByDate = $tasks->groupBy(fn ($t) => $t->due_date ? $t->due_date->toDateString() : null);
@endphp

<style>
    .calendario-wrap { display:flex; flex-direction:column; gap:20px; }
    .calendario-header { display:flex; flex-wrap:wrap; align-items:center; gap:18px; }
    .calendario-nav { display:flex; align-items:center; gap:12px; background:var(--surface); border:1px solid var(--border); border-radius:999px; padding:6px 14px; }
    .calendario-nav button, .calendario-nav a { display:flex; align-items:center; justify-content:center; width:32px; height:32px; border-radius:50%; border:none; background:transparent; color:var(--text); cursor:pointer; transition:background .15s; }
    .calendario-nav button:hover, .calendario-nav a:hover { background:var(--surface-2); }
    .calendario-nav span { font-weight:700; font-size:15px; min-width:110px; text-align:center; }
    .calendario-hoy { padding:8px 16px; border-radius:999px; border:none; background:var(--primary); color:#fff; font-weight:700; font-size:14px; cursor:pointer; }
    .calendario-hoy:hover { background:var(--primary-strong); }

    .calendario-filters { display:flex; flex-direction:column; gap:12px; }
    .calendario-filters-row { display:flex; flex-wrap:wrap; align-items:center; gap:8px; }
    .filter-chip { display:inline-flex; align-items:center; gap:6px; padding:6px 12px; border-radius:999px; border:1px solid var(--border); background:var(--surface); color:var(--text); font-size:13px; font-weight:600; cursor:pointer; transition:background .15s, border-color .15s, color .15s; }
    .filter-chip:hover { background:var(--surface-2); }
    .filter-chip.active { background:var(--primary-soft); color:var(--primary); border-color:var(--primary); }
    .filter-chip-dot { width:10px; height:10px; border-radius:50%; }
    .filter-chip-badge { width:20px; height:20px; border-radius:50%; display:flex; align-items:center; justify-content:center; color:#fff; font-size:10px; font-weight:800; }
    .filter-chip-badge svg { width:10px; height:10px; }

    .calendario-grid { display:grid; grid-template-columns:repeat(7, 1fr); border:1px solid var(--border); border-radius:14px; overflow:hidden; background:var(--surface); }
    .calendario-day-head { background:var(--primary); color:#fff; padding:14px 8px; text-align:center; font-size:13px; font-weight:700; text-transform:uppercase; }
    .calendario-day { min-height:120px; padding:10px; border-right:1px solid var(--border); border-bottom:1px solid var(--border); display:flex; flex-direction:column; justify-content:space-between; }
    .calendario-day:nth-child(7n) { border-right:none; }
    .calendario-day:nth-last-child(-n+7) { border-bottom:none; }
    .calendario-day.empty { background:var(--surface-2); }
    .calendario-day-number { width:28px; height:28px; border-radius:8px; display:flex; align-items:center; justify-content:center; font-size:14px; font-weight:700; color:var(--text); }
    .calendario-day.hoy { box-shadow:inset 0 0 0 2px var(--primary); }
    .calendario-day.hoy .calendario-day-number { background:var(--primary); color:#fff; }
    .calendario-day-hoy { align-self:flex-start; font-size:9px; font-weight:800; letter-spacing:.04em; color:var(--primary); background:var(--primary-soft); padding:2px 6px; border-radius:999px; margin-top:4px; }
    .calendario-day-empty { color:var(--muted); font-size:12px; margin-top:auto; }
    .calendario-add-task { align-self:flex-start; font-size:11px; font-weight:700; padding:4px 8px; border-radius:999px; border:1px solid var(--border); background:transparent; color:var(--muted); cursor:pointer; margin-top:auto; }
    .calendario-add-task:hover { background:var(--primary-soft); color:var(--primary); border-color:var(--primary); }
    .calendario-day { cursor:pointer; transition:background .15s; }
    .calendario-day:hover:not(.empty) { background:var(--surface-2); }
    .calendario-day-content { margin-top:auto; display:flex; flex-direction:column; gap:6px; }
    .calendario-task { display:flex; align-items:center; gap:6px; padding:5px 8px; border-radius:8px; background:var(--surface-2); border:1px solid var(--border); font-size:11px; font-weight:700; cursor:pointer; transition:background .15s; }
    .calendario-task:hover { background:var(--surface); }
    .calendario-task-dot { width:7px; height:7px; border-radius:50%; flex:0 0 auto; }
    .calendario-task-title { flex:1; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .calendario-task-avatar { width:18px; height:18px; border-radius:50%; display:flex; align-items:center; justify-content:center; color:#fff; font-size:9px; font-weight:800; flex:0 0 auto; }

    /* Modal nueva tarea */
    .modal-overlay { position:fixed; inset:0; background:rgba(0,0,0,.55); backdrop-filter:blur(4px); z-index:100; display:none; align-items:center; justify-content:center; padding:20px; }
    .modal-overlay.active { display:flex; }
    .modal { width:100%; max-width:540px; max-height:90vh; overflow-y:auto; background:var(--surface); border:1px solid var(--border); border-radius:20px; box-shadow:var(--shadow); display:flex; flex-direction:column; }
    .modal-head { display:flex; align-items:flex-start; justify-content:space-between; gap:16px; padding:22px 24px 0; }
    .modal-sub { font-size:12px; font-weight:700; text-transform:uppercase; color:var(--primary); margin:0; }
    .modal-title { font-size:24px; font-weight:800; margin:6px 0 0; }
    .modal-close { background:transparent; border:none; color:var(--muted); cursor:pointer; padding:6px; border-radius:8px; transition:background .15s; }
    .modal-close:hover { background:var(--surface-2); }
    .modal-body { padding:22px 24px; display:flex; flex-direction:column; gap:18px; }
    .modal-field { display:flex; flex-direction:column; gap:6px; }
    .modal-label { font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:0.04em; color:var(--muted); display:flex; align-items:center; gap:6px; }
    .modal-label svg { width:12px; height:12px; }
    .modal-input, .modal-textarea, .modal-select { width:100%; background:var(--surface-2); border:1px solid var(--border); border-radius:12px; padding:11px 14px; color:var(--text); font-family:inherit; font-size:14px; outline:none; transition:border-color .15s, background .15s; }
    .modal-input:focus, .modal-textarea:focus, .modal-select:focus { border-color:var(--primary); background:var(--surface); }
    .modal-textarea { min-height:90px; resize:vertical; }
    .modal-preview { min-height:120px; border:1px dashed var(--border); border-radius:12px; display:flex; align-items:center; justify-content:center; text-align:center; padding:20px; color:var(--muted); font-size:13px; }
    .modal-preview:has(iframe), .modal-preview:has(.lp-card) { padding:0; border-style:solid; border-color:var(--border); background:var(--surface-2); display:block; text-align:left; }
    .lp-card { display:flex; border-radius:12px; overflow:hidden; background:var(--surface); text-decoration:none; color:inherit; }
    .lp-card:hover { box-shadow:0 4px 16px rgba(0,0,0,.12); }
    .lp-card-img { width:140px; min-height:120px; max-height:180px; object-fit:cover; flex-shrink:0; background:var(--surface-2); }
    .lp-card-body { padding:14px 16px; display:flex; flex-direction:column; gap:6px; min-width:0; flex:1; }
    .lp-card-title { font-size:14px; font-weight:700; color:var(--text); line-height:1.35; overflow:hidden; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; }
    .lp-card-desc { font-size:12px; color:var(--muted); line-height:1.45; overflow:hidden; display:-webkit-box; -webkit-line-clamp:3; -webkit-box-orient:vertical; margin:0; }
    .lp-card-host { font-size:11px; color:var(--muted); text-transform:uppercase; letter-spacing:0.04em; font-weight:700; margin-top:auto; display:inline-flex; align-items:center; gap:5px; }
    .lp-embed { width:100%; aspect-ratio:16/9; border:0; border-radius:12px; display:block; background:#000; }
    .lp-embed.canva { aspect-ratio:auto; min-height:300px; }
    .lp-img-full { width:100%; max-height:400px; object-fit:contain; border-radius:12px; display:block; background:var(--surface-2); }
    .lp-loading { display:inline-flex; align-items:center; gap:8px; color:var(--muted); font-size:13px; }
    .lp-loading::before { content:''; width:14px; height:14px; border:2px solid var(--border); border-top-color:var(--primary); border-radius:50%; animation:lp-spin .7s linear infinite; }
    @keyframes lp-spin { to { transform:rotate(360deg); } }
    .lp-error { color:var(--danger, #c0392b); font-size:13px; }
    .modal-dates { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
    .modal-row { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
    .modal-hint { font-size:12px; color:var(--muted); line-height:1.5; padding:12px 14px; border:1px dashed var(--border); border-radius:10px; }
    .modal-status-pill { display:inline-flex; align-items:center; gap:8px; font-size:13px; font-weight:700; padding:8px 14px; border-radius:10px; border:1px solid var(--border); }
    .modal-foot { display:flex; justify-content:flex-end; gap:10px; padding:14px 24px 20px; }
    .btn-guardar { display:inline-flex; align-items:center; gap:8px; padding:12px 22px; border:none; border-radius:12px; background:var(--primary); color:#fff; font-weight:700; font-size:15px; cursor:pointer; }
    .btn-guardar:hover { background:var(--primary-strong); }
    .modal-foot .btn-guardar { padding:8px 16px; font-size:13px; border-radius:10px; }
    .rev-tag { display:inline-block; font-size:10px; font-weight:800; color:var(--primary); background:var(--primary-soft); padding:2px 6px; border-radius:6px; margin-left:auto; }
</style>

<div class="calendario-wrap">
    <div class="calendario-header">
        <div class="calendario-nav">
            <a href="{{ route('marketing.calendario.index', ['month' => $current->copy()->subMonth()->month, 'year' => $current->copy()->subMonth()->year]) }}" aria-label="Mes anterior">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><polyline points="15 18 9 12 15 6"/></svg>
            </a>
            <span>{{ ucfirst($current->locale('es')->translatedFormat('F Y')) }}</span>
            <a href="{{ route('marketing.calendario.index', ['month' => $current->copy()->addMonth()->month, 'year' => $current->copy()->addMonth()->year]) }}" aria-label="Mes siguiente">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><polyline points="9 18 15 12 9 6"/></svg>
            </a>
        </div>
        <a href="{{ route('marketing.calendario.index', ['month' => now()->month, 'year' => now()->year]) }}" class="calendario-hoy">Hoy</a>
    </div>

    <div class="calendario-filters">
        <div class="calendario-filters-row" id="category-filters">
            @foreach ($categories as $cat)
                <button type="button" class="filter-chip" data-filter="category" data-value="{{ $cat['name'] }}">
                    <span class="filter-chip-dot" style="background-color:{{ $cat['color'] }};"></span>
                    @if (!empty($cat['hasIcon']))
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="10" height="10" style="color:var(--muted);"><polyline points="20 6 9 17 4 12"/></svg>
                    @endif
                    {{ $cat['name'] }}
                </button>
            @endforeach
        </div>

        <div class="calendario-filters-row" id="user-filters">
            @foreach ($users as $u)
                <button type="button" class="filter-chip" data-filter="user" data-value="{{ $u['name'] }}">
                    <span class="filter-chip-badge" style="background-color:{{ $u['color'] }};">
                        @if (!empty($u['isVideo']))
                            <svg viewBox="0 0 24 24" fill="currentColor" width="10" height="10"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                        @else
                            {{ $u['initial'] }}
                        @endif
                    </span>
                    {{ $u['name'] }}
                </button>
            @endforeach
        </div>
    </div>

    <div class="calendario-grid" id="calendario-grid">
        @foreach (['Lun','Mar','Mié','Jue','Vie','Sáb','Dom'] as $dayName)
            <div class="calendario-day-head">{{ $dayName }}</div>
        @endforeach

        @for ($i = 0; $i < $startOffset; $i++)
            <div class="calendario-day empty"></div>
        @endfor

        @for ($day = 1; $day <= $daysInMonth; $day++)
            @php
                $dateString = $current->copy()->setDay($day)->toDateString();
                $isToday = $dateString === $today;
                $dayTasks = $tasksByDate->get($dateString, collect());
            @endphp
            <div class="calendario-day {{ $isToday ? 'hoy' : '' }}" data-date="{{ $dateString }}" onclick="openTaskModal('{{ $dateString }}')">
                <div>
                    <div class="calendario-day-number">{{ $day }}</div>
                    @if ($isToday)
                        <div class="calendario-day-hoy">HOY</div>
                    @endif
                </div>

                <div class="calendario-day-content">
                    @if ($dayTasks->isEmpty())
                        <div class="calendario-day-empty">—</div>
                    @else
                        @foreach ($dayTasks as $task)
                            @php
                                $statusColor = $statusColors[$task->status] ?? '#64748b';
                                $userName = $task->user ? $task->user->name : '';
                                $uMap = collect($users)->first(fn ($u) => $u['name'] === $userName);
                                $uColor = $uMap['color'] ?? '#64748b';
                                $uInitial = $uMap['initial'] ?? ($task->user ? mb_substr($task->user->name, 0, 1) : '?');
                            @endphp
                            <div class="calendario-task"
                                 data-id='@json($task->id)'
                                 data-title='@json($task->title)'
                                 data-description='@json($task->description ?? '')'
                                 data-delivery-link='@json($task->delivery_link ?? '')'
                                 data-status='@json($task->status)'
                                 data-priority='@json($task->priority)'
                                 data-due-date='@json($task->due_date ? $task->due_date->format('Y-m-d') : '')'
                                 data-review-date='@json($task->review_date ? $task->review_date->format('Y-m-d') : '')'
                                 data-user-id='@json($task->user_id)'
                                 data-linked-piece='@json($task->linked_piece ?? '')'
                                 data-rejection-comment='@json($task->rejection_comment ?? '')'
                                 onclick="openViewModal(this, event)">
                                <span class="calendario-task-dot" style="background-color:{{ $statusColor }};"></span>
                                <span class="calendario-task-title">{{ $task->title }}</span>
                                <span class="calendario-task-avatar" style="background-color:{{ $uColor }};">{{ $uInitial }}</span>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
        @endfor
    </div>
</div>

<div class="modal-overlay" id="task-modal-overlay" onclick="closeTaskModal(event)">
    <form method="POST" action="{{ route('marketing.tareas.store') }}" class="modal" onclick="event.stopPropagation()">
        @csrf
        <div class="modal-head">
            <div>
                <p class="modal-sub">Nueva tarea</p>
                <h2 class="modal-title">Crear tarea</h2>
            </div>
            <button type="button" class="modal-close" onclick="closeTaskModal()" aria-label="Cerrar">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="22" height="22"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>

        <div class="modal-body">
            <div class="modal-field">
                <label class="modal-label">Título</label>
                <input type="text" name="title" class="modal-input" placeholder="¿Qué hay que hacer?" required>
            </div>

            <div class="modal-field">
                <label class="modal-label">
                    <svg viewBox="0 0 24 24" fill="currentColor" width="12" height="12"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                    Copy
                </label>
                <textarea name="description" class="modal-textarea" placeholder="Escribe aquí el copy / texto de la publicación..."></textarea>
            </div>

            <div class="modal-field">
                <label class="modal-label">
                    <svg viewBox="0 0 24 24" fill="currentColor" width="12" height="12"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                    Trabajo entregado (Canva / Drive / YouTube / imagen o video)
                </label>
                <input type="url" name="delivery_link" id="create-delivery-link" class="modal-input" placeholder="Pega el link de Canva, Drive o YouTube del trabajo (que esté cómo: cualquiera con el enlace)">
            </div>

            <div class="modal-field">
                <label class="modal-label">Vista previa de la entrega</label>
                <div class="modal-preview" id="delivery-preview">Aún no hay entrega. Pega el enlace arriba y aquí lo verá todo el equipo.</div>
            </div>

            <div class="modal-dates">
                <div class="modal-field">
                    <label class="modal-label">Fecha de revisión</label>
                    <input type="date" name="review_date" id="review_date" class="modal-input">
                </div>
                <div class="modal-field">
                    <label class="modal-label" style="justify-content:space-between;">
                        <span>Fecha de publicación</span>
                        <span class="rev-tag">rev -3d</span>
                    </label>
                    <input type="date" name="due_date" id="due_date" class="modal-input" onchange="updateReviewDate()">
                </div>
            </div>

            <p class="modal-hint">La fecha de revisión se pone automáticamente 3 días antes de la publicación. Ambas fechas aparecen juntas en el calendario.</p>

            <div class="modal-row">
                <div class="modal-field">
                    <label class="modal-label">Responsable</label>
                    <select name="user_id" class="modal-select" required>
                        <option value="">—</option>
                        @foreach ($responsables as $res)
                            <option value="{{ $res->id }}">{{ $res->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="modal-field">
                    <label class="modal-label">Estado</label>
                    <select name="status" class="modal-select" required>
                        @foreach ($estados as $key => $label)
                            <option value="{{ $key }}" {{ $key === 'pendiente' ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="modal-row">
                <div class="modal-field">
                    <label class="modal-label">Prioridad</label>
                    <select name="priority" class="modal-select" required>
                        @foreach ($prioridades as $key => $label)
                            <option value="{{ $key }}" {{ $key === 'media' ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="modal-field">
                    <label class="modal-label">Pieza vinculada</label>
                    <select name="linked_piece" class="modal-select">
                        <option value="" selected>— ninguna —</option>
                    </select>
                </div>
            </div>

            <div class="modal-status-pill">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="16" height="16"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                Revisión: <span id="modal-revision-text">Pendiente</span>
            </div>
        </div>

        <div class="modal-foot">
            <button type="submit" class="btn-guardar">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="18" height="18"><polyline points="20 6 9 17 4 12"/></svg>
                Guardar
            </button>
        </div>
        <input type="hidden" name="progress" value="0">
        <input type="hidden" name="tags" value="">
    </form>
</div>

<div class="modal-overlay" id="view-task-overlay" onclick="closeViewModal(event)">
    <div class="modal" onclick="event.stopPropagation()">
        <div class="modal-head">
            <div>
                <p class="modal-sub">Ver tarea</p>
                <h2 class="modal-title" id="view-task-title">Prueba</h2>
            </div>
            <button type="button" class="modal-close" onclick="closeViewModal()" aria-label="Cerrar">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="22" height="22"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>

        <div class="modal-body">
            <form id="edit-task-form" method="POST" action="{{ url('/marketing/tareas') }}" class="modal-body-form">
                @csrf
                <input type="hidden" name="_method" value="PUT">
                <input type="hidden" name="progress" value="0">
                <input type="hidden" name="tags" value="">

                <div class="modal-field">
                    <label class="modal-label">Título</label>
                    <input type="text" name="title" id="edit-title" class="modal-input" required>
                </div>

                <div class="modal-field">
                    <label class="modal-label">
                        <svg viewBox="0 0 24 24" fill="currentColor" width="12" height="12"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                        Copy
                    </label>
                    <textarea name="description" id="edit-description" class="modal-textarea" placeholder="Escribe aquí el copy / texto de la publicación..."></textarea>
                </div>

                <div class="modal-field">
                    <label class="modal-label">
                        <svg viewBox="0 0 24 24" fill="currentColor" width="12" height="12"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                        Trabajo entregado (Canva / Drive / YouTube / imagen o video)
                    </label>
                    <input type="url" name="delivery_link" id="edit-delivery-link" class="modal-input" placeholder="Pega el link de Canva, Drive o YouTube del trabajo (que esté cómo: cualquiera con el enlace)">
                </div>

                <div class="modal-field">
                    <label class="modal-label">Vista previa de la entrega</label>
                    <div class="modal-preview" id="edit-delivery-preview">Aún no hay entrega. Pega el enlace arriba y aquí lo verá todo el equipo.</div>
                </div>

                <div class="modal-dates">
                    <div class="modal-field">
                        <label class="modal-label">Fecha de revisión</label>
                        <input type="date" name="review_date" id="edit-review-date" class="modal-input">
                    </div>
                    <div class="modal-field">
                        <label class="modal-label" style="justify-content:space-between;">
                            <span>Fecha de publicación</span>
                            <span class="rev-tag">rev -3d</span>
                        </label>
                        <input type="date" name="due_date" id="edit-due-date" class="modal-input" onchange="updateEditReviewDate()">
                    </div>
                </div>

                <div class="modal-row">
                    <div class="modal-field">
                        <label class="modal-label">Responsable</label>
                        <select name="user_id" id="edit-user-id" class="modal-select" required>
                            <option value="">—</option>
                            @foreach ($responsables as $res)
                                <option value="{{ $res->id }}">{{ $res->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="modal-field">
                        <label class="modal-label">Estado</label>
                        <select name="status" id="edit-status" class="modal-select" required>
                            @foreach ($estados as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="modal-row">
                    <div class="modal-field">
                        <label class="modal-label">Prioridad</label>
                        <select name="priority" id="edit-priority" class="modal-select" required>
                            @foreach ($prioridades as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="modal-field">
                        <label class="modal-label">Pieza vinculada</label>
                        <select name="linked_piece" id="edit-linked-piece" class="modal-select">
                            <option value="" selected>— ninguna —</option>
                        </select>
                    </div>
                </div>

                <p class="modal-hint">La fecha de revisión se pone automáticamente 3 días antes de la publicación. Ambas fechas aparecen juntas en el calendario.</p>
            </form>

            <form id="return-task-form" method="POST" action="{{ url('/marketing/tareas') }}">
                @csrf
                <input type="hidden" name="_method" value="PUT">
                <div class="modal-status-pill">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="16" height="16"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    Revisión: <span id="edit-review-status">Pendiente</span>
                </div>
                <div class="modal-field">
                    <label class="modal-label" style="color:var(--danger);">
                        Comentario para devolver (si la rechazas)
                    </label>
                    <textarea name="rejection_comment" id="edit-rejection-comment" class="modal-textarea" placeholder="Escribe qué debe corregir..."></textarea>
                </div>
            </form>

            <form id="delete-task-form" method="POST" action="{{ url('/marketing/tareas') }}" style="display:none;">
                @csrf
                <input type="hidden" name="_method" value="DELETE">
            </form>

            <form id="approve-task-form" method="POST" action="{{ url('/marketing/tareas') }}" style="display:none;">
                @csrf
                <input type="hidden" name="_method" value="PUT">
            </form>
        </div>


    </div>
</div>

<div class="modal-overlay" id="category-overlay" onclick="closeCategoryModal(event)">
    <div class="modal" onclick="event.stopPropagation()">
        <div class="modal-head">
            <div>
                <p class="modal-sub">Tareas por categoría</p>
                <h2 class="modal-title" id="category-modal-title"></h2>
            </div>
            <button type="button" class="modal-close" onclick="closeCategoryModal()" aria-label="Cerrar">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="22" height="22"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <div class="modal-body" id="category-task-list" style="gap:12px;"></div>
    </div>
</div>

<script>
    const overlay = document.getElementById('task-modal-overlay');
    const dueInput = document.getElementById('due_date');
    const reviewInput = document.getElementById('review_date');

    function openTaskModal(date) {
        dueInput.value = date;
        updateReviewDate();
        overlay.classList.add('active');
    }

    function closeTaskModal(event) {
        if (!event || event.target === overlay) {
            overlay.classList.remove('active');
        }
    }

    function updateReviewDate() {
        if (!dueInput.value) return;
        const pub = new Date(dueInput.value);
        pub.setDate(pub.getDate() - 3);
        reviewInput.value = pub.toISOString().split('T')[0];
    }

    const viewOverlay = document.getElementById('view-task-overlay');
    const taskBaseUrl = '{{ url('/marketing/tareas') }}';
    const editForm = document.getElementById('edit-task-form');
    const deleteForm = document.getElementById('delete-task-form');
    const approveForm = document.getElementById('approve-task-form');
    const returnForm = document.getElementById('return-task-form');

    const editTitle = document.getElementById('view-task-title');
    const editTitleInput = document.getElementById('edit-title');
    const editDescription = document.getElementById('edit-description');
    const editDeliveryLink = document.getElementById('edit-delivery-link');
    const editDeliveryPreview = document.getElementById('edit-delivery-preview');
    const createDeliveryLink = document.getElementById('create-delivery-link');
    const createDeliveryPreview = document.getElementById('delivery-preview');
    const previewLinkUrl = '{{ route("marketing.tareas.preview_link") }}';
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const previewCache = {};
    let previewAbort = null;
    const editReviewDate = document.getElementById('edit-review-date');
    const editDueDate = document.getElementById('edit-due-date');
    const editUserId = document.getElementById('edit-user-id');
    const editStatus = document.getElementById('edit-status');
    const editPriority = document.getElementById('edit-priority');
    const editLinkedPiece = document.getElementById('edit-linked-piece');
    const editReviewStatus = document.getElementById('edit-review-status');
    const editRejectionComment = document.getElementById('edit-rejection-comment');
    const statusColors = @json($statusColors);
    const tasksByCategory = @json($tasksByCategory);
    const categoryOverlay = document.getElementById('category-overlay');
    const categoryList = document.getElementById('category-task-list');
    const categoryTitle = document.getElementById('category-modal-title');

    function updateEditReviewDate() {
        if (!editDueDate.value) return;
        const pub = new Date(editDueDate.value);
        pub.setDate(pub.getDate() - 3);
        editReviewDate.value = pub.toISOString().split('T')[0];
    }

    function escapeHtml(s) {
        const d = document.createElement('div');
        d.textContent = s ?? '';
        return d.innerHTML;
    }

    function hostFromUrl(url) {
        try { return new URL(url).hostname.replace(/^www\./, ''); }
        catch (e) { return url; }
    }

    function renderPreviewData(data, container) {
        if (!data || !data.type) {
            container.textContent = 'Aún no hay entrega. Pega el enlace arriba y aquí lo verá todo el equipo.';
            return;
        }
        const url = data.url || '';
        switch (data.type) {
            case 'youtube':
            case 'vimeo':
                container.innerHTML = '<iframe class="lp-embed" src="' + escapeHtml(data.embed_url) + '" allow="autoplay; fullscreen; encrypted-media" allowfullscreen loading="lazy"></iframe>';
                break;
            case 'canva':
                if (data.embed_html) {
                    container.innerHTML = data.embed_html;
                    const iframe = container.querySelector('iframe');
                    if (iframe) { iframe.className = 'lp-embed canva'; iframe.loading = 'lazy'; }
                } else {
                    container.innerHTML = '<iframe class="lp-embed canva" src="' + escapeHtml(data.embed_url) + '" allow="fullscreen" allowfullscreen loading="lazy"></iframe>';
                }
                break;
            case 'drive':
                container.innerHTML = '<iframe class="lp-embed" src="' + escapeHtml(data.embed_url) + '" allow="autoplay" allowfullscreen loading="lazy"></iframe>';
                break;
            case 'image':
                container.innerHTML = '<img class="lp-img-full" src="' + escapeHtml(data.image || url) + '" alt="Vista previa" loading="lazy">';
                break;
            case 'og':
            case 'link': {
                if (data.image) {
                    container.innerHTML = '<a class="lp-card" href="' + escapeHtml(url) + '" target="_blank" rel="noopener">' +
                        '<img class="lp-card-img" src="' + escapeHtml(data.image) + '" alt="" loading="lazy" onerror="this.style.display=\'none\'">' +
                        '<div class="lp-card-body">' +
                        '<span class="lp-card-title">' + escapeHtml(data.title || url) + '</span>' +
                        (data.description ? '<p class="lp-card-desc">' + escapeHtml(data.description) + '</p>' : '') +
                        '<span class="lp-card-host"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="11" height="11"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg> ' + escapeHtml(hostFromUrl(url)) + '</span>' +
                        '</div></a>';
                } else {
                    container.innerHTML = '<a class="lp-card" href="' + escapeHtml(url) + '" target="_blank" rel="noopener" style="padding:16px;">' +
                        '<div class="lp-card-body">' +
                        '<span class="lp-card-title">' + escapeHtml(data.title || url) + '</span>' +
                        '<span class="lp-card-host"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="11" height="11"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg> ' + escapeHtml(hostFromUrl(url)) + '</span>' +
                        '</div></a>';
                }
                break;
            }
            default:
                container.textContent = 'Aún no hay entrega. Pega el enlace arriba y aquí lo verá todo el equipo.';
        }
    }

    async function loadDeliveryPreview(url, container) {
        if (!url) {
            container.textContent = 'Aún no hay entrega. Pega el enlace arriba y aquí lo verá todo el equipo.';
            return;
        }
        if (previewCache[url]) {
            renderPreviewData(previewCache[url], container);
            return;
        }
        if (previewAbort) { try { previewAbort.abort(); } catch (e) {} }
        previewAbort = (window.AbortController) ? new AbortController() : null;
        container.innerHTML = '<span class="lp-loading">Cargando vista previa…</span>';
        try {
            const resp = await fetch(previewLinkUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ url: url }),
                signal: previewAbort ? previewAbort.signal : undefined,
            });
            const data = await resp.json();
            previewCache[url] = data;
            renderPreviewData(data, container);
        } catch (e) {
            if (e.name === 'AbortError') return;
            container.innerHTML = '<span class="lp-error">No se pudo cargar la vista previa. <a href="' + escapeHtml(url) + '" target="_blank" rel="noopener" style="color:inherit;text-decoration:underline;">Abrir enlace</a></span>';
        }
    }

    function updateEditPreview() {
        loadDeliveryPreview(editDeliveryLink.value.trim(), editDeliveryPreview);
    }

    function updateCreatePreview() {
        loadDeliveryPreview(createDeliveryLink.value.trim(), createDeliveryPreview);
    }

    let editDebounce = null, createDebounce = null;
    editDeliveryLink.addEventListener('input', function () {
        clearTimeout(editDebounce);
        editDebounce = setTimeout(updateEditPreview, 400);
    });
    if (createDeliveryLink) {
        createDeliveryLink.addEventListener('input', function () {
            clearTimeout(createDebounce);
            createDebounce = setTimeout(updateCreatePreview, 400);
        });
    }

    editDueDate.addEventListener('change', function () {
        if (!editReviewDate.value) {
            updateEditReviewDate();
        }
    });

    function setViewModalReadOnly(readonly) {
        if (viewOverlay) {
            viewOverlay.querySelectorAll('input, textarea, select').forEach(input => input.disabled = readonly);
        }
    }

    function openViewModal(el, event) {
        if (event) event.stopPropagation();
        const id = JSON.parse(el.dataset.id);

        editForm.action = taskBaseUrl + '/' + id;
        deleteForm.action = taskBaseUrl + '/' + id;
        approveForm.action = taskBaseUrl + '/' + id + '/aprobar';
        returnForm.action = taskBaseUrl + '/' + id + '/devolver';

        editTitle.textContent = JSON.parse(el.dataset.title);
        editTitleInput.value = JSON.parse(el.dataset.title);
        editDescription.value = JSON.parse(el.dataset.description);
        editDeliveryLink.value = JSON.parse(el.dataset.deliveryLink);
        updateEditPreview();
        editReviewDate.value = JSON.parse(el.dataset.reviewDate);
        editDueDate.value = JSON.parse(el.dataset.dueDate);
        editUserId.value = JSON.parse(el.dataset.userId);
        editStatus.value = JSON.parse(el.dataset.status);
        editPriority.value = JSON.parse(el.dataset.priority);
        editLinkedPiece.value = JSON.parse(el.dataset.linkedPiece);
        editRejectionComment.value = JSON.parse(el.dataset.rejectionComment);

        const status = JSON.parse(el.dataset.status);
        editReviewStatus.textContent = status === 'completada' ? 'Aprobada' : 'Pendiente';

        setViewModalReadOnly(true);
        viewOverlay.classList.add('active');
    }

    function closeViewModal(event) {
        if (!event || event.target === viewOverlay) {
            viewOverlay.classList.remove('active');
        }
    }

    function escapeHtml(text) {
        if (text == null) return '';
        return String(text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function openViewModalFromTask(task, event) {
        if (event) event.stopPropagation();
        categoryOverlay.classList.remove('active');
        const wrapper = document.createElement('div');
        wrapper.dataset.id = JSON.stringify(task.id);
        wrapper.dataset.title = JSON.stringify(task.title ?? '');
        wrapper.dataset.description = JSON.stringify(task.description ?? '');
        wrapper.dataset.deliveryLink = JSON.stringify(task.delivery_link ?? '');
        wrapper.dataset.status = JSON.stringify(task.status);
        wrapper.dataset.priority = JSON.stringify(task.priority);
        wrapper.dataset.dueDate = JSON.stringify(task.due_date ?? '');
        wrapper.dataset.reviewDate = JSON.stringify(task.review_date ?? '');
        wrapper.dataset.userId = JSON.stringify(task.user_id ?? null);
        wrapper.dataset.linkedPiece = JSON.stringify(task.linked_piece ?? '');
        wrapper.dataset.rejectionComment = JSON.stringify(task.rejection_comment ?? '');
        openViewModal(wrapper, null);
    }

    function openCategoryModal(category) {
        if (!category) return;
        categoryTitle.textContent = category;
        const list = tasksByCategory[category] || [];
        categoryList.innerHTML = '';
        if (!list.length) {
            categoryList.innerHTML = '<div class="modal-hint" style="text-align:center;">No hay tareas en esta categoría.</div>';
        } else {
            list.forEach(task => {
                const row = document.createElement('div');
                row.className = 'calendario-task';
                row.style.padding = '8px 10px';
                row.innerHTML = '<span class="calendario-task-dot" style="background-color:' + (statusColors[task.status] || '#64748b') + ';"></span>' +
                    '<span class="calendario-task-title">' + escapeHtml(task.title || '(sin título)') + '</span>' +
                    '<span style="margin-left:auto;font-size:11px;color:var(--muted);font-weight:700;white-space:nowrap;">' + (task.due_date || '—') + '</span>';
                row.addEventListener('click', function(e) {
                    openViewModalFromTask(task, e);
                });
                categoryList.appendChild(row);
            });
        }
        categoryOverlay.classList.add('active');
    }

    function closeCategoryModal(event) {
        if (!event || event.target === categoryOverlay) {
            categoryOverlay.classList.remove('active');
        }
    }

    document.querySelectorAll('.filter-chip[data-filter="category"]').forEach(chip => {
        chip.addEventListener('click', function(e) {
            e.stopPropagation();
            openCategoryModal(chip.dataset.value);
        });
    });

    document.querySelectorAll('.filter-chip[data-filter="user"]').forEach(chip => {
        chip.addEventListener('click', () => {
            chip.classList.toggle('active');
        });
    });
</script>
@endsection
