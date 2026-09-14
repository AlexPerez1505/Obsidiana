@extends('structure.commercial_management.erp')

@section('title', 'Venta ' . $venta->folio)

@section('erp_content')
    {{-- Cabecera estándar del sistema: la flecha de regresar es un icono,
         no un botón que compita con las acciones reales. --}}
    <x-ui.page-header :title="$venta->folio"
                      :back="route('commercial.ventas.index')">
        @if (! $venta->cancelada())
            {{-- Editar es una acción de apoyo: va como icono para no competir
                 con la acción principal de la pantalla. --}}
            <a href="{{ route('commercial.ventas.edit', $venta) }}" class="btn-icono" title="Editar venta" aria-label="Editar venta">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
            </a>
            <button type="button" class="btn-icono" title="Cancelar venta" aria-label="Cancelar venta" data-abrir-cancelar style="color:var(--danger);">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="m15 9-6 6M9 9l6 6"/></svg>
            </button>
        @endif

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

            @if ($venta->tieneGarantia())
            <x-ui.menu-item :href="route('commercial.ventas.garantia', $venta)" blank
                            :detalle="$venta->garantia_meses . ' meses sobre el equipo'">
                <x-slot:icono>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/></svg>
                </x-slot:icono>
                Carta garantía
            </x-ui.menu-item>
            @endif
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

        {{-- La orden de salida la trabaja almacén; desde aquí solo se consulta cómo va. --}}
        @if ($venta->ordenSalida)
            @can('salidas.ver')
                <a href="{{ route('inventory.salidas.show', $venta->ordenSalida) }}"
                   class="pill-dato" title="Orden de salida {{ $venta->ordenSalida->folio }}">
                    <span class="et">Salida</span>
                    <span class="val">{{ $venta->ordenSalida->estadoLabel() }}</span>
                </a>
                <a href="{{ route('inventory.salidas.pdf', $venta->ordenSalida) }}" target="_blank" class="btn-icono" title="PDF de la orden de salida" aria-label="PDF de la orden de salida">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><path d="M9 15h6M9 11h2"/></svg>
                </a>
            @else
                <span class="pill-dato" title="Orden de salida {{ $venta->ordenSalida->folio }}">
                    <span class="et">Salida</span>
                    <span class="val">{{ $venta->ordenSalida->estadoLabel() }}</span>
                </span>
            @endcan
        @endif

        @if (! $venta->cancelada())
            <a href="{{ route('commercial.facturas.create', ['venta' => $venta->id]) }}" class="btn">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="15" height="15"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                Borrador de factura
            </a>
        @endif
    </x-ui.page-header>

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

    @if ($venta->cancelada())
        @php $cancelacion = $venta->bitacora->firstWhere('tipo', 'venta_cancelada'); @endphp
        <x-ui.alert type="danger" style="margin-bottom:14px;">
            <b>Venta cancelada</b>
            @if ($cancelacion)
                el {{ $cancelacion->created_at?->format('d/m/Y H:i') }}{{ $cancelacion->user ? ' por '.$cancelacion->user->name : '' }}.
                Motivo: {{ $cancelacion->datos['motivo'] ?? '—' }}.
                @if (($cancelacion->datos['cobrado'] ?? 0) > 0.009)
                    El cliente había pagado ${{ number_format($cancelacion->datos['cobrado'], 2) }}; queda pendiente su devolución.
                @endif
            @endif
            El equipo regresó al inventario. Esta venta ya no se edita ni recibe cobros.
        </x-ui.alert>
    @endif

    <p class="doc-resumen-linea">
        {{ trim(($venta->customer->nombre ?? '') . ' ' . ($venta->customer->apellido ?? '')) ?: 'Sin cliente' }}
        · {{ $venta->created_at?->format('d/m/Y') }}
        @if ($venta->cotizacion) · desde {{ $venta->cotizacion->folio }} @endif
        <span class="badge {{ $venta->estado === 'cancelada' ? 'badge--danger' : 'badge--ok' }}">{{ $venta->estadoLabel() }}</span>
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
                        {{ $venta->garantiaLabel() }}
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

    {{-- ===================== Modal: cancelar venta ===================== --}}
    @if (! $venta->cancelada())
        <dialog class="vc-modal" id="modalCancelar" aria-labelledby="modalCancelarT">
            <form method="POST" action="{{ route('commercial.ventas.cancelar', $venta) }}" class="vc-box">
                @csrf
                <div class="vc-head">
                    <h3 id="modalCancelarT">Cancelar la venta {{ $venta->folio }}</h3>
                    <button type="button" class="vc-x" data-cerrar-cancelar aria-label="Cerrar">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
                    </button>
                </div>

                <p class="vc-sub">
                    La venta no se borra: queda como cancelada con su historial. El equipo regresa al inventario y la orden de salida se cancela.
                    @if ($venta->totalCobrado() > 0.009)
                        <b style="color:var(--danger);">El cliente ya pagó ${{ number_format($venta->totalCobrado(), 2) }}: los cobros se conservan y quedará pendiente devolverlos.</b>
                    @endif
                </p>

                <x-ui.form-group label="Motivo *" for="cancelMotivo">
                    <textarea id="cancelMotivo" name="motivo" rows="3" required maxlength="500" placeholder="Ej. el cliente desistió, se capturó por error, se sustituye por otra venta...">{{ old('motivo') }}</textarea>
                </x-ui.form-group>
                <x-ui.form-group label="{{ auth()->user()->approval_pin_hash ? 'PIN de aprobación *' : 'Tu contraseña *' }}" for="cancelPassword">
                    <input type="password" id="cancelPassword" name="password" required autocomplete="current-password">
                </x-ui.form-group>

                <div class="vc-foot">
                    <button type="button" class="btn btn--ghost" data-cerrar-cancelar>Volver</button>
                    <button type="submit" class="btn" style="background:var(--danger); border-color:var(--danger);">Cancelar venta</button>
                </div>
            </form>
        </dialog>

        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const modal = document.getElementById('modalCancelar');
                if (!modal) return;
                document.querySelectorAll('[data-abrir-cancelar]').forEach(b => b.addEventListener('click', () => { modal.showModal(); document.getElementById('cancelMotivo').focus(); }));
                modal.querySelectorAll('[data-cerrar-cancelar]').forEach(b => b.addEventListener('click', () => modal.close()));
                modal.addEventListener('click', e => { if (e.target === modal) modal.close(); });
                // Si el servidor regresó con error (PIN mal, motivo vacío), se reabre.
                @if ($errors->has('password') || $errors->has('motivo'))
                    modal.showModal();
                @endif
                // Desde el listado se llega con #cancelar para abrirlo directo.
                if (window.location.hash === '#cancelar') modal.showModal();
            });
        </script>
    @endif

    @include('structure.commercial_management._documento_estilos')

    <style>
        .vc-modal { border:none; border-radius:16px; padding:0; width:min(520px, calc(100vw - 32px)); background:var(--surface, #fff); color:var(--text); box-shadow:0 24px 64px rgba(0,0,0,.28); }
        .vc-modal::backdrop { background:rgba(15,23,42,.55); }
        .vc-box { padding:18px; }
        .vc-head { display:flex; align-items:center; gap:10px; margin-bottom:6px; }
        .vc-head h3 { margin:0; font-size:16px; flex:1; }
        .vc-x { border:none; background:transparent; cursor:pointer; color:var(--muted); width:30px; height:30px; border-radius:8px; display:flex; align-items:center; justify-content:center; }
        .vc-x svg { width:18px; height:18px; }
        .vc-sub { margin:0 0 14px; color:var(--muted); font-size:13px; }
        .vc-foot { display:flex; justify-content:flex-end; gap:10px; margin-top:14px; }
        .vc-modal textarea, .vc-modal input[type="password"] { width:100%; padding:9px 11px; border:1px solid var(--border); border-radius:9px; font-size:14px; background:var(--surface); color:var(--text); font-family:inherit; }
        .doc-resumen-linea { margin:-10px 0 18px; color:var(--muted); font-size:13.5px; }
        .doc-resumen-linea .badge { margin-left:6px; }
        .doc-barra { margin-top:14px; height:6px; border-radius:3px; background:var(--surface-2); overflow:hidden; }
        .doc-barra span { display:block; height:100%; background:var(--green); }
        .doc-avance { margin:7px 0 0; color:var(--muted); font-size:12.5px; }
    </style>
@endsection
