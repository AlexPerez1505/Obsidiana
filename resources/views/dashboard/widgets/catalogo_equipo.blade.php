<div class="dw">
    <div class="dw-head">
        <span class="dw-ico">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 7.5 12 3l8 4.5v9L12 21l-8-4.5z"/><path d="m4 7.5 8 4.5 8-4.5M12 12v9"/></svg>
        </span>
        <h3>{{ $titulo }}</h3>
        <a href="{{ route('configuracion.catalogos.index') }}" class="dw-link">Administrar</a>
    </div>

    <div class="dw-mini">
        <div><b>{{ $w['tipos'] ?? 0 }}</b><span>Tipos</span></div>
        <div><b>{{ $w['subtipos'] ?? 0 }}</b><span>Subtipos</span></div>
        <div><b>{{ $w['marcas'] ?? 0 }}</b><span>Marcas</span></div>
        <div><b>{{ $w['modelos'] ?? 0 }}</b><span>Modelos</span></div>
    </div>

    @if (($w['nivel'] ?? 1) >= 3)
        <div class="dw-sep">Tipos con más subtipos</div>
        @include('dashboard.widgets._tabla', ['filas' => $w['tabla'] ?? []])
    @endif
</div>
