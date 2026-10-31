@extends('layouts.app')

@section('title', 'Registro de mantenimiento · ' . config('app.name'))
@section('card-class', 'card--wide')

@section('content')
    @php
        $movimientos = [
            'diagnostico' => 'Diagnóstico general',
            'limpieza' => 'Limpieza interna y externa',
            'lubricacion' => 'Lubricación de componentes',
            'ajuste' => 'Ajuste y calibración',
            'cambio_piezas' => 'Cambio de refacciones / piezas',
            'reparacion' => 'Reparación de componentes',
            'pruebas' => 'Pruebas de funcionamiento',
        ];
        $registro = $tracking->registro_data ?? [];
        $seleccionados = collect(old('movimientos', $registro['movimientos'] ?? []));
        $tipo = old('tipo_mantenimiento', $registro['tipo_mantenimiento'] ?? '');
    @endphp

    <style>
        .qr-head { text-align:center; margin-bottom:22px; }
        .qr-head h1 { font-size:22px; margin:0 0 6px; }
        .qr-head p { color:var(--muted); margin:0; font-size:14px; }
        .qr-info { background:var(--surface-2); border-radius:10px; padding:14px; margin-bottom:18px; font-size:13px; }
        .qr-info div { display:flex; justify-content:space-between; padding:6px 0; border-bottom:1px solid var(--border); }
        .qr-info div:last-child { border-bottom:none; }
        .form-group { margin-bottom:18px; text-align:left; }
        .form-group > label { display:block; font-size:13px; font-weight:700; margin-bottom:8px; color:var(--text); }
        .form-group input[type="file"],
        .form-group textarea { width:100%; padding:10px 12px; border:1px solid var(--border); border-radius:9px; font-size:14px; background:#fff; color:var(--text); }
        .form-group textarea { resize:vertical; min-height:70px; }
        .tipo-opts { display:flex; gap:10px; }
        .tipo-opts label { flex:1; display:flex; align-items:center; justify-content:center; gap:8px; padding:12px; border:1px solid var(--border); border-radius:9px; font-size:14px; font-weight:600; cursor:pointer; background:#fff; }
        .tipo-opts input { accent-color:var(--primary); width:16px; height:16px; }
        .mov-list label { display:flex; align-items:center; gap:10px; padding:8px 4px; border-bottom:1px solid var(--border); font-size:14px; cursor:pointer; }
        .mov-list label:last-child { border-bottom:none; }
        .mov-list input { width:17px; height:17px; accent-color:var(--primary); }
        .qr-actions { display:flex; gap:10px; margin-top:22px; align-items:center; }
        .qr-actions .btn { flex:1; margin-top:0; }
        .qr-actions a { font-size:13px; color:var(--muted); }
        .sec { font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:.04em; color:var(--muted); margin:20px 0 10px; border-top:1px solid var(--border); padding-top:16px; }
        .sec:first-of-type { border-top:none; padding-top:0; margin-top:0; }
    </style>

    <div class="qr-head">
        <h1>Registro de mantenimiento</h1>
        <p>Orden {{ $service->service_number ?? ('OS-' . $service->id) }} · Paso: {{ $tracking->serviceStep?->name ?? '—' }}</p>
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
            <span>Serie</span>
            <strong>{{ $service->serviceEquipment?->serial_number ?? '—' }}</strong>
        </div>
    </div>

    <form method="POST" action="{{ route('qr.registro.guardar', $tracking->qr_token) }}" enctype="multipart/form-data">
        @csrf

        <div class="sec">Tipo de mantenimiento</div>
        <div class="form-group">
            <div class="tipo-opts">
                <label>
                    <input type="radio" name="tipo_mantenimiento" value="correctivo" {{ $tipo === 'correctivo' ? 'checked' : '' }} required>
                    Correctivo
                </label>
                <label>
                    <input type="radio" name="tipo_mantenimiento" value="preventivo" {{ $tipo === 'preventivo' ? 'checked' : '' }}>
                    Preventivo
                </label>
            </div>
            @error('tipo_mantenimiento') <div style="color:var(--danger); font-size:12px; margin-top:4px;">{{ $message }}</div> @enderror
        </div>

        <div class="sec">Movimientos realizados</div>
        <div class="form-group mov-list">
            @foreach ($movimientos as $key => $label)
                <label>
                    <input type="checkbox" name="movimientos[]" value="{{ $label }}" {{ $seleccionados->contains($label) ? 'checked' : '' }}>
                    {{ $label }}
                </label>
            @endforeach
        </div>
        <div class="form-group">
            <label for="movimientos_otros">Otros movimientos / detalles</label>
            <textarea id="movimientos_otros" name="movimientos_otros" rows="2" placeholder="Describe otras acciones realizadas">{{ old('movimientos_otros', $registro['movimientos_otros'] ?? '') }}</textarea>
        </div>

        <div class="sec">Evidencia fotográfica</div>
        <div class="form-group">
            <label for="evidencia_envio">Envío del equipo (hasta 5 fotos)</label>
            <input type="file" id="evidencia_envio" name="evidencia_envio[]" accept="image/*" multiple>
            <div style="font-size:12px; color:var(--muted); margin-top:4px;">Cómo se empaca / envía el equipo.</div>
        </div>
        <div class="form-group">
            <label for="evidencia_devolucion">Devolución del equipo (hasta 5 fotos)</label>
            <input type="file" id="evidencia_devolucion" name="evidencia_devolucion[]" accept="image/*" multiple>
            <div style="font-size:12px; color:var(--muted); margin-top:4px;">Cómo se devuelve el equipo al cliente.</div>
        </div>

        <div class="qr-actions">
            <button type="submit" class="btn">Guardar registro</button>
            <a href="{{ $nextUrl }}">Omitir</a>
        </div>
    </form>
@endsection
