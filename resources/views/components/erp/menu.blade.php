{{-- Menú de acciones (tres puntos). Uso: <x-erp.menu> ...items... </x-erp.menu> --}}
<div {{ $attributes->merge(['class' => 'erp-menu']) }}>
    <button type="button" class="erp-kebab" aria-label="Acciones" aria-haspopup="true">
        <svg viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="5" r="2"/><circle cx="12" cy="12" r="2"/><circle cx="12" cy="19" r="2"/></svg>
    </button>
    <div class="erp-menu-panel">
        {{ $slot }}
    </div>
</div>
