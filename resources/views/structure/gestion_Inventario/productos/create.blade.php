@extends('layouts.dashboard')
@section('title', 'Agregar Producto')
@section('page-title', 'Agregar Producto')
@section('page-sub', 'Registra un nuevo equipo en el inventario')

@section('content')
    <form method="POST" action="{{ route('inventory.productos.store') }}" enctype="multipart/form-data">
        @csrf

        <x-ui.card style="margin-bottom:18px;">
            <div style="display:flex; align-items:center; gap:12px; margin-bottom:20px;">
                <div class="qbox-ico blue" style="width:42px; height:42px;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                </div>
                <div>
                    <div style="font-weight:700; font-size:16px;">Datos del Producto</div>
                    <div class="muted" style="font-size:13px;">Información general del equipo a registrar en el inventario.</div>
                </div>
            </div>

            <div class="qgrid">
                <div class="qbox">
                    <label class="qlabel" for="tipo_equipo"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/></svg> Tipo de Equipo *</label>
                    <input id="tipo_equipo" name="tipo_equipo" type="text" value="{{ old('tipo_equipo') }}" placeholder="Ej. Endoscopio" required class="qinput">
                </div>
                <div class="qbox">
                    <label class="qlabel" for="subtipo"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h16M4 18h7"/></svg> Subtipo</label>
                    <input id="subtipo" name="subtipo" type="text" value="{{ old('subtipo') }}" placeholder="Ej. Flexible" class="qinput">
                </div>
                <div class="qbox">
                    <label class="qlabel" for="marca"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.59 13.41 12 22l-9.41-9.41A2 2 0 0 1 2 11.17V4a2 2 0 0 1 2-2h7.17a2 2 0 0 1 1.42.59l9.41 9.41a2 2 0 0 1 0 2.41Z"/><circle cx="7.5" cy="7.5" r="1.5"/></svg> Marca</label>
                    <input id="marca" name="marca" type="text" value="{{ old('marca') }}" placeholder="Ej. Olympus" class="qinput">
                </div>
                <div class="qbox">
                    <label class="qlabel" for="modelo"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M9 9h6v6H9z"/></svg> Modelo</label>
                    <input id="modelo" name="modelo" type="text" value="{{ old('modelo') }}" placeholder="Ej. GIF-H190" class="qinput">
                </div>
                <div class="qbox">
                    <label class="qlabel" for="precio"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v12M15 9.5a3 3 0 0 0-3-1.5c-1.7 0-3 1-3 2.5S10.3 13 12 13s3 1 3 2.5-1.3 2.5-3 2.5a3 3 0 0 1-3-1.5"/></svg> Precio *</label>
                    <div style="position:relative;">
                        <span class="qprefix">$</span>
                        <input id="precio" name="precio" type="number" step="0.01" min="0" value="{{ old('precio') }}" placeholder="0.00" required class="qinput" style="padding-left:26px;">
                    </div>
                </div>
                <div class="qbox">
                    <label class="qlabel" for="stock"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 8v13H3V8"/><path d="M1 3h22v5H1z"/><path d="M10 12h4"/></svg> Stock *</label>
                    <input id="stock" name="stock" type="number" min="0" value="{{ old('stock') }}" placeholder="0" required class="qinput">
                </div>
                <div class="qbox">
                    <label class="qlabel" for="proveedor"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 3h22v5H1z"/><path d="M3 8v11a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V8"/><path d="M10 12h4"/></svg> Proveedor</label>
                    <input id="proveedor" name="proveedor" type="text" value="{{ old('proveedor') }}" placeholder="Nombre del proveedor" class="qinput">
                </div>
                <div class="qbox">
                    <label class="qlabel" for="no_serie"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="7" width="18" height="10" rx="1"/><path d="M7 7V5a2 2 0 0 1 2-2h6a2 2 0 0 1 2 2v2"/></svg> No. Serie</label>
                    <input id="no_serie" name="no_serie" type="text" value="{{ old('no_serie') }}" placeholder="Número de serie" class="qinput">
                </div>
            </div>

            <div style="margin-top:16px;">
                <label class="qlabel" for="descripcion"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h16M4 18h10"/></svg> Descripción</label>
                <textarea id="descripcion" name="descripcion" rows="3" class="qinput" style="resize:vertical;">{{ old('descripcion') }}</textarea>
            </div>

            <div style="margin-top:16px;">
                <label class="qlabel" for="imagen"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg> Imagen del Producto</label>
                <input type="file" id="imagen" name="imagen" accept="image/*" class="qinput" style="padding:8px;">
                <small class="muted" style="display:block; margin-top:6px;">Formatos: JPG, PNG, GIF. Máximo 5MB.</small>
            </div>
        </x-ui.card>

        <div style="display:flex; gap:10px;">
            <x-ui.button style="width:auto;">Guardar Producto</x-ui.button>
            <a href="{{ route('inventory.productos.index') }}" class="btn btn--ghost" style="text-decoration:none;">Cancelar</a>
        </div>
    </form>

    <style>
        :root { --field-border: #c9ccd2; }
        :root[data-theme="dark"] { --field-border: var(--border); }

        .qbox-ico { border-radius:11px; display:flex; align-items:center; justify-content:center; flex:0 0 auto; }
        .qbox-ico.blue { background:var(--primary-soft); color:var(--primary); }

        .qlabel { display:flex; align-items:center; gap:6px; font-size:13px; font-weight:600; margin-bottom:6px; color:var(--text); }
        .qinput { width:100%; padding:11px 12px; border:1px solid var(--field-border); border-radius:9px; font-size:15px; background:var(--surface); color:var(--text); }
        .qinput:focus { outline:none; border-color:var(--primary); box-shadow:0 0 0 3px rgba(0,122,255,.15); }
        .qprefix { position:absolute; left:12px; top:50%; transform:translateY(-50%); color:var(--muted); font-size:14px; pointer-events:none; }

        .qgrid { display:grid; grid-template-columns:repeat(3, 1fr); gap:16px; }
        .qbox { border:1px solid var(--field-border); border-radius:12px; padding:14px 16px; background:var(--surface); display:flex; flex-direction:column; }

        @media (max-width:900px) { .qgrid { grid-template-columns:repeat(2, 1fr); } }
        @media (max-width:640px) { .qgrid { grid-template-columns:1fr; } }
    </style>
@endsection
