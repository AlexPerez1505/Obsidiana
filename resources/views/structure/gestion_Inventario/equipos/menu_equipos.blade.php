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

    $equipmentRows = collect($equipmentList ?? [])->map(function ($equipo) use ($statusTones) {
        return [
            'code' => $equipo->code,
            'name' => $equipo->name,
            'type' => $equipo->equipmentType?->name ?? 'Sin tipo',
            'location' => $equipo->warehouse ?? 'Sin ubicacion',
            'owner' => $equipo->assigned_to ?? 'Sin asignar',
            'status' => $equipo->status ?? 'Activo',
            'tone' => $statusTones[$equipo->status] ?? 'green',
            'thumb' => $equipo->thumb ?? 'tower',
        ];
    })->all();
@endphp

@push('head')
<style>
    .equipment-search {
        position: relative;
        margin-bottom: 18px;
    }
    .equipment-search svg {
        position: absolute;
        left: 14px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--muted);
        width: 18px;
        height: 18px;
    }
    .equipment-search input {
        width: 100%;
        padding: 11px 14px 11px 42px;
        border: 1px solid var(--border);
        border-radius: 9px;
        background: var(--surface);
        color: var(--text);
        font: inherit;
        font-size: 15px;
    }
    .equipment-search input:focus {
        outline: none;
        border-color: #7c3aed;
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
    .equipment-search input { background: rgba(4,10,24,0.72); border-color: rgba(0,168,255,0.55); color: #fff; }
    .equipment-search input:focus { border-color: #00A8FF; box-shadow: 0 0 0 3px rgba(0,168,255,0.18), 0 0 18px rgba(0,168,255,0.45); outline: none; }
    .equipment-search svg { color: #00A8FF; }
    .equipment-foot { color: rgba(255,255,255,0.55); }
    :root[data-theme="light"] .equipment-menu.card {
        background: linear-gradient(145deg, rgba(15,23,42,0.04), rgba(15,23,42,0.08));
        border-color: rgba(0,168,255,0.55);
        box-shadow: 0 8px 28px rgba(0,0,0,0.1), 0 0 14px rgba(0,168,255,0.18), inset 0 1px 0 rgba(255,255,255,0.5);
    }
    :root[data-theme="light"] .equipment-menu .btn--ghost {
        background: rgba(0,168,255,0.08);
        border-color: rgba(0,168,255,0.55);
        color: #00A8FF;
    }
    :root[data-theme="light"] .equipment-menu .btn--ghost:hover { background: rgba(0,168,255,0.14); border-color: #00A8FF; }
    :root[data-theme="light"] .equipment-search input { background: #fff; color: var(--text); border-color: rgba(0,168,255,0.35); }
    :root[data-theme="light"] .equipment-search input:focus { border-color: #00A8FF; box-shadow: 0 0 0 3px rgba(0,168,255,0.12), 0 0 18px rgba(0,168,255,0.25); outline: none; }
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
</style>
@endpush

@section('content')
    <div class="equipment-menu-header" style="display:flex; justify-content:flex-end; margin-bottom:18px;">
        <a href="{{ route('inventory.equipos.create') }}" class="btn" style="text-decoration:none; display:inline-flex; align-items:center; gap:7px;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12h14"/></svg>
            Nuevo Equipo
        </a>
    </div>

    <x-ui.card class="equipment-menu">
        <div class="equipment-search">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="11" cy="11" r="7"></circle>
                <path d="m20 20-3.5-3.5"></path>
            </svg>
            <input id="equipmentSearch" type="search" placeholder="Buscar por nombre, codigo o categoria..." autocomplete="off">
        </div>

        <div style="overflow-x:auto;">
            <table>
                <thead>
                    <tr>
                        <th>Codigo</th>
                        <th>Imagen</th>
                        <th>Equipo</th>
                        <th>Tipo</th>
                        <th>Ubicacion</th>
                        <th>Responsable</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody id="equipmentBody">
                    @forelse ($equipmentRows as $equipment)
                        <tr data-search="{{ strtolower($equipment['code'].' '.$equipment['name'].' '.$equipment['type'].' '.$equipment['location'].' '.$equipment['owner'].' '.$equipment['status']) }}">
                            <td style="font-weight:700;">{{ $equipment['code'] }}</td>
                            <td>
                                <span class="equipment-thumb" aria-label="Imagen de {{ $equipment['name'] }}">
                                    @include('structure.gestion_Inventario.equipos.partials.equipment-thumb', ['type' => $equipment['thumb']])
                                </span>
                            </td>
                            <td>{{ $equipment['name'] }}</td>
                            <td>{{ $equipment['type'] }}</td>
                            <td>{{ $equipment['location'] }}</td>
                            <td>{{ $equipment['owner'] }}</td>
                            <td><span class="equipment-state {{ $equipment['tone'] }}">{{ $equipment['status'] }}</span></td>
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
                                        <a href="{{ route('inventory.equipos.show', ['equipo' => $equipment['code']]) }}" role="menuitem">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" width="15" height="15"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/></svg>
                                            Visualizar
                                        </a>
                                        <a href="{{ route('inventory.equipos.edit', ['equipo' => $equipment['code']]) }}" role="menuitem">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" width="15" height="15"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5z"/></svg>
                                            Actualizar
                                        </a>
                                        <form method="POST" action="{{ route('inventory.equipos.destroy', ['equipo' => $equipment['code']]) }}" onsubmit="return confirm('¿Eliminar este equipo?');" role="none">
                                            @csrf
                                            @method('DELETE')
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
                            <td colspan="8" style="text-align:center; padding:32px; color:var(--muted);">
                                No hay equipos registrados. Agrega uno para gestionar el inventario.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="equipment-foot">
            <span id="equipmentCount">Mostrando {{ count($equipmentRows) ? 1 : 0 }} a {{ count($equipmentRows) }} de {{ count($equipmentRows) }} resultados</span>
            <button type="button" class="btn btn--ghost" style="font-size:13px;">Ver mas &gt;</button>
        </div>
    </x-ui.card>

    <script>
        const equipmentSearch = document.getElementById('equipmentSearch');
        const equipmentRows = Array.from(document.querySelectorAll('#equipmentBody tr'));
        const equipmentCount = document.getElementById('equipmentCount');

        equipmentSearch.addEventListener('input', () => {
            const query = equipmentSearch.value.trim().toLowerCase();
            let visible = 0;

            equipmentRows.forEach((row) => {
                const show = !query || row.dataset.search.includes(query);
                row.style.display = show ? '' : 'none';
                if (show) visible += 1;
            });

            equipmentCount.textContent = visible === 0
                ? 'Sin resultados'
                : 'Mostrando 1 a ' + visible + ' de 25 resultados';
        });

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
    </script>
@endsection
