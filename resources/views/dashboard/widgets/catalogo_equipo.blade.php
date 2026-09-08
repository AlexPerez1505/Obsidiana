<div class="dw">
    <div class="dw-head">
        <span class="dw-ico">
            <x-gravityui-box />
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
