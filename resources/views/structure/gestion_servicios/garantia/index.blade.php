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

    <div class="erp-card" style="overflow:visible;">
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
                            <td style="text-align:right; white-space:nowrap;">
                                <div class="row-menu" data-row-menu>
                                    <button type="button" class="row-menu-btn" data-row-menu-toggle
                                            aria-haspopup="true" aria-expanded="false"
                                            aria-label="Acciones de {{ $documento->folio }}">
                                        <svg viewBox="0 0 24 24" fill="currentColor"><circle cx="5" cy="12" r="1.7"/><circle cx="12" cy="12" r="1.7"/><circle cx="19" cy="12" r="1.7"/></svg>
                                    </button>
                                    <div class="row-menu-pop" data-row-menu-pop role="menu" hidden>
                                        <button type="button" role="menuitem" data-gar-ver="{{ $documento->id }}">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                                            Ver
                                        </button>
                                        @if (!empty($documento->archivos))
                                            <a href="{{ route('gestion.servicios.garantia.descargar', [$documento, 0]) }}" role="menuitem">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                                                Descargar archivo
                                            </a>
                                        @endif
                                        <a href="{{ route('gestion.servicios.garantia.editar', $documento) }}" role="menuitem">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.1 2.1 0 0 1 3 3L12 15l-4 1 1-4z"/></svg>
                                            Editar
                                        </a>
                                        <form action="{{ route('gestion.servicios.garantia.eliminar', $documento) }}" method="POST" style="margin:0;" data-confirm="¿Eliminar esta carta de garantía y sus archivos?">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="es-danger" role="menuitem" @disabled(auth()->user()->cannot('servicios.eliminar'))>
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

    {{-- Modal Ver --}}
    @foreach ($documentos as $documento)
        <div class="gar-modal" id="gar-modal-{{ $documento->id }}" hidden>
            <div class="gar-modal-box">
                <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:16px;">
                    <h3 style="margin:0; font-size:17px;">{{ $documento->nombre }}</h3>
                    <button type="button" class="gar-close" data-gar-close>&times;</button>
                </div>
                <div class="gar-field"><span>Folio</span><strong>{{ $documento->folio }}</strong></div>
                <div class="gar-field"><span>Tipo de equipo</span><strong>{{ $documento->tipo_equipo }}</strong></div>
                <div class="gar-field" style="align-items:flex-start;">
                    <span>Archivos</span>
                    <div style="display:flex; flex-direction:column; gap:6px; text-align:right;">
                        @forelse ($documento->archivos ?? [] as $i => $archivo)
                            <a href="{{ route('gestion.servicios.garantia.descargar', [$documento, $i]) }}" style="color:var(--primary); font-size:13px;">
                                {{ basename($archivo) }}
                            </a>
                        @empty
                            <span style="color:var(--muted); font-size:13px;">Sin archivos</span>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    @endforeach

    <style>
        .gar-modal { position:fixed; inset:0; z-index:90; display:flex; align-items:center; justify-content:center; background:rgba(15,23,42,.45); padding:20px; }
        .gar-modal[hidden] { display:none; }
        .gar-modal-box { background:var(--surface); border:1px solid var(--border); border-radius:14px; padding:22px; width:100%; max-width:460px; box-shadow:0 24px 60px rgba(0,0,0,.25); }
        .gar-close { background:none; border:none; font-size:22px; color:var(--muted); cursor:pointer; line-height:1; padding:0 4px; }
        .gar-close:hover { color:var(--text); }
        .gar-field { display:flex; justify-content:space-between; gap:14px; padding:10px 0; border-bottom:1px solid var(--border); font-size:13.5px; }
        .gar-field:last-child { border-bottom:none; }
        .gar-field span { color:var(--muted); }
    </style>

    @include('partials.row-menu')

    @push('scripts')
    <script>
        document.querySelectorAll('[data-gar-ver]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var modal = document.getElementById('gar-modal-' + btn.getAttribute('data-gar-ver'));
                if (modal) modal.hidden = false;
            });
        });
        document.querySelectorAll('.gar-modal').forEach(function (modal) {
            modal.addEventListener('click', function (e) {
                if (e.target === modal || e.target.closest('[data-gar-close]')) {
                    modal.hidden = true;
                }
            });
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                document.querySelectorAll('.gar-modal').forEach(function (m) { m.hidden = true; });
            }
        });
    </script>
    @endpush
@endsection
