@extends('structure.commercial_management.erp')

@section('title', 'Orden de servicio · ' . ($service->service_number ?? 'OS-' . $service->id))

@section('erp_content')
    @php
        $equipment = $service->serviceEquipment;
        $fotos = collect([$equipment?->evidence_1_path, $equipment?->evidence_2_path, $equipment?->evidence_3_path])->filter();
        $inputStyle = 'width:100%; padding:11px 13px; border:1px solid var(--border); border-radius:9px; background:var(--surface); color:var(--text); font-size:14px;';
        $readonlyStyle = $inputStyle . ' background:var(--surface-2, var(--surface)); color:var(--muted);';
        $labelStyle = 'display:block; font-size:12px; color:var(--muted); font-weight:700; text-transform:uppercase; letter-spacing:.03em; margin-bottom:6px;';
    @endphp

    <div class="erp-head">
        <div class="erp-head-l">
            <a href="{{ route('gestion.servicios.servicio_externo.orden_servicio', $service) }}" class="erp-back" title="Volver">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
            </a>
            <h1 class="erp-h1">Orden de servicio · {{ $service->service_number ?? ('OS-' . $service->id) }}</h1>
            <span class="erp-badge info"><span class="dot"></span>Externo interno</span>
        </div>
    </div>

    <div style="margin-bottom:14px; font-size:12px; color:var(--muted); font-weight:700; text-transform:uppercase; letter-spacing:.04em;">Paso 2 de 4 · Especificaciones</div>

    <form method="POST" action="{{ route('gestion.servicios.servicio_externo.orden_servicio.paso3', $service) }}">
        @csrf
        <input type="hidden" name="fecha_ingreso" value="{{ $fechas['fecha_ingreso'] }}">
        <input type="hidden" name="fecha_mantenimiento" value="{{ $fechas['fecha_mantenimiento'] }}">

        <div class="erp-card pad" style="max-width:860px;">
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:18px;">
                <div>
                    <label style="{{ $labelStyle }}">Equipo</label>
                    <div style="{{ $readonlyStyle }}">{{ $equipment?->type_text ?? '—' }}</div>
                    <input type="hidden" name="equipo" value="{{ $equipment?->type_text }}">
                </div>
                <div>
                    <label style="{{ $labelStyle }}">Marca</label>
                    <div style="{{ $readonlyStyle }}">{{ $equipment?->brand_text ?? '—' }}</div>
                    <input type="hidden" name="marca" value="{{ $equipment?->brand_text }}">
                </div>
                <div>
                    <label style="{{ $labelStyle }}">Modelo</label>
                    <div style="{{ $readonlyStyle }}">{{ $equipment?->model_text ?? '—' }}</div>
                    <input type="hidden" name="modelo" value="{{ $equipment?->model_text }}">
                </div>
                <div>
                    <label style="{{ $labelStyle }}">Número de serie</label>
                    <div style="{{ $readonlyStyle }}">{{ $equipment?->serial_number ?? '—' }}</div>
                    <input type="hidden" name="serie" value="{{ $equipment?->serial_number }}">
                </div>
                <div>
                    <label for="proximo_mantenimiento" style="{{ $labelStyle }}">Próximo mantenimiento</label>
                    <select id="proximo_mantenimiento" name="proximo_mantenimiento" style="{{ $inputStyle }}">
                        <option value="">—</option>
                        @foreach ([3, 6, 9, 12] as $meses)
                            <option value="{{ $meses }}" {{ (string) old('proximo_mantenimiento') === (string) $meses ? 'selected' : '' }}>{{ $meses }} meses</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="tipo_mantenimiento" style="{{ $labelStyle }}">Tipo de mantenimiento</label>
                    <select id="tipo_mantenimiento" name="tipo_mantenimiento" style="{{ $inputStyle }}">
                        <option value="">—</option>
                        <option value="preventivo" {{ old('tipo_mantenimiento', $tipoMantenimiento) === 'preventivo' ? 'selected' : '' }}>Preventivo</option>
                        <option value="correctivo" {{ old('tipo_mantenimiento', $tipoMantenimiento) === 'correctivo' ? 'selected' : '' }}>Correctivo</option>
                    </select>
                    <div style="font-size:12px; color:var(--muted); margin-top:4px;">Se toma del registro de mantenimiento del servicio.</div>
                </div>
                <div style="grid-column:1/-1;">
                    <label for="observaciones" style="{{ $labelStyle }}">Observaciones iniciales</label>
                    <textarea id="observaciones" name="observaciones" rows="3" style="{{ $inputStyle }} resize:vertical;" placeholder="Observaciones del equipo">{{ old('observaciones', $equipment?->observations) }}</textarea>
                </div>
            </div>
        </div>

        <div class="erp-card pad" style="max-width:860px; margin-top:16px;">
            <div style="{{ $labelStyle }} margin-bottom:12px;">Registro fotográfico</div>
            @if ($fotos->isNotEmpty())
                <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(160px,1fr)); gap:14px;">
                    @foreach ($fotos as $i => $foto)
                        <div style="text-align:center;">
                            <img src="{{ asset('storage/' . $foto) }}" alt="Foto {{ $i + 1 }}" style="width:100%; height:120px; object-fit:cover; border:1px dashed var(--border); border-radius:10px;">
                            <div style="font-size:12px; color:var(--muted); margin-top:6px;">Foto {{ $i + 1 }}</div>
                        </div>
                    @endforeach
                </div>
            @else
                <div style="color:var(--muted); font-size:13px;">Este servicio no tiene fotografías registradas.</div>
            @endif
        </div>

        <div style="margin-top:18px; display:flex; gap:10px; align-items:center;">
            <a href="{{ route('gestion.servicios.servicio_externo.orden_servicio', $service) }}" class="erp-btn ghost">← Anterior</a>
            <button type="submit" class="erp-btn">Siguiente →</button>
        </div>
    </form>
@endsection
