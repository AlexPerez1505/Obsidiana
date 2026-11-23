@extends('layouts.dashboard')

@section('title', 'Consulta por escaneo')
@section('page-title', 'Consulta por escaneo')
@section('page-sub', 'Escanea una etiqueta y ve qué es, dónde está, cuánto hay para surtir y su historial')

@push('head')
    <link rel="stylesheet" href="{{ asset('css/consulta-escaneo.css') }}?v={{ filemtime(public_path('css/consulta-escaneo.css')) }}">
@endpush

@section('content')
{{-- data-live="off": es una pantalla de captura; el tiempo real no la toca. --}}
<div class="cxe" data-live="off" data-cxe
     data-url-buscar="{{ route('inventory.consulta.buscar') }}"
     data-url-ficha="{{ route('inventory.consulta.ficha') }}"
     data-url-historial="{{ route('inventory.consulta.historial') }}">

    <section class="cxe-lector" aria-labelledby="cxe-lector-t">
        <div class="cxe-lector-top">
            <h2 id="cxe-lector-t" class="cxe-sr">Lector</h2>
            <span class="cxe-listo" data-cxe-listo data-tono="listo">
                <span class="cxe-listo-punto" aria-hidden="true"></span>
                <span data-cxe-listo-t>Listo para escanear</span>
            </span>

            {{-- Señales: cada una se prende o apaga por separado. --}}
            <div class="cxe-senales" role="group" aria-label="Avisos del resultado">
                <button type="button" class="cxe-senal" data-cxe-activar-sonido hidden>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M11 5 6 9H2v6h4l5 4V5z"/><path d="M15.5 8.5a5 5 0 0 1 0 7"/></svg>
                    Activar sonido
                </button>
                <button type="button" class="cxe-senal" data-cxe-sonido aria-pressed="true" title="Sonido al escanear">
                    <svg class="si" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M11 5 6 9H2v6h4l5 4V5z"/><path d="M15.5 8.5a5 5 0 0 1 0 7M19 5a10 10 0 0 1 0 14"/></svg>
                    <svg class="no" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M11 5 6 9H2v6h4l5 4V5z"/><path d="m22 9-6 6M16 9l6 6"/></svg>
                    <span>Sonido</span>
                </button>
                <button type="button" class="cxe-senal" data-cxe-vibrar aria-pressed="true" title="Vibración al escanear">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="7" y="3" width="10" height="18" rx="2"/><path d="M3 9v6M21 9v6"/></svg>
                    <span data-cxe-vibrar-t>Vibración</span>
                </button>
            </div>
        </div>

        <form class="cxe-marco" data-cxe-form data-cxe-marco autocomplete="off">
            <svg class="cxe-marco-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true">
                <path d="M3 7V5a2 2 0 0 1 2-2h2M17 3h2a2 2 0 0 1 2 2v2M21 17v2a2 2 0 0 1-2 2h-2M7 21H5a2 2 0 0 1-2-2v-2"/>
                <path d="M7 8v8M10 8v8M13 8v8M17 8v8"/>
            </svg>
            <label class="cxe-sr" for="cxe-codigo">Escanea o escribe un código</label>
            <input id="cxe-codigo" type="text" data-cxe-input inputmode="text" autocomplete="off"
                   spellcheck="false" autocapitalize="characters" enterkeyhint="search"
                   placeholder="Escanea o escribe un código" aria-describedby="cxe-pista" autofocus>
            <span class="cxe-resultado" data-cxe-resultado aria-hidden="true"></span>
            <button type="submit" class="cxe-buscar">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
                <span>Buscar</span>
            </button>
        </form>
        <p class="cxe-pista" id="cxe-pista">Etiqueta (MB-000147), número de serie de la pieza o de un componente, o referencia del producto.</p>

        {{-- Lo que pasó con la última lectura cuando la ficha está cerrada. --}}
        <div class="cxe-avisos" data-cxe-aviso-pagina aria-live="polite"></div>

        <div class="cxe-camara">
            @include('partials.lector_qr', ['id' => 'consulta-escaneo'])
        </div>
    </section>

    <section class="cxe-almacen" aria-labelledby="cxe-almacen-t">
        <div class="cxe-almacen-head">
            <div class="cxe-almacen-title">
                <span class="cxe-almacen-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 10 12 4l9 6v9H3v-9Z"/><path d="M8 19v-5h8v5M6 10h12"/></svg>
                </span>
                <div>
                    <h2 id="cxe-almacen-t">Almacén</h2>
                    <p>Productos y equipos que has escaneado recientemente.</p>
                </div>
            </div>
            <button type="button" class="cxe-btn cxe-btn--chico" data-cxe-almacen-limpiar hidden>Limpiar</button>
        </div>
        <div class="cxe-almacen-lista" data-cxe-almacen-lista>
            <p class="cxe-almacen-vacio" data-cxe-almacen-vacio>Aquí aparecerán los equipos conforme los escanees.</p>
        </div>
    </section>

    <details class="cxe-ayuda">
        <summary>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
            Cómo funciona y de dónde salen los números
        </summary>
        <div class="cxe-ayuda-c">
            <ul>
                <li>La ficha se cierra sola en 10 segundos. <b>Mantener abierto</b> la deja fija; <b>Esc</b> la cierra.</li>
                <li>Puedes seguir escaneando con la ficha abierta: se actualiza, o queda como pendiente si la dejaste fija.</li>
                <li>Solo consulta: no aparta, no mueve ni cambia existencias.</li>
            </ul>
            <ul>
                <li><b>Disponible para surtir</b>: mismo cálculo que el stock del producto (sin vender y sin proceso pendiente).</li>
                <li><b>Reservadas</b>: vendidas que todavía no se entregan.</li>
                <li><b>En proceso</b>: en revisión, hojalatería, mantenimiento o limpieza.</li>
            </ul>
        </div>
    </details>

    {{-- Para lectores de pantalla: el resultado de cada lectura. --}}
    <p class="cxe-sr" aria-live="assertive" data-cxe-anuncio></p>

    {{-- ===== Ficha: modal en escritorio, panel desde abajo en celular ===== --}}
    <dialog class="cxe-modal" data-cxe-modal aria-labelledby="cxe-titulo">
        <div class="cxe-hoja">
            <div class="cxe-agarre" aria-hidden="true"></div>

            {{-- Botones y cuenta regresiva: siempre a la vista. --}}
            <div class="cxe-acciones">
                <div class="cxe-tiempo">
                    <span class="cxe-cuenta" data-cxe-cuenta></span>
                    <span class="cxe-barra" data-cxe-barra aria-hidden="true"><span data-cxe-barra-v></span></span>
                </div>
                <button type="button" class="cxe-btn" data-cxe-mantener aria-pressed="false">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>
                    <span>Mantener abierto</span>
                </button>
                <button type="button" class="cxe-btn cxe-btn--primario" data-cxe-cerrar>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>
                    <span>Cerrar</span>
                </button>
            </div>

            <div class="cxe-avisos" data-cxe-aviso-modal aria-live="polite"></div>

            {{-- Resumen fijo: el estado actual siempre a la vista. --}}
            <div class="cxe-resumen" data-cxe-resumen></div>

            <section class="cxe-historia" aria-label="Historial de movimientos">
                <div class="cxe-historia-top">
                    <h3>Historial</h3>
                    <span class="cxe-historia-n" data-cxe-historia-n></span>
                    <button type="button" class="cxe-btn cxe-btn--chico" data-cxe-ultimo>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 5v14M5 12l7 7 7-7"/></svg>
                        <span>Ir al último movimiento</span>
                    </button>
                </div>
                <div class="cxe-linea" data-cxe-linea tabindex="0" aria-label="Línea de tiempo. Usa las flechas para desplazarte."></div>
            </section>
        </div>
    </dialog>
</div>
@endsection

@push('scripts')
    <script src="{{ asset('js/consulta-escaneo.js') }}?v={{ filemtime(public_path('js/consulta-escaneo.js')) }}"></script>
@endpush
