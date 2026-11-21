/* Toast, confirmaciones y menú del panel (antes en línea en layouts/dashboard). */
    // ===== Toast global =====
    var TOAST_ICON_OK = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>';
    var TOAST_ICON_WARN = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M12 9v4"/><path d="M12 17h.01"/><path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/></svg>';
    window.showToast = function (msg, type) {
        var t = document.getElementById('appToast');
        if (!t) return;
        document.getElementById('appToastMsg').textContent = msg;
        var ico = document.getElementById('appToastIco');
        ico.classList.toggle('warn', type === 'warn');
        ico.innerHTML = type === 'warn' ? TOAST_ICON_WARN : TOAST_ICON_OK;
        t.classList.add('show');
        clearTimeout(window._toastTimer);
        window._toastTimer = setTimeout(function () { t.classList.remove('show'); }, 3600);
    };
    window.toast = window.showToast;

    // ===== Modal de confirmacion (reemplaza confirm() nativo) =====
    var CM_ICON = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>';
    var CM_DANGER_RE = /elimin|borrar|quitar|banear|desactiv|cancelar|denegar|rechaz/i;
    window.confirmModal = function (opts) {
        opts = opts || {};
        return new Promise(function (resolve) {
            var ov = document.getElementById('cmOverlay');
            if (!ov) { resolve(window.confirm(opts.message || '¿Confirmar?')); return; }
            var box = ov.querySelector('.cm-box');
            var esDanger = (opts.danger !== undefined) ? !!opts.danger
                : CM_DANGER_RE.test(String(opts.message || '') + ' ' + String(opts.title || ''));
            document.getElementById('cmTitle').textContent = opts.title || '¿Estás seguro?';
            document.getElementById('cmMsg').textContent = opts.message || '';
            document.getElementById('cmIco').innerHTML = CM_ICON;
            var ok = document.getElementById('cmOk'), cancel = document.getElementById('cmCancel');
            ok.textContent = opts.confirmText || (esDanger ? 'Sí, continuar' : 'Confirmar');
            cancel.textContent = opts.cancelText || 'Cancelar';
            box.classList.toggle('danger', esDanger);
            ov.hidden = false;
            requestAnimationFrame(function () { ov.classList.add('show'); });
            setTimeout(function () { ok.focus(); }, 30);
            function cerrar(val) {
                ov.classList.remove('show');
                setTimeout(function () { ov.hidden = true; }, 180);
                ok.removeEventListener('click', onOk);
                cancel.removeEventListener('click', onCancel);
                ov.removeEventListener('mousedown', onBackdrop);
                document.removeEventListener('keydown', onKey);
                resolve(val);
            }
            function onOk() { cerrar(true); }
            function onCancel() { cerrar(false); }
            function onBackdrop(e) { if (e.target === ov) cerrar(false); }
            function onKey(e) {
                if (e.key === 'Escape') cerrar(false);
                else if (e.key === 'Enter') { e.preventDefault(); cerrar(true); }
            }
            ok.addEventListener('click', onOk);
            cancel.addEventListener('click', onCancel);
            ov.addEventListener('mousedown', onBackdrop);
            document.addEventListener('keydown', onKey);
        });
    };

    // Redirige los alert() nativos al toast (no bloqueante).
    var _nativeAlert = window.alert.bind(window);
    window.alert = function (msg) {
        try { window.showToast(String(msg), 'warn'); }
        catch (e) { _nativeAlert(msg); }
    };

    // Interceptor declarativo: cualquier <form data-confirm="..."> abre el modal
    // antes de enviarse. Reemplaza onsubmit="return confirm(...)".
    document.addEventListener('submit', function (e) {
        var form = e.target;
        if (!(form instanceof HTMLFormElement) || !form.hasAttribute('data-confirm')) return;
        if (form.dataset.cmDone === '1') { form.dataset.cmDone = ''; return; }
        e.preventDefault();
        window.confirmModal({
            message: form.getAttribute('data-confirm') || '¿Confirmar esta acción?',
            title: form.getAttribute('data-confirm-title') || undefined,
            confirmText: form.getAttribute('data-confirm-ok') || undefined,
            danger: form.hasAttribute('data-confirm-danger') ? true : undefined
        }).then(function (ok) {
            if (!ok) return;
            form.dataset.cmDone = '1';
            if (form.requestSubmit) { form.requestSubmit(); } else { form.submit(); }
        });
    }, true);

    // Enlaces <a data-confirm="..."> tambien pasan por el modal.
    document.addEventListener('click', function (e) {
        var el = e.target.closest('a[data-confirm]');
        if (!el || !el.getAttribute('href')) return;
        e.preventDefault();
        var href = el.getAttribute('href');
        window.confirmModal({ message: el.getAttribute('data-confirm') }).then(function (ok) {
            if (ok) window.location.href = href;
        });
    }, true);

    document.addEventListener('DOMContentLoaded', function () {
        // Saludo según la hora real del navegador (evita desfase de zona horaria del servidor)
        var greetWord = document.getElementById('greet-word');
        if (greetWord) {
            var hh = new Date().getHours();
            greetWord.textContent = hh < 12 ? 'Buenos días' : (hh < 19 ? 'Buenas tardes' : 'Buenas noches');
        }

        // Mostrar mensajes flash como toast
        var flash = document.getElementById('appFlash');
        var flashErr = document.getElementById('appFlashErr');
        if (flash && flash.dataset.msg) { window.showToast(flash.dataset.msg, 'ok'); }
        else if (flashErr && flashErr.dataset.msg) { window.showToast(flashErr.dataset.msg, 'warn'); }

        var app = document.getElementById('app');
        var isMobile = function () { return window.matchMedia('(max-width:1024px)').matches; };

        // Etiqueta accesible del botón según el estado
        var collapseBtn = document.getElementById('collapse-btn');
        function syncCollapseLabel() {
            var collapsed = app.classList.contains('collapsed');
            var label = collapsed ? 'Expandir menú' : 'Contraer menú';
            collapseBtn.setAttribute('aria-label', label);
            collapseBtn.setAttribute('title', label);
        }

        // Colapsar sidebar (escritorio) / abrir-cerrar (móvil)
        // Con el cajón abierto la página de atrás no se desplaza.
        function cerrarCajon() {
            app.classList.remove('sidebar-open');
            document.body.classList.remove('menu-abierto');
        }
        function toggleSidebar() {
            if (isMobile()) {
                var abierto = app.classList.toggle('sidebar-open');
                document.body.classList.toggle('menu-abierto', abierto);
            } else {
                app.classList.toggle('collapsed');
                try { localStorage.setItem('sidebar-collapsed', app.classList.contains('collapsed') ? '1' : '0'); } catch (e) {}
                syncCollapseLabel();
            }
        }
        if (!isMobile()) {
            app.classList.add('no-anim');
            try { if (localStorage.getItem('sidebar-collapsed') === '1') app.classList.add('collapsed'); } catch (e) {}
            // Forzar reflow y reactivar animaciones tras aplicar el estado inicial
            void app.offsetWidth;
            requestAnimationFrame(function () { app.classList.remove('no-anim'); });
        }
        syncCollapseLabel();
        document.getElementById('hamburger').addEventListener('click', toggleSidebar);
        document.getElementById('collapse-btn').addEventListener('click', toggleSidebar);
        document.getElementById('overlay').addEventListener('click', cerrarCajon);
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && app.classList.contains('sidebar-open')) cerrarCajon();
        });
        // La opción activa queda a la vista aunque esté al fondo de la lista.
        (function () {
            var nav = document.querySelector('.sidebar .nav');
            var activo = nav && nav.querySelector('.nav-item.active');
            if (!activo) return;
            var delta = activo.getBoundingClientRect().top - nav.getBoundingClientRect().top;
            if (delta > nav.clientHeight - 60) nav.scrollTop = delta - nav.clientHeight / 2;
        })();
        // Si la ventana crece a escritorio con el cajón abierto, no queda la página bloqueada.
        window.addEventListener('resize', function () {
            if (!isMobile() && app.classList.contains('sidebar-open')) cerrarCajon();
        });

        // Tema claro / oscuro
        document.getElementById('theme-toggle').addEventListener('click', function () {
            var cur = document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
            document.documentElement.setAttribute('data-theme', cur);
            try { localStorage.setItem('theme', cur); } catch (e) {}
        });

        // Dropdowns (notificaciones / usuario)
        document.querySelectorAll('[data-dd]').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.stopPropagation();
                var dd = document.getElementById(btn.getAttribute('data-dd'));
                var wasOpen = dd.classList.contains('open');
                document.querySelectorAll('.dd.open').forEach(function (o) { o.classList.remove('open'); });
                if (!wasOpen) dd.classList.add('open');
            });
        });
        document.addEventListener('click', function () {
            document.querySelectorAll('.dd.open').forEach(function (o) { o.classList.remove('open'); });
        });
        document.querySelectorAll('.dd-panel').forEach(function (p) {
            p.addEventListener('click', function (e) { e.stopPropagation(); });
        });

        // Ojo para contraseñas
        var eyeOn = '<svg class="icon-on" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/></svg>';
        var eyeOff = '<svg class="icon-off" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-6.5 0-10-7-10-7a18.4 18.4 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c6.5 0 10 7 10 7a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>';
        document.querySelectorAll('input[type="password"]').forEach(function (input) {
            var wrap = document.createElement('span');
            wrap.className = 'pw-wrap';
            input.parentNode.insertBefore(wrap, input);
            wrap.appendChild(input);
            var b = document.createElement('button');
            b.type = 'button'; b.className = 'pw-toggle'; b.setAttribute('aria-label', 'Mostrar u ocultar contraseña');
            b.innerHTML = eyeOn + eyeOff;
            wrap.appendChild(b);
            b.addEventListener('click', function () {
                var show = input.type === 'password';
                input.type = show ? 'text' : 'password';
                b.classList.toggle('is-visible', show);
            });
        });
    });

        // Submenús del sidebar
        (function () {
            var app = document.getElementById('app');
            var grupos = Array.prototype.slice.call(document.querySelectorAll('.nav-group'));
            var flotante = null;
            var temporizador = null;

            // El panel flotante toma su título del apartado al que pertenece.
            grupos.forEach(function (g) {
                var sub = g.querySelector('.submenu');
                var toggle = g.querySelector('.nav-toggle');
                if (sub && toggle) sub.dataset.nombre = toggle.dataset.tip || '';
            });

            // En móvil el menú se ve completo aunque quedara marcado contraído.
            function contraido() {
                return app.classList.contains('collapsed') && window.innerWidth > 1024;
            }

            function ocultar() {
                if (!flotante) return;
                flotante.removeAttribute('data-flotante');
                flotante = null;
            }

            function mostrar(g) {
                clearTimeout(temporizador);
                if (flotante === g) return;
                ocultar();

                var sub = g.querySelector('.submenu');
                var r = g.getBoundingClientRect();

                // El panel se ancla al ancho final de la barra, no a su medida
                // en pantalla: durante la animación de contraer esa medida
                // todavía está cambiando y el panel quedaría descuadrado.
                var barra = parseInt(getComputedStyle(document.documentElement)
                    .getPropertyValue('--sidebar-w-collapsed'), 10) || 78;

                sub.style.left = (barra + 8) + 'px';
                // Si el panel no cabe hacia abajo, sube lo necesario.
                sub.style.top = Math.max(10, Math.min(r.top, window.innerHeight - sub.offsetHeight - 10)) + 'px';

                g.setAttribute('data-flotante', '');
                flotante = g;
            }

            grupos.forEach(function (g) {
                var toggle = g.querySelector('.nav-toggle');
                if (!toggle) return;

                toggle.addEventListener('click', function (e) {
                    e.preventDefault();

                    if (contraido()) {
                        flotante === g ? ocultar() : mostrar(g);
                        return;
                    }

                    var abierto = g.classList.contains('open');
                    // Acordeón: un solo apartado abierto a la vez, para que el
                    // menú no se convierta en una lista interminable.
                    grupos.forEach(function (o) { o.classList.remove('open'); });
                    if (!abierto) g.classList.add('open');
                });

                g.addEventListener('mouseenter', function () { if (contraido()) mostrar(g); });
                g.addEventListener('mouseleave', function () {
                    // Margen para que el mouse alcance el panel sin que se cierre.
                    if (contraido()) temporizador = setTimeout(ocultar, 200);
                });
            });

            document.addEventListener('click', function (e) {
                if (!e.target.closest('.nav-group')) ocultar();
            });
            document.addEventListener('keydown', function (e) { if (e.key === 'Escape') ocultar(); });
            window.addEventListener('resize', ocultar);
        })();
