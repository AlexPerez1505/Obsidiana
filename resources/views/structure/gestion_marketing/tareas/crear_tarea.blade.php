@extends('layouts.dashboard')

@section('title', 'Crear tarea')
@section('page-title', 'Nueva tarea')

@section('content')
<style>
    .task-create-wrap {
        max-width: 720px;
        margin: 0 auto;
    }
    .task-create-card {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: 22px;
        overflow: hidden;
    }
    .task-create-head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 16px;
        padding: 22px 26px;
        border-bottom: 1px solid var(--border);
    }
    .task-create-eyebrow {
        font-size: 11px;
        font-weight: 800;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        color: var(--primary);
    }
    .task-create-title {
        margin: 4px 0 0;
        font-size: 24px;
        font-weight: 800;
        letter-spacing: -0.02em;
    }
    .task-create-close {
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
        transition: background .15s, color .15s;
    }
    .task-create-close:hover { background: var(--surface); color: var(--text); }

    .task-create-body {
        padding: 26px;
        display: flex;
        flex-direction: column;
        gap: 22px;
    }
    .task-field { display: flex; flex-direction: column; gap: 7px; }
    .task-field label:not(.platform-pill):not(.video-toggle),
    .task-field .field-label {
        font-size: 11px;
        font-weight: 800;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        color: var(--muted);
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .task-field label svg { width: 14px; height: 14px; color: var(--primary); }
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

    .kanban-delivery-preview { min-height:80px; border:1px dashed var(--border); border-radius:10px; padding:14px; color:var(--muted); font-size:13px; display:flex; align-items:center; justify-content:center; text-align:center; }
    .kanban-delivery-preview:has(iframe), .kanban-delivery-preview:has(.lp-card), .kanban-delivery-preview:has(.lp-img-full) { padding:0; border-style:solid; display:block; text-align:left; }
    .lp-card { display:flex; border-radius:10px; overflow:hidden; background:var(--surface); text-decoration:none; color:inherit; }
    .lp-card:hover { box-shadow:0 4px 16px rgba(0,0,0,.12); }
    .lp-card-img { width:120px; min-height:100px; max-height:160px; object-fit:cover; flex-shrink:0; background:var(--surface-2); }
    .lp-card-body { padding:12px 14px; display:flex; flex-direction:column; gap:5px; min-width:0; flex:1; }
    .lp-card-title { font-size:13px; font-weight:700; color:var(--text); line-height:1.35; overflow:hidden; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; }
    .lp-card-desc { font-size:12px; color:var(--muted); line-height:1.45; overflow:hidden; display:-webkit-box; -webkit-line-clamp:3; -webkit-box-orient:vertical; margin:0; }
    .lp-card-host { font-size:11px; color:var(--muted); text-transform:uppercase; letter-spacing:0.04em; font-weight:700; margin-top:auto; display:inline-flex; align-items:center; gap:5px; }
    .lp-embed { width:100%; aspect-ratio:16/9; border:0; border-radius:10px; display:block; background:#000; }
    .lp-embed.canva { aspect-ratio:auto; min-height:280px; }
    .lp-img-full { width:100%; max-height:360px; object-fit:contain; border-radius:10px; display:block; background:var(--surface-2); }
    .lp-loading { display:inline-flex; align-items:center; gap:8px; color:var(--muted); font-size:13px; }
    .lp-loading::before { content:''; width:14px; height:14px; border:2px solid var(--border); border-top-color:var(--primary); border-radius:50%; animation:lp-spin .7s linear infinite; }
    @keyframes lp-spin { to { transform:rotate(360deg); } }
    .lp-error { color:var(--danger, #c0392b); font-size:13px; }

    .task-row {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 18px;
    }
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

    .task-hint {
        font-size: 12px;
        color: var(--muted);
        line-height: 1.5;
        padding: 14px 16px;
        border: 1px dashed var(--border);
        border-radius: 12px;
        background: var(--surface-2);
    }
    .task-hint strong { color: var(--text); font-weight: 700; }

    .task-review-status {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 16px;
        border: 1px dashed var(--border);
        border-radius: 12px;
        background: var(--surface-2);
        font-size: 14px;
        color: var(--text);
        font-weight: 700;
    }
    .task-review-status span { color: var(--muted); font-weight: 600; }

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
        background: var(--primary);
        color: #fff;
        font-size: 15px;
        font-weight: 700;
        cursor: pointer;
        transition: background .15s;
    }
    .task-save:hover { background: var(--primary-strong); }
    .task-save svg { width: 18px; height: 18px; }

    .rev-badge {
        margin-left: auto;
        font-size: 9px;
        font-weight: 800;
        letter-spacing: 0.04em;
        padding: 3px 8px;
        border-radius: 6px;
        background: var(--primary-soft);
        color: var(--primary);
        text-transform: uppercase;
    }

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

<div class="task-create-wrap">
    <form method="POST" action="{{ route('marketing.tareas.store') }}" class="task-create-card" id="taskForm">
        @csrf
        <input type="hidden" name="progress" value="0">

        <div class="task-create-head">
            <div>
                <div class="task-create-eyebrow">Nueva tarea</div>
                <h1 class="task-create-title">Crear tarea</h1>
            </div>
            <a href="{{ route('marketing.tareas.index') }}" class="task-create-close" title="Cerrar">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="18" height="18"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </a>
        </div>

        <div class="task-create-body">
            <div class="task-field">
                <label for="title">Pieza / Título</label>
                <input type="text" id="title" name="title" placeholder="Título de la pieza..." required value="{{ old('title') }}">
            </div>

            <div class="task-field">
                <label for="category">Categoría</label>
                <select id="category" name="category">
                    <option value="">—</option>
                    @php
                        $categories = [
                            'antes_despues' => 'Antes / Después',
                            'carruseles' => 'Carruseles',
                            'congreso' => 'Congreso',
                            'cumpleanos' => 'Cumpleaños',
                            'dias_conmemorativos' => 'Días conmemorativos',
                            'educacion' => 'Educación',
                            'equipos' => 'Equipos',
                            'promociones' => 'Promociones',
                        ];
                    @endphp
                    @foreach ($categories as $value => $label)
                        <option value="{{ $label }}" {{ old('category') == $label ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="task-row">
                <div class="task-field">
                    <label for="due_date">Fecha</label>
                    <input type="date" id="due_date" name="due_date" value="{{ old('due_date') }}">
                </div>

                <div class="task-field">
                    <label for="user_id">Responsable</label>
                    <select id="user_id" name="user_id" required>
                        <option value="">—</option>
                        @foreach ($users as $u)
                            <option value="{{ $u->id }}" {{ old('user_id') == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="task-field">
                <label for="priority">Prioridad</label>
                <select id="priority" name="priority" required>
                    @foreach ($priorities as $value => $label)
                        <option value="{{ $value }}" {{ old('priority', 'media') == $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="task-row">
                <div class="task-field">
                    <label for="reviewer_id">Revisor</label>
                    <select id="reviewer_id" name="reviewer_id">
                        <option value="">—</option>
                        @foreach ($users as $u)
                            <option value="{{ $u->id }}" {{ old('reviewer_id', auth()->id()) == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="task-field">
                    <label for="linked_piece">Producto / Equipo</label>
                    <input type="text" id="linked_piece" name="linked_piece" placeholder="Ej. Endoscopia" value="{{ old('linked_piece') }}">
                </div>
            </div>

            <div class="task-row">
                <div class="task-field">
                    <span class="field-label">Plataforma destino</span>
                    <div class="platform-choices">
                        @php
                            $platforms = [
                                'Facebook' => 'Facebook',
                                'Instagram' => 'Instagram',
                                'LinkedIn' => 'LinkedIn',
                                'TikTok' => 'TikTok',
                                'Todas' => 'Todas',
                                'WhatsApp' => 'WhatsApp',
                                'YouTube' => 'YouTube',
                            ];
                            $oldPlatforms = old('platform', []);
                        @endphp
                        @foreach ($platforms as $value => $label)
                            <label class="platform-pill">
                                <input type="checkbox" name="platform[]" value="{{ $value }}" {{ in_array($value, (array) $oldPlatforms) ? 'checked' : '' }}>
                                <span>{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="task-field">
                    <span class="field-label">Video</span>
                    <label class="video-toggle" for="has_video">
                        <input type="checkbox" id="has_video" name="has_video" value="1" {{ old('has_video') ? 'checked' : '' }}>
                        <span class="video-knob"></span>
                        <span class="video-text">¿Incluye video?</span>
                    </label>
                </div>
            </div>

            <div class="task-field">
                <label for="delivery_link">Enlace (Canva / Drive)</label>
                <input type="url" id="delivery_link" name="delivery_link" placeholder="Pega el link de Canva o Drive del flyer" value="{{ old('delivery_link') }}">
            </div>

            <div class="task-field">
                <span class="field-label">Vista previa del enlace</span>
                <div class="kanban-delivery-preview" id="crearDeliveryPreview">Pega el enlace arriba para ver la vista previa.</div>
            </div>

            <div class="task-field">
                <label for="task_description">Descripción</label>
                <textarea id="task_description" name="task_description" placeholder="Descripción de la pieza / instrucciones...">{{ old('task_description') }}</textarea>
            </div>

            <div class="task-field">
                <label for="description">Copy / Texto del post</label>
                <textarea id="description" name="description" placeholder="Texto de la publicación...">{{ old('description') }}</textarea>
            </div>

        </div>

        <div class="task-footer">
            <button type="submit" class="task-save">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="18" height="18"><polyline points="20 6 9 17 4 12"/></svg>
                Guardar
            </button>
        </div>
    </form>
</div>

<script>
(function () {
    var input = document.getElementById('delivery_link');
    var preview = document.getElementById('crearDeliveryPreview');
    if (!input || !preview) return;

    var csrfToken = document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').content : '';
    var previewUrl = '{{ route("marketing.tareas.preview_link") }}';
    var cache = {};
    var abortCtrl = null;

    function escapeHtml(s) {
        var d = document.createElement('div');
        d.textContent = s == null ? '' : String(s);
        return d.innerHTML;
    }
    function hostFromUrl(url) {
        try { return new URL(url).hostname.replace(/^www\./, ''); }
        catch (e) { return url; }
    }
    function renderPreview(data) {
        if (!data || !data.type) {
            preview.textContent = 'Pega el enlace arriba para ver la vista previa.';
            return;
        }
        var url = data.url || '';
        switch (data.type) {
            case 'youtube':
            case 'vimeo':
                preview.innerHTML = '<iframe class="lp-embed" src="' + escapeHtml(data.embed_url) + '" allow="autoplay; fullscreen; encrypted-media" allowfullscreen loading="lazy"></iframe>';
                break;
            case 'canva':
                if (data.embed_html) {
                    preview.innerHTML = data.embed_html;
                    var iframe = preview.querySelector('iframe');
                    if (iframe) { iframe.className = 'lp-embed canva'; iframe.loading = 'lazy'; }
                } else {
                    preview.innerHTML = '<iframe class="lp-embed canva" src="' + escapeHtml(data.embed_url) + '" allow="fullscreen" allowfullscreen loading="lazy"></iframe>';
                }
                break;
            case 'drive':
                preview.innerHTML = '<iframe class="lp-embed" src="' + escapeHtml(data.embed_url) + '" allow="autoplay" allowfullscreen loading="lazy"></iframe>';
                break;
            case 'image':
                preview.innerHTML = '<img class="lp-img-full" src="' + escapeHtml(data.image || url) + '" alt="Vista previa" loading="lazy">';
                break;
            case 'og':
            case 'link':
                if (data.image) {
                    preview.innerHTML = '<a class="lp-card" href="' + escapeHtml(url) + '" target="_blank" rel="noopener">' +
                        '<img class="lp-card-img" src="' + escapeHtml(data.image) + '" alt="" loading="lazy" onerror="this.style.display=\'none\'">' +
                        '<div class="lp-card-body">' +
                        '<span class="lp-card-title">' + escapeHtml(data.title || url) + '</span>' +
                        (data.description ? '<p class="lp-card-desc">' + escapeHtml(data.description) + '</p>' : '') +
                        '<span class="lp-card-host">' + escapeHtml(hostFromUrl(url)) + '</span>' +
                        '</div></a>';
                } else {
                    preview.innerHTML = '<a class="lp-card" href="' + escapeHtml(url) + '" target="_blank" rel="noopener" style="padding:14px;">' +
                        '<div class="lp-card-body">' +
                        '<span class="lp-card-title">' + escapeHtml(data.title || url) + '</span>' +
                        '<span class="lp-card-host">' + escapeHtml(hostFromUrl(url)) + '</span>' +
                        '</div></a>';
                }
                break;
            default:
                preview.textContent = 'Pega el enlace arriba para ver la vista previa.';
        }
    }

    async function loadPreview(url) {
        if (!url) {
            preview.textContent = 'Pega el enlace arriba para ver la vista previa.';
            return;
        }
        if (cache[url]) { renderPreview(cache[url]); return; }
        if (abortCtrl) { try { abortCtrl.abort(); } catch (e) {} }
        abortCtrl = (window.AbortController) ? new AbortController() : null;
        preview.innerHTML = '<span class="lp-loading">Cargando vista previa…</span>';
        try {
            var resp = await fetch(previewUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ url: url }),
                signal: abortCtrl ? abortCtrl.signal : undefined,
            });
            var data = await resp.json();
            cache[url] = data;
            renderPreview(data);
        } catch (e) {
            if (e.name === 'AbortError') return;
            preview.innerHTML = '<span class="lp-error">No se pudo cargar. <a href="' + escapeHtml(url) + '" target="_blank" rel="noopener" style="color:inherit;text-decoration:underline;">Abrir enlace</a></span>';
        }
    }

    var debounce = null;
    input.addEventListener('input', function () {
        clearTimeout(debounce);
        debounce = setTimeout(function () { loadPreview(input.value.trim()); }, 400);
    });

    if (input.value.trim()) { loadPreview(input.value.trim()); }
})();
</script>
@endsection
