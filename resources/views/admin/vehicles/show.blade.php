@extends('layouts.dashboard')
@section('title', 'Detalle de Vehículo')
@section('page-title', 'Detalle de Vehículo')
@section('page-sub', 'Ficha técnica y control del vehículo')

@php
    $statusInfo = match($vehicle->status) {
        'maintenance' => ['class' => 'maintenance', 'label' => 'En mantenimiento', 'dot' => 'yellow'],
        'inactive'    => ['class' => 'inactive', 'label' => 'Inactivo', 'dot' => 'red'],
        default       => ['class' => 'active', 'label' => 'Activo', 'dot' => 'green'],
    };

    $documents = [
        ['name' => 'Tarjeta de Circulación', 'file' => $vehicle->circulation_card_doc],
        ['name' => 'Póliza de Seguro', 'file' => $vehicle->insurance_doc],
        ['name' => 'Pago de Tenencia', 'file' => $vehicle->tenancy_doc],
        ['name' => 'Verificación Vehicular', 'file' => $vehicle->verification_doc],
    ];
@endphp

@push('head')
<style>
    .vd-header-row {
        display: flex; align-items: center; gap: 16px; flex-wrap: wrap;
        margin-bottom: 24px;
    }
    .vd-back {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 9px 16px; border: 1.5px solid #94a3b8; border-radius: 10px;
        background: var(--surface); color: var(--text);
        font-size: 14px; font-weight: 700; text-decoration: none;
        transition: all .15s;
    }
    .vd-back:hover { background: var(--surface-2); border-color: var(--primary); color: var(--primary); }
    .vd-back svg { width: 18px; height: 18px; }
    .vd-title-block { flex: 1; min-width: 200px; }
    .vd-title {
        font-size: 22px; font-weight: 800; margin: 0;
        text-transform: uppercase; letter-spacing: -.01em;
    }
    .vd-plate {
        display: inline-flex; align-items: center; gap: 8px;
        font-size: 15px; font-weight: 700; color: var(--muted); margin-top: 4px;
    }
    .vd-plate-badge {
        display: inline-block; padding: 3px 12px; border-radius: 6px;
        background: var(--surface-2); border: 1.5px solid #94a3b8;
        font-size: 14px; font-weight: 800; letter-spacing: .05em;
    }
    .vd-status-pill {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 5px 14px; border-radius: 20px;
        font-size: 13px; font-weight: 700;
        border: 1.5px solid transparent;
    }
    .vd-status-pill .dot { width: 8px; height: 8px; border-radius: 50%; }
    .vd-status-pill.active { background: #e6ffe6; color: #15803d; border-color: #22c55e; }
    .vd-status-pill.active .dot { background: #22c55e; }
    .vd-status-pill.maintenance { background: #fef9c3; color: #a16207; border-color: #f59e0b; }
    .vd-status-pill.maintenance .dot { background: #f59e0b; }
    .vd-status-pill.inactive { background: #ffebeb; color: #ff4a4a; border-color: #ef4444; }
    .vd-status-pill.inactive .dot { background: #ef4444; }

    .vd-actions { display: flex; gap: 10px; flex: 0 0 auto; }
    .vd-btn {
        display: inline-flex; align-items: center; gap: 7px;
        padding: 10px 18px; border: 1.5px solid #94a3b8; border-radius: 10px;
        font-size: 14px; font-weight: 700; cursor: pointer;
        font-family: inherit; transition: all .15s; text-decoration: none;
    }
    .vd-btn svg { width: 17px; height: 17px; }
    .vd-btn-edit { background: var(--primary); color: #fff; border-color: rgba(255,255,255,.35); box-shadow: 0 2px 0 rgba(0,0,0,.12); }
    .vd-btn-edit:hover { background: var(--primary-strong); }
    .vd-btn-danger { background: #fee2e2; color: #dc2626; }
    .vd-btn-danger:hover { background: #fecaca; }

    .vd-layout { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; align-items: flex-start; }

    /* Left column */
    .vd-photo-card {
        background: var(--surface); border: 1.5px solid #94a3b8;
        border-radius: 16px; padding: 16px; box-shadow: 0 2px 8px rgba(0,0,0,.04);
    }
    .vd-photo-main {
        width: 100%; aspect-ratio: 16/10; border-radius: 12px; overflow: hidden;
        background: var(--surface-2); border: 1.5px solid #94a3b8;
        position: relative; display: flex; align-items: center; justify-content: center;
    }
    .vd-photo-main svg { width: 64px; height: 64px; color: #cbd5e1; }
    .vd-photo-zoom {
        position: absolute; top: 12px; right: 12px;
        width: 36px; height: 36px; border-radius: 9px;
        background: rgba(255,255,255,.9); border: 1.5px solid #94a3b8;
        display: flex; align-items: center; justify-content: center;
        cursor: pointer; transition: all .15s;
    }
    .vd-photo-zoom:hover { background: var(--primary); color: #fff; border-color: var(--primary); }
    .vd-photo-zoom svg { width: 18px; height: 18px; color: inherit; }
    .vd-photo-thumbs {
        display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin-top: 12px;
    }
    .vd-photo-thumb {
        aspect-ratio: 4/3; border-radius: 10px; overflow: hidden;
        border: 2px solid #94a3b8; cursor: pointer;
        background: var(--surface-2); transition: border-color .15s;
        display: flex; align-items: center; justify-content: center; position: relative;
    }
    .vd-photo-thumb:hover { border-color: var(--primary); }
    .vd-photo-thumb.active { border-color: var(--primary); }
    .vd-photo-thumb svg { width: 24px; height: 24px; color: #cbd5e1; }
    .vd-photo-thumb-label {
        position: absolute; bottom: 4px; left: 4px;
        font-size: 9px; font-weight: 700; color: var(--muted);
        background: rgba(255,255,255,.85); padding: 1px 6px; border-radius: 4px;
    }

    /* Document expediente */
    .vd-docs-card {
        background: var(--surface); border: 1.5px solid #94a3b8;
        border-radius: 16px; padding: 20px; margin-top: 20px;
        box-shadow: 0 2px 8px rgba(0,0,0,.04);
    }
    .vd-docs-title {
        font-size: 14px; font-weight: 800; text-transform: uppercase;
        letter-spacing: .04em; color: var(--primary); margin: 0 0 14px;
        padding-bottom: 8px; border-bottom: 1.5px solid #94a3b8;
        display: flex; align-items: center; gap: 8px;
    }
    .vd-docs-title svg { width: 18px; height: 18px; }
    .vd-doc-item {
        display: flex; align-items: center; gap: 12px;
        padding: 12px 14px; border: 1.5px solid #94a3b8;
        border-radius: 10px; background: var(--surface);
        margin-bottom: 8px; transition: border .15s;
    }
    .vd-doc-item:hover { border-color: var(--primary); }
    .vd-doc-icon {
        width: 38px; height: 38px; border-radius: 9px;
        background: #fee2e2; color: #dc2626;
        display: flex; align-items: center; justify-content: center; flex: 0 0 auto;
        border: 1.5px solid #94a3b8;
    }
    .vd-doc-icon svg { width: 18px; height: 18px; }
    .vd-doc-info { flex: 1; }
    .vd-doc-name { font-size: 13.5px; font-weight: 700; margin: 0; }
    .vd-doc-status { font-size: 12px; color: var(--muted); margin: 1px 0 0; }
    .vd-doc-actions { display: flex; gap: 6px; flex: 0 0 auto; }
    .vd-doc-btn {
        width: 34px; height: 34px; border-radius: 8px;
        border: 1.5px solid #94a3b8; background: var(--surface);
        color: var(--muted); cursor: pointer;
        display: flex; align-items: center; justify-content: center;
        transition: all .15s;
    }
    .vd-doc-btn:hover { background: var(--primary-soft); color: var(--primary); border-color: var(--primary); }
    .vd-doc-btn svg { width: 16px; height: 16px; }

    /* Right column */
    .vd-info-card {
        background: var(--surface); border: 1.5px solid #94a3b8;
        border-radius: 16px; padding: 20px; margin-bottom: 20px;
        box-shadow: 0 2px 8px rgba(0,0,0,.04);
    }
    .vd-info-title {
        font-size: 14px; font-weight: 800; text-transform: uppercase;
        letter-spacing: .04em; color: var(--primary); margin: 0 0 16px;
        padding-bottom: 8px; border-bottom: 1.5px solid #94a3b8;
        display: flex; align-items: center; gap: 8px;
    }
    .vd-info-title svg { width: 18px; height: 18px; }
    .vd-info-grid {
        display: grid; grid-template-columns: 1fr 1fr; gap: 14px;
    }
    .vd-info-item {
        display: flex; flex-direction: column; gap: 4px;
        padding: 12px 14px; border: 1.5px solid #94a3b8;
        border-radius: 10px; background: var(--surface-2);
    }
    .vd-info-label {
        font-size: 11px; font-weight: 700; text-transform: uppercase;
        letter-spacing: .04em; color: var(--muted);
        display: flex; align-items: center; gap: 6px;
    }
    .vd-info-label svg { width: 14px; height: 14px; }
    .vd-info-value { font-size: 15px; font-weight: 700; color: var(--text); }

    /* Maintenance section */
    .vd-maint-item {
        display: flex; align-items: center; gap: 12px;
        padding: 14px 16px; border: 1.5px solid #94a3b8;
        border-radius: 10px; background: var(--surface);
        margin-bottom: 8px;
    }
    .vd-maint-dot {
        width: 12px; height: 12px; border-radius: 50%; flex: 0 0 auto;
        border: 2px solid var(--surface);
    }
    .vd-maint-dot.green { background: #22c55e; box-shadow: 0 0 0 1px #22c55e; }
    .vd-maint-dot.yellow { background: #f59e0b; box-shadow: 0 0 0 1px #f59e0b; }
    .vd-maint-dot.red { background: #ef4444; box-shadow: 0 0 0 1px #ef4444; }
    .vd-maint-info { flex: 1; }
    .vd-maint-label { font-size: 13px; font-weight: 700; margin: 0; }
    .vd-maint-date { font-size: 12px; color: var(--muted); margin: 2px 0 0; }
    .vd-maint-badge {
        font-size: 11px; font-weight: 700; padding: 3px 10px;
        border-radius: 20px; border: 1.5px solid transparent;
    }
    .vd-maint-badge.green { background: #e6ffe6; color: #15803d; border-color: #22c55e; }
    .vd-maint-badge.yellow { background: #fef9c3; color: #a16207; border-color: #f59e0b; }
    .vd-maint-badge.red { background: #ffebeb; color: #ff4a4a; border-color: #ef4444; }

    @media (max-width: 900px) {
        .vd-layout { grid-template-columns: 1fr; }
        .vd-info-grid { grid-template-columns: 1fr; }
    }
</style>
@endpush

@section('content')
    {{-- Header row --}}
    <div class="vd-header-row">
        <a href="{{ route('admin.vehicles.index') }}" class="vd-back">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
            Volver
        </a>
        <div class="vd-title-block">
            <h1 class="vd-title">{{ strtoupper($vehicle->brand ?? 'Vehículo') }} {{ $vehicle->model ?? '' }} {{ $vehicle->year ?? '' }}</h1>
            <div class="vd-plate">
                <span class="vd-plate-badge">{{ $vehicle->plate_number }}</span>
                <span class="vd-status-pill {{ $statusInfo['class'] }}">
                    <span class="dot"></span>
                    {{ $statusInfo['label'] }}
                </span>
            </div>
        </div>
        <div class="vd-actions">
            <button type="button" class="vd-btn vd-btn-edit" onclick="alert('Edición próximamente')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                Editar Ficha
            </button>
            <button type="button" class="vd-btn vd-btn-danger" onclick="alert('Desactivar próximamente')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
                Desactivar
            </button>
        </div>
    </div>

    <div class="vd-layout">
        {{-- Left Column --}}
        <div>
            {{-- Photo gallery --}}
            @php($photos = $vehicle->photos ?: [])
            <div class="vd-photo-card">
                <div class="vd-photo-main" id="vdMainPhoto">
                    @if(count($photos))
                        <img src="{{ asset('storage/'.$photos[0]) }}" alt="Foto del vehículo" style="width:100%;height:100%;object-fit:cover;border-radius:12px;">
                    @else
                        <x-gravityui-car />
                    @endif
                    <a class="vd-photo-zoom" href="{{ count($photos) ? asset('storage/'.$photos[0]) : '#' }}" target="_blank" rel="noopener" style="{{ count($photos) ? '' : 'pointer-events:none;opacity:.4;' }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35M11 8v6M8 11h6"/></svg>
                    </a>
                </div>
                @if(count($photos))
                    <div class="vd-photo-thumbs">
                        @foreach($photos as $photo)
                            <div class="vd-photo-thumb {{ $loop->first ? 'active' : '' }}" onclick="vdSelectThumb(this, '{{ asset('storage/'.$photo) }}')" style="background-image:url('{{ asset('storage/'.$photo) }}');background-size:cover;background-position:center;">
                            </div>
                        @endforeach
                    </div>
                @else
                    <p style="margin:12px 0 0;color:var(--muted);font-size:13px;">Este vehículo no tiene fotos registradas.</p>
                @endif
            </div>

            {{-- Expediente de Documentos --}}
            <div class="vd-docs-card">
                <h3 class="vd-docs-title">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>
                    Expediente de Documentos
                </h3>
                @foreach($documents as $doc)
                    <div class="vd-doc-item">
                        <div class="vd-doc-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M9 13h6M9 17h6"/></svg>
                        </div>
                        <div class="vd-doc-info">
                            <p class="vd-doc-name">{{ $doc['name'] }}</p>
                            <p class="vd-doc-status">{{ $doc['file'] ? 'Archivo adjunto' : 'Sin archivo adjunto' }}</p>
                        </div>
                        <div class="vd-doc-actions">
                            @if($doc['file'])
                                <a class="vd-doc-btn" href="{{ asset('storage/'.$doc['file']) }}" target="_blank" rel="noopener" title="Ver">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                </a>
                                <a class="vd-doc-btn" href="{{ asset('storage/'.$doc['file']) }}" download title="Descargar">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                                </a>
                            @else
                                <button type="button" class="vd-doc-btn" disabled style="opacity:.4;cursor:not-allowed;" title="Sin archivo">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                </button>
                                <button type="button" class="vd-doc-btn" disabled style="opacity:.4;cursor:not-allowed;" title="Sin archivo">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                                </button>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Right Column --}}
        <div>
            {{-- Ficha Técnica --}}
            <div class="vd-info-card">
                <h3 class="vd-info-title">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
                    Ficha Técnica
                </h3>
                <div class="vd-info-grid">
                    <div class="vd-info-item">
                        <span class="vd-info-label">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="6" width="18" height="12" rx="2"/><path d="M7 12h10"/></svg>
                            VIN
                        </span>
                        <span class="vd-info-value">{{ $vehicle->vin ?: 'N/A' }}</span>
                    </div>
                    <div class="vd-info-item">
                        <span class="vd-info-label">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                            Año
                        </span>
                        <span class="vd-info-value">{{ $vehicle->year ?: 'N/A' }}</span>
                    </div>
                    <div class="vd-info-item">
                        <span class="vd-info-label">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="13.5" cy="6.5" r=".5"/><circle cx="17.5" cy="10.5" r=".5"/><circle cx="8.5" cy="7.5" r=".5"/><circle cx="6.5" cy="12.5" r=".5"/><path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10c.926 0 1.648-.746 1.648-1.688 0-.437-.18-.835-.437-1.125-.29-.289-.438-.652-.438-1.125a1.64 1.64 0 0 1 1.668-1.668h1.996c3.051 0 5.555-2.503 5.555-5.554C21.965 6.012 17.461 2 12 2z"/></svg>
                            Color
                        </span>
                        <span class="vd-info-value">{{ $vehicle->color ?: 'N/A' }}</span>
                    </div>
                    <div class="vd-info-item">
                        <span class="vd-info-label">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                            Número de Póliza
                        </span>
                        <span class="vd-info-value">{{ $vehicle->insurance_policy_number ?: 'N/A' }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function vdSelectThumb(el, url) {
            document.querySelectorAll('.vd-photo-thumb').forEach(t => t.classList.remove('active'));
            el.classList.add('active');

            var main = document.getElementById('vdMainPhoto');
            var img = main.querySelector('img');
            if (!img) {
                img = document.createElement('img');
                img.alt = 'Foto del vehículo';
                img.style.cssText = 'width:100%;height:100%;object-fit:cover;border-radius:12px;';
                main.insertBefore(img, main.firstChild);
            }
            img.src = url;

            var zoom = main.querySelector('.vd-photo-zoom');
            if (zoom) {
                zoom.href = url;
                zoom.style.pointerEvents = '';
                zoom.style.opacity = '';
            }
        }
    </script>
@endsection
