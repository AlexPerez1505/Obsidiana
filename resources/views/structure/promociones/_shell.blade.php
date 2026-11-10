{{--
    Encabezado del módulo: título, periodo, botón Crear y pestañas.
    Variables: $tab (resumen|campanas|cupones|whatsapp).
--}}
@php
    $periodoActual = \App\Services\Promociones\Indicadores::periodoValido(request('periodo'));
    $cont = app(\App\Services\Promociones\Indicadores::class)->contadores();
    $waListo = app(\App\Services\WhatsApp\EstadoWhatsApp::class)->listoParaEnviar();
    $conPeriodo = fn ($ruta) => route($ruta, $periodoActual !== '30d' ? ['periodo' => $periodoActual] : []);
@endphp
@include('structure.promociones._ui')

<header class="mk-head">
    <h1>Marketing y promociones</h1>
    <div class="mk-head-tools">
        <label class="mk-sel" data-tip-mk="Periodo de los indicadores">
            @include('structure.promociones._ic', ['n' => 'calendario'])
            <span class="sr-only" style="position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0 0 0 0);">Periodo</span>
            <select id="mkPeriodo" aria-label="Periodo">
                @foreach (\App\Services\Promociones\Indicadores::PERIODOS as $k => $v)
                    <option value="{{ $k }}" @selected($periodoActual === $k)>{{ $v }}</option>
                @endforeach
            </select>
        </label>
        @if (auth()->user()->can('promociones.gestionar') || auth()->user()->can('cupones.gestionar'))
            <div class="mk-menu">
                <button type="button" class="mk-btn" data-mk-menu aria-haspopup="true" aria-expanded="false">
                    @include('structure.promociones._ic', ['n' => 'plus']) Crear @include('structure.promociones._ic', ['n' => 'chevron'])
                </button>
                <div class="mk-menu-panel" role="menu">
                    @can('promociones.gestionar')
                        <a class="mk-mi" role="menuitem" href="{{ route('promociones.campanas.create') }}">@include('structure.promociones._ic', ['n' => 'megafono'])<span>Campaña<small>Mensaje por WhatsApp</small></span></a>
                    @endcan
                    @can('cupones.gestionar')
                        <a class="mk-mi" role="menuitem" href="{{ route('promociones.cupones.index', ['nuevo' => 1]) }}" data-crear-cupon>@include('structure.promociones._ic', ['n' => 'tag'])<span>Cupón<small>Descuento por código</small></span></a>
                    @endcan
                </div>
            </div>
        @endif
    </div>
</header>

<nav class="mk-tabs" aria-label="Secciones de Marketing y promociones">
    <a class="mk-tab" href="{{ $conPeriodo('promociones.inicio') }}" @if($tab === 'resumen') aria-current="page" @endif>@include('structure.promociones._ic', ['n' => 'resumen']) Resumen</a>
    <a class="mk-tab" href="{{ $conPeriodo('promociones.campanas.index') }}" @if($tab === 'campanas') aria-current="page" @endif data-tip-mk="Activas o programadas">
        @include('structure.promociones._ic', ['n' => 'megafono']) Campañas @if($cont['campanas'])<span class="n">{{ $cont['campanas'] }}</span>@endif</a>
    <a class="mk-tab" href="{{ $conPeriodo('promociones.cupones.index') }}" @if($tab === 'cupones') aria-current="page" @endif data-tip-mk="Cupones activos">
        @include('structure.promociones._ic', ['n' => 'tag']) Cupones @if($cont['cupones'])<span class="n">{{ $cont['cupones'] }}</span>@endif</a>
    <a class="mk-tab" href="{{ $conPeriodo('promociones.configuracion') }}" @if($tab === 'whatsapp') aria-current="page" @endif data-tip-mk="{{ $waListo ? 'Conectado' : 'Modo de pruebas' }}">
        @include('structure.promociones._ic', ['n' => 'chat']) WhatsApp <span class="dot {{ $waListo ? 'ok' : '' }}"></span></a>
</nav>

<script>
document.getElementById('mkPeriodo')?.addEventListener('change', e => {
    const p = new URLSearchParams(location.search);
    e.target.value === '30d' ? p.delete('periodo') : p.set('periodo', e.target.value);
    p.delete('page');
    location.search = p.toString();
});
</script>
