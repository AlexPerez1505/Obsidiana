@extends('layouts.dashboard')

@section('title', 'Entrada / Salida')
@section('page-title', 'Entrada / Salida')
@section('page-sub', 'Gestion de Inventario > Entrada / Salida')

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
        color: #111827;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
    }

    .movement-action:hover {
        background: #eef4ff;
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
                        @foreach ($movements as $movement)
                            <tr class="movement-main" data-type="{{ $movement['movement_type'] }}" data-warehouse="{{ strtolower($movement['warehouse']) }}" data-date="{{ $movement['date'] }}">
                                <td>{{ $movement['date'] }}</td>
                                <td><span class="movement-pill {{ $movement['tone'] }}">{{ $movement['type'] }}</span></td>
                                <td>{{ $movement['folio'] }}</td>
                                <td>{{ $movement['warehouse'] }}</td>
                                <td>{{ $movement['product'] }}</td>
                                <td>{{ $movement['quantity'] }}</td>
                                <td>{{ $movement['reference'] }}</td>
                                <td>
                                    <button class="movement-action" type="button" aria-label="Ver detalles de {{ $movement['folio'] }}" onclick="toggleDetails('details-{{ $loop->index }}')">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"></path>
                                            <circle cx="12" cy="12" r="3"></circle>
                                        </svg>
                                    </button>
                                </td>
                            </tr>
                            <tr id="details-{{ $loop->index }}" class="movement-details" style="display:none;" data-type="{{ $movement['movement_type'] }}" data-warehouse="{{ strtolower($movement['warehouse']) }}" data-date="{{ $movement['date'] }}">
                                <td colspan="8" style="padding: 14px 18px; background: #f8fafc;">
                                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; font-size: 12px; color: #374151;">
                                        @if($movement['movement_type'] === 'entrada')
                                            <div>
                                                <strong style="color: #111827;">Caja al recibir</strong>
                                                <p style="margin: 4px 0;">Tipo: {{ $movement['metadata']['entrada']['caja']['tipo'] ?? '-' }}</p>
                                                <p style="margin: 4px 0;">Estado: {{ $movement['metadata']['entrada']['caja']['estado'] ?? '-' }}</p>
                                                <p style="margin: 4px 0;">Dimensiones: {{ $movement['metadata']['entrada']['caja']['dimensiones'] ?? '-' }}</p>
                                                <p style="margin: 4px 0;">Peso: {{ $movement['metadata']['entrada']['caja']['peso'] ?? '-' }}</p>
                                            </div>
                                            <div>
                                                <strong style="color: #111827;">Envoltura</strong>
                                                <p style="margin: 4px 0;">Material: {{ $movement['metadata']['entrada']['envoltura']['material'] ?? '-' }}</p>
                                                <p style="margin: 4px 0;">Estado: {{ $movement['metadata']['entrada']['envoltura']['estado'] ?? '-' }}</p>
                                                <p style="margin: 4px 0;">Observaciones: {{ $movement['metadata']['entrada']['envoltura']['observaciones'] ?? '-' }}</p>
                                            </div>
                                            <div>
                                                <strong style="color: #111827;">Contenido de la caja</strong>
                                                <p style="margin: 4px 0;">Accesorios: {{ $movement['metadata']['entrada']['contenido']['accesorios'] ?? '-' }}</p>
                                                <p style="margin: 4px 0;">Acomodo: {{ $movement['metadata']['entrada']['contenido']['acomodo'] ?? '-' }}</p>
                                                <p style="margin: 4px 0;">Observaciones: {{ $movement['metadata']['entrada']['contenido']['observaciones'] ?? '-' }}</p>
                                            </div>
                                        @elseif($movement['movement_type'] === 'salida')
                                            <div>
                                                <strong style="color: #111827;">Información del envío</strong>
                                                <p style="margin: 4px 0;">Paquetería: {{ $movement['metadata']['salida']['envio']['paqueteria'] ?? '-' }}</p>
                                                <p style="margin: 4px 0;">Guía: {{ $movement['metadata']['salida']['envio']['guia'] ?? '-' }}</p>
                                                <p style="margin: 4px 0;">Fecha de envío: {{ $movement['metadata']['salida']['envio']['fecha_envio'] ?? '-' }}</p>
                                                <p style="margin: 4px 0;">Responsable: {{ $movement['metadata']['salida']['envio']['responsable'] ?? '-' }}</p>
                                            </div>
                                            <div>
                                                <strong style="color: #111827;">Embalaje de salida</strong>
                                                <p style="margin: 4px 0;">Tipo: {{ $movement['metadata']['salida']['embalaje']['tipo'] ?? '-' }}</p>
                                                <p style="margin: 4px 0;">Material: {{ $movement['metadata']['salida']['embalaje']['material'] ?? '-' }}</p>
                                                <p style="margin: 4px 0;">Estado: {{ $movement['metadata']['salida']['embalaje']['estado'] ?? '-' }}</p>
                                            </div>
                                            <div>
                                                <strong style="color: #111827;">Cómo se manda</strong>
                                                <p style="margin: 4px 0;">Dirección: {{ $movement['metadata']['salida']['envio']['direccion'] ?? '-' }}</p>
                                                <p style="margin: 4px 0;">Instrucciones: {{ $movement['metadata']['salida']['envio']['instrucciones'] ?? '-' }}</p>
                                                <p style="margin: 4px 0;">Prioridad: {{ $movement['metadata']['salida']['envio']['prioridad'] ?? '-' }}</p>
                                            </div>
                                        @else
                                            <div>
                                                <strong style="color: #111827;">Detalles</strong>
                                                <p style="margin: 4px 0;">{{ $movement['metadata']['notas'] ?? 'Sin detalles adicionales' }}</p>
                                            </div>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="movement-foot">
                <span id="movementCount">Mostrando 1 a {{ $total }} de {{ $total }} resultados</span>
                <button type="button">Ver mas &gt;</button>
            </div>
        </div>
    </section>

    <script>
        let activeMovementType = 'all';
        const mainRows = Array.from(document.querySelectorAll('#movementBody tr.movement-main'));
        const detailRows = Array.from(document.querySelectorAll('#movementBody tr.movement-details'));
        const movementTypeSelect = document.getElementById('movement-type');
        const movementWarehouse = document.getElementById('movement-warehouse');
        const movementCount = document.getElementById('movementCount');
        const total = {{ $total }};

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

        function filterMovements() {
            const type = activeMovementType;
            const warehouse = movementWarehouse.value;
            let visible = 0;

            mainRows.forEach((row) => {
                const matchesType = type === 'all' || row.dataset.type === type;
                const matchesWarehouse = warehouse === 'all' || row.dataset.warehouse === warehouse;
                const show = matchesType && matchesWarehouse;

                row.style.display = show ? '' : 'none';
                if (show) visible += 1;
            });

            detailRows.forEach((row) => {
                row.style.display = 'none';
                row.classList.remove('is-open');
            });

            movementCount.textContent = visible === 0
                ? 'Sin resultados'
                : 'Mostrando 1 a ' + visible + ' de ' + total + ' resultados';
        }

        function toggleDetails(id) {
            const detail = document.getElementById(id);
            if (!detail) return;

            const isOpen = detail.classList.contains('is-open');
            detail.classList.toggle('is-open', !isOpen);
            detail.style.display = isOpen ? 'none' : '';
        }
    </script>
@endsection
