@extends('layouts.dashboard')
@section('title', 'Control de Usuarios')
@section('page-title', 'Control de Usuarios')
@section('page-sub', 'Administra las cuentas del sistema')

@php
    $pending = $users->where('status', \App\Models\User::STATUS_PENDING);
    $approved = $users->where('status', \App\Models\User::STATUS_APPROVED);
    $banned = $users->where('status', \App\Models\User::STATUS_BANNED);

    $avatarColors = [
        'A' => ['#e6ffe6', '#15803d'],
        'B' => ['#e6f0ff', '#007aff'],
        'C' => ['#fff3e8', '#f97316'],
        'D' => ['#f3e8ff', '#9333ea'],
        'E' => ['#ffebeb', '#ff4a4a'],
        'F' => ['#e6fff7', '#0d9488'],
        'G' => ['#fff3e8', '#fb923c'],
        'H' => ['#e6f0ff', '#4da3ff'],
        'I' => ['#fef9c3', '#ca8a04'],
        'J' => ['#e6ffe6', '#22c55e'],
        'K' => ['#fce7f3', '#db2777'],
        'L' => ['#e0f2fe', '#0284c7'],
        'M' => ['#f5f3ff', '#7c3aed'],
        'N' => ['#ecfdf5', '#059669'],
        'O' => ['#fff7ed', '#ea580c'],
        'P' => ['#eff6ff', '#2563eb'],
        'Q' => ['#fdf4ff', '#c026d3'],
        'R' => ['#f0fdf4', '#16a34a'],
        'S' => ['#fefce8', '#a16207'],
        'T' => ['#f0f9ff', '#0891b2'],
        'U' => ['#fdf2f8', '#db2777'],
        'V' => ['#f0fdfa', '#0d9488'],
        'W' => ['#fefcfa', '#d946ef'],
        'X' => ['#f8fafc', '#64748b'],
        'Y' => ['#fffbeb', '#d97706'],
        'Z' => ['#faf5ff', '#9333ea'],
    ];

    $initials = function ($name) {
        $parts = explode(' ', trim($name));
        $i = '';
        foreach ($parts as $p) {
            if ($p !== '') $i .= strtoupper($p[0]);
            if (strlen($i) >= 2) break;
        }
        return $i ?: '?';
    };

    $statusInfo = function ($user) {
        if ($user->isBanned()) return ['dot' => 'red', 'label' => 'Baneado'];
        if ($user->isPending()) return ['dot' => 'yellow', 'label' => 'Pendiente'];
        return ['dot' => 'green', 'label' => 'Activo'];
    };
@endphp

@push('head')
    @include('admin.users.partials._styles')
@endpush

@section('content')
<div class="uc-wrap">
    <div class="grid stat-row" style="margin-bottom:18px;">
        <x-ui.stat-card
            :value="$users->count()"
            label="Usuarios registrados"
            color="blue"
        >
            <x-slot:icon>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="26" height="26"><circle cx="9" cy="8" r="3.5"/><path d="M2 20c0-3.5 3-5.5 7-5.5s7 2 7 5.5"/><path d="M17 5a3 3 0 0 1 0 6"/></svg>
            </x-slot:icon>
        </x-ui.stat-card>

        <x-ui.stat-card
            :value="$pending->count()"
            label="Pendientes de aprobar"
            color="orange"
        >
            <x-slot:icon>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="26" height="26"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/></svg>
            </x-slot:icon>
        </x-ui.stat-card>

        <x-ui.stat-card
            :value="$approved->count()"
            label="Usuarios activos"
            color="green"
        >
            <x-slot:icon>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="26" height="26"><path d="M20 6L9 17l-5-5"/></svg>
            </x-slot:icon>
        </x-ui.stat-card>
    </div>

    {{-- Toolbar unificada: búsqueda, filtros, agregar y toggle de vista --}}
    <form method="GET" action="{{ route('admin.users.index') }}" class="uc-toolbar">
        <div class="uc-search">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
            <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Buscar usuario, nómina...">
        </div>
        <div class="uc-filter">
            <select name="status">
                <option value="">Estado: Todos</option>
                <option value="approved" {{ ($filters['status'] ?? '') === 'approved' ? 'selected' : '' }}>Activos</option>
                <option value="pending" {{ ($filters['status'] ?? '') === 'pending' ? 'selected' : '' }}>Pendientes</option>
                <option value="banned" {{ ($filters['status'] ?? '') === 'banned' ? 'selected' : '' }}>Baneados</option>
            </select>
        </div>
        @if($positions->isNotEmpty())
        <div class="uc-filter">
            <select name="position">
                <option value="">Puesto: Todos</option>
                @foreach($positions as $pos)
                    <option value="{{ $pos }}" {{ ($filters['position'] ?? '') === $pos ? 'selected' : '' }}>{{ $pos }}</option>
                @endforeach
            </select>
        </div>
        @endif
        <button type="submit" style="display:none;">Filtrar</button>

        <div class="uc-spacer"></div>

        <button type="button" class="uc-btn-add" onclick="document.getElementById('modal-hr-profile').style.display='flex'">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12h14"/></svg>
            Agregar usuario
        </button>
        <div class="uc-view-toggle">
            <button type="button" class="uc-view-btn active" data-view="grid" title="Vista de cuadrícula">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
            </button>
            <button type="button" class="uc-view-btn" data-view="list" title="Vista de lista">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/></svg>
            </button>
        </div>
    </form>

    {{-- Modal: completar datos de RH de un usuario ya registrado --}}
    <div id="modal-hr-profile" class="modal-overlay" style="display:none;">
        <div class="hr-modal">
            <div class="hr-modal-head">
                <div>
                    <h3>Agregar usuario a Recursos Humanos</h3>
                    <p>Selecciona un usuario ya registrado y completa sus datos. No se crean cuentas nuevas.</p>
                </div>
                <button type="button" class="hr-modal-close" onclick="document.getElementById('modal-hr-profile').style.display='none'" aria-label="Cerrar">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
                </button>
            </div>

            <form method="POST" action="{{ route('admin.users.hrProfile.update') }}" enctype="multipart/form-data" class="hr-modal-body">
                @csrf

                <select id="hr_user_id" name="user_id" required onchange="rellenarDatosRhUsuario(this)" class="hr-select">
                    <option value="">— Selecciona un usuario —</option>
                    @foreach($users as $u)
                        <option value="{{ $u->id }}"
                            data-position="{{ $u->position }}"
                            data-cargo="{{ $u->cargo }}"
                            data-phone="{{ $u->phone }}"
                            data-payroll="{{ $u->payroll_number }}"
                            data-checador="{{ $u->checador_id }}"
                            data-curp="{{ $u->curp }}"
                            data-ine="{{ $u->ine }}"
                            data-acta="{{ $u->acta_nacimiento }}"
                            data-licencia="{{ $u->licencia }}"
                            data-domicilio="{{ $u->domicilio }}"
                            data-fecha-ingreso="{{ optional($u->fecha_ingreso)->format('Y-m-d') }}"
                            data-vacaciones="{{ $u->vacaciones_disponibles }}"
                            data-nce="{{ $u->nombre_contacto_emergencia }}"
                            data-numce="{{ $u->numero_contacto_emergencia }}"
                            data-domce="{{ $u->domicilio_contacto_emergencia }}"
                            data-nces="{{ $u->nombre_contacto_emergencia_secundario }}"
                            data-numces="{{ $u->numero_contacto_emergencia_secundario }}"
                            data-domces="{{ $u->domicilio_contacto_emergencia_secundario }}"
                            data-roles="{{ $u->roles->pluck('id')->implode(',') }}"
                            data-docs='@json($u->employeeDocuments->pluck("file_path", "name")->map(fn ($p) => \Illuminate\Support\Facades\Storage::url($p)))'>
                            {{ $u->name }} — {{ $u->email }}
                        </option>
                    @endforeach
                </select>

                <div class="hr-section">
                    <p class="hr-section-title">Datos laborales</p>
                    <div class="hr-grid-2">
                        <label class="hr-field"><span>Puesto</span><input type="text" name="position" placeholder="Ej. Ingeniero de sistemas"></label>
                        <label class="hr-field"><span>Cargo</span><input type="text" name="cargo" placeholder="Ej. Ingeniero, Licenciado"></label>
                        <label class="hr-field"><span>Teléfono</span><input type="text" name="phone"></label>
                        <label class="hr-field"><span>Número de nómina</span><input type="text" name="payroll_number"></label>
                        <label class="hr-field"><span>ID de checador</span><input type="text" name="checador_id"></label>
                        <label class="hr-field"><span>Fecha de ingreso</span><input type="date" name="fecha_ingreso"></label>
                        <label class="hr-field hr-field--full"><span>Vacaciones disponibles (días)</span><input type="number" name="vacaciones_disponibles" min="0"></label>
                    </div>
                </div>

                <div class="hr-section">
                    <p class="hr-section-title">Roles</p>
                    <div class="hr-roles">
                        @foreach($roles as $role)
                            <label class="hr-chip">
                                <input type="checkbox" name="roles[]" value="{{ $role->id }}">
                                <span>{{ $role->label }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="hr-section">
                    <p class="hr-section-title">Identificación</p>
                    <p class="hr-hint">Captura el número/folio y, si quieres, sube el documento escaneado (PDF o imagen, máx. 5MB).</p>
                    <div class="hr-grid-2">
                        <div class="hr-doc">
                            <label class="hr-field"><span>CURP</span><input type="text" name="curp" maxlength="18"></label>
                            <x-ui.file-chip name="curp_file" doc="CURP" />
                        </div>
                        <div class="hr-doc">
                            <label class="hr-field"><span>INE</span><input type="text" name="ine"></label>
                            <x-ui.file-chip name="ine_file" doc="INE" />
                        </div>
                        <div class="hr-doc">
                            <label class="hr-field"><span>Acta de nacimiento</span><input type="text" name="acta_nacimiento"></label>
                            <x-ui.file-chip name="acta_nacimiento_file" doc="Acta de nacimiento" />
                        </div>
                        <div class="hr-doc">
                            <label class="hr-field"><span>Licencia de manejo</span><input type="text" name="licencia"></label>
                            <x-ui.file-chip name="licencia_file" doc="Licencia de manejo" />
                        </div>
                    </div>
                    <label class="hr-field"><span>Domicilio</span><input type="text" name="domicilio"></label>
                </div>

                <div class="hr-section">
                    <p class="hr-section-title">Contacto de emergencia</p>
                    <div class="hr-grid-2">
                        <label class="hr-field"><span>Nombre</span><input type="text" name="nombre_contacto_emergencia"></label>
                        <label class="hr-field"><span>Teléfono</span><input type="text" name="numero_contacto_emergencia"></label>
                    </div>
                    <label class="hr-field"><span>Domicilio del contacto</span><input type="text" name="domicilio_contacto_emergencia"></label>
                </div>

                <div class="hr-section hr-section--last">
                    <p class="hr-section-title">Contacto de emergencia secundario</p>
                    <div class="hr-grid-2">
                        <label class="hr-field"><span>Nombre</span><input type="text" name="nombre_contacto_emergencia_secundario"></label>
                        <label class="hr-field"><span>Teléfono</span><input type="text" name="numero_contacto_emergencia_secundario"></label>
                    </div>
                    <label class="hr-field"><span>Domicilio del contacto</span><input type="text" name="domicilio_contacto_emergencia_secundario"></label>
                </div>

                <div class="hr-modal-foot">
                    <button type="button" class="btn btn--ghost" onclick="document.getElementById('modal-hr-profile').style.display='none'">Cancelar</button>
                    <x-ui.button type="submit" style="width:auto;">Guardar</x-ui.button>
                </div>
            </form>
        </div>
    </div>

    <style>
        :root { --field-border: #c9ccd2; }
        :root[data-theme="dark"] { --field-border: var(--border); }

        .modal-overlay { position: fixed; inset: 0; background: rgba(15,17,21,0.5); backdrop-filter: blur(2px); display: flex; align-items: center; justify-content: center; z-index: 1000; padding: 24px; }

        .hr-modal { background: var(--surface); border-radius: 16px; width: 100%; max-width: 620px; max-height: 88vh; display: flex; flex-direction: column; box-shadow: 0 20px 50px rgba(0,0,0,0.25); overflow: hidden; }

        .hr-modal-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; padding: 22px 26px 18px; border-bottom: 1px solid var(--border); }
        .hr-modal-head h3 { margin: 0 0 4px; font-size: 17px; font-weight: 600; letter-spacing: -.01em; }
        .hr-modal-head p { margin: 0; font-size: 12.5px; color: var(--muted); line-height: 1.4; max-width: 440px; }
        .hr-modal-close { flex-shrink: 0; width: 30px; height: 30px; border: none; border-radius: 8px; background: transparent; color: var(--muted); cursor: pointer; display: flex; align-items: center; justify-content: center; transition: background .15s, color .15s; }
        .hr-modal-close:hover { background: var(--surface-2); color: var(--text); }
        .hr-modal-close svg { width: 16px; height: 16px; }

        .hr-modal-body { padding: 20px 26px 0; overflow-y: auto; flex: 1; }

        .hr-select { width: 100%; padding: 11px 14px; border-radius: 10px; border: 1px solid var(--field-border, var(--border)); background: var(--surface); color: var(--text); font-size: 14px; margin-bottom: 4px; }

        .hr-section { padding: 16px 0; border-bottom: 1px solid var(--border); }
        .hr-section--last { border-bottom: none; padding-bottom: 4px; }
        .hr-section-title { margin: 0 0 2px; font-size: 12px; font-weight: 600; color: var(--muted); text-transform: uppercase; letter-spacing: .04em; }
        .hr-hint { margin: 2px 0 10px; font-size: 12px; color: var(--muted); }

        .hr-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 4px 14px; }
        .hr-field { display: flex; flex-direction: column; gap: 5px; margin: 8px 0; font-size: 12.5px; color: var(--muted); }
        .hr-field--full { grid-column: 1 / -1; }
        .hr-field input { padding: 9px 12px; border-radius: 9px; border: 1px solid var(--field-border, var(--border)); background: var(--surface); color: var(--text); font-size: 13.5px; }
        .hr-field input:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(0,122,255,.12); }

        .hr-roles { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 8px; }
        .hr-chip { position: relative; }
        .hr-chip input { position: absolute; opacity: 0; width: 0; height: 0; }
        .hr-chip span { display: inline-flex; align-items: center; padding: 7px 14px; border-radius: 999px; border: 1px solid var(--field-border, var(--border)); font-size: 13px; color: var(--text); cursor: pointer; transition: background .15s, border-color .15s, color .15s; user-select: none; }
        .hr-chip input:checked + span { background: var(--primary); border-color: var(--primary); color: #fff; }

        .hr-doc { margin-bottom: 4px; }

        .hr-file-chip { display: flex; align-items: center; gap: 10px; margin: 2px 0 8px; flex-wrap: wrap; }
        .hr-file-btn { display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; border-radius: 8px; border: 1px dashed var(--field-border, var(--border)); font-size: 12px; color: var(--muted); cursor: pointer; transition: border-color .15s, color .15s; }
        .hr-file-btn:hover { border-color: var(--primary); color: var(--primary); }
        .hr-file-btn svg { width: 14px; height: 14px; flex-shrink: 0; }
        .hr-file-btn input[type="file"] { position: absolute; opacity: 0; width: 0; height: 0; }
        .hr-file-name { max-width: 140px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .hr-file-link { display: inline-flex; align-items: center; gap: 4px; font-size: 12px; color: var(--primary); text-decoration: none; }
        .hr-file-link svg { width: 13px; height: 13px; }
        .hr-file-link:hover { text-decoration: underline; }

        .hr-modal-foot { display: flex; justify-content: flex-end; gap: 10px; padding: 18px 0 22px; margin-top: 6px; }
    </style>

    <script>
        function rellenarDatosRhUsuario(select) {
            const opt = select.options[select.selectedIndex];
            const modal = document.getElementById('modal-hr-profile');
            const set = (name, value) => {
                const el = modal.querySelector('[name="' + name + '"]');
                if (el) el.value = value || '';
            };
            set('position', opt.dataset.position);
            set('cargo', opt.dataset.cargo);
            set('phone', opt.dataset.phone);
            set('payroll_number', opt.dataset.payroll);
            set('checador_id', opt.dataset.checador);
            set('curp', opt.dataset.curp);
            set('ine', opt.dataset.ine);
            set('acta_nacimiento', opt.dataset.acta);
            set('licencia', opt.dataset.licencia);
            set('domicilio', opt.dataset.domicilio);
            set('fecha_ingreso', opt.dataset.fechaIngreso);
            set('vacaciones_disponibles', opt.dataset.vacaciones);
            set('nombre_contacto_emergencia', opt.dataset.nce);
            set('numero_contacto_emergencia', opt.dataset.numce);
            set('domicilio_contacto_emergencia', opt.dataset.domce);
            set('nombre_contacto_emergencia_secundario', opt.dataset.nces);
            set('numero_contacto_emergencia_secundario', opt.dataset.numces);
            set('domicilio_contacto_emergencia_secundario', opt.dataset.domces);

            const selectedRoleIds = (opt.dataset.roles || '').split(',').filter(Boolean);
            modal.querySelectorAll('input[name="roles[]"]').forEach(cb => {
                cb.checked = selectedRoleIds.includes(cb.value);
            });

            let docs = {};
            try { docs = JSON.parse(opt.dataset.docs || '{}'); } catch (e) { docs = {}; }
            modal.querySelectorAll('.hr-file-link').forEach(link => {
                const url = docs[link.dataset.doc];
                if (url) {
                    link.href = url;
                    link.style.display = 'inline-flex';
                } else {
                    link.href = '#';
                    link.style.display = 'none';
                }
            });
        }

        document.getElementById('modal-hr-profile').addEventListener('click', function (e) {
            if (e.target === this) this.style.display = 'none';
        });

        document.querySelectorAll('.hr-file-input').forEach(function (input) {
            input.addEventListener('change', function () {
                const label = input.closest('.hr-file-chip').querySelector('.hr-file-name');
                label.textContent = input.files.length ? input.files[0].name : 'Subir documento';
            });
        });
    </script>

    @if($users->isEmpty())
        <x-ui.card>
            <div class="uc-empty">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><path d="M8 12h8"/></svg>
                <p>No se encontraron usuarios con los filtros aplicados.</p>
            </div>
        </x-ui.card>
    @else
    <div class="uc-grid" id="ucGrid">
        @foreach($users as $u)
            @php
                $si = $statusInfo($u);
                $init = $initials($u->name);
                $firstLetter = strtoupper($u->name[0] ?? 'A');
                $colors = $avatarColors[$firstLetter] ?? ['#e6f0ff', '#007aff'];
            @endphp
            <div class="uc-card">
                <div class="uc-card-top">
                    <div class="uc-avatar-wrap">
                        <div class="uc-avatar">
                            @if($u->avatar)
                                <img src="{{ $u->avatar }}" alt="{{ $u->name }}">
                            @else
                                <span style="background:{{ $colors[0] }}; color:{{ $colors[1] }}; width:100%; height:100%; display:flex; align-items:center; justify-content:center;">{{ $init }}</span>
                            @endif
                        </div>
                        <span class="uc-status-dot {{ $si['dot'] }}"></span>
                    </div>
                    <div class="uc-info">
                        <h3 class="uc-name"><a href="{{ route('admin.users.show', $u) }}" class="link" style="text-decoration:none;color:var(--text);">{{ $u->name }}</a></h3>
                        <p class="uc-role">{{ $u->position ?: ($u->is_admin ? 'Administrador' : 'Usuario') }}</p>
                        <span class="uc-status-badge {{ $si['dot'] === 'green' ? 'active' : ($si['dot'] === 'yellow' ? 'leave' : 'banned') }}">
                            <span class="dot {{ $si['dot'] }}"></span>
                            {{ $si['label'] }}
                        </span>
                    </div>
                </div>

                <div class="uc-contact">
                    @if($u->payroll_number)
                    <div class="uc-contact-row">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>
                        <span>Nómina: {{ $u->payroll_number }}</span>
                    </div>
                    @endif
                    <div class="uc-contact-row">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                        <span>{{ $u->phone ?: $u->email }}</span>
                    </div>
                </div>

                <button class="uc-dots" onclick="event.stopPropagation();toggleDotsMenu(this)" title="Acciones rápidas">
                    <svg viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="5" r="1.8"/><circle cx="12" cy="12" r="1.8"/><circle cx="12" cy="19" r="1.8"/></svg>
                </button>
                <div class="uc-dots-menu">
                    <a href="{{ route('admin.users.show', $u) }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        Ver detalle
                    </a>
                    <a href="{{ route('admin.users.permissions', $u) }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                        Permisos
                    </a>
                    @if($u->isPending())
                        <form method="POST" action="{{ route('admin.users.approve', $u) }}">
                            @csrf
                            <button type="submit" class="ok">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6L9 17l-5-5"/></svg>
                                Aprobar acceso
                            </button>
                        </form>
                    @endif
                    @if($u->isBanned())
                        <form method="POST" action="{{ route('admin.users.unban', $u) }}">
                            @csrf
                            <button type="submit" class="ok">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>
                                Reactivar
                            </button>
                        </form>
                    @elseif(! $u->is_admin)
                        <form method="POST" action="{{ route('admin.users.ban', $u) }}">
                            @csrf
                            <button type="submit" class="danger">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M4.93 4.93l14.14 14.14"/></svg>
                                Banear
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
    @endif

    <script>
        // Toggle dots menu
        function toggleDotsMenu(btn) {
            const menu = btn.nextElementSibling;
            const isOpen = menu.classList.contains('open');
            document.querySelectorAll('.uc-dots-menu.open').forEach(m => m.classList.remove('open'));
            if (!isOpen) menu.classList.add('open');
        }
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.uc-dots') && !e.target.closest('.uc-dots-menu')) {
                document.querySelectorAll('.uc-dots-menu.open').forEach(m => m.classList.remove('open'));
            }
        });

        // Toggle grid/list view
        document.querySelectorAll('.uc-view-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.uc-view-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                const grid = document.getElementById('ucGrid');
                if (this.dataset.view === 'list') {
                    grid.classList.add('uc-list-view');
                } else {
                    grid.classList.remove('uc-list-view');
                }
                localStorage.setItem('uc-view', this.dataset.view);
            });
        });
        // Restore view preference
        (function() {
            const saved = localStorage.getItem('uc-view');
            if (saved === 'list') {
                document.querySelector('.uc-view-btn[data-view="list"]')?.click();
            }
        })();
    </script>
</div>
@endsection
