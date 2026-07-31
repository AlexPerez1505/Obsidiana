@extends('layouts.dashboard')

@section('title', 'Nueva tarea · Marketing')
@section('page-title', 'Nueva tarea')
@section('page-sub', 'Crear tarea para el equipo de marketing')

@section('content')
    <style>
        :root {
            --mk-bg: #070c17;
            --mk-surface: #0f1a30;
            --mk-surface-2: #111c36;
            --mk-surface-3: #162444;
            --mk-border: rgba(90, 140, 230, 0.18);
            --mk-text: #e8eef8;
            --mk-muted: #93a4bd;
            --mk-primary: #0a84ff;
            --mk-amber: #f59e0b;
            --mk-emerald: #10b981;
            --mk-rose: #f43f5e;
            --mk-violet: #8b5cf6;
        }

        [data-theme="light"] .task-form {
            --mk-surface: #ffffff;
            --mk-surface-2: #f7f8fa;
            --mk-surface-3: #eef2f7;
            --mk-border: #e2e8f0;
            --mk-text: #1e293b;
            --mk-muted: #64748b;
        }

        .task-form {
            color: var(--mk-text);
            font-size: 14px;
            min-height: 0;
        }

        .form-header {
            margin-bottom: 28px;
        }

        .form-back {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 12px;
            border-radius: 20px;
            background: rgba(10, 132, 255, 0.10);
            color: var(--mk-primary);
            font-size: 12px;
            font-weight: 700;
            text-decoration: none;
            margin-bottom: 14px;
            transition: background 0.15s ease, transform 0.15s ease;
        }

        .form-back:hover {
            background: rgba(10, 132, 255, 0.18);
            transform: translateX(-2px);
        }

        .form-back svg {
            width: 14px;
            height: 14px;
        }

        .form-title {
            font-size: 32px;
            font-weight: 800;
            margin: 0;
            letter-spacing: -0.02em;
            background: linear-gradient(135deg, #fff 0%, var(--mk-primary) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        [data-theme="light"] .form-title {
            background: linear-gradient(135deg, #1e293b 0%, var(--mk-primary) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .form-subtitle {
            color: var(--mk-muted);
            font-size: 15px;
            margin-top: 8px;
            line-height: 1.5;
        }

        .form-card {
            background: linear-gradient(145deg, rgba(15, 26, 48, 0.95), rgba(17, 28, 54, 0.92));
            border: 1px solid var(--mk-border);
            border-radius: 20px;
            padding: 32px;
            width: 100%;
            box-shadow:
                0 12px 40px rgba(0, 0, 0, 0.35),
                inset 0 1px 0 rgba(255, 255, 255, 0.04);
            backdrop-filter: blur(12px);
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
        }

        @media (max-width: 900px) {
            .form-grid { grid-template-columns: 1fr; }
            .form-card { padding: 22px; }
        }

        @media (max-width: 640px) {
            .form-card { padding: 18px; border-radius: 16px; }
            .form-title { font-size: 26px; }
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .form-group.full { grid-column: 1 / -1; }

        .form-label {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
            font-weight: 800;
            color: var(--mk-text);
            letter-spacing: 0.02em;
        }

        .form-label::before {
            content: '';
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: var(--mk-primary);
            box-shadow: 0 0 8px var(--mk-primary);
        }

        .form-input,
        .form-select,
        .form-textarea {
            padding: 14px 16px;
            border-radius: 14px;
            border: 1px solid var(--mk-border);
            background: var(--mk-surface-2);
            color: var(--mk-text);
            font-size: 14px;
            font-weight: 600;
            outline: none;
            width: 100%;
            transition: border-color 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
        }

        .form-input::placeholder,
        .form-textarea::placeholder { color: var(--mk-muted); }

        .form-input:focus,
        .form-select:focus,
        .form-textarea:focus {
            border-color: var(--mk-primary);
            background: var(--mk-surface-3);
            box-shadow: 0 0 0 4px rgba(10, 132, 255, 0.12);
        }

        .form-textarea { min-height: 130px; resize: vertical; line-height: 1.5; }

        .form-select {
            appearance: none;
            padding-right: 42px;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%2393a4bd' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 14px center;
            cursor: pointer;
        }

        select.form-select option {
            background: var(--mk-surface-2);
            color: var(--mk-text);
        }

        .form-hint {
            font-size: 12px;
            color: var(--mk-muted);
            margin-top: 2px;
        }

        .form-error {
            color: var(--mk-rose);
            font-size: 12px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .form-error::before {
            content: '!';
            width: 16px;
            height: 16px;
            border-radius: 50%;
            background: rgba(244, 63, 94, 0.15);
            color: var(--mk-rose);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            font-weight: 800;
        }

        .form-divider {
            grid-column: 1 / -1;
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--mk-border), transparent);
            margin: 8px 0;
        }

        .form-actions {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 14px;
            margin-top: 32px;
            padding-top: 24px;
            border-top: 1px solid var(--mk-border);
        }

        @media (max-width: 640px) {
            .form-actions {
                flex-direction: column;
                align-items: stretch;
            }
        }

        .btn {
            padding: 13px 22px;
            border-radius: 14px;
            font-size: 14px;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
            border: 1px solid var(--mk-border);
            background: var(--mk-surface-2);
            color: var(--mk-text);
            text-align: center;
            transition: background 0.2s ease, transform 0.2s ease, box-shadow 0.2s ease;
        }

        .btn:hover {
            background: var(--mk-surface-3);
            transform: translateY(-2px);
        }

        .btn-primary {
            background: linear-gradient(135deg, #0a84ff, #7c3aed);
            border-color: transparent;
            color: #fff;
            box-shadow: 0 8px 24px rgba(10, 132, 255, 0.35);
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #007aff, #6d28d9);
            box-shadow: 0 12px 32px rgba(10, 132, 255, 0.45);
            transform: translateY(-2px);
        }

        .progress-wrap {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 10px 0;
        }

        .progress-input {
            flex: 1;
            -webkit-appearance: none;
            appearance: none;
            height: 8px;
            border-radius: 999px;
            background: var(--mk-surface-3);
            outline: none;
        }

        .progress-input::-webkit-slider-thumb {
            -webkit-appearance: none;
            appearance: none;
            width: 22px;
            height: 22px;
            border-radius: 50%;
            background: linear-gradient(135deg, #0a84ff, #7c3aed);
            border: 2px solid var(--mk-surface);
            cursor: pointer;
            box-shadow: 0 0 12px rgba(10, 132, 255, 0.55);
            transition: transform 0.15s ease;
        }

        .progress-input::-webkit-slider-thumb:hover {
            transform: scale(1.15);
        }

        .progress-input::-moz-range-thumb {
            width: 22px;
            height: 22px;
            border-radius: 50%;
            background: linear-gradient(135deg, #0a84ff, #7c3aed);
            border: 2px solid var(--mk-surface);
            cursor: pointer;
            box-shadow: 0 0 12px rgba(10, 132, 255, 0.55);
        }

        .progress-value {
            min-width: 52px;
            text-align: center;
            font-weight: 800;
            font-size: 14px;
            padding: 8px 12px;
            border-radius: 12px;
            background: var(--mk-surface-2);
            border: 1px solid var(--mk-border);
            color: var(--mk-primary);
        }
    </style>

    <div class="task-form">
        <div class="form-header">
            <a href="{{ route('marketing.tareas.index') }}" class="form-back">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><polyline points="15 18 9 12 15 6"/></svg>
                Volver a tareas
            </a>
            <h1 class="form-title">Nueva tarea</h1>
            <p class="form-subtitle">Completa la información para asignar una nueva tarea al equipo de marketing.</p>
        </div>

        <form class="form-card" method="POST" action="{{ route('marketing.tareas.store') }}">
            @csrf

            <div class="form-grid">
                <div class="form-group full">
                    <label class="form-label" for="title">Título de la tarea *</label>
                    <input type="text" id="title" name="title" class="form-input" value="{{ old('title') }}" placeholder="Ej. Campaña de vacunación Q3" required>
                    @error('title')
                        <span class="form-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group full">
                    <label class="form-label" for="description">Descripción</label>
                    <textarea id="description" name="description" class="form-textarea" placeholder="Detalles, instrucciones o contexto de la tarea">{{ old('description') }}</textarea>
                    @error('description')
                        <span class="form-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-divider"></div>

                <div class="form-group">
                    <label class="form-label" for="status">Estado *</label>
                    <select id="status" name="status" class="form-select" required>
                        @foreach ($statuses as $value => $label)
                            <option value="{{ $value }}" {{ old('status', 'pendiente') === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('status')
                        <span class="form-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="priority">Prioridad *</label>
                    <select id="priority" name="priority" class="form-select" required>
                        @foreach ($priorities as $value => $label)
                            <option value="{{ $value }}" {{ old('priority', 'media') === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('priority')
                        <span class="form-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="due_date">Fecha límite</label>
                    <input type="date" id="due_date" name="due_date" class="form-input" value="{{ old('due_date') }}">
                    @error('due_date')
                        <span class="form-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="user_id">Responsable *</label>
                    <select id="user_id" name="user_id" class="form-select" required>
                        <option value="" disabled {{ old('user_id') ? '' : 'selected' }}>Selecciona un responsable</option>
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}" {{ old('user_id') == $user->id ? 'selected' : '' }}>
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('user_id')
                        <span class="form-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="progress">Avance (%)</label>
                    <div class="progress-wrap">
                        <input type="range" id="progress" name="progress" class="progress-input" min="0" max="100" value="{{ old('progress', '0') }}" oninput="document.getElementById('progressValue').textContent = this.value + '%'">
                        <span class="progress-value" id="progressValue">{{ old('progress', '0') }}%</span>
                    </div>
                    @error('progress')
                        <span class="form-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="tags">Etiquetas</label>
                    <input type="text" id="tags" name="tags" class="form-input" value="{{ old('tags') }}" placeholder="Flyer, Educación, Carrusel">
                    <span class="form-hint">Separa las etiquetas con comas.</span>
                    @error('tags')
                        <span class="form-error">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            <div class="form-actions">
                <a href="{{ route('marketing.tareas.index') }}" class="btn">Cancelar</a>
                <button type="submit" class="btn btn-primary">Crear tarea</button>
            </div>
        </form>
    </div>
@endsection
