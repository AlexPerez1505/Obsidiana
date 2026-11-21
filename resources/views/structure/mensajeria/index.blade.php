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
                        <div class="msj-mas">
                            <button type="button" class="msj-icono" id="msjAdjuntar" aria-label="Adjuntar archivo" title="Adjuntar" aria-haspopup="true" aria-expanded="false">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="m21 11-8.6 8.6a5 5 0 0 1-7-7L14 4a3.3 3.3 0 0 1 4.7 4.7l-8.6 8.6a1.7 1.7 0 0 1-2.4-2.4L15.5 7"/></svg>
                            </button>
                            <div class="msj-mas-menu" id="msjAdjMenu" hidden>
                                <button type="button" data-tomar="image/jpeg,image/png,video/mp4,video/3gpp"><i class="msj-mi msj-mi--foto"></i>Foto o video</button>
                                <button type="button" data-tomar="application/pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt"><i class="msj-mi msj-mi--doc"></i>Documento</button>
                                <button type="button" data-tomar="audio/mpeg,audio/mp4,audio/aac,audio/amr,audio/ogg,.m4a,.mp3,.ogg,.aac,.amr"><i class="msj-mi msj-mi--audio"></i>Audio</button>
                                <button type="button" data-abrir-plantillas><i class="msj-mi msj-mi--plantilla"></i>Plantilla</button>
                            </div>
                        </div>
                        <input type="file" id="msjArchivo" hidden>
                        <button type="button" class="msj-icono msj-pl-btn" data-abrir-plantillas aria-label="Plantillas" title="Plantillas">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="4" y="3" width="16" height="18" rx="2"/><path d="M8 8h8M8 12h8M8 16h5"/></svg>
                        </button>
                        <textarea id="msjTexto" rows="1" placeholder="Escribe un mensaje" aria-label="Mensaje" maxlength="4096"></textarea>
                        <button type="button" class="msj-icono msj-mic" id="msjMic" aria-label="Grabar nota de voz" title="Nota de voz">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="9" y="3" width="6" height="11" rx="3"/><path d="M5 11a7 7 0 0 0 14 0M12 18v3"/></svg>
                        </button>
                        <button type="submit" class="msj-enviar" id="msjEnviar" aria-label="Enviar" hidden>
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

{{-- Plantillas --}}
<dialog class="msj-dlg msj-pl" id="msjPl" aria-labelledby="msjPlTitulo">
    <div class="msj-dlg-cab">
        <h3 id="msjPlTitulo">Enviar plantilla</h3>
        <button type="button" class="msj-icono" data-cerrar aria-label="Cerrar">✕</button>
    </div>
    <div class="msj-pl-cuerpo">
        <div class="msj-pl-lado">
            <div class="msj-buscar">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                <input type="search" id="msjPlBuscar" placeholder="Buscar plantilla" aria-label="Buscar plantilla">
            </div>
            <div class="msj-pl-filtros">
                <select id="msjPlIdioma" aria-label="Idioma"><option value="">Idioma</option></select>
                <select id="msjPlCategoria" aria-label="Categoría"><option value="">Categoría</option></select>
                <select id="msjPlEstado" aria-label="Estado"><option value="">Estado</option></select>
            </div>
            <ul class="msj-pl-lista" id="msjPlLista"></ul>
        </div>
        <div class="msj-pl-detalle" id="msjPlDetalle">
            <p class="msj-pl-hint">Elige una plantilla para ver cómo se verá y llenar sus datos.</p>
        </div>
    </div>
</dialog>

{{-- Nueva conversación --}}
<dialog class="msj-dlg msj-dlg--chico" id="msjNuevaDlg" aria-labelledby="msjNuevaTitulo">
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
