@extends('layouts.dashboard')

@section('title', 'Nueva entrada')
@section('page-title', 'Nueva entrada')
@section('page-sub', 'Registra el equipo que llegó, con la evidencia de cómo llegó')

@include('structure.gestion_Inventario.entrada_salida._estilos')

@section('content')
    <form method="POST" action="{{ route('inventory.movimientos.store') }}"
          enctype="multipart/form-data" id="form-entrada" novalidate>
        @csrf

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
                <b>Se conservó lo que ya habías llenado</b> (datos, checklist, firma
                @if (old('video_path')) y el video ya subido @endif).
                Lo único que hay que volver a adjuntar son las <b>fotos</b>: por seguridad
                el navegador no permite que el sistema las vuelva a poner solo.
            </div>
        @endif

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
             3. Una pieza, una evidencia

             Si llegaron 3 piezas son 3 juegos de evidencia, no un montón
             general: cada pieza llegó en su propio estado y así se puede
             saber después cuál venía golpeada.
        ============================================================ --}}
        <section class="paso" data-paso="piezas" data-titulo="Piezas y evidencia">
            <x-ui.card style="margin-bottom:18px;">
                <x-ui.section-title style="margin:0 0 6px;">Evidencia de cada pieza *</x-ui.section-title>
                <p class="campo-nota" style="margin:0 0 12px;">
                    Abajo hay un bloque por cada una de las <b data-eco-cantidad>1</b> pieza(s) que
                    pusiste en el paso anterior. Cada una necesita <b>al menos una foto propia</b>
                    (hasta 3) y, si quieres, su video. Cada pieza recibe además su etiqueta interna
                    con código QR, que imprimes después desde la ficha de la entrada.
                </p>

                {{-- Mucho equipo llega sin serial de fábrica. Aquí se le
                     arma uno propio con el catálogo que ya se eligió. --}}
                <div class="generar-series">
                    <div class="txt">
                        <b>¿No traen número de serie?</b>
                        <span data-generar-nota>Se les puede armar uno con el tipo, subtipo, marca y modelo.</span>
                    </div>
                    <button type="button" class="btn btn--ghost" data-generar-series>Generar series</button>
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

            <div id="piezas-rows"></div>

            <x-ui.card style="margin-top:18px;">
                <x-ui.section-title style="margin:0 0 6px;">Foto del producto (catálogo)</x-ui.section-title>
                <p class="campo-nota" style="margin:0 0 14px;">
                    Esta sí es del modelo, no de una pieza: es la foto que se ve en el listado de
                    Productos y en las cotizaciones. Si el modelo ya tiene una, puedes saltarte esto.
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

                <canvas class="signature-box" id="signature-pad"
                        @if (auth()->user()->tieneFirma())
                            data-firma-registrada="{{ auth()->user()->firmaDataUri() }}"
                        @endif></canvas>
                <p style="margin:10px 0 0; display:flex; align-items:center; gap:14px; flex-wrap:wrap;">
                    <a href="#" id="limpiar-firma" class="link" style="font-size:13px;">Limpiar firma</a>

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
    @include('structure.gestion_Inventario.entrada_salida._script_captura')
    @include('structure.gestion_Inventario.entrada_salida._script_pasos')
@endsection
