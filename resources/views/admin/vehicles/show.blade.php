@extends('layouts.dashboard')
@section('title', 'Detalle de Vehículo')
@section('page-title', 'Detalle de Vehículo')
@section('page-sub', 'Ficha técnica y control del vehículo')

@php
    $statusInfo = match($vehicle->status) {
        'maintenance' => ['variant' => 'warn', 'label' => 'En mantenimiento'],
        'inactive'    => ['variant' => 'danger', 'label' => 'Inactivo'],
        default       => ['variant' => 'ok', 'label' => 'Activo'],
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
    .vd-layout { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; align-items: start; }
    @media (max-width: 900px) { .vd-layout { grid-template-columns: 1fr; } }

    .vd-photo-main {
        width: 100%; aspect-ratio: 16/10; border-radius: 10px; overflow: hidden;
        background: var(--surface-2); border: 1px solid var(--border);
        position: relative; display: flex; align-items: center; justify-content: center;
    }
    .vd-photo-main svg { width: 56px; height: 56px; color: var(--muted); }
    .vd-photo-zoom {
        position: absolute; top: 10px; right: 10px;
        width: 34px; height: 34px; border-radius: 8px;
        background: var(--surface); border: 1px solid var(--border);
        display: flex; align-items: center; justify-content: center;
        cursor: pointer; transition: all .15s; color: var(--text);
    }
    .vd-photo-zoom:hover { background: var(--primary); color: #fff; border-color: var(--primary); }
    .vd-photo-zoom svg { width: 17px; height: 17px; color: inherit; }
    .vd-photo-thumbs {
        display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; margin-top: 10px;
    }
    .vd-photo-thumb {
        aspect-ratio: 4/3; border-radius: 8px; overflow: hidden;
        border: 1.5px solid var(--border); cursor: pointer;
        background: var(--surface-2); transition: border-color .15s;
    }
    .vd-photo-thumb:hover, .vd-photo-thumb.active { border-color: var(--primary); }

    .vd-doc-item {
        display: flex; align-items: center; gap: 12px;
        padding: 12px 14px; border: 1px solid var(--border);
        border-radius: 10px; background: var(--surface);
        margin-bottom: 8px; transition: border-color .15s;
    }
    .vd-doc-item:hover { border-color: var(--primary); }
    .vd-doc-icon {
        width: 36px; height: 36px; border-radius: 9px;
        background: var(--danger-soft); color: var(--danger);
        display: flex; align-items: center; justify-content: center; flex: 0 0 auto;
    }
    .vd-doc-icon svg { width: 17px; height: 17px; }
    .vd-doc-info { flex: 1; min-width: 0; }
    .vd-doc-name { font-size: 13.5px; font-weight: 700; margin: 0; }
    .vd-doc-status { font-size: 12px; color: var(--muted); margin: 1px 0 0; }
    .vd-doc-actions { display: flex; gap: 6px; flex: 0 0 auto; }
    .vd-doc-btn {
        width: 32px; height: 32px; border-radius: 8px;
        border: 1px solid var(--border); background: var(--surface);
        color: var(--muted); cursor: pointer;
        display: flex; align-items: center; justify-content: center;
        transition: all .15s;
    }
    .vd-doc-btn:hover { background: var(--primary-soft); color: var(--primary); border-color: var(--primary); }
    .vd-doc-btn svg { width: 15px; height: 15px; }

    .vd-info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    @media (max-width: 900px) { .vd-info-grid { grid-template-columns: 1fr; } }
    .vd-info-item {
        display: flex; flex-direction: column; gap: 4px;
        padding: 12px 14px; border: 1px solid var(--border);
        border-radius: 10px; background: var(--surface-2);
    }
    .vd-info-label {
        font-size: 11px; font-weight: 700; text-transform: uppercase;
        letter-spacing: .04em; color: var(--muted);
    }
    .vd-info-value { font-size: 15px; font-weight: 700; color: var(--text); }
</style>
@endpush

@section('content')
    <x-ui.page-header
        :title="trim(($vehicle->brand ?? '') . ' ' . ($vehicle->model ?? '') . ' ' . ($vehicle->year ?? '')) ?: 'Vehículo'"
        :back="route('admin.vehicles.index')"
    >
        <button type="button" class="btn" onclick="alert('Edición próximamente')">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="15" height="15"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
            Editar ficha
        </button>
        <button type="button" class="btn btn--danger" onclick="alert('Desactivar próximamente')">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="15" height="15"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
            Desactivar
        </button>
    </x-ui.page-header>

    <div style="display:flex; align-items:center; gap:10px; margin:-6px 0 18px;">
        <span class="badge badge--plain" style="font-family:ui-monospace, Consolas, monospace; font-weight:700;">{{ $vehicle->plate_number }}</span>
        <x-ui.badge :variant="$statusInfo['variant']">{{ $statusInfo['label'] }}</x-ui.badge>
    </div>

    <div class="vd-layout">
        {{-- Columna izquierda --}}
        <div>
            @php($photos = $vehicle->photos ?: [])
            <x-ui.card style="margin-bottom:18px;">
                <div class="vd-photo-main" id="vdMainPhoto">
                    @if(count($photos))
                        <img src="{{ asset('storage/'.$photos[0]) }}" alt="Foto del vehículo" style="width:100%;height:100%;object-fit:cover;">
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
                            <div class="vd-photo-thumb {{ $loop->first ? 'active' : '' }}" onclick="vdSelectThumb(this, '{{ asset('storage/'.$photo) }}')" style="background-image:url('{{ asset('storage/'.$photo) }}');background-size:cover;background-position:center;"></div>
                        @endforeach
                    </div>
                @else
                    <p class="muted" style="margin:12px 0 0; font-size:13px;">Este vehículo no tiene fotos registradas.</p>
                @endif
            </x-ui.card>

            <x-ui.card>
                <x-ui.section-title style="margin:0 0 14px;">Expediente de documentos</x-ui.section-title>
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
            </x-ui.card>
        </div>

        {{-- Columna derecha --}}
        <div>
            <x-ui.card>
                <x-ui.section-title style="margin:0 0 14px;">Ficha técnica</x-ui.section-title>
                <div class="vd-info-grid">
                    <div class="vd-info-item">
                        <span class="vd-info-label">VIN</span>
                        <span class="vd-info-value">{{ $vehicle->vin ?: 'N/A' }}</span>
                    </div>
                    <div class="vd-info-item">
                        <span class="vd-info-label">Año</span>
                        <span class="vd-info-value">{{ $vehicle->year ?: 'N/A' }}</span>
                    </div>
                    <div class="vd-info-item">
                        <span class="vd-info-label">Color</span>
                        <span class="vd-info-value">{{ $vehicle->color ?: 'N/A' }}</span>
                    </div>
                    <div class="vd-info-item">
                        <span class="vd-info-label">Número de póliza</span>
                        <span class="vd-info-value">{{ $vehicle->insurance_policy_number ?: 'N/A' }}</span>
                    </div>
                </div>
            </x-ui.card>
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
                img.style.cssText = 'width:100%;height:100%;object-fit:cover;';
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
