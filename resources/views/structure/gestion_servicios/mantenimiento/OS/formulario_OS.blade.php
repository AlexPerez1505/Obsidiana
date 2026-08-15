@extends('structure.gestion_servicios.layout')

@section('title', 'Generar Orden de Servicio')

@section('service_content')
    @php
        $customerName = trim(($service->customer->nombre ?? '') . ' ' . ($service->customer->apellido ?? ''));
        $equipment = $service->serviceEquipment;
        $maintenance = $service->maintenance ?? new \App\Models\ServiceMaintenance(['service_id' => $service->id]);
        $checklist = $maintenance->checklist ?? [];
        $cotizacionRefacciones = $maintenance->refacciones ?? [];
        $partidas = old('partidas_remision', $maintenance->partidas_remision ?? []);
        if (empty($partidas) && ! empty($cotizacionRefacciones)) {
            $partidas = collect($cotizacionRefacciones)->map(fn ($r) => [
                'item' => 'Refacción',
                'descripcion' => $r['concepto'] ?? $r['nombre'] ?? 'Sin descripción',
                'unidad' => 'SERVICIO',
                'cantidad' => (float) ($r['cantidad'] ?? 1),
                'precio_unitario' => (float) ($r['precio'] ?? (($r['total'] ?? 0) / max((float) ($r['cantidad'] ?? 1), 1))),
            ])->all();
        }
        if (empty($partidas)) {
            $partidas = [
                ['item' => 'Partida 1', 'descripcion' => 'Mantenimiento preventivo general', 'unidad' => 'SERVICIO', 'cantidad' => 1, 'precio_unitario' => 0],
                ['item' => 'Partida 2', 'descripcion' => 'Limpieza, inspección, ajuste y pruebas', 'unidad' => 'SERVICIO', 'cantidad' => 1, 'precio_unitario' => 0],
            ];
        }
        $defaultDescription = 'MANTENIMIENTO ' . strtoupper($maintenance->tipo_reparacion ?? 'PREVENTIVO') . ' A ' . strtoupper($equipment->type_text ?? 'EQUIPO') . ' ' . strtoupper($equipment->brand_text ?? '') . ' ' . strtoupper($equipment->model_text ?? '') . ' CON NÚMERO DE SERIE ' . ($equipment->serial_number ?? 'N/A') . '. ' . ($maintenance->descripcion ?? '') . ' ' . ($maintenance->fallas_encontradas ?? '');
        $techName = $service->externalTechnician
            ? trim(($service->externalTechnician->nombre ?? '') . ' ' . ($service->externalTechnician->apellidos ?? ''))
            : trim(($service->internalTechnician->name ?? '') . ' ' . ($service->internalTechnician->last_name ?? ''));
        $startDate = $service->started_at?->format('Y-m-d') ?? $service->created_at?->format('Y-m-d');
        $endDate = $maintenance->proximo_mantenimiento?->format('Y-m-d') ?? now()->addMonths(3)->format('Y-m-d');
        $proxMonths = $startDate && $endDate ? ceil(now()->parse($startDate)->diffInMonths(now()->parse($endDate))) : 3;
    @endphp

    <style>
        .os-page {
            max-width: 1000px;
            margin: 0 auto;
            padding: 24px;
        }
        .os-card {
            background: #151d2e;
            border: 1px solid rgba(255,255,255,0.06);
            border-radius: 20px;
            padding: 24px;
            margin-bottom: 20px;
        }
        .os-section-title {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 16px;
            font-weight: 700;
            color: #f1f5f9;
            margin: 0 0 18px;
        }
        .os-section-icon {
            width: 22px;
            height: 22px;
            color: #60a5fa;
        }

        .os-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 22px;
            gap: 16px;
            flex-wrap: wrap;
        }
        .os-header-title {
            display: flex;
            align-items: center;
            gap: 14px;
        }
        .os-header-icon {
            width: 44px;
            height: 44px;
            background: rgba(59,130,246,0.15);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #60a5fa;
        }
        .os-header-icon svg {
            width: 24px;
            height: 24px;
        }
        .os-header-text h2 {
            margin: 0;
            font-size: 20px;
            font-weight: 700;
            color: #f8fafc;
        }
        .os-header-text p {
            margin: 4px 0 0;
            font-size: 13px;
            color: #94a3b8;
        }
        .os-id-badge {
            background: rgba(59,130,246,0.12);
            color: #60a5fa;
            padding: 10px 16px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 700;
            text-align: right;
        }
        .os-id-badge span {
            display: block;
            font-size: 11px;
            color: #64748b;
            font-weight: 600;
            margin-bottom: 2px;
        }

        .review-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 14px;
        }
        .review-row {
            display: flex;
            align-items: center;
            gap: 12px;
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.05);
            border-radius: 14px;
            padding: 14px;
        }
        .review-icon {
            width: 38px;
            height: 38px;
            background: rgba(96,165,250,0.12);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #60a5fa;
            flex-shrink: 0;
        }
        .review-icon svg {
            width: 18px;
            height: 18px;
        }
        .review-content {
            flex: 1;
            min-width: 0;
        }
        .review-label {
            display: block;
            color: #64748b;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            margin-bottom: 4px;
        }
        .review-value {
            display: block;
            color: #f1f5f9;
            font-size: 14px;
            font-weight: 600;
            word-break: break-word;
        }

        .checklist-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }
        .checklist-table th {
            text-align: left;
            padding: 12px;
            color: #94a3b8;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            border-bottom: 1px solid rgba(255,255,255,0.08);
        }
        .checklist-table td {
            padding: 14px 12px;
            border-bottom: 1px solid rgba(255,255,255,0.05);
            color: #e2e8f0;
        }
        .checklist-table td:last-child { text-align: right; }
        .check-done {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: #22c55e;
            font-weight: 700;
        }
        .check-done svg {
            width: 18px;
            height: 18px;
        }
        .status-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
        }
        .status-done { background: rgba(34,197,94,0.12); color: #4ade80; }
        .status-pending { background: rgba(239,68,68,0.12); color: #f87171; }

        .chip {
            display: inline-flex;
            align-items: center;
            padding: 8px 14px;
            border-radius: 20px;
            background: rgba(96,165,250,0.12);
            color: #93c5fd;
            font-size: 13px;
            font-weight: 600;
            margin: 0 6px 6px 0;
        }

        .partida-card {
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.06);
            border-radius: 16px;
            padding: 18px;
            margin-bottom: 16px;
        }
        .partida-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }
        .partida-title {
            font-weight: 700;
            font-size: 15px;
            color: #f1f5f9;
        }
        .btn-remove {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: #f87171;
            background: rgba(239,68,68,0.10);
            border: 1px solid rgba(239,68,68,0.25);
            padding: 7px 14px;
            border-radius: 10px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
        }
        .btn-remove svg { width: 14px; height: 14px; }
        .partida-grid {
            display: grid;
            grid-template-columns: 130px 2fr 110px 90px 130px;
            gap: 12px;
        }
        .partida-grid .field { display: flex; flex-direction: column; }
        .partida-grid label {
            font-size: 11px;
            color: #64748b;
            margin-bottom: 6px;
            text-transform: uppercase;
            font-weight: 700;
        }
        .partida-grid input,
        .partida-grid select {
            padding: 10px 12px;
            border: 1px solid rgba(255,255,255,0.10);
            border-radius: 10px;
            font-size: 13px;
            background: #0f172a;
            color: #f8fafc;
            outline: none;
        }
        .partida-grid input:focus,
        .partida-grid select:focus { border-color: #3b82f6; }
        .partida-import {
            text-align: right;
            font-weight: 700;
            margin-top: 12px;
            color: #60a5fa;
            font-size: 15px;
        }

        .btn-add, .btn-catalog {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: transparent;
            border: 1px dashed rgba(148,163,184,0.40);
            padding: 10px 16px;
            border-radius: 12px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 700;
            color: #cbd5e1;
            margin-right: 8px;
        }
        .btn-catalog {
            border-style: solid;
            border-color: rgba(255,255,255,0.12);
        }

        .two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .field-block { display: flex; flex-direction: column; }
        .field-block label {
            font-size: 11px;
            color: #64748b;
            margin-bottom: 6px;
            text-transform: uppercase;
            font-weight: 700;
        }
        .form-input {
            width: 100%;
            padding: 12px;
            border: 1px solid rgba(255,255,255,0.10);
            border-radius: 10px;
            font-size: 14px;
            background: #0f172a;
            color: #f8fafc;
            outline: none;
        }
        .form-input:focus { border-color: #3b82f6; }
        textarea.form-input { min-height: 100px; resize: vertical; }

        .iva-row {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            color: #e2e8f0;
            margin: 16px 0;
            cursor: pointer;
        }
        .iva-row input {
            width: 18px;
            height: 18px;
            accent-color: #3b82f6;
        }

        .totals-wrap {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 14px;
            border-top: 1px solid rgba(255,255,255,0.08);
            padding-top: 20px;
            margin-top: 20px;
        }
        .total-box {
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.06);
            border-radius: 14px;
            padding: 16px;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .total-box.pagar {
            background: rgba(34,197,94,0.12);
            border-color: rgba(34,197,94,0.25);
        }
        .total-box label {
            font-size: 12px;
            color: #64748b;
            font-weight: 700;
            text-transform: uppercase;
        }
        .total-box.pagar label { color: #4ade80; }
        .total-box value {
            font-size: 18px;
            font-weight: 800;
            color: #f1f5f9;
        }
        .total-box.pagar value { color: #4ade80; }

        .actions-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 14px;
            margin-top: 24px;
        }
        .actions-right {
            display: flex;
            gap: 14px;
            flex: 1;
            justify-content: flex-end;
        }
        .btn-primary, .btn-outline, .btn-secondary {
            padding: 16px 24px;
            border-radius: 14px;
            font-weight: 700;
            font-size: 15px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .btn-primary {
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            color: #fff;
            border: none;
        }
        .btn-primary:disabled {
            background: #334155;
            color: #94a3b8;
            cursor: not-allowed;
        }
        .btn-outline {
            background: transparent;
            color: #93c5fd;
            border: 1px solid rgba(59,130,246,0.40);
        }
        .btn-outline:disabled {
            color: #64748b;
            border-color: rgba(255,255,255,0.10);
            cursor: not-allowed;
        }
        .btn-secondary {
            background: #1e293b;
            color: #e2e8f0;
            border: 1px solid rgba(255,255,255,0.10);
        }

        .empty-note { color: #64748b; font-size: 14px; }

        @media (max-width: 760px) {
            .review-grid, .partida-grid, .two-col, .totals-wrap { grid-template-columns: 1fr; }
            .os-header { flex-direction: column; }
            .actions-footer { flex-direction: column; align-items: stretch; }
            .actions-right { flex-direction: column; align-items: stretch; }
        }
    </style>

    @if (session('success'))
        <div class="os-card" style="color: #22c55e; margin-bottom: 20px;">{{ session('success') }}</div>
    @endif

    @if (session('error'))
        <div class="os-card" style="color: #f87171; margin-bottom: 20px;">{{ session('error') }}</div>
    @endif

    <form id="osForm" action="{{ route('gestion.servicios.os.store', $service) }}" method="POST">
        @csrf

        <div class="os-page">

            {{-- Revisión Final --}}
            <div class="os-card">
                <div class="os-header">
                    <div class="os-header-title">
                        <div class="os-header-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                        </div>
                        <div class="os-header-text">
                            <h2>Revisión Final</h2>
                            <p>Verifica los detalles antes de remitir la orden de servicio</p>
                        </div>
                    </div>
                    <div class="os-id-badge">
                        <span>ID REVISIÓN</span>
                        {{ strtoupper($customerName) }}
                    </div>
                </div>
                <div class="review-grid">
                    <div class="review-row">
                        <div class="review-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        </div>
                        <div class="review-content">
                            <span class="review-label">Cliente</span>
                            <span class="review-value">{{ strtoupper($customerName) }}</span>
                        </div>
                    </div>
                    <div class="review-row">
                        <div class="review-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
                        </div>
                        <div class="review-content">
                            <span class="review-label">Equipo</span>
                            <span class="review-value">{{ $equipment->type_text ?? 'N/A' }}</span>
                        </div>
                    </div>
                    <div class="review-row">
                        <div class="review-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                        </div>
                        <div class="review-content">
                            <span class="review-label">Fecha</span>
                            <span class="review-value">{{ $startDate }} / {{ $endDate }}</span>
                        </div>
                    </div>
                    <div class="review-row">
                        <div class="review-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
                        </div>
                        <div class="review-content">
                            <span class="review-label">Identificación</span>
                            <span class="review-value">{{ $equipment->brand_text ?? '' }} | {{ $equipment->model_text ?? '' }} | SN: {{ $equipment->serial_number ?? 'N/A' }}</span>
                        </div>
                    </div>
                    <div class="review-row">
                        <div class="review-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        </div>
                        <div class="review-content">
                            <span class="review-label">Próx. Revisión</span>
                            <span class="review-value">{{ $proxMonths }} meses</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Cotización original --}}
            @if ($maintenance->refacciones || $maintenance->subtotal)
                <div class="os-card">
                    <h3 class="os-section-title">
                        <svg class="os-section-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                        Cotización original
                    </h3>
                    <table class="checklist-table">
                        <thead>
                            <tr><th>Concepto</th><th style="text-align:right;">Cant.</th><th style="text-align:right;">P. Unit.</th><th style="text-align:right;">Total</th></tr>
                        </thead>
                        <tbody>
                            @forelse ($maintenance->refacciones ?? [] as $r)
                                <tr>
                                    <td>{{ $r['concepto'] ?? $r['nombre'] ?? 'N/A' }}</td>
                                    <td style="text-align:right;">{{ $r['cantidad'] ?? 1 }}</td>
                                    <td style="text-align:right;">${{ number_format($r['precio'] ?? 0, 2) }}</td>
                                    <td style="text-align:right;">${{ number_format($r['total'] ?? (($r['cantidad'] ?? 1) * ($r['precio'] ?? 0)), 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="empty-note">Sin refacciones en cotización</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                    @php
                        $quoteIva = ($maintenance->total ?? 0) - ($maintenance->subtotal ?? 0) - ($maintenance->envio ?? 0) + ($maintenance->descuento ?? 0);
                    @endphp
                    <div style="margin-top: 16px; border-top: 1px solid rgba(255,255,255,0.08); padding-top: 16px;">
                        <div style="display:flex; justify-content:space-between; padding:6px 0; font-size:14px; color:#cbd5e1;"><span>Subtotal</span><span>${{ number_format($maintenance->subtotal ?? 0, 2) }}</span></div>
                        <div style="display:flex; justify-content:space-between; padding:6px 0; font-size:14px; color:#cbd5e1;"><span>Envío</span><span>${{ number_format($maintenance->envio ?? 0, 2) }}</span></div>
                        <div style="display:flex; justify-content:space-between; padding:6px 0; font-size:14px; color:#cbd5e1;"><span>Descuento</span><span>-${{ number_format($maintenance->descuento ?? 0, 2) }}</span></div>
                        <div style="display:flex; justify-content:space-between; padding:6px 0; font-size:14px; color:#cbd5e1;"><span>IVA</span><span>${{ number_format($quoteIva, 2) }}</span></div>
                        <div style="display:flex; justify-content:space-between; padding:8px 0; font-size:16px; font-weight:800; color:#60a5fa;"><span>Total cotizado</span><span>${{ number_format($maintenance->total ?? 0, 2) }}</span></div>
                    </div>
                </div>
            @endif

            {{-- Checklist --}}
            <div class="os-card">
                <h3 class="os-section-title">
                    <svg class="os-section-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                    Checklist Procesado
                </h3>
                <table class="checklist-table">
                    <thead>
                        <tr><th>Item</th><th>Sección</th><th style="text-align:right;">Estatus</th></tr>
                    </thead>
                    <tbody>
                        @forelse ($checklist as $item)
                            @php $done = is_array($item) ? ($item['done'] ?? false) : false; $text = is_array($item) ? ($item['text'] ?? '') : $item; @endphp
                            <tr>
                                <td>
                                    @if($done)
                                        <span class="check-done">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                            {{ $text }}
                                        </span>
                                    @else
                                        {{ $text }}
                                    @endif
                                </td>
                                <td>General</td>
                                <td><span class="status-pill {{ $done ? 'status-done' : 'status-pending' }}">{{ $done ? 'Realizado' : 'Pendiente' }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="empty-note">Sin checklist registrado</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Acciones realizadas --}}
            <div class="os-card">
                <h3 class="os-section-title">
                    <svg class="os-section-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="14 2 18 6 7 17 3 17 3 13 14 2"/><line x1="3" y1="22" x2="21" y2="22"/></svg>
                    Acciones Realizadas
                </h3>
                <div>
                    @php
                        $acciones = [];
                        foreach ($checklist as $c) {
                            if (is_array($c) && ($c['done'] ?? false) && ($c['text'] ?? '')) {
                                $acciones[] = $c['text'];
                            }
                        }
                        if (empty($acciones) && $maintenance->descripcion) {
                            $acciones = array_filter(array_map('trim', preg_split('/[,\.\s]+/', $maintenance->descripcion)));
                        }
                    @endphp
                    @forelse ($acciones as $accion)
                        <span class="chip">{{ $accion }}</span>
                    @empty
                        <span class="empty-note">Sin acciones registradas</span>
                    @endforelse
                </div>
            </div>

            {{-- Partidas para remisión --}}
            <div class="os-card">
                <div class="partida-header">
                    <div>
                        <h3 class="os-section-title" style="margin-bottom:4px;">
                            <svg class="os-section-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                            Partidas para remisión
                        </h3>
                        <p style="color:#64748b; font-size:13px; margin:0;">Configuración final de cobro.</p>
                    </div>
                </div>

                <div id="partidasContainer">
                    @foreach ($partidas as $i => $partida)
                        <div class="partida-card" data-index="{{ $i }}">
                            <div class="partida-header">
                                <span class="partida-title">Partida {{ $i + 1 }}</span>
                                <button type="button" class="btn-remove" onclick="removePartida(this)">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>
                                    Eliminar
                                </button>
                            </div>
                            <div class="partida-grid">
                                <div class="field">
                                    <label>Item</label>
                                    <select name="partidas_remision[{{ $i }}][item]" onchange="recalc()">
                                        <option value="Refacción" {{ ($partida['item'] ?? '') == 'Refacción' ? 'selected' : '' }}>Refacción</option>
                                        <option value="Servicio" {{ ($partida['item'] ?? '') == 'Servicio' ? 'selected' : '' }}>Servicio</option>
                                        <option value="Mano de obra" {{ ($partida['item'] ?? '') == 'Mano de obra' ? 'selected' : '' }}>Mano de obra</option>
                                    </select>
                                </div>
                                <div class="field">
                                    <label>Descripción</label>
                                    <input type="text" name="partidas_remision[{{ $i }}][descripcion]" value="{{ $partida['descripcion'] ?? '' }}">
                                </div>
                                <div class="field">
                                    <label>Unidad</label>
                                    <input type="text" name="partidas_remision[{{ $i }}][unidad]" value="{{ $partida['unidad'] ?? 'SERVICIO' }}">
                                </div>
                                <div class="field">
                                    <label>Cantidad</label>
                                    <input type="number" name="partidas_remision[{{ $i }}][cantidad]" value="{{ $partida['cantidad'] ?? 1 }}" min="0" step="any" onchange="recalc()">
                                </div>
                                <div class="field">
                                    <label>Precio unitario</label>
                                    <input type="number" name="partidas_remision[{{ $i }}][precio_unitario]" value="{{ $partida['precio_unitario'] ?? 0 }}" min="0" step="any" onchange="recalc()">
                                </div>
                            </div>
                            <div class="partida-import">Importe $<span class="importe-val">0.00</span></div>
                        </div>
                    @endforeach
                </div>

                <div style="margin-bottom: 22px;">
                    <button type="button" class="btn-add" onclick="addPartida()">+ Agregar partida</button>
                    <button type="button" class="btn-catalog">Seleccionar de catálogo</button>
                </div>

                <div class="two-col" style="margin-bottom: 6px;">
                    <div class="field-block">
                        <label>Envío</label>
                        <input type="number" name="envio" id="envio" value="{{ old('envio', $maintenance->envio ?? 0) }}" min="0" step="any" class="form-input" onchange="recalc()">
                    </div>
                    <div class="field-block">
                        <label>Anticipo</label>
                        <input type="number" name="anticipo" id="anticipo" value="{{ old('anticipo', $maintenance->anticipo ?? 0) }}" min="0" step="any" class="form-input" onchange="recalc()">
                    </div>
                </div>

                <label class="iva-row">
                    <input type="checkbox" name="requiere_iva" id="requiere_iva" value="1" {{ old('requiere_iva', $maintenance->requiere_iva ?? false) ? 'checked' : '' }} onchange="recalc()">
                    Requiere IVA (16%)
                </label>

                <div class="totals-wrap">
                    <div class="total-box">
                        <label>Subtotal</label>
                        <value id="subtotal">$0.00</value>
                    </div>
                    <div class="total-box">
                        <label>IVA (16%)</label>
                        <value id="iva">$0.00</value>
                    </div>
                    <div class="total-box">
                        <label>Anticipo</label>
                        <value id="anticipoDisplay">-$0.00</value>
                    </div>
                    <div class="total-box pagar">
                        <label>Total a pagar</label>
                        <value id="pagar">$0.00</value>
                    </div>
                </div>
            </div>

            {{-- Descripción general --}}
            <div class="os-card">
                <h3 class="os-section-title">
                    <svg class="os-section-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                    Descripción general remisión
                </h3>
                <textarea name="descripcion_general" id="descripcion_general" class="form-input">{{ old('descripcion_general', $maintenance->descripcion_general ?? $defaultDescription) }}</textarea>
            </div>

            {{-- Footer actions --}}
            <div class="actions-footer">
                <button type="submit" name="action" value="save" class="btn-secondary">Cancelar</button>
                <div class="actions-right">
                    @php $isOsDraft = in_array($service->currentStep?->slug, ['generacion-os', 'interno-generacion-os']); @endphp
                    <button type="submit" name="action" value="generate-remision-pdf" class="btn-outline" {{ $isOsDraft ? 'disabled' : '' }} title="{{ $isOsDraft ? 'Disponible después de la validación' : 'Descargar PDF de la remisión' }}">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                        {{ $isOsDraft ? 'PDF Remisión bloqueado' : 'PDF Remisión' }}
                    </button>
                    <button type="submit" name="action" value="generate-pdf" class="btn-primary" {{ $isOsDraft ? 'disabled' : '' }} title="{{ $isOsDraft ? 'Disponible después de la validación' : 'Descargar PDF de la OS' }}">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                        {{ $isOsDraft ? 'PDF bloqueado hasta validación' : 'PDF OS' }}
                    </button>
                </div>
            </div>
        </div>
    </form>

    <script>
        function formatMoney(value) {
            return '$' + (parseFloat(value) || 0).toFixed(2);
        }

        function recalc() {
            let subtotal = 0;
            const cards = document.querySelectorAll('.partida-card');
            cards.forEach(card => {
                const cantidad = parseFloat(card.querySelector('[name*="[cantidad]"]').value) || 0;
                const precio = parseFloat(card.querySelector('[name*="[precio_unitario]"]').value) || 0;
                const importe = cantidad * precio;
                card.querySelector('.importe-val').textContent = importe.toFixed(2);
                subtotal += importe;
            });

            const envio = parseFloat(document.getElementById('envio').value) || 0;
            subtotal += envio;

            const requiereIva = document.getElementById('requiere_iva').checked;
            const iva = requiereIva ? subtotal * 0.16 : 0;
            const total = subtotal + iva;
            const anticipo = parseFloat(document.getElementById('anticipo').value) || 0;
            const pagar = total - anticipo;

            document.getElementById('subtotal').textContent = formatMoney(subtotal);
            document.getElementById('iva').textContent = formatMoney(iva);
            document.getElementById('total')?.textContent = formatMoney(total);
            document.getElementById('anticipoDisplay').textContent = '-' + formatMoney(anticipo);
            document.getElementById('pagar').textContent = formatMoney(pagar);
        }

        function addPartida() {
            const container = document.getElementById('partidasContainer');
            const index = container.children.length;
            const div = document.createElement('div');
            div.className = 'partida-card';
            div.dataset.index = index;
            div.innerHTML = `
                <div class="partida-header">
                    <span class="partida-title">Partida ${index + 1}</span>
                    <button type="button" class="btn-remove" onclick="removePartida(this)">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>
                        Eliminar
                    </button>
                </div>
                <div class="partida-grid">
                    <div class="field"><label>Item</label><select name="partidas_remision[${index}][item]" onchange="recalc()"><option value="Refacción" selected>Refacción</option><option value="Servicio">Servicio</option><option value="Mano de obra">Mano de obra</option></select></div>
                    <div class="field"><label>Descripción</label><input type="text" name="partidas_remision[${index}][descripcion]"></div>
                    <div class="field"><label>Unidad</label><input type="text" name="partidas_remision[${index}][unidad]" value="SERVICIO"></div>
                    <div class="field"><label>Cantidad</label><input type="number" name="partidas_remision[${index}][cantidad]" value="1" min="0" step="any" onchange="recalc()"></div>
                    <div class="field"><label>Precio unitario</label><input type="number" name="partidas_remision[${index}][precio_unitario]" value="0" min="0" step="any" onchange="recalc()"></div>
                </div>
                <div class="partida-import">Importe $<span class="importe-val">0.00</span></div>
            `;
            container.appendChild(div);
            recalc();
        }

        function removePartida(btn) {
            btn.closest('.partida-card').remove();
            reindexPartidas();
            recalc();
        }

        function reindexPartidas() {
            const container = document.getElementById('partidasContainer');
            Array.from(container.children).forEach((card, i) => {
                card.dataset.index = i;
                card.querySelector('.partida-title').textContent = 'Partida ' + (i + 1);
                card.querySelectorAll('input, select').forEach(input => {
                    const name = input.name.replace(/partidas_remision\[\d+\]/, `partidas_remision[${i}]`);
                    input.name = name;
                });
            });
        }

        function regenerateDefaults() {
            document.getElementById('descripcion_general').value = `{!! addslashes($defaultDescription) !!}`;
            recalc();
        }

        document.addEventListener('DOMContentLoaded', recalc);
    </script>
@endsection
                      