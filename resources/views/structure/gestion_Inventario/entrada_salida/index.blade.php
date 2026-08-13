@extends('layouts.dashboard')

@section('title', 'Entrada / Salida')
@section('page-title', 'Entrada / Salida')
@section('page-sub', 'Gestion de Inventario > Entrada / Salida')

@push('head')
<style>
    .movement-page {
        display: grid;
        gap: 22px;
    }

    .page-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 18px;
        flex-wrap: wrap;
        padding: 18px 22px;
        border-radius: 12px;
        background: linear-gradient(135deg, #158be8 0%, #0f6fbd 100%);
        color: #fff;
        box-shadow: 0 8px 24px rgba(21, 139, 232, .22);
    }

    .page-header h2 {
        margin: 0;
        font-size: 18px;
        font-weight: 900;
    }

    .page-header p {
        margin: 4px 0 0;
        font-size: 13px;
        opacity: .92;
    }

    .page-header a {
        padding: 10px 16px;
        border-radius: 6px;
        background: #fff;
        color: #158be8;
        text-decoration: none;
        font-size: 13px;
        font-weight: 900;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: background .2s;
    }

    .page-header a:hover {
        background: #e6f4ff;
    }

    .page-header a svg {
        width: 16px;
        height: 16px;
    }

    .movement-tabs {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 6px;
        border-radius: 8px;
        background: #fff;
        border: 1px solid #e2e8f0;
        box-shadow: 0 2px 10px rgba(0, 0, 0, .04);
    }

    .movement-tab {
        border: 0;
        border-radius: 6px;
        background: transparent;
        color: #64748b;
        font: inherit;
        font-size: 13px;
        font-weight: 800;
        cursor: pointer;
        padding: 8px 16px;
        transition: background .2s, color .2s;
    }

    .movement-tab.is-active {
        background: #158be8;
        color: #fff;
    }

    .filters-card {
        display: grid;
        grid-template-columns: repeat(4, minmax(150px, 1fr));
        gap: 16px;
        align-items: end;
        padding: 18px 20px;
        border-radius: 12px;
        background: #fff;
        border: 1px solid #e2e8f0;
        box-shadow: 0 2px 10px rgba(0, 0, 0, .04);
    }

    .filter-field {
        display: flex;
        flex-direction: column;
        gap: 5px;
    }

    .filter-field label {
        color: #475569;
        font-size: 12px;
        font-weight: 800;
    }

    .filter-field input,
    .filter-field select {
        width: 100%;
        height: 38px;
        padding: 0 12px;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        background: #f8fafc;
        color: #1f2937;
        font: inherit;
        font-size: 13px;
        outline: none;
        transition: border-color .2s, box-shadow .2s;
    }

    .filter-field input:focus,
    .filter-field select:focus {
        border-color: #158be8;
        box-shadow: 0 0 0 3px rgba(21, 139, 232, .12);
    }

    .table-panel {
        overflow: hidden;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        background: #fff;
        box-shadow: 0 2px 10px rgba(0, 0, 0, .04);
    }

    .table-wrap {
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
        padding: 16px 18px;
        background: #f1f5f9;
        color: #1f2937;
        font-size: 12px;
        font-weight: 900;
        text-align: left;
        border-bottom: 1px solid #e2e8f0;
    }

    .movement-table td {
        height: 66px;
        padding: 12px 18px;
        border-bottom: 1px solid #f1f5f9;
        background: #fff;
        vertical-align: middle;
        font-weight: 600;
    }

    .movement-table tr:last-child td {
        border-bottom: 0;
    }

    .movement-pill {
        min-width: 74px;
        min-height: 26px;
        padding: 0 12px;
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
        width: 34px;
        height: 34px;
        border: 0;
        border-radius: 50%;
        background: #f1f5f9;
        color: #158be8;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: background .2s;
    }

    .movement-action:hover {
        background: #e6f4ff;
    }

    .movement-action svg {
        width: 18px;
        height: 18px;
    }

    .table-foot {
        min-height: 46px;
        padding: 0 18px;
        background: #f8fafc;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
        color: #64748b;
        font-size: 12px;
        font-weight: 700;
        border-top: 1px solid #e2e8f0;
    }

    .table-foot button {
        border: 0;
        background: transparent;
        color: #158be8;
        font: inherit;
        font-weight: 800;
        cursor: pointer;
    }

    .movement-details {
        background: #f8fafc;
    }

    .movement-details td {
        border-bottom: 1px solid #e2e8f0;
    }

    .details-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 16px;
    }

    .details-card {
        padding: 14px;
        border-radius: 8px;
        background: #fff;
        border: 1px solid #e2e8f0;
    }

    .details-card strong {
        display: flex;
        align-items: center;
        gap: 6px;
        color: #111827;
        font-size: 12px;
        font-weight: 900;
        margin-bottom: 8px;
    }

    .details-card p {
        margin: 4px 0;
        font-size: 12px;
        color: #4b5563;
    }

    .evidence-gallery {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-top: 6px;
    }

    .evidence-gallery a {
        display: inline-block;
    }

    .evidence-gallery img,
    .evidence-gallery video {
        width: 80px;
        height: 60px;
        object-fit: cover;
        border-radius: 6px;
        border: 1px solid #cbd5e1;
        transition: transform .2s;
    }

    .evidence-gallery a:hover img,
    .evidence-gallery a:hover video {
        transform: scale(1.05);
    }

    :root[data-theme="dark"] .page-header {
        background: linear-gradient(135deg, #0f6fbd 0%, #0c5a99 100%);
    }

    :root[data-theme="dark"] .movement-tabs {
        background: var(--surface);
        border-color: var(--border);
    }

    :root[data-theme="dark"] .movement-tab {
        color: var(--muted);
    }

    :root[data-theme="dark"] .movement-tab.is-active {
        background: #158be8;
        color: #fff;
    }

    :root[data-theme="dark"] .filters-card,
    :root[data-theme="dark"] .table-panel,
    :root[data-theme="dark"] .details-card {
        background: var(--surface);
        border-color: var(--border);
    }

    :root[data-theme="dark"] .filter-field input,
    :root[data-theme="dark"] .filter-field select,
    :root[data-theme="dark"] .movement-table td {
        background: var(--surface-2);
        color: var(--text);
        border-color: var(--border);
    }

    :root[data-theme="dark"] .movement-table th,
    :root[data-theme="dark"] .table-foot,
    :root[data-theme="dark"] .movement-details {
        background: rgba(10, 132, 255, .08);
    }

    :root[data-theme="dark"] .movement-table th {
        color: var(--text);
    }

    :root[data-theme="dark"] .filter-field label {
        color: var(--muted);
    }

    @media (max-width: 860px) {
        .filters-card {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 640px) {
        .page-header {
            flex-direction: column;
            align-items: stretch;
        }

        .movement-tabs {
            gap: 6px;
            overflow-x: auto;
        }

        .filters-card {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

@section('content')
    <section class="movement-page">
        <div class="page-header">
            <div>
                <h2>Entrada / Salida</h2>
                <p>Consulta y filtra los movimientos de equipos por paqueteria</p>
            </div>
            <a href="{{ route('inventory.movimientos.create') }}">
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

        <form class="filters-card" onsubmit="event.preventDefault(); filterMovements();">
            <div class="filter-field">
                <label for="movement-start">Fecha inicial</label>
                <input id="movement-start" type="date">
            </div>
            <div class="filter-field">
                <label for="movement-end">Fecha final</label>
                <input id="movement-end" type="date">
            </div>
            <div class="filter-field">
                <label for="movement-type">Tipo de movimiento</label>
                <select id="movement-type">
                    <option value="all">Todos</option>
                    <option value="entrada">Entrada</option>
                    <option value="salida">Salida</option>
                    <option value="transferencia">Transferencia</option>
                </select>
            </div>
            <div class="filter-field">
                <label for="movement-warehouse">Almacen</label>
                <select id="movement-warehouse">
                    <option value="all">Todos</option>
                    <option value="almacen central">Almacen Central</option>
                </select>
            </div>
        </form>

        <div class="table-panel">
            <div class="table-wrap">
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
                                <td colspan="8" style="padding: 16px 18px;">
                                    <div class="details-grid">
                                        @if($movement['movement_type'] === 'entrada')
                                            <div class="details-card">
                                                <strong>
                                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2"></rect><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path></svg>
                                                    Caja al recibir
                                                </strong>
                                                <p>Tipo: {{ $movement['metadata']['entrada']['caja']['tipo'] ?? '-' }}</p>
                                                <p>Estado: {{ $movement['metadata']['entrada']['caja']['estado'] ?? '-' }}</p>
                                                <p>Dimensiones: {{ $movement['metadata']['entrada']['caja']['dimensiones'] ?? '-' }}</p>
                                                <p>Peso: {{ $movement['metadata']['entrada']['caja']['peso'] ?? '-' }}</p>
                                            </div>
                                            <div class="details-card">
                                                <strong>
                                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                                                    Envoltura
                                                </strong>
                                                <p>Material: {{ $movement['metadata']['entrada']['envoltura']['material'] ?? '-' }}</p>
                                                <p>Estado: {{ $movement['metadata']['entrada']['envoltura']['estado'] ?? '-' }}</p>
                                                <p>Observaciones: {{ $movement['metadata']['entrada']['envoltura']['observaciones'] ?? '-' }}</p>
                                            </div>
                                            <div class="details-card">
                                                <strong>
                                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path></svg>
                                                    Contenido de la caja
                                                </strong>
                                                <p>Accesorios: {{ $movement['metadata']['entrada']['contenido']['accesorios'] ?? '-' }}</p>
                                                <p>Acomodo: {{ $movement['metadata']['entrada']['contenido']['acomodo'] ?? '-' }}</p>
                                                <p>Observaciones: {{ $movement['metadata']['entrada']['contenido']['observaciones'] ?? '-' }}</p>
                                            </div>
                                        @elseif($movement['movement_type'] === 'salida')
                                            <div class="details-card">
                                                <strong>
                                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13"></rect><polygon points="16 8 23 3 23 16 16 11"></polygon></svg>
                                                    Informacion del envio
                                                </strong>
                                                <p>Paqueteria: {{ $movement['metadata']['salida']['envio']['paqueteria'] ?? '-' }}</p>
                                                <p>Guia: {{ $movement['metadata']['salida']['envio']['guia'] ?? '-' }}</p>
                                                <p>Fecha de envio: {{ $movement['metadata']['salida']['envio']['fecha_envio'] ?? '-' }}</p>
                                                <p>Responsable: {{ $movement['metadata']['salida']['envio']['responsable'] ?? '-' }}</p>
                                            </div>
                                            <div class="details-card">
                                                <strong>
                                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s-8-4.5-8-11.8A8 8 0 0 1 12 2a8 8 0 0 1 8 8.2c0 7.3-8 11.8-8 11.8z"></path></svg>
                                                    Embalaje de salida
                                                </strong>
                                                <p>Tipo: {{ $movement['metadata']['salida']['embalaje']['tipo'] ?? '-' }}</p>
                                                <p>Material: {{ $movement['metadata']['salida']['embalaje']['material'] ?? '-' }}</p>
                                                <p>Estado: {{ $movement['metadata']['salida']['embalaje']['estado'] ?? '-' }}</p>
                                            </div>
                                            <div class="details-card">
                                                <strong>
                                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
                                                    Como se manda
                                                </strong>
                                                <p>Direccion: {{ $movement['metadata']['salida']['envio']['direccion'] ?? '-' }}</p>
                                                <p>Instrucciones: {{ $movement['metadata']['salida']['envio']['instrucciones'] ?? '-' }}</p>
                                                <p>Prioridad: {{ $movement['metadata']['salida']['envio']['prioridad'] ?? '-' }}</p>
                                            </div>
                                        @else
                                            <div class="details-card">
                                                <strong>Detalles</strong>
                                                <p>{{ $movement['metadata']['notas'] ?? 'Sin detalles adicionales' }}</p>
                                            </div>
                                        @endif
                                        <div class="details-card">
                                            <strong>
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path>
                                                    <circle cx="12" cy="13" r="4"></circle>
                                                </svg>
                                                Evidencias
                                            </strong>
                                            @php
                                                $evidencias = $movement['metadata']['evidencias'] ?? [];
                                                $imagenes = $evidencias['imagenes'] ?? [];
                                                $video = $evidencias['video'] ?? null;
                                            @endphp
                                            @if(!empty($imagenes) || !empty($video))
                                                <div class="evidence-gallery">
                                                    @foreach($imagenes as $imagen)
                                                        <a href="{{ asset('storage/' . $imagen) }}" target="_blank">
                                                            <img src="{{ asset('storage/' . $imagen) }}" alt="Evidencia">
                                                        </a>
                                                    @endforeach
                                                    @if($video)
                                                        <a href="{{ asset('storage/' . $video) }}" target="_blank">
                                                            <video src="{{ asset('storage/' . $video) }}" muted playsinline preload="metadata"></video>
                                                        </a>
                                                    @endif
                                                </div>
                                            @else
                                                <p>Sin evidencias</p>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="table-foot">
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
