{{-- Menú de acciones (tres puntos). Uso: <x-erp.menu> ...items... </x-erp.menu> --}}
<div {{ $attributes->merge(['class' => 'erp-menu']) }}>
    <button type="button" class="erp-kebab" aria-label="Acciones" aria-haspopup="true">
        <x-gravityui-ellipsis-vertical />
    </button>
    <div class="erp-menu-panel">
        {{ $slot }}
    </div>
</div>
