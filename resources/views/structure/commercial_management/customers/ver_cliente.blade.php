@extends('layouts.dashboard')
@section('title', 'Ver Cliente')

@section('content')
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
            <div class="card" style="margin-bottom:18px; display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap;">
                <div style="display:flex; align-items:center; gap:12px;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="32" height="32" style="color:var(--text);">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                        <circle cx="12" cy="12" r="3"/>
                    </svg>
                    <div>
                        <h2 class="page-title" style="margin:0;">{{ $customer->nombre }} {{ $customer->apellido }}</h2>
                        <p class="muted" style="margin:4px 0 0;">Detalle del cliente</p>
                    </div>
                </div>
                <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                    <a href="{{ route('commercial.clientes.index') }}" class="btn btn--ghost" style="text-decoration:none; display:inline-flex; align-items:center; gap:6px;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5"/><path d="M12 19l-7-7 7-7"/></svg>
                        Regresar
                    </a>
                    <a href="{{ route('commercial.clientes.edit', $customer) }}" class="btn" style="text-decoration:none; display:inline-flex; align-items:center; gap:6px;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                        Editar
                    </a>
                </div>
            </div>

            {{-- Datos personales --}}
            <x-ui.card style="margin-bottom:18px;">
                <x-ui.section-title style="margin:0 0 16px;">Datos Personales</x-ui.section-title>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                    <div>
                        <div class="muted" style="font-size:13px;">Nombre</div>
                        <div style="font-size:15px; font-weight:600;">{{ $customer->nombre }}</div>
                    </div>
                    <div>
                        <div class="muted" style="font-size:13px;">Apellido</div>
                        <div style="font-size:15px; font-weight:600;">{{ $customer->apellido ?: '—' }}</div>
                    </div>
                    <div>
                        <div class="muted" style="font-size:13px;">Teléfono</div>
                        <div style="font-size:15px; font-weight:600;">{{ $customer->telefono ?: '—' }}</div>
                    </div>
                    <div>
                        <div class="muted" style="font-size:13px;">Correo</div>
                        <div style="font-size:15px; font-weight:600;">{{ $customer->gmail ?? 'No registrado' }}</div>
                    </div>
                </div>
            </x-ui.card>

            {{-- Información comercial --}}
            <x-ui.card style="margin-bottom:18px;">
                <x-ui.section-title style="margin:0 0 16px;">Información Comercial</x-ui.section-title>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                    <div>
                        <div class="muted" style="font-size:13px;">Asesor</div>
                        <div style="font-size:15px; font-weight:600;">{{ $customer->asesor?->name ?? 'Sin asesor' }}</div>
                    </div>
                    <div>
                        <div class="muted" style="font-size:13px;">Categoría</div>
                        <div style="font-size:15px; font-weight:600;">{{ $customer->category?->nombre ?? 'Sin categoría' }}</div>
                    </div>
                    <div>
                        <div class="muted" style="font-size:13px;">Congreso Conocido</div>
                        <div style="font-size:15px; font-weight:600;">{{ $customer->congress?->nombre ?? 'Sin congreso' }}</div>
                    </div>
                    <div>
                        <div class="muted" style="font-size:13px;">¿Recibe Promoción?</div>
                        <div style="font-size:15px; font-weight:600;">{{ $customer->recibe_promocion ? 'Sí' : 'No' }}</div>
                    </div>
                    <div>
                        <div class="muted" style="font-size:13px;">Estado</div>
                        <div style="font-size:15px; font-weight:600;">{{ $customer->activo ? 'Activo' : 'Inactivo' }}</div>
                    </div>
                </div>
            </x-ui.card>

            {{-- Cotizaciones --}}
            <x-ui.card style="margin-bottom:18px;">
                <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:14px;">
                    <x-ui.section-title style="margin:0;">Cotizaciones</x-ui.section-title>
                </div>
                @if($customer->cotizaciones->isEmpty())
                    <p class="muted" style="margin:0; font-size:14px;">Este cliente aún no tiene cotizaciones.</p>
                @else
                    <div style="display:flex; flex-direction:column; gap:8px;">
                        @foreach($customer->cotizaciones as $cot)
                            <div style="display:flex; justify-content:space-between; padding:10px 12px; border:1px solid var(--border); border-radius:9px; font-size:13.5px;">
                                <span>Cotización #{{ $cot->id }}</span>
                                <span class="muted">${{ number_format($cot->total, 2) }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-ui.card>

            {{-- Información adicional --}}
            <x-ui.card>
                <x-ui.section-title style="margin:0 0 16px;">Información Adicional</x-ui.section-title>
                <div class="muted" style="font-size:13px;">Dirección</div>
                <div style="font-size:15px; font-weight:500; margin-bottom:14px;">{{ $customer->direccion ?: 'Sin dirección' }}</div>
                <div class="muted" style="font-size:13px;">Comentarios</div>
                <div style="font-size:15px; font-weight:500; line-height:1.7; white-space:pre-wrap;">{{ $customer->comentarios ?: 'Sin comentarios' }}</div>
            </x-ui.card>
        </div>

        {{-- Sidebar --}}
        <div style="display:flex; flex-direction:column; gap:18px;">
            <x-ui.card style="text-align:center;">
                <div style="width:80px; height:80px; border-radius:50%; margin:0 auto 14px; display:flex; align-items:center; justify-content:center; font-size:28px; font-weight:700; color:#fff; background:{{ $avatarColor }};">
                    {{ $initials }}
                </div>
                <h3 class="page-title" style="font-size:18px; margin:0;">{{ $customer->nombre }} {{ $customer->apellido }}</h3>
                <p class="muted" style="margin:6px 0 0;">Cliente registrado</p>
            </x-ui.card>

            <x-ui.card>
                <x-ui.section-title style="margin:0 0 12px;">Resumen</x-ui.section-title>
                <ul style="margin:0; padding-left:18px; font-size:14px; color:var(--text); line-height:1.9;">
                    <li>Teléfono: {{ $customer->telefono ?: '—' }}</li>
                    <li>Promoción: {{ $customer->recibe_promocion ? 'Sí' : 'No' }}</li>
                    <li>Estado: {{ $customer->activo ? 'Activo' : 'Inactivo' }}</li>
                </ul>
            </x-ui.card>
        </div>
    </div>
@endsection
