@extends('structure.commercial_management.erp')

@section('title', 'Cobranza ' . $venta->folio)

@section('erp_content')
    <x-ui.page-header :title="'Cobranza · ' . $venta->folio"
                      :subtitle="trim(($venta->customer->nombre ?? '') . ' ' . ($venta->customer->apellido ?? ''))"
                      :back="route('commercial.ventas.show', $venta)">
        <a href="{{ route('commercial.ventas.pdf', $venta) }}" target="_blank" class="erp-btn ghost sm">PDF de la venta</a>
    </x-ui.page-header>

    @if ($errors->any())
        <div class="erp-card pad" style="margin-bottom:18px; border-color:var(--danger); background:var(--danger-soft);">
            <ul style="margin:0; padding-left:18px; color:var(--danger); font-size:13px;">
                @foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach
            </ul>
        </div>
    @endif

    {{-- ===================== Resumen ===================== --}}
    <div class="cb-stats">
        <div class="cb-stat">
            <span class="k">Total de la venta</span>
            <b>${{ number_format($venta->montoExigible(), 2) }}</b>
        </div>
        <div class="cb-stat">
            <span class="k">Cobrado</span>
            <b class="ok">${{ number_format($venta->totalCobrado(), 2) }}</b>
        </div>
        <div class="cb-stat">
            <span class="k">Saldo</span>
            <b class="{{ $venta->saldo() > 0 ? 'pend' : 'ok' }}">${{ number_format($venta->saldo(), 2) }}</b>
        </div>
        <div class="cb-stat">
            <span class="k">Estado</span>
            <b>{{ $venta->estadoPagoLabel() }}</b>
            <div class="barra"><span style="width:{{ $venta->avance() }}%"></span></div>
        </div>
    </div>

    <div class="cb-grid">
        {{-- ===================== Calendario ===================== --}}
        <div>
            <div class="erp-card pad">
                <div class="cb-head">
                    <h3>Calendario de pagos</h3>
                    <div class="cb-head-acc">
                        <button type="button" class="erp-btn ghost sm" data-abrir="modalRecorrer">Recorrer fechas</button>
                        <button type="button" class="erp-btn ghost sm" data-abrir="modalParcialidad">Agregar</button>
                    </div>
                </div>

                @forelse ($venta->pagos as $p)
                    <div class="cb-fila">
                        <div class="cb-fila-txt">
                            <div class="t">{{ $p->nombre }}</div>
                            <div class="s">
                                {{ optional($p->fecha)->format('d/m/Y') ?: 'Sin fecha' }}
                                @if ($p->cobrado() > 0)
                                    · cobrado ${{ number_format($p->cobrado(), 2) }} de ${{ number_format((float) $p->monto, 2) }}
                                @endif
                            </div>
                        </div>

                        <div class="cb-fila-monto">
                            ${{ number_format($p->saldo(), 2) }}
                            @if ($p->cobrado() > 0)
                                <div class="s" style="font-weight:400;">de ${{ number_format((float) $p->monto, 2) }}</div>
                            @endif
                        </div>

                        <span class="cb-chip es-{{ $p->estado() }}">{{ $p->estadoLabel() }}</span>

                        <div class="cb-fila-acc">
                            <button type="button" class="cb-mini" title="Editar"
                                    data-editar-parcialidad
                                    data-url="{{ route('commercial.ventas.cobros.parcialidad.actualizar', [$venta, $p]) }}"
                                    data-nombre="{{ $p->nombre }}"
                                    data-fecha="{{ optional($p->fecha)->format('Y-m-d') }}"
                                    data-monto="{{ $p->monto }}"
                                    data-cobrado="{{ $p->cobrado() }}">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                            </button>

                            @if ($p->saldo() > 0)
                                <button type="button" class="cb-mini cb-mini--ok" title="Registrar pago"
                                        data-cobrar
                                        data-parcialidad="{{ $p->id }}"
                                        data-monto="{{ $p->saldo() }}">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path d="M12 5v14M5 12h14"/></svg>
                                </button>
                            @endif

                            @unless ($p->tieneCobros())
                                <form method="POST" action="{{ route('commercial.ventas.cobros.parcialidad.eliminar', [$venta, $p]) }}" style="display:inline;">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="cb-mini cb-mini--danger" title="Eliminar">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/></svg>
                                    </button>
                                </form>
                            @endunless
                        </div>
                    </div>
                @empty
                    <p class="cb-vacio">Esta venta no tiene calendario de pagos.</p>
                @endforelse

                @php $planeado = (float) $venta->pagos->sum('monto'); @endphp
                @if ($venta->pagos->isNotEmpty() && abs($planeado - $venta->montoExigible()) > 0.01)
                    <div class="cb-alerta">
                        El plan suma ${{ number_format($planeado, 2) }} y la venta exige
                        ${{ number_format($venta->montoExigible(), 2) }}.
                        <form method="POST" action="{{ route('commercial.ventas.cobros.rebalancear', $venta) }}" style="display:inline;">
                            @csrf
                            <button type="submit" class="cb-enlace">Repartir la diferencia</button>
                        </form>
                    </div>
                @endif

                @if ($excedentePendiente > 0.01)
                    <div class="cb-alerta">
                        Hay ${{ number_format($excedentePendiente, 2) }} ya cobrados que ninguna parcialidad tiene ligados todavía
                        (un abono suelto, o uno que superó lo que le tocaba a su parcialidad).
                        <form method="POST" action="{{ route('commercial.ventas.cobros.absorber-excedente', $venta) }}" style="display:inline;">
                            @csrf
                            <button type="submit" class="cb-enlace">Aplicar a lo pendiente</button>
                        </form>
                    </div>
                @endif
            </div>

            {{-- ===================== Cobros registrados ===================== --}}
            <div class="erp-card pad" style="margin-top:18px;">
                <div class="cb-head">
                    <h3>Pagos recibidos</h3>
                    <button type="button" class="erp-btn sm" data-cobrar>Registrar pago</button>
                </div>

                @forelse ($venta->cobros as $c)
                    <div class="cb-fila">
                        <div class="cb-fila-txt">
                            <div class="t">{{ $c->folio }} · {{ $c->metodoLabel() }}</div>
                            <div class="s">
                                {{ $c->fecha->format('d/m/Y') }}
                                @if ($c->parcialidad) · {{ $c->parcialidad->nombre }} @endif
                                @if ($c->referencia) · ref. {{ $c->referencia }} @endif
                                @if ($c->evidencias->count()) · {{ $c->evidencias->count() }} evidencia(s) @endif
                            </div>
                        </div>

                        <div class="cb-fila-monto ok">${{ number_format((float) $c->monto, 2) }}</div>

                        <div class="cb-fila-acc">
                            @foreach ($c->evidencias as $ev)
                                <a href="{{ asset('storage/' . $ev->archivo) }}" target="_blank" rel="noopener"
                                   class="cb-mini" title="{{ $ev->nombre }}">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>
                                </a>
                            @endforeach

                            <a href="{{ route('commercial.ventas.cobros.recibo', [$venta, $c]) }}" target="_blank"
                               class="cb-mini" title="Recibo">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="m7 10 5 5 5-5"/><path d="M12 15V3"/></svg>
                            </a>

                            <form method="POST" action="{{ route('commercial.ventas.cobros.destroy', [$venta, $c]) }}"
                                  style="display:inline;" data-confirmar="Se cancelará el cobro {{ $c->folio }} y se borrarán sus evidencias.">
                                @csrf @method('DELETE')
                                <button type="submit" class="cb-mini cb-mini--danger" title="Cancelar cobro">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
                                </button>
                            </form>
                        </div>
                    </div>
                @empty
                    <p class="cb-vacio">Todavía no se registra ningún pago.</p>
                @endforelse
            </div>
        </div>

        {{-- ===================== Bitácora ===================== --}}
        <div>
            <div class="erp-card pad">
                <h3 style="margin:0 0 14px; font-size:15px;">Movimientos</h3>

                @forelse ($venta->bitacora->take(25) as $b)
                    <div class="cb-mov">
                        <div class="t">{{ $b->descripcion }}</div>
                        <div class="s">
                            {{ $b->created_at->format('d/m/Y H:i') }}
                            @if ($b->user) · {{ $b->user->name }} @endif
                        </div>
                    </div>
                @empty
                    <p class="cb-vacio">Sin movimientos.</p>
                @endforelse
            </div>
        </div>
    </div>

    @include('structure.commercial_management.cobros._modales', ['venta' => $venta, 'metodos' => $metodos])
    @include('structure.commercial_management.cobros._estilos')
@endsection
