@extends('layouts.app')

@section('title', 'Actualizar paso · ' . config('app.name'))
@section('card-class', 'card--wide')

@section('content')
    <style>
        .qr-head { text-align:center; margin-bottom:22px; }
        .qr-head h1 { font-size:22px; margin:0 0 6px; }
        .qr-head p { color:var(--muted); margin:0; font-size:14px; }
        .qr-step { display:inline-flex; align-items:center; gap:8px; padding:6px 12px; border-radius:999px; background:#e6f0ff; color:#0062cc; font-size:12px; font-weight:700; margin-bottom:16px; }
        .form-group { margin-bottom:16px; text-align:left; }
        .form-group label { display:block; font-size:13px; font-weight:700; margin-bottom:6px; color:var(--text); }
        .form-group input[type="file"],
        .form-group textarea { width:100%; padding:10px 12px; border:1px solid var(--border); border-radius:9px; font-size:14px; background:#fff; color:var(--text); }
        .form-group textarea { resize:vertical; min-height:80px; }
        .qr-info { background:var(--surface-2); border-radius:10px; padding:14px; margin-bottom:18px; font-size:13px; }
        .qr-info div { display:flex; justify-content:space-between; padding:6px 0; border-bottom:1px solid var(--border); }
        .qr-info div:last-child { border-bottom:none; }
        .qr-actions { display:flex; gap:10px; margin-top:22px; }
        .qr-actions .btn { flex:1; margin-top:0; }
    </style>

    <div class="qr-head">
        <div class="qr-step">Paso actual: {{ $tracking->serviceStep->name }}</div>
        <h1>Actualizar orden de servicio</h1>
        <p>Orden {{ $service->service_number }} · Producto {{ $service->serviceEquipment->product_code ?? 'N/A' }}</p>
    </div>

    <div class="qr-info">
        <div>
            <span>Cliente</span>
            <strong>{{ $service->customer?->nombre ?? '—' }} {{ $service->customer?->apellido ?? '' }}</strong>
        </div>
        <div>
            <span>Equipo</span>
            <strong>{{ $service->serviceEquipment?->type_text ?? '—' }} {{ $service->serviceEquipment?->brand_text ?? '' }} {{ $service->serviceEquipment?->model_text ?? '' }}</strong>
        </div>
        <div>
            <span>Tipo de servicio</span>
            <strong style="text-transform:capitalize;">{{ $service->service_type === 'externo' ? 'Mantenimiento externo' : 'Mantenimiento interno' }}</strong>
        </div>
    </div>

    <form method="POST" action="{{ route('qr.update', $tracking->qr_token) }}" enctype="multipart/form-data">
        @csrf

        <div class="form-group">
            <label for="notes">Observaciones</label>
            <textarea id="notes" name="notes" rows="3" placeholder="Notas del paso"></textarea>
        </div>

        <div class="form-group">
            <label for="evidencia_1">Evidencia 1 (imagen)</label>
            <input type="file" id="evidencia_1" name="evidencia_1" accept="image/*">
        </div>

        <div class="form-group">
            <label for="evidencia_2">Evidencia 2 (imagen)</label>
            <input type="file" id="evidencia_2" name="evidencia_2" accept="image/*">
        </div>

        <div class="form-group">
            <label for="evidencia_3">Evidencia 3 (imagen)</label>
            <input type="file" id="evidencia_3" name="evidencia_3" accept="image/*">
        </div>

        <div class="form-group">
            <label for="evidencia_video">Video</label>
            <input type="file" id="evidencia_video" name="evidencia_video" accept="video/*">
        </div>

        <div class="qr-actions">
            <button type="submit" class="btn">Confirmar paso</button>
        </div>
    </form>
@endsection
