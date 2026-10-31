@extends('structure.commercial_management.erp')

@section('title', 'Servicio externo · ' . ($service->service_number ?? 'OS-' . $service->id))

@section('erp_content')
    @php
        $equipment = $service->serviceEquipment;
        $customerName = trim(($service->customer?->nombre ?? '') . ' ' . ($service->customer?->apellido ?? '')) ?: 'Sin cliente';
        $badge = match($service->status) {
            'entregado', 'completado' => 'ok',
            'en_progreso' => 'info',
            'aprobado' => 'warn',
            default => 'neutral',
        };
        $finalizado = in_array($service->status, ['entregado', 'cancelado', 'rechazado']);
        $carta = $service->garantiaDocumento;
        $qrVigente = $service->qr_token && (! $service->qr_expires_at || $service->qr_expires_at->isFuture());
    @endphp

    <div class="erp-head">
        <div class="erp-head-l">
            <a href="{{ route('gestion.servicios.servicio_externo') }}" class="erp-back" title="Volver">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
            </a>
            <h1 class="erp-h1">Servicio {{ $service->service_number ?? ('OS-' . $service->id) }}</h1>
            <span class="erp-badge {{ $badge }}"><span class="dot"></span>{{ ucfirst(str_replace('_', ' ', $service->status ?? '—')) }}</span>
            <span class="erp-badge {{ $service->service_type === 'externo' ? 'warn' : 'info' }}"><span class="dot"></span>Externo {{ $service->service_type ?? '—' }}</span>
        </div>
        <div style="display:flex; gap:8px; flex-wrap:wrap;">
            <a href="{{ route('gestion.servicios.area_endoscopia.edit', $service) }}" class="erp-btn ghost">Editar</a>
            @if (! $finalizado)
                <form action="{{ route('gestion.servicios.servicio_externo.finalizar', $service) }}" method="POST" onsubmit="return confirm('¿Finalizar este servicio?')">
                    @csrf
                    <button type="submit" class="erp-btn">Finalizar servicio</button>
                </form>
            @endif
            <form action="{{ route('gestion.servicios.area_endoscopia.destroy', $service) }}" method="POST" onsubmit="return confirm('¿Eliminar este servicio?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="erp-btn ghost" style="color:var(--danger); border-color:var(--danger);">Eliminar</button>
            </form>
        </div>
    </div>

    <div class="erp-card pad" style="margin-bottom:16px;">
        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:18px;">
            <div>
                <div style="font-size:12px; color:var(--muted); font-weight:700; text-transform:uppercase; letter-spacing:.03em; margin-bottom:4px;">OS</div>
                <div>{{ $service->service_number ?? ('OS-' . $service->id) }}</div>
            </div>
            <div>
                <div style="font-size:12px; color:var(--muted); font-weight:700; text-transform:uppercase; letter-spacing:.03em; margin-bottom:4px;">Cliente</div>
                <div>{{ $customerName }}</div>
            </div>
            <div>
                <div style="font-size:12px; color:var(--muted); font-weight:700; text-transform:uppercase; letter-spacing:.03em; margin-bottom:4px;">Equipo</div>
                <div>{{ $equipment?->type_text ?? '—' }}</div>
            </div>
            <div>
                <div style="font-size:12px; color:var(--muted); font-weight:700; text-transform:uppercase; letter-spacing:.03em; margin-bottom:4px;">Marca / Modelo / Serie</div>
                <div>{{ $equipment?->brand_text ?? '—' }} / {{ $equipment?->model_text ?? '—' }} / {{ $equipment?->serial_number ?? '—' }}</div>
            </div>
            <div>
                <div style="font-size:12px; color:var(--muted); font-weight:700; text-transform:uppercase; letter-spacing:.03em; margin-bottom:4px;">{{ $service->service_type === 'externo' ? 'Técnico externo' : 'Técnico interno' }}</div>
                <div>
                    {{ $service->service_type === 'externo'
                        ? ($service->externalTechnician?->name ?? $service->internalTechnician?->name ?? '—')
                        : ($service->internalTechnician?->name ?? '—') }}
                </div>
                @if ($service->service_type === 'externo' && $service->externalTechnician?->company)
                    <div style="color:var(--muted); font-size:12px;">{{ $service->externalTechnician->company }}</div>
                @endif
            </div>
            <div>
                <div style="font-size:12px; color:var(--muted); font-weight:700; text-transform:uppercase; letter-spacing:.03em; margin-bottom:4px;">Fecha de registro</div>
                <div>{{ $service->created_at?->format('d/m/Y H:i') ?? '—' }}</div>
            </div>
            @if ($service->finished_at)
                <div>
                    <div style="font-size:12px; color:var(--muted); font-weight:700; text-transform:uppercase; letter-spacing:.03em; margin-bottom:4px;">Fecha de finalización</div>
                    <div>{{ $service->finished_at->format('d/m/Y H:i') }}</div>
                </div>
            @endif
            @if ($equipment?->description)
                <div style="grid-column:1/-1;">
                    <div style="font-size:12px; color:var(--muted); font-weight:700; text-transform:uppercase; letter-spacing:.03em; margin-bottom:4px;">Descripción del equipo</div>
                    <div>{{ $equipment->description }}</div>
                </div>
            @endif
            @if ($equipment?->observations)
                <div style="grid-column:1/-1;">
                    <div style="font-size:12px; color:var(--muted); font-weight:700; text-transform:uppercase; letter-spacing:.03em; margin-bottom:4px;">Observaciones</div>
                    <div>{{ $equipment->observations }}</div>
                </div>
            @endif
        </div>
    </div>

    <div class="erp-card" style="margin-bottom:16px;">
        <div style="padding:14px 16px; border-bottom:1px solid var(--border);">
            <h2 style="margin:0; font-size:15px; font-weight:700;">Documentos</h2>
        </div>
        <div style="padding:16px; display:flex; gap:10px; flex-wrap:wrap;">
            <a href="{{ route('gestion.servicios.mantenimiento.reporte.raw', $service) }}" class="erp-btn ghost">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" width="15" height="15"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                Reporte
            </a>
            @if ($service->service_type === 'interno')
                <a href="{{ route('gestion.servicios.servicio_externo.orden_servicio', $service) }}" class="erp-btn">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" width="15" height="15"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="9" y1="13" x2="15" y2="13"/><line x1="9" y1="17" x2="15" y2="17"/></svg>
                    Generar orden de servicio
                </a>
            @endif
        </div>
        @if (! empty($service->documentos))
            <div style="padding:0 16px 16px; display:flex; gap:10px; flex-wrap:wrap; align-items:center; border-top:1px dashed var(--border); padding-top:14px; margin-top:2px;">
                <span style="font-size:12px; color:var(--muted); font-weight:700; text-transform:uppercase; letter-spacing:.03em;">Guardados:</span>
                @if (! empty($service->documentos['remision']))
                    <a href="{{ asset('storage/' . $service->documentos['remision']) }}" target="_blank" class="erp-btn ghost" style="font-size:13px;">Remisión</a>
                @endif
                @if (! empty($service->documentos['orden_servicio']))
                    <a href="{{ asset('storage/' . $service->documentos['orden_servicio']) }}" target="_blank" class="erp-btn ghost" style="font-size:13px;">Orden de servicio</a>
                @endif
                @if (! empty($service->documentos['generado_at']))
                    <span style="color:var(--muted); font-size:12px;">{{ \Illuminate\Support\Carbon::parse($service->documentos['generado_at'])->format('d/m/Y H:i') }}</span>
                @endif
            </div>
        @endif
    </div>

    @if ($service->service_type === 'externo')
        <div class="erp-card" style="margin-bottom:16px;">
            <div style="padding:14px 16px; border-bottom:1px solid var(--border);">
                <h2 style="margin:0; font-size:15px; font-weight:700;">Código QR de seguimiento</h2>
            </div>
            <div style="padding:16px;">
                @if ($qrVigente)
                    <div id="qr-print-area" style="text-align:center;">
                        <div style="font-weight:800; font-size:16px; margin-bottom:10px; color:var(--text);">
                            Orden {{ $service->service_number ?? ('OS-' . $service->id) }}
                        </div>
                        <div style="display:inline-block; border:1px solid var(--border); border-radius:12px; padding:12px; background:#fff;">
                            {!! \App\Support\CodigoQr::svg(route('qr.show', $service->qr_token), 220) !!}
                        </div>
                        <p style="margin:10px 0 0; font-size:13px; color:var(--muted);">
                            Cliente: {{ $customerName }} · Equipo: {{ $equipment?->type_text ?? '—' }}<br>
                            Válido hasta: <strong>{{ $service->qr_expires_at?->format('d/m/Y H:i') ?? '—' }}</strong>
                        </p>
                    </div>
                @else
                    <p style="margin:0; font-size:14px; color:var(--accent); font-weight:700;">● QR sin generar o expirado</p>
                @endif
                <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap; margin-top:12px;">
                    @if ($qrVigente)
                        <a href="{{ route('qr.print', $service->qr_token) }}" target="_blank" class="erp-btn">
                            Imprimir QR
                        </a>
                    @endif
                    <form action="{{ route('qr.renew', $service) }}" method="POST">
                        @csrf
                        <button type="submit" class="erp-btn ghost">{{ $qrVigente ? 'Generar nuevo QR' : 'Generar QR' }}</button>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <div class="erp-card">
        <div style="padding:14px 16px; border-bottom:1px solid var(--border); display:flex; align-items:center; justify-content:space-between; gap:12px;">
            <h2 style="margin:0; font-size:15px; font-weight:700;">Carta de garantía</h2>
            <a href="{{ route('gestion.servicios.garantia.agregar_carta') }}" class="tbl-link">Agregar carta al catálogo</a>
        </div>
        <div style="padding:16px;">
            @if ($carta)
                <div style="margin-bottom:14px; display:flex; gap:24px; flex-wrap:wrap; font-size:14px;">
                    <div><span style="color:var(--muted);">Folio:</span> <strong>{{ $carta->folio }}</strong></div>
                    <div><span style="color:var(--muted);">Nombre:</span> <strong>{{ $carta->nombre }}</strong></div>
                    <div><span style="color:var(--muted);">Tipo de equipo:</span> <strong>{{ $carta->tipo_equipo }}</strong></div>
                </div>
                @if (! empty($carta->archivos))
                    <div style="margin-bottom:14px; display:flex; gap:8px; flex-wrap:wrap;">
                        @foreach ($carta->archivos as $i => $archivo)
                            <a href="{{ asset('storage/' . $archivo) }}" target="_blank" class="erp-btn ghost" style="font-size:13px;">Archivo {{ $i + 1 }}</a>
                        @endforeach
                    </div>
                @endif
            @endif

            <form method="POST" action="{{ route('gestion.servicios.servicio_externo.garantia', $service) }}" style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
                @csrf
                <select name="garantia_documento_id" style="flex:1; min-width:240px; padding:10px 12px; border:1px solid var(--border); border-radius:8px; background:var(--surface); color:var(--text); font-size:14px;">
                    <option value="">— Sin carta de garantía —</option>
                    @foreach ($cartas as $doc)
                        <option value="{{ $doc->id }}" {{ (string) old('garantia_documento_id', $service->garantia_documento_id) === (string) $doc->id ? 'selected' : '' }}>
                            {{ $doc->folio }} · {{ $doc->nombre }} ({{ $doc->tipo_equipo }})
                        </option>
                    @endforeach
                </select>
                <button type="submit" class="erp-btn">{{ $carta ? 'Cambiar carta' : 'Añadir carta' }}</button>
            </form>
        </div>
    </div>
@endsection
