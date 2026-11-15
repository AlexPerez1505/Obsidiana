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

        <x-ui.card>
            <x-ui.section-title style="margin:0 0 4px;">Datos de la ficha</x-ui.section-title>
            <p class="muted" style="margin:0 0 18px; font-size:13.5px;">
                El nombre es con el que se busca al adjuntarla a una cotización.
            </p>

            <x-ui.form-group label="Nombre de la ficha *" name="titulo"
                             placeholder="Ej. Gastroscopio Olympus GIF-H190"
                             :value="$ficha->titulo" :required="true" :autofocus="true" />

            {{-- El equipo que describe, con el catálogo: obligatorio. Con el
                 modelo se liga sola al producto que coincide en el inventario. --}}
            <div class="ft-catalogo" data-ft-catalogo>
                @foreach ([
                    ['equipment_type_id', 'tipo', 'Tipo de equipo *', 'Elige el tipo'],
                    ['subtype_id', 'subtipo', 'Subtipo *', 'Elige el subtipo'],
                    ['brand_id', 'marca', 'Marca *', 'Elige la marca'],
                    ['equipment_model_id', 'modelo', 'Modelo *', 'Elige el modelo'],
                ] as [$campo, $clave, $etiqueta, $vacio])
                    <x-ui.form-group :for="$campo" :label="$etiqueta">
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
                    <x-ui.form-group for="paquete_id" label="Paquete relacionado (opcional)">
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
                <x-ui.form-group label="Notas" for="contenido">
                    <textarea id="contenido" name="contenido" rows="3"
                              placeholder="Descripción corta, opcional">{{ old('contenido', $ficha->contenido) }}</textarea>
                </x-ui.form-group>
            </div>

            <div style="margin-top:16px;">
                <x-ui.form-group for="activo" label="¿Disponible para adjuntar?">
                    <input type="hidden" name="activo" value="0">
                    <label class="ft-switch">
                        <input type="checkbox" id="activo" name="activo" value="1"
                               @checked(old('activo', $ficha->activo ?? true))>
                        <span class="slider"></span>
                    </label>
                </x-ui.form-group>
            </div>
        </x-ui.card>

        <x-ui.card style="margin-top:18px;">
            <x-ui.section-title style="margin:0 0 4px;">Archivo PDF</x-ui.section-title>
            <p class="muted" style="margin:0 0 16px; font-size:13.5px;">
                Solo PDF. El único límite lo pone el servidor ({{ ini_get('upload_max_filesize') }}).
                @if ($esEdicion) Si no subes otro, se conserva el actual. @endif
            </p>

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

        <div class="page-foot">
            <a href="{{ route('inventory.fichas.index') }}" class="btn btn--ghost">Regresar</a>
            <button type="submit" class="btn">{{ $esEdicion ? 'Guardar cambios' : 'Guardar ficha' }}</button>
        </div>
    </form>

    <style>
        /* Centrado en la pantalla, no pegado a la izquierda. */
        .ft-form { max-width:680px; margin:0 auto; }
        .ft-form select, .ft-form textarea { width:100%; padding:11px 12px; border:1px solid var(--border);
                                             border-radius:9px; background:var(--surface); color:var(--text);
                                             font-family:inherit; font-size:15px; outline:none; }
        .ft-form select:focus, .ft-form textarea:focus { border-color:var(--primary); }
        .ft-form textarea { resize:vertical; }

        .ft-catalogo { display:grid; grid-template-columns:1fr 1fr; gap:0 12px; margin-top:16px; }
        .ft-liga { margin:2px 0 0; font-size:13px; color:var(--muted); min-height:18px; }
        .ft-liga b { color:var(--text); }
        .ft-liga.ok { color:var(--green, #15803d); }
        @media (max-width:560px) { .ft-catalogo { grid-template-columns:1fr; } }

        .ft-file { position:absolute; width:1px; height:1px; opacity:0; pointer-events:none; }

        .ft-drop { display:flex; flex-direction:column; align-items:center; gap:6px;
                   padding:28px 20px; border:1.5px dashed var(--border); border-radius:12px;
                   background:var(--surface-2); cursor:pointer; text-align:center;
                   transition:border-color .15s ease, background .15s ease; }
        .ft-drop:hover, .ft-drop.is-encima { border-color:var(--primary); background:var(--primary-soft); }
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

        function pintarLiga() {
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
