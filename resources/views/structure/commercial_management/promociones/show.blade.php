@extends('layouts.dashboard')
@section('title', $promocion->nombre)
@section('page-title', $promocion->nombre)
@section('page-sub', 'Detalle y envío de la promoción')

@section('content')
    @if(session('status'))
        <div class="badge badge--info" style="display:block; padding:12px 16px; margin-bottom:18px; font-size:13.5px;">{{ session('status') }}</div>
    @endif
    @if(session('error'))
        <div class="badge badge--danger" style="display:block; padding:12px 16px; margin-bottom:18px; font-size:13.5px;">{{ session('error') }}</div>
    @endif

    @unless($whatsappConfigurado)
        <div class="danger-box" style="margin-bottom:18px;">
            <strong>WhatsApp no está configurado.</strong>
            <p class="muted" style="margin:6px 0 0; font-size:13.5px;">Agrega <code>WHATSAPP_TOKEN</code>, <code>WHATSAPP_PHONE_NUMBER_ID</code> y una plantilla aprobada (<code>WHATSAPP_TEMPLATE_PROMO</code>) en el archivo <code>.env</code> para poder enviar esta promoción.</p>
        </div>
    @endunless

    <x-ui.card style="margin-bottom:18px;">
        <div style="display:flex; align-items:center; gap:12px; margin-bottom:20px;">
            <div class="qbox-ico blue" style="width:42px; height:42px;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M8 14s1.5 2 4 2 4-2 4-2"/><line x1="9" y1="9" x2="9.01" y2="9"/><line x1="15" y1="9" x2="15.01" y2="9"/></svg>
            </div>
            <div>
                <div style="font-weight:700; font-size:16px;">Resumen de la Promoción</div>
                <div class="muted" style="font-size:13px;">Revisa el mensaje y el segmento antes de enviar.</div>
            </div>
        </div>

        <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:16px; flex-wrap:wrap;">
            <div style="display:flex; gap:16px;">
                @if($promocion->imagen_path)
                    <img src="{{ asset('storage/'.$promocion->imagen_path) }}" alt="" style="width:96px; height:96px; object-fit:cover; border-radius:12px; border:1px solid var(--field-border);">
                @endif
                <div>
                    <div style="font-weight:700; font-size:16px; margin-bottom:4px;">{{ $promocion->mensaje }}</div>
                    <div class="muted" style="font-size:13px;">
                        Segmento: {{ $promocion->categoria?->nombre ?? 'Todos los clientes con recibe_promocion = Sí' }}
                        @if($promocion->producto) · Producto: {{ $promocion->producto->tipo_equipo }} @endif
                        @if($promocion->paquete) · Paquete: {{ $promocion->paquete->nombre }} @endif
                    </div>
                    <span class="badge badge--info" style="margin-top:8px; display:inline-block;">Estado: {{ ucfirst($promocion->estado) }}</span>
                </div>
            </div>

            <form method="POST" action="{{ route('commercial.promociones.send', $promocion) }}" onsubmit="return confirm('¿Enviar esta promoción a los destinatarios pendientes?');">
                @csrf
                <button type="submit" class="btn" @disabled(!$whatsappConfigurado || $destinatarios->isEmpty())>
                    Enviar promoción ({{ $destinatarios->count() - $idsYaEnviados->count() }} pendientes)
                </button>
            </form>
        </div>
    </x-ui.card>

    <x-ui.card style="margin-bottom:18px;">
        <div style="display:flex; align-items:center; gap:12px; margin-bottom:20px;">
            <div class="qbox-ico green" style="width:42px; height:42px;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v12M15 9.5a3 3 0 0 0-3-1.5c-1.7 0-3 1-3 2.5S10.3 13 12 13s3 1 3 2.5-1.3 2.5-3 2.5a3 3 0 0 1-3-1.5"/></svg>
            </div>
            <div>
                <div style="font-weight:700; font-size:16px;">Métricas de Envío</div>
                <div class="muted" style="font-size:13px;">Datos reales tomados del historial de envíos, no de logs.</div>
            </div>
        </div>

        @php
            $enviados = $promocion->envios->where('estado', 'enviado')->count();
            $fallidos = $promocion->envios->where('estado', 'fallido')->count();
            $sinDestino = $promocion->envios->where('estado', 'sin_destino')->count();
        @endphp
        <div class="qgrid" style="grid-template-columns:repeat(4, 1fr);">
            <div class="qbox">
                <div class="qbox-head">
                    <div class="qbox-ico blue"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg></div>
                    <span class="qbox-label">Destinatarios candidatos</span>
                </div>
                <div class="qbox-value">{{ $destinatarios->count() }}</div>
            </div>
            <div class="qbox">
                <div class="qbox-head">
                    <div class="qbox-ico green"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6 9 17l-5-5"/></svg></div>
                    <span class="qbox-label">Enviados</span>
                </div>
                <div class="qbox-value" style="color:var(--green);">{{ $enviados }}</div>
            </div>
            <div class="qbox">
                <div class="qbox-head">
                    <div class="qbox-ico orange"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 9v4M12 17h.01"/><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/></svg></div>
                    <span class="qbox-label">Fallidos</span>
                </div>
                <div class="qbox-value" style="color:var(--danger);">{{ $fallidos }}</div>
            </div>
            <div class="qbox">
                <div class="qbox-head">
                    <div class="qbox-ico purple"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="3" width="15" height="13" rx="1"/><path d="M16 8h4l3 3v5h-7"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg></div>
                    <span class="qbox-label">Sin teléfono válido</span>
                </div>
                <div class="qbox-value">{{ $sinDestino }}</div>
            </div>
        </div>
    </x-ui.card>

    <x-ui.card>
        <div style="display:flex; align-items:center; gap:12px; margin-bottom:20px;">
            <div class="qbox-ico blue" style="width:42px; height:42px;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            </div>
            <div>
                <div style="font-weight:700; font-size:16px;">Destinatarios</div>
                <div class="muted" style="font-size:13px;">Clientes que cumplen el segmento y aceptaron recibir promociones.</div>
            </div>
        </div>
        <div style="overflow-x:auto;">
            <table>
                <thead>
                    <tr>
                        <th>Cliente</th>
                        <th>Teléfono</th>
                        <th>Estado de envío</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($destinatarios as $cliente)
                        @php
                            $envio = $promocion->envios->firstWhere('cliente_id', $cliente->id);
                        @endphp
                        <tr>
                            <td>{{ $cliente->nombre }} {{ $cliente->apellido }}</td>
                            <td>{{ $cliente->telefono ?? '-' }}</td>
                            <td>
                                @if($envio)
                                    @php
                                        $badgeClass = match($envio->estado) {
                                            'enviado' => '',
                                            'fallido', 'sin_destino' => 'badge--danger',
                                            default => 'badge--info',
                                        };
                                    @endphp
                                    <span class="badge {{ $badgeClass }}" title="{{ $envio->error_detalle }}">{{ ucfirst(str_replace('_', ' ', $envio->estado)) }}</span>
                                @else
                                    <span class="muted" style="font-size:13px;">Pendiente</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" style="text-align:center; padding:32px; color:var(--muted);">
                                No hay clientes que cumplan el segmento y tengan activado "Recibe promoción".
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-ui.card>

    <style>
        :root { --field-border: #c9ccd2; }
        :root[data-theme="dark"] { --field-border: var(--border); }

        .qbox-ico { border-radius:11px; display:flex; align-items:center; justify-content:center; flex:0 0 auto; }
        .qbox-ico.blue { background:var(--primary-soft); color:var(--primary); }
        .qbox-ico.green { background:var(--green-soft); color:var(--green); }
        .qbox-ico.orange { background:var(--accent-soft); color:var(--accent); }
        .qbox-ico.purple { background:rgba(147,51,234,.12); color:#9333ea; }

        .qgrid { display:grid; grid-template-columns:repeat(3, 1fr); gap:16px; }
        .qbox { border:1px solid var(--field-border); border-radius:12px; padding:14px 16px; background:var(--surface); display:flex; flex-direction:column; }
        .qbox-head { display:flex; align-items:center; gap:10px; margin-bottom:10px; }
        .qbox-head .qbox-ico { width:32px; height:32px; }
        .qbox-label { font-size:13px; font-weight:600; color:var(--muted); }
        .qbox-value { font-size:21px; font-weight:800; }

        @media (max-width:900px) { .qgrid { grid-template-columns:repeat(2, 1fr); } }
        @media (max-width:640px) { .qgrid { grid-template-columns:1fr; } }
    </style>
@endsection
