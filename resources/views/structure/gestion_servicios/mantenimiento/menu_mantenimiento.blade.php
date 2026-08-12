@extends('structure.gestion_servicios.layout')

@section('title', 'Órdenes de Servicio')

@section('service_content')
    <style>
        .service-page-header {
            display: flex; align-items: center; justify-content: space-between; gap: 16px;
            flex-wrap: wrap; margin-bottom: 24px;
        }
        .service-page-header .title-group h2 {
            margin: 0; font-size: 28px; color: #fff;
        }
        :root[data-theme="light"] .service-page-header .title-group h2 { color: var(--text); }
        .service-page-header .title-group p {
            margin: 4px 0 0; color: rgba(255,255,255,0.55); font-size: 14px;
        }
        :root[data-theme="light"] .service-page-header .title-group p { color: var(--muted); }

        .actions-menu { position: relative; display: inline-block; }
        .actions-menu-trigger {
            width: 34px; height: 34px; border-radius: 8px; border: 1px solid rgba(34,197,94,0.35);
            background: rgba(34,197,94,0.08); color: #22C55E; cursor: pointer;
            display: flex; align-items: center; justify-content: center;
        }
        .actions-menu-trigger svg { width: 18px; height: 18px; }
        .actions-menu-dropdown {
            position: absolute; right: 0; top: calc(100% + 4px); z-index: 50;
            min-width: 220px; border-radius: 12px; overflow: hidden;
            background: #0b1a35; border: 1px solid rgba(34,197,94,0.35);
            box-shadow: 0 8px 24px rgba(0,0,0,0.5); padding: 4px;
            display: none; flex-direction: column; gap: 2px;
        }
        :root[data-theme="light"] .actions-menu-dropdown {
            background: #fff; border-color: rgba(15,23,42,0.14);
            box-shadow: 0 8px 24px rgba(15,23,42,0.18);
        }
        .actions-menu.open .actions-menu-dropdown { display: flex; }
        .actions-menu-item {
            display: flex; align-items: center; gap: 10px; width: 100%; padding: 9px 11px;
            border: none; border-radius: 8px; background: transparent; color: #fff;
            font-size: 13px; cursor: pointer; text-align: left; transition: background .12s ease;
        }
        :root[data-theme="light"] .actions-menu-item { color: #0f172a; }
        .actions-menu-item:hover { background: rgba(34,197,94,0.14); }
        :root[data-theme="light"] .actions-menu-item:hover { background: rgba(15,23,42,0.06); }
        .actions-menu-item svg { width: 16px; height: 16px; flex: 0 0 auto; }
        .actions-menu-item.danger { color: #ff4a4a; }
        .actions-menu-item.danger:hover { background: rgba(255,74,74,0.14); }

        .modal-overlay {
            display: none; position: fixed; inset: 0; z-index: 1000;
            background: rgba(0,0,0,0.7); align-items: center; justify-content: center; padding: 24px;
        }
        .modal-overlay.open { display: flex; }
        .modal-box {
            background: #0b1a35; border: 1px solid rgba(34,197,94,0.55); border-radius: 18px;
            width: 100%; max-width: 520px; padding: 0; overflow: hidden;
            box-shadow: 0 12px 40px rgba(0,0,0,0.5);
        }
        :root[data-theme="light"] .modal-box { background: #fff; border-color: rgba(15,23,42,0.14); }
        .modal-header {
            display: flex; align-items: center; justify-content: space-between;
            padding: 18px 22px; border-bottom: 1px solid rgba(34,197,94,0.25);
        }
        .modal-header h3 { margin: 0; font-size: 18px; color: #fff; }
        :root[data-theme="light"] .modal-header h3 { color: var(--text); }
        .modal-close {
            width: 32px; height: 32px; border-radius: 8px; border: none;
            background: rgba(255,255,255,0.08); color: #fff; cursor: pointer;
            display: flex; align-items: center; justify-content: center;
        }
        :root[data-theme="light"] .modal-close { background: rgba(15,23,42,0.08); color: var(--text); }
        .modal-body { padding: 22px; }
        .modal-service { font-size: 22px; font-weight: 800; color: #fff; margin: 0 0 4px; }
        :root[data-theme="light"] .modal-service { color: var(--text); }
        .modal-step { color: rgba(255,255,255,0.6); font-size: 14px; margin-bottom: 20px; }
        :root[data-theme="light"] .modal-step { color: var(--muted); }
        .modal-table { width: 100%; border-collapse: collapse; font-size: 14px; }
        .modal-table th, .modal-table td { text-align: left; padding: 12px 0; border-bottom: 1px solid rgba(255,255,255,0.08); color: #fff; }
        :root[data-theme="light"] .modal-table th, :root[data-theme="light"] .modal-table td { border-color: rgba(15,23,42,0.08); color: var(--text); }
        .modal-table th { color: rgba(255,255,255,0.55); font-weight: 600; width: 120px; }
        :root[data-theme="light"] .modal-table th { color: var(--muted); }
        .modal-alert { padding: 12px 14px; border-radius: 10px; font-size: 14px; margin-bottom: 20px; display: none; }
        .modal-alert.ok { display: block; background: rgba(34,197,94,0.14); color: #22C55E; border: 1px solid rgba(34,197,94,0.4); }
        .modal-alert.err { display: block; background: rgba(255,74,74,0.14); color: #ff4a4a; border: 1px solid rgba(255,74,74,0.4); }
        .modal-footer { padding: 18px 22px; border-top: 1px solid rgba(34,197,94,0.25); text-align: right; }
        .modal-btn {
            padding: 10px 18px; border-radius: 10px; border: none; font-size: 14px; font-weight: 700;
            cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;
        }
        .modal-btn--primary { background: #22C55E; color: #fff; }
        .modal-btn--ghost { background: transparent; border: 1px solid rgba(255,255,255,0.2); color: #fff; margin-right: 10px; }
        :root[data-theme="light"] .modal-btn--ghost { border-color: rgba(15,23,42,0.2); color: var(--text); }
        .modal-status { display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 700; background: rgba(34,197,94,0.14); color: #22C55E; border: 1px solid rgba(34,197,94,0.4); }
    </style>

    @if (session('success'))
        <div class="catalog-card" style="margin-bottom: 20px; color: #22C55E;">
            {{ session('success') }}
        </div>
    @endif
    @if (session('error'))
        <div class="catalog-card" style="margin-bottom: 20px; color: #ff4a4a;">
            {{ session('error') }}
        </div>
    @endif

    <div class="catalog-card service-section">
        <div class="service-page-header">
            <div class="title-group">
                <h2>Órdenes de Servicio aprobadas</h2>
                <p>Servicios que ya fueron aprobados y están listos para mantenimiento.</p>
            </div>
        </div>

        <div class="service-table-wrap">
            <table class="service-table">
                <thead>
                    <tr>
                        <th>OS</th>
                        <th>CLIENTE</th>
                        <th>TÉCNICO</th>
                        <th>EQUIPO</th>
                        <th>ESTADO</th>
                        <th>ACCIONES</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($services as $service)
                        @php
                            $customerName = trim(($service->customer->nombre ?? '') . ' ' . ($service->customer->apellido ?? ''));
                            $techName = trim(($service->externalTechnician->nombre ?? '') . ' ' . ($service->externalTechnician->apellidos ?? ''));
                            $equipment = $service->serviceEquipment;
                            $osNumber = preg_replace('/^NS-/', 'OS-', $service->service_number ?? '');
                            $menuId = 'menu-' . $service->id;

                            $currentSlug = $service->currentStep->slug ?? null;
                            $stepActionLabel = match ($currentSlug) {
                                'entrada-equipo' => 'Registrar entrada de equipo',
                                'salida-tecnico-externo' => 'Salida hacia técnico externo',
                                'notificacion-llegada-tecnico' => 'Validar llegada del técnico',
                                'llenado-mantenimiento' => 'Llenar mantenimiento',
                                default => 'Completar: ' . ($service->currentStep->name ?? 'paso actual'),
                            };
                        @endphp
                        <tr>
                            <td>{{ $osNumber ?: ('OS-' . $service->id) }}</td>
                            <td>{{ $customerName ?: 'N/A' }}</td>
                            <td>{{ $techName ?: 'N/A' }}</td>
                            <td>{{ $equipment->type_text ?? 'N/A' }} {{ $equipment->brand_text ?? '' }}</td>
                            <td>
                                <span class="service-badge active">
                                    {{ $service->currentStep->name ?? 'Aprobado' }}
                                </span>
                            </td>
                            <td>
                                <div class="actions-menu" id="{{ $menuId }}">
                                    <button type="button" class="actions-menu-trigger" onclick="toggleMenu('{{ $menuId }}')">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="6" r="1.5" fill="currentColor"/><circle cx="12" cy="12" r="1.5" fill="currentColor"/><circle cx="12" cy="18" r="1.5" fill="currentColor"/></svg>
                                    </button>
                                    <div class="actions-menu-dropdown">
                                        <button type="button" class="actions-menu-item" onclick="alert('Edición en construcción')">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                            Editar
                                        </button>

                                        <form action="{{ route('gestion.servicios.destroy', $service) }}" method="POST" onsubmit="return confirm('¿Eliminar esta orden de servicio?');" style="display:contents;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="actions-menu-item danger">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                                Eliminar
                                            </button>
                                        </form>

                                        @if ($service->currentStep)
                                            <button type="button" class="actions-menu-item complete-step-btn"
                                                    data-url="{{ route('gestion.servicios.completeStep', $service) }}"
                                                    data-service-number="{{ $service->service_number }}"
                                                    data-step-name="{{ $service->currentStep->name }}"
                                                    data-customer="{{ $customerName ?: 'N/A' }}"
                                                    data-tech="{{ $techName ?: 'N/A' }}"
                                                    data-equipment="{{ ($equipment->type_text ?? 'N/A') . ' ' . ($equipment->brand_text ?? '') }}"
                                                    data-label="{{ $stepActionLabel }}"
                                                    onclick="openCompleteModal(this)">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="12" y1="18" x2="12" y2="12"/><line x1="9" y1="15" x2="15" y2="15"/></svg>
                                                {{ $stepActionLabel }}
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="service-table-empty">No hay órdenes de servicio aprobadas.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div id="completeModal" class="modal-overlay" onclick="closeCompleteModalFromOverlay(event)">
        <div class="modal-box" onclick="event.stopPropagation()">
            <div class="modal-header">
                <h3>Confirmar paso</h3>
                <button type="button" class="modal-close" onclick="closeCompleteModal()">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
            <div class="modal-body" id="modalBody">
                <div class="modal-service" id="modalServiceNumber"></div>
                <div class="modal-step" id="modalStepName"></div>

                <div class="modal-alert" id="modalAlert"></div>

                <table class="modal-table" id="modalInfoTable">
                    <tr>
                        <th>CLIENTE</th>
                        <td id="modalCustomer"></td>
                    </tr>
                    <tr>
                        <th>TÉCNICO</th>
                        <td id="modalTech"></td>
                    </tr>
                    <tr>
                        <th>EQUIPO</th>
                        <td id="modalEquipment"></td>
                    </tr>
                    <tr>
                        <th>ESTADO</th>
                        <td id="modalStatus"><span class="modal-status">Pendiente</span></td>
                    </tr>
                </table>
            </div>
            <div class="modal-footer" id="modalFooter">
                <button type="button" class="modal-btn modal-btn--ghost" onclick="closeCompleteModal()">Cancelar</button>
                <button type="button" class="modal-btn modal-btn--primary" id="modalConfirmBtn" onclick="submitCompleteStep()">Completar paso</button>
            </div>
        </div>
    </div>

    <script>
        let currentCompleteUrl = null;

        function toggleMenu(menuId) {
            const menu = document.getElementById(menuId);
            const wasOpen = menu.classList.contains('open');
            document.querySelectorAll('.actions-menu').forEach(m => m.classList.remove('open'));
            if (! wasOpen) menu.classList.add('open');
        }

        document.addEventListener('click', function (event) {
            if (! event.target.closest('.actions-menu')) {
                document.querySelectorAll('.actions-menu').forEach(m => m.classList.remove('open'));
            }
        });

        function openCompleteModal(button) {
            document.querySelectorAll('.actions-menu').forEach(m => m.classList.remove('open'));

            currentCompleteUrl = button.dataset.url;
            document.getElementById('modalServiceNumber').textContent = button.dataset.serviceNumber || 'Servicio';
            document.getElementById('modalStepName').textContent = button.dataset.stepName || 'Paso actual';
            document.getElementById('modalCustomer').textContent = button.dataset.customer;
            document.getElementById('modalTech').textContent = button.dataset.tech;
            document.getElementById('modalEquipment').textContent = button.dataset.equipment;

            document.getElementById('modalAlert').className = 'modal-alert';
            document.getElementById('modalAlert').textContent = '';
            document.getElementById('modalInfoTable').style.display = 'table';
            document.getElementById('modalFooter').style.display = 'block';
            document.getElementById('modalConfirmBtn').textContent = button.dataset.label || 'Completar paso';
            document.getElementById('modalConfirmBtn').disabled = false;

            document.getElementById('completeModal').classList.add('open');
        }

        function closeCompleteModal() {
            document.getElementById('completeModal').classList.remove('open');
        }

        function closeCompleteModalFromOverlay(event) {
            if (event.target === document.getElementById('completeModal')) {
                closeCompleteModal();
            }
        }

        async function submitCompleteStep() {
            const btn = document.getElementById('modalConfirmBtn');
            btn.disabled = true;
            btn.textContent = 'Procesando...';

            try {
                const response = await fetch(currentCompleteUrl, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    document.getElementById('modalAlert').className = 'modal-alert ok';
                    document.getElementById('modalAlert').textContent = data.message;
                    document.getElementById('modalInfoTable').style.display = 'none';
                    document.getElementById('modalFooter').style.display = 'none';
                } else {
                    document.getElementById('modalAlert').className = 'modal-alert err';
                    document.getElementById('modalAlert').textContent = data.message || 'Error al completar el paso.';
                    btn.disabled = false;
                    btn.textContent = 'Reintentar';
                }
            } catch (e) {
                document.getElementById('modalAlert').className = 'modal-alert err';
                document.getElementById('modalAlert').textContent = 'Error de conexión.';
                btn.disabled = false;
                btn.textContent = 'Reintentar';
            }
        }
    </script>
@endsection
