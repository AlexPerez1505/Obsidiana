@extends('structure.commercial_management.layout')

@section('title', 'Clientes')
@section('page-title', 'Clientes')

@section('commercial_content')
    @php
        $total = $customers->count();
        $newCustomers = $customers
            ->filter(fn ($customer) => $customer->created_at?->greaterThanOrEqualTo(now()->startOfMonth()))
            ->count();
        $inactiveCustomers = $customers->where('activo', false)->count();

        // Listas para los filtros: solo se ofrece lo que realmente existe en los datos.
        $asesores = $customers->pluck('asesor.name')->filter()->unique()->sort()->values();
        $categorias = $customers->pluck('category.nombre')->filter()->unique()->sort()->values();
        $congresos = $customers->pluck('congress.nombre')->filter()->unique()->sort()->values();
    @endphp

    <div class="content-actions">
        <a href="{{ route('commercial.clientes.create') }}" class="btn">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="15" height="15"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Registrar cliente
        </a>
    </div>

    {{-- Metricas --}}
    <div class="cl-stats">
        <div class="card card--accent stat">
            <span class="stat-ico blue">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            </span>
            <div>
                <div class="stat-num">{{ $total }}</div>
                <div class="stat-lbl">Total de clientes</div>
            </div>
        </div>

        <div class="card card--accent is-green stat">
            <span class="stat-ico green">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/></svg>
            </span>
            <div>
                <div class="stat-num">{{ $newCustomers }}</div>
                <div class="stat-lbl">Nuevos este mes</div>
            </div>
        </div>

        <div class="card card--accent is-amber stat">
            <span class="stat-ico orange">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            </span>
            <div>
                <div class="stat-num">{{ $inactiveCustomers }}</div>
                <div class="stat-lbl">Inactivos · {{ $total > 0 ? round(($inactiveCustomers / $total) * 100) : 0 }}% del total</div>
            </div>
        </div>
    </div>

    {{-- ===================== Barra de busqueda y filtros ===================== --}}
    <div class="f-toolbar">
        <div class="f-search">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input type="text" id="fBuscar" placeholder="Buscar por nombre, correo, telefono o asesor" autocomplete="off">
        </div>

        {{-- Panel principal de filtros --}}
        <div class="flt" data-flt>
            <button type="button" class="flt-btn" data-flt-toggle aria-expanded="false">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
                Filtros
                <span class="flt-count" data-flt-count hidden>0</span>
            </button>

            <div class="flt-panel" data-flt-panel hidden>
                @if ($asesores->isNotEmpty())
                    <div class="flt-group">
                        <h4>Asignado</h4>
                        @foreach ($asesores as $nombreAsesor)
                            <label class="flt-opt">
                                <span class="flt-opt-txt">{{ $nombreAsesor }}</span>
                                <input type="checkbox" data-f="asesor" value="{{ $nombreAsesor }}">
                            </label>
                        @endforeach
                    </div>
                @endif

                <div class="flt-group">
                    <h4>Preferencias</h4>
                    <label class="flt-opt">
                        <span class="flt-opt-txt">Solo con promocion</span>
                        <input type="checkbox" data-f="pref" value="promo">
                    </label>
                    <label class="flt-opt">
                        <span class="flt-opt-txt">Solo con correo</span>
                        <input type="checkbox" data-f="pref" value="correo">
                    </label>
                    <label class="flt-opt">
                        <span class="flt-opt-txt">Solo con telefono</span>
                        <input type="checkbox" data-f="pref" value="telefono">
                    </label>
                </div>

                @if ($categorias->isNotEmpty())
                    <div class="flt-group">
                        <h4>Categoria</h4>
                        @foreach ($categorias as $nombreCategoria)
                            <label class="flt-opt">
                                <span class="flt-dot c{{ (crc32($nombreCategoria) % 5) + 1 }}"></span>
                                <span class="flt-opt-txt">{{ $nombreCategoria }}</span>
                                <input type="checkbox" data-f="categoria" value="{{ $nombreCategoria }}">
                            </label>
                        @endforeach
                    </div>
                @endif

                <div class="flt-group">
                    <h4>Rango de fechas</h4>
                    <div class="flt-fechas">
                        <input type="date" data-f="desde" aria-label="Alta desde">
                        <input type="date" data-f="hasta" aria-label="Alta hasta">
                    </div>
                </div>
            </div>
        </div>

        {{-- Panel de congresos (equivale a las etiquetas) --}}
        @if ($congresos->isNotEmpty())
            <div class="flt" data-flt>
                <button type="button" class="flt-btn" data-flt-toggle aria-expanded="false">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
                    Congresos
                    <span class="flt-count" data-flt-count hidden>0</span>
                </button>

                <div class="flt-panel" data-flt-panel hidden>
                    <div class="flt-group">
                        <h4>Congreso de origen</h4>
                        @foreach ($congresos as $nombreCongreso)
                            <label class="flt-opt">
                                <span class="flt-opt-txt">{{ $nombreCongreso }}</span>
                                <input type="checkbox" data-f="congreso" value="{{ $nombreCongreso }}">
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        {{-- Accesos rapidos: activos / inactivos --}}
        <div class="flt-toggles" role="group" aria-label="Estado del cliente">
            <button type="button" class="flt-tgl is-on" data-f="estado" data-valor="1" title="Ver clientes activos" aria-pressed="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
            </button>
            <button type="button" class="flt-tgl" data-f="estado" data-valor="0" title="Ver clientes inactivos" aria-pressed="false">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><polyline points="21 8 21 21 3 21 3 8"/><rect x="1" y="3" width="22" height="5"/><line x1="10" y1="12" x2="14" y2="12"/></svg>
            </button>
        </div>

        <button type="button" class="flt-btn flt-btn--icon" id="fLimpiar" title="Limpiar todos los filtros" aria-label="Limpiar filtros">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M22 3H2l8 9.46V19l4 2v-8.54"/><line x1="16" y1="5" x2="22" y2="11"/><line x1="22" y1="5" x2="16" y2="11"/></svg>
        </button>

        <x-ui.view-switch key="clientes" />
    </div>

    {{-- Resumen de lo que esta filtrado --}}
    <div class="flt-chips" id="fChips" hidden></div>

    @php
        $filas = $customers->map(function ($customer) {
            $nombreCompleto = trim($customer->nombre . ' ' . $customer->apellido);
            $partes = array_values(array_filter(explode(' ', $nombreCompleto)));
            $iniciales = count($partes) >= 2
                ? mb_strtoupper(mb_substr($partes[0], 0, 1) . mb_substr($partes[1], 0, 1))
                : (count($partes) === 1 ? mb_strtoupper(mb_substr($partes[0], 0, 2)) : 'CL');

            return [
                'modelo' => $customer,
                'nombre' => $nombreCompleto ?: 'Sin nombre',
                'iniciales' => $iniciales,
                'categoria' => $customer->category?->nombre ?? 'Sin categoria',
                'congreso' => $customer->congress?->nombre ?? '',
                'asesor' => $customer->asesor?->name ?? 'Sin asesor',
                'promo' => $customer->recibe_promocion ? '1' : '0',
                'activo' => $customer->activo ? '1' : '0',
                'fecha' => $customer->created_at?->format('Y-m-d') ?? '',
                // Tinte estable por cliente: mismas iniciales, mismo color siempre.
                'tinte' => 'a' . ((crc32($iniciales) % 5) + 1),
            ];
        });

        // Los mismos atributos alimentan la tabla y las tarjetas, asi que se arman una sola vez.
        $datos = function (array $fila) {
            return [
                'data-buscar' => mb_strtolower($fila['nombre'] . ' ' . ($fila['modelo']->gmail ?? '') . ' ' . ($fila['modelo']->telefono ?? '') . ' ' . $fila['asesor']),
                'data-asesor' => $fila['asesor'],
                'data-categoria' => $fila['categoria'],
                'data-congreso' => $fila['congreso'],
                'data-promo' => $fila['promo'],
                'data-activo' => $fila['activo'],
                'data-correo' => $fila['modelo']->gmail ? '1' : '0',
                'data-telefono' => $fila['modelo']->telefono ? '1' : '0',
                'data-fecha' => $fila['fecha'],
            ];
        };
    @endphp

    {{-- ===================== Vista lista ===================== --}}
    <div class="card" id="clLista" data-view-list style="overflow-x:auto; padding:0;">
        <table class="cl-table">
            <thead>
                <tr>
                    <th>Cliente</th>
                    <th>Telefono</th>
                    <th>Correo</th>
                    <th>Asesor</th>
                    <th>Promocion</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($filas as $fila)
                    <tr class="f-row" @foreach ($datos($fila) as $attr => $valor) {{ $attr }}="{{ $valor }}" @endforeach>
                        <td>
                            <div class="cell-id">
                                <span class="avatar {{ $fila['tinte'] }}">{{ $fila['iniciales'] }}</span>
                                <div style="min-width:0;">
                                    <div class="t">{{ $fila['nombre'] }}</div>
                                    <div class="s">{{ $fila['categoria'] }}</div>
                                </div>
                            </div>
                        </td>
                        <td>{{ $fila['modelo']->telefono ?: '—' }}</td>
                        <td>{{ $fila['modelo']->gmail ?: '—' }}</td>
                        <td>{{ $fila['asesor'] }}</td>
                        <td>
                            <span class="badge {{ $fila['promo'] === '1' ? 'badge--ok' : '' }}">
                                {{ $fila['promo'] === '1' ? 'Si' : 'No' }}
                            </span>
                        </td>
                        <td style="text-align:right; white-space:nowrap;">
                            <div class="row-menu" data-row-menu>
                                <button type="button" class="row-menu-btn" data-row-menu-toggle
                                        aria-haspopup="true" aria-expanded="false"
                                        aria-label="Acciones de {{ $fila['nombre'] }}">
                                    <svg viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="5" r="1.7"/><circle cx="12" cy="12" r="1.7"/><circle cx="12" cy="19" r="1.7"/></svg>
                                </button>
                                <div class="row-menu-pop" data-row-menu-pop role="menu" hidden>
                                    <a href="{{ route('commercial.clientes.show', $fila['modelo']) }}" role="menuitem">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                                        Ver detalle
                                    </a>
                                    <a href="{{ route('commercial.clientes.edit', $fila['modelo']) }}" role="menuitem">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                                        Editar
                                    </a>
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">
                            <div class="empty-state">
                                <span class="ico">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                                </span>
                                <h3>Aún no hay clientes</h3>
                                <p>Registra el primero y aparecerá en esta lista.</p>
                                <a href="{{ route('commercial.clientes.create') }}" class="btn">Registrar cliente</a>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- ===================== Vista tarjetas ===================== --}}
    <div class="data-cards" id="clTarjetas" data-view-cards style="display:none;">
        @forelse ($filas as $fila)
            <article class="data-card f-row" @foreach ($datos($fila) as $attr => $valor) {{ $attr }}="{{ $valor }}" @endforeach>
                <div class="data-card-top">
                    <span class="avatar {{ $fila['tinte'] }}">{{ $fila['iniciales'] }}</span>
                    <div style="min-width:0; flex:1;">
                        <div class="t">{{ $fila['nombre'] }}</div>
                        <div class="s">{{ $fila['categoria'] }}</div>
                    </div>
                    @if ($fila['promo'] === '1')
                        <span class="badge badge--ok">Promo</span>
                    @endif
                </div>

                <dl>
                    <div><dt>Telefono</dt><dd>{{ $fila['modelo']->telefono ?: '—' }}</dd></div>
                    <div><dt>Correo</dt><dd>{{ $fila['modelo']->gmail ?: '—' }}</dd></div>
                    <div><dt>Asesor</dt><dd>{{ $fila['asesor'] }}</dd></div>
                    <div><dt>Promocion</dt><dd>{{ $fila['promo'] === '1' ? 'Si' : 'No' }}</dd></div>
                </dl>

                <div class="data-card-foot">
                    <div class="row-menu" data-row-menu>
                        <button type="button" class="row-menu-btn" data-row-menu-toggle
                                aria-haspopup="true" aria-expanded="false"
                                aria-label="Acciones de {{ $fila['nombre'] }}">
                            <svg viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="5" r="1.7"/><circle cx="12" cy="12" r="1.7"/><circle cx="12" cy="19" r="1.7"/></svg>
                        </button>
                        <div class="row-menu-pop" data-row-menu-pop role="menu" hidden>
                            <a href="{{ route('commercial.clientes.show', $fila['modelo']) }}" role="menuitem">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                                Ver detalle
                            </a>
                            <a href="{{ route('commercial.clientes.edit', $fila['modelo']) }}" role="menuitem">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                                Editar
                            </a>
                        </div>
                    </div>
                </div>
            </article>
        @empty
            <div class="card">
                <div class="empty-state">
                    <span class="ico">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                    </span>
                    <h3>Aún no hay clientes</h3>
                    <p>Registra el primero y aparecerá aquí.</p>
                    <a href="{{ route('commercial.clientes.create') }}" class="btn">Registrar cliente</a>
                </div>
            </div>
        @endforelse
    </div>

    {{-- Aviso cuando los filtros no dejan nada visible --}}
    <div class="card" id="fVacio" hidden>
        <div class="empty-state">
            <span class="ico">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            </span>
            <h3>Ningún cliente coincide</h3>
            <p>Prueba a quitar algún filtro o a cambiar la búsqueda.</p>
            <button type="button" class="btn" data-limpiar-filtros>Limpiar filtros</button>
        </div>
    </div>

    <p class="f-conteo" id="fConteo"></p>

    <style>
        /* Solo lo propio de esta pantalla: lo demás vive en los partials. */
        .cl-stats { display:grid; grid-template-columns:repeat(auto-fit,minmax(210px,1fr)); gap:12px; margin-bottom:16px; }
        .cl-table { width:100%; border-collapse:collapse; }
    </style>

    @include('partials.tabla-filtrable.estilos')

    @include('partials.tabla-filtrable.script', [
        'singular' => 'cliente',
        'plural' => 'clientes',
        'estadoCampo' => 'activo',
        'etiquetas' => [
            'asesor' => 'Asesor',
            'categoria' => 'Categoría',
            'congreso' => 'Congreso',
            'promo' => 'Con promoción',
            'correo' => 'Con correo',
            'telefono' => 'Con teléfono',
            'estado:1' => 'Solo activos',
            'estado:0' => 'Solo inactivos',
        ],
    ])
@endsection
