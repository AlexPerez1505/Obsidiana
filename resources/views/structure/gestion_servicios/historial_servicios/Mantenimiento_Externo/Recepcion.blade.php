@extends('structure.commercial_management.erp')

@section('title', 'Recepción de equipo')

@section('erp_content')
    @php
        $equipment = $service->serviceEquipment;
        $customerName = trim(($service->customer?->nombre ?? '') . ' ' . ($service->customer?->apellido ?? '')) ?: 'Sin cliente';
        $yaRecibido = $service->external_received_at !== null;
    @endphp

    <div class="erp-head">
        <div class="erp-head-l">
            <h1 class="erp-h1">Recepción de equipo</h1>
            <span class="erp-count">{{ $service->service_number ?? ('OS-' . $service->id) }}</span>
        </div>
        <a href="{{ route('gestion.servicios.externo') }}" class="erp-btn ghost">Volver a Externo</a>
    </div>

    @if (session('success'))
        <div class="erp-card" style="border-color:var(--green); padding:14px 18px; color:var(--green); font-weight:700;">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="erp-card" style="border-color:var(--danger); padding:14px 18px; color:var(--danger); font-weight:700;">
            <ul style="margin:0; padding-left:18px;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="erp-card" style="padding:20px;">
        <h3 style="margin:0 0 14px;">Datos del equipo</h3>
        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:14px; margin-bottom:20px;">
            <div>
                <div style="color:var(--muted); font-size:12px;">Cliente</div>
                <div style="font-weight:700;">{{ $customerName }}</div>
            </div>
            <div>
                <div style="color:var(--muted); font-size:12px;">Equipo</div>
                <div style="font-weight:700;">{{ $equipment?->type_text ?? '—' }}</div>
            </div>
            <div>
                <div style="color:var(--muted); font-size:12px;">Marca / Modelo</div>
                <div style="font-weight:700;">{{ $equipment?->brand_text ?? '—' }} {{ $equipment?->model_text ?? '' }}</div>
            </div>
            <div>
                <div style="color:var(--muted); font-size:12px;">No. de serie</div>
                <div style="font-weight:700;">{{ $equipment?->serial_number ?? '—' }}</div>
            </div>
        </div>

        @if ($yaRecibido)
            <div style="border:1px dashed var(--border); border-radius:12px; padding:16px; margin-bottom:20px;">
                <div style="color:var(--green); font-weight:800; margin-bottom:8px;">
                    Recepción ya registrada el {{ $service->external_received_at->format('d/m/Y H:i') }}
                </div>
                <p style="margin:0 0 12px; white-space:pre-wrap;">{{ $service->external_reception_notes }}</p>
                <div style="display:flex; gap:10px; flex-wrap:wrap;">
                    @foreach ($service->externalReceptionEvidenceUrls() as $url)
                        <a href="{{ $url }}" target="_blank">
                            <img src="{{ $url }}" alt="Evidencia" style="width:100px; height:100px; object-fit:cover; border-radius:10px; border:1px solid var(--border);">
                        </a>
                    @endforeach
                </div>
                <p style="margin:16px 0 0; color:var(--muted); font-size:13px;">
                    Si necesitas corregir algo, vuelve a llenar el formulario de abajo: se reemplaza lo anterior.
                </p>
            </div>
        @endif

        <h3 style="margin:0 0 14px;">¿Cómo llegó el equipo?</h3>
        <form method="POST" action="{{ route('gestion.servicios.externo.recepcion.store', $service) }}" enctype="multipart/form-data">
            @csrf

            <label for="notas" style="display:block; font-weight:700; margin-bottom:6px;">Descripción</label>
            <textarea id="notas" name="notas" rows="5" placeholder="Describe en qué estado llegó el equipo, golpes, faltantes, accesorios incluidos, etc." required style="width:100%; padding:10px 12px; border:1px solid var(--border); border-radius:10px; background:var(--surface); color:var(--text); font-family:inherit; margin-bottom:18px;">{{ old('notas') }}</textarea>

            <label for="evidencias" style="display:block; font-weight:700; margin-bottom:6px;">Evidencia (1 a 3 fotos)</label>
            <input type="file" id="evidencias" name="evidencias[]" accept="image/*" multiple required style="margin-bottom:18px;">

            <div style="display:flex; justify-content:flex-end; gap:10px;">
                <button type="submit" class="erp-btn">Guardar recepción</button>
            </div>
        </form>
    </div>
@endsection
