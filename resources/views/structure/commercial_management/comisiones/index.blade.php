@extends('structure.commercial_management.erp')

@section('title', 'Comisiones')

@section('erp_content')
    <x-ui.page-header title="Comisiones"
                      subtitle="Quién vendió más y cuánto le toca"
                      :back="route('commercial.cobranza.index')" />

    @if (session('status'))
        <x-ui.alert type="success" style="margin-bottom:14px;">{{ session('status') }}</x-ui.alert>
    @endif

    @if ($errors->any())
        <x-ui.alert type="danger" style="margin-bottom:14px;">
            <ul style="margin:0; padding-left:18px;">
                @foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach
            </ul>
        </x-ui.alert>
    @endif

    {{-- ===================== Mes y base de cálculo ===================== --}}
    <form method="GET" class="cg-filtros cm-barra">
        <div class="cm-mes">
            <a class="cm-mes-btn" href="{{ route('commercial.comisiones.index', ['periodo' => $anterior, 'base' => $base]) }}" title="Mes anterior" aria-label="Mes anterior">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
            </a>
            <input type="month" name="periodo" value="{{ $periodo }}" onchange="this.form.submit()" aria-label="Mes">
            <a class="cm-mes-btn {{ $esMesActual ? 'is-off' : '' }}" href="{{ route('commercial.comisiones.index', ['periodo' => $siguiente, 'base' => $base]) }}" title="Mes siguiente" aria-label="Mes siguiente">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
            </a>
        </div>

        <div class="cg-tabs">
            @foreach ($bases as $valor => $texto)
                <a href="{{ route('commercial.comisiones.index', ['periodo' => $periodo, 'base' => $valor]) }}"
                   class="cg-tab {{ $base === $valor ? 'is-on' : '' }}">{{ $texto }}</a>
            @endforeach
        </div>

        <span class="cm-nota">
            @if ($base === 'cobrado')
                La comisión se calcula sobre el dinero que entró en {{ $periodoTexto }}, aunque la venta sea de otro mes.
            @else
                La comisión se calcula sobre lo vendido en {{ $periodoTexto }}, se haya cobrado o no.
            @endif
        </span>
    </form>

    {{-- ===================== Indicadores ===================== --}}
    <div class="cg-kpis">
        <div class="cg-kpi">
            <span class="k">Vendido en {{ $periodoTexto }}</span>
            <b>${{ number_format($resumen['vendido'], 2) }}</b>
            <span class="s">{{ $resumen['ventas'] }} venta(s)</span>
        </div>
        <div class="cg-kpi">
            <span class="k">Cobrado en el mes</span>
            <b class="ok">${{ number_format($resumen['cobrado'], 2) }}</b>
            <span class="s">Dinero que entró</span>
        </div>
        <div class="cg-kpi">
            <span class="k">Comisiones del mes</span>
            <b class="pend">${{ number_format($resumen['comision'], 2) }}</b>
            <span class="s">{{ $bases[$base] }}</span>
        </div>
        <div class="cg-kpi {{ $resumen['pendiente'] > 0.009 ? 'es-alerta' : '' }}">
            <span class="k">Por pagar a asesores</span>
            <b class="{{ $resumen['pendiente'] > 0.009 ? 'mal' : 'ok' }}">${{ number_format(max($resumen['pendiente'], 0), 2) }}</b>
            <span class="s">Ya pagado: ${{ number_format($resumen['pagado'], 2) }}</span>
        </div>
    </div>

    {{-- ===================== Ranking ===================== --}}
    <div class="erp-card" style="padding:0; overflow-x:auto; margin-bottom:18px;">
        <table class="cg-tabla cm-tabla">
            <thead>
                <tr>
                    <th style="width:44px;">#</th>
                    <th>Asesor</th>
                    <th class="r">Ventas</th>
                    <th>Vendido</th>
                    <th class="r">Cobrado</th>
                    <th class="r">% comisión</th>
                    <th class="r">Comisión</th>
                    <th class="r">Pagado</th>
                    <th class="r">Pendiente</th>
                    @if ($puedeGestionar)
                        <th></th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @forelse ($filas as $i => $f)
                    @php
                        $ancho = $maximo > 0 ? round(($f['vendido'] / $maximo) * 100) : 0;
                        $anchoCobrado = $maximo > 0 ? round((min($f['cobrado'], $maximo) / $maximo) * 100) : 0;
                        $liquidado = $f['comision'] > 0 && $f['pendiente'] <= 0.009;
                    @endphp
                    <tr>
                        <td><span class="cm-pos {{ $i === 0 && $f['vendido'] > 0 ? 'es-top' : '' }}">{{ $i + 1 }}</span></td>
                        <td>
                            <div class="cg-id">
                                <div class="t">{{ $f['asesor']->name }}</div>
                                <div class="s">{{ $f['asesor']->cargo ?: $f['asesor']->position ?: 'Asesor' }}</div>
                            </div>
                        </td>
                        <td class="r">{{ $f['ventas'] }}</td>
                        <td style="min-width:180px;">
                            <b>${{ number_format($f['vendido'], 2) }}</b>
                            <div class="cg-riel" title="Vendido ${{ number_format($f['vendido'], 2) }} · cobrado ${{ number_format($f['cobrado'], 2) }}">
                                <span class="v" style="width:{{ $ancho }}%"></span>
                                <span class="c" style="width:{{ $anchoCobrado }}%"></span>
                            </div>
                        </td>
                        <td class="r ok">${{ number_format($f['cobrado'], 2) }}</td>
                        <td class="r">
                            @if ($puedeGestionar)
                                <form method="POST" action="{{ route('commercial.comisiones.porcentaje', $f['asesor']) }}" class="cm-pct">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="periodo" value="{{ $periodo }}">
                                    <input type="number" name="porcentaje" step="0.01" min="0" max="100"
                                           value="{{ $f['porcentaje'] !== null ? rtrim(rtrim(number_format($f['porcentaje'], 2, '.', ''), '0'), '.') : '' }}"
                                           placeholder="—" aria-label="Porcentaje de comisión de {{ $f['asesor']->name }}"
                                           onchange="this.form.submit()">
                                    <span>%</span>
                                </form>
                            @else
                                {{ $f['porcentaje'] !== null ? rtrim(rtrim(number_format($f['porcentaje'], 2, '.', ''), '0'), '.') . '%' : '—' }}
                            @endif
                        </td>
                        <td class="r">
                            @if ($f['porcentaje'] === null)
                                <span class="cm-sin">Sin %</span>
                            @else
                                <b>${{ number_format($f['comision'], 2) }}</b>
                            @endif
                        </td>
                        <td class="r ok">${{ number_format($f['pagado'], 2) }}</td>
                        <td class="r">
                            @if ($f['porcentaje'] === null)
                                —
                            @elseif ($liquidado)
                                <span class="cg-chip es-pagado">Liquidado</span>
                            @elseif ($f['pendiente'] < -0.009)
                                <span class="cg-chip es-vencido" title="Se pagó más de lo que resulta con este cálculo">Excedente ${{ number_format(abs($f['pendiente']), 2) }}</span>
                            @elseif ($f['pendiente'] > 0.009)
                                <b class="cm-pend">${{ number_format($f['pendiente'], 2) }}</b>
                            @else
                                —
                            @endif
                        </td>
                        @if ($puedeGestionar)
                            <td class="r" style="white-space:nowrap;">
                                <button type="button" class="erp-btn sm" data-pagar
                                        data-id="{{ $f['asesor']->id }}"
                                        data-nombre="{{ $f['asesor']->name }}"
                                        data-monto="{{ $f['pendiente'] > 0 ? number_format($f['pendiente'], 2, '.', '') : '' }}"
                                        @disabled($f['porcentaje'] === null)
                                        title="{{ $f['porcentaje'] === null ? 'Primero define su porcentaje' : 'Registrar un pago de comisión' }}">
                                    Registrar pago
                                </button>
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $puedeGestionar ? 10 : 9 }}">
                            <div class="empty-state">
                                <span class="ico">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                                </span>
                                <h3>Sin movimiento en {{ $periodoTexto }}</h3>
                                <p>Cuando haya ventas o cobros en este mes, aquí aparece cada asesor con lo que le toca.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- ===================== Pagos del mes ===================== --}}
    <div class="erp-card pad">
        <div class="cg-head">
            <h3>Pagos de comisión de {{ $periodoTexto }}</h3>
            <span class="cg-leyenda">{{ $pagos->count() }} pago(s) · ${{ number_format((float) $pagos->sum('monto'), 2) }}</span>
        </div>

        @if ($pagos->isEmpty())
            <p class="cg-vacio">Todavía no se ha pagado ninguna comisión de este mes.</p>
        @else
            <div style="overflow-x:auto;">
                <table class="cg-tabla">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Asesor</th>
                            <th class="r">Monto</th>
                            <th>Base</th>
                            <th>Nota</th>
                            <th>Registró</th>
                            @if ($puedeGestionar)<th></th>@endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($pagos as $p)
                            <tr>
                                <td>{{ $p->fecha?->format('d/m/Y') }}</td>
                                <td><b>{{ $p->asesor?->name ?? 'Asesor eliminado' }}</b></td>
                                <td class="r ok">${{ number_format((float) $p->monto, 2) }}</td>
                                <td>{{ $bases[$p->base] ?? $p->base }}</td>
                                <td style="color:var(--muted);">{{ $p->nota ?: '—' }}</td>
                                <td style="color:var(--muted);">{{ $p->registradoPor?->name ?? '—' }}</td>
                                @if ($puedeGestionar)
                                    <td class="r">
                                        <form method="POST" action="{{ route('commercial.comisiones.pagos.destroy', $p) }}"
                                              data-confirm="¿Eliminar este pago de ${{ number_format((float) $p->monto, 2) }}? Se vuelve a contar como pendiente.">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="cg-link" style="background:none; border:none; cursor:pointer; color:var(--danger);">Eliminar</button>
                                        </form>
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- ===================== Modal: registrar pago ===================== --}}
    @if ($puedeGestionar)
        <dialog class="cm-modal" id="modalPago" aria-labelledby="modalPagoT">
            <form method="POST" action="{{ route('commercial.comisiones.pagar') }}" class="cm-modal-box">
                @csrf
                <input type="hidden" name="user_id" id="pagoUserId" value="{{ old('user_id') }}">
                <input type="hidden" name="periodo" value="{{ $periodo }}">
                <input type="hidden" name="base" value="{{ $base }}">

                <div class="cm-modal-head">
                    <h3 id="modalPagoT">Registrar pago de comisión</h3>
                    <button type="button" class="cm-modal-x" data-cerrar aria-label="Cerrar">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
                    </button>
                </div>

                <p class="cm-modal-sub">A <b id="pagoNombre">—</b> por {{ $periodoTexto }} ({{ mb_strtolower($bases[$base]) }}).</p>

                <div class="rgrid-2">
                    <x-ui.form-group label="Monto *" for="pagoMonto">
                        <input type="number" id="pagoMonto" name="monto" step="0.01" min="0.01" required value="{{ old('monto') }}">
                    </x-ui.form-group>
                    <x-ui.form-group label="Fecha de pago *" for="pagoFecha">
                        <input type="date" id="pagoFecha" name="fecha" required value="{{ old('fecha', now()->format('Y-m-d')) }}">
                    </x-ui.form-group>
                </div>
                <x-ui.form-group label="Nota" for="pagoNota">
                    <input type="text" id="pagoNota" name="nota" maxlength="255" placeholder="Ej. transferencia, quincena 2" value="{{ old('nota') }}">
                </x-ui.form-group>

                <div class="cm-modal-foot">
                    <button type="button" class="btn btn--ghost" data-cerrar>Cancelar</button>
                    <button type="submit" class="btn">Guardar pago</button>
                </div>
            </form>
        </dialog>
    @endif

    @include('structure.commercial_management.cobranza._estilos')

    <style>
        .cm-barra { align-items:center; }
        .cm-mes { display:inline-flex; align-items:center; border:1px solid var(--border); border-radius:10px; overflow:hidden; background:var(--surface); }
        .cm-mes input { border:none; padding:8px 10px; font-family:inherit; font-size:13.5px; background:transparent; color:var(--text); outline:none; }
        .cm-mes-btn { display:flex; align-items:center; justify-content:center; width:34px; height:36px; color:var(--muted); text-decoration:none; }
        .cm-mes-btn:hover { background:var(--surface-2); color:var(--text); }
        .cm-mes-btn svg { width:16px; height:16px; }
        .cm-mes-btn.is-off { opacity:.35; pointer-events:none; }
        .cm-nota { flex-basis:100%; color:var(--muted); font-size:12.5px; }

        .cm-pos { display:inline-flex; align-items:center; justify-content:center; width:26px; height:26px; border-radius:50%; background:var(--surface-2); color:var(--muted); font-size:12px; font-weight:700; }
        .cm-pos.es-top { background:var(--accent-soft, #fef3c7); color:var(--accent, #b45309); }
        .cm-tabla td b { font-weight:700; }
        .cm-pct { display:inline-flex; align-items:center; gap:4px; justify-content:flex-end; }
        .cm-pct input { width:64px; padding:6px 8px; border:1px solid var(--border); border-radius:8px; background:var(--surface); color:var(--text); font-family:inherit; font-size:13px; text-align:right; }
        .cm-pct input:focus { border-color:var(--primary); outline:none; }
        .cm-pct span { color:var(--muted); font-size:12.5px; }
        .cm-sin { color:var(--muted); font-size:12px; }
        .cm-pend { color:var(--accent); }

        .cm-modal { border:none; border-radius:16px; padding:0; width:min(480px, calc(100vw - 32px)); background:var(--surface, #fff); color:var(--text); box-shadow:0 24px 64px rgba(0,0,0,.28); }
        .cm-modal::backdrop { background:rgba(15,23,42,.55); }
        .cm-modal-box { padding:18px; }
        .cm-modal-head { display:flex; align-items:center; gap:10px; margin-bottom:6px; }
        .cm-modal-head h3 { margin:0; font-size:16px; flex:1; }
        .cm-modal-x { border:none; background:transparent; cursor:pointer; color:var(--muted); width:30px; height:30px; border-radius:8px; display:flex; align-items:center; justify-content:center; }
        .cm-modal-x:hover { background:var(--surface-2); }
        .cm-modal-x svg { width:18px; height:18px; }
        .cm-modal-sub { margin:0 0 14px; color:var(--muted); font-size:13px; }
        .cm-modal-foot { display:flex; justify-content:flex-end; gap:10px; margin-top:14px; }
        .cm-modal .rgrid-2 { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
        @media (max-width:480px) { .cm-modal .rgrid-2 { grid-template-columns:1fr; } }
    </style>

    @if ($puedeGestionar)
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const modal = document.getElementById('modalPago');
                if (!modal) return;

                const abrir = (btn) => {
                    document.getElementById('pagoUserId').value = btn.dataset.id;
                    document.getElementById('pagoNombre').textContent = btn.dataset.nombre;
                    // Se propone lo pendiente; se puede cambiar (pagos parciales).
                    document.getElementById('pagoMonto').value = btn.dataset.monto || '';
                    modal.showModal();
                    document.getElementById('pagoMonto').focus();
                };

                document.querySelectorAll('[data-pagar]').forEach(b => b.addEventListener('click', () => abrir(b)));
                modal.querySelectorAll('[data-cerrar]').forEach(b => b.addEventListener('click', () => modal.close()));
                modal.addEventListener('click', e => { if (e.target === modal) modal.close(); });

                // Si el servidor regresó con errores del pago, se reabre con lo capturado.
                @if ($errors->any() && old('user_id'))
                    const btn = document.querySelector('[data-pagar][data-id="{{ old('user_id') }}"]');
                    if (btn) { abrir(btn); document.getElementById('pagoMonto').value = @json(old('monto')); }
                @endif
            });
        </script>
    @endif
@endsection
