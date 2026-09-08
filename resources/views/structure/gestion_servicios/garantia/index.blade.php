@extends('structure.commercial_management.erp')

@section('title', 'Cartas de Garantía')

@section('erp_content')
    @php
        $total = $documentos->count();
    @endphp

    <div class="content-actions">
        <a href="{{ route('gestion.servicios.garantia.agregar_carta') }}" class="erp-btn">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Agregar carta
        </a>
    </div>

    <div class="erp-stats">
        <div class="erp-stat"><span class="ic blue"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg></span><div><div class="n">{{ $total }}</div><div class="l">Cartas de garantía</div></div></div>
    </div>

    <div class="erp-card">
        <div class="erp-table-wrap">
            <table class="erp-table">
                <thead>
                    <tr><th>Folio</th><th>Tipo de equipo</th><th>Nombre</th><th>Archivos</th><th style="text-align:right;">Acciones</th></tr>
                </thead>
                <tbody>
                    @forelse ($documentos as $documento)
                        <tr>
                            <td class="erp-strong">{{ $documento->folio }}</td>
                            <td>{{ $documento->tipo_equipo }}</td>
                            <td>{{ $documento->nombre }}</td>
                            <td>{{ count($documento->archivos ?? []) }}</td>
                            <td style="text-align:right;">
                                @if (!empty($documento->archivos))
                                    <a href="{{ asset('storage/' . $documento->archivos[0]) }}" target="_blank" class="tbl-link">Ver archivo</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                <div class="empty-state">
                                    <span class="ico">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                                    </span>
                                    <h3>Aún no hay cartas de garantía</h3>
                                    <p>Agrega la primera carta y aparecerá aquí.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
