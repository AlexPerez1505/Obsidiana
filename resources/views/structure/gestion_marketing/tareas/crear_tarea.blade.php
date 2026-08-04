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
    .task-field label {
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
</style>

<div class="task-create-wrap">
    <form method="POST" action="{{ route('marketing.tareas.store') }}" class="task-create-card" id="taskForm">
        @csrf
        <input type="hidden" name="progress" value="0">
        <input type="hidden" name="tags" value="">

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
                <label for="title">Título</label>
                <input type="text" id="title" name="title" placeholder="¿Qué hay que hacer?" required value="{{ old('title') }}">
            </div>

            <div class="task-field">
                <label for="description">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                    Copy
                </label>
                <textarea id="description" name="description" placeholder="Escribe aquí el copy / texto de la publicación...">{{ old('description') }}</textarea>
            </div>

            <div class="task-field">
                <label for="delivery_link">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
                    Trabajo entregado (Canva / Drive / YouTube / imagen o video)
                </label>
                <input type="url" id="delivery_link" name="delivery_link" placeholder="Pega el link de Canva, Drive o YouTube del trabajo (que esté como: cualquiera con el enlace)" value="{{ old('delivery_link') }}">
            </div>

            <div class="task-field">
                <label>Vista previa de la entrega</label>
                <div class="task-preview">
                    Aún no hay entrega. Pega el enlace arriba y aquí lo verá todo el equipo.
                </div>
            </div>

            <div class="task-row">
                <div class="task-field">
                    <label for="review_date">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        Fecha de revisión
                    </label>
                    <input type="date" id="review_date" name="review_date" value="{{ old('review_date') }}">
                </div>

                <div class="task-field">
                    <label for="due_date">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.5 13.5 13 16l4-4"/><path d="M18 8a3 3 0 1 0-6 0 3 3 0 0 0 6 0"/><path d="M17.92 16.62c.57-.7.96-1.55 1.08-2.52l.28-2.15a2.67 2.67 0 0 0-2.64-3.04h-1.28a2.67 2.67 0 0 0-2.64 3.04l.28 2.15c.12.97.51 1.82 1.08 2.52"/><path d="M11 20a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2h-2z"/></svg>
                        Fecha de publicación
                        <span class="rev-badge">rev -3d</span>
                    </label>
                    <input type="date" id="due_date" name="due_date" value="{{ old('due_date') }}">
                </div>
            </div>

            <div class="task-row">
                <div class="task-field">
                    <label for="user_id">Responsable</label>
                    <select id="user_id" name="user_id" required>
                        <option value="">—</option>
                        @foreach ($users as $u)
                            <option value="{{ $u->id }}" {{ old('user_id') == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="task-field">
                    <label for="status">Estado</label>
                    <select id="status" name="status" required>
                        @foreach ($statuses as $value => $label)
                            <option value="{{ $value }}" {{ old('status', 'pendiente') == $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="task-row">
                <div class="task-field">
                    <label for="priority">Prioridad</label>
                    <select id="priority" name="priority" required>
                        @foreach ($priorities as $value => $label)
                            <option value="{{ $value }}" {{ old('priority', 'media') == $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="task-field">
                    <label for="linked_piece">Pieza vinculada</label>
                    <select id="linked_piece" name="linked_piece">
                        <option value="">— ninguna —</option>
                        <option value="nueva" {{ old('linked_piece') == 'nueva' ? 'selected' : '' }}>Nueva pieza</option>
                    </select>
                </div>
            </div>

            <div class="task-hint">
                <strong>Nota:</strong> La fecha de revisión se pone automáticamente 3 días antes de la publicación. Ambas fechas aparecen también en el calendario.
            </div>

            <div class="task-review-status">
                Revisión: <span>Pendiente</span>
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
    (function() {
        var dueInput = document.getElementById('due_date');
        var reviewInput = document.getElementById('review_date');

        function offsetDate(dateStr, days) {
            if (!dateStr) return '';
            var d = new Date(dateStr);
            if (isNaN(d.getTime())) return '';
            d.setDate(d.getDate() - days);
            return d.toISOString().split('T')[0];
        }

        dueInput.addEventListener('change', function() {
            if (!reviewInput.value) {
                reviewInput.value = offsetDate(dueInput.value, 3);
            }
        });
    })();
</script>
@endsection
