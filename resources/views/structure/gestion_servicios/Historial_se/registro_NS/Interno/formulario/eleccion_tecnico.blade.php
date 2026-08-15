@extends('structure.gestion_servicios.layout')

@section('title', 'Asignar técnico')

@section('service_content')
    @include('structure.gestion_servicios.Historial_se.registro_ns.Interno.interno_estilos_base')
    <style>
        .ns-page { max-width: 1000px; }
        .ns-section-title { margin-bottom: 4px; }
        .ns-technician-search {
            width: 100%; margin-bottom: 14px; padding: 10px 14px; border-radius: 12px;
            border: 1px solid rgba(255,255,255,0.12); background: rgba(8,18,40,0.55);
            color: #fff; font-size: 14px;
        }
        :root[data-theme="light"] .ns-technician-search { background: #fff; color: var(--text); border-color: rgba(15,23,42,0.14); }
        .ns-technician-list { max-height: 320px; overflow-y: auto; padding-right: 4px; }
        .ns-technician-card.hidden { display: none; }
        .ns-active-list { max-height: 300px; overflow-y: auto; padding-right: 4px; }
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
                    <h2>Tecnicos</h2>
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

                    <input type="text" id="techSearch" class="ns-technician-search" placeholder="Buscar tecnico por nombre, puesto o telefono">

                    @if ($roleFallback)
                        <div class="ns-alert visible" style="margin-bottom: 14px;">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                            No se encontraron usuarios con rol <strong>tecnico</strong>. Se muestran todos los empleados aprobados.
                        </div>
                    @endif

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

        function normalizeText(text) {
            return (text || '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
        }

        document.getElementById('techSearch').addEventListener('input', function(e) {
            const term = normalizeText(e.target.value);
            document.querySelectorAll('.ns-technician-card').forEach(card => {
                const name = normalizeText(card.dataset.name);
                const info = card.querySelector('.ns-technician-info p');
                const extra = info ? normalizeText(info.textContent) : '';
                card.classList.toggle('hidden', !(name.includes(term) || extra.includes(term)));
            });
        });
    </script>
@endsection
