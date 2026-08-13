@extends('layouts.dashboard')

@section('title', 'Equipos')
@section('page-title', 'Equipos')
@section('page-sub', 'Gestion de Inventario > Equipos')

@php
    $statusTones = [
        'Activo' => 'green',
        'Bueno' => 'green',
        'Mantenimiento' => 'blue',
        'Malo' => 'red',
    ];
@endphp

@push('head')
<style>
    .equipment-search {
        display: flex;
        align-items: center;
        gap: 10px;
        margin: 12px 0 26px;
        padding: 10px 14px;
        border: 1px solid rgba(0,168,255,0.55);
        border-radius: 10px;
        background: rgba(4,10,24,0.72);
    }
    .equipment-search svg,
    .equipment-search__icon {
        color: rgba(160,174,192,0.85);
        width: 18px;
        height: 18px;
        flex: 0 0 auto;
    }
    .equipment-search input {
        flex: 1;
        background: transparent;
        border: 0;
        color: #fff;
        font: inherit;
        font-size: 15px;
        padding: 0;
        outline: none;
    }
    .equipment-search input::placeholder {
        color: rgba(255,255,255,0.45);
    }
    :root[data-theme="light"] .equipment-search {
        background: #ffffff;
    }
    :root[data-theme="light"] .equipment-search input {
        color: #1e1b4b;
    }
    :root[data-theme="light"] .equipment-search input::placeholder {
        color: rgba(30,27,75,0.45);
    }
    .equipment-state {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 70px;
        padding: 4px 10px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 700;
        white-space: nowrap;
    }
    .equipment-state.green {
        color: #34d355;
        border: 1px solid #22c943;
        background: rgba(34,201,67,0.12);
        box-shadow: 0 0 8px rgba(34,201,67,0.55), 0 0 16px rgba(34,201,67,0.25), inset 0 1px 0 rgba(255,255,255,0.08);
    }
    .equipment-state.blue {
        color: #42a5ff;
        border: 1px solid #1689ff;
        background: rgba(22,137,255,0.12);
        box-shadow: 0 0 8px rgba(22,137,255,0.55), 0 0 16px rgba(22,137,255,0.25), inset 0 1px 0 rgba(255,255,255,0.08);
    }
    .equipment-state.red {
        color: #ff6b6b;
        border: 1px solid #ff4b4b;
        background: rgba(255,75,75,0.12);
        box-shadow: 0 0 8px rgba(255,75,75,0.55), 0 0 16px rgba(255,75,75,0.25), inset 0 1px 0 rgba(255,255,255,0.08);
    }
    :root[data-theme="light"] .equipment-state.green { color: #16a329; border: 1px solid #22c943; background: #f7fff8; box-shadow: none; }
    :root[data-theme="light"] .equipment-state.blue { color: #1689ff; border: 1px solid #1689ff; background: #f5fbff; box-shadow: none; }
    :root[data-theme="light"] .equipment-state.red { color: #ff3131; border: 1px solid #ff4b4b; background: #fff8f8; box-shadow: none; }
    .equipment-action-menu { position: relative; display: inline-flex; }
    .equipment-action-list {
        position: absolute;
        right: 100%;
        top: 0;
        margin-right: 6px;
        min-width: 210px;
        padding: 6px;
        border: 1px solid var(--border);
        border-radius: 10px;
        background: var(--surface);
        box-shadow: var(--shadow);
        display: none;
        z-index: 20;
    }
    .equipment-action-menu.is-open .equipment-action-list { display: grid; gap: 3px; }
    .equipment-action-list a, .equipment-action-list button {
        display: flex; align-items: center; gap: 8px;
        width: 100%; min-height: 34px; padding: 0 10px;
        border: 0; border-radius: 7px; background: transparent;
        color: var(--text); text-decoration: none; font-size: 13px; font-weight: 700;
        text-align: left; white-space: nowrap; cursor: pointer;
    }
    .equipment-action-list a:hover, .equipment-action-list button:hover { background: #eef4ff; color: #0879d0; }
    .equipment-action-list .danger { color: #ef4444; }
    .equipment-action-list .danger:hover { background: #fff1f2; color: #dc2626; }
    .equipment-foot { display: flex; align-items: center; justify-content: space-between; padding-top: 14px; color: var(--muted); font-size: 13px; font-weight: 600; }
    .equipment-menu.card {
        padding: 20px;
        border-radius: 14px;
        background: linear-gradient(145deg, rgba(8,18,40,0.88), rgba(4,12,30,0.88));
        border: 1px solid rgba(0,168,255,0.55);
        box-shadow: 0 8px 28px rgba(0,0,0,0.35), 0 0 14px rgba(0,168,255,0.2), inset 0 1px 0 rgba(255,255,255,0.04);
    }
    .equipment-menu .btn, .equipment-menu-header .btn {
        background: linear-gradient(135deg, #00A8FF, #7C3AED);
        color: #fff;
        border: 1px solid rgba(255,255,255,0.15);
        border-radius: 12px;
        box-shadow: 0 0 12px rgba(59,130,246,0.35), 0 0 30px rgba(124,58,237,0.2);
        transition: all 0.2s ease;
    }
    .equipment-menu .btn:hover, .equipment-menu-header .btn:hover { filter: brightness(1.1); }
    .equipment-menu .btn--ghost, .equipment-menu-header .btn--ghost {
        background: rgba(8,18,40,0.45);
        color: #00A8FF;
        border: 1px solid rgba(0,168,255,0.55);
    }
    .equipment-menu .btn--ghost:hover, .equipment-menu-header .btn--ghost:hover { background: rgba(0,168,255,0.14); border-color: #00A8FF; }
    .equipment-search input,
    .equipment-search select { background: rgba(4,10,24,0.72); border-color: rgba(0,168,255,0.55); color: #fff; }
    .equipment-search input:focus,
    .equipment-search select:focus { border-color: #00A8FF; box-shadow: 0 0 0 3px rgba(0,168,255,0.18), 0 0 18px rgba(0,168,255,0.45); outline: none; }
    .equipment-search svg { color: #00A8FF; }
    .equipment-foot { color: rgba(255,255,255,0.55); }
    :root[data-theme="light"] .equipment-menu.card {
        background: #ffffff;
        border-color: rgba(0,168,255,0.55);
        box-shadow: 0 8px 28px rgba(0,168,255,0.12), 0 0 14px rgba(0,168,255,0.18), inset 0 1px 0 rgba(255,255,255,0.6);
    }
    :root[data-theme="light"] .equipment-menu .btn,
    :root[data-theme="light"] .equipment-menu-header .btn { color: #fff; }
    :root[data-theme="light"] .equipment-menu .btn--ghost,
    :root[data-theme="light"] .equipment-menu-header .btn--ghost {
        background: rgba(0,168,255,0.08);
        border-color: rgba(0,168,255,0.55);
        color: #00A8FF;
    }
    :root[data-theme="light"] .equipment-menu .btn--ghost:hover,
    :root[data-theme="light"] .equipment-menu-header .btn--ghost:hover { background: rgba(0,168,255,0.14); border-color: #00A8FF; }
    :root[data-theme="light"] .equipment-search input { background: #ffffff; color: #1e1b4b; border-color: rgba(0,168,255,0.55); }
    :root[data-theme="light"] .equipment-search input:focus { border-color: #00A8FF; box-shadow: 0 0 0 3px rgba(0,168,255,0.18), 0 0 18px rgba(0,168,255,0.35); outline: none; }
    :root[data-theme="light"] .equipment-search svg { color: #00A8FF; }
    :root[data-theme="light"] .equipment-foot {
        color: #3730a3;
        background: linear-gradient(180deg, #e0e7ff, #dbeafe);
        padding: 14px 20px;
        margin: 14px -20px -20px -20px;
        border-radius: 0 0 12px 12px;
    }
    :root[data-theme="light"] .equipment-menu .equipment-state.green { color: #16a329; background: #f7fff8; border: 1px solid #22c943; box-shadow: none; }
    :root[data-theme="light"] .equipment-menu .equipment-state.blue { color: #1689ff; background: #f5fbff; border: 1px solid #1689ff; box-shadow: none; }
    :root[data-theme="light"] .equipment-menu .equipment-state.red { color: #ff3131; background: #fff8f8; border: 1px solid #ff4b4b; box-shadow: none; }
    .equipment-menu .card { overflow: visible; }
    .equipment-action-list { z-index: 50; }
    .equipment-menu th {
        color: rgba(255,255,255,0.75);
        font-weight: 700;
        text-transform: uppercase;
        font-size: 11px;
        letter-spacing: 0.04em;
        padding: 12px 8px;
        border-bottom: 1px solid rgba(0,168,255,0.35);
    }
    :root[data-theme="light"] .equipment-menu th {
        background: linear-gradient(180deg, #e0e7ff, #dbeafe);
        color: #3730a3;
        border-bottom: 1px solid rgba(15,23,42,0.08);
    }
    .equipment-menu table { width: 100%; border-collapse: collapse; }
    :root[data-theme="light"] .equipment-menu table, :root[data-theme="light"] .equipment-menu tbody { background: #ffffff; }
    .equipment-menu td { padding: 12px 8px; border-bottom: 1px solid rgba(0,168,255,0.18); }
    :root[data-theme="light"] .equipment-menu tbody tr { background: #ffffff; }
    :root[data-theme="light"] .equipment-menu tr { border-bottom: 1px solid rgba(0,168,255,0.35); }
    :root[data-theme="light"] .equipment-menu tbody tr:hover { background: #f5f9ff; }
    .equipment-pin-modal { position: fixed; inset: 0; z-index: 1000; display: none; }
    .equipment-pin-modal__overlay { position: absolute; inset: 0; background: rgba(0,0,0,0.6); display: grid; place-items: center; padding: 20px; }
    .equipment-pin-modal__card { width: 100%; max-width: 360px; padding: 24px; border-radius: 14px; background: var(--surface); border: 1px solid rgba(0,168,255,0.55); box-shadow: 0 0 20px rgba(0,168,255,0.35); }
    .equipment-pin-modal__title { margin: 0 0 12px; color: var(--text); font-size: 1.1rem; font-weight: 800; }
    .equipment-pin-modal__text { margin: 0 0 16px; color: var(--muted); font-size: 14px; }
    .equipment-pin-modal__input { width: 100%; padding: 9px 12px; border: 1px solid rgba(0,168,255,0.55); border-radius: 10px; background: var(--surface); color: var(--text); font: inherit; font-size: 14px; box-sizing: border-box; }
    .equipment-pin-modal__actions { display: flex; gap: 12px; margin-top: 18px; }
    .equipment-pin-modal__btn { display: inline-flex; align-items: center; justify-content: center; flex: 1; padding: 10px 14px; background: linear-gradient(135deg, #00A8FF, #7C3AED); color: #fff; border: 1px solid rgba(255,255,255,0.15); border-radius: 12px; font-size: 14px; font-weight: 600; cursor: pointer; box-shadow: 0 0 12px rgba(59,130,246,0.35), 0 0 30px rgba(124,58,237,0.2); transition: all 0.2s ease; }
    .equipment-pin-modal__btn:hover { filter: brightness(1.1); }
    .equipment-pin-modal__btn--ghost { background: rgba(0,168,255,0.12); color: #00A8FF; border: 1px solid rgba(0,168,255,0.55); box-shadow: 0 0 10px rgba(0,168,255,0.15); }
    .equipment-pin-modal__btn--ghost:hover { background: rgba(0,168,255,0.22); border-color: #00A8FF; }
    :root[data-theme="light"] .equipment-pin-modal__btn--ghost { background: rgba(0,168,255,0.08); color: #00A8FF; }
</style>
@endpush

@section('content')
    <div class="equipment-menu-header" style="display:flex; justify-content:flex-end; margin-bottom:22px;">
        <a href="{{ route('inventory.equipos.create') }}" class="btn" style="text-decoration:none; display:inline-flex; align-items:center; gap:7px;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12h14"/></svg>
            Nuevo Equipo
        </a>
    </div>

    <x-ui.card class="equipment-menu">
        <form method="GET">
            <div class="equipment-search">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" class="equipment-search__icon">
                    <circle cx="11" cy="11" r="7"></circle>
                    <path d="m20 20-3.5-3.5"></path>
                </svg>
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Buscar equipo..." autocomplete="off">
            </div>

            <div style="display:grid; grid-template-columns:repeat(3, 1fr); gap:10px; margin-bottom:18px;">
                <select class="equipment-input" name="tipo" onchange="this.form.submit()">
                    <option value="">Todos los tipos</option>
                    @foreach ($tipos as $tipo)
                        <option value="{{ $tipo }}" @selected(($filters['tipo'] ?? '') === $tipo)>{{ $tipo }}</option>
                    @endforeach
                </select>
                <select class="equipment-input" name="marca" onchange="this.form.submit()">
                    <option value="">Todas las marcas</option>
                    @foreach ($marcas as $marca)
                        <option value="{{ $marca }}" @selected(($filters['marca'] ?? '') === $marca)>{{ $marca }}</option>
                    @endforeach
                </select>
                <select class="equipment-input" name="estado" onchange="this.form.submit()">
                    <option value="">Todos los estados</option>
                    @foreach ($estados as $estado)
                        <option value="{{ $estado }}" @selected(($filters['estado'] ?? '') === $estado)>{{ $estado }}</option>
                    @endforeach
                </select>
            </div>
        </form>

        <div style="overflow-x:auto;">
            <table>
                <thead>
                    <tr>
                        <th>Equipo</th>
                        <th>Estado</th>
                        <th>Serie</th>
                        <th>Fecha de adquisicion</th>
                        <th>Registrado por</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($equipmentList as $equipo)
                        <tr>
                            <td>
                                <div style="font-weight:700;">{{ $equipo->name }}</div>
                                <small style="color:var(--muted);">{{ $equipo->code }}</small>
                            </td>
                            <td><span class="equipment-state {{ $statusTones[$equipo->status] ?? 'green' }}">{{ $equipo->status ?? 'Activo' }}</span></td>
                            <td>
                                <div>{{ $equipo->serial_number ?: '—' }}</div>
                                <small style="color:var(--muted);">{{ $equipo->base_serial ?: '' }}</small>
                            </td>
                            <td>{{ $equipo->acquisition_date?->format('d/m/Y') ?? '—' }}</td>
                            <td>{{ $equipo->registered_by ?? '—' }}</td>
                            <td>
                                <div class="equipment-action-menu" data-equipment-action-menu>
                                    <button type="button" class="btn btn--ghost" style="padding:6px;" aria-haspopup="true" aria-expanded="false" data-equipment-action-toggle>
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                                            <circle cx="12" cy="5" r="1.8"></circle>
                                            <circle cx="12" cy="12" r="1.8"></circle>
                                            <circle cx="12" cy="19" r="1.8"></circle>
                                        </svg>
                                    </button>

                                    <div class="equipment-action-list" role="menu">
                                        <a href="{{ route('inventory.equipos.show', $equipo) }}" role="menuitem">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" width="15" height="15"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/></svg>
                                            Ver
                                        </a>
                                        <button type="button" class="edit-equipment-btn" data-url="{{ route('inventory.equipos.verify-edit', $equipo) }}" data-folio="{{ $equipo->folio }}" role="menuitem" style="display:flex; align-items:center; gap:8px; width:100%; padding:9px 14px; color:var(--text); background:none; border:none; cursor:pointer; font-size:13px; font-weight:600; text-align:left;">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" width="15" height="15"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5z"/></svg>
                                            Editar
                                        </button>
                                        <a href="{{ route('inventory.equipos.download', $equipo) }}" role="menuitem">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" width="15" height="15"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                                            Descargar
                                        </a>
                                        <form method="POST" action="{{ route('inventory.equipos.destroy', $equipo) }}" data-delete-form role="none">
                                            @csrf
                                            @method('DELETE')
                                            <input type="hidden" name="pin" value="">
                                            <button type="submit" class="danger" role="menuitem">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" width="15" height="15"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v5"/><path d="M14 11v5"/></svg>
                                                Eliminar
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="text-align:center; padding:32px; color:var(--muted);">
                                No hay equipos registrados. Agrega uno para gestionar el inventario.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="equipment-foot">
            <span id="equipmentCount">Mostrando {{ $equipmentList->count() ? 1 : 0 }} a {{ $equipmentList->count() }} de {{ $equipmentList->count() }} resultados</span>
            <button type="button" class="btn btn--ghost" style="font-size:13px;">Ver mas &gt;</button>
        </div>
    </x-ui.card>

    <form id="equipmentEditPinForm" method="POST" action="" style="display:none;">
        @csrf
        <input type="hidden" name="password" id="equipmentEditPinHidden">
    </form>

    <div id="equipmentPinModal" class="equipment-pin-modal">
        <div class="equipment-pin-modal__overlay">
            <div class="equipment-pin-modal__card">
                <h3 id="equipmentPinTitle" class="equipment-pin-modal__title">Confirmar eliminacion</h3>
                <p id="equipmentPinText" class="equipment-pin-modal__text">Ingrese el PIN de acceso para eliminar:</p>
                <input type="password" id="equipmentPinInput" class="equipment-pin-modal__input" placeholder="PIN" autocomplete="off">
                <div class="equipment-pin-modal__actions">
                    <button type="button" id="equipmentPinCancel" class="equipment-pin-modal__btn equipment-pin-modal__btn--ghost">Cancelar</button>
                    <button type="button" id="equipmentPinAccept" class="equipment-pin-modal__btn">Aceptar</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('click', (event) => {
            const toggle = event.target.closest('[data-equipment-action-toggle]');
            const actionButton = event.target.closest('[data-equipment-action-message]');

            if (actionButton && window.showToast) {
                window.showToast(actionButton.dataset.equipmentActionMessage);
            }

            document.querySelectorAll('[data-equipment-action-menu]').forEach((menu) => {
                if (!toggle || menu !== toggle.closest('[data-equipment-action-menu]')) {
                    menu.classList.remove('is-open');
                    const button = menu.querySelector('[data-equipment-action-toggle]');
                    if (button) button.setAttribute('aria-expanded', 'false');
                }
            });

            if (!toggle) return;

            const menu = toggle.closest('[data-equipment-action-menu]');
            const list = menu.querySelector('.equipment-action-list');
            const isOpen = menu.classList.toggle('is-open');
            toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');

            if (list) {
                const rect = toggle.getBoundingClientRect();
                const listWidth = list.offsetWidth || 220;
                let left = rect.left + rect.width - listWidth;
                if (left < 8) left = 8;

                if (isOpen) {
                    list.style.position = 'fixed';
                    list.style.top = (rect.bottom + 6) + 'px';
                    list.style.left = left + 'px';
                    list.style.zIndex = '9999';
                } else {
                    list.style.position = '';
                    list.style.top = '';
                    list.style.left = '';
                    list.style.zIndex = '';
                }
            }
        });

        document.addEventListener('keydown', (event) => {
            if (event.key !== 'Escape') return;

            document.querySelectorAll('[data-equipment-action-menu]').forEach((menu) => {
                menu.classList.remove('is-open');
                const button = menu.querySelector('[data-equipment-action-toggle]');
                if (button) button.setAttribute('aria-expanded', 'false');
            });
        });

        const equipmentPinModal = document.getElementById('equipmentPinModal');
        const equipmentPinTitle = document.getElementById('equipmentPinTitle');
        const equipmentPinText = document.getElementById('equipmentPinText');
        const equipmentPinInput = document.getElementById('equipmentPinInput');
        const equipmentEditPinForm = document.getElementById('equipmentEditPinForm');
        const equipmentEditPinHidden = document.getElementById('equipmentEditPinHidden');
        let currentEquipmentDeleteForm = null;
        let currentEquipmentEditUrl = null;

        document.querySelectorAll('[data-delete-form]').forEach(form => {
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                currentEquipmentDeleteForm = form;
                currentEquipmentEditUrl = null;
                equipmentPinTitle.textContent = 'Confirmar eliminacion';
                equipmentPinText.textContent = 'Ingrese el PIN de acceso para eliminar:';
                equipmentPinInput.value = '';
                equipmentPinModal.style.display = 'block';
                equipmentPinInput.focus();
            });
        });

        document.querySelectorAll('.edit-equipment-btn').forEach(button => {
            button.addEventListener('click', (e) => {
                e.stopPropagation();
                currentEquipmentDeleteForm = null;
                currentEquipmentEditUrl = button.dataset.url;
                equipmentPinTitle.textContent = 'Confirmar edicion';
                equipmentPinText.textContent = 'Ingrese el PIN de acceso para editar:';
                equipmentPinInput.value = '';
                equipmentPinModal.style.display = 'block';
                equipmentPinInput.focus();
            });
        });

        document.getElementById('equipmentPinAccept').addEventListener('click', () => {
            const pin = equipmentPinInput.value.trim();
            if (!pin) return;

            if (currentEquipmentDeleteForm) {
                currentEquipmentDeleteForm.querySelector('input[name="pin"]').value = pin;
                currentEquipmentDeleteForm.submit();
                return;
            }

            if (currentEquipmentEditUrl) {
                equipmentEditPinForm.action = currentEquipmentEditUrl;
                equipmentEditPinHidden.value = pin;
                equipmentEditPinForm.submit();
            }
        });

        function closeEquipmentPinModal() {
            equipmentPinModal.style.display = 'none';
            currentEquipmentDeleteForm = null;
            currentEquipmentEditUrl = null;
        }

        document.getElementById('equipmentPinCancel').addEventListener('click', closeEquipmentPinModal);
        equipmentPinModal.querySelector('.equipment-pin-modal__overlay').addEventListener('click', (e) => {
            if (e.target === e.currentTarget) closeEquipmentPinModal();
        });
    </script>
@endsection
