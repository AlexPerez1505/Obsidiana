@extends('layouts.app')

@section('title', 'Servicio completado · ' . config('app.name'))

@section('content')
    <div style="text-align:center; padding:12px 0;">
        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="var(--green)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom:16px;">
            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
            <polyline points="22 4 12 14.01 9 11.01"/>
        </svg>
        <h1 style="margin:0 0 8px; font-size:22px;">Servicio completado</h1>
        <p style="color:var(--muted); margin:0 0 22px; font-size:14px;">
            La información fue registrada correctamente. Gracias.
        </p>
        <a href="{{ url('/') }}" class="btn">Ir al inicio</a>
    </div>
@endsection
