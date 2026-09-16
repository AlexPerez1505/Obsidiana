@extends('layouts.dashboard')

@section('title', 'Escanear')
@section('page-title', 'Escanear')
@section('page-sub', 'Pasa la pistola por las etiquetas y se van sumando solas')

@push('head')
    <style>
        .esc-estado { display:flex; align-items:center; gap:12px; padding:16px 18px;
                      border:1px solid var(--border); border-radius:12px; background:var(--surface); }
        .esc-estado .luz { width:12px; height:12px; border-radius:50%; background:var(--green); flex:0 0 12px;
                           box-shadow:0 0 0 0 color-mix(in srgb, var(--green) 60%, transparent);
                           animation:latido 1.8s infinite; }
        .esc-estado.dormido .luz { background:var(--muted); animation:none; }
        @keyframes latido {
            0% { box-shadow:0 0 0 0 color-mix(in srgb, var(--green) 55%, transparent); }
            70% { box-shadow:0 0 0 9px transparent; }
            100% { box-shadow:0 0 0 0 transparent; }
        }
        .esc-estado .t { flex:1; min-width:0; }
        .esc-estado .t b { display:block; font-size:14.5px; }
        .esc-estado .t span { color:var(--muted); font-size:13px; }
        @media (prefers-reduced-motion: reduce) { .esc-estado .luz { animation:none; } }

        .esc-manual { display:flex; gap:8px; margin-top:14px; }
        .esc-manual input { flex:1; font-family:ui-monospace, Consolas, monospace;
                            letter-spacing:.04em; text-transform:uppercase; }

        .esc-cuentas { display:grid; grid-template-columns:repeat(auto-fit, minmax(130px, 1fr));
                       gap:1px; background:var(--border); border:1px solid var(--border);
                       border-radius:12px; overflow:hidden; margin:16px 0; }
        .esc-cuentas > div { padding:13px 15px; background:var(--surface); }
        .esc-cuentas .e { display:block; color:var(--muted); font-size:11px; font-weight:700;
                          letter-spacing:.06em; text-transform:uppercase; }
        .esc-cuentas .v { display:block; margin-top:3px; font-size:20px; font-weight:700;
                          font-variant-numeric:tabular-nums; }

        .esc-fila { display:flex; align-items:center; gap:13px; padding:12px 0;
                    border-bottom:1px solid var(--border); }
        .esc-fila:last-child { border-bottom:none; }
        .esc-fila.nueva { animation:entra .4s ease; }
        @keyframes entra { from { background:var(--primary-soft); } to { background:transparent; } }
        .esc-fila img, .esc-fila .sinfoto { width:46px; height:46px; border-radius:9px; flex:0 0 46px;
                                            object-fit:cover; border:1px solid var(--border);
                                            background:var(--surface-2); }
        .esc-fila .txt { flex:1; min-width:0; }
        .esc-fila .cod { font-family:ui-monospace, Consolas, monospace; font-size:13px;
                         font-weight:700; letter-spacing:.03em; }
        .esc-fila .eq { color:var(--muted); font-size:13px; line-height:1.35;
                        overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
        .esc-fila .veces { padding:2px 9px; border-radius:999px; background:var(--surface-2);
                           border:1px solid var(--border); font-size:12px; font-weight:700; }
        .esc-fila .quitar { width:30px; height:30px; border:0; border-radius:8px; background:transparent;
                            color:var(--muted); font-size:17px; line-height:1; cursor:pointer; flex:0 0 30px; }
        .esc-fila .quitar:hover { background:var(--danger-soft); color:var(--danger); }

        .esc-error { margin-top:12px; padding:11px 14px; border-radius:9px;
                     background:var(--danger-soft); color:var(--danger); font-size:13.5px; }

        @media (max-width:560px) {
            .esc-fila .eq { white-space:normal; }
        }
    </style>
@endpush

@section('content')
    <div class="rgrid-sidebar">
        <div>
            <x-ui.card style="margin-bottom:18px;">
                <div class="esc-estado" data-estado>
                    <span class="luz"></span>
                    <span class="t">
                        <b data-estado-titulo>Listo para escanear</b>
                        <span data-estado-nota>Dispara la pistola sobre la etiqueta. No hace falta hacer clic en ningún lado.</span>
                    </span>
                </div>

                {{-- Si no hay pistola a la mano, se teclea. --}}
                <div class="esc-manual">
                    <input type="text" data-manual placeholder="MB-000147" autocomplete="off" spellcheck="false">
                    <button type="button" class="btn btn--ghost" data-manual-ok>Agregar</button>
                </div>

                <div class="esc-error" data-error style="display:none;"></div>
            </x-ui.card>

            <x-ui.card>
                <div style="display:flex; align-items:center; gap:10px; margin-bottom:4px;">
                    <x-ui.section-title style="margin:0; flex:1;">Escaneado</x-ui.section-title>
                    <button type="button" class="btn btn--ghost" data-copiar>Copiar lista</button>
                    <button type="button" class="btn btn--ghost" data-limpiar>Vaciar</button>
                </div>

                <div class="esc-cuentas">
                    <div><span class="e">Piezas</span><span class="v" data-cuenta-piezas>0</span></div>
                    <div><span class="e">Distintas</span><span class="v" data-cuenta-distintas>0</span></div>
                    <div><span class="e">Disponibles</span><span class="v" data-cuenta-vendibles>0</span></div>
                    <div><span class="e">En proceso</span><span class="v" data-cuenta-proceso>0</span></div>
                </div>

                <div data-lista></div>

                <div class="empty-state" data-vacio>
                    <span class="ico">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M7 8v8M11 8v8M15 8v4M19 8v8"/></svg>
                    </span>
                    <h3>Nada escaneado todavía</h3>
                    <p>Lo que vayas pasando con la pistola aparece aquí.</p>
                </div>
            </x-ui.card>
        </div>

        <div style="display:flex; flex-direction:column; gap:18px;">
            <x-ui.card>
                <div style="display:flex; align-items:center; gap:10px; margin-bottom:10px;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="var(--green)" stroke-width="2" width="26" height="26"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                    <x-ui.section-title style="margin:0;">Cómo funciona</x-ui.section-title>
                </div>
                <p class="muted" style="margin:0;">
                    La pistola escribe la etiqueta como si fuera un teclado. Esta pantalla la escucha
                    sola: no tienes que pararte en ningún campo.
                </p>
                <p class="muted" style="margin:8px 0 0;">
                    Si pasas dos veces la misma pieza, no se duplica: sube su contador.
                </p>
            </x-ui.card>

            <x-ui.card>
                <x-ui.section-title style="margin:0 0 12px;">Para qué sirve</x-ui.section-title>
                <ul style="margin:0; padding-left:18px; font-size:14px; line-height:1.7;">
                    <li>Conteo físico del almacén.</li>
                    <li>Armar la lista de un préstamo sin buscar pieza por pieza.</li>
                    <li>Revisar qué trae un lote antes de moverlo.</li>
                </ul>
            </x-ui.card>
        </div>
    </div>

    @push('scripts')
        <script>
        document.addEventListener('DOMContentLoaded', function () {
            const url = @json(route('inventory.escaneo.buscar'));
            const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';

            const lista = document.querySelector('[data-lista]');
            const vacio = document.querySelector('[data-vacio]');
            const error = document.querySelector('[data-error]');
            const estado = document.querySelector('[data-estado]');
            const titulo = document.querySelector('[data-estado-titulo]');
            const nota = document.querySelector('[data-estado-nota]');
            const manual = document.querySelector('[data-manual]');

            // codigo -> { datos, veces }
            const escaneado = new Map();

            /* ==========================================================
               Escuchar la pistola

               Una pistola lectora es un teclado que escribe muy rápido y
               cierra con Enter. Se junta lo que llega y, al Enter, se
               procesa. Se ignora si el usuario está tecleando en un campo
               de verdad, para no robarle lo que escribe.
            ========================================================== */
            let buffer = '';
            let ultimaTecla = 0;

            document.addEventListener('keydown', function (e) {
                // e.target no siempre es un elemento: cuando no hay nada
                // enfocado puede ser el propio documento, que no tiene
                // matches() y tronaba justo en el caso más común, que es
                // disparar la pistola sin haber hecho clic en ningún lado.
                const enCampo = typeof e.target?.matches === 'function'
                    && e.target.matches('input, textarea, select');

                // El campo manual sí procesa su propio Enter.
                if (enCampo && e.target !== manual) return;

                const ahora = Date.now();

                // Una pausa larga significa que empezó otra lectura.
                if (ahora - ultimaTecla > 120) buffer = '';
                ultimaTecla = ahora;

                if (e.key === 'Enter') {
                    const leido = (enCampo ? e.target.value : buffer).trim();
                    buffer = '';

                    if (leido) {
                        e.preventDefault();
                        if (enCampo) e.target.value = '';
                        procesar(leido);
                    }

                    return;
                }

                if (e.key.length === 1) buffer += e.key;
            });

            document.querySelector('[data-manual-ok]').addEventListener('click', function () {
                const v = manual.value.trim();
                if (v) { manual.value = ''; procesar(v); }
            });

            /* ===================== Consultar y agregar ===================== */
            async function procesar(leido) {
                marcarEstado('buscando', 'Buscando…', leido);

                try {
                    const r = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrf,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({ codigo: leido }),
                    });

                    const data = await r.json();

                    if (!data.encontrado) {
                        marcarEstado('malo', 'No se reconoció', data.mensaje || '');
                        mostrarError(data.mensaje || 'No se reconoció esa etiqueta.');
                        return;
                    }

                    agregar(data);
                    ocultarError();
                    marcarEstado('bueno', data.codigo + ' agregado', data.equipo);
                } catch (e) {
                    marcarEstado('malo', 'No se pudo consultar', 'Revisa tu conexión.');
                    mostrarError('No se pudo consultar la etiqueta. Revisa tu conexión.');
                }
            }

            function agregar(data) {
                const previo = escaneado.get(data.codigo);

                // Pasar dos veces la misma pieza no la duplica: sube su
                // contador, que es la señal de que algo se contó de más.
                escaneado.set(data.codigo, {
                    datos: data,
                    veces: previo ? previo.veces + 1 : 1,
                });

                pintar(data.codigo);
            }

            /* ===================== Pintar ===================== */
            function pintar(resaltar) {
                lista.innerHTML = '';

                // Lo último escaneado hasta arriba: es lo que se acaba de ver.
                const filas = Array.from(escaneado.entries()).reverse();

                filas.forEach(function ([codigo, item]) {
                    const d = item.datos;
                    const fila = document.createElement('div');
                    fila.className = 'esc-fila' + (codigo === resaltar ? ' nueva' : '');

                    const foto = d.foto
                        ? `<img src="${d.foto}" alt="">`
                        : '<span class="sinfoto"></span>';

                    const detalle = [d.marca_modelo, d.no_serie ? 'Serie ' + d.no_serie : null]
                        .filter(Boolean).join(' · ');

                    fila.innerHTML = `
                        ${foto}
                        <span class="txt">
                            <span class="cod">${codigo}</span>
                            <span class="eq">${d.equipo}${detalle ? ' · ' + detalle : ''}</span>
                        </span>
                        <span class="badge ${d.vendible ? 'badge--ok' : ''}">${d.estado_label}</span>
                        ${item.veces > 1 ? `<span class="veces">×${item.veces}</span>` : ''}
                        <button type="button" class="quitar" title="Quitar" aria-label="Quitar ${codigo}">×</button>
                    `;

                    fila.querySelector('.quitar').addEventListener('click', function () {
                        escaneado.delete(codigo);
                        pintar();
                    });

                    lista.appendChild(fila);
                });

                const piezas = filas.reduce((s, [, i]) => s + i.veces, 0);
                const vendibles = filas.filter(([, i]) => i.datos.vendible).length;

                poner('piezas', piezas);
                poner('distintas', filas.length);
                poner('vendibles', vendibles);
                poner('proceso', filas.length - vendibles);

                vacio.style.display = filas.length ? 'none' : '';
            }

            function poner(cual, valor) {
                const el = document.querySelector(`[data-cuenta-${cual}]`);
                if (el) el.textContent = valor;
            }

            /* ===================== Avisos ===================== */
            function marcarEstado(tipo, texto, detalle) {
                estado.classList.toggle('dormido', tipo === 'malo');
                titulo.textContent = texto;
                nota.textContent = detalle || '';

                if (tipo === 'bueno' || tipo === 'malo') {
                    clearTimeout(marcarEstado.reloj);
                    marcarEstado.reloj = setTimeout(function () {
                        estado.classList.remove('dormido');
                        titulo.textContent = 'Listo para escanear';
                        nota.textContent = 'Dispara la pistola sobre la etiqueta. No hace falta hacer clic en ningún lado.';
                    }, 2500);
                }
            }

            function mostrarError(texto) { error.textContent = texto; error.style.display = ''; }
            function ocultarError() { error.style.display = 'none'; }

            /* ===================== Acciones de la lista ===================== */
            document.querySelector('[data-limpiar]').addEventListener('click', function () {
                if (escaneado.size && !confirm('¿Vaciar lo escaneado?')) return;
                escaneado.clear();
                pintar();
            });

            document.querySelector('[data-copiar]').addEventListener('click', function () {
                const texto = Array.from(escaneado.entries())
                    .map(([c, i]) => c + (i.veces > 1 ? ' ×' + i.veces : '') + '\t' + i.datos.equipo)
                    .join('\n');

                navigator.clipboard?.writeText(texto).then(
                    () => window.mostrarToast?.('Lista copiada'),
                    () => mostrarError('No se pudo copiar.')
                );
            });

            pintar();
        });
        </script>
    @endpush
@endsection
