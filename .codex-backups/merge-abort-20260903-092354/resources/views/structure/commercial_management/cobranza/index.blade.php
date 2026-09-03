@extends('structure.commercial_management.erp')

@section('title', 'Cobranza')

@section('erp_content')
    <x-ui.page-header title="Cobranza y ventas"
                      subtitle="Quién debe, quién está atrasado y cómo va el mes"
                      :back="route('commercial.ventas.index')" />

    {{-- ===================== Indicadores ===================== --}}
    <div class="cg-kpis">
        <div class="cg-kpi">
            <span class="k">Por cobrar</span>
            <b class="pend">${{ number_format($resumen['por_cobrar'], 2) }}</b>
            <span class="s">{{ $resumen['clientes_deben'] }} venta(s) con saldo</span>
        </div>

        <div class="cg-kpi {{ $resumen['vencido'] > 0 ? 'es-alerta' : '' }}">
            <span class="k">Vencido</span>
            <b class="{{ $resumen['vencido'] > 0 ? 'mal' : '' }}">${{ number_format($resumen['vencido'], 2) }}</b>
            <span class="s">{{ $resumen['clientes_atrasados'] }} con atraso</span>
        </div>

        <div class="cg-kpi">
            <span class="k">Vendido este mes</span>
            <b>${{ number_format($resumen['ventas_mes'], 2) }}</b>
            <span class="s">{{ $resumen['ventas_mes_cantidad'] }} venta(s)</span>
        </div>

        <div class="cg-kpi">
            <span class="k">Cobrado este mes</span>
            <b class="ok">${{ number_format($resumen['cobrado_mes'], 2) }}</b>
            <span class="s">De ${{ number_format($resumen['cobrado_total'], 2) }} histórico</span>
        </div>
    </div>

    {{-- ===================== Gráficas ===================== --}}
    <div class="cg-graficas">
        <div class="erp-card pad">
            <div class="cg-head">
                <h3>Vendido y cobrado por mes</h3>
                <div class="cg-leyenda">
                    <span><i class="c-vendido"></i> Vendido</span>
                    <span><i class="c-cobrado"></i> Cobrado</span>
                </div>
            </div>

            @if ($porMes['maximo'] <= 0)
                <p class="cg-vacio">Todavía no hay ventas ni cobros que graficar.</p>
            @else
                <div class="cg-barras">
                    @foreach ($porMes['meses'] as $m)
                        @php
                            $hv = $porMes['maximo'] > 0 ? round(($m['vendido'] / $porMes['maximo']) * 100) : 0;
                            $hc = $porMes['maximo'] > 0 ? round(($m['cobrado'] / $porMes['maximo']) * 100) : 0;
                        @endphp
                        <div class="cg-mes" title="{{ $m['etiqueta'] }}: vendido ${{ number_format($m['vendido'], 2) }} · cobrado ${{ number_format($m['cobrado'], 2) }}">
                            <div class="par">
                                <span class="b c-vendido" style="height:{{ max($hv, $m['vendido'] > 0 ? 2 : 0) }}%"></span>
                                <span class="b c-cobrado" style="height:{{ max($hc, $m['cobrado'] > 0 ? 2 : 0) }}%"></span>
                            </div>
                            <span class="e">{{ $m['etiqueta'] }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="erp-card pad">
            <div class="cg-head"><h3>Asesores</h3></div>

            @if (empty($asesores['filas']))
                <p class="cg-vacio">Sin ventas registradas.</p>
            @else
                @foreach ($asesores['filas'] as $a)
                    @php $ancho = $asesores['maximo'] > 0 ? round(($a['monto'] / $asesores['maximo']) * 100) : 0; @endphp
                    <div class="cg-asesor">
                        <div class="cg-asesor-top">
                            <span class="n">{{ $a['nombre'] }}</span>
                            <span class="m">${{ number_format($a['monto'], 2) }}</span>
                        </div>
                        <div class="cg-riel">
                            <span class="v" style="width:{{ $ancho }}%"></span>
                            @if ($a['monto'] > 0)
                                <span class="c" style="width:{{ round(($a['cobrado'] / $asesores['maximo']) * 100) }}%"></span>
                            @endif
                        </div>
                        <div class="cg-asesor-pie">
                            {{ $a['ventas'] }} venta(s) · cobrado ${{ number_format($a['cobrado'], 2) }}
                        </div>
                    </div>
                @endforeach
            @endif
        </div>
    </div>

    {{-- ===================== Filtros ===================== --}}
    <form method="GET" class="cg-filtros">
        <div class="cg-tabs">
            @foreach (['deben' => 'Con saldo', 'atrasadas' => 'Atrasadas', 'pagadas' => 'Pagadas', 'todas' => 'Todas'] as $valor => $texto)
                <a href="{{ request()->fullUrlWithQuery(['estado' => $valor]) }}"
                   class="cg-tab {{ $estado === $valor ? 'is-on' : '' }}">
                    {{ $texto }}
                    <span class="cg-num">{{ $conteos[$valor] ?? 0 }}</span>
                </a>
            @endforeach
        </div>

        <input type="hidden" name="estado" value="{{ $estado }}">

        <div class="cg-busca">
            <input type="text" name="buscar" value="{{ $buscar }}" placeholder="Folio, cliente o asesor" autocomplete="off">
        </div>

        <select name="asesor" onchange="this.form.submit()">
            <option value="">Todos los asesores</option>
            @foreach ($listaAsesores as $a)
                <option value="{{ $a->id }}" @selected($asesorId === $a->id)>{{ $a->name }}</option>
            @endforeach
        </select>

        <button type="submit" class="erp-btn ghost sm">Buscar</button>
    </form>

    {{-- ===================== Lista ===================== --}}
    <div class="erp-card" style="padding:0; overflow-x:auto;">
        <table class="cg-tabla">
            <thead>
                <tr>
                    <th>Venta</th>
                    <th>Asesor</th>
                    <th class="r">Total</th>
                    <th class="r">Cobrado</th>
                    <th class="r">Saldo</th>
                    <th>Próximo pago</th>
                    <th>Estado</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($filas as $f)
                    <tr class="{{ $f['atrasada'] ? 'es-atrasada' : '' }}">
                        <td>
                            <div class="cg-id">
                                <div class="t">{{ $f['venta']->folio }}</div>
                                <div class="s">{{ $f['cliente'] }}</div>
                            </div>
                        </td>
                        <td>{{ $f['asesor'] }}</td>
                        <td class="r">${{ number_format($f['total'], 2) }}</td>
                        <td class="r ok">${{ number_format($f['cobrado'], 2) }}</td>
                        <td class="r"><b>${{ number_format($f['saldo'], 2) }}</b></td>
                        <td>
                            @if ($f['proxima'])
                                {{ $f['proxima']->format('d/m/Y') }}
                                @if ($f['atrasada'])
                                    <div class="cg-atraso">{{ $f['dias_atraso'] }} día(s) de atraso</div>
                                @endif
                            @else
                                —
                            @endif
                        </td>
                        <td>
                            <span class="cg-chip es-{{ $f['atrasada'] ? 'vencido' : $f['estado'] }}">
                                {{ $f['atrasada'] ? 'Atrasada' : $f['venta']->estadoPagoLabel() }}
                            </span>
                            <div class="cg-riel chico">
                                <span class="v" style="width:{{ $f['avance'] }}%"></span>
                            </div>
                        </td>
                        <td class="r" style="white-space:nowrap;">
                            <a href="{{ route('commercial.ventas.cobros.index', $f['venta']) }}" class="cg-link">Cobranza</a>
                            <a href="{{ route('commercial.ventas.show', $f['venta']) }}" class="cg-link">Ver</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8">
                            <div class="empty-state">
                                <span class="ico">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                                </span>
                                <h3>Nada por aquí</h3>
                                <p>No hay ventas que coincidan con este filtro.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @include('structure.commercial_management.cobranza._estilos')
@endsection
