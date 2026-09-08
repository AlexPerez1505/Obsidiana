@props([
    'etiqueta' => 'Acciones',
    'align' => 'right',
])

{{--
    Menu desplegable para la cabecera de pagina.

    Agrupa acciones del mismo tipo (los documentos de una venta, por ejemplo)
    en un solo control, en lugar de una fila de botones identicos donde nada
    destaca. Dentro va <x-ui.menu-item>.

    El panel se posiciona con position:fixed calculado en JS: dentro de la
    cabecera cualquier ancestro con overflow o filtro lo recortaria.
--}}

<div class="ui-menu" data-ui-menu>
    <button type="button" class="btn btn--ghost ui-menu-trigger" aria-expanded="false" aria-haspopup="true">
        {{ $etiqueta }}
        <svg class="ui-menu-flecha" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
    </button>

    <div class="ui-menu-panel" data-align="{{ $align }}" role="menu" hidden>
        {{ $slot }}
    </div>
</div>

@once
    @push('head')
        <style>
            .ui-menu { position:relative; display:inline-flex; }
            .ui-menu-trigger { cursor:pointer; }
            .ui-menu-flecha { width:14px; height:14px; opacity:.6; transition:transform .16s ease; }
            .ui-menu[data-abierto] .ui-menu-flecha { transform:rotate(180deg); }

            .ui-menu-panel { position:fixed; z-index:1200; min-width:236px; padding:6px;
                             background:var(--surface); border:1px solid var(--border);
                             border-radius:11px; box-shadow:var(--shadow, 0 10px 30px rgba(15,23,42,.12));
                             opacity:0; transform:translateY(-4px); pointer-events:none;
                             transition:opacity .14s ease, transform .14s ease; }
            .ui-menu-panel[data-abierto] { opacity:1; transform:translateY(0); pointer-events:auto; }

            .ui-menu-item { display:flex; align-items:flex-start; gap:10px; width:100%;
                            padding:8px 10px; border:0; border-radius:8px; background:transparent;
                            color:var(--text); font-family:inherit; font-size:13.5px; text-align:left;
                            text-decoration:none; cursor:pointer; transition:background .13s ease; }
            .ui-menu-item:hover { background:var(--surface-2); }
            .ui-menu-item svg { width:16px; height:16px; margin-top:1px; color:var(--muted); flex:0 0 16px; }
            .ui-menu-item .txt { min-width:0; }
            .ui-menu-item .t { display:block; font-weight:500; line-height:1.35; }
            .ui-menu-item .d { display:block; margin-top:1px; color:var(--muted); font-size:12px; line-height:1.35; }
            .ui-menu-sep { height:1px; margin:5px 8px; background:var(--border); }

            @media (prefers-reduced-motion: reduce) {
                .ui-menu-panel, .ui-menu-flecha { transition:none; }
            }
        </style>
    @endpush

    @push('scripts')
        <script>
            (() => {
                let abierto = null;

                const cerrar = () => {
                    if (!abierto) return;
                    const panel = abierto.querySelector('.ui-menu-panel');
                    panel.removeAttribute('data-abierto');
                    abierto.removeAttribute('data-abierto');
                    abierto.querySelector('.ui-menu-trigger').setAttribute('aria-expanded', 'false');
                    // Se oculta al terminar la transicion para no dejarlo tabulable.
                    setTimeout(() => { if (!panel.hasAttribute('data-abierto')) panel.hidden = true; }, 150);
                    abierto = null;
                };

                const colocar = (menu) => {
                    const panel = menu.querySelector('.ui-menu-panel');
                    const r = menu.getBoundingClientRect();
                    const margen = 8;

                    panel.style.top = `${r.bottom + 6}px`;

                    // Se alinea por el lado que pidan, salvo que no quepa.
                    let izq = panel.dataset.align === 'left'
                        ? r.left
                        : r.right - panel.offsetWidth;

                    izq = Math.max(margen, Math.min(izq, window.innerWidth - panel.offsetWidth - margen));
                    panel.style.left = `${izq}px`;
                };

                document.addEventListener('click', (e) => {
                    const trigger = e.target.closest('.ui-menu-trigger');

                    if (!trigger) {
                        if (!e.target.closest('.ui-menu-panel')) cerrar();
                        return;
                    }

                    e.preventDefault();
                    const menu = trigger.closest('[data-ui-menu]');

                    if (abierto === menu) { cerrar(); return; }

                    cerrar();

                    const panel = menu.querySelector('.ui-menu-panel');
                    panel.hidden = false;
                    colocar(menu);
                    panel.setAttribute('data-abierto', '');
                    menu.setAttribute('data-abierto', '');
                    trigger.setAttribute('aria-expanded', 'true');
                    abierto = menu;
                });

                document.addEventListener('keydown', (e) => { if (e.key === 'Escape') cerrar(); });
                window.addEventListener('resize', cerrar);
                window.addEventListener('scroll', cerrar, true);
            })();
        </script>
    @endpush
@endonce
