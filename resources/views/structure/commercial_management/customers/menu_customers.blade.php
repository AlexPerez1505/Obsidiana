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
        $categorias = $customers->pluck('tipoEquipo.name')->filter()->unique()->sort()->values();
        $estadosMx = $customers->pluck('estado')->filter()->unique()->sort()->values();
        // Congresos: los manda el controlador (todos los del sistema); si no
        // llegaran, se cae a los que aparecen en los clientes.
        $congresos = collect($congresos ?? $customers->pluck('congress.nombre'))->filter()->unique()->sort()->values();
    @endphp

    @if (request()->query('from') === 'dashboard')
        <x-ui.page-header title="Clientes" :back="route('dashboard')" />
    @endif

    <div class="content-actions">
        @can('clientes.crear')
        <a href="{{ route('commercial.clientes.create') }}" class="btn">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="15" height="15"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Registrar cliente
        </a>
        @endcan
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
            <input type="text" id="fBuscar" placeholder="Buscar por nombre, correo, telefono, direccion o asesor" autocomplete="off">
        </div>

        {{-- Panel principal de filtros --}}
        <div class="flt" data-flt>
            <button type="button" class="flt-btn" data-flt-toggle aria-expanded="false">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
                Filtros
                <span class="flt-count" data-flt-count hidden>0</span>
            </button>

            <div class="flt-panel" data-flt-panel hidden>
                {{-- Quien solo ve sus propios clientes no necesita filtrar por asesor. --}}
                @if (($veTodos ?? true) && $asesores->isNotEmpty())
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
                        <h4>Tipo de equipo</h4>
                        @foreach ($categorias as $nombreCategoria)
                            <label class="flt-opt">
                                <span class="flt-dot c{{ (crc32($nombreCategoria) % 5) + 1 }}"></span>
                                <span class="flt-opt-txt">{{ $nombreCategoria }}</span>
                                <input type="checkbox" data-f="categoria" value="{{ $nombreCategoria }}">
                            </label>
                        @endforeach
                    </div>
                @endif

                @if ($estadosMx->isNotEmpty())
                    <div class="flt-group">
                        <h4>Estado</h4>
                        @foreach ($estadosMx as $nombreEstado)
                            <label class="flt-opt">
                                <span class="flt-opt-txt">{{ $nombreEstado }}</span>
                                <input type="checkbox" data-f="edo" value="{{ $nombreEstado }}">
                            </label>
                        @endforeach
                    </div>
                @endif

                <div class="flt-group">
                    <h4>Etapa</h4>
                    <label class="flt-opt">
                        <span class="flt-opt-txt">Clientes</span>
                        <input type="checkbox" data-f="etapa" value="cliente">
                    </label>
                    <label class="flt-opt">
                        <span class="flt-opt-txt">Prospectos</span>
                        <input type="checkbox" data-f="etapa" value="prospecto">
                    </label>
                    <label class="flt-opt">
                        <span class="flt-opt-txt">Con seguimiento pendiente</span>
                        <input type="checkbox" data-f="pref" value="seguimiento">
                    </label>
                </div>

                @if ($congresos->isNotEmpty())
                    <div class="flt-group">
                        <h4>Congreso de origen</h4>
                        @foreach ($congresos as $nombreCongreso)
                            <label class="flt-opt">
                                <span class="flt-opt-txt">{{ $nombreCongreso }}</span>
                                <input type="checkbox" data-f="congreso" value="{{ $nombreCongreso }}">
                            </label>
                        @endforeach
                        <label class="flt-opt">
                            <span class="flt-opt-txt">Sin congreso</span>
                            <input type="checkbox" data-f="congreso" value="">
                        </label>
                    </div>
                @endif

                {{-- La dirección es texto libre: se filtra por "contiene"
                     (una colonia, una ciudad, una calle). --}}
                <div class="flt-group">
                    <h4>Dirección</h4>
                    <div class="flt-fechas">
                        <input type="text" data-f-texto="direccion" placeholder="Ciudad, colonia o calle" aria-label="Filtrar por dirección" autocomplete="off">
                    </div>
                </div>

                <div class="flt-group">
                    <h4>Rango de fechas</h4>
                    <div class="flt-fechas">
                        <input type="date" data-f="desde" aria-label="Alta desde">
                        <input type="date" data-f="hasta" aria-label="Alta hasta">
                    </div>
                </div>
            </div>
        </div>

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
                'categoria' => $customer->tipoEquipo?->name ?? 'Sin tipo de equipo',
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
                'data-buscar' => mb_strtolower($fila['nombre'] . ' ' . ($fila['modelo']->gmail ?? '') . ' ' . ($fila['modelo']->telefono ?? '') . ' ' . ($fila['modelo']->direccion ?? '') . ' ' . $fila['congreso'] . ' ' . $fila['asesor']),
                'data-direccion' => mb_strtolower($fila['modelo']->direccion ?? ''),
                'data-etapa' => $fila['modelo']->etapa ?: 'cliente',
                'data-seguimiento' => $fila['modelo']->proximoSeguimiento() ? '1' : '0',
                'data-asesor' => $fila['asesor'],
                'data-categoria' => $fila['categoria'],
                'data-edo' => $fila['modelo']->estado ?? '',
                'data-congreso' => $fila['congreso'],
                'data-promo' => $fila['promo'],
                'data-activo' => $fila['activo'],
                'data-correo' => $fila['modelo']->gmail ? '1' : '0',
                'data-telefono' => $fila['modelo']->telefono ? '1' : '0',
                'data-fecha' => $fila['fecha'],
            ];
        };
    @endphp

    @php
        // Botón de la columna "Seguimiento": lo próximo a la vista; el clic abre el historial completo.
        $botonSeguimiento = function ($c) {
            $sig = $c->proximoSeguimiento();
            $total = $c->seguimientos->count();
            $clase = $sig ? ($sig->vencido() ? 'is-vencido' : ($sig->esHoy() ? 'is-hoy' : 'is-prox')) : 'is-nada';
            $texto = $sig ? $sig->tipoLabel() : 'Sin seguimiento';
            $sub = $sig ? $sig->cuando() : ($total ? 'Ver historial' : 'Nada registrado');

            return '<button type="button" class="sg-col '.$clase.'" data-seg-historial="'.e(route('commercial.clientes.seguimientos.historial', $c)).'" data-seg-nombre="'.e(trim($c->nombre.' '.$c->apellido)).'"'
                .' aria-label="Ver el historial de seguimiento de '.e(trim($c->nombre.' '.$c->apellido)).'">'
                .'<span class="sg-col-dot"></span><span class="sg-col-txt"><b>'.e($texto).'</b><small>'.e($sub).'</small></span>'
                .($total ? '<span class="sg-col-n" title="Seguimientos registrados">'.$total.'</span>' : '')
                .'</button>';
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
                    <th>Direccion</th>
                    <th>Asesor</th>
                    <th>Seguimiento</th>
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
                                    <div class="t">
                                        {{ $fila['nombre'] }}
                                        @if ($fila['modelo']->esProspecto())
                                            <span class="badge badge--info" style="margin-left:6px; font-size:10.5px;">Prospecto</span>
                                        @endif
                                    </div>
                                    <div class="s">{{ $fila['categoria'] }}</div>
                                </div>
                            </div>
                        </td>
                        <td>{{ $fila['modelo']->telefono ?: '—' }}</td>
                        <td>{{ $fila['modelo']->gmail ?: '—' }}</td>
                        <td style="max-width:220px; white-space:normal;">{{ $fila['modelo']->direccion ?: '—' }}</td>
                        <td>{{ $fila['asesor'] }}</td>
                        <td>{!! $botonSeguimiento($fila['modelo']) !!}</td>
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
                                    {{-- No se borra: está ligado a ventas y servicios. Solo se desactiva. --}}
                                    <form method="POST" action="{{ route('commercial.clientes.toggleActivo', $fila['modelo']) }}"
                                          data-confirm="{{ $fila['modelo']->activo ? '¿Desactivar a '.$fila['nombre'].'? Dejará de aparecer en las listas pero conserva su historial.' : '¿Reactivar a '.$fila['nombre'].'?' }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" role="menuitem" @disabled(auth()->user()->cannot('clientes.editar'))>
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="9"/><path d="M8 12h8"/></svg>
                                            {{ $fila['modelo']->activo ? 'Desactivar' : 'Reactivar' }}
                                        </button>
                                    </form>
                                    {{-- Eliminar de verdad: el servidor solo lo permite si el cliente no tiene historial. --}}
                                    <form method="POST" action="{{ route('commercial.clientes.destroy', $fila['modelo']) }}"
                                          data-confirm="¿Eliminar a {{ $fila['nombre'] }} para siempre? Solo se puede si no tiene ventas, cotizaciones ni servicios.">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="es-danger" role="menuitem" @disabled(auth()->user()->cannot('clientes.eliminar'))>
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 6h18M8 6V4a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2m3 0v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/></svg>
                                            Eliminar
                                        </button>
                                    </form>
                                    {{-- Borra TODO el historial también: pide dos confirmaciones. --}}
                                    <form method="POST" action="{{ route('commercial.clientes.destroyDefinitivo', $fila['modelo']) }}" data-definitivo="{{ $fila['nombre'] }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="es-danger" role="menuitem" @disabled(auth()->user()->cannot('clientes.eliminar_definitivo'))>
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 9v4m0 4h.01M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"/></svg>
                                            Eliminar con historial
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8">
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
                    <div><dt>Direccion</dt><dd>{{ $fila['modelo']->direccion ?: '—' }}</dd></div>
                    <div><dt>Congreso</dt><dd>{{ $fila['congreso'] ?: '—' }}</dd></div>
                    <div><dt>Asesor</dt><dd>{{ $fila['asesor'] }}</dd></div>
                    <div><dt>Seguimiento</dt><dd>{!! $botonSeguimiento($fila['modelo']) !!}</dd></div>
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
                            {{-- No se borra: está ligado a ventas y servicios. Solo se desactiva. --}}
                            <form method="POST" action="{{ route('commercial.clientes.toggleActivo', $fila['modelo']) }}"
                                  data-confirm="{{ $fila['modelo']->activo ? '¿Desactivar a '.$fila['nombre'].'? Dejará de aparecer en las listas pero conserva su historial.' : '¿Reactivar a '.$fila['nombre'].'?' }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit" role="menuitem" @disabled(auth()->user()->cannot('clientes.editar'))>
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="9"/><path d="M8 12h8"/></svg>
                                    {{ $fila['modelo']->activo ? 'Desactivar' : 'Reactivar' }}
                                </button>
                            </form>
                            {{-- Eliminar de verdad: el servidor solo lo permite si el cliente no tiene historial. --}}
                            <form method="POST" action="{{ route('commercial.clientes.destroy', $fila['modelo']) }}"
                                  data-confirm="¿Eliminar a {{ $fila['nombre'] }} para siempre? Solo se puede si no tiene ventas, cotizaciones ni servicios.">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="es-danger" role="menuitem" @disabled(auth()->user()->cannot('clientes.eliminar'))>
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 6h18M8 6V4a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2m3 0v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/></svg>
                                    Eliminar
                                </button>
                            </form>
                            {{-- Borra TODO el historial también: pide dos confirmaciones. --}}
                            <form method="POST" action="{{ route('commercial.clientes.destroyDefinitivo', $fila['modelo']) }}" data-definitivo="{{ $fila['nombre'] }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="es-danger" role="menuitem" @disabled(auth()->user()->cannot('clientes.eliminar_definitivo'))>
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 9v4m0 4h.01M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"/></svg>
                                    Eliminar con historial
                                </button>
                            </form>
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


    {{-- ===================== Historial de seguimiento (modal) ===================== --}}
    <dialog class="sgm" id="sgModal" aria-labelledby="sgmTitulo">
        <div class="sgm-caja">
            <header class="sgm-cab">
                <div style="min-width:0">
                    <span class="sgm-k">Seguimiento</span>
                    <h3 id="sgmTitulo">Cliente</h3>
                    <p class="sgm-sub" id="sgmSub"></p>
                </div>
                <button type="button" class="sgm-x" data-sgm-cerrar aria-label="Cerrar">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M6 6l12 12M18 6L6 18"/></svg>
                </button>
            </header>
            <div class="sgm-res" id="sgmResumen"></div>
            <div class="sgm-cuerpo" id="sgmCuerpo" aria-live="polite"></div>
            <footer class="sgm-pie">
                <a class="btn btn--ghost" id="sgmAgenda" href="{{ route('agenda.index') }}" @cannot('agenda.ver') hidden @endcannot>Abrir agenda</a>
                <a class="btn" id="sgmFicha" href="#">Ir a la ficha</a>
            </footer>
        </div>
    </dialog>

    <style>
        /* ===== Columna Seguimiento ===== */
        .sg-col { display:flex; align-items:center; gap:9px; width:100%; min-width:170px; max-width:240px; padding:6px 9px; border:1px solid var(--border); border-radius:10px; background:var(--surface); cursor:pointer; text-align:left; font:inherit; color:var(--text); transition: border-color .15s, background .15s, box-shadow .15s; }
        .sg-col:hover { border-color: color-mix(in srgb, var(--primary) 45%, var(--border)); box-shadow:0 2px 8px rgba(15,23,42,.06); }
        .sg-col:focus-visible { outline:2px solid var(--primary); outline-offset:2px; }
        .sg-col-dot { width:8px; height:8px; border-radius:50%; flex:0 0 8px; background:var(--muted); }
        .sg-col-txt { display:flex; flex-direction:column; min-width:0; flex:1; line-height:1.25; }
        .sg-col-txt b { font-size:13px; font-weight:600; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
        .sg-col-txt small { font-size:11.5px; color:var(--muted); overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
        .sg-col-n { min-width:20px; height:20px; padding:0 6px; border-radius:10px; background:var(--surface-2); color:var(--muted); font-size:11px; font-weight:700; display:grid; place-items:center; }
        .sg-col.is-vencido .sg-col-dot { background:var(--danger); }
        .sg-col.is-vencido small { color:var(--danger); font-weight:600; }
        .sg-col.is-hoy .sg-col-dot { background:var(--accent); }
        .sg-col.is-hoy small { color:var(--accent); font-weight:600; }
        .sg-col.is-prox .sg-col-dot { background:var(--primary); }
        .sg-col.is-nada { border-style:dashed; }
        .sg-col.is-nada b { color:var(--muted); font-weight:500; }

        /* ===== Modal de historial ===== */
        .sgm { width:min(640px, calc(100vw - 24px)); max-height:min(86vh, 820px); padding:0; border:0; border-radius:16px; background:var(--surface); color:var(--text); box-shadow:0 24px 60px -16px rgba(15,23,42,.35); overflow:hidden; }
        .sgm::backdrop { background:rgba(15,23,42,.38); }
        .sgm[open] { animation: sgm-entra .2s cubic-bezier(.2,.7,.2,1); }
        @keyframes sgm-entra { from { opacity:0; transform: translateY(8px) scale(.985); } to { opacity:1; transform:none; } }
        .sgm-caja { display:flex; flex-direction:column; max-height:inherit; }
        .sgm-cab { display:flex; align-items:flex-start; gap:12px; padding:18px 20px 12px; border-bottom:1px solid var(--border); }
        .sgm-k { font-size:11px; font-weight:700; letter-spacing:.06em; text-transform:uppercase; color:var(--primary); }
        .sgm-cab h3 { margin:2px 0 0; font-size:18px; font-weight:700; letter-spacing:-.01em; }
        .sgm-sub { margin:3px 0 0; font-size:12.5px; color:var(--muted); }
        .sgm-x { margin-left:auto; width:34px; height:34px; border:0; border-radius:9px; background:none; color:var(--muted); display:grid; place-items:center; cursor:pointer; flex:0 0 34px; }
        .sgm-x:hover { background:var(--surface-2); color:var(--text); }
        .sgm-x svg { width:18px; height:18px; }
        .sgm-res { display:flex; flex-wrap:wrap; gap:6px; padding:10px 20px; border-bottom:1px solid var(--border); background: color-mix(in srgb, var(--surface-2) 60%, var(--surface)); }
        .sgm-res:empty { display:none; }
        .sgm-chip { display:inline-flex; align-items:center; gap:6px; height:24px; padding:0 10px; border-radius:12px; font-size:12px; font-weight:600; background:var(--surface); border:1px solid var(--border); color:var(--muted); }
        .sgm-chip b { color:var(--text); }
        .sgm-chip.is-alerta { background:var(--danger-soft); border-color:transparent; color:var(--danger); }
        .sgm-chip.is-alerta b { color:var(--danger); }
        .sgm-cuerpo { flex:1; overflow:auto; padding:6px 20px 16px; }
        .sgm-sec { margin:14px 0 6px; font-size:11.5px; font-weight:700; letter-spacing:.05em; text-transform:uppercase; color:var(--muted); }
        .sgm-tl { position:relative; margin:0; padding:0; list-style:none; }
        .sgm-tl::before { content:''; position:absolute; left:8px; top:8px; bottom:8px; width:2px; background:var(--border); border-radius:1px; }
        .sgm-it { position:relative; padding:0 0 14px 30px; }
        .sgm-it::before { content:''; position:absolute; left:2px; top:5px; width:14px; height:14px; border-radius:50%; background:var(--surface); border:3px solid var(--muted); box-sizing:border-box; }
        .sgm-it.is-pendiente::before { border-color:var(--primary); }
        .sgm-it.is-vencido::before { border-color:var(--danger); }
        .sgm-it.is-completado::before { border-color:var(--green); background:var(--green); }
        .sgm-it.is-cancelado::before { border-color:var(--muted); background:var(--surface-2); }
        .sgm-card { padding:10px 12px; border:1px solid var(--border); border-radius:11px; background:var(--surface); }
        .sgm-it.is-vencido .sgm-card { border-color: color-mix(in srgb, var(--danger) 35%, var(--border)); }
        .sgm-top { display:flex; align-items:center; gap:8px; flex-wrap:wrap; }
        .sgm-top b { font-size:14px; }
        .sgm-cuando { font-size:12.5px; color:var(--muted); }
        .sgm-tag { display:inline-flex; align-items:center; height:20px; padding:0 8px; border-radius:10px; font-size:11px; font-weight:650; background:var(--surface-2); color:var(--muted); }
        .sgm-tag.is-pendiente, .sgm-tag.is-en_proceso { background:var(--primary-soft); color:var(--primary); }
        .sgm-tag.is-vencido { background:var(--danger-soft); color:var(--danger); }
        .sgm-tag.is-completado { background:var(--green-soft); color:var(--green); }
        .sgm-tag.is-alta { background:var(--accent-soft); color: color-mix(in srgb, var(--accent) 85%, var(--text)); }
        .sgm-txt { margin:6px 0 0; font-size:13px; line-height:1.45; overflow-wrap:anywhere; }
        .sgm-txt small { color:var(--muted); font-weight:600; }
        .sgm-meta { margin-top:6px; font-size:12px; color:var(--muted); display:flex; flex-wrap:wrap; gap:4px 12px; }
        .sgm-meta a { color:var(--primary); font-weight:600; text-decoration:none; }
        .sgm-cambios { margin-top:6px; }
        .sgm-cambios summary { cursor:pointer; font-size:12px; font-weight:600; color:var(--primary); list-style:none; }
        .sgm-cambios summary::-webkit-details-marker { display:none; }
        .sgm-cambios ul { margin:6px 0 0; padding:0 0 0 12px; border-left:2px solid var(--border); list-style:none; font-size:12px; }
        .sgm-cambios li { padding:3px 0; }
        .sgm-cambios li span { color:var(--muted); }
        .sgm-vacio { padding:34px 10px; text-align:center; color:var(--muted); }
        .sgm-vacio b { display:block; color:var(--text); font-size:14.5px; margin-bottom:4px; }
        .sgm-sk { height:64px; border-radius:11px; margin:12px 0 0; background: linear-gradient(90deg, var(--surface-2) 25%, color-mix(in srgb, var(--border) 50%, var(--surface)) 50%, var(--surface-2) 75%); background-size:200% 100%; animation: sgm-brillo 1.2s ease-in-out infinite; }
        @keyframes sgm-brillo { to { background-position:-200% 0; } }
        .sgm-pie { display:flex; justify-content:flex-end; gap:8px; padding:12px 20px; border-top:1px solid var(--border); }
        @media (max-width: 640px) {
            .sgm { width:100vw; max-width:100vw; max-height:92vh; margin:auto 0 0; border-radius:18px 18px 0 0; }
            .sgm-cab, .sgm-cuerpo, .sgm-res, .sgm-pie { padding-left:16px; padding-right:16px; }
        }
        @media (prefers-reduced-motion: reduce) { .sgm[open], .sgm-sk { animation:none; } }
    </style>

    <script>
    (function () {
        const modal = document.getElementById('sgModal');
        if (!modal || typeof modal.showModal !== 'function') return;
        const esc = (t) => String(t ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
        const cuerpo = document.getElementById('sgmCuerpo');
        let pedido = 0;

        function item(s) {
            const clase = s.vencido ? 'is-vencido' : (s.estado === 'en_proceso' ? 'is-pendiente' : 'is-' + s.estado);
            const estado = s.vencido ? '<span class="sgm-tag is-vencido">Vencido</span>' : '<span class="sgm-tag is-' + esc(s.estado) + '">' + esc(s.estado_label) + '</span>';
            const meta = [];
            if (s.responsable) meta.push('Responsable: ' + esc(s.responsable));
            if (s.estado === 'completado' && s.hecho_en) meta.push('Hecho el ' + esc(s.hecho_en) + (s.hecho_por ? ' por ' + esc(s.hecho_por) : ''));
            if (s.estado === 'cancelado' && s.cancelado_en) meta.push('Cancelado el ' + esc(s.cancelado_en));
            if (s.creado_en) meta.push('Programado el ' + esc(s.creado_en) + (s.creado_por ? ' por ' + esc(s.creado_por) : '') + (s.origen === 'agenda' ? ' desde la agenda' : ''));
            if (s.agenda) meta.push('<a href="' + esc(s.agenda) + '">Ver en agenda</a>');
            const cambios = (s.cambios || []).filter((c) => c.accion !== 'Creó');
            return '<li class="sgm-it ' + clase + '"><div class="sgm-card">'
                + '<div class="sgm-top"><b>' + esc(s.tipo) + '</b>' + estado + (s.prioridad === 'alta' ? '<span class="sgm-tag is-alta">Prioridad alta</span>' : '')
                + '<span class="sgm-cuando">' + esc(s.fecha) + (s.hora ? ' · ' + esc(s.hora) : ' · sin hora') + (s.abierto ? ' · ' + esc(s.cuando) : '') + '</span></div>'
                + (s.nota ? '<p class="sgm-txt"><small>Motivo:</small> ' + esc(s.nota) + '</p>' : '')
                + (s.resultado ? '<p class="sgm-txt"><small>Resultado:</small> ' + esc(s.resultado) + '</p>' : '')
                + (s.motivo_cancelacion ? '<p class="sgm-txt"><small>Motivo de cancelación:</small> ' + esc(s.motivo_cancelacion) + '</p>' : '')
                + '<div class="sgm-meta">' + meta.map((m) => '<span>' + m + '</span>').join('') + '</div>'
                + (cambios.length ? '<details class="sgm-cambios"><summary>Cambios (' + cambios.length + ')</summary><ul>'
                    + cambios.map((c) => '<li><b>' + esc(c.accion) + '</b> · ' + esc(c.usuario) + ' <span>' + esc(c.cuando) + '</span>'
                        + (c.detalle && c.detalle.length ? '<br><span>' + c.detalle.map(esc).join(' · ') + '</span>' : '')
                        + (c.motivo ? '<br><span>Motivo: ' + esc(c.motivo) + '</span>' : '') + '</li>').join('') + '</ul></details>' : '')
                + '</div></li>';
        }

        async function abrir(boton) {
            const id = ++pedido;
            document.getElementById('sgmTitulo').textContent = boton.dataset.segNombre || 'Cliente';
            document.getElementById('sgmSub').textContent = '';
            document.getElementById('sgmResumen').innerHTML = '';
            cuerpo.innerHTML = '<div class="sgm-sk"></div><div class="sgm-sk"></div><div class="sgm-sk"></div>';
            modal.showModal();
            try {
                const r = await fetch(boton.dataset.segHistorial, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' });
                if (!r.ok) throw new Error(r.status === 403 ? 'No tienes acceso al seguimiento de este cliente.' : 'No se pudo cargar el historial.');
                const d = await r.json();
                if (id !== pedido) return;
                const c = d.cliente;
                document.getElementById('sgmTitulo').textContent = c.nombre;
                document.getElementById('sgmSub').textContent = [c.etapa, c.asesor ? 'Asesor: ' + c.asesor : null, c.telefono].filter(Boolean).join(' · ');
                document.getElementById('sgmFicha').href = c.ficha;
                document.getElementById('sgmFicha').textContent = d.puede_programar ? 'Programar en la ficha' : 'Ir a la ficha';
                const res = d.resumen;
                document.getElementById('sgmResumen').innerHTML = [
                    ['Pendientes', res.pendientes, false], ['Vencidos', res.vencidos, res.vencidos > 0],
                    ['Completados', res.completados, false], ['Cancelados', res.cancelados, false],
                ].map(([t, n, alerta]) => '<span class="sgm-chip' + (alerta ? ' is-alerta' : '') + '"><b>' + n + '</b>' + t + '</span>').join('');
                let h = '';
                if (d.pendientes.length) h += '<div class="sgm-sec">Pendientes</div><ul class="sgm-tl">' + d.pendientes.map(item).join('') + '</ul>';
                if (d.historial.length) h += '<div class="sgm-sec">Historial</div><ul class="sgm-tl">' + d.historial.map(item).join('') + '</ul>';
                cuerpo.innerHTML = h || '<div class="sgm-vacio"><b>Sin seguimientos todavía</b>Programa el primero desde la ficha del cliente.</div>';
            } catch (e) {
                if (id !== pedido) return;
                cuerpo.innerHTML = '<div class="sgm-vacio"><b>' + esc(e.message) + '</b>Intenta de nuevo en un momento.</div>';
            }
        }

        document.addEventListener('click', (e) => {
            const b = e.target.closest('[data-seg-historial]');
            if (b) { e.preventDefault(); abrir(b); return; }
            if (e.target.closest('[data-sgm-cerrar]') || e.target === modal) modal.close();
        });
    })();
    </script>

    @include('partials.tabla-filtrable.script', [
        'singular' => 'cliente',
        'plural' => 'clientes',
        'estadoCampo' => 'activo',
        'etiquetas' => [
            'asesor' => 'Asesor',
            'categoria' => 'Tipo de equipo',
            'edo' => 'Estado',
            'congreso' => 'Congreso',
            'congreso:' => 'Sin congreso',
            'direccion' => 'Dirección',
            'etapa' => 'Etapa',
            'seguimiento' => 'Con seguimiento pendiente',
            'promo' => 'Con promoción',
            'correo' => 'Con correo',
            'telefono' => 'Con teléfono',
            'estado:1' => 'Solo activos',
            'estado:0' => 'Solo inactivos',
        ],
    ])

@push('scripts')
<script>
// "Eliminar con historial" pide dos confirmaciones seguidas: la acción
// borra también ventas, cotizaciones, pagos y servicios del cliente.
document.addEventListener('submit', function (e) {
    var form = e.target;
    if (!(form instanceof HTMLFormElement) || !form.hasAttribute('data-definitivo')) return;
    if (form.dataset.cmDone === '1') { form.dataset.cmDone = ''; return; }
    e.preventDefault();
    var nombre = form.getAttribute('data-definitivo');
    window.confirmModal({
        title: 'Eliminar con historial',
        message: '¿Eliminar a ' + nombre + ' junto con TODAS sus ventas, cotizaciones, pagos y servicios? Esta acción no se puede deshacer.',
        confirmText: 'Continuar',
        danger: true
    }).then(function (ok) {
        if (!ok) return;
        // El segundo aviso solo puede abrirse cuando el primero ya se
        // ocultó por completo (~180 ms): si se abre antes, el hidden del
        // primero lo vuelve invisible.
        var espera = setInterval(function () {
            var ov = document.getElementById('cmOverlay');
            if (ov && !ov.hidden) return;
            clearInterval(espera);
            window.confirmModal({
                title: 'Confirmación final',
                message: 'Se borrará también su historial comercial completo. ¿Seguro que quieres eliminar definitivamente a ' + nombre + '?',
                confirmText: 'Sí, eliminar todo',
                danger: true
            }).then(function (seguro) {
                if (!seguro) return;
                form.dataset.cmDone = '1';
                if (form.requestSubmit) { form.requestSubmit(); } else { form.submit(); }
            });
        }, 150);
    });
}, true);
</script>
@endpush
@endsection
