@extends('structure.gestion_servicios.layout')

@section('title', 'Asignar técnico')

@section('service_content')
    <style>
        .ns-page { max-width: 1000px; margin: 0 auto; }

        .ns-header {
            display: flex; align-items: center; justify-content: space-between; gap: 16px;
            flex-wrap: wrap; margin-bottom: 22px;
        }
        .ns-header-title { display: flex; align-items: center; gap: 14px; }
        .ns-icon {
            width: 48px; height: 48px; border-radius: 12px;
            background: rgba(0,122,255,0.12); color: #007AFF;
            display: flex; align-items: center; justify-content: center; flex-shrink: 0;
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
            margin-bottom: 24px; padding-bottom: 20px;
            border-bottom: 1px solid rgba(255,255,255,0.08);
        }
        :root[data-theme="light"] .ns-stepper { border-color: rgba(15,23,42,0.08); }
        .ns-step { display: flex; align-items: center; gap: 10px; font-size: 14px; font-weight: 700; color: rgba(255,255,255,0.45); }
        :root[data-theme="light"] .ns-step { color: var(--muted); }
        .ns-step-number {
            width: 28px; height: 28px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 12px; font-weight: 800;
            background: rgba(255,255,255,0.08); color: rgba(255,255,255,0.55);
        }
        :root[data-theme="light"] .ns-step-number { background: rgba(15,23,42,0.08); color: var(--muted); }
        .ns-step.completed { color: #22C55E; }
        :root[data-theme="light"] .ns-step.completed { color: #16A34A; }
        .ns-step.completed .ns-step-number { background: #22C55E; color: #fff; }
        .ns-step.active { color: #fff; }
        :root[data-theme="light"] .ns-step.active { color: var(--text); }
        .ns-step.active .ns-step-number { background: #007AFF; color: #fff; }
        .ns-step-line { flex: 1; height: 1px; min-width: 30px; background: rgba(255,255,255,0.08); }
        :root[data-theme="light"] .ns-step-line { background: rgba(15,23,42,0.08); }

        .ns-alert {
            display: none; align-items: center; gap: 10px;
            background: rgba(239,68,68,0.12); color: #F87171;
            border: 1px solid rgba(239,68,68,0.25); border-radius: 12px;
            padding: 12px 16px; font-size: 13px; font-weight: 600;
            margin-bottom: 22px;
        }
        .ns-alert svg { width: 18px; height: 18px; flex-shrink: 0; }
        .ns-alert.visible { display: flex; }

        .ns-customer-summary {
            display: flex; align-items: center; justify-content: space-between; gap: 14px;
            margin-bottom: 22px;
        }
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
        .ns-registrar {
            display: flex; align-items: center; gap: 8px;
            font-size: 13px; color: rgba(255,255,255,0.6);
        }
        :root[data-theme="light"] .ns-registrar { color: var(--muted); }
        .ns-registrar svg { width: 18px; height: 18px; }

        .ns-columns {
            display: grid; grid-template-columns: 1fr 1fr; gap: 22px;
            align-items: start;
        }
        @media (max-width: 860px) { .ns-columns { grid-template-columns: 1fr; } }

        .ns-section-title {
            display: flex; align-items: center; gap: 10px;
            font-size: 16px; font-weight: 800; color: #fff;
            margin-bottom: 4px;
        }
        :root[data-theme="light"] .ns-section-title { color: var(--text); }
        .ns-section-title svg { width: 20px; height: 20px; color: #007AFF; }
        .ns-section-subtitle { margin: 0 0 18px; font-size: 13px; color: rgba(255,255,255,0.55); }
        :root[data-theme="light"] .ns-section-subtitle { color: var(--muted); }

        .ns-technician-list { display: flex; flex-direction: column; gap: 12px; }
        .ns-technician-card {
            display: flex; align-items: center; justify-content: space-between; gap: 14px;
            padding: 14px; border-radius: 14px;
            border: 1px solid rgba(255,255,255,0.08);
            background: rgba(8,18,40,0.45);
            cursor: pointer; transition: all .16s ease;
        }
        :root[data-theme="light"] .ns-technician-card { background: rgba(15,23,42,0.04); border-color: rgba(15,23,42,0.1); }
        .ns-technician-card:hover { border-color: rgba(0,122,255,0.35); }
        .ns-technician-card.selected {
            border-color: #007AFF;
            background: rgba(0,122,255,0.12);
            box-shadow: 0 0 14px rgba(0,122,255,0.15);
        }
        .ns-technician-main { display: flex; align-items: center; gap: 12px; }
        .ns-technician-avatar {
            width: 42px; height: 42px; border-radius: 50%;
            background: #007AFF; color: #fff;
            display: flex; align-items: center; justify-content: center;
            font-size: 14px; font-weight: 800; flex-shrink: 0;
        }
        .ns-technician-info h4 { margin: 0 0 3px; font-size: 15px; color: #fff; }
        :root[data-theme="light"] .ns-technician-info h4 { color: var(--text); }
        .ns-technician-info p { margin: 0; font-size: 12px; color: rgba(255,255,255,0.55); }
        :root[data-theme="light"] .ns-technician-info p { color: var(--muted); }
        .ns-technician-badge {
            padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 800;
            white-space: nowrap;
        }
        .ns-technician-badge.green { background: rgba(34,197,94,0.18); color: #22C55E; }
        .ns-technician-badge.yellow { background: rgba(234,179,8,0.18); color: #EAB308; }
        .ns-technician-badge.red { background: rgba(239,68,68,0.18); color: #EF4444; }
        :root[data-theme="light"] .ns-technician-badge.green { background: rgba(34,197,94,0.12); }
        :root[data-theme="light"] .ns-technician-badge.yellow { background: rgba(234,179,8,0.12); }
        :root[data-theme="light"] .ns-technician-badge.red { background: rgba(239,68,68,0.12); }

        .ns-active-title { margin: 0 0 4px; font-size: 16px; font-weight: 800; color: #fff; }
        :root[data-theme="light"] .ns-active-title { color: var(--text); }
        .ns-active-subtitle { margin: 0 0 18px; font-size: 13px; color: rgba(255,255,255,0.55); }
        :root[data-theme="light"] .ns-active-subtitle { color: var(--muted); }
        .ns-active-list { display: flex; flex-direction: column; gap: 10px; margin-bottom: 24px; }
        .ns-active-list.hidden { display: none; }
        .ns-active-item {
            display: flex; align-items: center; gap: 12px;
            padding: 10px 14px; border-radius: 12px;
            background: rgba(8,18,40,0.35); border: 1px solid rgba(255,255,255,0.06);
        }
        :root[data-theme="light"] .ns-active-item { background: rgba(15,23,42,0.04); border-color: rgba(15,23,42,0.08); }
        .ns-active-dot { width: 9px; height: 9px; border-radius: 50%; background: #22C55E; flex-shrink: 0; }
        .ns-active-info { display: flex; flex-direction: column; }
        .ns-active-info strong { font-size: 14px; color: #fff; }
        :root[data-theme="light"] .ns-active-info strong { color: var(--text); }
        .ns-active-info span { font-size: 12px; color: rgba(255,255,255,0.55); }
        :root[data-theme="light"] .ns-active-info span { color: var(--muted); }

        .ns-dates { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 18px; }
        @media (max-width: 520px) { .ns-dates { grid-template-columns: 1fr; } }
        .ns-date-field { display: flex; flex-direction: column; gap: 6px; }
        .ns-date-field label { font-size: 12px; font-weight: 700; color: rgba(255,255,255,0.75); }
        :root[data-theme="light"] .ns-date-field label { color: var(--text); }
        .ns-date-inputs { display: flex; gap: 8px; }
        .ns-date-inputs input {
            width: 56px; padding: 10px; text-align: center; border-radius: 10px;
            border: 1px solid rgba(255,255,255,0.12); background: rgba(8,18,40,0.55); color: #fff; font-size: 14px;
        }
        :root[data-theme="light"] .ns-date-inputs input { background: #fff; color: var(--text); border-color: rgba(15,23,42,0.14); }
        .ns-date-inputs input.year { width: 70px; }
        .ns-date-inputs input::placeholder { color: rgba(255,255,255,0.4); }
        :root[data-theme="light"] .ns-date-inputs input::placeholder { color: var(--muted); }

        .ns-notify {
            display: flex; align-items: center; gap: 10px;
            font-size: 13px; color: rgba(255,255,255,0.75);
            cursor: pointer;
        }
        :root[data-theme="light"] .ns-notify { color: var(--text); }
        .ns-notify input { width: 18px; height: 18px; accent-color: #007AFF; cursor: pointer; }
        .ns-notify span { font-size: 12px; color: rgba(255,255,255,0.5); }
        :root[data-theme="light"] .ns-notify span { color: var(--muted); }

        .ns-modal-overlay {
            position: fixed; inset: 0; z-index: 100;
            display: none; align-items: center; justify-content: center;
            background: rgba(0,0,0,0.6); backdrop-filter: blur(4px);
        }
        .ns-modal-overlay.active { display: flex; }
        .ns-modal {
            width: 100%; max-width: 460px; margin: 18px;
            background: #0b1220; border: 1px solid rgba(255,255,255,0.1);
            border-radius: 18px; padding: 24px;
            box-shadow: 0 24px 60px rgba(0,0,0,0.4);
        }
        :root[data-theme="light"] .ns-modal { background: #fff; border-color: rgba(15,23,42,0.1); }
        .ns-modal-header {
            display: flex; align-items: center; justify-content: space-between;
            margin-bottom: 20px;
        }
        .ns-modal-title { font-size: 18px; font-weight: 800; color: #fff; }
        :root[data-theme="light"] .ns-modal-title { color: var(--text); }
        .ns-modal-close {
            background: transparent; border: none; color: rgba(255,255,255,0.55); cursor: pointer;
        }
        :root[data-theme="light"] .ns-modal-close { color: var(--muted); }
        .ns-modal-close svg { width: 22px; height: 22px; }
        .ns-modal-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 16px; }
        @media (max-width: 520px) { .ns-modal-grid { grid-template-columns: 1fr; } }
        .ns-modal-field { display: flex; flex-direction: column; gap: 6px; }
        .ns-modal-field label { font-size: 12px; font-weight: 700; color: rgba(255,255,255,0.75); }
        :root[data-theme="light"] .ns-modal-field label { color: var(--text); }
        .ns-modal-field input {
            padding: 11px 13px; border-radius: 10px;
            border: 1px solid rgba(255,255,255,0.12); background: rgba(8,18,40,0.55); color: #fff; font-size: 14px;
        }
        :root[data-theme="light"] .ns-modal-field input { background: #fff; color: var(--text); border-color: rgba(15,23,42,0.14); }
        .ns-modal-field input::placeholder { color: rgba(255,255,255,0.4); }
        :root[data-theme="light"] .ns-modal-field input::placeholder { color: var(--muted); }
        .ns-modal-footer { display: flex; justify-content: flex-end; gap: 10px; margin-top: 4px; }
    </style>

    @php
        $fullName = trim($customer->nombre . ' ' . ($customer->apellido ?? ''));
        $initials = collect([$customer->nombre, $customer->apellido])->filter()->map(fn($n) => mb_substr($n, 0, 1))->implode('');
        $registrar = auth()->user()?->name ?? 'Oliver';
    @endphp

    <div class="ns-page">
        <div class="ns-header">
            <div class="ns-header-title">
                <div class="ns-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                </div>
                <div>
                    <h2>Agenda</h2>
                    <p>Asigna un especialista al servicio programado</p>
                </div>
            </div>
            <div class="ns-header-actions">
                <a href="{{ route('gestion.servicios.nuevo.interno.equipo') }}?customer_id={{ $customer->id }}" class="ns-btn ns-btn--ghost">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
                    Regresar
                </a>
                <button type="submit" form="technicianForm" class="ns-btn ns-btn--primary">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
                    Siguiente: Cotizacion
                </button>
            </div>
        </div>

        <div class="ns-alert" id="overloadAlert">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
            Sobrecarga de activos, favor cambiar tecnico
        </div>

        <div class="ns-stepper">
            <div class="ns-step completed">
                <div class="ns-step-number">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" width="12" height="12"><polyline points="20 6 9 17 4 12"/></svg>
                </div>
                <span>Cliente</span>
            </div>
            <div class="ns-step-line"></div>
            <div class="ns-step completed">
                <div class="ns-step-number">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" width="12" height="12"><polyline points="20 6 9 17 4 12"/></svg>
                </div>
                <span>Equipo</span>
            </div>
            <div class="ns-step-line"></div>
            <div class="ns-step active">
                <div class="ns-step-number">3</div>
                <span>Tecnico</span>
            </div>
            <div class="ns-step-line"></div>
            <div class="ns-step">
                <div class="ns-step-number">4</div>
                <span>Cotizacion</span>
            </div>
        </div>

        <div class="ns-customer-summary">
            <div class="ns-customer-main">
                <div class="ns-customer-avatar">{{ $initials ?: 'C' }}</div>
                <div class="ns-customer-info">
                    <h4>{{ $fullName }}</h4>
                    <p>{{ $customer->telefono ?: 'Sin teléfono' }}</p>
                </div>
            </div>
            <div class="ns-registrar">
                Registrado por: <strong>{{ $registrar }}</strong>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            </div>
        </div>

        <form id="technicianForm" method="POST" action="{{ route('gestion.servicios.nuevo.interno.cotizacion') }}">
            @csrf
            <input type="hidden" name="customer_id" value="{{ $customer->id }}">
            <input type="hidden" name="technician_id" id="technicianId" value="">

            <div class="ns-columns">
                <div class="catalog-card service-section">
                    <div class="ns-section-title">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                        Asignar tecnico responsable
                    </div>
                    <p class="ns-section-subtitle">Selecciona alguno de los tecnicos internos disponibles</p>

                    <div class="ns-technician-list">
                        @foreach ($technicians as $tech)
                            @php
                                $techInitials = $tech->name ? mb_substr($tech->name, 0, 1) : 'T';
                                $techServices = $servicesByTech->get($tech->id) ?? collect();
                                $activeCount = $techServices->count();
                                if ($activeCount <= 3) { $badgeClass = 'green'; }
                                elseif ($activeCount <= 7) { $badgeClass = 'yellow'; }
                                else { $badgeClass = 'red'; }
                            @endphp
                            <div class="ns-technician-card" data-id="{{ $tech->id }}" data-name="{{ $tech->name }}" data-count="{{ $activeCount }}" onclick="selectTechnician({{ $tech->id }})">
                                <div class="ns-technician-main">
                                    <div class="ns-technician-avatar">{{ $techInitials }}</div>
                                    <div class="ns-technician-info">
                                        <h4>{{ $tech->name }}</h4>
                                        <p>{{ $tech->cargo ?: ($tech->position ?: 'Sin puesto') }} &nbsp;|&nbsp; {{ $tech->phone ?: 'Sin teléfono' }}</p>
                                    </div>
                                </div>
                                <div class="ns-technician-badge {{ $badgeClass }}">{{ $activeCount }} activos</div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="catalog-card service-section">
                    <div class="ns-active-title">Servicios activos del tecnico</div>
                    <div class="ns-active-subtitle" id="activeSubtitle">Mostrando servicios activos de <strong>—</strong></div>

                    @foreach ($technicians as $tech)
                        @php
                            $techServices = $servicesByTech->get($tech->id) ?? collect();
                        @endphp
                        <div class="ns-active-list hidden" id="tech-services-{{ $tech->id }}">
                            @forelse ($techServices as $service)
                                <div class="ns-active-item">
                                    <div class="ns-active-dot"></div>
                                    <div class="ns-active-info">
                                        <strong>{{ $service->service_number ?: 'Sin número' }}</strong>
                                        <span>{{ $service->customer?->nombre ? trim($service->customer->nombre . ' ' . ($service->customer->apellido ?? '')) : 'Cliente no registrado' }}</span>
                                    </div>
                                </div>
                            @empty
                                <div class="ns-active-item" style="color: rgba(255,255,255,0.55);">
                                    No hay servicios activos asignados.
                                </div>
                            @endforelse
                        </div>
                    @endforeach


                </div>
            </div>
        </form>
    </div>

    <script>
        const overloadThreshold = 8;
        let selectedTechnicianId = null;

        function selectTechnician(id) {
            selectedTechnicianId = id;
            document.getElementById('technicianId').value = id;

            const cards = document.querySelectorAll('.ns-technician-card');
            const selectedCard = document.querySelector(`.ns-technician-card[data-id="${id}"]`);
            const name = selectedCard ? selectedCard.dataset.name : '';
            const count = parseInt(selectedCard ? selectedCard.dataset.count : '0');

            cards.forEach(c => c.classList.remove('selected'));
            selectedCard.classList.add('selected');

            document.getElementById('activeSubtitle').innerHTML = 'Mostrando servicios activos de <strong>' + name + '</strong>';

            document.querySelectorAll('.ns-active-list').forEach(el => el.classList.add('hidden'));
            const list = document.getElementById('tech-services-' + id);
            if (list) list.classList.remove('hidden');

            const alert = document.getElementById('overloadAlert');
            if (count >= overloadThreshold) {
                alert.classList.add('visible');
            } else {
                alert.classList.remove('visible');
            }
        }

        document.getElementById('technicianForm').addEventListener('submit', function(e) {
            if (!document.getElementById('technicianId').value) {
                e.preventDefault();
                alert('Selecciona un técnico antes de guardar la orden.');
            }
        });

        // Select first technician by default
        const firstCard = document.querySelector('.ns-technician-card');
        if (firstCard) selectTechnician(firstCard.dataset.id);
    </script>
@endsection
