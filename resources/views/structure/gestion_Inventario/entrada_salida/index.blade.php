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
<<<<<<< Updated upstream
    <section class="movement-page">
        <div class="movement-head">
            <div>
                <p>Entradas: lo que llega, con su evidencia. Salidas: lo que se vendió, se registra solo desde Ventas.</p>
=======
    @php
        // Se recorre una sola vez: los mismos datos alimentan la tabla y las tarjetas.
        $filas = collect($movements->items())->map(function ($m) {
            $tipo = $m->movement_type;

            return [
                'modelo' => $m,
                'tipo' => $tipo,
                'tipoLabel' => ucfirst($tipo),
                'condicion' => $m->condicion ?: 'nuevo',
                'almacen' => $m->warehouse ?: 'Sin almacén',
                'quien' => $m->creator?->name ?: 'Sin registrar',
                'fecha' => $m->movement_date?->format('Y-m-d') ?? '',
                'fechaVista' => $m->movement_date?->format('d/m/Y') ?? '—',
                'nombre' => $m->item_name ?: 'Sin nombre',
            ];
        });

        $almacenes = $filas->pluck('almacen')->filter()->unique()->sort()->values();
        $quienes = $filas->pluck('quien')->filter()->unique()->sort()->values();

        // Cómo se pinta cada tipo de movimiento.
        $tono = ['entrada' => 'badge--ok', 'salida' => 'badge--danger', 'transferencia' => 'badge--info'];

        $datos = function (array $fila) {
            return [
                'data-buscar' => mb_strtolower($fila['modelo']->folio.' '.$fila['nombre'].' '.$fila['almacen'].' '.$fila['quien']),
                'data-tipo' => $fila['tipo'],
                'data-almacen' => $fila['almacen'],
                'data-condicion' => $fila['condicion'],
                'data-quien' => $fila['quien'],
                'data-fecha' => $fila['fecha'],
            ];
        };
    @endphp

    <div class="content-actions">
        <a href="{{ route('inventory.movimientos.create') }}" class="btn">
            <x-gravityui-plus width="15" height="15" />
            Nueva entrada
        </a>
    </div>

    {{-- ===================== Métricas ===================== --}}
    <div class="mv-stats">
        <div class="card card--accent stat">
            <span class="stat-ico blue">
                <x-gravityui-box />
            </span>
            <div>
                <div class="stat-num">{{ $resumen['movimientos'] }}</div>
                <div class="stat-lbl">Movimientos registrados</div>
            </div>
        </div>

        <div class="card card--accent is-green stat">
            <span class="stat-ico green">
                <x-gravityui-arrow-down />
            </span>
            <div>
                <div class="stat-num">{{ $resumen['entradas_mes'] }}</div>
                <div class="stat-lbl">Entradas este mes</div>
            </div>
        </div>

        <div class="card card--accent stat">
            <span class="stat-ico blue">
                <x-gravityui-boxes-3 />
            </span>
            <div>
                <div class="stat-num">{{ $resumen['piezas'] }}</div>
                <div class="stat-lbl">Piezas en inventario</div>
            </div>
        </div>

        <div class="card card--accent is-amber stat">
            <span class="stat-ico orange">
                <x-gravityui-clock />
            </span>
            <div>
                <div class="stat-num">{{ $resumen['en_proceso'] }}</div>
                <div class="stat-lbl">
                    En proceso, sin poder venderse
                    @if ($resumen['en_proceso'] > 0)
                        · <a href="{{ route('inventory.procesos.index') }}" class="link">ver</a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- ===================== Búsqueda y filtros ===================== --}}
    <div class="f-toolbar">
        <div class="f-search">
            <x-gravityui-magnifier />
            <input type="text" id="fBuscar" placeholder="Buscar por folio, equipo, almacén o quien registró" autocomplete="off">
        </div>

        <div class="flt" data-flt>
            <button type="button" class="flt-btn" data-flt-toggle aria-expanded="false">
                <x-gravityui-funnel />
                Filtros
                <span class="flt-count" data-flt-count hidden>0</span>
            </button>

            <div class="flt-panel" data-flt-panel hidden>
                <div class="flt-group">
                    <h4>Tipo de movimiento</h4>
                    @foreach (['entrada' => 'Entradas', 'salida' => 'Salidas', 'transferencia' => 'Transferencias'] as $valor => $texto)
                        <label class="flt-opt">
                            <span class="flt-opt-txt">{{ $texto }}</span>
                            <input type="checkbox" data-f="tipo" value="{{ $valor }}">
                        </label>
                    @endforeach
                </div>

                <div class="flt-group">
                    <h4>Condición</h4>
                    <label class="flt-opt">
                        <span class="flt-opt-txt">Equipo nuevo</span>
                        <input type="checkbox" data-f="condicion" value="nuevo">
                    </label>
                    <label class="flt-opt">
                        <span class="flt-opt-txt">Equipo usado</span>
                        <input type="checkbox" data-f="condicion" value="usado">
                    </label>
                </div>

                @if ($almacenes->count() > 1)
                    <div class="flt-group">
                        <h4>Almacén</h4>
                        @foreach ($almacenes as $almacen)
                            <label class="flt-opt">
                                <span class="flt-opt-txt">{{ $almacen }}</span>
                                <input type="checkbox" data-f="almacen" value="{{ $almacen }}">
                            </label>
                        @endforeach
                    </div>
                @endif

                @if ($quienes->count() > 1)
                    <div class="flt-group">
                        <h4>Registró</h4>
                        @foreach ($quienes as $quien)
                            <label class="flt-opt">
                                <span class="flt-opt-txt">{{ $quien }}</span>
                                <input type="checkbox" data-f="quien" value="{{ $quien }}">
                            </label>
                        @endforeach
                    </div>
                @endif

                <div class="flt-group">
                    <h4>Fecha del movimiento</h4>
                    <div class="flt-fechas">
                        <input type="date" data-f="desde" aria-label="Movimiento desde">
                        <input type="date" data-f="hasta" aria-label="Movimiento hasta">
                    </div>
                </div>
            </div>
        </div>

        {{-- Accesos rápidos: lo que entra / lo que sale --}}
        <div class="flt-toggles" role="group" aria-label="Tipo de movimiento">
            <button type="button" class="flt-tgl" data-f="estado" data-valor="entrada" title="Ver solo entradas" aria-pressed="false">
                <x-gravityui-arrow-down />
            </button>
            <button type="button" class="flt-tgl" data-f="estado" data-valor="salida" title="Ver solo salidas" aria-pressed="false">
                <x-gravityui-arrow-up />
            </button>
        </div>

        <button type="button" class="flt-btn flt-btn--icon" id="fLimpiar" title="Limpiar todos los filtros" aria-label="Limpiar filtros">
            <x-gravityui-funnel-xmark />
        </button>

        <x-ui.view-switch key="movimientos" />
    </div>

    <div class="flt-chips" id="fChips" hidden></div>

    {{-- ===================== Vista lista ===================== --}}
    <div class="card" data-view-list style="overflow-x:auto; padding:0;">
        <table class="mv-table">
            <thead>
                <tr>
                    <th>Movimiento</th>
                    <th>Equipo</th>
                    <th>Cantidad</th>
                    <th>Almacén</th>
                    <th>Registró</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($filas as $fila)
                    @php $m = $fila['modelo']; @endphp

                    <tr class="f-row" @foreach ($datos($fila) as $attr => $valor) {{ $attr }}="{{ $valor }}" @endforeach>
                        <td>
                            <div class="cell-id">
                                <span class="mv-ico {{ $fila['tipo'] }}">
                                    @if ($fila['tipo'] === 'entrada')
                                        <x-gravityui-arrow-down />
                                    @elseif ($fila['tipo'] === 'salida')
                                        <x-gravityui-arrow-up />
                                    @else
                                        <x-gravityui-arrow-right-arrow-left />
                                    @endif
                                </span>
                                <div style="min-width:0;">
                                    <div class="t">{{ $m->folio }}</div>
                                    <div class="s">{{ $fila['fechaVista'] }}</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="t">{{ $fila['nombre'] }}</div>
                            <div class="s">{{ ucfirst($m->item_type) }} · {{ ucfirst($fila['condicion']) }}</div>
                        </td>
                        <td style="white-space:nowrap;">{{ $m->quantity }} {{ $m->unit }}</td>
                        <td>{{ $fila['almacen'] }}</td>
                        <td>{{ $fila['quien'] }}</td>
                        <td style="text-align:right; white-space:nowrap;">
                            @include('structure.gestion_Inventario.entrada_salida._acciones', ['movimiento' => $m])
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">
                            <div class="empty-state">
                                <span class="ico">
                                    <x-gravityui-box />
                                </span>
                                <h3>Todavía no hay movimientos</h3>
                                <p>Registra la primera entrada y aparecerá aquí con su evidencia.</p>
                                <a href="{{ route('inventory.movimientos.create') }}" class="btn">Nueva entrada</a>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- ===================== Vista tarjetas ===================== --}}
    <div class="data-cards" data-view-cards style="display:none;">
        @forelse ($filas as $fila)
            @php $m = $fila['modelo']; @endphp

            <article class="data-card f-row" @foreach ($datos($fila) as $attr => $valor) {{ $attr }}="{{ $valor }}" @endforeach>
                <div class="data-card-top">
                    <span class="mv-ico {{ $fila['tipo'] }}">
                        @if ($fila['tipo'] === 'entrada')
                            <x-gravityui-arrow-down />
                        @elseif ($fila['tipo'] === 'salida')
                            <x-gravityui-arrow-up />
                        @else
                            <x-gravityui-arrow-right-arrow-left />
                        @endif
                    </span>
                    <div style="min-width:0; flex:1;">
                        <div class="t">{{ $m->folio }}</div>
                        <div class="s">{{ $fila['fechaVista'] }}</div>
                    </div>
                    <span class="badge {{ $tono[$fila['tipo']] ?? '' }}">{{ $fila['tipoLabel'] }}</span>
                </div>

                <dl>
                    <div><dt>Equipo</dt><dd>{{ $fila['nombre'] }}</dd></div>
                    <div><dt>Cantidad</dt><dd>{{ $m->quantity }} {{ $m->unit }}</dd></div>
                    <div><dt>Condición</dt><dd>{{ ucfirst($fila['condicion']) }}</dd></div>
                    <div><dt>Almacén</dt><dd>{{ $fila['almacen'] }}</dd></div>
                    <div><dt>Registró</dt><dd>{{ $fila['quien'] }}</dd></div>
                </dl>

                <div class="data-card-foot">
                    @include('structure.gestion_Inventario.entrada_salida._acciones', ['movimiento' => $m])
                </div>
            </article>
        @empty
            <div class="card">
                <div class="empty-state">
                    <span class="ico">
                        <x-gravityui-box />
                    </span>
                    <h3>Todavía no hay movimientos</h3>
                    <p>Registra la primera entrada y aparecerá aquí con su evidencia.</p>
                    <a href="{{ route('inventory.movimientos.create') }}" class="btn">Nueva entrada</a>
                </div>
            </div>
        @endforelse
    </div>

    {{-- Aviso cuando los filtros no dejan nada visible --}}
    <div class="card" id="fVacio" hidden>
        <div class="empty-state">
            <span class="ico">
                <x-gravityui-magnifier />
            </span>
            <h3>Ningún movimiento coincide</h3>
            <p>Prueba a quitar algún filtro o a cambiar la búsqueda.</p>
            <button type="button" class="btn" data-limpiar-filtros>Limpiar filtros</button>
        </div>
    </div>

    <p class="f-conteo" id="fConteo"></p>

    @include('partials._paginacion', ['paginator' => $movements])

    {{-- ===================== Eliminar ===================== --}}
    <dialog id="modalEliminar" class="mv-modal">
        <form method="POST" action="" id="formEliminar">
            @csrf
            @method('DELETE')

            <div class="mv-modal-ico">
                <x-gravityui-triangle-exclamation />
>>>>>>> Stashed changes
            </div>

            <a href="{{ route('inventory.movimientos.create') }}" class="movement-create">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M12 5v14"></path>
                    <path d="M5 12h14"></path>
                </svg>
                Nueva Entrada
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
                <label for="movement-type">Estado</label>
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
                            <th>Estado</th>
                            <th>Folio</th>
                            <th>Almacen</th>
                            <th>Equipo/Producto</th>
                            <th>Cantidad</th>
                            <th>Nombre Equipo/Producto</th>
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
                                <td>{{ ucfirst($movement->item_type) }}</td>
                                <td>{{ $movement->quantity }} {{ $movement->unit }}</td>
                                <td>{{ $movement->item_name }}</td>
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
                                            @if ($movement->movement_type === 'entrada')
                                                <li>
                                                    <button type="button" class="action-link delete-movement-btn" data-url="{{ route('inventory.movimientos.destroy', $movement) }}" data-folio="{{ $movement->folio }}" style="display:flex; align-items:center; gap:8px; width:100%; padding:9px 14px; color:var(--danger); background:none; border:none; cursor:pointer; font-size:13px; font-weight:600; text-align:left;">
                                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                                        Eliminar
                                                    </button>
                                                </li>
                                            @endif
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
                <span id="movementCount">Mostrando {{ count($movements) }} movimiento{{ count($movements) === 1 ? '' : 's' }} de esta página</span>
            </div>
            @include('partials._paginacion', ['paginator' => $movements])
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
                    <label for="deletePassword" style="display:block; margin:0 0 6px; font-size:13px; font-weight:700; color:var(--text);">PIN</label>
                    <input id="deletePassword" name="password" type="password" inputmode="numeric" required placeholder="Ingresa tu PIN" style="width:100%; padding:11px 12px; border:1px solid var(--border); border-radius:9px; font-size:15px; background:var(--surface); color:var(--text);">
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
