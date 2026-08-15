@extends('structure.commercial_management.erp')

@section('title', 'Venta '.$venta->folio)

@section('erp_content')
    @php $badge = match($venta->estado) { 'confirmada' => 'ok', 'facturada' => 'info', 'cancelada' => 'danger', default => 'neutral' }; @endphp

    <div class="erp-head">
        <div class="erp-head-l">
            <span class="erp-ic">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
            </span>
            <div>
                <h1 class="erp-h1">{{ $venta->folio }} <span class="erp-badge {{ $badge }}"><span class="dot"></span>{{ $venta->estadoLabel() }}</span></h1>
                <p class="erp-sub">{{ $venta->customer?->nombre }} {{ $venta->customer?->apellido }} · {{ $venta->created_at?->format('d/m/Y') }}@if($venta->cotizacion) · desde {{ $venta->cotizacion->folio }} @endif</p>
            </div>
        </div>
        <div class="erp-actions">
            <a href="{{ route('commercial.ventas.index') }}" class="erp-btn ghost sm">Regresar</a>
            <a href="{{ route('commercial.ventas.edit', $venta) }}" class="erp-btn ghost sm">Editar</a>
            <a href="{{ route('commercial.ventas.pdf', $venta) }}" target="_blank" class="erp-btn ghost sm">PDF</a>
            <a href="{{ route('commercial.facturas.create', ['venta' => $venta->id]) }}" class="erp-btn sm">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                Generar borrador de factura
            </a>
        </div>
    </div>

    <div class="erp-two">
        <div style="display:flex; flex-direction:column; gap:20px;">
            <div class="erp-card">
                <div class="erp-table-wrap">
                    <table class="erp-table">
                        <thead><tr><th>Equipo</th><th>Modelo</th><th>Marca</th><th>Cant.</th><th>P. Unit.</th><th>Sobrep.</th><th>Importe</th></tr></thead>
                        <tbody>
                            @foreach ($venta->items as $it)
                                <tr>
                                    <td class="erp-strong">{{ $it->nombre }}</td>
                                    <td>{{ $it->modelo ?? '—' }}</td>
                                    <td>{{ $it->marca ?? '—' }}</td>
                                    <td>{{ $it->cantidad }}</td>
                                    <td>@if($it->es_regalo)<span class="erp-badge ok">Regalo</span>@else ${{ number_format($it->precio_unitario, 2) }} @endif</td>
                                    <td>${{ number_format($it->sobreprecio, 2) }}</td>
                                    <td class="erp-strong">@if($it->es_regalo)—@else ${{ number_format($it->importe(), 2) }} @endif</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            @if ($venta->modalidad === 'financiamiento' && $venta->pagos->count())
                <div class="erp-card pad">
                    <h3 style="margin:0 0 12px; font-size:15px;">Plan de pagos · {{ $venta->num_meses }} meses</h3>
                    <div class="erp-table-wrap">
                        <table class="erp-table">
                            <thead><tr><th>Pago</th><th>Fecha</th><th>%</th><th>Monto</th></tr></thead>
                            <tbody>
                                @foreach ($venta->pagos as $p)
                                    <tr><td class="erp-strong">{{ $p->nombre }}</td><td style="color:var(--muted);">{{ optional($p->fecha)->format('d/m/Y') }}</td><td>{{ $p->porcentaje }}%</td><td class="erp-strong">${{ number_format($p->monto, 2) }}</td></tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>

        <div class="erp-card pad">
            <h3 style="margin:0 0 14px; font-size:15px;">Resumen</h3>
            @php $rows = [['Subtotal', $venta->subtotal], ['Descuento', -$venta->descuento_monto], ['Envío', $venta->envio], ['IVA', $venta->iva_monto]]; @endphp
            @foreach ($rows as [$lbl, $val])
                <div style="display:flex; justify-content:space-between; padding:8px 0; border-bottom:1px dashed var(--border); font-size:14px;"><span class="erp-sub" style="margin:0;">{{ $lbl }}</span><span>${{ number_format($val, 2) }}</span></div>
            @endforeach
            <div style="display:flex; justify-content:space-between; padding:14px 0 4px; font-size:19px; font-weight:800;"><span>Total</span><span>${{ number_format($venta->total, 2) }}</span></div>
            @if ($venta->valor_a_cuenta > 0)
                <div style="display:flex; justify-content:space-between; padding:8px 0; font-size:14px; color:var(--muted);"><span>Valor a cuenta</span><span>-${{ number_format($venta->valor_a_cuenta, 2) }}</span></div>
                <div style="display:flex; justify-content:space-between; padding:8px 0 0; font-size:16px; font-weight:800; color:var(--green);"><span>Total del contrato</span><span>${{ number_format($venta->total_contrato, 2) }}</span></div>
            @endif
        </div>
    </div>
@endsection
