@extends('layouts.dashboard')

@section('title', 'Base de datos · Contenido & Aprobación')
@section('page-title', 'Base de datos · Contenido & Aprobación')
@section('page-sub', 'Todas las piezas del plan de marketing con su estado, responsable, red y checklist de aprobación.')

@section('content')
<style>
    .approval-wrap { display: flex; flex-direction: column; gap: 26px; }
    .approval-header { display: flex; flex-direction: column; gap: 8px; }
    .approval-tag { display: inline-flex; align-items: center; gap: 6px; padding: 5px 12px; border-radius: 999px; background: var(--green-soft); color: var(--green); font-size: 10px; font-weight: 800; letter-spacing: 0.06em; text-transform: uppercase; width: fit-content; }
    .approval-title { margin: 0; font-size: 32px; font-weight: 800; line-height: 1.1; letter-spacing: -0.02em; }
    .approval-sub { margin: 0; color: var(--muted); font-size: 15px; line-height: 1.5; max-width: 760px; }

    .stats-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 12px; }
    .stat-card { background: var(--surface); border: 1px solid var(--border); border-radius: 14px; padding: 16px; display: flex; flex-direction: column; align-items: flex-start; gap: 6px; }
    .stat-number { font-size: 28px; font-weight: 800; line-height: 1; }
    .stat-label { font-size: 11px; font-weight: 700; color: var(--muted); text-transform: uppercase; letter-spacing: 0.04em; }
    .stat-purple { border-left: 3px solid #a855f7; }
    .stat-amber { border-left: 3px solid #f59e0b; }
    .stat-blue { border-left: 3px solid #3b82f6; }
    .stat-red { border-left: 3px solid #ef4444; }
    .stat-green { border-left: 3px solid #22c55e; }
    .stat-emerald { border-left: 3px solid #10b981; }

    .filters-row { display: flex; flex-wrap: wrap; align-items: center; gap: 12px; }
    .filter-input { position: relative; flex: 1; min-width: 220px; }
    .filter-input input { width: 100%; padding: 10px 14px 10px 38px; border-radius: 10px; border: 1px solid var(--border); background: var(--surface); color: var(--text); font-size: 14px; }
    .filter-input svg { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); width: 16px; height: 16px; color: var(--muted); }
    .filter-select { padding: 10px 14px; border-radius: 10px; border: 1px solid var(--border); background: var(--surface); color: var(--text); font-size: 14px; cursor: pointer; }
    .filter-pill { display: inline-flex; align-items: center; gap: 8px; padding: 8px 14px; border-radius: 10px; border: 1px solid var(--border); background: var(--surface); color: var(--text); font-size: 13px; font-weight: 600; cursor: pointer; }
    .filter-pill.active { background: var(--primary); color: #fff; border-color: var(--primary); }
    .filter-icon { width: 16px; height: 16px; }

    .approval-table { background: var(--surface); border: 1px solid var(--border); border-radius: 18px; overflow: hidden; }
    .approval-table table { width: 100%; border-collapse: collapse; font-size: 13px; }
    .approval-table th { text-align: left; padding: 14px 16px; font-size: 10px; font-weight: 800; color: var(--muted); text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 1px solid var(--border); background: var(--surface-2); white-space: nowrap; }
    .approval-table td { padding: 14px 16px; border-bottom: 1px solid var(--border); vertical-align: middle; }
    .approval-table tr:last-child td { border-bottom: none; }
    .approval-table tr:hover td { background: var(--surface-2); }
    .approval-table tbody tr { cursor: pointer; }

    .td-piece { display: flex; flex-direction: column; gap: 3px; }
    .piece-title { font-weight: 700; font-size: 14px; color: var(--text); }
    .piece-sub { font-size: 12px; color: var(--muted); }
    .piece-cat { display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; border-radius: 999px; font-size: 11px; font-weight: 700; background: var(--primary-soft); color: var(--primary); width: fit-content; }
    .piece-cat-dot { width: 6px; height: 6px; border-radius: 50%; background: var(--primary); }
    .status-badge { display: inline-flex; align-items: center; gap: 6px; padding: 5px 10px; border-radius: 999px; font-size: 11px; font-weight: 800; }
    .status-dot { width: 6px; height: 6px; border-radius: 50%; }
    .avatar { width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 11px; font-weight: 800; }
    .platform-tag { display: inline-flex; flex-direction: column; gap: 2px; }
    .platform-item { font-size: 11px; padding: 2px 8px; border-radius: 6px; background: var(--surface-2); border: 1px solid var(--border); color: var(--text); }
    .check-bar { width: 80px; height: 6px; border-radius: 999px; background: var(--border); overflow: hidden; }
    .check-bar-fill { height: 100%; background: #22c55e; border-radius: 999px; }
    .check-text { font-size: 11px; color: var(--muted); font-weight: 700; margin-top: 3px; }
    .review-btn { display: inline-flex; align-items: center; justify-content: center; padding: 8px 14px; border-radius: 8px; border: none; background: var(--primary); color: #fff; font-size: 12px; font-weight: 700; cursor: pointer; }
    .review-btn:hover { background: var(--primary-strong); }
    .review-btn:disabled { background: var(--border); color: var(--muted); cursor: not-allowed; }

    .approval-modal { position: fixed; inset: 0; background: rgba(0,0,0,.55); display: none; align-items: center; justify-content: center; padding: 20px; z-index: 1000; }
    .approval-modal.is-open { display: flex; }
    .approval-modal-card { background: var(--surface); border: 1px solid var(--border); border-radius: 22px; width: 100%; max-width: 1200px; max-height: 90vh; overflow: hidden; display: flex; flex-direction: column; }
    #approvalForm { display: flex; flex-direction: column; flex: 1; min-height: 0; }
    .approval-modal-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; padding: 22px 26px; border-bottom: 1px solid var(--border); }
    .approval-modal-title { margin: 0; font-size: 22px; font-weight: 800; }
    .approval-modal-close { width: 34px; height: 34px; border-radius: 10px; border: 1px solid var(--border); background: var(--surface-2); color: var(--muted); display: flex; align-items: center; justify-content: center; cursor: pointer; }
    .approval-modal-scroll { overflow: auto; padding: 26px; display: flex; flex-direction: column; gap: 24px; flex: 1; min-height: 0; }
    .checklist-title { font-size: 12px; font-weight: 800; color: var(--primary); text-transform: uppercase; letter-spacing: 0.06em; margin: 0 0 14px; }
    .checklist-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; }
    @media (max-width: 640px) { .checklist-grid { grid-template-columns: 1fr; } }
    .checklist-item { display: flex; align-items: center; gap: 10px; padding: 12px 14px; border-radius: 12px; border: 1px solid var(--border); background: var(--surface-2); cursor: pointer; font-size: 13px; font-weight: 600; }
    .checklist-item input { width: 18px; height: 18px; accent-color: var(--primary); cursor: pointer; }
    .checklist-item.locked { cursor: not-allowed; opacity: .75; }
    .checklist-item.locked input { cursor: not-allowed; }
    .checklist-item.checked { background: var(--green-soft); border-color: #22c55e; color: var(--green); }
    .comment-box { display: flex; flex-direction: column; gap: 8px; }
    .comment-box textarea { width: 100%; padding: 12px 14px; border-radius: 12px; border: 1px solid var(--border); background: var(--surface-2); color: var(--text); font-size: 14px; min-height: 90px; resize: vertical; }
    .comment-box label { font-size: 11px; font-weight: 800; color: var(--muted); text-transform: uppercase; }
    .modal-actions { display: flex; gap: 10px; padding: 18px 26px; border-top: 1px solid var(--border); background: var(--surface-2); }
    .modal-actions form { flex: 1; }
    .modal-btn { width: auto; display: inline-flex; align-items: center; justify-content: center; gap: 6px; padding: 8px 12px; border-radius: 10px; border: none; font-size: 12px; font-weight: 700; cursor: pointer; color: #fff; }
    .modal-btn-green { background: #2563eb; }
    .modal-btn-green:hover { background: #1d4ed8; }
    .modal-btn-amber { background: #60a5fa; }
    .modal-btn-amber:hover { background: #3b82f6; }
    .spec-section { display: flex; flex-direction: column; gap: 16px; }
    .spec-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; }
    @media (max-width: 640px) { .spec-grid { grid-template-columns: 1fr; } }
    .spec-split { display: grid; grid-template-columns: minmax(0, 0.9fr) minmax(0, 1.1fr); gap: 24px; align-items: start; }
    @media (max-width: 840px) { .spec-split { grid-template-columns: 1fr; } }
    .spec-image-col { background: var(--surface-2); border: 1px solid var(--border); border-radius: 14px; padding: 20px; min-height: 320px; display: flex; align-items: center; justify-content: center; position: sticky; top: 20px; align-self: start; order: 2; }
    .spec-info-col { order: 1; }
    .spec-image-col img { max-width: 100%; max-height: 72vh; width: auto; height: auto; border-radius: 12px; object-fit: contain; display: block; }
    .spec-image-empty { color: var(--muted); font-size: 14px; font-weight: 600; }
    .spec-info-col .spec-grid { grid-template-columns: 1fr; }
    .spec-field { display: flex; flex-direction: column; gap: 6px; }
    .spec-label { font-size: 11px; font-weight: 800; color: var(--muted); text-transform: uppercase; letter-spacing: 0.06em; }
    .spec-value { padding: 12px 14px; border-radius: 12px; border: 1px solid var(--border); background: var(--surface-2); color: var(--text); font-size: 14px; line-height: 1.5; word-break: break-word; white-space: pre-wrap; }
    .spec-value a { color: var(--primary); text-decoration: none; }
    .spec-value a:hover { text-decoration: underline; }
    .select-all-toggle { display: inline-flex; align-items: center; gap: 8px; padding: 6px 12px; border-radius: 8px; border: 1px solid var(--border); background: var(--surface-2); color: var(--text); font-size: 12px; font-weight: 700; cursor: pointer; user-select: none; }
    .select-all-toggle input { position: absolute; opacity: 0; pointer-events: none; }
    .select-all-check { width: 18px; height: 18px; border-radius: 4px; border: 1px solid var(--border); background: #fff; display: flex; align-items: center; justify-content: center; transition: .15s; }
    .select-all-toggle input:checked + .select-all-check { background: #2563eb; border-color: #2563eb; }
    .select-all-toggle input:checked + .select-all-check::after { content: ''; width: 5px; height: 9px; border: solid #fff; border-width: 0 2px 2px 0; transform: rotate(45deg); margin-top: -2px; }
</style>

<div class="approval-wrap">
    <div class="approval-header">
        <span class="approval-tag">NOTION · SINCRONIZADO</span>
        <h1 class="approval-title">Base de datos · Contenido & Aprobación</h1>
        <p class="approval-sub">Todas las piezas del plan de marketing con su estado, responsable, red y checklist de aprobación. Filtra, busca y abre cada pieza para revisarla.</p>
    </div>

    @php
        $statusLabels = ['pendiente' => 'Pendiente', 'en_proceso' => 'En curso', 'revision' => 'En revisión', 'completada' => 'Aprobado'];
        $statusColors = [
            'pendiente' => ['dot' => '#f59e0b', 'bg' => 'rgba(245,158,11,.14)', 'color' => '#f59e0b'],
            'en_proceso' => ['dot' => '#3b82f6', 'bg' => 'rgba(59,130,246,.14)', 'color' => '#3b82f6'],
            'revision' => ['dot' => '#a855f7', 'bg' => 'rgba(168,85,247,.14)', 'color' => '#a855f7'],
            'completada' => ['dot' => '#22c55e', 'bg' => 'rgba(34,197,94,.14)', 'color' => '#22c55e'],
        ];
        $priorityColors = [
            'baja' => ['bg' => 'rgba(34,197,94,.14)', 'color' => '#22c55e', 'border' => '#22c55e'],
            'media' => ['bg' => 'rgba(245,158,11,.14)', 'color' => '#f59e0b', 'border' => '#f59e0b'],
            'alta' => ['bg' => 'rgba(239,68,68,.14)', 'color' => '#ef4444', 'border' => '#ef4444'],
        ];
        $userColors = ['#3b82f6', '#22c55e', '#a855f7', '#f59e0b', '#0ea5e9', '#ef4444'];
    @endphp

    <div class="stats-row">
        <div class="stat-card stat-amber">
            <span class="stat-number" style="color:#f59e0b;">{{ $stats['por_hacer'] }}</span>
            <span class="stat-label">Pendiente</span>
        </div>
        <div class="stat-card stat-blue">
            <span class="stat-number" style="color:#3b82f6;">{{ $stats['en_curso'] }}</span>
            <span class="stat-label">En curso</span>
        </div>
        <div class="stat-card stat-purple">
            <span class="stat-number" style="color:#a855f7;">{{ $stats['en_revision'] }}</span>
            <span class="stat-label">En revisión</span>
        </div>
        <div class="stat-card stat-red">
            <span class="stat-number" style="color:#ef4444;">{{ $stats['cambios'] }}</span>
            <span class="stat-label">Cambios solicitados</span>
        </div>
        <div class="stat-card stat-green">
            <span class="stat-number" style="color:#22c55e;">{{ $stats['aprobado'] }}</span>
            <span class="stat-label">Aprobado</span>
        </div>
        <div class="stat-card stat-emerald">
            <span class="stat-number" style="color:#10b981;">{{ $stats['hecho'] }}</span>
            <span class="stat-label">Hecho</span>
        </div>
    </div>

    <div class="filters-row">
        <div class="filter-input">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input type="text" id="searchInput" placeholder="Buscar pieza, producto, ID, comentario..." onkeyup="filterTable()">
        </div>
        <select class="filter-select" id="filterCategory" onchange="filterTable()">
            <option value="">Categoría: todas</option>
            @foreach ($tasks->pluck('category')->filter()->unique() as $cat)
                <option value="{{ $cat }}">{{ $cat }}</option>
            @endforeach
        </select>
        <select class="filter-select" id="filterStatus" onchange="filterTable()">
            <option value="">Estado: todos</option>
            @foreach (['pendiente' => 'Pendiente', 'en_proceso' => 'En curso', 'revision' => 'En revisión', 'completada' => 'Aprobado'] as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </select>
        <button class="filter-pill" type="button" onclick="resetFilters()">Restablecer</button>
    </div>

    <div class="approval-table">
        <table>
            <thead>
                <tr>
                    <th>Pieza</th>
                    <th>Categoría</th>
                    <th>Estado</th>
                    <th>Fecha</th>
                    <th>Responsable</th>
                    <th>Red</th>
                    <th>Video</th>
                    <th>Checklist</th>
                    <th></th>
                </tr>
            </thead>
            <tbody id="approvalTableBody">
                @forelse ($tasks as $task)
                    @php
                        $status = $task->status;
                        $badge = $statusColors[$status] ?? ['dot' => 'var(--primary)', 'bg' => 'var(--primary-soft)', 'color' => 'var(--primary)'];
                        $initial = $task->user ? mb_substr($task->user->name, 0, 1) : '?';
                        $uColor = $userColors[($task->user->id ?? 0) % count($userColors)];
                        $fecha = $task->due_date ? $task->due_date->format('d/m/Y') : '—';
                        $platforms = $task->platform ?? [];
                        $progress = $task->progress ?? 0;
                        $progressCount = round($progress / 10);
                        $taskJson = json_encode([
                            'id' => $task->id,
                            'title' => $task->title,
                            'category' => $task->category,
                            'status' => $task->status,
                            'due_date' => $fecha,
                            'user' => $task->user?->name,
                            'reviewer' => $task->reviewer?->name,
                            'platform' => $task->platform,
                            'has_video' => $task->has_video,
                            'delivery_link' => $task->delivery_link,
                            'project_image' => $task->project_image ? asset('storage/' . $task->project_image) : null,
                            'task_description' => $task->task_description,
                            'description' => $task->description,
                            'rejection_comment' => $task->rejection_comment,
                            'approval_checklist' => $task->approval_checklist ?? [],
                            'linked_piece' => $task->linked_piece,
                        ], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
                    @endphp
                    <tr data-title="{{ strtolower($task->title) }}" data-category="{{ strtolower($task->category ?? '') }}" data-status="{{ $status }}" data-json="{{ $taskJson }}" onclick="openApprovalModalFromElement(this)">
                        <td>
                            <div class="td-piece">
                                <span class="piece-title">{{ $task->title }}</span>
                                @if ($task->linked_piece)
                                    <span class="piece-sub">{{ $task->linked_piece }}</span>
                                @endif
                            </div>
                        </td>
                        <td>
                            @if ($task->category)
                                <span class="piece-cat">
                                    <span class="piece-cat-dot"></span>
                                    {{ $task->category }}
                                </span>
                            @else
                                —
                            @endif
                        </td>
                        <td>
                            <span class="status-badge" style="background:{{ $badge['bg'] }}; color:{{ $badge['color'] }};">
                                <span class="status-dot" style="background:{{ $badge['dot'] }}"></span>
                                {{ $statusLabels[$status] ?? 'Tarea' }}
                            </span>
                        </td>
                        <td>{{ $fecha }}</td>
                        <td>
                            <div style="display:flex;align-items:center;gap:8px;">
                                <span class="avatar" style="background:{{ $uColor }};">{{ $initial }}</span>
                                <span style="font-weight:600;">{{ $task->user?->name ?? '—' }}</span>
                            </div>
                        </td>
                        <td>
                            <div class="platform-tag">
                                @forelse ($platforms as $p)
                                    <span class="platform-item">{{ $p }}</span>
                                @empty
                                    <span style="color:var(--muted);">—</span>
                                @endforelse
                            </div>
                        </td>
                        <td>{{ $task->has_video ? 'Sí' : '—' }}</td>
                        <td>
                            <div style="display:flex;flex-direction:column;gap:2px;">
                                <div class="check-bar"><div class="check-bar-fill" style="width:{{ $progress }}%;"></div></div>
                                <span class="check-text">{{ $progressCount }}/10</span>
                            </div>
                        </td>
                        <td>
                            <button type="button" class="review-btn" data-json="{{ $taskJson }}" onclick="event.stopPropagation(); openApprovalModalFromElement(this)" {{ $status === 'revision' ? '' : 'disabled' }}>
                                Revisar
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" style="text-align:center;color:var(--muted);padding:40px;">No hay piezas registradas.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="approval-modal" id="approvalModal" onclick="if(event.target === this) closeApprovalModal()">
    <div class="approval-modal-card">
        <div class="approval-modal-head">
            <h2 class="approval-modal-title" id="modalTitle">Revisar pieza</h2>
            <button type="button" class="approval-modal-close" onclick="closeApprovalModal()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="18" height="18"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <form id="approvalForm" method="POST">
            @csrf
            @method('PUT')
            <div class="approval-modal-scroll">
                <div class="spec-section">
                    <h3 class="checklist-title">Especificaciones de la pieza</h3>
                    <div class="spec-split">
                        <div class="spec-image-col">
                            <div id="specImageWrap" style="display:none;">
                                <img id="specImage" src="" alt="Imagen del proyecto">
                            </div>
                            <div id="specImageEmpty" class="spec-image-empty" style="display:block;">—</div>
                        </div>
                        <div class="spec-info-col">
                            <div class="spec-grid">
                                <div class="spec-field"><span class="spec-label">Categoría</span><div class="spec-value" id="specCategory">—</div></div>
                                <div class="spec-field"><span class="spec-label">Copy / Texto del post</span><div class="spec-value" id="specCopy">—</div></div>
                                <div class="spec-field"><span class="spec-label">Comentarios del revisor</span><div class="spec-value" id="specComments">—</div></div>
                            </div>

                            <div style="margin-top:24px;">
                                <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:14px;">
                                    <h3 class="checklist-title" style="margin:0;">Checklist de aprobación ( <span id="checkCount">0</span> /10)</h3>
                                    <label class="select-all-toggle">
                                        <input type="checkbox" id="selectAllCheck" onchange="toggleSelectAll()">
                                        <span class="select-all-check"></span>
                                        <span class="select-all-label">Seleccionar todos</span>
                                    </label>
                                </div>
                                <div style="display:flex;flex-direction:column;gap:4px;margin-bottom:16px;">
                                    <div class="check-bar" style="width:100%;"><div class="check-bar-fill" id="modalCheckBar" style="width:0%;"></div></div>
                                    <span class="check-text"><span id="checkPercent">0</span>% de aprobación</span>
                                </div>
                                <div class="checklist-grid" id="checklistGrid">
                                    @foreach ([
                                        'Nombre/modelo',
                                        'Specs verificados',
                                        'Marca/logo',
                                        'Precio/política',
                                        'Ortografía',
                                        'Datos de contacto',
                                        'Sin claims indebidos',
                                        'Formato de red',
                                        'Imagen nítida',
                                        'Leyenda salud'
                                    ] as $i => $item)
                                        <label class="checklist-item">
                                            <input type="checkbox" name="approval_checklist[]" value="{{ $i }}" onchange="updateCheckCount()">
                                            <span>{{ $item }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>

                            <div class="comment-box" style="margin-top:24px;">
                                <label for="rejectionComment">Comentario de solicitud de cambios</label>
                                <textarea id="rejectionComment" name="rejection_comment" placeholder="Especifica qué le falta o qué debe corregirse..."></textarea>
                            </div>
                        </div>
                    </div>
                </div>



            </div>
            <div class="modal-actions">
                <button type="button" class="modal-btn modal-btn-green" id="btnAprobar" onclick="submitAprobar()">Aprobar</button>
                <button type="button" class="modal-btn modal-btn-amber" id="btnDevolver" onclick="submitDevolver()">Solicitar cambios</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openApprovalModalFromElement(el) {
        openApprovalModal(JSON.parse(el.dataset.json));
    }

    function openApprovalModal(data) {
        const base = '{{ url('/marketing/tareas') }}';
        document.getElementById('btnAprobar').setAttribute('formaction', `${base}/${data.id}/aprobar`);
        document.getElementById('btnDevolver').dataset.formaction = `${base}/${data.id}/devolver`;
        document.getElementById('approvalForm').action = `${base}/${data.id}/aprobar`;
        document.getElementById('modalTitle').textContent = data.title || 'Revisar pieza';

        document.getElementById('specCategory').textContent = data.category || '—';
        document.getElementById('specCopy').textContent = data.description || '—';

        const specImage = document.getElementById('specImage');
        const specImageWrap = document.getElementById('specImageWrap');
        const specImageEmpty = document.getElementById('specImageEmpty');
        if (data.project_image) {
            specImage.src = data.project_image;
            specImageWrap.style.display = 'block';
            specImageEmpty.style.display = 'none';
        } else {
            specImage.src = '';
            specImageWrap.style.display = 'none';
            specImageEmpty.style.display = 'block';
        }
        document.getElementById('specComments').textContent = data.rejection_comment || '—';



        const isRevision = data.status === 'revision';
        const isCompleted = data.status === 'completada';
        const lockedArray = Array.isArray(data.approval_checklist) ? data.approval_checklist : [];
        document.querySelectorAll('.checklist-item input').forEach((cb, index) => {
            const isLocked = lockedArray.includes(index);
            cb.checked = isCompleted || isLocked;
            cb.disabled = isCompleted || !isRevision || isLocked;
            cb.closest('.checklist-item').classList.toggle('locked', isCompleted || !isRevision || isLocked);
        });
        document.querySelectorAll('.checklist-item').forEach(l => l.classList.remove('checked'));
        document.getElementById('selectAllCheck').checked = false;
        document.getElementById('rejectionComment').value = '';

        document.querySelector('#approvalForm .comment-box').style.display = isRevision ? 'flex' : 'none';
        document.querySelector('#approvalForm .modal-actions').style.display = isRevision ? 'flex' : 'none';

        updateCheckCount();

        document.getElementById('approvalModal').classList.add('is-open');
    }

    function closeApprovalModal() {
        document.getElementById('approvalModal').classList.remove('is-open');
    }

    function updateCheckCount() {
        const count = document.querySelectorAll('.checklist-item input:checked').length;
        const percent = count * 10;
        document.getElementById('checkCount').textContent = count;
        document.getElementById('modalCheckBar').style.width = percent + '%';
        document.getElementById('checkPercent').textContent = percent;
        document.querySelectorAll('.checklist-grid .checklist-item').forEach(item => {
            item.classList.toggle('checked', item.querySelector('input').checked);
        });

        const enabled = document.querySelectorAll('.checklist-grid .checklist-item input:not([disabled])');
        const master = document.getElementById('selectAllCheck');
        if (master && enabled.length) {
            master.checked = enabled.length === document.querySelectorAll('.checklist-grid .checklist-item input:not([disabled]):checked').length;
        }
    }

    function toggleSelectAll() {
        const master = document.getElementById('selectAllCheck');
        const checked = master.checked;
        document.querySelectorAll('.checklist-grid .checklist-item input:not([disabled])').forEach(cb => {
            cb.checked = checked;
        });
        updateCheckCount();
    }

    function submitAprobar() {
        const total = document.querySelectorAll('.checklist-item input').length;
        const checked = document.querySelectorAll('.checklist-item input:checked').length;
        if (checked < total) {
            alert('Debes marcar todos los aspectos del checklist antes de aprobar.');
            return;
        }
        document.getElementById('approvalForm').submit();
    }

    function submitDevolver() {
        const comment = document.getElementById('rejectionComment').value.trim();
        if (!comment) {
            alert('Escribe un comentario explicando qué cambios se solicitan.');
            return;
        }
        const form = document.getElementById('approvalForm');
        form.action = document.getElementById('btnDevolver').dataset.formaction;
        form.submit();
    }

    function filterTable() {
        const q = document.getElementById('searchInput').value.toLowerCase();
        const cat = document.getElementById('filterCategory').value.toLowerCase();
        const status = document.getElementById('filterStatus').value;

        document.querySelectorAll('#approvalTableBody tr').forEach(row => {
            const title = row.dataset.title;
            const rowCat = row.dataset.category;
            const rowStatus = row.dataset.status;
            const show = (!q || title.includes(q)) &&
                         (!cat || rowCat === cat) &&
                         (!status || rowStatus === status);
            row.style.display = show ? '' : 'none';
        });
    }

    function resetFilters() {
        document.getElementById('searchInput').value = '';
        document.getElementById('filterCategory').value = '';
        document.getElementById('filterStatus').value = '';
        filterTable();
    }

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeApprovalModal();
    });
</script>
@endsection
