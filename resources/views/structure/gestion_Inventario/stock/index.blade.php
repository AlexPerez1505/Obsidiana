@extends('layouts.dashboard')

@section('title', 'Stock')
@section('page-title', 'Stock')
@section('page-sub', 'Gestion de Inventario > Stock')

@php
    $metrics = [
        ['label' => 'Valor total del inventario', 'value' => '$2,456,890', 'caption' => 'MXN', 'tone' => 'blue', 'icon' => 'chart'],
        ['label' => 'Unidades totales', 'value' => '8,450', 'caption' => 'Unidades', 'tone' => 'cyan', 'icon' => 'box'],
        ['label' => 'Productos en stock', 'value' => '78', 'caption' => 'Productos', 'tone' => 'indigo', 'icon' => 'calculator'],
        ['label' => 'Productos criticos', 'value' => '12', 'caption' => 'Productos', 'tone' => 'red', 'icon' => 'alert'],
    ];

    $stockRows = [
        ['product' => 'Endoscopia flexible Gastro', 'warehouse' => 'Almacen Central', 'current' => 3, 'max' => 1, 'min' => 3, 'status' => 'Activo'],
        ['product' => 'Endoscopia flexible Gastro', 'warehouse' => 'Almacen Central', 'current' => 3, 'max' => 1, 'min' => 3, 'status' => 'Activo'],
        ['product' => 'Endoscopia flexible Gastro', 'warehouse' => 'Almacen Central', 'current' => 3, 'max' => 1, 'min' => 3, 'status' => 'Activo'],
        ['product' => 'Endoscopia flexible Gastro', 'warehouse' => 'Almacen Central', 'current' => 3, 'max' => 1, 'min' => 3, 'status' => 'Activo'],
        ['product' => 'Endoscopia flexible Gastro', 'warehouse' => 'Almacen Central', 'current' => 3, 'max' => 1, 'min' => 3, 'status' => 'Activo'],
        ['product' => 'Endoscopia flexible Gastro', 'warehouse' => 'Almacen Central', 'current' => 3, 'max' => 1, 'min' => 3, 'status' => 'Activo'],
    ];
@endphp

@push('head')
<style>
    .stock-page {
        display: grid;
        gap: 20px;
    }

    .stock-head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 16px;
    }

    .stock-head p {
        margin: 0;
        color: #718096;
        font-size: 14px;
        font-weight: 600;
    }

    .stock-search-btn {
        min-width: 138px;
        min-height: 34px;
        border: 1px solid #158be8;
        border-radius: 4px;
        background: #fff;
        color: #158be8;
        font: inherit;
        font-size: 12px;
        font-weight: 900;
        cursor: pointer;
    }

    .stock-search-btn:hover {
        background: #eff8ff;
    }

    .stock-metrics {
        display: grid;
        grid-template-columns: repeat(4, minmax(150px, 1fr));
        gap: 48px;
    }

    .stock-metric {
        min-height: 82px;
        padding: 16px 22px;
        border-radius: 18px;
        background: rgba(255, 255, 255, .72);
        display: grid;
        grid-template-columns: 42px 1fr;
        align-items: center;
        gap: 12px;
        border: 1px solid rgba(226, 232, 240, .72);
    }

    .stock-metric-icon {
        width: 42px;
        height: 42px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .stock-metric-icon svg {
        width: 38px;
        height: 38px;
    }

    .stock-metric.blue .stock-metric-icon { color: #3b82f6; }
    .stock-metric.cyan .stock-metric-icon { color: #38bdf8; }
    .stock-metric.indigo .stock-metric-icon { color: #4f8fe8; }
    .stock-metric.red .stock-metric-icon { color: #ff5b5b; }

    .stock-metric span {
        display: block;
        color: #718096;
        font-size: 11px;
        font-weight: 700;
        line-height: 1.2;
    }

    .stock-metric strong {
        display: block;
        margin-top: 3px;
        color: #111827;
        font-size: 17px;
        font-weight: 900;
        line-height: 1.1;
    }

    .stock-metric b {
        display: block;
        margin-top: 3px;
        color: #111827;
        font-size: 11px;
        font-weight: 900;
    }

    .stock-table-panel {
        overflow: hidden;
        border: 1px solid #a8c5ff;
        border-radius: 5px;
        background: #fff;
    }

    .stock-table-wrap {
        overflow-x: auto;
    }

    .stock-table {
        width: 100%;
        min-width: 900px;
        border-collapse: collapse;
        color: #202938;
        font-size: 13px;
    }

    .stock-table th {
        padding: 17px 16px;
        background: #d8e2ff;
        color: #111827;
        font-size: 12px;
        font-weight: 900;
        text-align: left;
        border-bottom: 1px solid #a8c5ff;
    }

    .stock-table td {
        height: 70px;
        padding: 11px 16px;
        border-bottom: 1px solid #a8c5ff;
        background: #fff;
        vertical-align: middle;
        font-weight: 600;
    }

    .stock-state {
        min-width: 70px;
        min-height: 24px;
        padding: 0 10px;
        border-radius: 999px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #16a329;
        border: 1px solid #22c943;
        background: #f7fff8;
        font-size: 12px;
        font-weight: 800;
        line-height: 1;
        white-space: nowrap;
    }

    .stock-action {
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

    .stock-action svg {
        width: 16px;
        height: 16px;
    }

    .stock-action:hover {
        background: #eef4ff;
    }

    .stock-foot {
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

    .stock-foot button {
        border: 0;
        background: transparent;
        color: #1689ff;
        font: inherit;
        font-weight: 800;
        cursor: pointer;
    }

    .stock-filter {
        display: none;
        padding: 13px 16px;
        border-radius: 18px;
        background: rgba(255, 255, 255, .62);
        border: 1px solid rgba(226, 232, 240, .78);
    }

    .stock-filter.is-open {
        display: block;
    }

    .stock-filter input {
        width: 100%;
        height: 40px;
        padding: 0 14px;
        border: 1px solid #cbd5e1;
        border-radius: 5px;
        background: #f8fafc;
        color: #1f2937;
        font: inherit;
        font-size: 13px;
        outline: none;
    }

    .stock-filter input:focus {
        border-color: #158be8;
        box-shadow: 0 0 0 3px rgba(21, 139, 232, .14);
    }

    :root[data-theme="dark"] .stock-search-btn,
    :root[data-theme="dark"] .stock-metric,
    :root[data-theme="dark"] .stock-table-panel,
    :root[data-theme="dark"] .stock-filter {
        background: var(--surface);
        border-color: var(--border);
    }

    :root[data-theme="dark"] .stock-table td,
    :root[data-theme="dark"] .stock-filter input {
        background: var(--surface-2);
        color: var(--text);
        border-color: var(--border);
    }

    :root[data-theme="dark"] .stock-table th {
        background: rgba(10, 132, 255, .18);
        color: var(--text);
        border-color: var(--border);
    }

    :root[data-theme="dark"] .stock-foot {
        background: rgba(10, 132, 255, .14);
    }

    :root[data-theme="dark"] .stock-head p,
    :root[data-theme="dark"] .stock-metric span {
        color: var(--muted);
    }

    :root[data-theme="dark"] .stock-metric strong,
    :root[data-theme="dark"] .stock-metric b {
        color: var(--text);
    }

    @media (max-width: 1180px) {
        .stock-metrics {
            grid-template-columns: repeat(2, minmax(150px, 1fr));
            gap: 16px;
        }
    }

    @media (max-width: 640px) {
        .stock-head {
            align-items: stretch;
            flex-direction: column;
        }

        .stock-search-btn {
            width: 100%;
        }

        .stock-metrics {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

@section('content')
    <section class="stock-page">
        <div class="stock-head">
            <p>Consulta el stock por almacen y producto.</p>
            <button class="stock-search-btn" type="button" onclick="toggleStockSearch()">Buscar</button>
        </div>

        <div class="stock-filter" id="stockFilter">
            <input id="stockSearch" type="search" placeholder="Buscar por producto, almacen o estado..." autocomplete="off">
        </div>

        <div class="stock-metrics">
            @foreach ($metrics as $metric)
                <div class="stock-metric {{ $metric['tone'] }}">
                    <span class="stock-metric-icon">
                        @if ($metric['icon'] === 'chart')
                            <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M4 14h4v7H4z"/><path d="M10 10h4v11h-4z"/><path d="M16 5h4v16h-4z"/><path d="M4 11l5-5 4 3 6-7 1.5 1.3-7.4 8.6-4-3-3.7 3.7z"/></svg>
                        @elseif ($metric['icon'] === 'box')
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 16V8l-9-5-9 5v8l9 5 9-5z"/><path d="M3.4 8.4 12 13l8.6-4.6"/><path d="M12 21v-8"/><path d="m7.5 5.5 9 5"/></svg>
                        @elseif ($metric['icon'] === 'calculator')
                            <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><rect x="5" y="3" width="14" height="18" rx="2"/><rect x="8" y="6" width="8" height="3" rx="1" fill="#dbeafe"/><circle cx="9" cy="13" r="1" fill="#dbeafe"/><circle cx="13" cy="13" r="1" fill="#dbeafe"/><circle cx="17" cy="13" r="1" fill="#dbeafe"/><circle cx="9" cy="17" r="1" fill="#dbeafe"/><circle cx="13" cy="17" r="1" fill="#dbeafe"/><circle cx="17" cy="17" r="1" fill="#dbeafe"/></svg>
                        @else
                            <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/><rect x="11" y="8" width="2" height="6" rx="1" fill="#fff"/><rect x="11" y="16.5" width="2" height="2" rx="1" fill="#fff"/></svg>
                        @endif
                    </span>
                    <span>
                        {{ $metric['label'] }}
                        <strong>{{ $metric['value'] }}</strong>
                        <b>{{ $metric['caption'] }}</b>
                    </span>
                </div>
            @endforeach
        </div>

        <div class="stock-table-panel">
            <div class="stock-table-wrap">
                <table class="stock-table">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th>Almacen</th>
                            <th>Stock actual</th>
                            <th>Stock maximo</th>
                            <th>Stock minimo</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="stockBody">
                        @foreach ($stockRows as $row)
                            <tr data-search="{{ strtolower($row['product'].' '.$row['warehouse'].' '.$row['status']) }}">
                                <td>{{ $row['product'] }}</td>
                                <td>{{ $row['warehouse'] }}</td>
                                <td>{{ $row['current'] }}</td>
                                <td>{{ $row['max'] }}</td>
                                <td>{{ $row['min'] }}</td>
                                <td><span class="stock-state">{{ $row['status'] }}</span></td>
                                <td>
                                    <button class="stock-action" type="button" aria-label="Acciones de {{ $row['product'] }}">
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
            <div class="stock-foot">
                <span id="stockCount">Mostrando 1 a {{ count($stockRows) }} de 25 resultados</span>
                <button type="button">Ver mas &gt;</button>
            </div>
        </div>
    </section>

    <script>
        const stockFilter = document.getElementById('stockFilter');
        const stockSearch = document.getElementById('stockSearch');
        const stockRows = Array.from(document.querySelectorAll('#stockBody tr'));
        const stockCount = document.getElementById('stockCount');

        function toggleStockSearch() {
            stockFilter.classList.toggle('is-open');
            if (stockFilter.classList.contains('is-open')) {
                stockSearch.focus();
            }
        }

        stockSearch.addEventListener('input', () => {
            const query = stockSearch.value.trim().toLowerCase();
            let visible = 0;

            stockRows.forEach((row) => {
                const show = !query || row.dataset.search.includes(query);
                row.style.display = show ? '' : 'none';
                if (show) visible += 1;
            });

            stockCount.textContent = visible === 0
                ? 'Sin resultados'
                : 'Mostrando 1 a ' + visible + ' de 25 resultados';
        });
    </script>
@endsection
