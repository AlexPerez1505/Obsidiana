@extends('layouts.dashboard')

@section('title', 'Nuevo Equipo')

@section('content')
    <form method="POST" action="{{ route('inventory.equipos.store') }}" enctype="multipart/form-data" style="max-width:760px;">
        @csrf

        <div class="card" style="margin-bottom:18px; display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap;">
            <div>
                <h2 class="page-title" style="margin:0;">Nuevo Equipo</h2>
                <p class="muted" style="margin:2px 0 0;">Registra un equipo o producto para el catálogo</p>
            </div>
            <div style="display:flex; align-items:center; gap:10px;">
                <a href="{{ route('inventory.equipos.index') }}" class="btn btn--ghost" style="text-decoration:none; display:inline-flex; align-items:center; gap:6px;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5"/><path d="M12 19l-7-7 7-7"/></svg>
                    Regresar
                </a>
                <x-ui.button>Guardar Equipo</x-ui.button>
            </div>
        </div>

        <x-ui.card>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                <x-ui.form-group label="Equipo / Tipo *" name="tipo" placeholder="Ej. LAPAROSCOPIA" :required="true" />
                <x-ui.form-group label="Modelo" name="modelo" placeholder="Ej. AIM 1588" />
                <x-ui.form-group label="Marca" name="marca" placeholder="Ej. STRYKER" />
                <x-ui.form-group label="Precio *" name="precio" type="text" inputmode="decimal" placeholder="0.00" :required="true" />
                <x-ui.form-group label="SKU / Clave" name="sku" placeholder="Opcional" />
                <x-ui.form-group for="imagen" label="Imagen">
                    <input id="imagen" type="file" name="imagen" accept="image/*"
                           style="width:100%; padding:9px 12px; border:1px solid var(--border); border-radius:9px; font-size:14px; background:var(--surface); color:var(--text);" />
                </x-ui.form-group>
            </div>
            <x-ui.form-group for="descripcion" label="Descripción">
                <textarea id="descripcion" name="descripcion" rows="3" placeholder="Descripción del equipo"
                          style="width:100%; padding:11px 12px; border:1px solid var(--border); border-radius:9px; font-size:15px; background:var(--surface); color:var(--text); resize:vertical;">{{ old('descripcion') }}</textarea>
            </x-ui.form-group>
            <label class="ui-switch-row" style="display:flex; align-items:center; gap:10px; margin-top:14px;">
                <input type="hidden" name="activo" value="0">
                <input type="checkbox" name="activo" value="1" checked>
                <span>Activo (disponible para cotizar)</span>
            </label>
        </x-ui.card>
    </form>
@endsection
