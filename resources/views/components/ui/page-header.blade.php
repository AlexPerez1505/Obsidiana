@props([
    'title',
    'count' => null,
    'back' => null,
    'subtitle' => null,
    'icon' => null,
])

{{--
    Cabecera de pagina estandar: limpia y minimalista.
    Solo flecha de regresar (si aplica), titulo, contador opcional y acciones.

    Los props 'subtitle' e 'icon' se aceptan por compatibilidad con las
    llamadas existentes, pero NO se dibujan: el sistema es minimalista.

    Uso:
        <x-ui.page-header title="Ventas" :count="$ventas->count()" :back="route('...')">
            <a href="..." class="btn">Nueva venta</a>
        </x-ui.page-header>
--}}

<div class="page-header">
    @if ($back)
        <a href="{{ $back }}" class="page-header-back" title="Regresar" aria-label="Regresar">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5"/><path d="M12 19l-7-7 7-7"/></svg>
        </a>
    @endif

    <h1 class="page-header-title">{{ $title }}</h1>

    {{-- El contador solo aparece si hay algo que contar: un "0" junto al
         titulo no aporta nada y ensucia la cabecera. --}}
    @if ($count)
        <span class="page-header-count">{{ $count }}</span>
    @endif

    @if (! $slot->isEmpty())
        <div class="page-header-actions">{{ $slot }}</div>
    @endif
</div>
