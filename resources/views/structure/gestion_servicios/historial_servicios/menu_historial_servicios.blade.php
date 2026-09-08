@extends('layouts.dashboard')

@section('title', 'Historial de Servicios')

@section('content')
    <div class="card" style="margin-bottom:24px; background:linear-gradient(135deg, var(--surface) 0%, var(--surface-2) 100%);">
        <div style="display:flex; align-items:center; justify-content:space-between; gap:18px; flex-wrap:wrap;">
            <div>
                <h1 class="section-title" style="margin:0; font-size:34px; line-height:1.15; letter-spacing:-0.02em; font-weight:800;">Historial de Servicios</h1>
                <p class="muted" style="margin:14px 0 0; font-size:15.5px; line-height:1.6; max-width:680px;">Registro y seguimiento de órdenes de servicio.</p>
            </div>
            <a href="{{ route('gestion.servicios.historial.nueva_orden') }}" style="background:linear-gradient(135deg, #00A8FF, #7C3AED); color:#fff; border:1px solid rgba(255,255,255,0.15); padding:10px 18px; border-radius:12px; font-size:14px; font-weight:600; text-decoration:none; display:inline-flex; align-items:center; gap:8px; box-shadow:0 0 12px rgba(59,130,246,0.35), 0 0 30px rgba(124,58,237,0.2); transition:all 0.2s ease;">
                <x-gravityui-plus width="16" height="16" />
                Nueva Orden
            </a>
        </div>
    </div>
@endsection
