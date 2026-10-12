@extends('structure.commercial_management.erp')

@section('title', 'Resumen ' . $service->service_number)

@section('erp_content')
    @php
        $totalRefacciones = $service->spareParts->sum('subtotal');
        $tech = $service->service_type === 'interno' ? $service->internalTechnician : $service->externalTechnician;
    @endphp

    <style>
        .resumen-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 18px; align-items: start; }
        .resumen-card { background: var(--surface); border: 1px solid var(--border); border-radius: 16px; padding: 18px; box-shadow: var(--shadow); }
        .resumen-title { display: flex; align-items: center; gap: 8px; font-size: 15px; font-weight: 700; margin: 0 0 16px; color: var(--text); }
        .resumen-title svg { color: var(--muted); flex-shrink: 0; }
        .resumen-detail { display: flex; align-items: center; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid var(--border); font-size: 13px; }
        .resumen-detail:last-child { border-bottom: none; padding-bottom: 0; }
        .resumen-detail--top { align-items: flex-start; }
        .resumen-label { color: var(--muted); font-weight: 600; letter-spacing: .03em; }
        .resumen-value { color: var(--text); text-align: right; max-width: 60%; }
        .resumen-value--wrap { white-space: pre-wrap; word-break: break-word; }
        .resumen-badge { display: inline-flex; align-items: center; gap: 6px; padding: 3px 10px; border-radius: 999px; font-size: 12px; font-weight: 600; }
        .resumen-badge .dot { width: 6px; height: 6px; border-radius: 50%; background: currentColor; }
        .resumen-badge.ok { background: var(--green-soft); color: var(--green); }
        .resumen-badge.warn { background: var(--accent-soft); color: var(--accent); }
        .resumen-badge.info { background: var(--primary-soft); color: var(--primary); }
        .resumen-badge.neutral { background: var(--surface-2); color: var(--muted); }
        .resumen-list { margin: 0; padding-left: 16px; font-size: 13px; color: var(--text); }
        .resumen-list li { margin-bottom: 6px; }
        .resumen-list li:last-child { margin-bottom: 0; }
        .resumen-empty { text-align: center; padding: 28px 10px; color: var(--muted); }
        .resumen-total-row { border-top: 1px solid var(--border); padding-top: 12px; margin-top: 12px; }
        .resumen-actions { display: flex; align-items: center; justify-content: flex-end; gap: 10px; margin-top: 20px; }
        @media (max-width: 900px) { .resumen-grid { grid-template-columns: 1fr; } }
    </style>

    <div class="erp-head">
        <div class="erp-head-l">
            <h1 class="erp-h1">Resumen de Orden</h1>
            <span class="erp-count">{{ $service->service_number }}</span>
        </div>
        <a href="{{ route('gestion.servicios.historial.aprobaciones.index') }}" class="erp-btn ghost">Volver a aprobaciones</a>
    </div>

    @if (session('success'))
        <div class="resumen-card" style="margin-bottom:18px; border-color:var(--green);">
            <strong style="color:var(--green);">{{ session('success') }}</strong>
        </div>
    @endif

    <div class="resumen-grid">
        <!-- Resumen del servicio -->
        <div class="resumen-card">
            <h3 class="resumen-title">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                Resumen del servicio
            </h3>

            <div class="resumen-detail">
                <span class="resumen-label">CLIENTE</span>
                <span class="resumen-value">{{ $service->customer?->nombre ?? '—' }} {{ $service->customer?->apellido ?? '' }}</span>
            </div>
            <div class="resumen-detail">
                <span class="resumen-label">TELÉFONO</span>
                <span class="resumen-value">{{ $service->customer?->phone ?? 'No registrado' }}</span>
            </div>
            <div class="resumen-detail">
                <span class="resumen-label">CORREO</span>
                <span class="resumen-value">{{ $service->customer?->email ?? 'No registrado' }}</span>
            </div>
            <div class="resumen-detail">
                <span class="resumen-label">TIPO DE SERVICIO</span>
                <span class="resumen-value" style="text-transform:capitalize;">{{ $service->service_type === 'externo' ? 'Mantenimiento externo' : 'Mantenimiento interno' }}</span>
            </div>
            <div class="resumen-detail">
                <span class="resumen-label">ESTATUS</span>
                <span class="resumen-value" style="text-transform:capitalize;">{{ $service->status }}</span>
            </div>

            <div class="resumen-detail">
                <span class="resumen-label">FECHA DE REGISTRO</span>
                <span class="resumen-value">{{ $service->created_at?->format('d/m/Y H:i') ?? '—' }}</span>
            </div>
            <div class="resumen-detail">
                <span class="resumen-label">TIPO DE EQUIPO</span>
                <span class="resumen-value">{{ $service->serviceEquipment?->type_text ?? '—' }} | {{ $service->serviceEquipment?->subtype_text ?? '—' }}</span>
            </div>
            <div class="resumen-detail">
                <span class="resumen-label">MARCA / MODELO</span>
                <span class="resumen-value">{{ $service->serviceEquipment?->brand_text ?? '—' }} {{ $service->serviceEquipment?->model_text ?? '—' }}</span>
            </div>
            <div class="resumen-detail">
                <span class="resumen-label">NO. DE SERIE</span>
                <span class="resumen-value">{{ $service->serviceEquipment?->serial_number ?? '—' }}</span>
            </div>
            <div class="resumen-detail resumen-detail--top">
                <span class="resumen-label">DESCRIPCIÓN</span>
                <span class="resumen-value resumen-value--wrap">{{ $service->serviceEquipment?->description ?? '—' }}</span>
            </div>
            <div class="resumen-detail resumen-detail--top">
                <span class="resumen-label">OBSERVACIONES</span>
                <span class="resumen-value resumen-value--wrap">{{ $service->serviceEquipment?->observations ?? '—' }}</span>
            </div>
            <div class="resumen-detail">
                <span class="resumen-label">TÉCNICO</span>
                <span class="resumen-value" style="font-weight:700;">{{ $tech?->name ?? '—' }}</span>
            </div>
            @if($service->service_type === 'externo' && $tech)
                <div class="resumen-detail">
                    <span class="resumen-label">ESPECIALIDAD</span>
                    <span class="resumen-value">{{ $tech->specialty ?? 'No registrada' }}</span>
                </div>
                <div class="resumen-detail">
                    <span class="resumen-label">EMPRESA</span>
                    <span class="resumen-value">{{ $tech->company ?? 'No registrada' }}</span>
                </div>
                <div class="resumen-detail">
                    <span class="resumen-label">UBICACIÓN</span>
                    <span class="resumen-value">{{ $tech->location ?? 'No registrada' }}</span>
                </div>
            @endif
            @if($tech)
                <div class="resumen-detail">
                    <span class="resumen-label">TELÉFONO TÉCNICO</span>
                    <span class="resumen-value">{{ $tech->phone ?? 'No registrado' }}</span>
                </div>
                <div class="resumen-detail">
                    <span class="resumen-label">CORREO TÉCNICO</span>
                    <span class="resumen-value">{{ $tech->email ?? 'No registrado' }}</span>
                </div>
            @endif
        </div>

        <!-- Cotización de refacciones -->
        <div class="resumen-card">
            <h3 class="resumen-title">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                Cotización de refacciones
            </h3>

            @if($service->spareParts->isEmpty())
                <div class="resumen-empty">No se agregaron refacciones.</div>
            @else
                <form action="{{ route('gestion.servicios.historial.aprobaciones.cotizacion', $service) }}" method="POST" id="cotizacion-precios-form">
                    @csrf
                    @foreach($service->spareParts as $part)
                        <div class="resumen-detail resumen-detail--top" data-part-row data-cantidad="{{ $part->cantidad }}">
                            <span class="resumen-label" style="max-width:45%;">
                                {{ $part->nombre }} <span style="color:var(--muted);">x{{ $part->cantidad }}</span>
                                @if (is_null($part->refaccion_id))
                                    <span class="resumen-badge warn" style="margin-left:4px;"><span class="dot"></span>Necesaria</span>
                                @endif
                            </span>
                            <span class="resumen-value" style="display:flex; align-items:center; gap:10px; justify-content:flex-end;">
                                <input type="number" name="precio_parte[{{ $part->id }}]" value="{{ number_format((float) $part->precio_unitario, 2, '.', '') }}" min="0" step="0.01" class="precio-parte-input" aria-label="Precio unitario" style="width:100px; padding:6px 9px; border:1px solid var(--border); border-radius:8px; background:var(--surface-2); color:var(--text); font-size:13px; text-align:right;">
                                <span class="parte-subtotal" style="min-width:80px; font-weight:700;">${{ number_format($part->subtotal, 2) }}</span>
                            </span>
                        </div>
                    @endforeach

                    <div class="resumen-detail">
                        <span class="resumen-label">MANO DE OBRA</span>
                        <span class="resumen-value">
                            <input type="number" name="mano_obra" id="aprob-mano-obra" value="{{ number_format((float) ($service->mano_obra ?? 0), 2, '.', '') }}" min="0" step="0.01" style="width:110px; padding:6px 9px; border:1px solid var(--border); border-radius:8px; background:var(--surface-2); color:var(--text); font-size:13px; text-align:right;">
                        </span>
                    </div>
                    <div class="resumen-detail resumen-total-row">
                        <span class="resumen-label">TOTAL COTIZACIÓN</span>
                        <span class="resumen-value" style="font-size:18px; font-weight:800; color:var(--primary);" id="aprob-gran-total">${{ number_format($totalRefacciones + ($service->mano_obra ?? 0), 2) }}</span>
                    </div>

                    <div style="display:flex; justify-content:flex-end; margin-top:14px;">
                        <button type="submit" class="erp-btn sm">Guardar precios</button>
                    </div>
                </form>
            @endif
        </div>
    </div>

    <div class="resumen-grid" style="margin-top:18px;">
        <div class="resumen-card">
            <h3 class="resumen-title">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                Enviar al cliente
            </h3>
            @if ($service->customer?->gmail)
                @php
                    $customerUrl = url()->signedRoute('gestion.servicios.historial.aprobaciones.cliente', $service, now()->addDays(7));
                    $subject = urlencode('Aprobación de servicio ' . $service->service_number);
                    $body = urlencode('Hola, por favor revisa el resumen del servicio y confirma tu aprobación: ' . $customerUrl);
                @endphp
                <div class="resumen-detail">
                    <span class="resumen-label">CORREO DEL CLIENTE</span>
                    <span class="resumen-value">{{ $service->customer->gmail }}</span>
                </div>
                <div style="display:flex; align-items:center; gap:10px; margin-top:14px; flex-wrap:wrap;">
                    <input type="text" id="customerLink" value="{{ $customerUrl }}" readonly style="flex:1; min-width:260px; padding:9px 12px; border:1px solid var(--border); border-radius:8px; background:var(--surface-2); color:var(--text); font-size:13px;">
                    <a href="mailto:{{ $service->customer->gmail }}?subject={{ $subject }}&body={{ $body }}" class="erp-btn" target="_blank" rel="noopener">Enviar cliente</a>
                </div>
            @else
                <p style="color:var(--muted); font-size:13px; margin:0;">El cliente no tiene correo registrado.</p>
            @endif
        </div>

        @if ($service->customer_decision)
            <div class="resumen-card">
                <h3 class="resumen-title">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
                    Decisión del cliente
                </h3>
                <div class="resumen-detail">
                    <span class="resumen-label">RESPUESTA</span>
                    <span class="resumen-value" style="text-transform:capitalize; font-weight:700; color:{{ $service->customer_decision === 'aprobado' ? 'var(--green)' : 'var(--danger)' }}">{{ $service->customer_decision }}</span>
                </div>
                <div class="resumen-detail">
                    <span class="resumen-label">FECHA</span>
                    <span class="resumen-value">{{ $service->customer_decision_at?->format('d/m/Y H:i') ?? '—' }}</span>
                </div>
            </div>
        @endif
    </div>

    <div class="resumen-actions">
        <form action="{{ route('gestion.servicios.historial.deny', $service) }}" method="POST" data-confirm="¿Denegar esta orden?">
            @csrf
            <button type="submit" class="erp-btn danger">Denegar</button>
        </form>
        <form action="{{ route('gestion.servicios.historial.approve', $service) }}" method="POST">
            @csrf
            <button type="submit" class="erp-btn">Aprobar</button>
        </form>
    </div>

    @push('scripts')
    <script>
    (function () {
        function money(n) {
            return '$' + Number(n).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
        }
        function recalc() {
            let total = 0;
            document.querySelectorAll('[data-part-row]').forEach(function (row) {
                const cantidad = parseFloat(row.getAttribute('data-cantidad')) || 0;
                const precio = parseFloat(row.querySelector('.precio-parte-input')?.value) || 0;
                const subtotal = cantidad * precio;
                const sub = row.querySelector('.parte-subtotal');
                if (sub) sub.textContent = money(subtotal);
                total += subtotal;
            });
            const mo = parseFloat(document.getElementById('aprob-mano-obra')?.value) || 0;
            const gt = document.getElementById('aprob-gran-total');
            if (gt) gt.textContent = money(total + mo);
        }
        document.getElementById('cotizacion-precios-form')?.addEventListener('input', recalc);
    })();
    </script>
    @endpush
@endsection
