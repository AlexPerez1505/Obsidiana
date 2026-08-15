@extends('structure.gestion_servicios.layout')

@section('title', 'Nuevo servicio interno')

@section('service_content')
    <style>
        .ns-page { max-width: 900px; margin: 0 auto; }

        .ns-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
            margin-bottom: 26px;
        }
        .ns-header-title { display: flex; align-items: center; gap: 14px; }
        .ns-icon {
            width: 48px; height: 48px; border-radius: 12px;
            background: rgba(0,122,255,0.12); color: #007AFF;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }
        .ns-icon svg { width: 24px; height: 24px; }
        .ns-header-title h2 { margin: 0; font-size: 22px; color: #fff; }
        :root[data-theme="light"] .ns-header-title h2 { color: var(--text); }
        .ns-header-title p { margin: 4px 0 0; color: rgba(255,255,255,0.55); font-size: 13px; }
        :root[data-theme="light"] .ns-header-title p { color: var(--muted); }
        .ns-header-actions { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
        .ns-btn {
            display: inline-flex; align-items: center; justify-content: center; gap: 6px;
            padding: 10px 16px; border-radius: 10px; font-size: 13px; font-weight: 700;
            text-decoration: none; cursor: pointer; transition: all .16s ease;
        }
        .ns-btn svg { width: 16px; height: 16px; }
        .ns-btn--ghost { border: 1px solid rgba(255,255,255,0.12); background: transparent; color: #007AFF; }
        :root[data-theme="light"] .ns-btn--ghost { border-color: rgba(15,23,42,0.14); color: #007AFF; }
        .ns-btn--ghost:hover { border-color: #007AFF; background: rgba(0,122,255,0.08); }
        .ns-btn--primary { border: none; background: #007AFF; color: #fff; box-shadow: 0 0 14px rgba(0,122,255,0.35); }
        .ns-btn--primary:hover { background: #005FCC; box-shadow: 0 0 20px rgba(0,122,255,0.5); }
        .ns-btn--primary:disabled { opacity: 0.55; cursor: not-allowed; }

        .ns-stepper {
            display: flex; align-items: center; gap: 12px;
            margin-bottom: 28px; padding-bottom: 20px;
            border-bottom: 1px solid rgba(255,255,255,0.08);
        }
        :root[data-theme="light"] .ns-stepper { border-color: rgba(15,23,42,0.08); }
        .ns-step {
            display: flex; align-items: center; gap: 10px;
            font-size: 14px; font-weight: 700;
            color: rgba(255,255,255,0.45);
        }
        :root[data-theme="light"] .ns-step { color: var(--muted); }
        .ns-step-number {
            width: 28px; height: 28px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 12px; font-weight: 800;
            background: rgba(255,255,255,0.08); color: rgba(255,255,255,0.55);
        }
        :root[data-theme="light"] .ns-step-number { background: rgba(15,23,42,0.08); color: var(--muted); }
        .ns-step.active { color: #fff; }
        :root[data-theme="light"] .ns-step.active { color: var(--text); }
        .ns-step.active .ns-step-number { background: #007AFF; color: #fff; }
        .ns-step-line {
            flex: 1; height: 1px; min-width: 30px;
            background: rgba(255,255,255,0.08);
        }
        :root[data-theme="light"] .ns-step-line { background: rgba(15,23,42,0.08); }

        .ns-tabs {
            display: flex; gap: 22px;
            margin-bottom: 24px;
            border-bottom: 1px solid rgba(255,255,255,0.08);
        }
        :root[data-theme="light"] .ns-tabs { border-color: rgba(15,23,42,0.08); }
        .ns-tab {
            padding: 12px 4px; font-size: 14px; font-weight: 700;
            color: rgba(255,255,255,0.55); cursor: pointer; border-bottom: 2px solid transparent;
            display: flex; align-items: center; gap: 8px;
            margin-bottom: -1px;
        }
        :root[data-theme="light"] .ns-tab { color: var(--muted); }
        .ns-tab.active { color: #007AFF; border-bottom-color: #007AFF; }
        .ns-tab svg { width: 18px; height: 18px; }

        .ns-search {
            position: relative; margin-bottom: 22px;
        }
        .ns-search svg {
            position: absolute; left: 14px; top: 50%; transform: translateY(-50%);
            width: 18px; height: 18px; color: rgba(255,255,255,0.4);
        }
        :root[data-theme="light"] .ns-search svg { color: var(--muted); }
        .ns-search input {
            width: 100%; padding: 12px 14px 12px 42px;
            border: 1px solid rgba(255,255,255,0.12); border-radius: 12px;
            background: rgba(8,18,40,0.55); color: #fff; font-size: 14px; outline: none;
        }
        :root[data-theme="light"] .ns-search input { background: #fff; color: var(--text); border-color: rgba(15,23,42,0.14); }
        .ns-search input::placeholder { color: rgba(255,255,255,0.4); }
        :root[data-theme="light"] .ns-search input::placeholder { color: var(--muted); }

        .ns-section-title {
            text-align: center; font-size: 13px; font-weight: 700;
            color: rgba(255,255,255,0.45); margin-bottom: 18px;
        }
        :root[data-theme="light"] .ns-section-title { color: var(--muted); }

        .ns-customer-list { display: flex; flex-direction: column; gap: 14px; }
        .ns-customer-card {
            display: flex; align-items: center; justify-content: space-between; gap: 14px;
            padding: 16px; border-radius: 14px;
            border: 1px solid rgba(255,255,255,0.08);
            background: rgba(8,18,40,0.45);
            transition: all .16s ease;
        }
        :root[data-theme="light"] .ns-customer-card { background: rgba(15,23,42,0.04); border-color: rgba(15,23,42,0.1); }
        .ns-customer-card:hover { border-color: rgba(0,122,255,0.35); }
        .ns-customer-main { display: flex; align-items: center; gap: 14px; }
        .ns-customer-avatar {
            width: 44px; height: 44px; border-radius: 50%;
            background: #007AFF; color: #fff;
            display: flex; align-items: center; justify-content: center;
            font-size: 14px; font-weight: 800; flex-shrink: 0;
        }
        .ns-customer-info h4 { margin: 0 0 4px; font-size: 15px; color: #fff; }
        :root[data-theme="light"] .ns-customer-info h4 { color: var(--text); }
        .ns-customer-info p { margin: 0; font-size: 12px; color: rgba(255,255,255,0.5); }
        :root[data-theme="light"] .ns-customer-info p { color: var(--muted); }
        .ns-customer-select {
            padding: 8px 16px; border-radius: 10px; border: 1px solid rgba(255,255,255,0.12);
            background: transparent; color: #007AFF; font-size: 13px; font-weight: 700; cursor: pointer;
        }
        :root[data-theme="light"] .ns-customer-select { color: #007AFF; border-color: rgba(15,23,42,0.14); }
        .ns-customer-select:hover { background: rgba(0,122,255,0.12); }
        .ns-customer-select.selected {
            background: #007AFF; color: #fff; border-color: #007AFF;
        }

        .ns-footer {
            margin-top: 24px; font-size: 13px; color: rgba(255,255,255,0.55);
            display: flex; align-items: center; gap: 6px;
        }
        :root[data-theme="light"] .ns-footer { color: var(--muted); }
        .ns-footer svg { width: 16px; height: 16px; }

        .ns-new-form { display: none; }
    </style>

    <div class="ns-page">
        <div class="ns-header">
            <div class="ns-header-title">
                <div class="ns-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                </div>
                <div>
                    <h2>Nuevo servicio interno</h2>
                    <p>Crea un nuevo servicio de mantenimiento interno</p>
                </div>
            </div>
            <div class="ns-header-actions">
                <a href="{{ route('gestion.servicios.historial') }}" class="ns-btn ns-btn--ghost">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    Cancelar e iniciar
                </a>
                <a href="{{ route('gestion.servicios.nuevo') }}" class="ns-btn ns-btn--ghost">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
                    Regresar
                </a>
                <button type="button" class="ns-btn ns-btn--primary" id="nextBtn" onclick="goToEquipment()" disabled>
                    Siguiente: Equipo
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
                </button>
            </div>
        </div>

        <div class="ns-stepper">
            <div class="ns-step active">
                <div class="ns-step-number">1</div>
                <span>Cliente</span>
            </div>
            <div class="ns-step-line"></div>
            <div class="ns-step">
                <div class="ns-step-number">2</div>
                <span>Equipo</span>
            </div>
            <div class="ns-step-line"></div>
            <div class="ns-step">
                <div class="ns-step-number">3</div>
                <span>Tecnico</span>
            </div>
        </div>

        <div class="catalog-card service-section">
            <div class="ns-tabs">
                <div class="ns-tab active" onclick="switchTab('search')" id="tab-search">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    Buscar cliente existente
                </div>
                <div class="ns-tab" onclick="switchTab('new')" id="tab-new">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/><line x1="17" y1="7" x2="17" y2="7" stroke-width="3"/></svg>
                    Registrar nuevo cliente
                </div>
            </div>

            <div id="panel-search">
                <form method="GET" action="{{ route('gestion.servicios.nuevo.interno') }}" class="ns-search">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <input type="text" name="search" value="{{ $search ?? '' }}" placeholder="Buscar por nombre, telefono o correo" autocomplete="off">
                </form>

                <div class="ns-section-title">Resultados encontrados</div>

                <div class="ns-customer-list">
                    @forelse ($customers as $customer)
                        @php
                            $fullName = trim($customer->nombre . ' ' . ($customer->apellido ?? ''));
                            $initials = collect([$customer->nombre, $customer->apellido])
                                ->filter()
                                ->map(fn($n) => mb_substr($n, 0, 1))
                                ->implode('');
                            $phone = $customer->telefono ?: 'Sin teléfono';
                            $email = $customer->gmail ?: 'Sin correo';
                        @endphp
                        <div class="ns-customer-card" data-id="{{ $customer->id }}">
                            <div class="ns-customer-main">
                                <div class="ns-customer-avatar">{{ $initials ?: 'C' }}</div>
                                <div class="ns-customer-info">
                                    <h4>{{ $fullName }}</h4>
                                    <p>{{ $phone }} &nbsp;|&nbsp; {{ $email }}</p>
                                </div>
                            </div>
                            <button type="button" class="ns-customer-select" onclick="selectCustomer({{ $customer->id }})" data-id="{{ $customer->id }}">
                                Seleccionar
                            </button>
                        </div>
                    @empty
                        <div class="ns-section-title">No se encontraron clientes.</div>
                    @endforelse
                </div>

                <div class="ns-footer">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                    ¿No encuentras al cliente? Cambiate a "Registrar nuevo cliente" para agregar un nuevo cliente al sistema.
                </div>
            </div>

            <div id="panel-new" class="ns-new-form">
                <div class="ns-section-title">Formulario de registro de nuevo cliente</div>
                <p class="page-subtitle" style="text-align: center;">Aquí irá el formulario para registrar un nuevo cliente.</p>
            </div>
        </div>
    </div>

    <script>
        let selectedCustomerId = null;

        function switchTab(tab) {
            document.getElementById('tab-search').classList.toggle('active', tab === 'search');
            document.getElementById('tab-new').classList.toggle('active', tab === 'new');
            document.getElementById('panel-search').style.display = tab === 'search' ? 'block' : 'none';
            document.getElementById('panel-new').style.display = tab === 'new' ? 'block' : 'none';
        }

        function selectCustomer(id) {
            selectedCustomerId = id;
            document.querySelectorAll('.ns-customer-select').forEach(btn => {
                if (parseInt(btn.dataset.id) === id) {
                    btn.classList.add('selected');
                    btn.textContent = 'Seleccionado';
                } else {
                    btn.classList.remove('selected');
                    btn.textContent = 'Seleccionar';
                }
            });
            document.getElementById('nextBtn').disabled = false;
        }

        function goToEquipment() {
            if (!selectedCustomerId) return;
            window.location.href = "{{ route('gestion.servicios.nuevo.interno.equipo') }}?customer_id=" + selectedCustomerId;
        }
    </script>
@endsection
