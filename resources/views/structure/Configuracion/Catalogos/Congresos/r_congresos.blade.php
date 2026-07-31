@extends('structure.Configuracion.layout')

@section('title', 'Ver Congreso')

@section('configuracion_content')
    @php
        $today = now()->startOfDay();
        $start = $congress->start_date->startOfDay();
        $end = $congress->end_date->startOfDay();
        if ($today < $start) { $estado = 'upcoming'; $estadoLabel = 'Programado'; $estadoColor = '#06b6d4'; }
        elseif ($today > $end) { $estado = 'finished'; $estadoLabel = 'Finalizado'; $estadoColor = '#22c55e'; }
        else { $estado = 'active'; $estadoLabel = 'En curso'; $estadoColor = '#f59e0b'; }
    @endphp

    <div style="max-width:1200px; margin:0 auto;">
        <div class="card catalog-card">
            <div class="catalog-header" style="display:flex; justify-content:space-between; align-items:center; gap:16px; flex-wrap:wrap;">
                <div>
                    <h2 class="page-title">{{ $congress->name }}</h2>
                    <p class="page-sub" style="margin:4px 0 0;">Detalle del congreso · <span style="display:inline-block; padding:3px 10px; border-radius:999px; font-size:12px; font-weight:700; background:{{ $estadoColor }}22; color:{{ $estadoColor }};">{{ $estadoLabel }}</span></p>
                </div>
                <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                    <a href="{{ route('configuracion.catalogos.index') }}" class="btn btn--secondary" style="text-decoration:none;">Regresar</a>
                    <a href="{{ route('configuracion.congresos.edit', $congress) }}" class="btn" style="text-decoration:none;">Editar</a>
                    <a href="{{ route('configuracion.congresos.delete', $congress) }}" class="btn" style="background:#ef4444; color:#fff; text-decoration:none;">Eliminar</a>
                </div>
            </div>

            <div style="display:grid; grid-template-columns:5fr 4fr; gap:16px; align-items:start; margin-top:18px;">
                <div class="form-section section-info">
                    <h3 class="form-section-title">Información general</h3>

                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                        <div>
                            <p class="form-label">Nombre</p>
                            <p style="font-size:15px; font-weight:600; margin:4px 0 0;">{{ $congress->name }}</p>
                        </div>
                        <div>
                            <p class="form-label">Categoría</p>
                            <p style="font-size:15px; font-weight:600; margin:4px 0 0;">{{ $congress->category?->name ?? '—' }}</p>
                        </div>
                    </div>

                    <div style="margin-top:16px;">
                        <p class="form-label">Comentarios</p>
                        <p style="font-size:15px; margin:4px 0 0; white-space:pre-wrap;">{{ $congress->comments ?: 'Sin comentarios' }}</p>
                    </div>

                    @if ($congress->image_path && count($congress->image_path))
                        <div style="margin-top:16px;">
                            <p class="form-label">Archivos / Portada</p>
                            <div class="files-preview">
                                @foreach ($congress->image_path as $path)
                                    @php
                                        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                                        $fileExists = \Illuminate\Support\Facades\Storage::disk('public')->exists($path);
                                        $isImage = in_array($ext, ['jpg','jpeg','png','webp','gif','svg','bmp']);
                                    @endphp
                                    <div class="file-card" style="align-items:center;">
                                        <div class="thumb" style="display:flex; align-items:center; justify-content:center; overflow:hidden;">
                                            @if ($fileExists && $isImage)
                                                <img src="{{ asset('storage/' . $path) }}" alt="{{ basename($path) }}" style="width:100%; height:100%; object-fit:cover;">
                                            @else
                                                <span style="font-size:13px; font-weight:700; color:var(--text-main);">{{ strtoupper($ext) }}</span>
                                            @endif
                                        </div>
                                        <div class="name">{{ basename($path) }}</div>
                                        @if ($fileExists)
                                            <a href="{{ asset('storage/' . $path) }}" target="_blank" rel="noopener" class="btn btn--secondary" style="margin-top:6px; text-decoration:none; font-size:12px; padding:4px 8px;">Ver</a>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                <div style="display:flex; flex-direction:column; gap:16px;">
                    <div class="form-section section-schedule">
                        <h3 class="form-section-title">Programación</h3>
                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                            <div>
                                <p class="form-label">Fecha de inicio</p>
                                <p style="font-size:15px; font-weight:600; margin:4px 0 0;">{{ $congress->start_date->format('d/m/Y') }}</p>
                            </div>
                            <div>
                                <p class="form-label">Fecha de finalización</p>
                                <p style="font-size:15px; font-weight:600; margin:4px 0 0;">{{ $congress->end_date->format('d/m/Y') }}</p>
                            </div>
                            <div>
                                <p class="form-label">Hora de montaje</p>
                                <p style="font-size:15px; font-weight:600; margin:4px 0 0;">{{ $congress->assembly_time->format('H:i') }}</p>
                            </div>
                            <div>
                                <p class="form-label">Hora de desmontaje</p>
                                <p style="font-size:15px; font-weight:600; margin:4px 0 0;">{{ $congress->disassembly_time->format('H:i') }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="form-section section-access">
                        <h3 class="form-section-title">Configuración de acceso</h3>

                        <div style="margin-bottom:12px;">
                            <p class="access-title">Descarga de recursos</p>
                            <p class="access-desc">{{ $congress->download_access ? 'Habilitada' : 'Deshabilitada' }}</p>
                            @if ($congress->download_access)
                                <p style="font-size:14px; margin-top:4px;">{{ $congress->download_text ?: '—' }}</p>
                            @endif
                        </div>

                        <hr class="form-divider">

                        <div style="margin-top:12px;">
                            <p class="access-title">Carga de archivos</p>
                            <p class="access-desc">{{ $congress->upload_access ? 'Habilitada' : 'Deshabilitada' }}</p>
                            @if ($congress->upload_access)
                                <p style="font-size:14px; margin-top:4px;">{{ $congress->upload_text ?: '—' }}</p>
                            @endif
                        </div>
                    </div>

                    <div class="form-section section-location">
                        <h3 class="form-section-title">Ubicación</h3>
                        <p style="font-size:15px; font-weight:600; margin:0 0 8px;">{{ $congress->address ?: '—' }}</p>
                        @if ($congress->address)
                            <a id="preview-map" href="https://www.google.com/maps/search/?api=1&query={{ urlencode($congress->address) }}" target="_blank" rel="noopener">Ver en Google Maps</a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('head')
        @include('structure.Configuracion.Catalogos.Congresos.styles_congreso')
    @endpush
@endsection