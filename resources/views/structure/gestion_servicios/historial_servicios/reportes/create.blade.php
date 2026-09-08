@extends('layouts.app')

@section('title', 'Reporte de equipo · ' . config('app.name'))
@section('card-class', 'card--wide')

@section('content')
    <style>
        .report-head { text-align:center; margin-bottom:22px; }
        .report-head h1 { font-size:22px; margin:0 0 6px; }
        .report-head p { color:var(--muted); margin:0; font-size:14px; }
        .report-info { background:#f9fafb; border-radius:10px; padding:14px; margin-bottom:18px; font-size:13px; }
        .report-info div { display:flex; justify-content:space-between; padding:6px 0; border-bottom:1px solid var(--border); }
        .report-info div:last-child { border-bottom:none; }
        .report-info span { color:var(--muted); }
        .report-info strong { text-align:right; max-width:55%; word-break:break-word; }
        label { display:block; font-size:13px; font-weight:700; margin:14px 0 6px; color:var(--text); }
        textarea { width:100%; padding:11px 12px; border:1px solid var(--border); border-radius:9px; font-size:15px; background:var(--card); color:var(--text); resize:vertical; min-height:120px; }
        textarea:focus { border-color:var(--indigo); box-shadow:0 0 0 3px rgba(0, 122, 255, 0.15); outline:none; }
    </style>

    <div class="report-head">
        <h1>Reporte de equipo</h1>
        <p>Describe el problema o novedad del equipo</p>
    </div>

    @if (session('status'))
        <div class="alert alert--ok">{{ session('status') }}</div>
    @endif

    <div class="report-info">
        <div><span>Cliente</span><strong>{{ $data['customer_name'] ?? '—' }}</strong></div>
        <div><span>Teléfono</span><strong>{{ $data['customer_phone'] ?? '—' }}</strong></div>
        <div><span>Correo</span><strong>{{ $data['customer_email'] ?? '—' }}</strong></div>
        <div><span>Tipo de equipo</span><strong>{{ $data['equipment_type'] ?? '—' }}</strong></div>
        <div><span>Marca / Modelo</span><strong>{{ ($data['equipment_brand'] ?? '') . ' ' . ($data['equipment_model'] ?? '') }}</strong></div>
        <div><span>No. de serie</span><strong>{{ $data['serial_number'] ?? '—' }}</strong></div>
        <div><span>Técnico</span><strong>{{ $data['technician_name'] ?? '—' }}</strong></div>
    </div>

    <form method="POST" action="{{ route('reporte.equipo.store') }}">
        @csrf

        @foreach ([
            'customer_name', 'customer_phone', 'customer_email',
            'equipment_type', 'equipment_subtype', 'equipment_brand', 'equipment_model',
            'serial_number', 'description', 'observations', 'technician_name',
        ] as $field)
            <input type="hidden" name="{{ $field }}" value="{{ $data[$field] ?? '' }}">
        @endforeach

        <label for="report">Reporte</label>
        <textarea id="report" name="report" placeholder="Describe el problema, falla o novedad del equipo" required>{{ old('report') }}</textarea>

        <button type="submit" class="btn">Enviar reporte</button>
    </form>
@endsection
