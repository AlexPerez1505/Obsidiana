@extends('structure.commercial_management.erp')

@section('title', 'Venta ' . $venta->folio)

@section('erp_content')
    {{-- Cabecera estándar del sistema: la flecha de regresar es un icono,
         no un botón que compita con las acciones reales. --}}
    <x-ui.page-header :title="$venta->folio"
                      :back="route('commercial.ventas.index')">
        {{-- Editar es una acción de apoyo: va como icono para no competir
             con la acción principal de la pantalla. --}}
        <a href="{{ route('commercial.ventas.edit', $venta) }}" class="btn-icono" title="Editar venta" aria-label="Editar venta">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
        </a>

        {{-- Los tres documentos son lo mismo: se agrupan en un solo control. --}}
        <x-ui.menu etiqueta="Documentos">
            <x-ui.menu-item :href="route('commercial.ventas.pdf', $venta)" blank
                            detalle="La venta con sus anexos y fichas">
                <x-slot:icono>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                </x-slot:icono>
                PDF de la venta
            </x-ui.menu-item>

            <div class="ui-menu-sep"></div>

            {{-- El contrato solo aplica a ventas a plazos. --}}
            @if ($venta->requiereContrato())
                <x-ui.menu-item :href="route('commercial.ventas.contrato', $venta)" blank
                                detalle="Compraventa con reserva de dominio">
                    <x-slot:icono>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7z"/><path d="M8 13h6M8 17h4"/></svg>
                    </x-slot:icono>
                    Contrato
                </x-ui.menu-item>
            @endif

            <x-ui.menu-item :href="route('commercial.ventas.garantia', $venta)" blank
                            :detalle="$venta->garantia_meses . ' meses sobre el equipo'">
                <x-slot:icono>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/></svg>
                </x-slot:icono>
                Carta garantía
            </x-ui.menu-item>
        </x-ui.menu>

        {{-- El saldo es información, no un botón más: se lee de un vistazo
             y el punto de color dice en qué estado va la cobranza. --}}
        <a href="{{ route('commercial.ventas.cobros.index', $venta) }}"
           class="pill-dato es-{{ $venta->estadoPago() }}" title="Ir a cobranza">
            <span class="punto"></span>
            @if ($venta->saldo() > 0)
                <span class="et">Saldo</span>
                <span class="val">${{ number_format($venta->saldo(), 2) }}</span>
            @else
                <span class="val">Pagada</span>
            @endif
        </a>

        <a href="{{ route('commercial.facturas.create', ['venta' => $venta->id]) }}" class="btn">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="15" height="15"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
            Borrador de factura
        </a>
    </x-ui.page-header>

    <p class="doc-resumen-linea">
        {{ trim(($venta->customer->nombre ?? '') . ' ' . ($venta->customer->apellido ?? '')) ?: 'Sin cliente' }}
        · {{ $venta->created_at?->format('d/m/Y') }}
        @if ($venta->cotizacion) · desde {{ $venta->cotizacion->folio }} @endif
        <span class="badge {{ $venta->estado === 'cancelada' ? '' : 'badge--ok' }}">{{ $venta->estadoLabel() }}</span>
        <span class="badge {{ $venta->estadoPago() === 'pagado' ? 'badge--ok' : 'badge--info' }}">{{ $venta->estadoPagoLabel() }}</span>
    </p>

    @include('structure.commercial_management._enlace_publico', [
        'url' => $venta->public_token ? route('publico.venta', $venta->public_token) : null,
    ])

    <div class="doc-grid">
        <div class="doc-col">
            {{-- ===================== Equipo ===================== --}}
            <x-ui.card>
                <div class="doc-head">
                    <h3>Equipo</h3>
                    <span class="der">{{ $venta->items->count() }} concepto(s) · {{ $venta->items->sum('cantidad') }} pieza(s)</span>
                </div>

                @include('structure.commercial_management._items_tabla', ['items' => $venta->items])
            </x-ui.card>

            {{-- ===================== Plan de pagos ===================== --}}
            @if ($venta->pagos->count())
                <x-ui.card>
                    <div class="doc-head">
                        <h3>Plan de pagos</h3>
                        <span class="der">
                            @if ($venta->modalidad === 'financiamiento'){{ $venta->num_meses }} meses · @endif
                            cobrado ${{ number_format($venta->totalCobrado(), 2) }}
                        </span>
                    </div>

                    @foreach ($venta->pagos as $p)
                        <div class="doc-pago">
                            <div class="txt">
                                <div class="t">{{ $p->nombre }}</div>
                                <div class="s">
                                    {{ optional($p->fecha)->format('d/m/Y') ?: 'Sin fecha' }}
                                    @if ($p->cobrado() > 0) · cobrado ${{ number_format($p->cobrado(), 2) }} @endif
                                </div>
                            </div>
                            <span class="doc-chip es-{{ $p->estado() }}">{{ $p->estadoLabel() }}</span>
                            <span class="m">${{ number_format($p->monto, 2) }}</span>
                        </div>
                    @endforeach
                </x-ui.card>
            @endif

            @if ($venta->nota_cliente)
                <x-ui.card>
                    <div class="doc-head"><h3>Nota al cliente</h3></div>
                    <div class="doc-nota">{{ $venta->nota_cliente }}</div>
                </x-ui.card>
            @endif
        </div>

        {{-- ===================== Lateral ===================== --}}
        <div class="doc-col">
            <x-ui.card>
                <div class="doc-head"><h3>Resumen</h3></div>

                <div class="doc-tot"><span class="e">Subtotal</span><span class="v">${{ number_format($venta->subtotal, 2) }}</span></div>
                @if ($venta->descuento_monto > 0)
                    <div class="doc-tot"><span class="e">Descuento</span><span class="v">-${{ number_format($venta->descuento_monto, 2) }}</span></div>
                @endif
                @if ($venta->envio > 0)
                    <div class="doc-tot"><span class="e">Envío</span><span class="v">${{ number_format($venta->envio, 2) }}</span></div>
                @endif
                @if ($venta->aplica_iva)
                    <div class="doc-tot"><span class="e">IVA 16%</span><span class="v">${{ number_format($venta->iva_monto, 2) }}</span></div>
                @endif

                <div class="doc-tot total"><span class="e">Total</span><span class="v">${{ number_format($venta->total, 2) }}</span></div>

                @if ($venta->valor_a_cuenta > 0)
                    <div class="doc-tot"><span class="e">Valor a cuenta</span><span class="v">-${{ number_format($venta->valor_a_cuenta, 2) }}</span></div>
                    <div class="doc-tot aparte"><span class="e">Total del contrato</span><span class="v">${{ number_format($venta->total_contrato, 2) }}</span></div>
                @endif
            </x-ui.card>

            <x-ui.card>
                <div class="doc-head"><h3>Cobranza</h3></div>

                <div class="doc-par"><span class="k">Cobrado</span><span class="v" style="color:var(--green);">${{ number_format($venta->totalCobrado(), 2) }}</span></div>
                <div class="doc-par"><span class="k">Saldo</span><span class="v">${{ number_format($venta->saldo(), 2) }}</span></div>
                <div class="doc-par"><span class="k">Pagos recibidos</span><span class="v">{{ $venta->cobros->count() }}</span></div>

                <div class="doc-barra"><span style="width:{{ $venta->avance() }}%"></span></div>
                <p class="doc-avance">{{ $venta->avance() }}% cubierto</p>
            </x-ui.card>

            <x-ui.card>
                <div class="doc-head"><h3>Datos</h3></div>

                <div class="doc-par"><span class="k">Modalidad</span><span class="v">{{ ucfirst($venta->modalidad) }}</span></div>
                <div class="doc-par"><span class="k">Atendió</span><span class="v">{{ $venta->seller?->name ?: '—' }}</span></div>
                <div class="doc-par">
                    <span class="k">Garantía</span>
                    <span class="v">
                        {{ $venta->garantia_meses }} meses
                        @if ($venta->garantiaHasta())
                            <span style="display:block; font-weight:400; color:var(--muted); font-size:12px;">
                                {{ $venta->garantiaVigente() ? 'vigente hasta' : 'venció el' }}
                                {{ $venta->garantiaHasta()->format('d/m/Y') }}
                            </span>
                        @endif
                    </span>
                </div>
                @if ($venta->lugar_propuesta)
                    <div class="doc-par"><span class="k">Congreso</span><span class="v">{{ $venta->lugar_propuesta }}</span></div>
                @endif
                @if ($venta->fichas->count())
                    <div class="doc-par"><span class="k">Fichas anexas</span><span class="v">{{ $venta->fichas->count() }}</span></div>
                @endif
            </x-ui.card>
        </div>
    </div>

    @include('structure.commercial_management._documento_estilos')

    <style>
        .doc-resumen-linea { margin:-10px 0 18px; color:var(--muted); font-size:13.5px; }
        .doc-resumen-linea .badge { margin-left:6px; }
        .doc-barra { margin-top:14px; height:6px; border-radius:3px; background:var(--surface-2); overflow:hidden; }
        .doc-barra span { display:block; height:100%; background:var(--green); }
        .doc-avance { margin:7px 0 0; color:var(--muted); font-size:12.5px; }
    </style>
@endsection
