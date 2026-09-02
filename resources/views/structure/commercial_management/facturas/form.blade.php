@extends('structure.commercial_management.erp')

@section('title', 'Nuevo borrador de factura')

@section('erp_content')
    <div class="erp-head">
        <div class="erp-head-l">
            <span class="erp-ic">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
            </span>
            <div>
                <h1 class="erp-h1">Borrador de factura</h1>
                <p class="erp-sub">Pre-factura interna @if($venta) desde venta {{ $venta->folio }} @endif · sin validez fiscal</p>
            </div>
        </div>
        <div class="erp-actions">
            <a href="{{ url()->previous() }}" class="erp-btn ghost">Regresar</a>
            <button type="submit" form="facturaForm" class="erp-btn">Generar borrador</button>
        </div>
    </div>

    @if ($errors->any())
        <div class="erp-card pad" style="margin-bottom:20px; border-color:var(--danger); background:var(--danger-soft);">
            <ul style="margin:0; padding-left:18px; color:var(--danger); font-size:13px;">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <form id="facturaForm" method="POST" action="{{ route('commercial.facturas.store') }}">
        @csrf
        <input type="hidden" name="venta_id" value="{{ $venta?->id }}">
        <input type="hidden" name="customer_id" value="{{ $cliente?->id }}">

        <div class="erp-two">
            <div class="erp-card pad">
                <h3 style="margin:0 0 16px; font-size:15px;">Datos fiscales</h3>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px;">
                    <div>
                        <label class="fac-lbl">RFC</label>
                        <input type="text" name="rfc" class="fac-input" value="{{ old('rfc', $cliente?->rfc) }}" placeholder="RFC del receptor">
                    </div>
                    <div>
                        <label class="fac-lbl">Razón social</label>
                        <input type="text" name="razon_social" class="fac-input" value="{{ old('razon_social', trim(($cliente?->nombre ?? '').' '.($cliente?->apellido ?? ''))) }}" placeholder="Razón social / nombre">
                    </div>
                    <div>
                        <label class="fac-lbl">Uso de CFDI</label>
                        <select name="uso_cfdi" class="fac-input">
                            <option value="">— Selecciona —</option>
                            <option value="G01">G01 · Adquisición de mercancías</option>
                            <option value="G03">G03 · Gastos en general</option>
                            <option value="I04">I04 · Equipo de cómputo</option>
                            <option value="I08">I08 · Otra maquinaria y equipo</option>
                            <option value="P01">P01 · Por definir</option>
                        </select>
                    </div>
                    <div>
                        <label class="fac-lbl">Forma de pago</label>
                        <select name="forma_pago" class="fac-input">
                            <option value="">— Selecciona —</option>
                            <option value="01">01 · Efectivo</option>
                            <option value="03">03 · Transferencia electrónica</option>
                            <option value="04">04 · Tarjeta de crédito</option>
                            <option value="28">28 · Tarjeta de débito</option>
                            <option value="99">99 · Por definir</option>
                        </select>
                    </div>
                    <div>
                        <label class="fac-lbl">Método de pago</label>
                        <select name="metodo_pago" class="fac-input">
                            <option value="">— Selecciona —</option>
                            <option value="PUE">PUE · Pago en una exhibición</option>
                            <option value="PPD">PPD · Pago en parcialidades o diferido</option>
                        </select>
                    </div>
                </div>
                <div style="margin-top:14px;">
                    <label class="fac-lbl">Observaciones</label>
                    <textarea name="observaciones" rows="3" class="fac-input" placeholder="Notas internas del borrador">{{ old('observaciones') }}</textarea>
                </div>

                @if ($venta)
                    <h3 style="margin:22px 0 10px; font-size:14px;">Conceptos (desde {{ $venta->folio }})</h3>
                    <div class="erp-table-wrap" style="border:1px solid var(--border); border-radius:12px;">
                        <table class="erp-table">
                            <thead><tr><th>Equipo</th><th>Modelo</th><th>Cant.</th><th>P. Unit.</th><th>Importe</th></tr></thead>
                            <tbody>
                                @foreach ($venta->items as $it)
                                    <tr>
                                        <td class="erp-strong">{{ $it->nombre }}</td>
                                        <td>{{ $it->modelo ?? '—' }}</td>
                                        <td>{{ $it->cantidad }}</td>
                                        <td>@if($it->es_regalo)Regalo @else ${{ number_format($it->precio_unitario, 2) }} @endif</td>
                                        <td>@if($it->es_regalo)— @else ${{ number_format($it->importe(), 2) }} @endif</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="erp-sub" style="margin-top:16px;">Sin venta de origen: se generará un borrador vacío que podrás completar después.</p>
                @endif
            </div>

            <div class="erp-card pad">
                <h3 style="margin:0 0 12px; font-size:15px;">Cliente</h3>
                @if ($cliente)
                    <p style="font-weight:700; margin:0;">{{ $cliente->nombre }} {{ $cliente->apellido }}</p>
                    <p class="erp-sub" style="margin:4px 0 0;">{{ $cliente->rfc ?? 'Sin RFC' }} · {{ $cliente->correo ?? 'sin correo' }}</p>
                @else
                    <p class="erp-sub" style="margin:0;">No se especificó cliente.</p>
                @endif

                @if ($venta)
                    <div style="margin-top:16px; border-top:1px solid var(--border); padding-top:12px;">
                        <div style="display:flex; justify-content:space-between; padding:7px 0; font-size:14px;"><span class="erp-sub" style="margin:0;">Subtotal</span><span>${{ number_format($venta->subtotal, 2) }}</span></div>
                        <div style="display:flex; justify-content:space-between; padding:7px 0; font-size:14px;"><span class="erp-sub" style="margin:0;">IVA</span><span>${{ number_format($venta->iva_monto, 2) }}</span></div>
                        <div style="display:flex; justify-content:space-between; padding:12px 0 0; font-size:19px; font-weight:800;"><span>Total</span><span>${{ number_format($venta->total, 2) }}</span></div>
                    </div>
                @endif
            </div>
        </div>
    </form>

    <style>
        .fac-lbl { display:block; font-size:12px; font-weight:600; color:var(--muted); margin-bottom:5px; }
        .fac-input { width:100%; padding:11px 12px; border:1px solid var(--border); border-radius:10px; font-size:14px;
                     background:var(--surface); color:var(--text); outline:none; font-family:inherit; }
        .fac-input:focus { border-color:var(--primary); box-shadow:0 0 0 3px rgba(0,122,255,.13); }
    </style>
@endsection
