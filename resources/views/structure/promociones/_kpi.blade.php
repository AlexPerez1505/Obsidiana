{{--
    Indicador compacto. Variables: $titulo, $icono, $valor (texto), $href, $k (arreglo de Indicadores con serie/variación, opcional), $contexto.
    La gráfica y la comparación solo aparecen si hay datos reales.
--}}
@php
    $serie = $k['serie'] ?? [];
    $puntos = '';
    if (count($serie) > 1) {
        $max = max($serie) ?: 1;
        $n = count($serie) - 1;
        $puntos = collect($serie)->map(fn ($v, $i) => round($i / $n * 84, 1).','.round(26 - $v / $max * 24, 1))->implode(' ');
    }
    $var = $k['variacion'] ?? null;
@endphp
<a class="mk-kpi" href="{{ $href }}">
    <span class="t"><span class="i">@include('structure.promociones._ic', ['n' => $icono])</span>{{ $titulo }}</span>
    <span class="v mk-num">{{ $valor }}</span>
    <span class="c">
        @if ($var !== null)
            <span class="mk-delta {{ $var > 0 ? 'up' : ($var < 0 ? 'down' : 'eq') }}" data-tip-mk="Contra el periodo anterior">{{ $var > 0 ? '+' : '' }}{{ $var }} %</span>
        @endif
        {{ $contexto }}
    </span>
    @if ($puntos)
        <svg class="spark" viewBox="0 0 84 28" preserveAspectRatio="none" aria-hidden="true"><polyline points="{{ $puntos }}" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round" stroke-linecap="round"/></svg>
    @endif
</a>
