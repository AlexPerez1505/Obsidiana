@extends('layouts.dashboard')
@section('title', $titulo)
@section('page-title', $titulo)
@section('page-sub', 'Próximamente')

@section('content')
    <x-ui.card>
        <div style="text-align:center; padding:60px 20px; color:var(--muted);">
            <x-gravityui-clock width="48" height="48" style="margin:0 auto 14px; opacity:.4; display:block;" />
            <p style="font-size:15px; font-weight:600; margin:0;">Módulo de {{ $titulo }} en construcción</p>
            <p style="font-size:13.5px; margin:8px 0 0;">Aún no se ha definido el esquema de datos para este módulo.</p>
        </div>
    </x-ui.card>
@endsection
