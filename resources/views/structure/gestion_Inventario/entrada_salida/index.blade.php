@extends('layouts.dashboard')

@section('title', 'Entrada / Salida')
@section('page-title', 'Entrada / Salida')
@section('page-sub', 'Gestion de Inventario > Entrada / Salida')

@php
    $toneMap = [
        'entrada' => 'green',
        'salida' => 'red',
        'transferencia' => 'blue',
    ];
@endphp

@push('head')
<style>
    .movement-page {
        display: grid;
        gap: 18px;
    }

    .movement-head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 16px;
    }

    .movement-head p {
        margin: 0;
        color: #718096;
        font-size: 14px;
        font-weight: 600;
    }

    .movement-create {
        min-height: 38px;
        margin-top: 22px;
        padding: 0 14px;
        border-radius: 4px;
        background: #158be8;
        color: #fff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        text-decoration: none;
        font-size: 12px;
        font-weight: 900;
        box-shadow: 0 7px 16px rgba(21, 139, 232, .22);
        white-space: nowrap;
    }

    .movement-create:hover {
        background: #0879d0;
    }

    .movement-create svg,
    .movement-action svg {
        width: 16px;
        height: 16px;
        flex: 0 0 auto;
    }

    .movement-tabs {
        display: flex;
        align-items: center;
        gap: 46px;
        min-height: 30px;
    }

    .movement-tab {
        border: 0;
        background: transparent;
        color: #64748b;
        font: inherit;
        font-size: 14px;
        font-weight: 800;
        cursor: pointer;
        padding: 0;
    }

    .movement-tab.is-active {
        color: #1689ff;
    }

    .movement-filters {
        display: grid;
        grid-template-columns: repeat(4, minmax(130px, 1fr));
        gap: 16px;
        align-items: end;
        padding: 14px 18px;
        border-radius: 18px;
        background: rgba(255, 255, 255, .62);
        border: 1px solid rgba(226, 232, 240, .78);
    }

    .movement-field label {
        display: block;
        margin: 0 0 7px;
        color: #718096;
        font-size: 13px;
        font-weight: 700;
    }

    .movement-field input,
    .movement-field select {
        width: 100%;
        height: 36px;
        padding: 0 10px;
        border: 1px solid #cbd5e1;
        border-radius: 4px;
        background: #f8fafc;
        color: #1f2937;
        font: inherit;
        font-size: 13px;
        outline: none;
    }

    .movement-field input:focus,
    .movement-field select:focus {
        border-color: #158be8;
        box-shadow: 0 0 0 3px rgba(21, 139, 232, .14);
    }

    .movement-table-panel {
        overflow: hidden;
        border: 1px solid #a8c5ff;
        border-radius: 5px;
        background: #fff;
    }

    .movement-table-wrap {
        overflow-x: auto;
    }

    .movement-table {
        width: 100%;
        min-width: 960px;
        border-collapse: collapse;
        color: #202938;
        font-size: 13px;
    }

    .movement-table th {
        padding: 17px 16px;
        background: #d8e2ff;
        color: #111827;
        font-size: 12px;
        font-weight: 900;
        text-align: left;
        border-bottom: 1px solid #a8c5ff;
    }

    .movement-table td {
        height: 70px;
        padding: 11px 16px;
        border-bottom: 1px solid #a8c5ff;
        background: #fff;
        vertical-align: middle;
        font-weight: 600;
    }

    .movement-pill {
        min-width: 72px;
        min-height: 24px;
        padding: 0 10px;
        border-radius: 999px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        font-weight: 800;
        line-height: 1;
        white-space: nowrap;
    }

    .movement-pill.green {
        color: #16a329;
        border: 1px solid #22c943;
        background: #f7fff8;
    }

    .movement-pill.blue {
        color: #1689ff;
        border: 1px solid #1689ff;
        background: #f5fbff;
    }

    .movement-pill.red {
        color: #ff3131;
        border: 1px solid #ff4b4b;
        background: #fff8f8;
    }

    .movement-action {
        width: 32px;
        height: 32px;
        border: 0;
        border-radius: 50%;
        background: transparent;
        color: var(--text);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
    }

    .movement-action:hover {
        background: var(--surface-2);
    }

    .movement-foot {
        min-height: 40px;
        padding: 0 16px;
        background: #d7e9ff;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
        color: #1689ff;
        font-size: 12px;
        font-weight: 700;
    }

    .movement-foot button {
        border: 0;
        background: transparent;
        color: #1689ff;
        font: inherit;
        font-weight: 800;
        cursor: pointer;
    }

    :root[data-theme="dark"] .movement-filters,
    :root[data-theme="dark"] .movement-table-panel {
        background: var(--surface);
        border-color: var(--border);
    }

    :root[data-theme="dark"] .movement-field input,
    :root[data-theme="dark"] .movement-field select,
    :root[data-theme="dark"] .movement-table td {
        background: var(--surface-2);
        color: var(--text);
        border-color: var(--border);
    }

    :root[data-theme="dark"] .movement-table th {
        background: rgba(10, 132, 255, .18);
        color: var(--text);
        border-color: var(--border);
    }

    :root[data-theme="dark"] .movement-foot {
        background: rgba(10, 132, 255, .14);
    }

    :root[data-theme="dark"] .movement-head p,
    :root[data-theme="dark"] .movement-field label,
    :root[data-theme="dark"] .movement-tab {
        color: var(--muted);
    }

    :root[data-theme="dark"] .movement-tab.is-active {
        color: var(--primary);
    }

    .movement-actions-list.is-open { display:block !important; }
    .movement-actions-list .action-link:hover { background:var(--surface-2); }

    @media (max-width: 860px) {
        .movement-filters {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 640px) {
        .movement-head {
            align-items: stretch;
            flex-direction: column;
        }

        .movement-create {
            width: 100%;
            margin-top: 0;
        }

        .movement-tabs {
            gap: 20px;
            overflow-x: auto;
            padding-bottom: 4px;
        }

        .movement-filters {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

@section('content')
    <section class="movement-page">
        <div class="movement-head">
            <div>
                <p>Consulta los movimientos de entrada y salida.</p>
            </div>

            <a href="{{ route('inventory.movimientos.create') }}" class="movement-create">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M12 5v14"></path>
                    <path d="M5 12h14"></path>
                </svg>
                Nuevo Movimiento
            </a>
        </div>

        <div class="movement-tabs" aria-label="Tipos de movimiento">
            <button class="movement-tab is-active" type="button" data-movement-type="all">Todo</button>
            <button class="movement-tab" type="button" data-movement-type="entrada">Entradas</button>
            <button class="movement-tab" type="button" data-movement-type="salida">Salidas</button>
            <button class="movement-tab" type="button" data-movement-type="transferencia">Transferencias</button>
        </div>

        <form class="movement-filters" onsubmit="event.preventDefault(); filterMovements();">
            <div class="movement-field">
                <label for="movement-start">Fecha inicial</label>
                <input id="movement-start" type="date">
            </div>
            <div class="movement-field">
                <label for="movement-end">Fecha final</label>
                <input id="movement-end" type="date">
            </div>
            <div class="movement-field">
                <label for="movement-type">Tipo de movimiento</label>
                <select id="movement-type">
                    <option value="all">Todos</option>
                    <option value="entrada">Entrada</option>
                    <option value="salida">Salida</option>
                    <option value="transferencia">Transferencia</option>
                </select>
            </div>
            <div class="movement-field">
                <label for="movement-warehouse">Almacen</label>
                <select id="movement-warehouse">
                    <option value="all">Todos</option>
                    <option value="almacen central">Almacen Central</option>
                </select>
            </div>
        </form>

        <div class="movement-table-panel">
            <div class="movement-table-wrap">
                <table class="movement-table">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Tipo</th>
                            <th>Folio</th>
                            <th>Almacen</th>
                            <th>Producto</th>
                            <th>Cantidad</th>
                            <th>Referencias/Proveedor</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="movementBody">
                        @forelse ($movements as $movement)
                            <tr data-type="{{ $movement->movement_type }}" data-warehouse="{{ strtolower($movement->warehouse) }}" data-date="{{ $movement->movement_date->format('Y-m-d') }}">
                                <td>{{ $movement->movement_date->format('d/m/Y') }}</td>
                                <td><span class="movement-pill {{ $toneMap[$movement->movement_type] ?? 'blue' }}">{{ ucfirst($movement->movement_type) }}</span></td>
                                <td>{{ $movement->folio }}</td>
                                <td>{{ $movement->warehouse }}</td>
                                <td>{{ $movement->item_name }}</td>
                                <td>{{ $movement->quantity }} {{ $movement->unit }}</td>
                                <td>{{ $movement->reference ?: ($movement->supplier ?: '-') }}</td>
                                <td>
                                    <div class="movement-actions-menu" style="position:relative; display:inline-block;">
                                        <button type="button" class="movement-action" aria-label="Acciones de {{ $movement->folio }}" onclick="this.nextElementSibling.classList.toggle('is-open')">
                                            <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                                <circle cx="12" cy="5" r="1.8"></circle>
                                                <circle cx="12" cy="12" r="1.8"></circle>
                                                <circle cx="12" cy="19" r="1.8"></circle>
                                            </svg>
                                        </button>
                                        <ul class="movement-actions-list" style="display:none; position:absolute; right:0; top:100%; margin-top:6px; min-width:160px; background:var(--surface); border:1px solid var(--border); border-radius:10px; box-shadow:0 10px 25px rgba(0,0,0,.12); z-index:100; list-style:none; padding:8px 0; margin:0; text-align:left;">
                                            <li>
                                                <a href="{{ route('inventory.movimientos.show', $movement) }}" class="action-link" style="display:flex; align-items:center; gap:8px; padding:9px 14px; color:var(--text); text-decoration:none; font-size:13px; font-weight:600;">
                                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                                    Ver detalle
                                                </a>
                                            </li>
                                            <li>
                                                <a href="{{ route('inventory.movimientos.edit', $movement) }}" class="action-link" style="display:flex; align-items:center; gap:8px; padding:9px 14px; color:var(--text); text-decoration:none; font-size:13px; font-weight:600;">
                                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                                    Editar
                                                </a>
                                            </li>
                                            <li>
                                                <button type="button" class="action-link delete-movement-btn" data-url="{{ route('inventory.movimientos.destroy', $movement) }}" data-folio="{{ $movement->folio }}" style="display:flex; align-items:center; gap:8px; width:100%; padding:9px 14px; color:var(--danger); background:none; border:none; cursor:pointer; font-size:13px; font-weight:600; text-align:left;">
                                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                                    Eliminar
                                                </button>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" style="text-align:center; padding:32px; color:#718096;">No hay movimientos registrados.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="movement-foot">
                <span id="movementCount">Mostrando 1 a {{ count($movements) }} de 25 resultados</span>
                <button type="button">Ver mas &gt;</button>
            </div>
        </div>
    </section>

    <div id="deleteModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.45); z-index:1000; align-items:center; justify-content:center; padding:16px;">
        <div style="width:100%; max-width:420px; background:var(--surface); border:1px solid var(--border); border-radius:16px; padding:24px; box-shadow:0 20px 40px rgba(0,0,0,0.2); text-align:center;">
            <div style="width:56px; height:56px; background:var(--danger-soft); color:var(--danger); border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 14px;">
                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
            </div>
            <h3 style="margin:0 0 8px; font-size:18px;">Confirmar eliminación</h3>
            <p class="muted" style="margin:0 0 20px; font-size:14px;">Ingresa tu contraseña para eliminar el movimiento <strong id="deleteFolio"></strong>.</p>

            <form id="deleteForm" method="POST" action="" style="text-align:left;">
                @csrf
                @method('DELETE')

                <div style="margin-bottom:18px;">
                    <label for="deletePassword" style="display:block; margin:0 0 6px; font-size:13px; font-weight:700; color:var(--text);">Contraseña</label>
                    <input id="deletePassword" name="password" type="password" required placeholder="Tu contraseña" style="width:100%; padding:11px 12px; border:1px solid var(--border); border-radius:9px; font-size:15px; background:var(--surface); color:var(--text);">
                </div>

                <div style="display:flex; align-items:center; justify-content:flex-end; gap:12px;">
                    <button type="button" id="cancelDelete" class="btn btn--ghost">Cancelar</button>
                    <button type="submit" class="btn" style="background:var(--danger); border-color:var(--danger); color:#fff;">Eliminar</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let activeMovementType = 'all';
        const movementRows = Array.from(document.querySelectorAll('#movementBody tr'));
        const movementTypeSelect = document.getElementById('movement-type');
        const movementWarehouse = document.getElementById('movement-warehouse');
        const movementStart = document.getElementById('movement-start');
        const movementEnd = document.getElementById('movement-end');
        const movementCount = document.getElementById('movementCount');

        document.querySelectorAll('.movement-tab').forEach((button) => {
            button.addEventListener('click', () => {
                document.querySelectorAll('.movement-tab').forEach((item) => item.classList.remove('is-active'));
                button.classList.add('is-active');
                activeMovementType = button.dataset.movementType;
                movementTypeSelect.value = activeMovementType;
                filterMovements();
            });
        });

        movementTypeSelect.addEventListener('change', () => {
            activeMovementType = movementTypeSelect.value;
            document.querySelectorAll('.movement-tab').forEach((item) => {
                item.classList.toggle('is-active', item.dataset.movementType === activeMovementType);
            });
            filterMovements();
        });

        movementWarehouse.addEventListener('change', filterMovements);
        movementStart.addEventListener('change', filterMovements);
        movementEnd.addEventListener('change', filterMovements);

        function filterMovements() {
            const type = activeMovementType;
            const warehouse = movementWarehouse.value;
            const start = movementStart.value;
            const end = movementEnd.value;
            let visible = 0;

            movementRows.forEach((row) => {
                const matchesType = type === 'all' || row.dataset.type === type;
                const matchesWarehouse = warehouse === 'all' || row.dataset.warehouse === warehouse;
                const rowDate = row.dataset.date;
                const matchesStart = !start || (rowDate && rowDate >= start);
                const matchesEnd = !end || (rowDate && rowDate <= end);
                const show = matchesType && matchesWarehouse && matchesStart && matchesEnd;

                row.style.display = show ? '' : 'none';
                if (show) visible += 1;
            });

            movementCount.textContent = visible === 0
                ? 'Sin resultados'
                : 'Mostrando ' + visible + ' movimiento' + (visible === 1 ? '' : 's');
        }

        const deleteModal = document.getElementById('deleteModal');
        const deleteForm = document.getElementById('deleteForm');
        const deleteFolio = document.getElementById('deleteFolio');
        const deletePassword = document.getElementById('deletePassword');
        const cancelDelete = document.getElementById('cancelDelete');

        document.querySelectorAll('.delete-movement-btn').forEach((button) => {
            button.addEventListener('click', (e) => {
                e.stopPropagation();
                deleteForm.action = button.dataset.url;
                deleteFolio.textContent = button.dataset.folio;
                deletePassword.value = '';
                deleteModal.style.display = 'flex';
            });
        });

        function closeDeleteModal() {
            deleteModal.style.display = 'none';
        }

        cancelDelete.addEventListener('click', closeDeleteModal);
        deleteModal.addEventListener('click', (e) => {
            if (e.target === deleteModal) closeDeleteModal();
        });
    </script>
@endsection
