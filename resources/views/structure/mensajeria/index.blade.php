@extends('layouts.dashboard')

@section('title', 'Mensajería')
@section('page-title', 'Mensajería')
@section('page-sub', 'Conversaciones de WhatsApp con clientes')

@push('head')
    <link rel="stylesheet" href="{{ asset('css/mensajeria.css') }}?v={{ filemtime(public_path('css/mensajeria.css')) }}">
@endpush

@section('content')
@php
    $msjConfig = [
        "rutas" => [
            "conversaciones" => route("mensajeria.conversaciones"),
            "mensajes" => route("mensajeria.mensajes", ["conversacion" => "__C__"]),
            "contacto" => route("mensajeria.contacto", ["conversacion" => "__C__"]),
            "leer" => route("mensajeria.leer", ["conversacion" => "__C__"]),
            "enviar" => route("mensajeria.enviar", ["conversacion" => "__C__"]),
            "plantilla" => route("mensajeria.plantilla", ["conversacion" => "__C__"]),
            "reintentar" => route("mensajeria.reintentar", ["mensaje" => "__M__"]),
            "reintentarMedia" => route("mensajeria.media.reintentar", ["mensaje" => "__M__"]),
            "plantillas" => route("mensajeria.plantillas"),
            "sync" => route("mensajeria.sync"),
            "nueva" => route("mensajeria.nueva"),
            "encoder" => asset("vendor/opus-recorder/encoderWorker.min.js"),
        ],
        "configurado" => $configurado,
        "bandejas" => $bandejas,
        "puedeResponder" => $puedeResponder,
        "abrir" => $abrir,
        "rev" => $rev,
        "ahora" => now()->subSeconds(5)->toIso8601String(),
        "yo" => auth()->user()->name,
     ];
@endphp
{{-- data-live="off": Mensajería lleva su propio tiempo real (mensajeria/sync). --}}
<div class="msj" id="msj" data-live="off" data-no-mayus data-no-ortografia
     data-config='@json($msjConfig)'>

    {{-- ================= Bandeja ================= --}}
    <aside class="msj-bandeja" aria-label="Conversaciones">
        <div class="msj-bandeja-cab">
            <div class="msj-bandeja-titulo">
                <h2>Chats</h2>
                @if ($puedeResponder)
                    <button type="button" class="msj-icono" id="msjNueva" title="Nueva conversación" aria-label="Nueva conversación">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                    </button>
                @endif
            </div>
            @if (count($bandejas) > 1)
                <label class="msj-bandeja-sel">
                    <span>Bandeja</span>
                    <select id="msjBandeja">
                        <option value="">Todos los números</option>
                        @foreach ($bandejas as $b)
                            <option value="{{ $b['id'] }}">{{ $b['numero'] ?? $b['id'] }}{{ $b['nombre'] ? ' · '.$b['nombre'] : '' }}</option>
                        @endforeach
                    </select>
                </label>
            @endif
            <div class="msj-buscar">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                <input type="search" id="msjBuscar" placeholder="Buscar por nombre o teléfono" autocomplete="off" aria-label="Buscar conversación">
            </div>
            <div class="msj-filtros" role="tablist" aria-label="Filtro">
                <button type="button" class="msj-filtro is-on" data-filtro="" role="tab" aria-selected="true">Todas</button>
                <button type="button" class="msj-filtro" data-filtro="sin_leer" role="tab" aria-selected="false">Sin leer <span class="msj-filtro-n" id="msjSinLeerN" hidden></span></button>
            </div>
        </div>

        @unless ($configurado)
            <div class="msj-aviso">WhatsApp no está conectado en el servidor (modo de prueba). Se ven las conversaciones guardadas, pero no se envía nada.</div>
        @endunless

        <ul class="msj-lista" id="msjLista" aria-live="polite"></ul>
        <div class="msj-lista-vacia" id="msjListaVacia" hidden>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M21 12a8 8 0 0 1-11.6 7.1L4 20l1-4.6A8 8 0 1 1 21 12Z"/></svg>
            <p>Sin conversaciones todavía.</p>
            <span>Cuando un cliente escriba a tu WhatsApp aparecerá aquí.</span>
        </div>
    </aside>

    {{-- ================= Chat ================= --}}
    <section class="msj-chat" id="msjChat" aria-label="Conversación">
        <div class="msj-chat-vacio" id="msjChatVacio">
            <div class="msj-chat-vacio-ico">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 12a8 8 0 0 1-11.6 7.1L4 20l1-4.6A8 8 0 1 1 21 12Z"/><path d="M8 11h8M8 14h5"/></svg>
            </div>
            <h3>Elige una conversación</h3>
            <p>Los mensajes de WhatsApp de tus clientes llegan aquí en tiempo real.</p>
        </div>

        <div class="msj-chat-in" id="msjChatIn" hidden>
            <header class="msj-chat-cab">
                <button type="button" class="msj-icono msj-volver" id="msjVolver" aria-label="Volver a los chats">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="m15 18-6-6 6-6"/></svg>
                </button>
                <button type="button" class="msj-chat-quien" id="msjQuien" aria-label="Ver datos del contacto">
                    <span class="msj-av" id="msjAv"></span>
                    <span class="msj-chat-nom">
                        <b id="msjNombre"></b>
                        <small id="msjSub"></small>
                    </span>
                </button>
                <span class="msj-ventana" id="msjVentana" hidden></span>
                <button type="button" class="msj-icono" id="msjInfo" aria-label="Datos del contacto" title="Datos del contacto">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="9"/><path d="M12 16v-4M12 8h.01"/></svg>
                </button>
            </header>

            <div class="msj-hilo" id="msjHilo" role="log" aria-live="polite" aria-relevant="additions">
                <div class="msj-hilo-cargando" id="msjMas" hidden>Cargando mensajes anteriores…</div>
                <div class="msj-hilo-in" id="msjHiloIn"></div>
            </div>
            <button type="button" class="msj-nuevos" id="msjNuevos" hidden>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="m6 9 6 6 6-6"/></svg>
                <span>Nuevos mensajes</span>
            </button>

            {{-- Campo de respuesta --}}
            <footer class="msj-comp" id="msjComp">
                <div class="msj-cita" id="msjCita" hidden>
                    <div><b id="msjCitaQuien"></b><span id="msjCitaTexto"></span></div>
                    <button type="button" class="msj-icono" id="msjCitaX" aria-label="Quitar respuesta">✕</button>
                </div>
                <div class="msj-adj" id="msjAdj" hidden>
                    <div class="msj-adj-vista" id="msjAdjVista"></div>
                    <div class="msj-adj-info"><b id="msjAdjNombre"></b><small id="msjAdjPeso"></small></div>
                    <button type="button" class="msj-icono" id="msjAdjX" aria-label="Quitar archivo">✕</button>
                </div>
                <div class="msj-cerrada" id="msjCerrada" hidden>
                    <p><b>La ventana de 24 h está cerrada.</b> Para escribirle a este cliente hay que mandar una plantilla aprobada.</p>
                    @if ($puedeResponder)
                        <button type="button" class="msj-btn msj-btn--primario" data-abrir-plantillas>Enviar plantilla</button>
                    @endif
                </div>
                @if ($puedeResponder)
                    <div class="msj-grabando" id="msjGrabando" hidden>
                        <button type="button" class="msj-icono msj-rojo" id="msjGrabX" aria-label="Cancelar nota de voz" title="Cancelar">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M3 6h18M8 6V4h8v2M6 6l1 14h10l1-14"/></svg>
                        </button>
                        <span class="msj-rec-punto" aria-hidden="true"></span>
                        <span class="msj-rec-t" id="msjGrabT">0:00</span>
                        <div class="msj-rec-vista" id="msjGrabVista"></div>
                        <button type="button" class="msj-btn" id="msjGrabStop">Detener</button>
                    </div>
                    <form class="msj-barra" id="msjBarra" autocomplete="off">
                        <div class="msj-pill">
                            <button type="button" class="msj-icono msj-pl-btn" data-abrir-plantillas aria-label="Plantillas" title="Plantillas">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/><path d="M14 3v6h6M8 13h8M8 17h5"/></svg>
                            </button>
                            <textarea id="msjTexto" rows="1" placeholder="Mensaje" aria-label="Mensaje" maxlength="4096"></textarea>
                            <div class="msj-mas">
                                <button type="button" class="msj-icono" id="msjAdjuntar" aria-label="Adjuntar" title="Adjuntar" aria-haspopup="true" aria-expanded="false">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21.4 11.1-9.2 9.2a6 6 0 0 1-8.5-8.5l9.2-9.2a4 4 0 0 1 5.7 5.7l-9.2 9.2a2 2 0 0 1-2.8-2.8l8.5-8.5"/></svg>
                                </button>
                                <div class="msj-mas-menu" id="msjAdjMenu" hidden role="menu">
                                    <button type="button" role="menuitem" data-tomar="image/jpeg,image/png,video/mp4,video/3gpp"><i class="msj-mi" style="--c:#2563eb"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="3"/><circle cx="9" cy="9" r="2"/><path d="m21 15-4.5-4.5L5 21"/></svg></i><span>Fotos y videos</span></button>
                                    <button type="button" role="menuitem" class="msj-solo-tel" data-tomar="image/*" data-captura="environment"><i class="msj-mi" style="--c:#db2777"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 4h-5L7.5 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3.5z"/><circle cx="12" cy="13" r="3.5"/></svg></i><span>Cámara</span></button>
                                    <button type="button" role="menuitem" data-tomar="application/pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt"><i class="msj-mi" style="--c:#7c3aed"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/><path d="M14 3v6h6"/></svg></i><span>Documento</span></button>
                                    <button type="button" role="menuitem" data-tomar="audio/mpeg,audio/mp4,audio/aac,audio/amr,audio/ogg,.m4a,.mp3,.ogg,.aac,.amr"><i class="msj-mi" style="--c:#ea580c"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 14v-2a9 9 0 0 1 18 0v2"/><path d="M21 15a2 2 0 0 1-2 2h-1v-6h1a2 2 0 0 1 2 2zM3 15a2 2 0 0 0 2 2h1v-6H5a2 2 0 0 0-2 2z"/></svg></i><span>Audio</span></button>
                                    <button type="button" role="menuitem" data-abrir-plantillas><i class="msj-mi" style="--c:#059669"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/><path d="M14 3v6h6M8 13h8M8 17h5"/></svg></i><span>Plantilla</span></button>
                                </div>
                            </div>
                            <input type="file" id="msjArchivo" hidden>
                        </div>
                        <button type="button" class="msj-redondo msj-mic" id="msjMic" aria-label="Grabar nota de voz" title="Nota de voz">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><rect x="9" y="3" width="6" height="11" rx="3"/><path d="M5 11a7 7 0 0 0 14 0M12 18v3"/></svg>
                        </button>
                        <button type="submit" class="msj-redondo msj-enviar" id="msjEnviar" aria-label="Enviar" hidden>
                            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M3.4 20.4 21 12 3.4 3.6 3.4 10l12 2-12 2z"/></svg>
                        </button>
                    </form>
                @else
                    <div class="msj-solo-ver">Tienes permiso para leer las conversaciones, no para contestar.</div>
                @endif
            </footer>
        </div>
    </section>

    {{-- ================= Panel de contacto ================= --}}
    <aside class="msj-contacto" id="msjContacto" aria-label="Datos del contacto" aria-hidden="true">
        <header>
            <h3>Datos del contacto</h3>
            <button type="button" class="msj-icono" id="msjContactoX" aria-label="Cerrar">✕</button>
        </header>
        <div class="msj-contacto-cuerpo" id="msjContactoCuerpo"></div>
    </aside>
</div>

{{-- Visor de fotos --}}
<div class="msj-visor" id="msjVisor" hidden role="dialog" aria-modal="true" aria-label="Foto">
    <button type="button" class="msj-visor-x" id="msjVisorX" aria-label="Cerrar">✕</button>
    <a class="msj-visor-bajar" id="msjVisorBajar" href="#" aria-label="Descargar">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 4v11M7 10l5 5 5-5M5 20h14"/></svg>
    </a>
    <figure><img id="msjVisorImg" alt=""><figcaption id="msjVisorPie"></figcaption></figure>
</div>

{{-- Plantillas: elegir (izquierda) → llenar con vista previa (derecha). En teléfono, dos pasos. --}}
<dialog class="msj-dlg msj-pl" id="msjPl" aria-labelledby="msjPlTitulo" data-no-mayus data-no-ortografia>
    <header class="msj-pl-cab">
        <button type="button" class="msj-icono msj-pl-atras" id="msjPlAtras" aria-label="Volver a la lista" hidden>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="m15 18-6-6 6-6"/></svg>
        </button>
        <div class="msj-pl-cab-t">
            <h3 id="msjPlTitulo">Enviar plantilla</h3>
            <small id="msjPlPara"></small>
        </div>
        <button type="button" class="msj-icono" data-cerrar aria-label="Cerrar">✕</button>
    </header>

    <div class="msj-pl-cuerpo" id="msjPlCuerpo">
        <section class="msj-pl-lado" aria-label="Plantillas">
            <div class="msj-buscar">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                <input type="search" id="msjPlBuscar" placeholder="Buscar por nombre o texto" aria-label="Buscar plantilla" autocomplete="off">
            </div>
            <div class="msj-pl-chips" id="msjPlChips" role="tablist" aria-label="Categoría"></div>
            <ul class="msj-pl-lista" id="msjPlLista"></ul>
            <label class="msj-pl-todas" id="msjPlTodasL" hidden>
                <input type="checkbox" id="msjPlTodas"> <span>Mostrar también las no aprobadas (<b id="msjPlOtrasN">0</b>)</span>
            </label>
        </section>

        <section class="msj-pl-det" id="msjPlDetalle" aria-label="Datos de la plantilla">
            <div class="msj-pl-vacio">
                <div class="msj-pl-vacio-ico">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><rect x="4" y="3" width="16" height="18" rx="2"/><path d="M8 8h8M8 12h8M8 16h5"/></svg>
                </div>
                <b>Elige una plantilla</b>
                <span>Verás cómo le llega al cliente y solo llenas lo que cambia.</span>
            </div>
        </section>
    </div>

    <footer class="msj-pl-pie" id="msjPlPie" hidden>
        <span class="msj-pl-falta" id="msjPlFalta"></span>
        <button type="button" class="msj-btn" data-cerrar>Cancelar</button>
        <button type="button" class="msj-btn msj-btn--primario" id="msjPlEnviar" disabled>
            <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M3.4 20.4 21 12 3.4 3.6 3.4 10l12 2-12 2z"/></svg>
            Enviar
        </button>
    </footer>
</dialog>

{{-- Nueva conversación --}}
<dialog class="msj-dlg msj-dlg--chico" id="msjNuevaDlg" aria-labelledby="msjNuevaTitulo" data-no-mayus data-no-ortografia>
    <form method="dialog" id="msjNuevaForm">
        <div class="msj-dlg-cab">
            <h3 id="msjNuevaTitulo">Nueva conversación</h3>
            <button type="button" class="msj-icono" data-cerrar aria-label="Cerrar">✕</button>
        </div>
        <div class="msj-dlg-in">
            <label class="msj-campo">
                <span>Número de WhatsApp</span>
                <input type="tel" id="msjNuevaTel" placeholder="10 dígitos, o con lada de país" inputmode="tel" required>
            </label>
            <p class="msj-nota">Para iniciar una conversación, WhatsApp pide mandar primero una plantilla aprobada.</p>
        </div>
        <div class="msj-dlg-pie">
            <button type="button" class="msj-btn" data-cerrar>Cancelar</button>
            <button type="submit" class="msj-btn msj-btn--primario">Continuar</button>
        </div>
    </form>
</dialog>
@endsection

@push('scripts')
    <script src="{{ asset('vendor/opus-recorder/recorder.min.js') }}"></script>
    <script src="{{ asset('js/mensajeria.js') }}?v={{ filemtime(public_path('js/mensajeria.js')) }}"></script>
@endpush
