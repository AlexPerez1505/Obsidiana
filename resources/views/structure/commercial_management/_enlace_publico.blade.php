{{--
    Enlace de consulta del cliente. Es el mismo que va en el QR del PDF,
    para poder mandárselo por WhatsApp o correo sin imprimir nada.

    Espera $url (puede venir nulo).
--}}

@if (! empty($url))
    <div class="erp-card pad ep-caja">
        <span class="ep-ico">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><path d="M14 14h3v3h-3zM18 18h3v3h-3z"/></svg>
        </span>

        <div class="ep-txt">
            <b>Enlace para el cliente</b>
            <p>El mismo que lleva el código QR del PDF. Se abre sin contraseña.</p>
            <code data-url>{{ $url }}</code>
        </div>

        <div class="ep-acciones">
            <button type="button" class="erp-btn ghost sm" data-copiar>Copiar</button>
            <a href="{{ $url }}" target="_blank" rel="noopener" class="erp-btn ghost sm">Abrir</a>
        </div>
    </div>

    <style>
        .ep-caja { display:flex; align-items:center; gap:16px; flex-wrap:wrap; margin-bottom:20px; }
        .ep-ico { display:flex; align-items:center; justify-content:center; width:44px; height:44px;
                  flex:0 0 44px; border-radius:12px; background:var(--primary-soft); color:var(--primary); }
        .ep-ico svg { width:22px; height:22px; }
        .ep-txt { flex:1; min-width:220px; }
        .ep-txt b { font-size:14px; }
        .ep-txt p { margin:2px 0 6px; color:var(--muted); font-size:12.5px; }
        .ep-txt code { display:block; color:var(--muted); font-size:11.5px; word-break:break-all; }
        .ep-acciones { display:flex; gap:8px; flex-wrap:wrap; }
    </style>

    <script>
    (function () {
        var caja = document.querySelector('.ep-caja');
        if (!caja) return;

        var boton = caja.querySelector('[data-copiar]');
        var url = caja.querySelector('[data-url]').textContent.trim();

        boton.addEventListener('click', function () {
            // clipboard.writeText solo existe en contextos seguros (https o
            // localhost); si no está, se cae al método viejo.
            var listo = function () {
                boton.textContent = 'Copiado';
                setTimeout(function () { boton.textContent = 'Copiar'; }, 1800);
            };

            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(url).then(listo);
                return;
            }

            var tmp = document.createElement('textarea');
            tmp.value = url;
            tmp.style.position = 'fixed';
            tmp.style.opacity = '0';
            document.body.appendChild(tmp);
            tmp.select();
            try { document.execCommand('copy'); listo(); } catch (e) {}
            document.body.removeChild(tmp);
        });
    })();
    </script>
@endif
