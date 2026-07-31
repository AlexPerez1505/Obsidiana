@extends('structure.commercial_management.layout')

@section('title', 'Ver Cliente')

@section('commercial_content')
    @php
        $names = explode(' ', trim($customer->nombre . ' ' . $customer->apellido));
        $initials = '';
        if (count($names) >= 2) {
            $initials = strtoupper(substr($names[0], 0, 1) . substr($names[1], 0, 1));
        } elseif (count($names) === 1) {
            $initials = strtoupper(substr($names[0], 0, 2));
        } else {
            $initials = 'CL';
        }
        $avatarColor = ['#7c3aed','#3b82f6','#10b981','#f59e0b','#ec4899'][crc32($initials) % 5];
    @endphp

    <div style="display:grid; grid-template-columns:1fr 320px; gap:18px; align-items:start;">
        <div>
            {{-- Header --}}
            <div class="dashboard-card" style="margin-bottom:18px; display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap;">
                <div style="display:flex; align-items:center; gap:12px;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="32" height="32" style="color:#fff;">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                        <circle cx="12" cy="12" r="3"/>
                    </svg>
                    <div>
                        <h2 class="header-title" style="margin:0;">{{ $customer->nombre }} {{ $customer->apellido }}</h2>
                        <p class="header-subtitle" style="margin:4px 0 0;">Detalle del cliente</p>
                    </div>
                </div>
                <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                    <a href="{{ route('commercial.clientes.index') }}" class="btn-glass" style="text-decoration:none; display:inline-flex; align-items:center; gap:6px;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5"/><path d="M12 19l-7-7 7-7"/></svg>
                        Regresar
                    </a>
                    <a href="{{ route('commercial.clientes.edit', $customer) }}" class="btn-gradient" style="text-decoration:none; display:inline-flex; align-items:center; gap:6px;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                        Editar
                    </a>
                </div>
            </div>

            {{-- Datos personales --}}
            <div class="dashboard-card" style="margin-bottom:18px;">
                <h3 class="header-title" style="font-size:18px; margin:0 0 16px;">Datos Personales</h3>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                    <div>
                        <div style="color:rgba(255,255,255,0.55); font-size:13px;">Nombre</div>
                        <div style="color:#fff; font-size:15px; font-weight:600;">{{ $customer->nombre }}</div>
                    </div>
                    <div>
                        <div style="color:rgba(255,255,255,0.55); font-size:13px;">Apellido</div>
                        <div style="color:#fff; font-size:15px; font-weight:600;">{{ $customer->apellido }}</div>
                    </div>
                    <div>
                        <div style="color:rgba(255,255,255,0.55); font-size:13px;">Teléfono</div>
                        <div style="color:#fff; font-size:15px; font-weight:600;">{{ $customer->telefono }}</div>
                    </div>
                    <div>
                        <div style="color:rgba(255,255,255,0.55); font-size:13px;">Correo Electrónico</div>
                        <div style="color:#fff; font-size:15px; font-weight:600;">{{ $customer->correo ?? 'No registrado' }}</div>
                    </div>
                </div>
            </div>

            {{-- Información comercial --}}
            <div class="dashboard-card" style="margin-bottom:18px;">
                <h3 class="header-title" style="font-size:18px; margin:0 0 16px;">Información Comercial</h3>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                    <div>
                        <div style="color:rgba(255,255,255,0.55); font-size:13px;">Asesor</div>
                        <div style="color:#fff; font-size:15px; font-weight:600;">{{ $customer->seller?->name ?? 'Sin asesor' }}</div>
                    </div>
                    <div>
                        <div style="color:rgba(255,255,255,0.55); font-size:13px;">Categoría</div>
                        <div style="color:#fff; font-size:15px; font-weight:600;">{{ $customer->category?->name ?? 'Sin categoría' }}</div>
                    </div>
                    <div>
                        <div style="color:rgba(255,255,255,0.55); font-size:13px;">Congreso Conocido</div>
                        <div style="color:#fff; font-size:15px; font-weight:600;">{{ $customer->congress?->name ?? 'Sin congreso' }}</div>
                    </div>
                    <div>
                        <div style="color:rgba(255,255,255,0.55); font-size:13px;">RFC</div>
                        <div style="color:#fff; font-size:15px; font-weight:600;">{{ $customer->rfc }}</div>
                    </div>
                    <div>
                        <div style="color:rgba(255,255,255,0.55); font-size:13px;">¿Recibe Promoción?</div>
                        <div style="color:#fff; font-size:15px; font-weight:600;">
                            <span class="promotion-badge {{ $customer->receives_promotion ? '' : 'inactive' }}">
                                <span style="width:6px;height:6px;border-radius:50%;background:currentColor;display:inline-block;"></span>
                                {{ $customer->receives_promotion ? 'Sí' : 'No' }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Información adicional --}}
            <div class="dashboard-card">
                <h3 class="header-title" style="font-size:18px; margin:0 0 16px;">Información Adicional</h3>
                <div style="color:rgba(255,255,255,0.55); font-size:13px;">Dirección y Comentarios</div>
                <div style="color:#fff; font-size:15px; font-weight:500; line-height:1.7; white-space:pre-wrap;">{{ $customer->comentarios ?: 'Sin comentarios' }}</div>
            </div>
        </div>

        {{-- Sidebar --}}
        <div style="display:flex; flex-direction:column; gap:18px;">
            <div class="dashboard-card" style="text-align:center;">
                <div style="width:80px; height:80px; border-radius:50%; margin:0 auto 14px; display:flex; align-items:center; justify-content:center; font-size:28px; font-weight:700; color:#fff; background:{{ $avatarColor }}; box-shadow: 0 0 18px {{ $avatarColor }};">
                    {{ $initials }}
                </div>
                <h3 class="header-title" style="font-size:18px; margin:0;">{{ $customer->nombre }} {{ $customer->apellido }}</h3>
                <p class="header-subtitle" style="margin:6px 0 0;">Cliente registrado</p>
            </div>

            <div class="dashboard-card">
                <h3 class="header-title" style="font-size:16px; margin:0 0 12px;">Resumen</h3>
                <ul style="margin:0; padding-left:18px; font-size:14px; color:rgba(255,255,255,0.7); line-height:1.9;">
                    <li>RFC: {{ $customer->rfc }}</li>
                    <li>Teléfono: {{ $customer->telefono }}</li>
                    <li>Promoción: {{ $customer->receives_promotion ? 'Sí' : 'No' }}</li>
                </ul>
            </div>
        </div>
    </div>
@endsection
