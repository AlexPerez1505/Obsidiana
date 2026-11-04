@extends('layouts.dashboard')

@section('title', 'Congresos')
@section('page-title', 'Congresos')
@section('page-sub', 'Gestiona y consulta todos los congresos, revisa los productos y usuarios asignados')

@push('head')
<style>

    .cgx-grid { display:grid; grid-template-columns:minmax(0,1.3fr) minmax(0,1fr); gap:20px; align-items:start; }
    @media (max-width:1024px) { .cgx-grid { grid-template-columns:1fr; } }

    /* El botón de crear vive en la cabecera del calendario: una fila
       entera solo para él era espacio muerto arriba de todo. */
    .cgx-cal-head { display:flex; align-items:center; justify-content:space-between; gap:10px 14px;
                    flex-wrap:wrap; margin-bottom:14px; }
    .cgx-cal-head-der { display:flex; align-items:center; gap:10px 14px; flex-wrap:wrap; }
    .cgx-cal-nav { display:flex; align-items:center; gap:10px; }
    .cgx-cal-nav a { display:flex; align-items:center; justify-content:center; width:30px; height:30px;
                     border:1px solid var(--border); border-radius:8px; color:var(--text); text-decoration:none; }
    .cgx-cal-nav a:hover { border-color:var(--primary); color:var(--primary); }
    .cgx-cal-nav a svg { width:15px; height:15px; }
    .cgx-cal-mes { font-size:15px; font-weight:700; min-width:150px; text-align:center; text-transform:capitalize; }

    .cgx-cal { display:grid; grid-template-columns:repeat(7, 1fr); gap:4px; }
    .cgx-cal-dia-nombre { text-align:center; font-size:11.5px; font-weight:700; color:var(--muted);
                          text-transform:uppercase; padding-bottom:6px; }
    .cgx-cal-cell { min-height:60px; border-radius:9px; border:1px solid var(--border); padding:5px;
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

    /* Las cuatro cifras en una sola fila; en el teléfono, de dos en dos. */
    .cgx-stats { display:grid; grid-template-columns:repeat(4, minmax(0, 1fr)); gap:8px; margin:14px 0; }
    .cgx-stat { border:1px solid var(--border); border-radius:10px; padding:9px 8px; text-align:center; min-width:0; }
    @media (max-width:480px) { .cgx-stats { grid-template-columns:repeat(2, minmax(0, 1fr)); } }
    .cgx-stat b { display:block; font-size:18px; font-weight:800; }
    .cgx-stat span { font-size:11px; color:var(--muted); }

    .cgx-costos { display:grid; grid-template-columns:repeat(3, minmax(0, 1fr)); gap:8px; margin:0 0 14px; }
    .cgx-costo { border:1px solid var(--border); border-radius:10px; padding:10px 12px; }
    .cgx-costo span { display:block; font-size:11px; color:var(--muted); margin-bottom:2px; }
    .cgx-costo b { font-size:17px; font-weight:800; }
    .cgx-costo b.ok { color:var(--green); }
    .cgx-costo b.due { color:var(--danger); }
    @media (max-width:640px) { .cgx-costos { grid-template-columns:1fr; } }

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

    /* Unidades para elegir: filas compactas (casilla | miniatura | código)
       en lugar de un mosaico de fotos. La foto se ve en grande en el visor. */
    .cgx-unidades-box { display:grid; grid-template-columns:repeat(auto-fill, minmax(200px, 1fr)); gap:6px; margin-top:10px; }
    .cgx-unidades-box:empty { display:none; }
    .cgx-unidad-card { display:flex; align-items:center; gap:9px; min-width:0; padding:6px 8px;
                       border:1.5px solid var(--border); border-radius:9px; cursor:pointer; background:var(--surface); }
    .cgx-unidad-card.is-elegida { border-color:var(--primary); box-shadow:0 0 0 2px var(--primary-soft); }
    .cgx-unidad-card input { flex:0 0 auto; width:15px; height:15px; margin:0; accent-color:var(--primary); }
    .cgx-unidad-foto, .cgx-unidad-sinfoto { flex:0 0 34px; width:34px; height:34px; border-radius:7px;
                                            border:1px solid var(--border); background:var(--surface-2); }
    .cgx-unidad-foto { padding:0; overflow:hidden; cursor:zoom-in; }
    .cgx-unidad-foto img { width:100%; height:100%; object-fit:cover; display:block; }
    .cgx-unidad-sinfoto { display:flex; align-items:center; justify-content:center; color:var(--muted); font-size:9px;
                          text-align:center; line-height:1.1; }
    .cgx-unidad-codigo { min-width:0; font-size:11.5px; font-family:ui-monospace, Consolas, monospace;
                         overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .cgx-unidades-submit { grid-column:1 / -1; justify-self:start; margin-top:4px; }
    .cgx-unidades-box > .cgx-unidades-vacio { grid-column:1 / -1; }

    /* Usuarios del sistema y externos: lado a lado cuando la tarjeta es
       ancha; en una columna angosta se apilan solos. */
    .cgx-personas { display:grid; grid-template-columns:repeat(auto-fit, minmax(260px, 1fr)); gap:0 22px; }
    .cgx-personas > div { min-width:0; }
    .cgx-unidades-vacio { font-size:12px; color:var(--muted); margin:8px 0 0; }
</style>
@endpush

@section('content')
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
                    <div class="cgx-cal-head-der">
                    <div class="cgx-cal-nav">
                        <a href="{{ route('inventory.congresos.index', ['mes' => $mesAnterior, 'congreso' => $congress?->id]) }}" aria-label="Mes anterior">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 18l-6-6 6-6"/></svg>
                        </a>
                        <span class="cgx-cal-mes">{{ $mes->locale('es')->translatedFormat('F Y') }}</span>
                        <a href="{{ route('inventory.congresos.index', ['mes' => $mesSiguiente, 'congreso' => $congress?->id]) }}" aria-label="Mes siguiente">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18l6-6-6-6"/></svg>
                        </a>
                    </div>
                    @can('congresos.editar')
                    <a href="{{ route('inventory.congresos.create') }}" class="btn">
                        <x-gravityui-plus width="15" height="15" />
                        Crear congreso
                    </a>
                    @endcan
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
                                    {{ $congress->fecha_inicio->format('d \d\e F \d\e Y') }}
                                    @if (! $congress->fecha_inicio->isSameDay($congress->fecha_finalizacion))
                                        – {{ $congress->fecha_finalizacion->format('d \d\e F \d\e Y') }}
                                    @endif
                                </span>
                                @if ($congress->lugar)
                                    <span>
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 1 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                                        {{ $congress->lugar }}
                                    </span>
                                @endif
                                @if ($congress->num_stand)
                                    <span>
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21h18M5 21V7l7-4 7 4v14M9 21v-6h6v6"/></svg>
                                        Stand {{ $congress->num_stand }}
                                    </span>
                                @endif
                                @if ($congress->fecha_montaje)
                                    <span>
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
                                        Montaje: {{ $congress->fecha_montaje->format('d/m/Y') }}
                                        @if ($congress->hora_montaje) · {{ $congress->hora_montaje->format('H:i') }} h @endif
                                    </span>
                                @endif
                            </div>
                        </div>
                        <div style="display:flex; gap:6px;">
                            <a href="{{ route('inventory.congresos.edit', $congress) }}" class="btn btn--ghost" style="padding:6px 10px;" title="Editar">
                                <x-gravityui-pencil width="14" height="14" />
                            </a>
                            <form method="POST" action="{{ route('inventory.congresos.destroy', $congress) }}" data-confirm="¿Eliminar este congreso? Esta acción no se puede deshacer.">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn--ghost" style="padding:6px 10px; color:var(--danger);" title="Eliminar" @disabled(auth()->user()->cannot('congresos.eliminar'))>
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
                                  data-confirm="¿Regresar al almacén todas las piezas que no se vendieron?">
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

                    {{-- ---------- Costos del congreso ---------- --}}
                    @if ($congress->tieneCostos())
                        <div class="cgx-costos">
                            <div class="cgx-costo">
                                <span>Costo</span>
                                <b>${{ number_format((float) $congress->costo, 2) }}</b>
                            </div>
                            <div class="cgx-costo">
                                <span>Pagado / apartado</span>
                                <b class="ok">${{ number_format((float) $congress->pagado, 2) }}</b>
                            </div>
                            <div class="cgx-costo">
                                <span>Pendiente por pagar</span>
                                @if ($congress->estaLiquidado())
                                    <b class="ok">Liquidado</b>
                                @else
                                    <b class="due">${{ number_format($congress->saldoPendiente(), 2) }}</b>
                                @endif
                            </div>
                        </div>
                    @endif

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
                                                    <form method="POST" action="{{ route('inventory.congresos.productos.destroy', ['congress' => $congress, 'serial' => $unidad]) }}" style="display:inline;" data-confirm="¿Regresar esta unidad del congreso?">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="cgx-quitar" title="Quitar" @disabled(auth()->user()->cannot('congresos.eliminar')) style="font-size:12px;">×</button>
                                                    </form>
                                                </div>
                                            @endforeach
                                        </td>
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

                    <div class="cgx-personas">
                    <div>
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
                                <form method="POST" action="{{ route('inventory.congresos.usuarios.destroy', ['congress' => $congress, 'user' => $u]) }}" data-confirm="¿Quitar a este usuario del congreso?">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="cgx-quitar" title="Quitar" @disabled(auth()->user()->cannot('congresos.eliminar'))>×</button>
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

                    </div>

                    <div>
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
                                <form method="POST" action="{{ route('inventory.congresos.participantes.destroy', ['congress' => $congress, 'participante' => $participante]) }}" data-confirm="¿Quitar a este participante?">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="cgx-quitar" title="Quitar" @disabled(auth()->user()->cannot('congresos.eliminar'))>×</button>
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
                    </div>
                    </div>
                </x-ui.card>
            @endif
        </div>
    </div>

    @include('partials.galeria')

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

                // Miniatura chica: tocarla abre la foto en grande (sin marcar
                // la casilla); tocar el resto de la fila elige la unidad.
                var media = u.foto
                    ? '<button type="button" class="cgx-unidad-foto" aria-label="Ver la foto de ' + u.codigo + '"><img src="' + u.foto + '" alt="" loading="lazy"></button>'
                    : '<span class="cgx-unidad-sinfoto">Sin foto</span>';

                var etiqueta = u.codigo + (u.no_serie ? ' · ' + u.no_serie : '');

                card.innerHTML = '<input type="checkbox" name="serial_ids[]" value="' + u.id + '">'
                    + media
                    + '<span class="cgx-unidad-codigo" title="' + etiqueta + '">' + etiqueta + '</span>';

                card.querySelector('input').addEventListener('change', function (e) {
                    card.classList.toggle('is-elegida', e.target.checked);
                });

                var foto = card.querySelector('.cgx-unidad-foto');
                if (foto) {
                    foto.addEventListener('click', function (e) {
                        e.preventDefault();
                        if (window.ObsGaleria) window.ObsGaleria.abrir([{ tipo: 'foto', url: u.foto }], 0);
                    });
                }

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
