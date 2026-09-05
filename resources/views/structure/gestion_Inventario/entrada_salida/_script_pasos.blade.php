@push('scripts')
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('form-entrada');
        if (!form) return;

        const barra = document.getElementById('pasos');
        const navCuenta = form.querySelector('[data-nav-cuenta]');
        const btnAtras = form.querySelector('[data-ir="atras"]');
        const btnAdelante = form.querySelector('[data-ir="adelante"]');
        const btnEnviar = form.querySelector('[data-enviar]');

        const todos = Array.from(form.querySelectorAll('.paso'));
        let actual = 0;

        function esUsado() {
            return form.querySelector('[data-condicion]:checked')?.value === 'usado';
        }

        /* El checklist solo existe si el equipo es usado: en nuevo, ese
           paso ni se cuenta ni se numera. */
        function visibles() {
            return todos.filter(p => !p.hasAttribute('data-solo-usado') || esUsado());
        }

        function pintarBarra() {
            const lista = visibles();
            barra.innerHTML = '';

            lista.forEach(function (paso, i) {
                const chip = document.createElement('button');
                chip.type = 'button';
                chip.className = 'paso-chip';
                chip.dataset.estado = i === actual ? 'actual' : (i < actual ? 'listo' : 'pendiente');
                chip.innerHTML = `<span class="n">${i < actual ? '✓' : i + 1}</span><span class="txt">${paso.dataset.titulo}</span>`;

                // Solo se puede saltar hacia atrás: adelante hay que pasar
                // por la validación de cada paso.
                chip.disabled = i > actual;
                chip.addEventListener('click', () => irA(i));

                barra.appendChild(chip);
            });

            if (navCuenta) navCuenta.textContent = `Paso ${actual + 1} de ${lista.length}`;
        }

        function mostrar() {
            const lista = visibles();
            actual = Math.max(0, Math.min(actual, lista.length - 1));

            todos.forEach(p => p.removeAttribute('data-activo'));
            lista[actual].setAttribute('data-activo', '');

            const ultimo = actual === lista.length - 1;
            btnAtras.style.display = actual === 0 ? 'none' : '';
            btnAdelante.style.display = ultimo ? 'none' : '';
            btnEnviar.style.display = ultimo ? '' : 'none';

            pintarBarra();

            form.dispatchEvent(new CustomEvent('paso:cambio', {
                detail: { paso: lista[actual].dataset.paso, indice: actual },
            }));

            if (window.actualizarResumenEntrada) window.actualizarResumenEntrada();
        }

        function irA(indice) {
            actual = indice;
            mostrar();
            // En el teléfono el paso siguiente empieza fuera de pantalla.
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        /*
        | Se valida solo el paso en el que estás. Sin esto el navegador
        | intentaría validar campos de pasos ocultos al enviar y marcaría
        | "un campo no visible no se puede enfocar", sin decir cuál.
        */
        function pasoValido() {
            const paso = visibles()[actual];
            const campos = paso.querySelectorAll('input, select, textarea');

            for (const campo of campos) {
                if (campo.disabled || campo.offsetParent === null) continue;

                if (!campo.checkValidity()) {
                    campo.reportValidity();
                    return false;
                }
            }

            // El checklist se responde completo: un punto sin marcar es un
            // punto que nadie revisó.
            if (paso.dataset.paso === 'checklist') {
                const puntos = Array.from(paso.querySelectorAll('[data-chk-punto]'));
                const falta = puntos.find(p => !p.querySelector('input[type=radio]:checked'));

                if (falta) {
                    falta.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    falta.querySelector('.tri').animate(
                        [{ outline: '2px solid var(--danger)' }, { outline: '2px solid transparent' }],
                        { duration: 1200 }
                    );
                    return false;
                }
            }

            return true;
        }

        btnAtras.addEventListener('click', () => irA(actual - 1));
        btnAdelante.addEventListener('click', function () {
            if (pasoValido()) irA(actual + 1);
        });

        // Enter dentro de un campo avanza en vez de enviar a medias.
        form.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' && e.target.tagName !== 'TEXTAREA' && actual < visibles().length - 1) {
                e.preventDefault();
                if (pasoValido()) irA(actual + 1);
            }
        });

        form.addEventListener('paso:ir', function (e) {
            const i = visibles().findIndex(p => p.dataset.paso === e.detail.paso);
            if (i >= 0) irA(i);
        });

        /* ===================== Nuevo / usado ===================== */
        form.querySelectorAll('[data-condicion]').forEach(function (radio) {
            radio.addEventListener('change', function () {
                // Los campos del checklist estorban si el equipo es nuevo:
                // se apagan para que no viajen ni se validen.
                const pasoChk = form.querySelector('[data-solo-usado]');
                pasoChk.querySelectorAll('input, textarea').forEach(c => { c.disabled = !esUsado(); });

                if (window.pintarUnidades) window.pintarUnidades();
                mostrar();
            });
        });

        /* ===================== Modo de identificación ===================== */
        function aplicarModo() {
            const modo = form.querySelector('[data-modo]:checked')?.value || 'lote';

            ['lote', 'series', 'unidades'].forEach(function (m) {
                const panel = form.querySelector(`[data-panel="${m}"]`);
                if (!panel) return;

                panel.style.display = m === modo ? '' : 'none';
                // Un campo escondido no debe enviarse ni bloquear el envío.
                panel.querySelectorAll('input, textarea').forEach(c => { c.disabled = m !== modo; });
            });

            if (modo === 'unidades' && window.pintarUnidades) window.pintarUnidades();
        }

        form.querySelectorAll('[data-modo]').forEach(r => r.addEventListener('change', aplicarModo));

        /* ===================== Ecos de la cantidad ===================== */
        const cantidad = document.getElementById('cantidad');

        if (cantidad) {
            cantidad.addEventListener('input', function () {
                const n = Math.max(1, parseInt(cantidad.value || '1', 10) || 1);
                form.querySelectorAll('[data-eco-cantidad]').forEach(e => { e.textContent = n; });

                if (form.querySelector('[data-modo]:checked')?.value === 'unidades' && window.pintarUnidades) {
                    window.pintarUnidades();
                }

                if (window.actualizarResumenEntrada) window.actualizarResumenEntrada();
            });
        }

        /* ===================== Contadores del checklist ===================== */
        const puntos = Array.from(form.querySelectorAll('[data-chk-punto]'));
        const chkCuenta = form.querySelector('[data-chk-cuenta]');
        const chkMal = form.querySelector('[data-chk-mal]');

        function contarChecklist() {
            let respondidos = 0;
            let mal = 0;

            // Se junta, por proceso, qué fallas lo hacen necesario.
            const porProceso = {};

            puntos.forEach(function (punto) {
                const elegido = punto.querySelector('input[type=radio]:checked');
                if (elegido) respondidos++;

                // La nota solo aparece donde hay algo que anotar.
                const esProblema = elegido?.value === 'no';
                if (esProblema) mal++;
                punto.classList.toggle('con-nota', esProblema);

                const manda = punto.dataset.manda;

                if (esProblema && manda) {
                    (porProceso[manda] ??= []).push(punto.querySelector('.txt').textContent.trim());
                }
            });

            if (chkCuenta) chkCuenta.textContent = respondidos;
            if (chkMal) chkMal.textContent = mal;

            proponerRuta(porProceso);
        }

        /*
        | La ruta se propone desde el checklist: un golpe manda a
        | hojalatería, que no encienda manda a mantenimiento. Solo se
        | propone; quien recibe puede marcar o desmarcar lo que quiera,
        | y si lo tocó a mano ya no se le vuelve a mover.
        */
        const resumenRuta = form.querySelector('[data-ruta-resumen]');
        const casillasProceso = Array.from(form.querySelectorAll('[data-proceso]'));
        const tocadoAMano = new Set();

        casillasProceso.forEach(function (casilla) {
            casilla.addEventListener('change', function () {
                tocadoAMano.add(casilla.dataset.proceso);
                pintarResumenRuta();
            });
        });

        function proponerRuta(porProceso) {
            casillasProceso.forEach(function (casilla) {
                const clave = casilla.dataset.proceso;
                const motivos = porProceso[clave] || [];
                const nota = form.querySelector(`[data-proceso-motivo="${clave}"]`);

                if (nota) {
                    nota.textContent = motivos.length
                        ? 'Por: ' + motivos.join('; ')
                        : 'No hace falta según el checklist';
                }

                if (!tocadoAMano.has(clave)) {
                    casilla.checked = motivos.length > 0;
                }
            });

            pintarResumenRuta();
        }

        function pintarResumenRuta() {
            if (!resumenRuta) return;

            const elegidos = casillasProceso
                .filter(c => c.checked)
                .map(c => c.closest('.opcion').querySelector('.t').textContent.trim());

            resumenRuta.textContent = elegidos.length === 0
                ? 'Sin procesos: entra directo a stock.'
                : 'Pasa por ' + elegidos.join(' y ').toLowerCase() + ', y hasta terminar entra a stock.';
        }

        puntos.forEach(p => p.addEventListener('change', contarChecklist));

        /* ===================== Arranque ===================== */
        const pasoChk = form.querySelector('[data-solo-usado]');
        pasoChk.querySelectorAll('input, textarea').forEach(c => { c.disabled = !esUsado(); });

        aplicarModo();
        contarChecklist();
        if (cantidad) cantidad.dispatchEvent(new Event('input'));
        mostrar();

        /*
        | Si el servidor regresó errores, se abre el paso donde están.
        |
        | Se ignoran los avisos que el formulario ya trae escondidos (el de
        | "máximo 3 fotos", el del video): también son .err, y sin filtrarlos
        | el formulario arrancaba siempre en el paso de la evidencia.
        */
        const primerError = Array.from(form.querySelectorAll('.err'))
            .find(e => e.textContent.trim() !== '' && e.style.display !== 'none');

        if (primerError) {
            const i = visibles().indexOf(primerError.closest('.paso'));
            if (i >= 0) irA(i);
        }
    });
    </script>
@endpush
