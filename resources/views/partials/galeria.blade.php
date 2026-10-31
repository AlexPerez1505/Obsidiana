{{--
    Visor de evidencia: fotos y video, uno por uno, en un modal.

    Uso: un elemento con data-medios='[{"tipo":"foto","url":"..."},{"tipo":"video","url":"..."}]'
    y, dentro (o él mismo), un disparador con data-galeria y opcional
    data-indice="N" para abrir en ese medio. También se puede llamar
    abrirGaleria(elemento) desde un onclick, como hacía Productos.

    Se cierra con la ×, tocando el fondo o con Esc; se avanza con las
    flechas, el teclado, las miniaturas o deslizando en el celular.
--}}
@once
    <style>
        .gal-overlay { position:fixed; inset:0; z-index:1100; display:flex; align-items:center; justify-content:center;
                       padding:20px; background:rgba(8, 12, 20, .86); }
        .gal-overlay[hidden] { display:none; }
        .gal-caja { width:100%; max-width:960px; display:flex; flex-direction:column; gap:12px; }
        .gal-barra { display:flex; align-items:center; justify-content:space-between; color:#fff; }
        .gal-cuenta { font-size:13.5px; opacity:.85; }
        .gal-x { width:38px; height:38px; border:0; border-radius:50%; background:rgba(255,255,255,.12);
                 color:#fff; font-size:24px; line-height:1; cursor:pointer; }
        .gal-x:hover { background:rgba(255,255,255,.24); }
        .gal-escena { position:relative; display:flex; align-items:center; justify-content:center; min-height:200px; }
        .gal-medio { display:flex; align-items:center; justify-content:center; width:100%; }
        .gal-medio img, .gal-medio video { max-width:100%; max-height:72vh; border-radius:12px; display:block;
                                            background:#000; box-shadow:0 12px 40px rgba(0,0,0,.45); }
        .gal-flecha { position:absolute; top:50%; transform:translateY(-50%); z-index:1; width:44px; height:44px;
                      border:0; border-radius:50%; background:rgba(255,255,255,.14); color:#fff;
                      font-size:28px; line-height:1; cursor:pointer; }
        .gal-flecha:hover { background:rgba(255,255,255,.28); }
        .gal-prev { left:6px; }
        .gal-sig { right:6px; }
        .gal-tira { display:flex; gap:8px; justify-content:center; overflow-x:auto; padding:2px; }
        .gal-mini { flex:0 0 auto; width:60px; height:60px; padding:0; border:2px solid transparent; border-radius:9px;
                    overflow:hidden; background:#000; cursor:pointer; opacity:.6; position:relative; }
        .gal-mini.activa { border-color:#fff; opacity:1; }
        .gal-mini img, .gal-mini video { width:100%; height:100%; object-fit:cover; display:block; pointer-events:none; }
        .gal-mini .gal-play { position:absolute; inset:0; display:flex; align-items:center; justify-content:center;
                              color:#fff; font-size:18px; background:rgba(0,0,0,.35); }
        .gal-solo .gal-flecha, .gal-solo .gal-tira { display:none; }
        .gal-otro { display:flex; flex-direction:column; align-items:center; gap:10px; padding:40px 30px;
                    border-radius:12px; background:rgba(255,255,255,.08); color:#fff; text-align:center; }
        .gal-otro b { font-size:28px; letter-spacing:.05em; }
        .gal-otro span { opacity:.8; font-size:14px; }
        @media (max-width: 600px) {
            .gal-overlay { padding:12px; }
            .gal-flecha { width:38px; height:38px; font-size:24px; }
        }
    </style>

    <script>
    (function () {
        // Se crea aquí, directo en el body: dentro del contenido, un
        // contenedor con transform encerraría al position:fixed, y la
        // actualización en tiempo real lo volvería a insertar.
        const plantilla = document.createElement('div');
        plantilla.innerHTML = `<div class="gal-overlay" id="galOverlay" hidden>
        <div class="gal-caja" role="dialog" aria-modal="true" aria-label="Evidencia">
            <div class="gal-barra">
                <span class="gal-cuenta" id="galCuenta"></span>
                <button type="button" class="gal-x" data-gal-cerrar aria-label="Cerrar">&times;</button>
            </div>

            <div class="gal-escena">
                <button type="button" class="gal-flecha gal-prev" data-gal-mover="-1" aria-label="Anterior">&lsaquo;</button>
                <div class="gal-medio" id="galMedio"></div>
                <button type="button" class="gal-flecha gal-sig" data-gal-mover="1" aria-label="Siguiente">&rsaquo;</button>
            </div>

            <div class="gal-tira" id="galTira"></div>
        </div>
    </div>`;
        const overlay = plantilla.firstElementChild;
        document.body.appendChild(overlay);
        const medioEl = document.getElementById('galMedio');
        const tira = document.getElementById('galTira');
        const cuenta = document.getElementById('galCuenta');
        let medios = [];
        let i = 0;

        // TIFF, RAW, HEIC...: el navegador no los dibuja. Se dice qué son y
        // se ofrecen para descargar, en lugar de una imagen rota.
        const SE_VEN = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif', 'bmp', 'jfif', 'svg'];
        function extension(url) {
            return (String(url).split('?')[0].split('#')[0].split('.').pop() || '').toLowerCase();
        }
        function seVe(m) {
            return m.tipo === 'video' || SE_VEN.includes(extension(m.url));
        }

        function pintar() {
            const m = medios[i];
            if (m.tipo === 'video') {
                medioEl.innerHTML = '<video src="' + m.url + '" controls autoplay playsinline></video>';
            } else if (seVe(m)) {
                medioEl.innerHTML = '<img src="' + m.url + '" alt="Evidencia">';
            } else {
                medioEl.innerHTML = '<div class="gal-otro"><b>' + extension(m.url).toUpperCase() + '</b>'
                    + '<span>Este formato no se puede ver en el navegador.</span>'
                    + '<a class="btn" href="' + m.url + '" download>Descargar</a></div>';
            }
            cuenta.textContent = (i + 1) + ' de ' + medios.length + (m.tipo === 'video' ? ' · video' : '');
            tira.querySelectorAll('.gal-mini').forEach((b, j) => b.classList.toggle('activa', j === i));
            tira.children[i]?.scrollIntoView({ block: 'nearest', inline: 'center' });
        }

        function mover(paso) {
            if (medios.length < 2) return;
            i = (i + paso + medios.length) % medios.length;
            pintar();
        }

        function abrir(lista, indice) {
            medios = Array.isArray(lista) ? lista.filter(m => m && m.url) : [];
            if (!medios.length) return;
            i = Math.min(Math.max(0, Number(indice) || 0), medios.length - 1);

            tira.innerHTML = '';
            medios.forEach(function (m, j) {
                const b = document.createElement('button');
                b.type = 'button';
                b.className = 'gal-mini';
                b.setAttribute('aria-label', 'Ver ' + (j + 1));
                b.innerHTML = m.tipo === 'video'
                    ? '<video src="' + m.url + '#t=0.5" muted preload="metadata"></video><span class="gal-play">▶</span>'
                    : (seVe(m)
                        ? '<img src="' + m.url + '" alt="" loading="lazy">'
                        : '<span class="gal-play" style="font-size:11px; font-weight:700;">' + extension(m.url).toUpperCase() + '</span>');
                b.addEventListener('click', () => { i = j; pintar(); });
                tira.appendChild(b);
            });

            overlay.querySelector('.gal-caja').classList.toggle('gal-solo', medios.length < 2);
            overlay.hidden = false;
            document.body.style.overflow = 'hidden';
            pintar();
        }

        function cerrar() {
            overlay.hidden = true;
            medioEl.innerHTML = '';
            document.body.style.overflow = '';
        }

        function desdeElemento(el) {
            const fuente = el.closest('[data-medios]');
            if (!fuente) return;
            let lista = [];
            try { lista = JSON.parse(fuente.dataset.medios || '[]'); } catch (e) {}
            abrir(lista, el.dataset.indice);
        }

        window.ObsGaleria = { abrir, cerrar };
        // Compatibilidad con los onclick="abrirGaleria(this)" que ya existían.
        window.abrirGaleria = desdeElemento;

        document.addEventListener('click', function (e) {
            const disparador = e.target.closest('[data-galeria]');
            if (disparador) { e.preventDefault(); desdeElemento(disparador); return; }
            if (overlay.hidden) return;
            if (e.target.closest('[data-gal-cerrar]') || e.target === overlay) cerrar();
            const flecha = e.target.closest('[data-gal-mover]');
            if (flecha) mover(Number(flecha.dataset.galMover));
        });

        document.addEventListener('keydown', function (e) {
            if (overlay.hidden) return;
            if (e.key === 'Escape') cerrar();
            if (e.key === 'ArrowLeft') mover(-1);
            if (e.key === 'ArrowRight') mover(1);
        });

        // Deslizar en el celular.
        let x0 = null;
        medioEl.addEventListener('touchstart', e => { x0 = e.touches[0].clientX; }, { passive: true });
        medioEl.addEventListener('touchend', function (e) {
            if (x0 === null) return;
            const dx = e.changedTouches[0].clientX - x0;
            if (Math.abs(dx) > 50) mover(dx < 0 ? 1 : -1);
            x0 = null;
        });
    })();
    </script>
@endonce
