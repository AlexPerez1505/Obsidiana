@extends('layouts.dashboard')

@section('title', 'Nueva entrada')
@section('page-title', 'Nueva entrada')
@section('page-sub', 'Registra el equipo que llegó, con la evidencia de cómo llegó')

@include('structure.gestion_Inventario.entrada_salida._estilos')

@section('content')
    <form method="POST" action="{{ route('inventory.movimientos.store') }}"
          enctype="multipart/form-data" id="form-entrada" novalidate>
        @csrf

        {{-- Los pasos también sirven para saltar: se puede regresar a
             cualquiera ya visto sin perder lo capturado. --}}
        <nav class="pasos" id="pasos" aria-label="Pasos del registro"></nav>

        {{-- ============================================================
             1. Qué llegó
        ============================================================ --}}
        <section class="paso" data-paso="equipo" data-titulo="Equipo" data-activo>
            <x-ui.card style="margin-bottom:18px;">
                <x-ui.section-title style="margin:0 0 6px;">¿El equipo es nuevo o usado?</x-ui.section-title>
                <p class="campo-nota" style="margin:0 0 14px;">
                    De esto depende lo que se te pida después: el usado lleva checklist de recepción.
                </p>

                <div class="opciones">
                    <label class="opcion">
                        <input type="radio" name="condicion" value="nuevo" data-condicion
                               {{ old('condicion', 'nuevo') === 'nuevo' ? 'checked' : '' }}>
                        <span class="ico">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><path d="m3.3 7 8.7 5 8.7-5M12 22V12"/></svg>
                        </span>
                        <span>
                            <span class="t">Nuevo</span>
                            <span class="d">De fábrica, sin uso. Entra directo a stock.</span>
                        </span>
                    </label>

                    <label class="opcion">
                        <input type="radio" name="condicion" value="usado" data-condicion
                               {{ old('condicion') === 'usado' ? 'checked' : '' }}>
                        <span class="ico">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
                        </span>
                        <span>
                            <span class="t">Usado</span>
                            <span class="d">Entra en revisión hasta pasar por su proceso.</span>
                        </span>
                    </label>
                </div>
            </x-ui.card>

            <x-ui.card>
                <x-ui.section-title style="margin:0 0 16px;">¿Qué llegó?</x-ui.section-title>

                <div id="modeloExistenteAviso" class="cat-aviso" style="display:none; margin-bottom:14px;"></div>

                <div class="rgrid-campos">
                    @include('structure.gestion_Inventario.productos._selects_catalogo')

                    {{-- El precio es dato de administración: quien no lo
                         puede ver tampoco lo captura, y el equipo se registra
                         igual. Un admin se lo pone después. --}}
                    @if (\App\Support\PrecioVisible::editable())
                        <x-ui.form-group label="Precio de venta" for="precio" data-campo-precio>
                            <input id="precio" type="number" name="precio" step="0.01" min="0"
                                   placeholder="0.00" value="{{ old('precio') }}">
                            <small class="campo-nota" data-precio-nota>
                                En cuánto se vende este equipo. No es lo que costó.
                            </small>
                        </x-ui.form-group>

                        {{-- Cuando el modelo ya tiene precio, no se vuelve a
                             preguntar: se muestra el que hay y solo se
                             desbloquea si de verdad se quiere cambiar. --}}
                        <div class="form-group precio-fijo" data-precio-fijo style="display:none;">
                            <label>Precio de venta</label>
                            <div class="precio-caja">
                                <span class="v" data-precio-valor></span>
                                <button type="button" class="btn btn--ghost" data-precio-cambiar>Cambiar</button>
                            </div>
                            <small class="campo-nota">Ya registrado para este modelo. Se conserva tal cual.</small>
                        </div>
                    @endif

                    <x-ui.form-group label="Cantidad que llegó *" name="cantidad" type="number" min="1" value="1" :required="true" />

                    <x-ui.form-group label="Fecha de llegada *" for="movement_date">
                        <input id="movement_date" type="date" name="movement_date"
                               value="{{ old('movement_date', now()->format('Y-m-d')) }}" required>
                    </x-ui.form-group>
                </div>

                <x-ui.form-group label="Descripción" for="descripcion">
                    <textarea id="descripcion" name="descripcion" rows="2">{{ old('descripcion') }}</textarea>
                </x-ui.form-group>

                <x-ui.form-group label="Notas de la entrada" for="notas">
                    <textarea id="notas" name="notas" rows="2"
                              placeholder="Ej. llegó en buen estado, caja abierta para inspección...">{{ old('notas') }}</textarea>
                </x-ui.form-group>
            </x-ui.card>
        </section>

        {{-- ============================================================
             2. Identificación de cada pieza
        ============================================================ --}}
        <section class="paso" data-paso="identificacion" data-titulo="Identificación">
            <x-ui.card style="margin-bottom:18px;">
                <x-ui.section-title style="margin:0 0 6px;">¿Cómo identificamos cada pieza?</x-ui.section-title>
                <p class="campo-nota" style="margin:0 0 14px;">
                    Pase lo que pase, cada pieza recibe su propia etiqueta interna con código QR.
                    Esto es solo para decidir si además capturas datos de cada una.
                </p>

                <div class="opciones">
                    <label class="opcion">
                        <input type="radio" name="modo_identificacion" value="lote" data-modo
                               {{ old('modo_identificacion', 'lote') === 'lote' ? 'checked' : '' }}>
                        <span class="ico">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/></svg>
                        </span>
                        <span>
                            <span class="t">Solo la cantidad</span>
                            <span class="d">Llegaron piezas iguales. Para accesorios y consumibles.</span>
                        </span>
                    </label>

                    <label class="opcion">
                        <input type="radio" name="modo_identificacion" value="series" data-modo
                               {{ old('modo_identificacion') === 'series' ? 'checked' : '' }}>
                        <span class="ico">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7V4h16v3"/><path d="M9 20h6"/><path d="M12 4v16"/></svg>
                        </span>
                        <span>
                            <span class="t">Con número de serie</span>
                            <span class="d">Pegas las series del fabricante, una por línea.</span>
                        </span>
                    </label>

                    <label class="opcion">
                        <input type="radio" name="modo_identificacion" value="unidades" data-modo
                               {{ old('modo_identificacion') === 'unidades' ? 'checked' : '' }}>
                        <span class="ico">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
                        </span>
                        <span>
                            <span class="t">Una por una, con foto</span>
                            <span class="d">Para equipo mayor. Serie y foto de cada pieza.</span>
                        </span>
                    </label>
                </div>
            </x-ui.card>

            {{-- Modo lote: no pide nada, solo confirma qué va a pasar --}}
            <x-ui.card data-panel="lote">
                <x-ui.section-title style="margin:0 0 8px;">Etiquetas que se van a generar</x-ui.section-title>
                <p class="campo-nota" style="margin:0;">
                    Se van a crear <b data-eco-cantidad>1</b> etiqueta(s) con su código QR, una por pieza.
                    Después las imprimes desde la ficha de la entrada y las pegas en cada producto.
                </p>
            </x-ui.card>

            {{-- Modo series --}}
            <x-ui.card data-panel="series" style="display:none;">
                <x-ui.form-group label="Números de serie (uno por línea)" for="series_texto">
                    <textarea id="series_texto" name="series_texto" rows="5"
                              placeholder="23A12345&#10;23A12346&#10;23A12347">{{ old('series_texto') }}</textarea>
                    <small class="campo-nota">
                        Deben ser tantas líneas como la cantidad de arriba. Si pones solo la primera,
                        el resto de la secuencia se completa solo (23A12345 &rarr; 23A12346, 23A12347...).
                    </small>
                </x-ui.form-group>

                {{-- Mucho equipo llega sin serial de fábrica. Aquí se le
                     arma uno propio con el catálogo que ya se eligió. --}}
                <div class="generar-series">
                    <div class="txt">
                        <b>¿No traen número de serie?</b>
                        <span data-generar-nota>Se les puede armar uno con el tipo, subtipo, marca y modelo.</span>
                    </div>
                    <button type="button" class="btn btn--ghost" data-generar-series>Generar series</button>
                </div>
            </x-ui.card>

            {{-- Modo una por una --}}
            <x-ui.card data-panel="unidades" style="display:none;">
                <x-ui.section-title style="margin:0 0 6px;">Una por una</x-ui.section-title>
                <p class="campo-nota" style="margin:0 0 12px;" data-nota-unidades></p>
                <div id="unidades-rows"></div>
                @error('unidades')<p class="err">{{ $message }}</p>@enderror
            </x-ui.card>
        </section>

        {{-- ============================================================
             3. Checklist de recepción (solo usado)
        ============================================================ --}}
        <section class="paso" data-paso="checklist" data-titulo="Checklist" data-solo-usado>
            <x-ui.card style="margin-bottom:18px;">
                <x-ui.section-title style="margin:0 0 6px;">¿En qué estado general llegó?</x-ui.section-title>
                <p class="campo-nota" style="margin:0 0 14px;">Es el resumen que se ve de un vistazo en la ficha.</p>

                <div class="opciones">
                    @foreach ($estadosGenerales as $valor => $texto)
                        @php ([$titulo, $detalle] = array_pad(explode(' · ', $texto, 2), 2, ''))
                        <label class="opcion">
                            <input type="radio" name="estado_general" value="{{ $valor }}"
                                   {{ old('estado_general') === $valor ? 'checked' : '' }}>
                            <span>
                                <span class="t">{{ $titulo }}</span>
                                <span class="d">{{ $detalle }}</span>
                            </span>
                        </label>
                    @endforeach
                </div>
                @error('estado_general')<p class="err">{{ $message }}</p>@enderror
            </x-ui.card>

            <x-ui.card>
                <x-ui.section-title style="margin:0 0 6px;">Checklist de recepción</x-ui.section-title>
                <p class="campo-nota" style="margin:0 0 6px;">
                    Marca cada punto. Lo que salga en <b>No</b> abre un espacio para anotar el detalle,
                    y es lo que después justifica mandarlo a hojalatería o mantenimiento.
                </p>
                <p class="campo-nota" style="margin:0 0 4px;">
                    <b data-chk-cuenta>0</b> de {{ collect($checklist)->sum(fn ($g) => count($g['puntos'])) }} respondidos
                    · <b data-chk-mal>0</b> con problema
                </p>

                @foreach ($checklist as $llaveGrupo => $grupo)
                    <div class="chk-grupo">
                        <h4>{{ $grupo['titulo'] }}</h4>

                        @foreach ($grupo['puntos'] as $llave => $punto)
                            {{-- data-manda dice a qué proceso va la pieza si
                                 este punto sale en "No": de ahí se propone
                                 sola la ruta de abajo. --}}
                            <div class="chk-punto" data-chk-punto @if ($punto['manda']) data-manda="{{ $punto['manda'] }}" @endif>
                                <span class="txt">{{ $punto['texto'] }}</span>

                                <span class="tri">
                                    @foreach (['si' => 'Sí', 'no' => 'No', 'na' => 'N/A'] as $r => $etiqueta)
                                        <label>
                                            <input type="radio" name="checklist[{{ $llave }}][r]" value="{{ $r }}"
                                                   {{ old("checklist.$llave.r") === $r ? 'checked' : '' }}>
                                            <span>{{ $etiqueta }}</span>
                                        </label>
                                    @endforeach
                                </span>

                                <input type="text" class="nota" name="checklist[{{ $llave }}][nota]"
                                       maxlength="300" placeholder="¿Qué tiene? (ej. rayón en la tapa derecha)"
                                       value="{{ old("checklist.$llave.nota") }}">
                            </div>
                        @endforeach
                    </div>
                @endforeach

                @error('checklist')<p class="err">{{ $message }}</p>@enderror
            </x-ui.card>

            {{-- ===================== Ruta de procesos =====================
                 No todas las piezas pasan por lo mismo. Lo que salió mal en
                 el checklist propone la ruta, y aquí se ajusta. --}}
            <x-ui.card style="margin-top:18px;">
                <x-ui.section-title style="margin:0 0 6px;">¿Por qué procesos tiene que pasar?</x-ui.section-title>
                <p class="campo-nota" style="margin:0 0 4px;">
                    Se marcan solos según lo que salió mal arriba, pero mándalo tú si sabes que hace falta.
                    Un carro puede necesitar solo hojalatería, y una torre solo mantenimiento.
                </p>
                <p class="campo-nota" style="margin:0 0 14px;">
                    <b data-ruta-resumen>Sin procesos: entra directo a stock.</b>
                </p>

                <div class="opciones">
                    @foreach (\App\Models\PiezaProceso::PROCESOS as $clave => $nombre)
                        <label class="opcion">
                            <input type="checkbox" name="procesos[]" value="{{ $clave }}" data-proceso="{{ $clave }}"
                                   @checked(in_array($clave, (array) old('procesos', []), true))>
                            <span class="ico">
                                @if ($clave === 'hojalateria')
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
                                @elseif ($clave === 'mantenimiento')
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.6 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.6a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                                @else
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18l-1.5 13a2 2 0 0 1-2 1.8H6.5a2 2 0 0 1-2-1.8z"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                @endif
                            </span>
                            <span>
                                <span class="t">{{ $nombre }}</span>
                                <span class="d" data-proceso-motivo="{{ $clave }}">No hace falta</span>
                            </span>
                        </label>
                    @endforeach
                </div>
            </x-ui.card>
        </section>

        {{-- ============================================================
             4. Evidencia
        ============================================================ --}}
        <section class="paso" data-paso="evidencia" data-titulo="Evidencia">
            <x-ui.card style="margin-bottom:18px;">
                <x-ui.section-title id="evidencias-title" style="margin:0 0 6px;">Fotos de cómo llegó *</x-ui.section-title>
                <p id="evidencias-help" class="campo-nota" style="margin:0 0 14px;">
                    Hasta 3 fotos del envío completo: la caja, la factura, el estado del equipo.
                    Arrástralas aquí, o toma la foto directo si estás en el teléfono.
                </p>

                <label class="soltar" data-soltar="fotos">
                    <span class="ico">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                    </span>
                    <span class="t">Arrastra las fotos o toca para elegir</span>
                    <span class="d" data-cuenta-fotos>Ninguna todavía · máximo 3 · JPG, PNG o GIF de hasta 5 MB</span>
                    <input type="file" id="evidencias" name="evidencias[]" accept="image/*" multiple required>
                </label>

                <div class="miniaturas" data-miniaturas="fotos"></div>
                <p id="evidencias-error" class="err" style="display:none;">Solo puedes subir hasta 3 fotos.</p>
                @error('evidencias')<p class="err">{{ $message }}</p>@enderror
            </x-ui.card>

            <x-ui.card style="margin-bottom:18px;">
                <x-ui.section-title style="margin:0 0 6px;">Video de verificación *</x-ui.section-title>
                <p class="campo-nota" style="margin:0 0 14px;">
                    Un video corto mostrando el equipo encendido y funcionando. Se sube en pedazos,
                    así que un archivo pesado no truena la carga.
                </p>

                <label class="soltar" data-soltar="video">
                    <span class="ico">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><polygon points="23 7 16 12 23 17 23 7"/><rect x="1" y="5" width="15" height="14" rx="2"/></svg>
                    </span>
                    <span class="t">Arrastra el video o toca para elegir</span>
                    <span class="d" data-cuenta-video>Ninguno todavía · MP4, MOV o WEBM de hasta 150 MB</span>
                    <input type="file" id="evidencia_video" accept="video/*">
                </label>

                <input type="hidden" name="video_path" id="video-path-input" value="{{ old('video_path') }}">

                <div id="video-progreso-wrap" style="display:none; margin-top:12px;">
                    <div class="barra-progreso"><span id="video-progreso-barra"></span></div>
                    <p id="video-progreso-texto" class="campo-nota" style="margin:6px 0 0;">Subiendo video...</p>
                </div>

                <p id="video-error" class="err" style="display:none;"></p>
                <div class="miniaturas" data-miniaturas="video"></div>
                @error('video_path')<p class="err">{{ $message }}</p>@enderror
            </x-ui.card>

            <x-ui.card>
                <x-ui.section-title style="margin:0 0 6px;">Foto del producto (catálogo)</x-ui.section-title>
                <p class="campo-nota" style="margin:0 0 14px;">
                    La foto representativa que se ve en el listado de Productos y en las cotizaciones.
                    Si el modelo ya tiene una, puedes saltarte esto.
                </p>

                <div id="imagen-actual-wrap" style="display:none; margin-bottom:14px;">
                    <img id="imagen-actual" src="" alt="Foto actual del producto"
                         style="width:100px; height:100px; object-fit:cover; border-radius:9px; border:1px solid var(--border);">
                    <p class="campo-nota" style="margin:6px 0 0;">Ya tiene foto. Sube otra solo si quieres cambiarla.</p>
                </div>

                <x-ui.form-group label="Imagen del producto" for="imagen">
                    <input type="file" id="imagen" name="imagen" accept="image/*">
                    <small class="campo-nota">JPG, PNG o GIF. Máximo 5 MB.</small>
                </x-ui.form-group>
            </x-ui.card>
        </section>

        {{-- ============================================================
             5. Firma y cierre
        ============================================================ --}}
        <section class="paso" data-paso="firma" data-titulo="Firma">
            <x-ui.card style="margin-bottom:18px;">
                <x-ui.section-title style="margin:0 0 12px;">Resumen</x-ui.section-title>
                <div class="resumen">
                    <div><span class="e">Condición</span><span class="v" data-res-condicion>Nuevo</span></div>
                    <div><span class="e">Piezas</span><span class="v" data-res-cantidad>1</span></div>
                    <div><span class="e">Entran como</span><span class="v" data-res-estado>Disponible</span></div>
                    <div><span class="e">Evidencia</span><span class="v" data-res-evidencia>0 fotos</span></div>
                </div>
            </x-ui.card>

            <x-ui.card>
                <x-ui.section-title style="margin:0 0 6px;">Firma de quien registró la entrada *</x-ui.section-title>
                <p class="campo-nota" style="margin:0 0 14px;">
                    Firma con el mouse o el dedo para confirmar quién capturó esta entrada.
                </p>

                <canvas class="signature-box" id="signature-pad"></canvas>
                <p style="margin:10px 0 0;">
                    <a href="#" id="limpiar-firma" class="link" style="font-size:13px;">Limpiar firma</a>
                </p>

                <input type="hidden" name="firma" id="firma-input">
                @error('firma')<p class="err">{{ $message }}</p>@enderror
            </x-ui.card>
        </section>

        {{-- Navegación entre pasos --}}
        <div class="paso-nav">
            <span class="cuenta" data-nav-cuenta></span>
            <a href="{{ route('inventory.movimientos.index') }}" class="btn btn--ghost">Cancelar</a>
            <button type="button" class="btn btn--ghost" data-ir="atras">Atrás</button>
            <button type="button" class="btn" data-ir="adelante">Continuar</button>
            <button type="submit" class="btn" data-enviar style="display:none;">Registrar entrada</button>
        </div>
    </form>

    {{-- Captura primero: define pintarUnidades() y el resumen que usan los pasos. --}}
    @include('structure.gestion_Inventario.entrada_salida._script_captura')
    @include('structure.gestion_Inventario.entrada_salida._script_pasos')
@endsection
