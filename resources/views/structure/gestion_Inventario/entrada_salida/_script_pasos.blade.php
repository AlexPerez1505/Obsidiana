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

        /*
        | Qué pasos ya quedaron completos y cuáles ya se visitaron.
        |
        | Se guardan por nombre de paso, no por número: el checklist aparece
        | y desaparece según nuevo/usado, así que los índices se recorren y
        | un paso ya terminado perdía su marca.
        */
        const completados = new Set();
        const visitados = new Set();

        function esUsado() {
            return form.querySelector('[data-condicion]:checked')?.value === 'usado';
        }

        /* El checklist solo existe si el equipo es usado: en nuevo, ese
           paso ni se cuenta ni se numera. */
        function visibles() {
            return todos.filter(p => !p.hasAttribute('data-solo-usado') || esUsado());
        }

        /* Se recalcula en cada pintado: así un paso que se completó y luego
           se vació deja de estar en azul. */
        function revisarMarcas() {
            visibles().forEach(function (paso) {
                const clave = paso.dataset.paso;

                if (faltantesDe(paso).length === 0) {
                    completados.add(clave);
                } else {
                    completados.delete(clave);
                }
            });
        }

        function pintarBarra() {
            const lista = visibles();
            revisarMarcas();
            barra.innerHTML = '';

            lista.forEach(function (paso, i) {
                const clave = paso.dataset.paso;
                const listo = completados.has(clave);
                const falta = ! listo && visitados.has(clave) && i !== actual;

                const chip = document.createElement('button');
                chip.type = 'button';
                chip.className = 'paso-chip';
                chip.dataset.estado = i === actual
                    ? 'actual'
                    : (listo ? 'listo' : (falta ? 'falta' : 'pendiente'));

                const marca = listo ? '✓' : (falta ? '!' : i + 1);
                chip.innerHTML = `<span class="n">${marca}</span><span class="txt">${paso.dataset.titulo}</span>`;

                // Se puede ir a cualquier paso: no hay que pasar por
                // "Continuar" para llegar al apartado que quieres llenar.
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
            const paso = visibles()[actual];
            if (paso) visitados.add(paso.dataset.paso);

            actual = indice;
            mostrar();
            // En el teléfono el paso siguiente empieza fuera de pantalla.
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        /*
        | Qué le falta a un paso, en palabras.
        |
        | No alcanza con checkValidity(): el video y la firma viven en
        | inputs ocultos (offsetParent null) y las fotos en un input
        | escondido dentro de su zona de arrastre, así que el navegador no
        | los revisa y antes solo se enteraba el servidor, al final.
        */
        function faltantesDe(paso) {
            const falta = [];

            paso.querySelectorAll('input, select, textarea').forEach(function (campo) {
                if (campo.disabled || campo.type === 'hidden' || campo.type === 'file') return;
                // Un campo de un panel apagado (otro modo de identificación)
                // no cuenta; los del paso oculto sí, porque el paso es el que
                // se está revisando.
                if (campo.closest('[data-panel]')?.style.display === 'none') return;

                if (! campo.checkValidity()) {
                    const etiqueta = campo.closest('.form-group')?.querySelector('label')?.textContent
                        || campo.name;
                    falta.push(etiqueta.replace('*', '').trim());
                }
            });

            if (paso.dataset.paso === 'checklist') {
                if (! paso.querySelector('[name="estado_general"]:checked')) {
                    falta.push('Marcar en qué estado general llegó');
                }

                const puntos = Array.from(paso.querySelectorAll('[data-chk-punto]'));
                const sinResponder = puntos.filter(p => ! p.querySelector('input[type=radio]:checked')).length;

                if (sinResponder > 0) {
                    falta.push(`Responder ${sinResponder} punto(s) del checklist`);
                }
            }

            if (paso.dataset.paso === 'evidencia') {
                const fotos = paso.querySelector('#evidencias');
                if (fotos && (! fotos.files || fotos.files.length === 0)) {
                    falta.push('Subir al menos una foto de cómo llegó');
                }

                const video = paso.querySelector('#video-path-input');
                if (video && ! video.value) {
                    falta.push('Subir el video de verificación');
                }
            }

            if (paso.dataset.paso === 'identificacion') {
                const panel = paso.querySelector('[data-panel="unidades"]');

                if (panel && panel.style.display !== 'none') {
                    const sinFoto = Array.from(panel.querySelectorAll('input[type=file]'))
                        .filter(i => i.required && (! i.files || i.files.length === 0)).length;

                    if (sinFoto > 0) {
                        falta.push(`Foto de ${sinFoto} pieza(s) sin capturar`);
                    }
                }
            }

            if (paso.dataset.paso === 'firma') {
                const firma = paso.querySelector('#firma-input');
                if (firma && ! firma.value) falta.push('Firmar en el recuadro');
            }

            return falta;
        }

        /* Pinta (o quita) el aviso de lo que falta, dentro del paso mismo. */
        function avisarFaltantes(paso, falta, enfocar = false) {
            let aviso = paso.querySelector('.paso-faltan');

            if (! falta.length) {
                if (aviso) aviso.remove();
                return;
            }

            if (! aviso) {
                aviso = document.createElement('div');
                aviso.className = 'paso-faltan';
                paso.prepend(aviso);
            }

            aviso.innerHTML = '<b>Falta esto en este paso:</b><ul>'
                + falta.map(f => `<li>${f}</li>`).join('')
                + '</ul>';

            if (enfocar) aviso.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }

        function pasoValido(enfocar = false) {
            const paso = visibles()[actual];
            const falta = faltantesDe(paso);

            avisarFaltantes(paso, falta, enfocar);

            return falta.length === 0;
        }

        btnAtras.addEventListener('click', () => irA(actual - 1));
        btnAdelante.addEventListener('click', function () {
            // Se avisa lo que falta, pero no se secuestra el avance: el chip
            // queda marcado en rojo y el envío sí lo detiene.
            pasoValido();
            irA(actual + 1);
        });

        // Enter dentro de un campo avanza en vez de enviar a medias.
        form.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' && e.target.tagName !== 'TEXTAREA' && actual < visibles().length - 1) {
                e.preventDefault();
                pasoValido();
                irA(actual + 1);
            }
        });

        /*
        | Antes de enviar se revisan todos los pasos: si algo falta se va al
        | primero incompleto y se dice qué es, en vez de dejar que el
        | servidor lo rechace (y que se pierdan los archivos ya elegidos,
        | que el navegador no permite volver a poner solos).
        */
        form.addEventListener('submit', function (e) {
            const lista = visibles();

            for (let i = 0; i < lista.length; i++) {
                const falta = faltantesDe(lista[i]);

                if (falta.length) {
                    e.preventDefault();
                    irA(i);
                    avisarFaltantes(lista[i], falta, true);
                    return;
                }
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
            // Al volver del servidor ya se vio todo el formulario: se marcan
            // todos los pasos para que los incompletos salgan señalados de
            // una vez, en vez de descubrirlos uno por uno.
            visibles().forEach(p => visitados.add(p.dataset.paso));

            const i = visibles().indexOf(primerError.closest('.paso'));
            if (i >= 0) irA(i);
            mostrar();
        }
    });
    </script>
@endpush
