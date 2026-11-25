@extends('layouts.dashboard')

@section('title', 'Nueva entrada')
@section('page-title', 'Nueva entrada')
@section('page-sub', 'Registra el equipo que llegó, con la evidencia de cómo llegó')

@include('structure.gestion_Inventario.entrada_salida._estilos')

@section('content')
    <form method="POST" action="{{ route('inventory.movimientos.store') }}"
          enctype="multipart/form-data" id="form-entrada" novalidate>
        @csrf

        <header class="entrada-form-hero">
            <span class="entrada-form-hero-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><path d="m3.3 7 8.7 5 8.7-5M12 22V12"/><path d="M19 16v5M16.5 18.5h5"/></svg>
            </span>
            <div>
                <h1>Nueva entrada</h1>
                <p>Registra el equipo que llegó, con la evidencia de cómo llegó</p>
            </div>
        </header>

        {{-- Los pasos también sirven para saltar: se puede ir directo al
             apartado que quieras, sin pasar por "Continuar". --}}
        <nav class="pasos" id="pasos" aria-label="Pasos del registro"></nav>

        {{--
            Cuando el servidor rechaza el registro se conserva todo lo que
            se puede: textos, selecciones, checklist, la firma y el video
            (que ya está en el servidor). Los archivos elegidos no: el
            navegador no permite volver a llenar un input de archivo, así
            que se dice claro en vez de dejar la duda.
        --}}
        @if ($errors->any())
            <div class="aviso-archivos">
                <b>Se conservó lo que ya habías llenado</b>: datos, checklist, firma, fotos y videos
                ya subidos. Revisa lo que se marca abajo y vuelve a registrar.
            </div>
        @endif

        {{-- ============================================================
             1. Qué llegó
        ============================================================ --}}
        <section class="paso" data-paso="equipo" data-titulo="Equipo" data-activo>
            <x-ui.card class="entrada-panel entrada-panel--condicion" style="margin-bottom:18px;">
                <div class="entrada-panel-head">
                    <span class="entrada-panel-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><path d="m3.3 7 8.7 5 8.7-5M12 22V12"/></svg>
                    </span>
                    <div>
                        <x-ui.section-title style="margin:0 0 2px;">¿El equipo es nuevo o usado?</x-ui.section-title>
                        <p class="campo-nota" style="margin:0;">De esto depende lo que se te pida después: el usado lleva checklist de recepción.</p>
                    </div>
                    <span class="entrada-panel-chevron" aria-hidden="true">⌄</span>
                </div>

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

            <x-ui.card class="entrada-panel entrada-panel--llego">
                <div class="entrada-panel-head">
                    <span class="entrada-panel-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 12V7l-8-4-8 4v5"/><path d="M4 12l8 4 8-4"/><path d="M12 16v5"/><path d="M4 7l8 4 8-4"/></svg>
                    </span>
                    <x-ui.section-title style="margin:0;">¿Qué llegó?</x-ui.section-title>
                    <span class="entrada-panel-chevron" aria-hidden="true">⌃</span>
                </div>

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

                    {{-- Opcionales y se pueden borrar: el botón × las deja
                         vacías (el calendario del navegador no trae cómo).
                         La caducidad solo aplica al equipo nuevo. --}}
                    <x-ui.form-group label="Fecha de fabricación" for="fecha_fabricacion">
                        <span class="fecha-limpiable">
                            <input id="fecha_fabricacion" type="date" name="fecha_fabricacion"
                                   value="{{ old('fecha_fabricacion') }}">
                            <button type="button" class="fecha-limpiar" data-limpiar-fecha="fecha_fabricacion"
                                    title="Quitar la fecha" aria-label="Quitar la fecha de fabricación">×</button>
                        </span>
                        <small class="campo-nota">Opcional.</small>
                        @error('fecha_fabricacion')<p class="err">{{ $message }}</p>@enderror
                    </x-ui.form-group>

                    <x-ui.form-group label="Fecha de caducidad" for="fecha_caducidad" data-solo-nuevo>
                        <span class="fecha-limpiable">
                            <input id="fecha_caducidad" type="date" name="fecha_caducidad"
                                   value="{{ old('fecha_caducidad') }}">
                            <button type="button" class="fecha-limpiar" data-limpiar-fecha="fecha_caducidad"
                                    title="Quitar la fecha" aria-label="Quitar la fecha de caducidad">×</button>
                        </span>
                        <small class="campo-nota">Opcional. Solo equipo nuevo.</small>
                        @error('fecha_caducidad')<p class="err">{{ $message }}</p>@enderror
                    </x-ui.form-group>

                    {{-- Opcional. Es del modelo: si ya la tiene, se completa sola. --}}
                    <x-ui.form-group label="Referencia" for="referencia">
                        <input id="referencia" type="text" name="referencia" maxlength="120"
                               value="{{ old('referencia') }}" placeholder="Ej. REF o número de parte (opcional)">
                    </x-ui.form-group>
                </div>

                <div class="entrada-description-panel">
                    <div class="entrada-panel-head entrada-panel-head--small">
                        <span class="entrada-panel-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M8 13h8M8 17h6"/></svg>
                        </span>
                        <x-ui.section-title style="margin:0;">Descripción</x-ui.section-title>
                        <span class="entrada-panel-chevron" aria-hidden="true">⌃</span>
                    </div>
                    <div class="entrada-description-grid">
                        <x-ui.form-group label="Descripción" for="descripcion">
                            <textarea id="descripcion" name="descripcion" rows="2">{{ old('descripcion') }}</textarea>
                        </x-ui.form-group>

                        <x-ui.form-group label="Notas de la entrada" for="notas">
                            <textarea id="notas" name="notas" rows="2"
                                      placeholder="Ej. llegó en buen estado, caja abierta para inspección...">{{ old('notas') }}</textarea>
                        </x-ui.form-group>
                    </div>
                </div>
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
                        {{-- Ojo: la directiva de una línea va pegada al
                             paréntesis. Con un espacio en medio, Blade la toma
                             como apertura de bloque y se traga el HTML que
                             sigue hasta el cierre del siguiente bloque. --}}
                        @php([$titulo, $detalle] = array_pad(explode(' · ', $texto, 2), 2, ''))
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
                    <b data-chk-cuenta>0</b> de <b data-chk-total>0</b> respondidos
                    · <b data-chk-mal>0</b> con problema
                </p>

                {{-- De dónde salió el checklist: hecho por la IA para este
                     equipo, o el general mientras no se elija tipo. --}}
                <div class="chk-origen" data-chk-origen>
                    <span data-chk-origen-texto>Elige el tipo, subtipo, marca y modelo en el paso de Equipo para armar el checklist de ese equipo.</span>
                    <button type="button" class="link" data-chk-regenerar hidden>Volver a armarlo</button>
                </div>

                {{-- Lo dibuja el script: cambia según el equipo elegido y se
                     le pueden sumar puntos a mano. Cada punto viaja con su
                     texto y su proceso, porque ya no es una lista fija. --}}
                <div data-chk-lista></div>

                <div class="chk-agregar">
                    <h4>Agregar un punto</h4>
                    <div class="chk-agregar-fila">
                        <input type="text" maxlength="200" data-chk-nuevo-texto
                               placeholder="Ej. El cabezal no tiene fisuras">
                        <select data-chk-nuevo-manda aria-label="Si sale en No, manda a">
                            <option value="">Si sale en No: solo se anota</option>
                            @foreach (\App\Models\PiezaProceso::PROCESOS as $clave => $nombre)
                                <option value="{{ $clave }}">Si sale en No: {{ mb_strtolower($nombre) }}</option>
                            @endforeach
                        </select>
                        <button type="button" class="btn btn--ghost" data-chk-agregar>Agregar</button>
                    </div>
                    <small class="campo-nota">Escríbelo para que "Sí" signifique que está bien. Al guardar una entrada usada con modelo seleccionado, estos puntos se recordarán para sus próximos registros, sin copiar respuestas ni notas. Sin modelo, solo se guardan en esta entrada.</small>
                </div>

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
             3. Una pieza, una evidencia

             Si llegaron 3 piezas son 3 juegos de evidencia, no un montón
             general: cada pieza llegó en su propio estado y así se puede
             saber después cuál venía golpeada.
        ============================================================ --}}
        <section class="paso" data-paso="piezas" data-titulo="Piezas y evidencia">
            <div class="entrada-paso-banner">
                <span class="entrada-paso-banner-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="13" height="14" rx="2"/><path d="m5 15 3-3 2 2 3-4 3 4M19 9v10M16 16l3 3 3-3"/><path d="M7 8h.01"/></svg>
                </span>
                <div>
                    <span class="entrada-paso-kicker">PASO 2 DE 3</span>
                    <h2>Piezas y evidencia</h2>
                    <p>Registra las piezas del equipo y sube la evidencia de su condición.</p>
                </div>
            </div>

            <x-ui.card class="entrada-paso-intro" style="margin-bottom:18px;">
                <div class="entrada-card-title">
                    <span class="entrada-card-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m12 3 8 4.5v9L12 21l-8-4.5v-9L12 3Z"/><path d="m4 7.5 8 4.5 8-4.5M12 12v9"/></svg></span>
                    <x-ui.section-title style="margin:0 0 6px;">Evidencia de cada pieza *</x-ui.section-title>
                </div>
                <p class="campo-nota" style="margin:0 0 12px;">
                    Abajo hay un bloque por cada una de las <b data-eco-cantidad>1</b> pieza(s) que
                    pusiste en el paso anterior. Puedes activar la foto compartida para todas, o subir fotos
                    individuales: son hasta 3 por pieza,
                    de cualquier formato y tamaño y, si quieres, su video. Cada pieza recibe además su etiqueta interna
                    con código QR, que imprimes después desde la ficha de la entrada.
                </p>

                {{-- Mucho equipo llega sin serial de fábrica. Aquí se le
                     arma uno propio con el catálogo que ya se eligió. --}}
                <div class="generar-series">
                    <div class="txt">
                        <b>Cada pieza lleva su propio número de serie.</b>
                        <span>Escríbelo en cada una. Si no traen, sigue el consecutivo
                            (<span data-generar-nota>con el subtipo, marca y modelo</span>).</span>
                    </div>
                    {{-- Un solo botón: llena las piezas vacías con el consecutivo
                         que comparten todos los equipos del mismo subtipo, marca
                         y modelo (aunque sean otros productos). --}}
                    <button type="button" class="btn btn--ghost" data-generar-series>
                        <svg class="entrada-inline-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14.5 6.5 17.5 3.5 20.5 6.5 17.5 9.5z"/><path d="m16 8-5.5 5.5M9.5 13.5l-3 3M6.5 16.5 4 19l1 1 2.5-2.5M11 6H5a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h9a2 2 0 0 0 2-2v-2"/></svg>
                        Seguir consecutivo
                    </button>
                </div>

                {{-- Los errores de cada pieza vienen con su índice
                     (unidades.0.evidencias): el controlador los junta porque
                     los bloques los dibuja el script y no pueden traerlos. --}}
                @if ($erroresPiezas->isNotEmpty())
                    <div class="paso-faltan" style="margin:12px 0 0;">
                        <b>Revisa esto:</b>
                        <ul>
                            @foreach ($erroresPiezas as $mensaje)<li>{{ $mensaje }}</li>@endforeach
                        </ul>
                    </div>
                @endif
            </x-ui.card>

            <x-ui.card class="entrada-paso-compartida" style="margin-bottom:18px;" data-foto-compartida-opcion>
                <label><input type="checkbox" name="foto_compartida" value="1" data-foto-compartida @checked(old('foto_compartida'))>
                    Usar las mismas fotos para todas las piezas</label>
                <p class="campo-nota">Se aplican a todo el lote, nuevo o usado. Si necesitas fotos distintas por pieza, no la actives.</p>
                <div data-foto-compartida-panel hidden>
                    <label class="soltar soltar--chico" data-foto-compartida-zona>
                        <span class="t">Fotos para todas las piezas *</span>
                        <span class="d" data-cuenta>Hasta 3 imágenes compartidas para este lote</span>
                        <input type="file" accept="image/*,.heic,.heif,.tif,.tiff,.dng,.cr2,.cr3,.nef,.arw,.orf,.rw2,.raf" multiple>
                    </label>
                    <div class="miniaturas" data-foto-compartida-minis></div>
                    @error('foto_compartida_paths')<p class="err">{{ $message }}</p>@enderror
                </div>
            </x-ui.card>

            <div id="piezas-rows"></div>

            <x-ui.card class="entrada-paso-catalogo" style="margin-top:18px;">
                <div class="entrada-card-title">
                    <span class="entrada-card-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="8.5" cy="9" r="1.5"/><path d="m4 17 5-5 3 3 2-2 6 5"/></svg></span>
                    <x-ui.section-title style="margin:0 0 6px;">Foto del producto (catálogo)</x-ui.section-title>
                </div>
                <p class="campo-nota" style="margin:0 0 14px;">
                    Esta sí es del modelo, no de una pieza: es la foto que se ve en el listado de
                    Productos y en las cotizaciones. Si el modelo ya tiene una, puedes saltarte esto.
                </p>

                <div id="imagen-actual-wrap" style="display:none; margin-bottom:14px;">
                    <img id="imagen-actual" src="" alt="Foto actual del producto"
                         style="width:100px; height:100px; object-fit:cover; border-radius:9px; border:1px solid var(--border);">
                    <p class="campo-nota" style="margin:6px 0 0;">Ya tiene foto. Sube otra solo si quieres cambiarla.</p>
                </div>

                {{-- Se sube en pedazos al elegirla: sin límite de tamaño y en
                     cualquier formato de imagen. Solo viaja su ruta. --}}
                <x-ui.form-group label="Imagen del producto" for="imagen">
                    <input type="file" id="imagen" accept="image/*,.heic,.heif,.tif,.tiff,.dng,.cr2,.cr3,.nef,.arw,.orf,.rw2,.raf" data-foto-catalogo>
                    <input type="hidden" name="imagen_path" value="{{ old('imagen_path') }}" data-foto-catalogo-path>
                    <small class="campo-nota" data-foto-catalogo-nota>
                        @if (old('imagen_path')) Foto ya subida. Elige otra solo si quieres cambiarla.
                        @else Cualquier formato de imagen (JPG, PNG, HEIC, WEBP, TIFF, RAW...) y de cualquier tamaño.
                        @endif
                    </small>
                </x-ui.form-group>
            </x-ui.card>
        </section>

        {{-- ============================================================
             5. Firma y cierre
        ============================================================ --}}
        <section class="paso" data-paso="firma" data-titulo="Firma">
            <div class="entrada-firma-banner">
                <span class="entrada-firma-banner-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m4 19 4.4-1.2L19.8 6.4a2.2 2.2 0 0 0-3.1-3.1L5.3 15.7 4 19Z"/><path d="m14.8 5.2 3.1 3.1M7.5 17.2l3.2-.8"/></svg>
                </span>
                <div>
                    <span class="entrada-firma-kicker">PASO 3 DE 3</span>
                    <h2>Firma de quien registró la entrada</h2>
                    <p>Confirma la captura de la entrada con tu firma. Puedes firmar con el mouse o el dedo.</p>
                </div>
            </div>

            <x-ui.card class="entrada-firma-resumen" style="margin-bottom:18px;">
                <x-ui.section-title style="margin:0 0 12px;">Resumen</x-ui.section-title>
                <div class="resumen">
                    <div><span class="e">Condición</span><span class="v" data-res-condicion>Nuevo</span></div>
                    <div><span class="e">Piezas</span><span class="v" data-res-cantidad>1</span></div>
                    <div><span class="e">Entran como</span><span class="v" data-res-estado>Disponible</span></div>
                    <div><span class="e">Evidencia</span><span class="v" data-res-evidencia>0 fotos</span></div>
                </div>
            </x-ui.card>

            <x-ui.card class="entrada-firma-panel">
                <x-ui.section-title style="margin:0 0 6px;">Firma de quien registró la entrada *</x-ui.section-title>
                <p class="campo-nota" style="margin:0 0 14px;">
                    Firma con el mouse o el dedo para confirmar quién capturó esta entrada.
                </p>

                <canvas class="signature-box" id="signature-pad"
                        @if (auth()->user()->tieneFirma())
                            data-firma-registrada="{{ auth()->user()->firmaDataUri() }}"
                        @endif></canvas>
                <p style="margin:10px 0 0; display:flex; align-items:center; gap:14px; flex-wrap:wrap;">
                    <button type="button" id="limpiar-firma" class="btn btn--ghost firma-accion">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18M9 6V4h6v2M7 6l1 14h8l1-14M10 10v6M14 10v6"/></svg>
                        Limpiar firma
                    </button>
                    <a href="{{ route('profile.edit') }}" class="btn btn--ghost firma-accion">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m4 19 4.4-1.2L19.8 6.4a2.2 2.2 0 0 0-3.1-3.1L5.3 15.7 4 19Z"/><path d="m14.8 5.2 3.1 3.1"/></svg>
                        Registrar mi firma
                    </a>

                    @if (auth()->user()->tieneFirma())
                        <span class="campo-nota" data-firma-aviso>Se cargó tu firma registrada.</span>
                        <a href="#" class="link" style="font-size:13px; display:none;" data-usar-firma>Usar mi firma registrada</a>
                    @else
                        <span class="campo-nota">
                            Puedes <a href="{{ route('profile.edit') }}" class="link">registrar tu firma</a>
                            para que se cargue sola la próxima vez.
                        </span>
                    @endif
                </p>

                {{-- La firma viaja como imagen en base64: conservarla evita
                     tener que volver a firmar si el servidor rechaza algo
                     más del formulario. El lienzo la re-dibuja sola. --}}
                <input type="hidden" name="firma" id="firma-input" value="{{ old('firma') }}">
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

    {{-- Captura primero: define pintarPiezas() y el resumen que usan los pasos. --}}
    @include('structure.gestion_Inventario._subir_fotos')
    @include('structure.gestion_Inventario.entrada_salida._script_captura')
    @include('structure.gestion_Inventario.entrada_salida._script_checklist')
    @include('structure.gestion_Inventario.entrada_salida._script_pasos')
@endsection
