@extends('structure.gestion_servicios.layout')

@section('title', 'Escanear QR')

@section('service_content')
<div class="card">
    <div class="wizard-header" style="margin-bottom:18px;">
        <div>
            <h1 class="section-title" style="font-size:22px; margin:0;">{{ $tracking->serviceStep->name }}</h1>
            <p class="muted" style="margin:6px 0 0;">Orden {{ $service->service_number }} · Producto {{ $service->serviceEquipment->product_code ?? 'N/A' }}</p>
        </div>
    </div>

    <form method="POST" action="{{ route('qr.update', $tracking->qr_token) }}" enctype="multipart/form-data">
        @csrf

        <div class="form-group" style="margin-bottom:14px;">
            <label>Observaciones</label>
            <textarea name="notes" rows="3" placeholder="Notas del paso"></textarea>
        </div>

        <div class="form-group" style="margin-bottom:14px;">
            <label>Evidencia 1 (imagen)</label>
            <input type="file" name="evidencia_1" accept="image/*">
        </div>

        <div class="form-group" style="margin-bottom:14px;">
            <label>Evidencia 2 (imagen)</label>
            <input type="file" name="evidencia_2" accept="image/*">
        </div>

        <div class="form-group" style="margin-bottom:14px;">
            <label>Evidencia 3 (imagen)</label>
            <input type="file" name="evidencia_3" accept="image/*">
        </div>

        <div class="form-group" style="margin-bottom:14px;">
            <label>Video</label>
            <input type="file" name="evidencia_video" accept="video/*">
        </div>

        <div style="display:flex; gap:10px; margin-top:18px;">
            <a href="{{ route('gestion.servicios.historial') }}" class="btn btn--ghost">Cancelar</a>
            <button type="submit" class="btn">Confirmar paso</button>
        </div>
    </form>
</div>
@endsection
