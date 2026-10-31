@extends('structure.gestion_servicios.layout')

@php
    $equipmentTypes = $equipmentTypes ?? collect([]);
    $brands = $brands ?? collect([]);
    $customers = $customers ?? collect([]);
    $technicians = $technicians ?? collect([]);
@endphp

@section('title', 'Nuevo registro externo')

@push('head')
<style>
.wizard-header { display:flex; align-items:center; gap:14px; margin-bottom:22px; }
.wizard-icon { width:52px; height:52px; border-radius:14px; background:var(--primary-soft); color:var(--primary); display:flex; align-items:center; justify-content:center; }
.wizard-actions { display:flex; align-items:center; gap:10px; }

.stepper { display:flex; align-items:center; gap:16px; margin:22px 0; }
.step { display:flex; align-items:center; gap:8px; color:var(--muted); font-size:14px; font-weight:700; }
.step .dot { width:28px; height:28px; border-radius:50%; border:2px solid var(--border); display:flex; align-items:center; justify-content:center; font-size:13px; background:var(--surface); }
.step.active { color:var(--primary); }
.step.active .dot { background:var(--primary); color:#fff; border-color:var(--primary); }
.step.done { color:var(--green); }
.step.done .dot { background:var(--green); color:#fff; border-color:var(--green); }
.stepper .line { flex:1; height:2px; background:var(--border); border-radius:2px; max-width:70px; }
.step-panel { display:none; }
.step-panel.active { display:block; }

.client-tabs { display:flex; gap:12px; margin-bottom:18px; border-bottom:1px solid var(--border); padding-bottom:12px; }
.tab-btn { background:transparent; border:none; font-size:14px; font-weight:700; color:var(--muted); padding:8px 16px; cursor:pointer; border-bottom:2px solid transparent; display:inline-flex; align-items:center; gap:8px; text-decoration:none; }
.tab-btn.active { color:var(--primary); border-bottom-color:var(--primary); }
.search-box { position:relative; }
.search-box input { width:100%; padding:12px 12px 12px 40px; border:1px solid var(--border); border-radius:9px; background:var(--surface); color:var(--text); }
.search-box svg { position:absolute; left:12px; top:50%; transform:translateY(-50%); color:var(--muted); }
.client-list { display:flex; flex-direction:column; gap:10px; }
.client-card { display:flex; align-items:center; justify-content:space-between; gap:14px; padding:16px; border:1px solid var(--border); border-radius:14px; background:var(--surface); cursor:pointer; }
.client-card.selected { border-color:var(--primary); background:var(--primary-soft); }
.client-avatar { width:44px; height:44px; border-radius:50%; background:var(--primary); color:#fff; display:flex; align-items:center; justify-content:center; font-size:15px; font-weight:800; }
.client-meta { flex:1; }
.client-name { font-weight:700; font-size:15px; }
.client-info { font-size:13px; color:var(--muted); display:flex; align-items:center; gap:12px; margin-top:4px; }

.technician-list { display:flex; flex-direction:column; gap:10px; }
.tech-row { display:flex; align-items:center; justify-content:space-between; gap:14px; padding:14px; border:1px solid var(--border); border-radius:14px; cursor:pointer; }
.tech-row.active { border-color:var(--primary); background:var(--primary-soft); }
.tech-avatar { width:40px; height:40px; border-radius:50%; background:var(--primary); color:#fff; display:flex; align-items:center; justify-content:center; font-weight:800; }
.badge { padding:3px 10px; border-radius:999px; font-size:12px; font-weight:700; }
.badge.ok { background:var(--green-soft); color:var(--green); }
.badge.warn { background:var(--accent-soft); color:var(--accent); }

.form-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:18px; margin-top:18px; }
.form-group label { font-size:13px; font-weight:700; margin-bottom:6px; display:block; }
.form-group input, .form-group select, .form-group textarea { width:100%; padding:11px 12px; border:1px solid var(--border); border-radius:9px; background:var(--surface); color:var(--text); font-size:14px; }
.form-group input:focus, .form-group select:focus, .form-group textarea:focus { border-color:var(--primary); outline:none; }
.form-group input::placeholder, .form-group textarea::placeholder { color:#aaa; }
.form-group input:disabled { opacity:0.55; cursor:not-allowed; }
.upload-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(130px,1fr)); gap:12px; margin-top:8px; }
.upload-card { position:relative; border:1px dashed var(--border); border-radius:12px; padding:14px; text-align:center; cursor:pointer; color:var(--muted); background:var(--surface); overflow:hidden; }
.upload-card:hover { border-color:var(--primary); color:var(--primary); }
.upload-card .evidence-preview { max-width:100%; max-height:100px; object-fit:contain; border-radius:8px; }
.upload-card .file-name { font-size:11px; margin-top:6px; word-break:break-word; color:var(--text); }
.upload-card .remove-evidence { position:absolute; top:6px; right:6px; width:22px; height:22px; background:var(--danger, #ff4a4a); color:#fff; border:none; border-radius:50%; cursor:pointer; font-size:14px; line-height:1; display:flex; align-items:center; justify-content:center; z-index:10; }
.signature-box { border:1px dashed var(--border); border-radius:12px; width:100%; height:120px; touch-action:none; }

.combobox { position:relative; display:flex; align-items:center; }
.combobox input { padding-right:38px; }
.combobox-arrow { position:absolute; right:10px; top:50%; transform:translateY(-50%); background:transparent; border:none; color:var(--muted); cursor:pointer; padding:4px; display:flex; align-items:center; justify-content:center; }
.combobox-list { position:absolute; top:calc(100% + 6px); left:0; right:0; max-height:220px; overflow-y:auto; background:var(--surface); border:1px solid var(--border); border-radius:9px; box-shadow:var(--shadow); z-index:100; list-style:none; margin:0; padding:6px 0; display:none; }
.combobox-list.open { display:block; }
.combobox-list li { padding:10px 14px; cursor:pointer; color:var(--text); font-size:14px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.combobox-list li:hover, .combobox-list li.active { background:var(--primary-soft); color:var(--primary); }
.combobox-list .no-results { color:var(--muted); cursor:default; text-align:center; font-size:13px; }

.hidden { display:none !important; }
</style>
@endpush

@section('service_content')
@if ($errors->any())
    <x-ui.card style="margin-bottom:22px; border-color:#ef4444;">
        <strong style="color:#ef4444;">Revisa los siguientes campos:</strong>
        <ul style="margin:6px 0 0; padding-left:18px; color:#ef4444;">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </x-ui.card>
@endif

<form id="registro-form" method="POST" action="{{ route('gestion.servicios.registro.store') }}" enctype="multipart/form-data" autocomplete="off">
    @csrf
    <input type="hidden" name="customer_id" id="customer_id" value="{{ $customers->first()?->id }}">
    <input type="hidden" name="internal_technician_id" id="internal_technician_id" value="{{ $technicians->first()?->id }}">
    <input type="hidden" name="mantenimiento_interno" value="1">
    <input type="hidden" name="wizard_step" id="wizard_step" value="{{ old('wizard_step', 1) }}">

    <x-ui.card style="position:relative;">
        <div class="wizard-actions" style="position:absolute; top:18px; right:18px; z-index:10;">
            <button type="button" class="btn btn--ghost" id="btn-secondary" style="display:none;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                Cancelar
            </button>
            <button type="button" class="btn" id="btn-primary">
                Siguiente: Equipo
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
            </button>
        </div>
        <div class="wizard-header">
            <div class="wizard-icon">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
            </div>
            <div>
                <h1 class="wizard-title" style="font-size:24px; margin:0;">Nuevo servicio</h1>
                <p class="wizard-subtitle" style="font-size:14px; color:var(--muted); margin:4px 0 0;">Crea un nuevo servicio</p>
            </div>
        </div>
    </x-ui.card>

    <div class="stepper">
        <div class="step active" data-step="1"><span class="dot">1</span> Cliente</div>
        <div class="line"></div>
        <div class="step" data-step="2"><span class="dot">2</span> Equipo</div>
        <div class="line"></div>
        <div class="step" data-step="3"><span class="dot">3</span> Técnico</div>
    </div>

    <!-- Paso 1: Cliente -->
    <div class="step-panel active" data-step="1">
        <x-ui.card>
            <div class="client-tabs">
                <button type="button" class="tab-btn active" data-tab="search">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    Buscar cliente existente
                </button>
                <a href="{{ route('commercial.clientes.create', ['return_to' => route('gestion.servicios.registro')]) }}" class="tab-btn" style="text-decoration:none;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/></svg>
                    Registrar nuevo cliente
                </a>
            </div>

            <div id="tab-search">
                <div class="search-box" style="margin-bottom:14px;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
                    <input type="text" placeholder="Buscar por nombre, telefono o correo" id="client-search">
                </div>

                <div class="client-list" id="client-list">
                    @forelse($customers as $customer)
                        @php
                            $customerNames = explode(' ', trim($customer->nombre . ' ' . $customer->apellido));
                            $initials = count($customerNames) >= 2
                                ? strtoupper(substr($customerNames[0], 0, 1) . substr($customerNames[1], 0, 1))
                                : (count($customerNames) === 1 ? strtoupper(substr($customerNames[0], 0, 2)) : 'CL');
                            $fullName = trim($customer->nombre . ' ' . $customer->apellido) ?: 'Sin nombre';
                        @endphp
                        <div class="client-card {{ $loop->first ? 'selected' : '' }}" data-client="{{ $loop->index }}" data-name="{{ $fullName }}" data-phone="{{ $customer->telefono ?? '' }}" data-email="{{ $customer->correo ?? '' }}">
                            <div class="client-avatar" style="{{ $loop->first ? '' : 'background:var(--muted);' }}">{{ $initials }}</div>
                            <div class="client-meta">
                                <div class="client-name">{{ $fullName }}</div>
                                <div class="client-info">
                                    <span>{{ $customer->telefono ?? 'Sin teléfono' }}</span>
                                    <span>{{ $customer->correo ?? 'Sin correo' }}</span>
                                </div>
                            </div>
                            <button type="button" class="btn {{ $loop->first ? '' : 'btn--ghost' }}" style="padding:8px 14px; font-size:13px;" onclick="selectClient({{ $loop->index }})">{{ $loop->first ? 'Seleccionado' : 'Seleccionar' }}</button>
                        </div>
                    @empty
                        <p class="muted" style="text-align:center; margin:14px 0; font-size:13px;">No hay clientes registrados.</p>
                    @endforelse
                </div>
            </div>
        </x-ui.card>
    </div>

    <!-- Paso 2: Equipo -->
    <div class="step-panel" data-step="2">
        <x-ui.card>
            <h3 style="display:flex; align-items:center; gap:10px; font-size:18px; margin:0 0 8px;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" color="var(--primary)"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
                Datos del equipo
            </h3>
            <p class="muted" style="margin:0 0 18px; font-size:13px;">Ingresa la información del equipo para registrarlo</p>

            <div class="form-grid">
                <div class="form-group">
                    <label for="tipo_equipo">Tipo de equipo</label>
                    <input type="text" name="tipo_equipo" id="tipo_equipo" list="tipo_equipo_list" placeholder="Ej. Equipo médico" value="{{ old('tipo_equipo') }}">
                    <datalist id="tipo_equipo_list">
                        @foreach ($equipmentTypes->unique('name')->sortBy('name')->values() as $type)
                            <option value="{{ $type->name }}">
                        @endforeach
                    </datalist>
                </div>
                <div class="form-group">
                    <label for="subtipo">Subtipo</label>
                    <input type="text" name="subtipo" id="subtipo" list="subtipo_list" placeholder="Ej. Monitor de signos vitales" value="{{ old('subtipo') }}" disabled>
                    <datalist id="subtipo_list"></datalist>
                </div>
                <div class="form-group">
                    <label for="marca">Marca</label>
                    <input type="text" name="marca" id="marca" list="marca_list" placeholder="Ej. Olympus" value="{{ old('marca') }}">
                    <datalist id="marca_list">
                        @foreach ($brands->unique('name')->sortBy('name')->values() as $brand)
                            <option value="{{ $brand->name }}">
                        @endforeach
                    </datalist>
                </div>
                <div class="form-group">
                    <label for="modelo">Modelo</label>
                    <input type="text" name="modelo" id="modelo" list="modelo_list" placeholder="Ej. C-90" value="{{ old('modelo') }}" disabled>
                    <datalist id="modelo_list"></datalist>
                </div>
                <div class="form-group">
                    <label for="serie">Número de serie</label>
                    <input type="text" name="serie" id="serie" placeholder="Ej. SN-893-832" value="{{ old('serie') }}">
                </div>
                <div class="form-group">
                    <label for="equipo_transporte">Equipo de transporte recibido</label>
                    <select name="equipo_transporte" id="equipo_transporte">
                        <option value="">Selecciona una opción</option>
                        <option value="maletin" {{ old('equipo_transporte') === 'maletin' ? 'selected' : '' }}>Maletín</option>
                        <option value="estuche" {{ old('equipo_transporte') === 'estuche' ? 'selected' : '' }}>Estuche</option>
                        <option value="contenedor" {{ old('equipo_transporte') === 'contenedor' ? 'selected' : '' }}>Contenedor</option>
                        <option value="otro" {{ old('equipo_transporte') === 'otro' ? 'selected' : '' }}>Otro</option>
                    </select>
                </div>
                <div class="form-group" id="equipo-transporte-otro-group" style="{{ old('equipo_transporte') === 'otro' ? '' : 'display:none;' }}">
                    <label for="equipo_transporte_otro">¿Cuál?</label>
                    <input type="text" name="equipo_transporte_otro" id="equipo_transporte_otro" placeholder="Especifica el equipo de transporte" value="{{ old('equipo_transporte_otro') }}">
                </div>
                <div class="form-group">
                    <label for="accesorios_incluidos">¿Accesorios incluidos?</label>
                    <select name="accesorios_incluidos" id="accesorios_incluidos">
                        <option value="">Selecciona una opción</option>
                        <option value="1" {{ old('accesorios_incluidos') === '1' ? 'selected' : '' }}>Sí</option>
                        <option value="0" {{ old('accesorios_incluidos') === '0' ? 'selected' : '' }}>No</option>
                    </select>
                </div>
                <div class="form-group" id="accesorios-detalle-group" style="{{ old('accesorios_incluidos') === '1' ? '' : 'display:none;' }}">
                    <label for="accesorios_detalle">¿Cuáles accesorios?</label>
                    <input type="text" name="accesorios_detalle" id="accesorios_detalle" placeholder="Ej. Cables, adaptadores, funda" value="{{ old('accesorios_detalle') }}">
                </div>

                <div class="form-group" style="grid-column:1/-1;">
                    <label for="descripcion_equipo">Descripción del equipo</label>
                    <textarea name="descripcion_equipo" id="descripcion_equipo" rows="3" placeholder="Describe el equipo y su función">{{ old('descripcion_equipo') }}</textarea>
                </div>

            </div>

            <div class="form-group" style="margin-top:18px;">
                <label>Evidencia del equipo</label>
                <div class="upload-grid">
                    <label class="upload-card">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                        <div style="font-size:13px; margin-top:8px;">Imagen 1</div>
                        <div style="font-size:12px;">Toca para subir</div>
                        <input type="file" name="evidencia_1" accept="image/*" style="display:none;">
                    </label>
                    <label class="upload-card">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                        <div style="font-size:13px; margin-top:8px;">Imagen 2</div>
                        <div style="font-size:12px;">Toca para subir</div>
                        <input type="file" name="evidencia_2" accept="image/*" style="display:none;">
                    </label>
                    <label class="upload-card">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                        <div style="font-size:13px; margin-top:8px;">Imagen 3</div>
                        <div style="font-size:12px;">Toca para subir</div>
                        <input type="file" name="evidencia_3" accept="image/*" style="display:none;">
                    </label>
                    <label class="upload-card">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="23 7 23 17 7 17 7 7 23 7"/><rect x="1" y="3" width="4" height="18" rx="1"/><polyline points="5 7 7 7 7 17 5 17"/></svg>
                        <div style="font-size:13px; margin-top:8px;">Video</div>
                        <div style="font-size:12px;">Toca para subir</div>
                        <input type="file" name="evidencia_video" accept="video/*" style="display:none;">
                    </label>
                </div>
                <p style="font-size:12px; color:var(--muted); margin-top:8px;">Formatos permitidos: JPG, PNG, MP4. Tamaño máximo: 10MB por archivo</p>
            </div>

            <div class="form-group" style="margin-top:18px;">
                <label>Firma Digital</label>
                <canvas class="signature-box" id="signature-pad" style="cursor:crosshair;"></canvas>
                <div style="display:flex; align-items:center; gap:14px; margin-top:8px;">
                    <a href="#" style="font-size:13px; color:var(--primary);" onclick="clearSignature(); return false;">Limpiar firma</a>
                    <a href="#" style="font-size:13px; color:var(--primary);" onclick="document.getElementById('signature-upload').click(); return false;">Cargar firma</a>
                    <input type="file" id="signature-upload" accept="image/*" style="display:none;">
                </div>
                <input type="hidden" name="firma" id="firma-input">
            </div>
        </x-ui.card>
    </div>

    <!-- Paso 3: Técnico -->
    <div class="step-panel" data-step="3">
        <x-ui.card>
            <h3 style="display:flex; align-items:center; gap:10px; font-size:18px; margin:0 0 8px;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" color="var(--primary)"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                Técnico responsable
            </h3>
            <p class="muted" style="margin:0 0 18px; font-size:13px;">Selecciona el técnico que será responsable del registro</p>

            <div class="technician-list" id="tech-list">
                @forelse($technicians as $index => $tech)
                    @php
                        $initials = collect(explode(' ', $tech->name))->map(fn($w) => strtoupper(substr($w, 0, 1)))->take(2)->join('');
                        $statusClass = match($tech->status) {
                            'approved' => 'ok',
                            'banned' => 'danger',
                            default => 'warn',
                        };
                        $statusLabel = $tech->statusLabel();
                    @endphp
                    <div class="tech-row {{ $index === 0 ? 'active' : '' }}" data-tech="{{ $index }}">
                        <div style="display:flex; align-items:center; gap:12px;">
                            <div class="tech-avatar">{{ $initials }}</div>
                            <div>
                                <div style="font-weight:700;">{{ $tech->name }}</div>
                                <div class="muted" style="font-size:13px;">{{ $tech->email }}</div>
                            </div>
                        </div>
                        <span class="badge {{ $statusClass }}">{{ $statusLabel }}</span>
                    </div>
                @empty
                    <p class="muted" style="text-align:center; font-size:13px;">No hay técnicos registrados.</p>
                @endforelse
            </div>
        </x-ui.card>
    </div>
</form>
@endsection

@push('scripts')
@php
$clients = $customers->map(function($customer) {
    $names = explode(' ', trim($customer->nombre . ' ' . $customer->apellido));
    if (count($names) >= 2) {
        $initials = strtoupper(substr($names[0], 0, 1) . substr($names[1], 0, 1));
    } elseif (count($names) === 1) {
        $initials = strtoupper(substr($names[0], 0, 2));
    } else {
        $initials = 'CL';
    }
    return [
        'id' => $customer->id,
        'name' => trim($customer->nombre . ' ' . $customer->apellido) ?: 'Sin nombre',
        'phone' => $customer->telefono ?? '',
        'email' => $customer->correo ?? '',
        'initials' => $initials,
    ];
})->values();
$techniciansData = $technicians->map(function ($t) {
    return [
        'id' => $t->id,
        'name' => $t->name,
        'email' => $t->email,
    ];
})->values();
@endphp
<script>
    const clients = @json($clients);
    const techniciansData = @json($techniciansData);
    let selectedClient = 0;
    let selectedTech = 0;
    const wizardStepInput = document.getElementById('wizard_step');
    let currentStep = parseInt(wizardStepInput?.value) || 1;
    const totalSteps = 3;
    const form = document.getElementById('registro-form');
    const btnPrimary = document.getElementById('btn-primary');
    const btnSecondary = document.getElementById('btn-secondary');
    const cancelUrl = '{{ route('gestion.servicios.historial') }}';

    function updateStep() {
        document.querySelectorAll('.step-panel').forEach(p => p.classList.remove('active'));
        document.querySelector(`.step-panel[data-step="${currentStep}"]`)?.classList.add('active');
        document.querySelectorAll('.step').forEach(s => {
            const step = parseInt(s.dataset.step);
            s.classList.remove('active','done');
            if (step === currentStep) s.classList.add('active');
            else if (step < currentStep) s.classList.add('done');
        });

        if (wizardStepInput) wizardStepInput.value = currentStep;

        btnSecondary.style.display = '';
        btnSecondary.innerHTML = `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg> Cancelar`;

        if (currentStep === 1) {
            btnPrimary.innerHTML = `Siguiente: Equipo <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>`;
            btnPrimary.type = 'button';
        } else if (currentStep === 2) {
            btnPrimary.innerHTML = `Siguiente: Técnico <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>`;
            btnPrimary.type = 'button';
            setTimeout(function() {
                if (typeof resizeCanvas === 'function') resizeCanvas();
            }, 0);
        } else {
            btnPrimary.innerHTML = `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg> Guardar registro`;
            btnPrimary.type = 'submit';
        }
    }

    btnPrimary.addEventListener('click', function(e) {
        if (this.type === 'button') {
            e.preventDefault();
            currentStep = Math.min(currentStep + 1, totalSteps);
            updateStep();
        }
    });

    btnSecondary.addEventListener('click', function() {
        if (currentStep > 1) {
            currentStep--;
            updateStep();
        } else {
            window.location.href = cancelUrl;
        }
    });

    function selectClient(index) {
        selectedClient = index;
        document.querySelectorAll('.client-card').forEach((card, i) => {
            const btn = card.querySelector('button');
            if (i === index) {
                card.classList.add('selected');
                if (btn) { btn.className = 'btn'; btn.style.cssText = 'padding:8px 14px; font-size:13px;'; btn.textContent = 'Seleccionado'; }
            } else {
                card.classList.remove('selected');
                if (btn) { btn.className = 'btn btn--ghost'; btn.style.cssText = 'padding:8px 14px; font-size:13px;'; btn.textContent = 'Seleccionar'; }
            }
        });
        const client = clients[index];
        if (client) document.getElementById('customer_id').value = client.id;
    }

    document.getElementById('client-search')?.addEventListener('input', function() {
        const term = this.value.toLowerCase().trim();
        document.querySelectorAll('.client-card').forEach(card => {
            const name = (card.dataset.name || '').toLowerCase();
            const phone = (card.dataset.phone || '').toLowerCase();
            const email = (card.dataset.email || '').toLowerCase();
            card.style.display = (name.includes(term) || phone.includes(term) || email.includes(term)) ? '' : 'none';
        });
    });

    document.querySelectorAll('.client-card').forEach(card => {
        card.addEventListener('click', function(e) {
            if (e.target.tagName === 'BUTTON' || e.target.closest('button')) return;
            selectClient(parseInt(this.dataset.client));
        });
    });

    function selectTech(index) {
        selectedTech = index;
        document.querySelectorAll('#tech-list .tech-row').forEach((row, i) => {
            row.classList.toggle('active', i === index);
        });
        const tech = techniciansData[index];
        if (tech) document.getElementById('internal_technician_id').value = tech.id;
    }

    document.querySelectorAll('#tech-list .tech-row').forEach(row => {
        row.addEventListener('click', () => selectTech(parseInt(row.dataset.tech)));
    });

    // Campos condicionales: "otro" transporte pide especificar y "accesorios" pide detalle
    const transporteSelect = document.getElementById('equipo_transporte');
    const accesoriosSelect = document.getElementById('accesorios_incluidos');
    function syncConditionalFields() {
        document.getElementById('equipo-transporte-otro-group').style.display = transporteSelect?.value === 'otro' ? '' : 'none';
        document.getElementById('accesorios-detalle-group').style.display = accesoriosSelect?.value === '1' ? '' : 'none';
    }
    transporteSelect?.addEventListener('change', syncConditionalFields);
    accesoriosSelect?.addEventListener('change', syncConditionalFields);
    syncConditionalFields();

    if (clients.length > 0) selectClient(0);
    if (techniciansData.length > 0) selectTech(0);
    updateStep();

    // Firma digital
    const canvas = document.getElementById('signature-pad');
    const ctx = canvas.getContext('2d');
    const firmaInput = document.getElementById('firma-input');
    const signatureUpload = document.getElementById('signature-upload');
    let drawing = false;

    function resizeCanvas() {
        const width = canvas.clientWidth;
        const height = canvas.clientHeight;
        if (canvas.width !== width || canvas.height !== height) {
            canvas.width = width;
            canvas.height = height;
        }
        ctx.lineWidth = 2.5;
        ctx.lineCap = 'round';
        ctx.lineJoin = 'round';
        ctx.strokeStyle = getComputedStyle(document.body).color || '#000';
    }

    function getPos(e) {
        const rect = canvas.getBoundingClientRect();
        let clientX, clientY;
        if (e.touches && e.touches.length) {
            clientX = e.touches[0].clientX;
            clientY = e.touches[0].clientY;
        } else {
            clientX = e.clientX;
            clientY = e.clientY;
        }
        return {
            x: (clientX - rect.left) * (canvas.width / rect.width),
            y: (clientY - rect.top) * (canvas.height / rect.height),
        };
    }

    function updateFirmaInput() {
        if (firmaInput) firmaInput.value = canvas.toDataURL('image/png');
    }

    function startDraw(e) {
        e.preventDefault();
        drawing = true;
        const pos = getPos(e);
        ctx.beginPath();
        ctx.moveTo(pos.x, pos.y);
    }

    function draw(e) {
        if (!drawing) return;
        e.preventDefault();
        const pos = getPos(e);
        ctx.lineTo(pos.x, pos.y);
        ctx.stroke();
    }

    function endDraw() {
        if (!drawing) return;
        drawing = false;
        ctx.closePath();
        updateFirmaInput();
    }

    function drawImageToCanvas(file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const img = new Image();
            img.onload = function() {
                resizeCanvas();
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                const scale = Math.min(canvas.width / img.width, canvas.height / img.height, 1);
                const x = (canvas.width - img.width * scale) / 2;
                const y = (canvas.height - img.height * scale) / 2;
                ctx.drawImage(img, x, y, img.width * scale, img.height * scale);
                updateFirmaInput();
            };
            img.src = e.target.result;
        };
        reader.readAsDataURL(file);
    }

    function clearSignature() {
        resizeCanvas();
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        if (firmaInput) firmaInput.value = '';
        if (signatureUpload) signatureUpload.value = '';
    }

    if (canvas) {
        canvas.addEventListener('mousedown', startDraw);
        canvas.addEventListener('mousemove', draw);
        canvas.addEventListener('mouseup', endDraw);
        canvas.addEventListener('mouseout', endDraw);
        canvas.addEventListener('touchstart', startDraw, { passive: false });
        canvas.addEventListener('touchmove', draw, { passive: false });
        canvas.addEventListener('touchend', endDraw);
    }

    if (signatureUpload) {
        signatureUpload.addEventListener('change', function() {
            if (this.files && this.files[0]) drawImageToCanvas(this.files[0]);
        });
    }

    window.clearSignature = clearSignature;
    window.addEventListener('resize', resizeCanvas);
    resizeCanvas();

    // Comboboxes
    (function () {
        function Combobox(input) {
            input.removeAttribute('list');
            input.setAttribute('autocomplete', 'off');

            var wrapper = document.createElement('div');
            wrapper.className = 'combobox';
            input.parentNode.insertBefore(wrapper, input);
            wrapper.appendChild(input);

            var arrow = document.createElement('button');
            arrow.type = 'button';
            arrow.className = 'combobox-arrow';
            arrow.setAttribute('tabindex', '-1');
            arrow.setAttribute('aria-label', 'Mostrar opciones');
            arrow.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9" /></svg>';
            wrapper.appendChild(arrow);

            var list = document.createElement('ul');
            list.className = 'combobox-list';
            wrapper.appendChild(list);

            var options = [];
            var open = false;
            var active = -1;

            function render(filter) {
                filter = filter || '';
                list.innerHTML = '';
                var term = filter.trim().toLowerCase();
                var matches = options.filter(function (o) { return o.toLowerCase().indexOf(term) !== -1; });
                matches.forEach(function (text, i) {
                    var li = document.createElement('li');
                    li.textContent = text;
                    if (i === active) li.classList.add('active');
                    li.addEventListener('mousedown', function (e) {
                        e.preventDefault();
                        pick(text);
                    });
                    list.appendChild(li);
                });
                if (matches.length === 0) {
                    var li = document.createElement('li');
                    li.className = 'no-results';
                    li.textContent = 'Sin coincidencias';
                    list.appendChild(li);
                }
            }

            function pick(text) {
                input.value = text;
                active = -1;
                close();
                input.dispatchEvent(new Event('input', { bubbles: true }));
            }

            function openList() {
                if (input.disabled) return;
                open = true;
                list.classList.add('open');
                active = -1;
                render(input.value);
            }

            function close() {
                open = false;
                active = -1;
                list.classList.remove('open');
            }

            input.addEventListener('focus', openList);
            input.addEventListener('blur', function () { setTimeout(close, 150); });
            input.addEventListener('input', function () {
                if (!open) openList();
                else render(input.value);
            });

            arrow.addEventListener('mousedown', function (e) {
                e.preventDefault();
                if (open) close();
                else input.focus();
            });

            input.addEventListener('keydown', function (e) {
                var items;
                if (!open) {
                    if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
                        e.preventDefault();
                        openList();
                    }
                    return;
                }
                items = list.querySelectorAll('li:not(.no-results)');
                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    active = (active + 1) % items.length;
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    active = (active - 1 + items.length) % items.length;
                } else if (e.key === 'Enter') {
                    e.preventDefault();
                    if (active >= 0 && items[active]) {
                        items[active].click();
                    } else if (input.value.trim()) {
                        close();
                    }
                } else if (e.key === 'Escape') {
                    close();
                    input.blur();
                } else {
                    return;
                }
                render(input.value);
                if (items[active]) items[active].scrollIntoView({ block: 'nearest' });
            });

            return {
                setOptions: function (arr) {
                    options = arr;
                    if (open) render(input.value);
                }
            };
        }

        var typeInput = document.getElementById('tipo_equipo');
        var subtypeInput = document.getElementById('subtipo');
        var brandInput = document.getElementById('marca');
        var modelInput = document.getElementById('modelo');

        if (typeInput && subtypeInput && brandInput && modelInput) {
            var typeCb = Combobox(typeInput);
            var subtypeCb = Combobox(subtypeInput);
            var brandCb = Combobox(brandInput);
            var modelCb = Combobox(modelInput);

            function debounce(fn, ms) {
                var t;
                return function () {
                    clearTimeout(t);
                    t = setTimeout(fn.bind(this), ms);
                };
            }

            function setEnabled(input, enabled) {
                var cb = input === subtypeInput ? subtypeCb : (input === modelInput ? modelCb : null);
                input.disabled = !enabled;
                if (!enabled) {
                    input.value = '';
                    if (cb) cb.setOptions([]);
                }
            }

            function loadSubtypes() {
                var value = typeInput.value.trim();
                setEnabled(subtypeInput, false);
                if (!value) return;
                fetch('{{ route('configuracion.tipos_equipo.subtypes') }}?equipment_type_name=' + encodeURIComponent(value))
                    .then(function (res) { return res.json(); })
                    .then(function (data) {
                        var names = data.map(function (i) { return i.name; });
                        subtypeCb.setOptions([...new Set(names)]);
                        setEnabled(subtypeInput, true);
                    })
                    .catch(function () { setEnabled(subtypeInput, false); });
            }

            function loadModels() {
                var value = brandInput.value.trim();
                setEnabled(modelInput, false);
                if (!value) return;
                fetch('{{ route('configuracion.tipos_equipo.models') }}?brand_name=' + encodeURIComponent(value))
                    .then(function (res) { return res.json(); })
                    .then(function (data) {
                        var names = data.map(function (i) { return i.name; });
                        modelCb.setOptions([...new Set(names)]);
                        setEnabled(modelInput, true);
                    })
                    .catch(function () { setEnabled(modelInput, false); });
            }

            typeCb.setOptions(Array.prototype.slice.call(document.querySelectorAll('#tipo_equipo_list option')).map(function (o) { return o.value; }));
            brandCb.setOptions(Array.prototype.slice.call(document.querySelectorAll('#marca_list option')).map(function (o) { return o.value; }));

            typeInput.addEventListener('input', debounce(loadSubtypes, 250));
            brandInput.addEventListener('input', debounce(loadModels, 250));

            if (typeInput.value) typeInput.dispatchEvent(new Event('input', { bubbles: true }));
            if (brandInput.value) brandInput.dispatchEvent(new Event('input', { bubbles: true }));
        }
    })();

    // Previsualización de evidencias
    (function () {
        function clearCard(card, input, icon, textDivs) {
            input.value = '';
            card.querySelectorAll('.evidence-preview, .file-name, .remove-evidence').forEach(function (el) { el.remove(); });
            if (icon) icon.style.display = '';
            textDivs.forEach(function (d) { d.style.display = ''; });
        }

        function renderPreview(card, input, file) {
            var icon = card.querySelector('svg');
            var textDivs = Array.prototype.slice.call(card.querySelectorAll('div')).filter(function (d) { return !d.classList.contains('evidence-preview') && !d.classList.contains('file-name') && !d.classList.contains('remove-evidence'); });
            card.querySelectorAll('.evidence-preview, .file-name').forEach(function (el) { el.remove(); });
            if (icon) icon.style.display = 'none';
            textDivs.forEach(function (d) { d.style.display = 'none'; });

            var isVideo = file.type.indexOf('video/') === 0;
            var preview;
            if (isVideo) {
                preview = document.createElement('div');
                preview.className = 'evidence-preview';
                preview.innerHTML = '<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="23 7 23 17 7 17 7 7 23 7"/><rect x="1" y="3" width="4" height="18" rx="1"/><polyline points="5 7 7 7 7 17 5 17"/></svg><div style="margin-top:4px;">Video seleccionado</div>';
            } else {
                preview = document.createElement('img');
                preview.className = 'evidence-preview';
                preview.src = URL.createObjectURL(file);
                preview.onload = function () { URL.revokeObjectURL(preview.src); };
            }
            card.appendChild(preview);

            var fileName = document.createElement('div');
            fileName.className = 'file-name';
            fileName.textContent = file.name;
            card.appendChild(fileName);

            if (!card.querySelector('.remove-evidence')) {
                var removeBtn = document.createElement('button');
                removeBtn.type = 'button';
                removeBtn.className = 'remove-evidence';
                removeBtn.innerHTML = '&times;';
                removeBtn.setAttribute('aria-label', 'Eliminar archivo');
                removeBtn.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    clearCard(card, input, icon, textDivs);
                });
                card.appendChild(removeBtn);
            }
        }

        document.querySelectorAll('.upload-card input[type="file"]').forEach(function (input) {
            var card = input.closest('.upload-card');
            if (!card) return;
            input.addEventListener('change', function () {
                if (input.files && input.files[0]) renderPreview(card, input, input.files[0]);
            });
        });
    })();
</script>
@endpush
