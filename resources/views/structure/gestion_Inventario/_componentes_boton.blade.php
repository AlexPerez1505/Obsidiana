{{-- Botón que abre los componentes de una pieza. Recibe $pieza y $resumen (de PiezaComponente::resumenPara). --}}
@php
    $r = $resumen[$pieza->id] ?? ['total' => 0, 'por_llegar' => 0];
@endphp
<button type="button" class="cmp-btn" data-componentes="{{ $pieza->id }}" data-pieza="{{ $pieza->codigo ?: $pieza->no_serie }}"
        title="Lo que trae dentro: procesador, fuente, pigtail, interfaz…">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="4" width="16" height="16" rx="2"/><rect x="9" y="9" width="6" height="6"/><path d="M9 1v3M15 1v3M9 20v3M15 20v3M20 9h3M20 14h3M1 9h3M1 14h3"/></svg>
    Componentes <span class="cmp-n">{{ $r['total'] ? '· '.$r['total'] : '' }}</span>
    <span class="cmp-falta" @if (! $r['por_llegar']) hidden @endif>{{ $r['por_llegar'] ? $r['por_llegar'].' por llegar' : '' }}</span>
</button>
