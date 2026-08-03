@extends('structure.Configuracion.layout')

@section('title', 'Refacciones')

@section('configuracion_content')
    <div class="catalog-card" style="margin-bottom:22px;">
        <div style="display:flex; align-items:center; justify-content:space-between; gap:18px; flex-wrap:wrap;">
            <div>
                <h2 class="page-title" style="margin:0; font-size:24px; font-weight:700;">Refacciones</h2>
                <p class="page-subtitle" style="margin:4px 0 0; font-size:14px;">Catálogo de refacciones y repuestos</p>
            </div>
            <a href="{{ route('configuracion.refaciones.create') }}" style="background:linear-gradient(135deg, #00A8FF, #7C3AED); color:#fff; border:1px solid rgba(255,255,255,0.15); padding:10px 18px; border-radius:12px; font-size:14px; font-weight:600; text-decoration:none; display:inline-flex; align-items:center; gap:8px; box-shadow:0 0 12px rgba(59,130,246,0.35), 0 0 30px rgba(124,58,237,0.2); transition:all 0.2s ease;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Agregar refacción
            </a>
        </div>
    </div>

    <div class="catalog-card">
        <div class="catalog-empty">Todavía no hay refacciones registradas.</div>
    </div>
@endsection
