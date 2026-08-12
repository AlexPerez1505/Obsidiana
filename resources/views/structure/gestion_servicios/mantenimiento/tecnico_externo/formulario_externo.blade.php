@extends('layouts.qr')

@section('title', 'Mantenimiento - ' . ($service->service_number ?? 'OS'))
@section('card-class', 'card--wide')

@section('content')
    @php
        $readonly = auth()->check();
        $techName = trim(($service->externalTechnician->nombre ?? '') . ' ' . ($service->externalTechnician->apellidos ?? ''));
        $equipment = $service->serviceEquipment;
        $osNumber = preg_replace('/^NS-/', 'OS-', $service->service_number ?? '');
        $maintenance = $service->maintenance;
        $currentStepSlug = $service->currentStep?->slug;
    @endphp

    @if ($errors->any())
        <div class="alert alert--err">{{ $errors->first() }}</div>
    @endif

    <style>
        :root { --green:#22C55E; --green-d:#16a34a; --green-soft:rgba(34,197,94,0.14); --surface:rgba(8,18,40,0.82); }
        body, .wrap { background: #050b14 !important; color: #fff !important; }
        .card, .card--wide {
            background: rgba(8,18,40,0.95) !important;
            border: 1px solid rgba(34,197,94,0.55) !important;
            box-shadow: 0 12px 40px rgba(0,0,0,0.45), 0 0 18px rgba(34,197,94,0.35), inset 0 1px 0 rgba(255,255,255,0.04) !important;
            color: #fff !important;
        }
        .os-banner { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; margin-bottom: 22px; }
        .os-banner h1 { font-size: 22px; margin: 0; color: #fff; }
        .os-badge { padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 700; text-transform: uppercase; background: rgba(34,197,94,0.14); color: #22C55E; border: 1px solid rgba(34,197,94,0.4); }
        .os-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-bottom: 24px; }
        .os-card {
            background: rgba(8,18,40,0.82);
            border: 1px solid rgba(34,197,94,0.55);
            border-radius: 14px; padding: 14px;
            box-shadow: 0 8px 28px rgba(0,0,0,0.35), 0 0 14px rgba(34,197,94,0.2), inset 0 1px 0 rgba(255,255,255,0.04);
            backdrop-filter: blur(14px);
        }
        .os-card-label { font-size: 11px; text-transform: uppercase; color: rgba(255,255,255,0.55); font-weight: 700; margin: 0 0 4px; }
        .os-card-value { font-size: 15px; font-weight: 700; color: #fff; }
        .section-card {
            background: rgba(8,18,40,0.82);
            border: 1px solid rgba(34,197,94,0.55);
            border-radius: 16px; padding: 20px;
            margin-bottom: 18px;
            box-shadow: 0 8px 28px rgba(0,0,0,0.35), 0 0 14px rgba(34,197,94,0.2), inset 0 1px 0 rgba(255,255,255,0.04);
            backdrop-filter: blur(14px);
        }
        .section-title { font-size: 13px; font-weight: 800; text-transform: uppercase; color: #22C55E; margin: 0 0 16px; letter-spacing: 0.04em; }
        label { display: block; font-size: 13px; font-weight: 700; color: #fff; margin: 0 0 6px; }
        input[type=text], input[type=date], input[type=number], select, textarea {
            width: 100%; padding: 11px 12px;
            border: 1px solid rgba(34,197,94,0.45); border-radius: 10px;
            font-size: 14px; background: rgba(8,18,40,0.55); color: #fff; outline: none; transition: border .15s, box-shadow .15s;
        }
        input:focus, select:focus, textarea:focus { border-color: #22C55E; box-shadow: 0 0 12px rgba(34,197,94,0.35); }
        textarea { min-height: 100px; resize: vertical; }
        input[readonly], textarea[readonly], select[disabled], input[disabled] { opacity: 0.75; cursor: default; }
        .two-cols { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .item-row { display: flex; align-items: center; gap: 8px; margin-bottom: 8px; }
        .item-row input[type=checkbox] { width: auto; accent-color: #22C55E; }
        .item-row .item-input { flex: 1; }
        .item-row .qty-input { width: 70px; }
        .item-row .btn-icon { width: 30px; height: 30px; border: none; border-radius: 8px; background: rgba(220,38,38,0.16); color: #f87171; cursor: pointer; font-weight: 800; }
        .add-btn {
            background: rgba(34,197,94,0.14); border: 1px dashed rgba(34,197,94,0.75); color: #22C55E;
            padding: 9px 14px; border-radius: 10px; cursor: pointer; font-size: 13px; font-weight: 700;
        }
        .add-btn:hover { background: rgba(34,197,94,0.22); }
        .evidence-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; }
        .evidence-box {
            border: 2px dashed rgba(34,197,94,0.45); border-radius: 14px; padding: 18px; text-align: center;
            cursor: pointer; background: rgba(8,18,40,0.55); transition: border-color .15s;
        }
        .evidence-box:hover { border-color: #22C55E; }
        .evidence-box input { display: none; }
        .evidence-box span { display: block; font-size: 13px; font-weight: 700; color: #22C55E; }
        .evidence-box small { display: block; font-size: 11px; color: rgba(255,255,255,0.55); margin-top: 4px; }
        .evidence-preview { max-width: 100%; max-height: 70px; margin-top: 10px; border-radius: 8px; display: none; }
        .evidence-empty { font-size: 12px; color: rgba(255,255,255,0.55); margin-top: 10px; }
        .btn-wrap { display: flex; justify-content: flex-end; margin-top: 10px; }
        .btn--blue, .btn { background: #22C55E !important; color: #062f18 !important; font-weight: 700; }
        .btn--blue:hover, .btn:hover { background: #16a34a !important; }
        .alert--err { background: rgba(239,68,68,0.14); color: #fca5a5; border: 1px solid rgba(239,68,68,0.45); }
        .success-card { text-align: center; padding: 40px 20px; }
        .success-card svg { color: #22C55E; margin-bottom: 16px; }
        .success-card h2 { color: #22C55E; margin: 0 0 10px; font-size: 22px; }
        .success-card p { color: rgba(255,255,255,0.8); margin: 0; font-size: 15px; }
        @media (max-width: 640px) { .os-grid, .two-cols, .evidence-grid { grid-template-columns: 1fr; } }
    </style>

    @if (!$readonly && $currentStepSlug !== 'llenado-mantenimiento')
        <div class="success-card">
            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            <h2>Salida de técnico externo registrada</h2>
            <p>El mantenimiento fue guardado correctamente. Puedes cerrar esta pestaña.</p>
        </div>
    @else
        <div class="os-banner">
            <h1>Orden de Servicio {{ $osNumber ?: 'OS-' . $service->id }}</h1>
            <span class="os-badge">Mantenimiento externo</span>
        </div>

        <div class="os-grid">
            <div class="os-card">
                <p class="os-card-label">Equipo</p>
                <p class="os-card-value">{{ $equipment->type_text ?? 'N/A' }} {{ $equipment->brand_text ?? '' }} {{ $equipment->model_text ?? '' }}</p>
            </div>
            <div class="os-card">
                <p class="os-card-label">Técnico externo</p>
                <p class="os-card-value">{{ $techName ?: 'N/A' }}</p>
            </div>
            <div class="os-card">
                <p class="os-card-label">Fecha de creación</p>
                <p class="os-card-value">{{ $service->created_at?->format('d/m/Y H:i') }}</p>
            </div>
        </div>

        <form action="{{ route('gestion.servicios.maintenance.store', ['service' => $service]) }}" method="POST" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="tipo_mantenimiento" value="externo">

            <div class="section-card">
                <h2 class="section-title">1. Tipo de reparación</h2>
                <select name="tipo_reparacion" style="max-width: 320px;" {{ $readonly ? 'disabled' : '' }}>
                    <option value="" disabled {{ old('tipo_reparacion', $maintenance?->tipo_reparacion) ? '' : 'selected' }}>Selecciona</option>
                    <option value="preventivo" {{ old('tipo_reparacion', $maintenance?->tipo_reparacion) === 'preventivo' ? 'selected' : '' }}>Preventivo</option>
                    <option value="correctivo" {{ old('tipo_reparacion', $maintenance?->tipo_reparacion) === 'correctivo' ? 'selected' : '' }}>Correctivo</option>
                    <option value="mixto" {{ old('tipo_reparacion', $maintenance?->tipo_reparacion) === 'mixto' ? 'selected' : '' }}>Mixto</option>
                </select>
            </div>

            <div class="section-card">
                <h2 class="section-title">2. Checklist de mantenimiento</h2>
                <div id="checklistContainer">
                    @php
                        $checklist = old('checklist', $maintenance?->checklist ?? ['Limpieza interna', 'Revisión de conexiones', 'Prueba de funcionamiento']);
                    @endphp
                    @foreach ($checklist as $index => $item)
                        <div class="item-row">
                            <input type="checkbox" name="checklist[{{ $index }}][done]" value="1" {{ ($item['done'] ?? false) ? 'checked' : '' }} {{ $readonly ? 'disabled' : '' }}>
                            <input type="text" name="checklist[{{ $index }}][text]" class="item-input" value="{{ $item['text'] ?? $item }}" placeholder="Actividad" {{ $readonly ? 'readonly' : '' }}>
                            @if (!$readonly)
                                <button type="button" class="btn-icon" onclick="this.parentElement.remove()">×</button>
                            @endif
                        </div>
                    @endforeach
                </div>
                @if (!$readonly)
                    <button type="button" class="add-btn" onclick="addChecklistItem()">+ Agregar actividad</button>
                @endif
            </div>

            <div class="section-card">
                <h2 class="section-title">3. Diagnóstico</h2>
                <div class="two-cols">
                    <div>
                        <label>Trabajo realizado</label>
                        <textarea name="descripcion" placeholder="Describe el trabajo realizado" {{ $readonly ? 'readonly' : '' }}>{{ old('descripcion', $maintenance?->descripcion) }}</textarea>
                    </div>
                    <div>
                        <label>Fallas encontradas</label>
                        <textarea name="fallas_encontradas" placeholder="Describe las fallas" {{ $readonly ? 'readonly' : '' }}>{{ old('fallas_encontradas', $maintenance?->fallas_encontradas) }}</textarea>
                    </div>
                </div>
            </div>

            <div class="section-card">
                <h2 class="section-title">4. Refacciones utilizadas</h2>
                <div id="refaccionesContainer">
                    @php
                        $refacciones = old('refacciones', $maintenance?->refacciones ?? [['nombre' => '', 'cantidad' => 1]]);
                    @endphp
                    @foreach ($refacciones as $index => $ref)
                        <div class="item-row">
                            <input type="text" name="refacciones[{{ $index }}][nombre]" class="item-input" value="{{ $ref['nombre'] ?? '' }}" placeholder="Refacción" {{ $readonly ? 'readonly' : '' }}>
                            <input type="number" name="refacciones[{{ $index }}][cantidad]" class="qty-input" value="{{ $ref['cantidad'] ?? 1 }}" min="1" {{ $readonly ? 'disabled' : '' }}>
                            @if (!$readonly)
                                <button type="button" class="btn-icon" onclick="this.parentElement.remove()">×</button>
                            @endif
                        </div>
                    @endforeach
                </div>
                @if (!$readonly)
                    <button type="button" class="add-btn" onclick="addRefaccion()">+ Agregar refacción</button>
                @endif
            </div>

            <div class="section-card">
                <h2 class="section-title">5. Evidencias</h2>
                <div class="evidence-grid">
                    @for ($i = 1; $i <= 3; $i++)
                        @php $ev = $maintenance?->{'evidencia_' . $i}; @endphp
                        <label class="evidence-box" id="evBox{{ $i }}">
                            @if ($ev)
                                <span>Evidencia {{ $i }}</span>
                                <img src="{{ asset('storage/' . $ev) }}" class="evidence-preview" style="display:block;">
                            @else
                                @if ($readonly)
                                    <span>Evidencia {{ $i }}</span>
                                    <div class="evidence-empty">Sin evidencia</div>
                                @else
                                    <span>Subir evidencia {{ $i }}</span>
                                    <small>JPG, PNG (máx. 5MB)</small>
                                    <input type="file" name="evidencia_{{ $i }}" accept="image/png,image/jpeg,image/jpg" onchange="previewImage(this, {{ $i }})">
                                    <img src="" class="evidence-preview" id="evPreview{{ $i }}">
                                @endif
                            @endif
                        </label>
                    @endfor
                </div>
            </div>

            <div class="section-card">
                <h2 class="section-title">6. Próximo mantenimiento</h2>
                <input type="date" name="proximo_mantenimiento" value="{{ old('proximo_mantenimiento', $maintenance?->proximo_mantenimiento?->format('Y-m-d')) }}" style="max-width: 220px;" {{ $readonly ? 'disabled' : '' }}>
            </div>

            @if (!$readonly)
                <div class="btn-wrap">
                    <button type="submit" class="btn btn--blue">Marcar como salida de técnico externo</button>
                </div>
            @endif
        </form>
    @endif

    <script>
        function addChecklistItem() {
            const container = document.getElementById('checklistContainer');
            const index = container.children.length;
            const div = document.createElement('div');
            div.className = 'item-row';
            div.innerHTML = `
                <input type="checkbox" name="checklist[${index}][done]" value="1">
                <input type="text" name="checklist[${index}][text]" class="item-input" placeholder="Actividad">
                <button type="button" class="btn-icon" onclick="this.parentElement.remove()">×</button>
            `;
            container.appendChild(div);
        }

        function addRefaccion() {
            const container = document.getElementById('refaccionesContainer');
            const index = container.children.length;
            const div = document.createElement('div');
            div.className = 'item-row';
            div.innerHTML = `
                <input type="text" name="refacciones[${index}][nombre]" class="item-input" placeholder="Refacción">
                <input type="number" name="refacciones[${index}][cantidad]" class="qty-input" value="1" min="1">
                <button type="button" class="btn-icon" onclick="this.parentElement.remove()">×</button>
            `;
            container.appendChild(div);
        }

        function previewImage(input, index) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const img = document.getElementById('evPreview' + index);
                    if (img) {
                        img.src = e.target.result;
                        img.style.display = 'block';
                    }
                };
                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>
@endsection
