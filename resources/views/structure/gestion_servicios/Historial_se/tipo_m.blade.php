@extends('structure.gestion_servicios.layout')

@section('title', 'Tipo de servicio')

@section('service_content')
    <style>
        .type-page {
            max-width: 720px;
            margin: 0 auto;
        }
        .type-page-header {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 26px;
        }
        .type-page-icon {
            width: 54px;
            height: 54px;
            border-radius: 14px;
            background: rgba(0, 122, 255, 0.12);
            color: #007AFF;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .type-page-icon svg { width: 26px; height: 26px; }
        .type-page-header h2 {
            margin: 0;
            font-size: 24px;
            color: #fff;
        }
        :root[data-theme="light"] .type-page-header h2 { color: var(--text); }
        .type-page-header p {
            margin: 4px 0 0;
            color: rgba(255,255,255,0.55);
            font-size: 14px;
        }
        :root[data-theme="light"] .type-page-header p { color: var(--muted); }

        .type-option {
            position: relative;
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 20px;
            border: 1px solid rgba(255,255,255,0.12);
            border-radius: 16px;
            background: rgba(8,18,40,0.55);
            cursor: pointer;
            margin-bottom: 16px;
            transition: all .18s ease;
        }
        :root[data-theme="light"] .type-option { background: rgba(15,23,42,0.04); border-color: rgba(15,23,42,0.14); }
        .type-option:hover { border-color: rgba(0,122,255,0.45); }
        .type-option.selected {
            border-color: #007AFF;
            background: rgba(0,122,255,0.12);
            box-shadow: 0 0 24px rgba(0,122,255,0.25), inset 0 1px 0 rgba(255,255,255,0.04);
        }
        :root[data-theme="light"] .type-option.selected { background: rgba(0,122,255,0.08); box-shadow: 0 0 16px rgba(0,122,255,0.12); }

        .type-option.selected--externo {
            border-color: #f59e0b;
            background: rgba(245,158,11,0.12);
            box-shadow: 0 0 24px rgba(245,158,11,0.25), inset 0 1px 0 rgba(255,255,255,0.04);
        }
        :root[data-theme="light"] .type-option.selected--externo { background: rgba(245,158,11,0.08); box-shadow: 0 0 16px rgba(245,158,11,0.12); }

        .type-check {
            width: 26px;
            height: 26px;
            border-radius: 7px;
            border: 2px solid rgba(255,255,255,0.25);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            color: transparent;
            transition: all .18s ease;
        }
        :root[data-theme="light"] .type-check { border-color: rgba(15,23,42,0.25); }
        .type-option.selected .type-check,
        .type-option.selected--externo .type-check {
            background: #007AFF;
            border-color: #007AFF;
            color: #fff;
        }
        .type-option.selected--externo .type-check { background: #f59e0b; border-color: #f59e0b; }
        .type-check svg { width: 14px; height: 14px; }

        .type-option-body h3 {
            margin: 0 0 4px;
            font-size: 16px;
            color: #fff;
        }
        :root[data-theme="light"] .type-option-body h3 { color: var(--text); }
        .type-option-body p {
            margin: 0;
            font-size: 13px;
            color: rgba(255,255,255,0.55);
        }
        :root[data-theme="light"] .type-option-body p { color: var(--muted); }

        .type-option.selected .type-option-body h3 { color: #007AFF; }
        .type-option.selected--externo .type-option-body h3 { color: #f59e0b; }

        .type-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
            margin-top: 28px;
        }
        .type-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 14px 20px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
            transition: all .16s ease;
        }
        .type-btn:hover { transform: translateY(-1px); }
        .type-btn--cancel {
            border: 1px solid rgba(255,255,255,0.12);
            background: transparent;
            color: #007AFF;
        }
        :root[data-theme="light"] .type-btn--cancel { border-color: rgba(15,23,42,0.14); color: #007AFF; }
        .type-btn--cancel:hover { border-color: #007AFF; background: rgba(0,122,255,0.08); }
        .type-btn--continue {
            border: none;
            background: #007AFF;
            color: #fff;
            box-shadow: 0 0 16px rgba(0,122,255,0.35);
        }
        .type-btn--continue:hover { background: #005FCC; box-shadow: 0 0 22px rgba(0,122,255,0.5); }
        .type-btn--continue:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        .type-btn svg { width: 16px; height: 16px; }
    </style>

    <div class="type-page">
        <div class="type-page-header">
            <div class="type-page-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
            </div>
            <div>
                <h2>Tipo de servicio</h2>
                <p>Selecciona el tipo de mantenimiento a registrar</p>
            </div>
        </div>

        <div class="type-option selected--externo" data-value="externo" onclick="selectType('externo')">
            <div class="type-check">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
            </div>
            <div class="type-option-body">
                <h3>Mantenimiento externo</h3>
                <p>El equipo se atiende fuera de las instalaciones del cliente.</p>
            </div>
        </div>

        <div class="type-option" data-value="interno" onclick="selectType('interno')">
            <div class="type-check">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
            </div>
            <div class="type-option-body">
                <h3>Mantenimiento interno</h3>
                <p>El tecnico asiste en las instalaciones del cliente.</p>
            </div>
        </div>

        <div class="type-actions">
            <a href="{{ route('gestion.servicios.historial') }}" class="type-btn type-btn--cancel">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                Cancelar
            </a>
            <button type="button" class="type-btn type-btn--continue" id="continueBtn" onclick="goToForm()">
                Continuar
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
            </button>
        </div>
    </div>

    <script>
        let selectedType = 'externo';

        function selectType(type) {
            selectedType = type;
            document.querySelectorAll('.type-option').forEach(option => {
                option.classList.remove('selected', 'selected--externo');
            });
            const active = document.querySelector('.type-option[data-value="' + type + '"]');
            if (type === 'externo') {
                active.classList.add('selected--externo');
            } else {
                active.classList.add('selected');
            }
        }

        function goToForm() {
            const urls = {
                interno: "{{ route('gestion.servicios.nuevo.interno') }}",
                externo: "{{ route('gestion.servicios.nuevo.externo') }}"
            };
            window.location.href = urls[selectedType];
        }
    </script>
@endsection
