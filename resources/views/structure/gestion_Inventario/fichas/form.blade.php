@extends('layouts.dashboard')

@php $esEdicion = (bool) $ficha->exists; @endphp

@section('title', $esEdicion ? 'Editar ficha técnica' : 'Nueva ficha técnica')
@section('page-title', $esEdicion ? 'Editar ficha técnica' : 'Nueva ficha técnica')

@section('content')
    <form method="POST"
          action="{{ $esEdicion ? route('inventory.fichas.update', $ficha) : route('inventory.fichas.store') }}"
          enctype="multipart/form-data" class="ft-form">
        @csrf
        @if ($esEdicion)
            @method('PUT')
        @endif

        <div class="ft-form-layout">
            <aside class="ft-form-promo" aria-label="Ficha técnica"></aside>
            <div class="ft-form-main">
        <x-ui.card class="ft-form-card">
            <div class="ft-form-heading">
                <span class="ft-form-heading-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M8 13h6M8 17h4"/></svg>
                </span>
                <div><x-ui.section-title style="margin:0 0 2px;">Datos de la ficha</x-ui.section-title>
                    <p class="muted" style="margin:0; font-size:12.5px;">El nombre es con el que se busca al adjuntarla a una cotizaciÃ³n.</p>
                </div>
            </div>
            <x-ui.section-title style="margin:0 0 4px;">Datos de la ficha</x-ui.section-title>
            <p class="muted" style="margin:0 0 18px; font-size:13.5px;">
                El nombre es con el que se busca al adjuntarla a una cotización.
            </p>

            <x-ui.form-group class="ft-field--title" label="Nombre de la ficha *" name="titulo"
                             placeholder="Ej. Gastroscopio Olympus GIF-H190"
                             :value="$ficha->titulo" :required="true" :autofocus="true" />

            {{-- Cualquier equipo del inventario, coincida o no con el catálogo.
                 Si se elige, se liga a ese y el catálogo pasa a ser opcional. --}}
            @php $productoElegido = (string) old('producto_id', $ficha->exists ? $ficha->producto_id : ''); @endphp
            <x-ui.form-group class="ft-field--product" for="producto_id" label="Equipo del inventario">
                <select id="producto_id" name="producto_id" data-ft-producto>
                    <option value="">Ligar por el modelo del catálogo (automático)</option>
                    @foreach ($productosInventario->groupBy(fn ($p) => $p->tipo_equipo ?: 'Sin tipo') as $tipoP => $lista)
                        <optgroup label="{{ $tipoP }}">
                            @foreach ($lista as $p)
                                @php $ocupado = isset($productosConFicha[$p->id]); @endphp
                                <option value="{{ $p->id }}" @selected($productoElegido === (string) $p->id) @disabled($ocupado && $productoElegido !== (string) $p->id)>
                                    {{ $p->nombreVisible() }}{{ $p->modelo ? ' · '.$p->modelo : '' }}{{ $p->marca ? ' · '.$p->marca : '' }}{{ $p->referencia ? ' · Ref. '.$p->referencia : '' }}{{ $ocupado ? ' (ya tiene ficha)' : '' }}
                                </option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>
            </x-ui.form-group>
            @error('producto_id')<p class="ft-liga" style="color:var(--danger);">{{ $message }}</p>@enderror
            <p class="campo-nota" style="margin:-4px 0 12px;">Elige cualquier equipo aunque no coincida con el tipo, la marca o el modelo de abajo.</p>

            {{-- El equipo que describe, con el catálogo. Con el modelo se liga
                 solo al producto que coincide en el inventario (si no se eligió
                 uno arriba). --}}
            <div class="ft-catalogo" data-ft-catalogo>
                @foreach ([
                    ['equipment_type_id', 'tipo', 'Tipo de equipo *', 'Elige el tipo'],
                    ['subtype_id', 'subtipo', 'Subtipo *', 'Elige el subtipo'],
                    ['brand_id', 'marca', 'Marca *', 'Elige la marca'],
                    ['equipment_model_id', 'modelo', 'Modelo *', 'Elige el modelo'],
                ] as [$campo, $clave, $etiqueta, $vacio])
                    <x-ui.form-group class="ft-field--{{ $clave }}" :for="$campo" :label="$etiqueta">
                        <select id="{{ $campo }}" name="{{ $campo }}" data-cat="{{ $clave }}" required
                                data-valor="{{ old($campo, $ficha->{$campo}) }}">
                            <option value="">{{ $vacio }}</option>
                        </select>
                    </x-ui.form-group>
                @endforeach
            </div>
            <p class="ft-liga" data-ft-liga></p>

            @if ($paquetes->isNotEmpty())
                <div style="margin-top:16px;">
                    <x-ui.form-group class="ft-field--package" for="paquete_id" label="Paquete relacionado (opcional)">
                        <select id="paquete_id" name="paquete_id">
                            <option value="">Sin paquete</option>
                            @foreach ($paquetes as $paquete)
                                <option value="{{ $paquete->id }}" @selected((string) old('paquete_id', $ficha->paquete_id) === (string) $paquete->id)>{{ $paquete->nombre }}</option>
                            @endforeach
                        </select>
                    </x-ui.form-group>
                </div>
            @endif

            <div style="margin-top:16px;">
                <x-ui.form-group class="ft-field--notes" label="Notas" for="contenido">
                    <textarea id="contenido" name="contenido" rows="3"
                              placeholder="Descripción corta, opcional">{{ old('contenido', $ficha->contenido) }}</textarea>
                </x-ui.form-group>
            </div>

            <div style="margin-top:16px;">
                <x-ui.form-group class="ft-field--active" for="activo" label="¿Disponible para adjuntar?">
                    <input type="hidden" name="activo" value="0">
                    <label class="ft-switch">
                        <input type="checkbox" id="activo" name="activo" value="1"
                               @checked(old('activo', $ficha->activo ?? true))>
                        <span class="slider"></span>
                    </label>
                </x-ui.form-group>
            </div>
        </x-ui.card>

        <x-ui.card class="ft-file-card" style="margin-top:18px;">
            <div class="ft-file-heading">
                <span class="ft-file-heading-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M8 13h8M8 17h5"/></svg>
                </span>
                <div>
                    <x-ui.section-title style="margin:0 0 2px;">Archivo PDF</x-ui.section-title>
                    <p class="muted" style="margin:0; font-size:12.5px;">
                Solo PDF. El único límite lo pone el servidor ({{ ini_get('upload_max_filesize') }}).
                @if ($esEdicion) Si no subes otro, se conserva el actual. @endif
                    </p>
                </div>
            </div>

            @if ($esEdicion && $ficha->archivo)
                <div class="ft-actual">
                    <span class="ft-actual-ico">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>
                    </span>
                    <div style="flex:1; min-width:0;">
                        <div class="ft-actual-t">PDF cargado</div>
                        <div class="ft-actual-s">{{ basename($ficha->archivo) }}</div>
                    </div>
                    <a href="{{ asset('storage/' . $ficha->archivo) }}" target="_blank" rel="noopener" class="tbl-link">Ver</a>
                </div>
            @endif

            <label class="ft-drop" for="archivo" data-drop>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="m17 8-5-5-5 5"/><path d="M12 3v12"/></svg>
                <span class="ft-drop-t" data-nombre>
                    {{ $esEdicion && $ficha->archivo ? 'Reemplazar el PDF' : 'Elige el PDF o arrástralo aquí' }}
                </span>
                <span class="ft-drop-s">Formato PDF · sin límite de la aplicación</span>
            </label>

            <input type="file" id="archivo" name="archivo" accept="application/pdf,.pdf"
                   class="ft-file" {{ $esEdicion ? '' : 'required' }}>

            @error('archivo')<p class="err">{{ $message }}</p>@enderror
        </x-ui.card>

        <div class="page-foot ft-form-foot">
            <a href="{{ route('inventory.fichas.index') }}" class="btn btn--ghost">Regresar</a>
            <button type="submit" class="btn">{{ $esEdicion ? 'Guardar cambios' : 'Guardar ficha' }}</button>
        </div>
            </div>
        </div>
    </form>

    <style>
        /* Centrado en la pantalla, no pegado a la izquierda. */
        .ft-form { max-width:1180px; margin:0 auto; }
        .ft-form-layout { display:grid; grid-template-columns:310px minmax(0,1fr); align-items:start; gap:24px; }
        .ft-form-main { min-width:0; display:flex; flex-direction:column; }
        .ft-form-promo { min-height:730px; border:1px solid #1c6eb9; border-radius:18px; background:#071a32 url('{{ asset('images/ficha-tecnica-cover.png') }}') center/cover no-repeat; box-shadow:0 16px 34px rgba(0,0,0,.2); }
        .ft-form-card, .ft-file-card { overflow:hidden; padding:0; border:1px solid #244c7b; border-radius:14px; background:linear-gradient(180deg,#0b1b33 0%,#081426 100%); box-shadow:0 14px 30px rgba(0,0,0,.16); }
        .ft-form-layout > .ft-file-card { margin-top:18px !important; }
        .ft-form-card > .section-title, .ft-form-card > .muted { display:none; }
        .ft-form-card > *:not(.ft-form-heading), .ft-file-card > * { margin-left:22px; margin-right:22px; }
        .ft-file-heading { margin-left:0 !important; margin-right:0 !important; }
        .ft-file-card > .section-title, .ft-file-card > .muted { margin-left:22px !important; margin-right:22px !important; }
        .ft-form-heading { position:relative; display:flex; align-items:center; gap:12px; margin:0 0 16px; padding:14px 22px; border-bottom:1px solid rgba(53,103,161,.46); background:linear-gradient(110deg,#102c51 0%,#10345f 70%,#0b2c55 100%); overflow:hidden; }
        .ft-form-heading::after { content:''; position:absolute; right:-28px; top:-42px; width:150px; height:90px; border-radius:50%; background:rgba(0,126,255,.24); transform:rotate(12deg); }
        .ft-form-heading-icon { position:relative; z-index:1; display:flex; align-items:center; justify-content:center; width:43px; height:43px; flex:0 0 43px; border-radius:50%; background:#0874df; color:#fff; box-shadow:0 0 0 7px rgba(0,126,255,.14); }
        .ft-form-heading-icon svg { width:23px; height:23px; }
        .ft-form-heading > div { position:relative; z-index:1; }
        .ft-form-heading h2, .ft-form-heading h3 { color:#f5f8ff; }
        .ft-form-heading .muted { color:#b8cbe4; }
        .ft-file-heading { display:flex; align-items:center; gap:12px; margin:0 0 16px; padding:16px 22px 14px; border-bottom:1px solid rgba(53,103,161,.46); background:linear-gradient(110deg,#102c51 0%,#10345f 70%,#0b2c55 100%); }
        .ft-file-heading-icon { display:flex; align-items:center; justify-content:center; width:38px; height:38px; flex:0 0 38px; border-radius:10px; background:#123f73; color:#55b8ff; }
        .ft-file-heading-icon svg { width:21px; height:21px; }
        .ft-file-heading h2, .ft-file-heading h3 { color:#f5f8ff; }
        .ft-file-heading .muted { color:#b8cbe4; }
        .ft-form .form-group { margin-bottom:12px; }
        .ft-form label { display:block; margin-bottom:5px; color:#e7eef8; font-size:12.5px; font-weight:700; }
        .ft-form label::before { content:''; display:inline-block; width:16px; height:16px; margin-right:6px; vertical-align:-3px; background-position:center; background-repeat:no-repeat; background-size:15px 15px; }
        .ft-field--title > label::before, .ft-field--notes > label::before { background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%2380b8ef' stroke-width='1.8' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z'/%3E%3Cpath d='M14 2v6h6M8 13h8M8 17h6'/%3E%3C/svg%3E"); }
        .ft-field--product > label::before, .ft-field--package > label::before { background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%2380b8ef' stroke-width='1.8' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='m12 2 9 5-9 5-9-5 9-5Z'/%3E%3Cpath d='m3 12 9 5 9-5M3 17l9 5 9-5'/%3E%3C/svg%3E"); }
        .ft-field--tipo > label::before, .ft-field--subtipo > label::before { background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%2380b8ef' stroke-width='1.8' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='m12 2 9 5v10l-9 5-9-5V7l9-5Z'/%3E%3Cpath d='m3 7 9 5 9-5M12 12v10'/%3E%3C/svg%3E"); }
        .ft-field--marca > label::before { background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%2380b8ef' stroke-width='1.8' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='m20.6 13.4-7.2 7.2a2 2 0 0 1-2.8 0L3.4 13.4a2 2 0 0 1 0-2.8l7.2-7.2A2 2 0 0 1 12 2.8h6.2a3 3 0 0 1 3 3V12a2 2 0 0 1-.6 1.4Z'/%3E%3Ccircle cx='16.5' cy='7.5' r='1'/%3E%3C/svg%3E"); }
        .ft-field--modelo > label::before { background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%2380b8ef' stroke-width='1.8' stroke-linecap='round' stroke-linejoin='round'%3E%3Crect x='4' y='4' width='16' height='16' rx='2'/%3E%3Cpath d='M9 1v3M15 1v3M9 20v3M15 20v3M20 9h3M20 14h3M1 9h3M1 14h3M9 9h6v6H9z'/%3E%3C/svg%3E"); }
        .ft-field--active > label::before { width:12px; height:12px; border-radius:50%; background:#35df82; }
        .ft-form select, .ft-form textarea { width:100%; padding:11px 12px; border:1px solid var(--border);
                                             border-radius:9px; background:#101f38; color:#e7eef8;
                                             font-family:inherit; font-size:13.5px; outline:none; }
        .ft-form input[type="text"], .ft-form input[type="number"], .ft-form input[type="date"] { width:100%; padding:11px 12px; border:1px solid var(--border); border-radius:9px; background:#101f38; color:#e7eef8; font-family:inherit; font-size:13.5px; outline:none; }
        .ft-form select, .ft-form input, .ft-form textarea { transition:border-color .15s ease, box-shadow .15s ease, background .15s ease; }
        .ft-form select:hover, .ft-form input:hover, .ft-form textarea:hover { border-color:#4d80b7; }
        .ft-form select:focus, .ft-form textarea:focus, .ft-form input:focus { border-color:var(--primary); box-shadow:0 0 0 3px rgba(0,126,255,.16); }
        .ft-form select:disabled, .ft-form input:disabled { cursor:not-allowed; opacity:.58; background:#0c192d; border-color:#29415f; }
        .ft-form .form-group:has(select:disabled) label { color:#8ea4be; }
        .ft-form input:invalid:not(:placeholder-shown), .ft-form select:invalid:not(:focus) { border-color:#e36b76; }
        .ft-form textarea { resize:vertical; }

        .ft-catalogo { display:grid; grid-template-columns:1fr 1fr; gap:0 12px; margin-top:16px; }
        .ft-liga { margin:2px 0 0; font-size:13px; color:var(--muted); min-height:18px; }
        .ft-liga b { color:var(--text); }
        .ft-liga.ok { color:var(--green, #15803d); }
        .ft-form .campo-nota { display:block; margin:-2px 0 18px !important; color:#9bb5d3; font-size:12.5px; line-height:1.45; }
        @media (max-width:560px) { .ft-catalogo { grid-template-columns:1fr; } }

        .ft-file { position:absolute; width:1px; height:1px; opacity:0; pointer-events:none; }

        .ft-drop { display:flex; flex-direction:column; align-items:center; gap:6px; margin:0 22px 22px;
                   padding:28px 20px; border:1.5px dashed var(--border); border-radius:12px;
                   background:#101f38; cursor:pointer; text-align:center;
                   transition:border-color .15s ease, background .15s ease, box-shadow .15s ease, transform .15s ease; }
        .ft-drop:hover, .ft-drop.is-encima { border-color:var(--primary); background:var(--primary-soft); }
        .ft-drop:focus-within { border-color:var(--primary); box-shadow:0 0 0 3px rgba(0,126,255,.16); }
        .ft-drop.is-encima { transform:translateY(-1px); }
        .ft-drop svg { width:26px; height:26px; color:var(--muted); }
        .ft-drop:hover svg, .ft-drop.is-encima svg { color:var(--primary); }
        .ft-drop-t { font-size:14px; font-weight:600; color:var(--text); overflow-wrap:anywhere; }
        .ft-drop-s { color:var(--muted); font-size:12.5px; }

        .ft-actual { display:flex; align-items:center; gap:12px; margin-bottom:14px; padding:12px 14px;
                     border:1px solid var(--border); border-radius:10px; background:var(--surface); }
        .ft-actual-ico { display:flex; align-items:center; justify-content:center; width:36px; height:36px;
                         flex:0 0 36px; border-radius:9px; background:var(--danger-soft); color:var(--danger); }
        .ft-actual-ico svg { width:17px; height:17px; }
        .ft-actual-t { font-size:13.5px; font-weight:600; }
        .ft-actual-s { color:var(--muted); font-size:12.5px; overflow-wrap:anywhere; }

        .ft-switch { position:relative; display:inline-block; width:50px; height:26px; }
        .ft-switch input { opacity:0; width:0; height:0; }
        .ft-switch .slider { position:absolute; inset:0; cursor:pointer; background:#ccc;
                             border-radius:26px; transition:.3s; }
        .ft-switch .slider:before { position:absolute; content:""; height:22px; width:22px; left:2px; bottom:2px;
                                    background:#fff; border-radius:50%; transition:.3s; box-shadow:0 1px 3px rgba(0,0,0,.3); }
        .ft-switch input:checked + .slider { background:var(--green); }
        .ft-switch input:checked + .slider:before { transform:translateX(24px); }
        .ft-field--active { display:flex; align-items:center; justify-content:space-between; gap:16px; }
        .ft-field--active > label:first-child { margin:0; }
        .ft-field--active > .ft-switch { flex:0 0 auto; }
        .ft-form-foot { max-width:none; margin:18px 0 0; padding:14px 16px; border:1px solid #244c7b; border-radius:0 0 14px 14px; background:linear-gradient(180deg,#0b1b33 0%,#081426 100%); }
        .ft-form-foot .btn { min-height:42px; padding:0 20px; border-radius:10px; font-size:13.5px; font-weight:700; }
        .ft-form-foot .btn:not(.btn--ghost) { background:#087cf0; border-color:#087cf0; }
        [data-theme="light"] .ft-form-card, [data-theme="light"] .ft-file-card { background:#ffffff; border-color:#c7d7e8; box-shadow:0 14px 30px rgba(36,73,113,.12); }
        [data-theme="light"] .ft-form-foot { background:#ffffff; border-color:#c7d7e8; box-shadow:0 10px 24px rgba(36,73,113,.1); }
        [data-theme="light"] .ft-form-heading { background:linear-gradient(110deg,#153b68,#123157); }
        [data-theme="light"] .ft-form select, [data-theme="light"] .ft-form textarea,
        [data-theme="light"] .ft-form input[type="text"], [data-theme="light"] .ft-form input[type="number"], [data-theme="light"] .ft-form input[type="date"] { background:#ffffff; color:#193452; border-color:#c5d3e2; }
        [data-theme="light"] .ft-form label { color:#26476a; }
        [data-theme="light"] .ft-form .campo-nota { color:#607c9b; }
        [data-theme="light"] .ft-form .ft-liga { color:#607c9b; }
        [data-theme="light"] .ft-form .ft-liga b { color:#234b75; }
        [data-theme="light"] .ft-form .ft-liga.ok { color:#168b54; }
        [data-theme="light"] .ft-form select:disabled, [data-theme="light"] .ft-form input:disabled { background:#eef3f8; border-color:#d3deea; color:#7890aa; }
        [data-theme="light"] .ft-file-heading { background:linear-gradient(110deg,#eaf4ff,#f5f9ff); border-bottom-color:#d5e3f0; }
        [data-theme="light"] .ft-file-heading h2, [data-theme="light"] .ft-file-heading h3 { color:#24476d; }
        [data-theme="light"] .ft-file-heading .muted { color:#607c9b; }
        [data-theme="light"] .ft-drop { background:#f5f9fd; border-color:#6fa9df; }
        [data-theme="light"] .ft-drop-t { color:#24476d; }
        [data-theme="light"] .ft-drop-s { color:#607c9b; }
        [data-theme="light"] .ft-actual { background:#f5f9fd; border-color:#cbdbea; }
        @media (max-width:560px) {
            .ft-form-layout { grid-template-columns:1fr; }
            .ft-form-promo { min-height:420px; max-height:520px; }
            .ft-form-card > *:not(.ft-form-heading), .ft-file-card > * { margin-left:14px; margin-right:14px; }
            .ft-file-heading { margin-left:0 !important; margin-right:0 !important; }
            .ft-file-card > .section-title, .ft-file-card > .muted { margin-left:14px !important; margin-right:14px !important; }
            .ft-form-heading { padding-left:16px; padding-right:16px; }
            .ft-drop { margin-left:14px; margin-right:14px; }
            .ft-form-foot { flex-direction:column-reverse; }
            .ft-form-foot .btn { width:100%; justify-content:center; }
        }

        @media (prefers-reduced-motion:reduce) {
            .ft-drop, .ft-switch .slider, .ft-switch .slider:before { transition:none; }
        }
    </style>

    <script>
    /* Tipo → subtipo → marca → modelo, y a qué producto se va a ligar. */
    (function () {
        var catalogo = @json($catalogo);
        var productos = @json($productosPorModelo);
        var caja = document.querySelector('[data-ft-catalogo]');
        var liga = document.querySelector('[data-ft-liga]');
        var sel = {};
        caja.querySelectorAll('[data-cat]').forEach(function (s) { sel[s.dataset.cat] = s; });

        function llenar(select, opciones, conservar) {
            var antes = conservar ? (select.value || select.dataset.valor || '') : '';
            var vacio = select.options[0].textContent;
            select.innerHTML = '';
            select.add(new Option(vacio, ''));
            (opciones || []).slice().sort(function (a, b) { return a.name.localeCompare(b.name, 'es'); })
                .forEach(function (o) { select.add(new Option(o.name, o.id)); });
            select.value = antes && select.querySelector('option[value="' + antes + '"]') ? antes : '';
            select.disabled = !opciones || !opciones.length;
        }

        var elegido = document.querySelector('[data-ft-producto]');
        // Con un equipo elegido a mano, el catálogo es opcional.
        function catalogoObligatorio() {
            var libre = elegido && elegido.value;
            Object.keys(sel).forEach(function (k) { sel[k].required = !libre; });
        }

        function pintarLiga() {
            if (elegido && elegido.value) {
                liga.className = 'ft-liga ok';
                liga.innerHTML = '✓ Se liga al equipo <b></b> que elegiste.';
                liga.querySelector('b').textContent = elegido.options[elegido.selectedIndex].textContent.trim();
                catalogoObligatorio();
                return;
            }
            catalogoObligatorio();
            var modelo = sel.modelo.value;
            if (!modelo) { liga.textContent = ''; liga.className = 'ft-liga'; return; }
            var p = productos[modelo];
            liga.className = 'ft-liga' + (p ? ' ok' : '');
            liga.innerHTML = p
                ? '✓ Se liga al producto <b></b> del inventario.'
                : 'Ese modelo aún no está en el inventario: se ligará solo cuando se dé de alta.';
            if (p) liga.querySelector('b').textContent = p;
        }

        function subtipos(c) { llenar(sel.subtipo, catalogo.subtipos[sel.tipo.value] || [], c); marcas(c); }
        function marcas(c) { llenar(sel.marca, catalogo.marcas[sel.subtipo.value] || [], c); modelos(c); }
        function modelos(c) { llenar(sel.modelo, (catalogo.modelos || {})[sel.subtipo.value + '-' + sel.marca.value] || [], c); pintarLiga(); }

        llenar(sel.tipo, catalogo.tipos, true);
        subtipos(true);

        sel.tipo.addEventListener('change', function () { subtipos(false); });
        sel.subtipo.addEventListener('change', function () { marcas(false); });
        sel.marca.addEventListener('change', function () { modelos(false); });
        sel.modelo.addEventListener('change', pintarLiga);
        if (elegido) elegido.addEventListener('change', pintarLiga);
    })();

    (function () {
        var input = document.getElementById('archivo');
        var zona = document.querySelector('[data-drop]');
        var nombre = zona.querySelector('[data-nombre]');

        function mostrar(archivo) {
            if (!archivo) return;
            nombre.textContent = archivo.name;
        }

        input.addEventListener('change', function () { mostrar(input.files[0]); });

        // Arrastrar y soltar sobre la zona
        ['dragenter', 'dragover'].forEach(function (evt) {
            zona.addEventListener(evt, function (e) {
                e.preventDefault();
                zona.classList.add('is-encima');
            });
        });

        ['dragleave', 'drop'].forEach(function (evt) {
            zona.addEventListener(evt, function (e) {
                e.preventDefault();
                zona.classList.remove('is-encima');
            });
        });

        zona.addEventListener('drop', function (e) {
            var archivo = e.dataTransfer.files && e.dataTransfer.files[0];
            if (!archivo) return;

            if (archivo.type !== 'application/pdf') {
                window.showToast?.('El archivo debe ser un PDF.', 'warn');
                return;
            }

            // DataTransfer para que el input se quede con el archivo soltado.
            input.files = e.dataTransfer.files;
            mostrar(archivo);
        });
    })();
    </script>
@endsection
