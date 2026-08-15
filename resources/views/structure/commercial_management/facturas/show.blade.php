@extends('structure.commercial_management.erp')

@section('title', 'Factura '.$factura->folio)

@section('erp_content')
    @php $badge = match($factura->estado) { 'emitida' => 'ok', 'cancelada' => 'danger', default => 'warn' }; @endphp

    <div class="erp-head">
        <div class="erp-head-l">
            <span class="erp-ic">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
            </span>
            <div>
                <h1 class="erp-h1">{{ $factura->folio }} <span class="erp-badge {{ $badge }}"><span class="dot"></span>{{ $factura->estadoLabel() }}</span></h1>
                <p class="erp-sub">{{ $factura->customer?->nombre }} {{ $factura->customer?->apellido }} · {{ $factura->created_at?->format('d/m/Y') }}@if($factura->venta) · venta {{ $factura->venta->folio }} @endif</p>
            </div>
        </div>
        <div class="erp-actions">
            <a href="{{ route('commercial.facturas.index') }}" class="erp-btn ghost sm">Regresar</a>
            <a href="{{ route('commercial.facturas.pdf', $factura) }}" target="_blank" class="erp-btn sm">Descargar PDF</a>
        </div>
    </div>

    <div style="display:flex; align-items:center; gap:10px; background:var(--accent-soft); color:var(--accent); border:1px solid var(--accent-soft); padding:12px 16px; border-radius:12px; margin-bottom:20px; font-weight:600; font-size:14px;">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
        Borrador sin validez fiscal — no está timbrado ante el SAT.
    </div>

    <div class="erp-two">
        <div class="erp-card">
            <div class="erp-table-wrap">
                <table class="erp-table">
                    <thead><tr><th>Concepto</th><th>Modelo</th><th>Cant.</th><th>P. Unit.</th><th>Importe</th></tr></thead>
                    <tbody>
                        @forelse ($factura->items as $it)
                            <tr>
                                <td class="erp-strong">{{ $it->nombre }}</td>
                                <td>{{ $it->modelo ?? '—' }}</td>
                                <td>{{ $it->cantidad }}</td>
                                <td>@if($it->es_regalo)<span class="erp-badge ok">Regalo</span>@else ${{ number_format($it->precio_unitario, 2) }} @endif</td>
                                <td class="erp-strong">@if($it->es_regalo)—@else ${{ number_format($it->importe(), 2) }} @endif</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="erp-empty">Borrador sin conceptos.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="erp-card pad">
            <h3 style="margin:0 0 12px; font-size:15px;">Datos fiscales</h3>
            <div style="font-size:14px; line-height:2;">
                <div><span class="erp-sub" style="margin:0;">RFC:</span> {{ $factura->rfc ?? '—' }}</div>
                <div><span class="erp-sub" style="margin:0;">Razón social:</span> {{ $factura->razon_social ?? '—' }}</div>
                <div><span class="erp-sub" style="margin:0;">Uso CFDI:</span> {{ $factura->uso_cfdi ?? '—' }}</div>
                <div><span class="erp-sub" style="margin:0;">Forma de pago:</span> {{ $factura->forma_pago ?? '—' }}</div>
                <div><span class="erp-sub" style="margin:0;">Método de pago:</span> {{ $factura->metodo_pago ?? '—' }}</div>
            </div>
            <div style="margin-top:14px; border-top:1px solid var(--border); padding-top:12px;">
                <div style="display:flex; justify-content:space-between; padding:7px 0; font-size:14px;"><span class="erp-sub" style="margin:0;">Subtotal</span><span>${{ number_format($factura->subtotal, 2) }}</span></div>
                @if ($factura->descuento_monto > 0)<div style="display:flex; justify-content:space-between; padding:7px 0; font-size:14px;"><span class="erp-sub" style="margin:0;">Descuento</span><span>-${{ number_format($factura->descuento_monto, 2) }}</span></div>@endif
                <div style="display:flex; justify-content:space-between; padding:7px 0; font-size:14px;"><span class="erp-sub" style="margin:0;">IVA</span><span>${{ number_format($factura->iva_monto, 2) }}</span></div>
                <div style="display:flex; justify-content:space-between; padding:12px 0 0; font-size:19px; font-weight:800;"><span>Total</span><span>${{ number_format($factura->total, 2) }}</span></div>
            </div>
        </div>
    </div>
@endsection
