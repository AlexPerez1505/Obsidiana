@extends('structure.commercial_management.erp')

@section('title', 'Cotización ' . $cotizacion->folio)

@section('erp_content')
    <x-ui.page-header :title="$cotizacion->folio"
                      :back="route('commercial.cotizaciones.index')">
        {{-- Mismo lenguaje que la venta: editar como icono, la acción
             principal como único botón lleno. --}}
        <a href="{{ route('commercial.cotizaciones.edit', $cotizacion) }}" class="btn-icono" title="Editar cotización" aria-label="Editar cotización">
            <x-gravityui-pencil />
        </a>
        <a href="{{ route('commercial.cotizaciones.pdf', $cotizacion) }}" target="_blank" class="btn btn--ghost">
            <x-gravityui-file-text width="15" height="15" />
            PDF
        </a>
        <a href="{{ route('commercial.ventas.create', ['cotizacion' => $cotizacion->id]) }}" class="btn">
            <x-gravityui-shopping-cart width="15" height="15" />
            Convertir a venta
        </a>
    </x-ui.page-header>

    <p class="doc-resumen-linea">
        {{ trim(($cotizacion->customer->nombre ?? '') . ' ' . ($cotizacion->customer->apellido ?? '')) ?: 'Sin cliente' }}
        · {{ $cotizacion->created_at?->format('d/m/Y') }}
        <span class="badge {{ in_array($cotizacion->estado, ['aceptada', 'convertida']) ? 'badge--ok' : 'badge--info' }}">
            {{ $cotizacion->estadoLabel() }}
        </span>
    </p>

    @include('structure.commercial_management._enlace_publico', [
        'url' => $cotizacion->public_token ? route('publico.cotizacion', $cotizacion->public_token) : null,
    ])

    <div class="doc-grid">
        <div class="doc-col">
            <x-ui.card>
                <div class="doc-head">
                    <h3>Equipo</h3>
                    <span class="der">{{ $cotizacion->items->count() }} concepto(s) · {{ $cotizacion->items->sum('cantidad') }} pieza(s)</span>
                </div>

                @include('structure.commercial_management._items_tabla', ['items' => $cotizacion->items])
            </x-ui.card>

            @if ($cotizacion->pagos->count())
                <x-ui.card>
                    <div class="doc-head">
                        <h3>Plan de pagos</h3>
                        @if ($cotizacion->modalidad === 'financiamiento')
                            <span class="der">{{ $cotizacion->num_meses }} meses</span>
                        @endif
                    </div>

                    @foreach ($cotizacion->pagos as $p)
                        <div class="doc-pago">
                            <div class="txt">
                                <div class="t">{{ $p->nombre }}</div>
                                <div class="s">{{ optional($p->fecha)->format('d/m/Y') ?: 'Sin fecha' }}</div>
                            </div>
                            <span class="m">${{ number_format($p->monto, 2) }}</span>
                        </div>
                    @endforeach
                </x-ui.card>
            @endif

            @if ($cotizacion->nota_cliente)
                <x-ui.card>
                    <div class="doc-head"><h3>Nota al cliente</h3></div>
                    <div class="doc-nota">{{ $cotizacion->nota_cliente }}</div>
                </x-ui.card>
            @endif
        </div>

        <div class="doc-col">
            <x-ui.card>
                <div class="doc-head"><h3>Resumen</h3></div>

                <div class="doc-tot"><span class="e">Subtotal</span><span class="v">${{ number_format($cotizacion->subtotal, 2) }}</span></div>
                @if ($cotizacion->descuento_monto > 0)
                    <div class="doc-tot"><span class="e">Descuento</span><span class="v">-${{ number_format($cotizacion->descuento_monto, 2) }}</span></div>
                @endif
                @if ($cotizacion->envio > 0)
                    <div class="doc-tot"><span class="e">Envío</span><span class="v">${{ number_format($cotizacion->envio, 2) }}</span></div>
                @endif
                @if ($cotizacion->aplica_iva)
                    <div class="doc-tot"><span class="e">IVA 16%</span><span class="v">${{ number_format($cotizacion->iva_monto, 2) }}</span></div>
                @endif

                <div class="doc-tot total"><span class="e">Total</span><span class="v">${{ number_format($cotizacion->total, 2) }}</span></div>

                @if ($cotizacion->valor_a_cuenta > 0)
                    <div class="doc-tot"><span class="e">Valor a cuenta</span><span class="v">-${{ number_format($cotizacion->valor_a_cuenta, 2) }}</span></div>
                    <div class="doc-tot aparte"><span class="e">Total del contrato</span><span class="v">${{ number_format($cotizacion->total_contrato, 2) }}</span></div>
                @endif
            </x-ui.card>

            <x-ui.card>
                <div class="doc-head"><h3>Datos</h3></div>

                <div class="doc-par"><span class="k">Modalidad</span><span class="v">{{ ucfirst($cotizacion->modalidad) }}</span></div>
                <div class="doc-par"><span class="k">Atendió</span><span class="v">{{ $cotizacion->seller?->name ?: '—' }}</span></div>
                @if ($cotizacion->lugar_propuesta)
                    <div class="doc-par"><span class="k">Congreso</span><span class="v">{{ $cotizacion->lugar_propuesta }}</span></div>
                @endif
                @if ($cotizacion->fichas->count())
                    <div class="doc-par"><span class="k">Fichas anexas</span><span class="v">{{ $cotizacion->fichas->count() }}</span></div>
                @endif
            </x-ui.card>
        </div>
    </div>

    @include('structure.commercial_management._documento_estilos')

    <style>
        .doc-resumen-linea { margin:-10px 0 18px; color:var(--muted); font-size:13.5px; }
        .doc-resumen-linea .badge { margin-left:6px; }
    </style>
@endsection
