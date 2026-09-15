@extends('layouts.dashboard')
@section('title', 'Nueva Promoción')
@section('page-title', 'Nueva Promoción')
@section('page-sub', 'Define el mensaje y el segmento de clientes que la recibirán por WhatsApp')

@section('content')
    <form method="POST" action="{{ route('commercial.promociones.store') }}" enctype="multipart/form-data">
        @csrf

        <x-ui.card style="margin-bottom:18px;">
            <div style="display:flex; align-items:center; gap:12px; margin-bottom:20px;">
                <div class="qbox-ico blue" style="width:42px; height:42px;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M8 14s1.5 2 4 2 4-2 4-2"/><line x1="9" y1="9" x2="9.01" y2="9"/><line x1="15" y1="9" x2="15.01" y2="9"/></svg>
                </div>
                <div>
                    <div style="font-weight:700; font-size:16px;">Datos de la Promoción</div>
                    <div class="muted" style="font-size:13px;">Este mensaje se enviará por WhatsApp a los clientes seleccionados.</div>
                </div>
            </div>

            <div class="qbox" style="margin-bottom:16px;">
                <label class="qlabel" for="nombre"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg> Nombre de la promoción *</label>
                <input id="nombre" name="nombre" type="text" value="{{ old('nombre') }}" placeholder="Ej. Promo Rayos X Agosto 2026" required class="qinput">
                @error('nombre') <p class="err">{{ $message }}</p> @enderror
            </div>

            <div style="margin-bottom:16px;">
                <label class="qlabel" for="mensaje"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h16M4 18h10"/></svg> Mensaje *</label>
                <textarea id="mensaje" name="mensaje" rows="4" placeholder="Texto que acompañará la imagen en WhatsApp..." required class="qinput" style="resize:vertical;">{{ old('mensaje') }}</textarea>
                @error('mensaje') <p class="err">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="qlabel" for="imagen"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg> Imagen (opcional)</label>
                <input type="file" id="imagen" name="imagen" accept="image/*" class="qinput" style="padding:8px;">
                <small class="muted" style="display:block; margin-top:6px;">Formatos: JPG, PNG. Máximo 5MB. Se sube una sola vez a WhatsApp y se reutiliza para todos los envíos.</small>
                @error('imagen') <p class="err">{{ $message }}</p> @enderror
            </div>
        </x-ui.card>

        <x-ui.card style="margin-bottom:18px;">
            <div style="display:flex; align-items:center; gap:12px; margin-bottom:20px;">
                <div class="qbox-ico green" style="width:42px; height:42px;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                </div>
                <div>
                    <div style="font-weight:700; font-size:16px;">Segmento de clientes</div>
                    <div class="muted" style="font-size:13px;">Solo se enviará a clientes que aceptaron recibir promociones. Puedes acotar además por categoría.</div>
                </div>
            </div>

            <div class="qgrid">
                <div class="qbox">
                    <label class="qlabel" for="categoria_id">Categoría de cliente</label>
                    <select id="categoria_id" name="categoria_id" class="qinput">
                        <option value="">Todas las categorías</option>
                        @foreach($categorias as $categoria)
                            <option value="{{ $categoria->id }}" @selected(old('categoria_id') == $categoria->id)>{{ $categoria->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="qbox">
                    <label class="qlabel" for="producto_id">Producto relacionado (opcional)</label>
                    <select id="producto_id" name="producto_id" class="qinput">
                        <option value="">Ninguno</option>
                        @foreach($productos as $producto)
                            <option value="{{ $producto->id }}" @selected(old('producto_id') == $producto->id)>{{ $producto->tipo_equipo }} {{ $producto->marca }} {{ $producto->modelo }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="qbox">
                    <label class="qlabel" for="paquete_id">Paquete relacionado (opcional)</label>
                    <select id="paquete_id" name="paquete_id" class="qinput">
                        <option value="">Ninguno</option>
                        @foreach($paquetes as $paquete)
                            <option value="{{ $paquete->id }}" @selected(old('paquete_id') == $paquete->id)>{{ $paquete->nombre }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </x-ui.card>

        <div style="display:flex; gap:10px;">
            <x-ui.button style="width:auto;">Guardar y revisar destinatarios</x-ui.button>
            <a href="{{ route('commercial.promociones.index') }}" class="btn btn--ghost" style="text-decoration:none;">Cancelar</a>
        </div>
    </form>

    <style>
        :root { --field-border: #c9ccd2; }
        :root[data-theme="dark"] { --field-border: var(--border); }

        .qbox-ico { border-radius:11px; display:flex; align-items:center; justify-content:center; flex:0 0 auto; }
        .qbox-ico.blue { background:var(--primary-soft); color:var(--primary); }
        .qbox-ico.green { background:var(--green-soft); color:var(--green); }

        .qlabel { display:flex; align-items:center; gap:6px; font-size:13px; font-weight:600; margin-bottom:6px; color:var(--text); }
        .qinput { width:100%; padding:11px 12px; border:1px solid var(--field-border); border-radius:9px; font-size:15px; background:var(--surface); color:var(--text); }
        .qinput:focus { outline:none; border-color:var(--primary); box-shadow:0 0 0 3px rgba(0,122,255,.15); }

        .qgrid { display:grid; grid-template-columns:repeat(3, 1fr); gap:16px; }
        .qbox { border:1px solid var(--field-border); border-radius:12px; padding:14px 16px; background:var(--surface); display:flex; flex-direction:column; }

        @media (max-width:900px) { .qgrid { grid-template-columns:repeat(2, 1fr); } }
        @media (max-width:640px) { .qgrid { grid-template-columns:1fr; } }
    </style>
@endsection
