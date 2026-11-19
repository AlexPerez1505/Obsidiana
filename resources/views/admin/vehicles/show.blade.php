@extends('layouts.dashboard')

@php
    $estado = match ($vehicle->status) {
        'maintenance' => ['texto' => 'En mantenimiento', 'badge' => 'badge--warn'],
        'inactive' => ['texto' => 'Inactivo', 'badge' => 'badge--danger'],
        default => ['texto' => 'Activo', 'badge' => 'badge--ok'],
    };

    $nombre = trim($vehicle->brand.' '.$vehicle->model.' '.$vehicle->year) ?: 'Vehículo';
    $fotos = $vehicle->photos ?: [];

    $documentos = [
        'Tarjeta de circulación' => $vehicle->circulation_card_doc,
        'Póliza de seguro' => $vehicle->insurance_doc,
        'Pago de tenencia' => $vehicle->tenancy_doc,
        'Verificación vehicular' => $vehicle->verification_doc,
    ];
@endphp

@section('title', $vehicle->plate_number)
@section('page-title', $vehicle->plate_number)
@section('page-sub', $nombre)

@section('content')
    <x-ui.page-header :title="$nombre" :back="route('admin.vehicles.index')">
        <span class="badge {{ $estado['badge'] }}">{{ $estado['texto'] }}</span>
        @can('rutas.flota')
            <a href="{{ route('logistica.flota.show', $vehicle) }}" class="btn btn--ghost">Combustible y servicios</a>
        @endcan
    </x-ui.page-header>

    <div class="vd-layout">
        {{-- ===================== Fotos ===================== --}}
        <div class="card" style="padding:0;">
            <div class="card-head"><h3>Fotos</h3><span class="right campo-nota">{{ count($fotos) }} de 10</span></div>
            <div class="vd-fotos">
                <a class="vd-principal" id="vdPrincipal"
                   @if (count($fotos)) href="{{ asset('storage/'.$fotos[0]) }}" target="_blank" rel="noopener" @endif>
                    @if (count($fotos))
                        <img src="{{ asset('storage/'.$fotos[0]) }}" alt="Foto del vehículo">
                    @else
                        <span class="vd-sin-foto"><x-gravityui-car /> Sin fotos registradas</span>
                    @endif
                </a>
                @if (count($fotos) > 1)
                    <div class="vd-miniaturas">
                        @foreach ($fotos as $foto)
                            <button type="button" class="vd-mini {{ $loop->first ? 'activa' : '' }}" data-foto="{{ asset('storage/'.$foto) }}" aria-label="Ver foto {{ $loop->iteration }}">
                                <img src="{{ asset('storage/'.$foto) }}" alt="">
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <div class="vd-col">
            {{-- ===================== Ficha técnica ===================== --}}
            <div class="card" style="padding:0;">
                <div class="card-head"><h3>Ficha técnica</h3></div>
                <dl class="vd-ficha">
                    <div><dt>Placa</dt><dd>{{ $vehicle->plate_number }}</dd></div>
                    <div><dt>Marca</dt><dd>{{ $vehicle->brand ?: '—' }}</dd></div>
                    <div><dt>Modelo</dt><dd>{{ $vehicle->model ?: '—' }}</dd></div>
                    <div><dt>Año</dt><dd>{{ $vehicle->year ?: '—' }}</dd></div>
                    <div><dt>Color</dt><dd>{{ $vehicle->color ?: '—' }}</dd></div>
                    <div><dt>VIN</dt><dd>{{ $vehicle->vin ?: '—' }}</dd></div>
                    <div><dt>Número de póliza</dt><dd>{{ $vehicle->insurance_policy_number ?: '—' }}</dd></div>
                    <div><dt>Alta</dt><dd>{{ $vehicle->created_at?->format('d/m/Y') ?? '—' }}</dd></div>
                </dl>
            </div>

            {{-- ===================== Documentos ===================== --}}
            <div class="card" style="padding:0;">
                <div class="card-head">
                    <h3>Documentos</h3>
                    <span class="right campo-nota">{{ collect($documentos)->filter()->count() }} de {{ count($documentos) }}</span>
                </div>
                @foreach ($documentos as $doc => $archivo)
                    <div class="vd-doc">
                        <div style="min-width:0; flex:1;">
                            <div class="t">{{ $doc }}</div>
                            <div class="s">{{ $archivo ? 'Archivo adjunto' : 'Sin archivo' }}</div>
                        </div>
                        @if ($archivo)
                            <a class="btn-icono" href="{{ asset('storage/'.$archivo) }}" target="_blank" rel="noopener" title="Ver" aria-label="Ver {{ $doc }}">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            </a>
                            <a class="btn-icono" href="{{ asset('storage/'.$archivo) }}" download title="Descargar" aria-label="Descargar {{ $doc }}">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                            </a>
                        @else
                            <span class="badge badge--plain">Pendiente</span>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <style>
        .vd-layout { display:grid; grid-template-columns:minmax(0,1.1fr) minmax(0,1fr); gap:18px; align-items:start; }
        .vd-col { display:grid; gap:18px; min-width:0; }

        .vd-fotos { padding:16px; }
        .vd-principal { display:flex; align-items:center; justify-content:center; aspect-ratio:16/10;
                        border-radius:10px; overflow:hidden; background:var(--surface-2); color:var(--muted); }
        .vd-principal img { width:100%; height:100%; object-fit:cover; display:block; }
        .vd-sin-foto { display:flex; flex-direction:column; align-items:center; gap:8px; font-size:13px; }
        .vd-sin-foto svg { width:40px; height:40px; opacity:.5; }
        .vd-miniaturas { display:grid; grid-template-columns:repeat(auto-fill, minmax(72px, 1fr)); gap:8px; margin-top:10px; }
        .vd-mini { aspect-ratio:4/3; padding:0; border:2px solid transparent; border-radius:8px; overflow:hidden;
                   background:var(--surface-2); cursor:pointer; transition:border-color .15s ease; }
        .vd-mini:hover { border-color:var(--border); }
        .vd-mini.activa { border-color:var(--primary); }
        .vd-mini img { width:100%; height:100%; object-fit:cover; display:block; }

        .vd-ficha { margin:0; padding:6px 18px 10px; display:grid; }
        .vd-ficha > div { display:flex; justify-content:space-between; gap:16px; padding:10px 0;
                          border-bottom:1px solid var(--border); }
        .vd-ficha > div:last-child { border-bottom:0; }
        .vd-ficha dt { color:var(--muted); font-size:13px; }
        .vd-ficha dd { margin:0; font-size:13.5px; font-weight:500; text-align:right; overflow-wrap:anywhere; }

        .vd-doc { display:flex; align-items:center; gap:8px; padding:11px 18px; border-bottom:1px solid var(--border); }
        .vd-doc:last-child { border-bottom:0; }
        .vd-doc .t { font-weight:600; font-size:14px; }
        .vd-doc .s { color:var(--muted); font-size:12.5px; }

        @media (max-width: 900px) { .vd-layout { grid-template-columns:1fr; } }
    </style>

    @push('scripts')
    <script>
    (function () {
        var principal = document.getElementById('vdPrincipal');
        document.querySelectorAll('.vd-mini').forEach(function (mini) {
            mini.addEventListener('click', function () {
                document.querySelectorAll('.vd-mini').forEach(function (m) { m.classList.remove('activa'); });
                mini.classList.add('activa');
                principal.href = mini.dataset.foto;
                principal.querySelector('img').src = mini.dataset.foto;
            });
        });
    })();
    </script>
    @endpush
@endsection
