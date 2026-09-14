@extends('structure.gestion_servicios.layout')

@section('title', 'Refacciones')

@push('head')
@include('partials.tabla-filtrable.estilos')
<style>
    .cl-stats { display:grid; grid-template-columns:repeat(auto-fit,minmax(210px,1fr)); gap:12px; margin-bottom:16px; }
    .cl-table { width:100%; border-collapse:collapse; }
</style>
@endpush

@section('service_content')
    <div class="content-actions">
        <a href="{{ route('gestion.servicios.refacciones.create') }}" class="btn">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="15" height="15"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Nueva refacción
        </a>
    </div>

    <div class="cl-stats">
        <div class="card card--accent stat">
            <span class="stat-ico blue">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="4" y="4" width="16" height="16" rx="2"/><path d="M9 9h6v6H9z"/></svg>
            </span>
            <div>
                <div class="stat-num">{{ number_format($totalRefacciones) }}</div>
                <div class="stat-lbl">Total de refacciones</div>
            </div>
        </div>

        <div class="card card--accent is-green stat">
            <span class="stat-ico green">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
            </span>
            <div>
                <div class="stat-num">{{ number_format($totalStock) }}</div>
                <div class="stat-lbl">Stock total</div>
            </div>
        </div>

        <div class="card card--accent is-amber stat">
            <span class="stat-ico orange">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="10"/><path d="M9 12l2 2 4-4"/></svg>
            </span>
            <div>
                <div class="stat-num">{{ number_format($totalCompatible) }}</div>
                <div class="stat-lbl">Con compatibilidad</div>
            </div>
        </div>
    </div>

    <div class="f-toolbar">
        <div class="f-search">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input type="text" id="fBuscar" placeholder="Buscar por nombre, subtipo o compatible con" autocomplete="off">
        </div>

        <button type="button" class="flt-btn flt-btn--icon" id="fLimpiar" title="Limpiar búsqueda" aria-label="Limpiar búsqueda">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M22 3H2l8 9.46V19l4 2v-8.54"/><line x1="16" y1="5" x2="22" y2="11"/><line x1="22" y1="5" x2="16" y2="11"/></svg>
        </button>
    </div>

    <div class="flt-chips" id="fChips" hidden></div>

    <div class="card" id="clLista" data-view-list style="overflow-x:auto; padding:0;">
        <table class="cl-table">
            <thead>
                <tr>
                    <th>Refacción</th>
                    <th>Descripción</th>
                    <th style="text-align:center;">Stock</th>
                    <th class="num">Precio</th>
                    <th>Compatible con</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($refacciones as $refaccion)
                    @php
                        $nombre = $refaccion->name;
                        $iniciales = mb_strtoupper(mb_substr($nombre, 0, 2));
                        $tinte = 'a' . ((crc32($iniciales) % 5) + 1);
                        $buscar = str_replace(["\r\n", "\r", "\n"], ' ', mb_strtolower($nombre . ' ' . $refaccion->subtype . ' ' . ($refaccion->compatible_with ?? '') . ' ' . ($refaccion->description ?? '')));
                        $compat = ! empty($refaccion->compatible_with);
                    @endphp
                    <tr class="f-row" data-buscar="{{ $buscar }}">
                        <td>
                            <div class="cell-id">
                                @if ($refaccion->photo_path)
                                    <span class="avatar {{ $tinte }}" style="padding:0; overflow:hidden;">
                                        <img src="{{ asset('storage/' . $refaccion->photo_path) }}" alt="{{ $nombre }}" style="width:100%;height:100%;object-fit:cover;">
                                    </span>
                                @else
                                    <span class="avatar {{ $tinte }}">{{ $iniciales }}</span>
                                @endif
                                <div style="min-width:0;">
                                    <div class="t">{{ $nombre }}</div>
                                    <div class="s">{{ $refaccion->subtype }}</div>
                                </div>
                            </div>
                        </td>
                        <td>{{ $refaccion->description ?: '—' }}</td>
                        <td style="text-align:center;">{{ number_format($refaccion->stock) }}</td>
                        <td class="num">${{ number_format($refaccion->price ?? 0, 2) }}</td>
                        <td>
                            <span class="badge {{ $compat ? 'badge--ok' : '' }}">
                                {{ $compat ? 'Sí' : 'No' }}
                            </span>
                        </td>
                        <td style="text-align:right; white-space:nowrap;">
                            <div class="row-menu" data-row-menu>
                                <button type="button" class="row-menu-btn" data-row-menu-toggle
                                        aria-haspopup="true" aria-expanded="false"
                                        aria-label="Acciones de {{ $nombre }}">
                                    <svg viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="5" r="1.7"/><circle cx="12" cy="12" r="1.7"/><circle cx="12" cy="19" r="1.7"/></svg>
                                </button>
                                <div class="row-menu-pop" data-row-menu-pop role="menu" hidden>
                                    <form action="{{ route('gestion.servicios.refacciones.destroy', $refaccion) }}" method="POST" onsubmit="return confirm('¿Eliminar esta refacción?')" style="margin:0;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="es-danger" role="menuitem">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                            Eliminar
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">
                            <div class="empty-state">
                                <span class="ico">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
                                </span>
                                <h3>No hay refacciones registradas</h3>
                                <p>Registra la primera y aparecerá en esta lista.</p>
                                <a href="{{ route('gestion.servicios.refacciones.create') }}" class="btn">Nueva refacción</a>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="card" id="fVacio" hidden>
        <div class="empty-state">
            <span class="ico">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            </span>
            <h3>Ninguna refacción coincide</h3>
            <p>Prueba a cambiar la búsqueda.</p>
            <button type="button" class="btn" data-limpiar-filtros>Limpiar búsqueda</button>
        </div>
    </div>

    @if ($refacciones->hasPages())
        <div style="margin-top:18px; display:flex; justify-content:flex-end;">
            {{ $refacciones->links('vendor.pagination.default') }}
        </div>
    @endif

    <p class="f-conteo" id="fConteo"></p>

    @include('partials.tabla-filtrable.script', [
        'singular' => 'refacción',
        'plural' => 'refacciones',
        'etiquetas' => [],
    ])
@endsection
