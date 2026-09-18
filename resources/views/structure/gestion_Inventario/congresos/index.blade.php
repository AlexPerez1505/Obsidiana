@extends('layouts.dashboard')

@section('title', 'Congresos')
@section('page-title', 'Congresos')
@section('page-sub', 'Gestiona y consulta todos los congresos, revisa los productos y usuarios asignados')

@push('head')
<style>
    .cgx-actions { display:flex; justify-content:flex-end; margin-bottom:18px; }

    .cgx-grid { display:grid; grid-template-columns:minmax(0,1.3fr) minmax(0,1fr); gap:20px; align-items:start; }
    @media (max-width:1024px) { .cgx-grid { grid-template-columns:1fr; } }

    .cgx-cal-head { display:flex; align-items:center; justify-content:space-between; margin-bottom:16px; }
    .cgx-cal-nav { display:flex; align-items:center; gap:10px; }
    .cgx-cal-nav a { display:flex; align-items:center; justify-content:center; width:30px; height:30px;
                     border:1px solid var(--border); border-radius:8px; color:var(--text); text-decoration:none; }
    .cgx-cal-nav a:hover { border-color:var(--primary); color:var(--primary); }
    .cgx-cal-nav a svg { width:15px; height:15px; }
    .cgx-cal-mes { font-size:15px; font-weight:700; min-width:150px; text-align:center; text-transform:capitalize; }

    .cgx-cal { display:grid; grid-template-columns:repeat(7, 1fr); gap:4px; }
    .cgx-cal-dia-nombre { text-align:center; font-size:11.5px; font-weight:700; color:var(--muted);
                          text-transform:uppercase; padding-bottom:6px; }
    .cgx-cal-cell { min-height:70px; border-radius:9px; border:1px solid var(--border); padding:5px;
                    display:flex; flex-direction:column; gap:3px; background:var(--surface); }
    .cgx-cal-cell.atenuado { opacity:.4; }
    .cgx-cal-cell.hoy { border-color:var(--primary); box-shadow:0 0 0 1px var(--primary) inset; }
    .cgx-cal-num { font-size:12px; font-weight:600; color:var(--muted); }
    .cgx-cal-evt { display:block; font-size:10.5px; font-weight:600; color:#fff; padding:2px 5px;
                   border-radius:5px; text-decoration:none; overflow:hidden; text-overflow:ellipsis;
                   white-space:nowrap; }
    .cgx-cal-evt.is-actual { outline:2px solid var(--text); outline-offset:1px; }

    .cgx-prox-table { width:100%; font-size:13px; border-collapse:collapse; }
    .cgx-prox-table th { text-align:left; color:var(--muted); font-size:11.5px; font-weight:700;
                         text-transform:uppercase; padding:6px 8px; border-bottom:1px solid var(--border); }
    .cgx-prox-table td { padding:9px 8px; border-bottom:1px solid var(--border); vertical-align:middle; }
    .cgx-prox-table tr:last-child td { border-bottom:none; }
    .cgx-prox-dot { display:inline-block; width:8px; height:8px; border-radius:50%; margin-right:7px; flex:0 0 auto; }
    .cgx-prox-nombre { display:flex; align-items:center; }
    .cgx-prox-nombre a { color:var(--text); text-decoration:none; font-weight:600; }
    .cgx-prox-nombre a:hover { color:var(--primary); }

    .cgx-detalle-head { display:flex; align-items:flex-start; gap:12px; margin-bottom:14px; }
    .cgx-detalle-ico { width:42px; height:42px; border-radius:11px; background:var(--primary-soft);
                       color:var(--primary); display:flex; align-items:center; justify-content:center; flex:0 0 auto; }
    .cgx-detalle-ico svg { width:20px; height:20px; }
    .cgx-detalle-nombre { font-size:16px; font-weight:800; margin:0; display:flex; align-items:center; gap:8px; flex-wrap:wrap; }
    .cgx-detalle-meta { color:var(--muted); font-size:13px; margin:4px 0 0; display:flex; flex-direction:column; gap:3px; }
    .cgx-detalle-meta span { display:flex; align-items:center; gap:6px; }
    .cgx-detalle-meta svg { width:14px; height:14px; flex:0 0 auto; }

    /* Terminó el congreso y las piezas siguen marcadas como que están allá. */
    .cgx-pendientes { display:flex; align-items:center; gap:12px; flex-wrap:wrap; margin:14px 0 0;
                      padding:12px 14px; border:1px solid var(--warn, #d97706); border-radius:10px;
                      background:var(--warn-soft, rgba(217,119,6,.1)); font-size:13px; }
    .cgx-pendientes > div { flex:1; min-width:180px; }
    .cgx-pendientes b { display:block; }
    .cgx-pendientes span { color:var(--muted); font-size:12.5px; }
    .cgx-pendientes form { flex:0 0 auto; }

    .cgx-stats { display:grid; grid-template-columns:repeat(2, 1fr); gap:10px; margin:16px 0; }
    .cgx-stat { border:1px solid var(--border); border-radius:10px; padding:10px 12px; text-align:center; }
    .cgx-stat b { display:block; font-size:18px; font-weight:800; }
    .cgx-stat span { font-size:11px; color:var(--muted); }

    .cgx-sub-title { display:flex; align-items:center; justify-content:space-between; margin:18px 0 8px; }
    .cgx-sub-title h4 { margin:0; font-size:13.5px; font-weight:700; }
    .cgx-sub-title a { font-size:12px; color:var(--primary); text-decoration:none; }

    .cgx-mini-table { width:100%; font-size:12.5px; border-collapse:collapse; }
    .cgx-mini-table th { text-align:left; color:var(--muted); font-size:10.5px; text-transform:uppercase;
                         font-weight:700; padding:5px 6px; border-bottom:1px solid var(--border); }
    .cgx-mini-table td { padding:7px 6px; border-bottom:1px solid var(--border); }
    .cgx-mini-table tr:last-child td { border-bottom:none; }
    .cgx-mini-serie { color:var(--muted); font-size:11px; font-family:ui-monospace, Consolas, monospace; }

    .cgx-user-row { display:flex; align-items:center; gap:10px; padding:8px 0; border-bottom:1px solid var(--border); }
    .cgx-user-row:last-child { border-bottom:none; }
    .cgx-user-avatar { width:32px; height:32px; border-radius:50%; background:var(--primary); color:#fff;
                       display:flex; align-items:center; justify-content:center; font-size:12px; font-weight:700;
                       flex:0 0 auto; }
    .cgx-user-info { flex:1; min-width:0; }
    .cgx-user-nombre { font-size:13px; font-weight:600; margin:0; }
    .cgx-user-rol { font-size:11.5px; color:var(--muted); margin:0; }
    .cgx-user-empresa { font-size:11.5px; color:var(--muted); }
    .cgx-quitar { border:none; background:transparent; color:var(--muted); cursor:pointer; font-size:16px;
                 line-height:1; padding:4px; flex:0 0 auto; }
    .cgx-quitar:hover { color:var(--danger); }

    .cgx-mini-form { display:flex; gap:6px; margin-top:10px; flex-wrap:wrap; }
    .cgx-mini-form select, .cgx-mini-form input { flex:1; min-width:90px; padding:7px 9px; font-size:12.5px;
                                                  border:1px solid var(--border); border-radius:7px;
                                                  background:var(--surface); color:var(--text); }
    .cgx-mini-form button { flex:0 0 auto; padding:7px 12px; font-size:12.5px; }

    .cgx-field { margin-top:10px; }
    .cgx-field-label { display:block; font-size:11.5px; color:var(--muted); margin-bottom:5px; }
    .cgx-field select { width:100%; padding:8px 10px; font-size:12.5px; border:1px solid var(--border);
                        border-radius:7px; background:var(--surface); color:var(--text); }

    .cgx-unidades-box { display:grid; grid-template-columns:repeat(auto-fill, minmax(90px, 1fr)); gap:8px; margin-top:10px; }
    .cgx-unidades-box:empty { display:none; }
    .cgx-unidad-card { position:relative; border:1.5px solid var(--border); border-radius:9px; overflow:hidden;
                       cursor:pointer; background:var(--surface-2); }
    .cgx-unidad-card.is-elegida { border-color:var(--primary); box-shadow:0 0 0 2px var(--primary-soft); }
    .cgx-unidad-card img { width:100%; height:70px; object-fit:cover; display:block; }
    .cgx-unidad-sinfoto { width:100%; height:70px; display:flex; align-items:center; justify-content:center;
                          color:var(--muted); font-size:10px; }
    .cgx-unidad-card input { position:absolute; top:5px; right:5px; width:15px; height:15px; margin:0; }
    .cgx-unidad-codigo { display:block; font-size:10px; padding:3px 5px; text-align:center; background:var(--surface);
                         font-family:ui-monospace, Consolas, monospace; }
    .cgx-unidades-submit { margin-top:10px; }
    .cgx-unidades-vacio { font-size:12px; color:var(--muted); margin:8px 0 0; }
</style>
@endpush

@section('content')
    <div class="cgx-actions">
        <a href="{{ route('inventory.congresos.create') }}" class="btn">
            <x-gravityui-plus width="15" height="15" />
            Crear congreso
        </a>
    </div>

    @if (session('status'))
        <x-ui.alert type="ok" style="margin-bottom:18px;">{{ session('status') }}</x-ui.alert>
    @endif
    @if ($errors->any())
        <x-ui.alert type="warn" style="margin-bottom:18px;">{{ $errors->first() }}</x-ui.alert>
    @endif

    <div class="cgx-grid">
        {{-- ===================== Calendario ===================== --}}
        <div>
            <x-ui.card style="margin-bottom:20px;">
                <div class="cgx-cal-head">
                    <x-ui.section-title style="margin:0;">Calendario de congresos</x-ui.section-title>
                    <div class="cgx-cal-nav">
                        <a href="{{ route('inventory.congresos.index', ['mes' => $mesAnterior, 'congreso' => $congress?->id]) }}" aria-label="Mes anterior">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 18l-6-6 6-6"/></svg>
                        </a>
                        <span class="cgx-cal-mes">{{ $mes->locale('es')->translatedFormat('F Y') }}</span>
                        <a href="{{ route('inventory.congresos.index', ['mes' => $mesSiguiente, 'congreso' => $congress?->id]) }}" aria-label="Mes siguiente">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18l6-6-6-6"/></svg>
                        </a>
                    </div>
                </div>

                <div class="cgx-cal">
                    @foreach (['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'] as $dia)
                        <div class="cgx-cal-dia-nombre">{{ $dia }}</div>
                    @endforeach

                    @foreach ($calendario as $dia)
                        <div class="cgx-cal-cell {{ $dia['atenuado'] ? 'atenuado' : '' }} {{ $dia['hoy'] ? 'hoy' : '' }}">
                            <span class="cgx-cal-num">{{ $dia['numero'] }}</span>
                            @foreach (array_slice($dia['eventos'], 0, 2) as $evento)
                                <a href="{{ route('inventory.congresos.index', ['congreso' => $evento['congress']->id, 'mes' => $mes->format('Y-m')]) }}"
                                   class="cgx-cal-evt {{ $congress && $congress->id === $evento['congress']->id ? 'is-actual' : '' }}"
                                   style="background:{{ $evento['color'] }};"
                                   title="{{ $evento['congress']->nombre }}">
                                    {{ $evento['congress']->nombre }}
                                </a>
                            @endforeach
                            @if (count($dia['eventos']) > 2)
                                <span style="font-size:10px; color:var(--muted);">+{{ count($dia['eventos']) - 2 }} más</span>
                            @endif
                        </div>
                    @endforeach
                </div>
            </x-ui.card>

            <x-ui.card>
                <x-ui.section-title style="margin:0 0 12px;">Próximos congresos</x-ui.section-title>

                @if ($proximos->isEmpty())
                    <p class="muted" style="margin:0;">No hay congresos programados.</p>
                @else
                    <table class="cgx-prox-table">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Nombre</th>
                                <th>Lugar</th>
                                <th>Estado</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($proximos as $c)
                                <tr>
                                    <td>{{ $c->fecha_inicio->format('d/m/Y') }}</td>
                                    <td>
                                        <span class="cgx-prox-nombre">
                                            <span class="cgx-prox-dot" style="background:{{ ['#3b82f6','#22c55e','#a855f7','#f97316','#ec4899','#14b8a6','#eab308'][$c->id % 7] }};"></span>
                                            <a href="{{ route('inventory.congresos.index', ['congreso' => $c->id]) }}">{{ $c->nombre }}</a>
                                        </span>
                                    </td>
                                    <td>{{ $c->lugar ?: '—' }}</td>
                                    <td>
                                        <span class="badge {{ $c->estado() === 'active' ? 'badge--ok' : '' }}">{{ $c->estadoLabel() }}</span>
                                    </td>
                                    <td style="text-align:right;">
                                        <a href="{{ route('inventory.congresos.index', ['congreso' => $c->id]) }}" class="btn btn--ghost" style="padding:5px 10px; font-size:12px;">Ver</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </x-ui.card>
        </div>

        {{-- ===================== Detalle ===================== --}}
        <div>
            @if (! $congress)
                <x-ui.card>
                    <div class="empty-state">
                        <span class="ico"><x-gravityui-calendar /></span>
                        <h3>Aún no hay congresos</h3>
                        <p>Crea el primero para empezar a llevar el control.</p>
                    </div>
                </x-ui.card>
            @else
                <x-ui.card style="margin-bottom:20px;">
                    <div class="cgx-detalle-head">
                        <span class="cgx-detalle-ico"><x-gravityui-calendar /></span>
                        <div style="flex:1; min-width:0;">
                            <p class="cgx-detalle-nombre">
                                {{ $congress->nombre }}
                                <span class="badge {{ $congress->estado() === 'active' ? 'badge--ok' : '' }}">{{ $congress->estadoLabel() }}</span>
                            </p>
                            <div class="cgx-detalle-meta">
                                <span>
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                                    {{ $congress->fecha_inicio->locale('es')->translatedFormat('d \d\e F \d\e Y') }}
                                    @if (! $congress->fecha_inicio->isSameDay($congress->fecha_finalizacion))
                                        – {{ $congress->fecha_finalizacion->locale('es')->translatedFormat('d \d\e F \d\e Y') }}
                                    @endif
                                </span>
                                @if ($congress->lugar)
                                    <span>
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 1 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                                        {{ $congress->lugar }}
                                    </span>
                                @endif
                            </div>
                        </div>
                        <div style="display:flex; gap:6px;">
                            <a href="{{ route('inventory.congresos.edit', $congress) }}" class="btn btn--ghost" style="padding:6px 10px;" title="Editar">
                                <x-gravityui-pencil width="14" height="14" />
                            </a>
                            <form method="POST" action="{{ route('inventory.congresos.destroy', $congress) }}" onsubmit="return confirm('¿Eliminar este congreso? Esta acción no se puede deshacer.');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn--ghost" style="padding:6px 10px; color:var(--danger);" title="Eliminar">
                                    <x-gravityui-trash-bin width="14" height="14" />
                                </button>
                            </form>
                        </div>
                    </div>

                    @if ($congress->descripcion)
                        <p class="muted" style="font-size:13px; margin:0 0 14px;">{{ $congress->descripcion }}</p>
                    @endif

                    {{--
                        El congreso terminó y nadie regresó las piezas: si
                        no se avisa, se quedan marcadas para siempre y el
                        "está en congreso" deja de ser confiable.
                    --}}
                    @if ($congress->tienePiezasSinRegresar())
                        <div class="cgx-pendientes">
                            <div>
                                <b>Este congreso ya terminó y todavía tiene piezas allá.</b>
                                <span>Si ya volvieron al almacén, regrésalas aquí para que el inventario diga la verdad.</span>
                            </div>
                            <form method="POST" action="{{ route('inventory.congresos.productos.regresarTodas', $congress) }}"
                                  onsubmit="return confirm('¿Regresar al almacén todas las piezas que no se vendieron?');">
                                @csrf
                                <button type="submit" class="btn">Regresar todas</button>
                            </form>
                        </div>
                    @endif

                    <div class="cgx-stats">
                        <div class="cgx-stat">
                            <b>{{ $productosResumen->sum('cantidad') }}</b>
                            <span>Piezas allá</span>
                        </div>
                        <div class="cgx-stat">
                            <b>{{ $vendidasEnCongreso }}</b>
                            <span>Vendidas aquí</span>
                        </div>
                        <div class="cgx-stat">
                            <b>{{ $participantes->count() }}</b>
                            <span>Usuarios inscritos</span>
                        </div>
                        <div class="cgx-stat">
                            <b>{{ $congress->estadoLabel() }}</b>
                            <span>Estado</span>
                        </div>
                    </div>

                    {{-- ---------- Productos del congreso ---------- --}}
                    <div class="cgx-sub-title">
                        <h4>Productos en el congreso</h4>
                        @if ($productosResumen->isNotEmpty())
                            <span class="muted" style="font-size:11.5px;">{{ $productosResumen->count() }} modelo(s)</span>
                        @endif
                    </div>

                    @if ($productosResumen->isEmpty())
                        <p class="muted" style="margin:0; font-size:12.5px;">
                            @if ($vendidasEnCongreso > 0)
                                Ya no queda nada allá: las {{ $vendidasEnCongreso }} pieza(s) que se llevaron se vendieron.
                            @else
                                Todavía no se ha llevado ningún producto.
                            @endif
                        </p>
                    @else
                        <table class="cgx-mini-table">
                            <thead>
                                <tr>
                                    <th>Producto</th>
                                    <th>Cantidad</th>
                                    <th>Series</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($productosResumen as $fila)
                                    <tr>
                                        <td>{{ trim(($fila['producto']?->marca ?? '').' '.($fila['producto']?->modelo ?? '')) ?: ($fila['producto']?->tipo_equipo ?? 'Producto') }}</td>
                                        <td>{{ $fila['cantidad'] }}</td>
                                        <td>
                                            @foreach ($fila['unidades'] as $unidad)
                                                <div class="cgx-mini-serie">
                                                    {{ $unidad->codigo }}
                                                    <form method="POST" action="{{ route('inventory.congresos.productos.destroy', ['congress' => $congress, 'serial' => $unidad]) }}" style="display:inline;" onsubmit="return confirm('¿Regresar esta unidad del congreso?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="cgx-quitar" title="Quitar" style="font-size:12px;">×</button>
                                                    </form>
                                                </div>
                                            @endforeach
                                        </td>
                                        <td></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif

                    <div class="cgx-field">
                        <label class="cgx-field-label">Elige el producto para ver sus unidades disponibles</label>
                        <select id="cgxProductoSelect">
                            <option value="">Producto…</option>
                            @foreach ($productosDisponibles as $p)
                                <option value="{{ $p->id }}">{{ trim(($p->marca ?? '').' '.($p->modelo ?? '')) ?: $p->tipo_equipo }}</option>
                            @endforeach
                        </select>
                    </div>

                    <form method="POST" action="{{ route('inventory.congresos.productos.store', $congress) }}" id="cgxProductosForm">
                        @csrf
                        <div id="cgxUnidadesBox" class="cgx-unidades-box"></div>
                    </form>

                    {{-- ---------- Usuarios del sistema en el congreso ---------- --}}
                    <div class="cgx-sub-title">
                        <h4>Usuarios del sistema en el congreso</h4>
                    </div>

                    @if ($usuariosAsignados->isEmpty())
                        <p class="muted" style="margin:0; font-size:12.5px;">Nadie de tu equipo está asignado todavía.</p>
                    @else
                        @foreach ($usuariosAsignados as $u)
                            <div class="cgx-user-row">
                                <span class="cgx-user-avatar">{{ mb_strtoupper(mb_substr($u->name, 0, 2)) }}</span>
                                <div class="cgx-user-info">
                                    <p class="cgx-user-nombre">{{ $u->name }}</p>
                                    <p class="cgx-user-rol">{{ $u->email }}</p>
                                </div>
                                <form method="POST" action="{{ route('inventory.congresos.usuarios.destroy', ['congress' => $congress, 'user' => $u]) }}" onsubmit="return confirm('¿Quitar a este usuario del congreso?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="cgx-quitar" title="Quitar">×</button>
                                </form>
                            </div>
                        @endforeach
                    @endif

                    @if ($usuariosDisponibles->isNotEmpty())
                        <form method="POST" action="{{ route('inventory.congresos.usuarios.store', $congress) }}" class="cgx-mini-form">
                            @csrf
                            <select name="user_id" required>
                                <option value="">Usuario del sistema…</option>
                                @foreach ($usuariosDisponibles as $u)
                                    <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->email }})</option>
                                @endforeach
                            </select>
                            <button type="submit" class="btn">Agregar</button>
                        </form>
                    @endif

                    {{-- ---------- Participantes externos (sin cuenta) ---------- --}}
                    <div class="cgx-sub-title">
                        <h4>Participantes externos</h4>
                    </div>

                    @if ($participantes->isEmpty())
                        <p class="muted" style="margin:0; font-size:12.5px;">Todavía no hay participantes registrados.</p>
                    @else
                        @foreach ($participantes as $participante)
                            <div class="cgx-user-row">
                                <span class="cgx-user-avatar">{{ mb_strtoupper(mb_substr($participante->nombre, 0, 2)) }}</span>
                                <div class="cgx-user-info">
                                    <p class="cgx-user-nombre">{{ $participante->nombre }}</p>
                                    <p class="cgx-user-rol">{{ $participante->rol ?: 'Sin rol' }} @if($participante->empresa) · <span class="cgx-user-empresa">{{ $participante->empresa }}</span> @endif</p>
                                </div>
                                <form method="POST" action="{{ route('inventory.congresos.participantes.destroy', ['congress' => $congress, 'participante' => $participante]) }}" onsubmit="return confirm('¿Quitar a este participante?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="cgx-quitar" title="Quitar">×</button>
                                </form>
                            </div>
                        @endforeach
                    @endif

                    <form method="POST" action="{{ route('inventory.congresos.participantes.store', $congress) }}" class="cgx-mini-form">
                        @csrf
                        <input type="text" name="nombre" placeholder="Nombre" required>
                        <input type="text" name="rol" placeholder="Rol">
                        <input type="text" name="empresa" placeholder="Empresa">
                        <button type="submit" class="btn">Agregar</button>
                    </form>
                </x-ui.card>
            @endif
        </div>
    </div>

    @push('scripts')
    <script>
    (function () {
        var select = document.getElementById('cgxProductoSelect');
        var box = document.getElementById('cgxUnidadesBox');
        if (! select || ! box) return;

        var urlUnidades = @json(route('inventory.congresos.unidadesDisponibles'));

        select.addEventListener('change', function () {
            box.innerHTML = '';
            if (! select.value) return;

            fetch(urlUnidades + '?producto_id=' + select.value, { headers: { 'Accept': 'application/json' } })
                .then(function (r) { return r.json(); })
                .then(function (data) { pintar(data.unidades || []); })
                .catch(function () {
                    box.innerHTML = '<p class="cgx-unidades-vacio">No se pudo consultar las unidades.</p>';
                });
        });

        function pintar(unidades) {
            box.innerHTML = '';

            if (! unidades.length) {
                box.innerHTML = '<p class="cgx-unidades-vacio">Ese producto no tiene unidades disponibles.</p>';
                return;
            }

            unidades.forEach(function (u) {
                var card = document.createElement('label');
                card.className = 'cgx-unidad-card';

                var media = u.foto
                    ? '<img src="' + u.foto + '" alt="' + u.codigo + '">'
                    : '<span class="cgx-unidad-sinfoto">Sin foto</span>';

                card.innerHTML = media
                    + '<input type="checkbox" name="serial_ids[]" value="' + u.id + '">'
                    + '<span class="cgx-unidad-codigo">' + u.codigo + (u.no_serie ? ' · ' + u.no_serie : '') + '</span>';

                card.querySelector('input').addEventListener('change', function (e) {
                    card.classList.toggle('is-elegida', e.target.checked);
                });

                box.appendChild(card);
            });

            var boton = document.createElement('button');
            boton.type = 'submit';
            boton.className = 'btn cgx-unidades-submit';
            boton.textContent = 'Agregar unidades elegidas al congreso';
            box.appendChild(boton);
        }
    })();
    </script>
    @endpush
@endsection
