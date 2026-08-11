@extends('layouts.dashboard')

@section('title', 'Entrada / Salida')
@section('page-title', 'Entrada / Salida')
@section('page-sub', 'Gestion de Inventario > Entrada / Salida')

@php
    $movements = [
        ['date' => '27/07/2026', 'type' => 'Entrada', 'tone' => 'green', 'folio' => 'EN-000125', 'warehouse' => 'Almacen Central', 'product' => 'Endoscopia flexible', 'quantity' => '1Pza', 'reference' => 'Olimpus Mexico S.A de C.V'],
        ['date' => '27/07/2026', 'type' => 'Transferencia', 'tone' => 'blue', 'folio' => 'EN-000125', 'warehouse' => 'Almacen Central', 'product' => 'Endoscopia flexible', 'quantity' => '1Pza', 'reference' => 'Olimpus Mexico S.A de C.V'],
        ['date' => '27/07/2026', 'type' => 'Salida', 'tone' => 'red', 'folio' => 'EN-000125', 'warehouse' => 'Almacen Central', 'product' => 'Endoscopia flexible', 'quantity' => '1Pza', 'reference' => 'Olimpus Mexico S.A de C.V'],
        ['date' => '27/07/2026', 'type' => 'Entrada', 'tone' => 'green', 'folio' => 'EN-000125', 'warehouse' => 'Almacen Central', 'product' => 'Endoscopia flexible', 'quantity' => '1Pza', 'reference' => 'Olimpus Mexico S.A de C.V'],
        ['date' => '27/07/2026', 'type' => 'Transferencia', 'tone' => 'blue', 'folio' => 'EN-000125', 'warehouse' => 'Almacen Central', 'product' => 'Endoscopia flexible', 'quantity' => '1Pza', 'reference' => 'Olimpus Mexico S.A de C.V'],
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
                            <tr data-type="{{ strtolower($movement['type']) }}" data-warehouse="{{ strtolower($movement['warehouse']) }}" data-date="{{ $movement['date'] }}">
                                <td>{{ $movement['date'] }}</td>
                                <td><span class="movement-pill {{ $movement['tone'] }}">{{ $movement['type'] }}</span></td>
                                <td>{{ $movement['folio'] }}</td>
                                <td>{{ $movement['warehouse'] }}</td>
                                <td>{{ $movement['product'] }}</td>
                                <td>{{ $movement['quantity'] }}</td>
                                <td>{{ $movement['reference'] }}</td>
                                <td>
                                    <button class="movement-action" type="button" aria-label="Acciones de {{ $movement['folio'] }}">
                                        <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                            <circle cx="12" cy="5" r="1.8"></circle>
                                            <circle cx="12" cy="12" r="1.8"></circle>
                                            <circle cx="12" cy="19" r="1.8"></circle>
                                        </svg>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="movement-foot">
                <span id="movementCount">Mostrando 1 a {{ count($movements) }} de 25 resultados</span>
                <button type="button">Ver mas &gt;</button>
            </div>
        </div>
    </section>

    <script>
        let activeMovementType = 'all';
        const movementRows = Array.from(document.querySelectorAll('#movementBody tr'));
        const movementTypeSelect = document.getElementById('movement-type');
        const movementWarehouse = document.getElementById('movement-warehouse');
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

        function filterMovements() {
            const type = activeMovementType;
            const warehouse = movementWarehouse.value;
            let visible = 0;

            movementRows.forEach((row) => {
                const matchesType = type === 'all' || row.dataset.type === type;
                const matchesWarehouse = warehouse === 'all' || row.dataset.warehouse === warehouse;
                const show = matchesType && matchesWarehouse;

                row.style.display = show ? '' : 'none';
                if (show) visible += 1;
            });

            movementCount.textContent = visible === 0
                ? 'Sin resultados'
                : 'Mostrando 1 a ' + visible + ' de 25 resultados';
        }
    </script>
@endsection
