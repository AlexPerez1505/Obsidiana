{{--
    Formulario compartido de congreso (alta y edición).

    Los nombres de los campos son los de la base: nombre, categoria_id,
    fecha_inicio… La versión anterior los mandaba en inglés y por eso el
    formulario nunca lograba guardar.

    Espera: $congress (puede ser uno nuevo), $categories, $accion, $metodo.
--}}

@php
    $esEdicion = $congress->exists;

    $v = fn (string $campo, $porOmision = null) => old($campo, $congress->{$campo} ?? $porOmision);

    $archivos = $esEdicion ? (array) ($congress->path_archivo ?? []) : [];
@endphp

<form method="POST" action="{{ $accion }}" enctype="multipart/form-data" class="cg-form">
    @csrf
    @if ($metodo !== 'POST')
        @method($metodo)
    @endif

    @if ($errors->any())
        <x-ui.card style="margin-bottom:18px; border-color:var(--danger); background:var(--danger-soft);">
            <b style="color:var(--danger);">Revisa lo siguiente:</b>
            <ul style="margin:8px 0 0; padding-left:18px; color:var(--danger); font-size:13px;">
                @foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach
            </ul>
        </x-ui.card>
    @endif

    <div class="cg-grid">
        {{-- ===================== Columna principal ===================== --}}
        <div class="cg-col">
            <x-ui.card>
                <div class="cg-head"><x-ui.section-title style="margin:0;">Información general</x-ui.section-title></div>

                <x-ui.form-group label="Nombre *" name="nombre" placeholder="Nombre del congreso"
                                 :value="$v('nombre')" :required="true" :autofocus="true" />

                <div style="margin-top:16px;">
                    <x-ui.form-group for="categoria_id" label="Categoría *">
                        <select id="categoria_id" name="categoria_id" required>
                            <option value="">Selecciona una categoría</option>
                            @foreach ($categories as $categoria)
                                <option value="{{ $categoria->id }}"
                                    @selected((string) $v('categoria_id') === (string) $categoria->id)>
                                    {{ $categoria->nombre }}
                                </option>
                            @endforeach
                        </select>
                        @if ($categories->isEmpty())
                            <p class="cg-hint">
                                No hay categorías todavía.
                                <a href="{{ route('configuracion.catalogos.index') }}">Da de alta una en Catálogos</a>.
                            </p>
                        @endif
                    </x-ui.form-group>
                </div>

                <div style="margin-top:16px;">
                    <x-ui.form-group label="Descripción" for="descripcion">
                        <textarea id="descripcion" name="descripcion" rows="3" maxlength="5000"
                                  placeholder="De qué trata el congreso">{{ $v('descripcion') }}</textarea>
                    </x-ui.form-group>
                </div>

                <div style="margin-top:16px;">
                    <x-ui.form-group label="Comentarios internos" for="comments">
                        <textarea id="comments" name="comments" rows="3" maxlength="5000"
                                  placeholder="Notas para el equipo">{{ $v('comments') }}</textarea>
                    </x-ui.form-group>
                </div>
            </x-ui.card>

            <x-ui.card>
                <div class="cg-head"><x-ui.section-title style="margin:0;">Documentos</x-ui.section-title></div>
                <p class="muted" style="margin:0 0 14px; font-size:13.5px;">
                    Manual del expositor, planos, kit de patrocinio… Imagen, PDF u Office.
                    @if ($esEdicion) Los que subas se agregan a los que ya tiene. @endif
                </p>

                @if ($archivos)
                    <div class="cg-archivos">
                        @foreach ($archivos as $ruta)
                            <label class="cg-archivo">
                                <span class="cg-archivo-ico">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>
                                </span>
                                <span class="cg-archivo-txt">
                                    <a href="{{ asset('storage/' . $ruta) }}" target="_blank" rel="noopener">{{ basename($ruta) }}</a>
                                    <small>{{ strtoupper(pathinfo($ruta, PATHINFO_EXTENSION)) }}</small>
                                </span>
                                <span class="cg-archivo-borrar">
                                    <input type="checkbox" name="quitar_archivos[]" value="{{ $ruta }}">
                                    Quitar
                                </span>
                            </label>
                        @endforeach
                    </div>
                @endif

                <label class="cg-drop" for="archivos" data-drop>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="m17 8-5-5-5 5"/><path d="M12 3v12"/></svg>
                    <span class="cg-drop-t" data-nombre>Elige los archivos o arrástralos aquí</span>
                    <span class="cg-drop-s">Hasta 10 archivos · {{ ini_get('upload_max_filesize') }} cada uno</span>
                </label>

                <input type="file" id="archivos" name="archivos[]" multiple class="cg-file"
                       accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx">
            </x-ui.card>
        </div>

        {{-- ===================== Columna lateral ===================== --}}
        <div class="cg-col">
            <x-ui.card>
                <div class="cg-head"><x-ui.section-title style="margin:0;">Programación</x-ui.section-title></div>

                <div class="cg-2">
                    <x-ui.form-group label="Inicio *" name="fecha_inicio" type="date"
                                     :value="$esEdicion ? optional($congress->fecha_inicio)->format('Y-m-d') : old('fecha_inicio')"
                                     :required="true" />
                    <x-ui.form-group label="Fin *" name="fecha_finalizacion" type="date"
                                     :value="$esEdicion ? optional($congress->fecha_finalizacion)->format('Y-m-d') : old('fecha_finalizacion')"
                                     :required="true" />
                    <x-ui.form-group label="Hora de montaje *" name="hora_montaje" type="time"
                                     :value="$esEdicion ? optional($congress->hora_montaje)->format('H:i') : old('hora_montaje')"
                                     :required="true" />
                    <x-ui.form-group label="Hora de desmontaje *" name="hora_desmontaje" type="time"
                                     :value="$esEdicion ? optional($congress->hora_desmontaje)->format('H:i') : old('hora_desmontaje')"
                                     :required="true" />
                </div>
            </x-ui.card>

            <x-ui.card>
                <div class="cg-head"><x-ui.section-title style="margin:0;">Accesos</x-ui.section-title></div>

                <div class="cg-sw">
                    <div>
                        <div class="cg-sw-t">Descarga de recursos</div>
                        <div class="cg-sw-s">Permitir descargar archivos del congreso</div>
                    </div>
                    <label class="cg-switch">
                        <input type="hidden" name="descarga_acceso" value="0">
                        <input type="checkbox" name="descarga_acceso" value="1"
                               data-abre="descarga" @checked($v('descarga_acceso'))>
                        <span class="slider"></span>
                    </label>
                </div>
                <div class="cg-oculto @if (! $v('descarga_acceso')) is-oculto @endif" data-campo="descarga">
                    <x-ui.form-group label="Lugar o enlace de descarga" name="descarga_texto"
                                     placeholder="Ej. portal del congreso" :value="$v('descarga_texto')" />
                </div>

                <hr class="cg-hr">

                <div class="cg-sw">
                    <div>
                        <div class="cg-sw-t">Carga de archivos</div>
                        <div class="cg-sw-s">Permitir subir archivos al congreso</div>
                    </div>
                    <label class="cg-switch">
                        <input type="hidden" name="acceso_subir" value="0">
                        <input type="checkbox" name="acceso_subir" value="1"
                               data-abre="subir" @checked($v('acceso_subir'))>
                        <span class="slider"></span>
                    </label>
                </div>
                <div class="cg-oculto @if (! $v('acceso_subir')) is-oculto @endif" data-campo="subir">
                    <x-ui.form-group label="Lugar o enlace de carga" name="subir_texto"
                                     placeholder="Dónde se entregan las cosas" :value="$v('subir_texto')" />
                </div>
            </x-ui.card>

            <x-ui.card>
                <div class="cg-head"><x-ui.section-title style="margin:0;">Ubicación</x-ui.section-title></div>

                <x-ui.form-group label="Dirección" name="direccion"
                                 placeholder="Ej. Hotel Hilton, Ciudad de México"
                                 :value="$v('direccion')" />

                <p class="cg-hint" data-mapa hidden>
                    <a href="#" target="_blank" rel="noopener" data-mapa-link>Ver en Google Maps</a>
                </p>
            </x-ui.card>
        </div>
    </div>

    <div class="page-foot">
        <a href="{{ route('configuracion.catalogos.index') }}" class="btn btn--ghost">Regresar</a>
        <button type="submit" class="btn">{{ $esEdicion ? 'Guardar cambios' : 'Guardar congreso' }}</button>
    </div>
</form>

<style>
    .cg-form { max-width:1080px; margin:0 auto; }
    .cg-grid { display:grid; grid-template-columns:minmax(0,1.35fr) minmax(0,1fr); gap:18px; align-items:start; }
    .cg-col { display:flex; flex-direction:column; gap:18px; min-width:0; }
    .cg-2 { display:grid; grid-template-columns:1fr 1fr; gap:16px; }

    .cg-head { padding-bottom:14px; margin-bottom:16px; border-bottom:1px solid var(--border); }

    .cg-form select, .cg-form textarea { width:100%; padding:11px 12px; border:1px solid var(--border);
                                         border-radius:9px; background:var(--surface); color:var(--text);
                                         font-family:inherit; font-size:15px; outline:none; }
    .cg-form select:focus, .cg-form textarea:focus { border-color:var(--primary); }
    .cg-form textarea { resize:vertical; }
    .cg-hint { margin:8px 0 0; color:var(--muted); font-size:12.5px; line-height:1.45; }
    .cg-hint a { color:var(--primary); }

    .cg-hr { margin:18px 0; border:0; border-top:1px solid var(--border); }

    /* Interruptores */
    .cg-sw { display:flex; align-items:center; gap:14px; }
    .cg-sw-t { font-size:14px; font-weight:600; color:var(--text); }
    .cg-sw-s { margin-top:2px; color:var(--muted); font-size:12.5px; }
    .cg-switch { position:relative; display:inline-block; width:50px; height:26px; margin-left:auto; flex:0 0 auto; }
    .cg-switch input[type="checkbox"] { opacity:0; width:0; height:0; }
    .cg-switch .slider { position:absolute; inset:0; cursor:pointer; background:#ccc; border-radius:26px; transition:.3s; }
    .cg-switch .slider:before { position:absolute; content:""; height:22px; width:22px; left:2px; bottom:2px;
                                background:#fff; border-radius:50%; transition:.3s; box-shadow:0 1px 3px rgba(0,0,0,.3); }
    .cg-switch input[type="checkbox"]:checked + .slider { background:var(--green); }
    .cg-switch input[type="checkbox"]:checked + .slider:before { transform:translateX(24px); }

    .cg-oculto { margin-top:14px; }
    .cg-oculto.is-oculto { display:none; }

    /* Archivos */
    .cg-file { position:absolute; width:1px; height:1px; opacity:0; pointer-events:none; }
    .cg-drop { display:flex; flex-direction:column; align-items:center; gap:6px; padding:26px 20px;
               border:1.5px dashed var(--border); border-radius:12px; background:var(--surface-2);
               cursor:pointer; text-align:center; transition:border-color .15s ease, background .15s ease; }
    .cg-drop:hover, .cg-drop.is-encima { border-color:var(--primary); background:var(--primary-soft); }
    .cg-drop svg { width:26px; height:26px; color:var(--muted); }
    .cg-drop:hover svg, .cg-drop.is-encima svg { color:var(--primary); }
    .cg-drop-t { font-size:14px; font-weight:600; color:var(--text); overflow-wrap:anywhere; }
    .cg-drop-s { color:var(--muted); font-size:12.5px; }

    .cg-archivos { display:flex; flex-direction:column; gap:8px; margin-bottom:14px; }
    .cg-archivo { display:flex; align-items:center; gap:12px; padding:10px 12px;
                  border:1px solid var(--border); border-radius:10px; background:var(--surface); }
    .cg-archivo-ico { display:flex; align-items:center; justify-content:center; width:34px; height:34px;
                      flex:0 0 34px; border-radius:9px; background:var(--surface-2); color:var(--muted); }
    .cg-archivo-ico svg { width:16px; height:16px; }
    .cg-archivo-txt { flex:1; min-width:0; display:flex; flex-direction:column; gap:1px; }
    .cg-archivo-txt a { font-size:13.5px; font-weight:500; color:var(--text); text-decoration:none; overflow-wrap:anywhere; }
    .cg-archivo-txt a:hover { color:var(--primary); }
    .cg-archivo-txt small { color:var(--muted); font-size:11.5px; }
    .cg-archivo-borrar { display:inline-flex; align-items:center; gap:6px; flex:0 0 auto;
                         color:var(--muted); font-size:12.5px; cursor:pointer; }
    .cg-archivo-borrar input { width:16px; height:16px; margin:0; accent-color:var(--danger); cursor:pointer; }

    @media (max-width:900px) {
        .cg-grid { grid-template-columns:1fr; }
        .cg-2 { grid-template-columns:1fr; }
    }
    @media (prefers-reduced-motion:reduce) {
        .cg-drop, .cg-switch .slider, .cg-switch .slider:before { transition:none; }
    }
</style>

<script>
(function () {
    // Cada interruptor abre o cierra su campo de texto.
    document.querySelectorAll('[data-abre]').forEach(function (sw) {
        var destino = document.querySelector('[data-campo="' + sw.dataset.abre + '"]');
        if (!destino) return;

        sw.addEventListener('change', function () {
            destino.classList.toggle('is-oculto', !sw.checked);
        });
    });

    // Archivos: clic o arrastrar
    var input = document.getElementById('archivos');
    var zona = document.querySelector('[data-drop]');
    var nombre = zona.querySelector('[data-nombre]');

    function mostrar(archivos) {
        if (!archivos || !archivos.length) return;
        nombre.textContent = archivos.length === 1
            ? archivos[0].name
            : archivos.length + ' archivos elegidos';
    }

    input.addEventListener('change', function () { mostrar(input.files); });

    ['dragenter', 'dragover'].forEach(function (evt) {
        zona.addEventListener(evt, function (e) { e.preventDefault(); zona.classList.add('is-encima'); });
    });

    ['dragleave', 'drop'].forEach(function (evt) {
        zona.addEventListener(evt, function (e) { e.preventDefault(); zona.classList.remove('is-encima'); });
    });

    zona.addEventListener('drop', function (e) {
        if (!e.dataTransfer.files || !e.dataTransfer.files.length) return;
        input.files = e.dataTransfer.files;
        mostrar(input.files);
    });

    // Enlace a Google Maps con lo que se escriba en dirección.
    var direccion = document.getElementById('direccion');
    var aviso = document.querySelector('[data-mapa]');
    var enlace = document.querySelector('[data-mapa-link]');

    function pintarMapa() {
        var texto = (direccion.value || '').trim();
        aviso.hidden = texto === '';
        if (texto) enlace.href = 'https://www.google.com/maps/search/?api=1&query=' + encodeURIComponent(texto);
    }

    if (direccion && aviso && enlace) {
        direccion.addEventListener('input', pintarMapa);
        pintarMapa();
    }
})();
</script>
