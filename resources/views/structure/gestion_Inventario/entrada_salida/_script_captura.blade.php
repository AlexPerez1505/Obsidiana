@push('scripts')
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('form-entrada');
        if (!form) return;

        const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';
        const cantidadInput = document.getElementById('cantidad');

        const CHUNK_SIZE = 4 * 1024 * 1024;
        const EXTENSIONES_VIDEO = ['mp4', 'mov', 'm4v', 'webm'];
        const MAX_FOTOS = 3;

        // Cuántos videos se están subiendo ahora: mientras haya uno, el
        // formulario no se envía.
        let subiendoVideos = 0;

        /*
        | Lo capturado antes, si el servidor regresó el formulario con error.
        |
        | Los bloques de cada pieza los arma este script, así que sin esto
        | volvían vacíos. Las fotos no se pueden restaurar (el navegador no
        | permite rellenar un input de archivo), pero la serie y el video ya
        | subido sí, y eso se avisa en pantalla.
        */
        const PREVIAS = @json(array_values((array) old('unidades', [])));

        /* ==========================================================
           Zona para soltar archivos

           Recibe la zona ya creada (cada pieza tiene la suya) en vez de
           buscarla por nombre: en esta pantalla hay una por cada pieza que
           llegó, no una sola del lote.

           Las fotos se SUMAN. El navegador, cada vez que se elige o se toma
           una foto, deja en el input solo la nueva; en el celular eso hacía
           que la segunda foto borrara la primera. Aquí se guarda la lista
           de lo ya elegido y lo nuevo se le agrega, hasta el máximo.
        ========================================================== */
        function zonaDeArchivos(zona, minis, { max = null, alCambiar = null } = {}) {
            if (!zona) return null;

            const input = zona.querySelector('input[type=file]');
            const cuenta = zona.querySelector('[data-cuenta]');
            const textoBase = cuenta ? cuenta.textContent : '';

            // Lo elegido hasta ahora, en orden.
            let elegidos = [];

            function sincronizar() {
                const dt = new DataTransfer();
                elegidos.forEach(a => dt.items.add(a));
                input.files = dt.files;
            }

            function pintar() {
                if (minis) minis.innerHTML = '';

                elegidos.forEach(function (archivo, i) {
                    if (!minis) return;

                    const caja = document.createElement('div');
                    caja.className = 'mini';

                    const esVideo = archivo.type.startsWith('video/');
                    const medio = document.createElement(esVideo ? 'video' : 'img');
                    medio.src = URL.createObjectURL(archivo);
                    if (esVideo) { medio.muted = true; medio.playsInline = true; }

                    const quitar = document.createElement('button');
                    quitar.type = 'button';
                    quitar.className = 'quitar';
                    quitar.textContent = '×';
                    quitar.title = 'Quitar';
                    quitar.setAttribute('aria-label', 'Quitar ' + archivo.name);
                    quitar.addEventListener('click', function (e) {
                        e.preventDefault();
                        e.stopPropagation();
                        quitarArchivo(i);
                    });

                    caja.append(medio, quitar);
                    minis.appendChild(caja);
                });

                const n = elegidos.length;
                zona.classList.toggle('lleno', n > 0);

                if (cuenta) {
                    if (n === 0) {
                        cuenta.textContent = textoBase;
                    } else if (max && n >= max) {
                        cuenta.textContent = n + ' de ' + max + ' fotos · quita una para cambiarla';
                    } else {
                        cuenta.textContent = (n === 1 ? '1 foto' : n + ' fotos')
                            + (max ? ' de ' + max : '') + ' · toca para agregar otra';
                    }
                }

                if (alCambiar) alCambiar(n);
            }

            function quitarArchivo(indice) {
                elegidos.splice(indice, 1);
                sincronizar();
                pintar();
            }

            function sumar(lista) {
                const nuevos = Array.from(lista || []);
                if (!nuevos.length) return;

                const espacio = max ? Math.max(0, max - elegidos.length) : nuevos.length;

                if (nuevos.length > espacio && window.showToast) {
                    window.showToast('Cada pieza lleva máximo ' + max + ' fotos. Quita una para agregar otra.', 'err');
                }

                elegidos = elegidos.concat(nuevos.slice(0, espacio));
                sincronizar();
                pintar();
            }

            ['dragenter', 'dragover'].forEach(ev => zona.addEventListener(ev, function (e) {
                e.preventDefault();
                zona.classList.add('encima');
            }));

            ['dragleave', 'drop'].forEach(ev => zona.addEventListener(ev, function (e) {
                e.preventDefault();
                zona.classList.remove('encima');
            }));

            zona.addEventListener('drop', function (e) {
                if (e.dataTransfer?.files?.length) sumar(e.dataTransfer.files);
            });

            // En el "change" el input ya trae solo lo recién elegido: se suma
            // a lo anterior y se vuelve a armar la lista completa.
            input.addEventListener('change', function () {
                sumar(input.files);
            });

            pintar();

            return { input, pintar };
        }

        /* ==========================================================
           Video de una pieza

           Se sube en pedazos de 4MB para que un archivo pesado no truene
           la carga. El servidor regresa la ruta ya ensamblada y eso es lo
           único que viaja en el submit.
        ========================================================== */
        async function subirVideo(file, destino) {
            const { pathInput, progresoWrap, barra, texto, error, zona, cuenta } = destino;

            error.hidden = true;
            pathInput.value = '';

            const extension = (file.name.split('.').pop() || '').toLowerCase();

            if (!EXTENSIONES_VIDEO.includes(extension)) {
                error.textContent = 'Formato de video no permitido. Usa MP4, MOV o WEBM.';
                error.hidden = false;
                return;
            }

            const uploadId = (crypto.randomUUID
                ? crypto.randomUUID()
                : (Date.now() + '-' + Math.random().toString(36).slice(2))).replace(/[^a-zA-Z0-9-]/g, '');

            const total = Math.max(1, Math.ceil(file.size / CHUNK_SIZE));

            subiendoVideos++;
            progresoWrap.hidden = false;

            try {
                for (let index = 0; index < total; index++) {
                    const inicio = index * CHUNK_SIZE;
                    const pedazo = file.slice(inicio, inicio + CHUNK_SIZE);

                    const formData = new FormData();
                    formData.append('chunk', pedazo, 'chunk');
                    formData.append('upload_id', uploadId);
                    formData.append('index', index);
                    formData.append('total', total);
                    formData.append('extension', extension);

                    const respuesta = await fetch(@json(route('inventory.movimientos.videoChunk')), {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                        body: formData,
                    });

                    const json = await respuesta.json();

                    if (!respuesta.ok) {
                        throw new Error(json.message || 'No se pudo subir el video.');
                    }

                    const porcentaje = Math.round(((index + 1) / total) * 100);
                    barra.style.width = porcentaje + '%';
                    texto.textContent = 'Subiendo video... ' + porcentaje + '%';

                    if (json.status === 'listo') {
                        pathInput.value = json.video_path;
                        texto.textContent = 'Video subido correctamente.';
                        zona.classList.add('lleno');
                        if (cuenta) cuenta.textContent = 'Video listo · toca para cambiarlo';
                    }
                }
            } catch (err) {
                error.textContent = err.message || 'No se pudo subir el video. Vuelve a intentarlo.';
                error.hidden = false;
                pathInput.value = '';
                progresoWrap.hidden = true;
            } finally {
                subiendoVideos--;
                actualizarResumen();
            }
        }

        /* ==========================================================
           Un bloque por pieza: su serie, sus fotos y su video

           La evidencia es de cada pieza, no del lote: si llegaron 3, son 3
           juegos de fotos. Solo así se sabe después cuál venía golpeada.
        ========================================================== */
        const contenedorPiezas = document.getElementById('piezas-rows');
        let sugeridoBase = null;

        function crearPieza(i) {
            const previa = PREVIAS[i] || {};

            const card = document.createElement('div');
            card.className = 'pieza-card';
            card.dataset.pieza = i;

            card.innerHTML = `
                <div class="pieza-head">
                    <span class="pieza-num">Pieza #${i + 1}</span>
                    <span class="pieza-estado" data-estado>Falta su foto</span>
                </div>

                <input type="text" class="pieza-serie" name="unidades[${i}][no_serie]"
                       placeholder="No. de serie del fabricante (opcional)"
                       value="${(previa.no_serie || '').replace(/"/g, '&quot;')}">

                <div class="pieza-medios">
                    <div>
                        <label class="soltar soltar--chico" data-zona-fotos>
                            <span class="ico">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                            </span>
                            <span class="t">Fotos de esta pieza *</span>
                            <span class="d" data-cuenta>Hasta ${MAX_FOTOS} · toma una y luego otra</span>
                            <input type="file" name="unidades[${i}][evidencias][]" accept="image/*" multiple>
                        </label>
                        <div class="miniaturas" data-minis></div>
                    </div>

                    <div>
                        <label class="soltar soltar--chico" data-zona-video>
                            <span class="ico">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><polygon points="23 7 16 12 23 17 23 7"/><rect x="1" y="5" width="15" height="14" rx="2"/></svg>
                            </span>
                            <span class="t">Video de esta pieza</span>
                            <span class="d" data-cuenta>Opcional · MP4, MOV o WEBM</span>
                            <input type="file" accept="video/*" data-video-input>
                        </label>

                        <input type="hidden" name="unidades[${i}][video_path]" data-video-path
                               value="${(previa.video_path || '').replace(/"/g, '&quot;')}">

                        <div class="pieza-progreso" data-progreso hidden>
                            <div class="barra-progreso"><span data-barra></span></div>
                            <p class="campo-nota" style="margin:5px 0 0;" data-video-texto>Subiendo video...</p>
                        </div>

                        <p class="err" data-video-error hidden></p>
                    </div>
                </div>
            `;

            const zonaFotos = card.querySelector('[data-zona-fotos]');
            const minis = card.querySelector('[data-minis]');
            const estado = card.querySelector('[data-estado]');

            const fotos = zonaDeArchivos(zonaFotos, minis, {
                max: MAX_FOTOS,
                alCambiar: function (n) {
                    estado.textContent = n === 0
                        ? 'Falta su foto'
                        : (n === 1 ? '1 foto' : n + ' fotos');
                    estado.classList.toggle('listo', n > 0);
                    actualizarResumen();
                },
            });

            // Video de esta pieza
            const zonaVideo = card.querySelector('[data-zona-video]');
            const videoInput = card.querySelector('[data-video-input]');
            const pathInput = card.querySelector('[data-video-path]');
            const progreso = card.querySelector('[data-progreso]');
            const cuentaVideo = zonaVideo.querySelector('[data-cuenta]');

            const destino = {
                pathInput,
                progresoWrap: progreso,
                barra: card.querySelector('[data-barra]'),
                texto: card.querySelector('[data-video-texto]'),
                error: card.querySelector('[data-video-error]'),
                zona: zonaVideo,
                cuenta: cuentaVideo,
            };

            videoInput.addEventListener('change', function () {
                if (videoInput.files && videoInput.files[0]) {
                    subirVideo(videoInput.files[0], destino);
                }
            });

            // El video que ya estaba subido no se vuelve a pedir.
            if (pathInput.value) {
                zonaVideo.classList.add('lleno');
                cuentaVideo.textContent = 'Video ya subido · toca para cambiarlo';
            }

            card.__fotos = fotos;

            return card;
        }

        /*
        | Se agregan o quitan bloques solo por la diferencia: volver a
        | dibujarlos todos borraría las fotos ya elegidas de las piezas que
        | no cambiaron (y esas no se pueden recuperar).
        */
        window.pintarPiezas = function () {
            if (!contenedorPiezas) return;

            const cantidad = Math.max(1, parseInt(cantidadInput?.value || '1', 10) || 1);
            const bloques = contenedorPiezas.querySelectorAll('.pieza-card');

            if (cantidad === bloques.length) return;

            if (cantidad < bloques.length) {
                for (let i = bloques.length - 1; i >= cantidad; i--) {
                    bloques[i].remove();
                }
            } else {
                for (let i = bloques.length; i < cantidad; i++) {
                    contenedorPiezas.appendChild(crearPieza(i));
                }
            }

            actualizarResumen();
        };

        /* La serie sugerida por el servidor se reparte como secuencia. */
        function incrementarSerial(base, delta) {
            const m = /^(.*?)(\d+)$/.exec(base || '');
            if (!m) return '';
            return m[1] + String(parseInt(m[2], 10) + delta).padStart(m[2].length, '0');
        }

        /*
        | La serie sugerida (consecutiva a la última registrada del modelo) ya
        | no se escribe sola en las piezas: cada pieza lleva la serie que se le
        | capture. Si de verdad siguen el consecutivo, el botón llena solo las
        | que estén vacías.
        */
        const btnSugeridas = form.querySelector('[data-usar-sugeridas]');

        function mostrarSugerida() {
            if (!btnSugeridas) return;

            btnSugeridas.hidden = !sugeridoBase;
            if (sugeridoBase) btnSugeridas.textContent = 'Seguir consecutivo desde ' + sugeridoBase;
        }

        function aplicarSugerido() {
            if (!sugeridoBase || !contenedorPiezas) return 0;

            let siguiente = 0;

            seriesDeLasPiezas().forEach(function (input) {
                if (!input.value.trim()) input.value = incrementarSerial(sugeridoBase, siguiente++);
            });

            return siguiente;
        }

        btnSugeridas?.addEventListener('click', function () {
            const llenadas = aplicarSugerido();

            if (window.showToast) {
                window.showToast(llenadas
                    ? 'Se llenaron ' + llenadas + ' pieza(s) vacía(s) con el consecutivo.'
                    : 'Todas las piezas ya tienen serie.', llenadas ? 'ok' : 'err');
            }
        });

        function seriesDeLasPiezas() {
            return Array.from(contenedorPiezas?.querySelectorAll('.pieza-serie') || []);
        }

        /* ===================== Firma ===================== */
        const lienzo = document.getElementById('signature-pad');
        const firmaInput = document.getElementById('firma-input');
        const limpiarFirma = document.getElementById('limpiar-firma');

        if (lienzo && firmaInput) {
            const ctx = lienzo.getContext('2d');

            /*
            | La firma que el usuario registró una vez en su perfil se carga
            | sola: no tiene que volver a trazarla con el mouse en cada
            | entrada. Si prefiere firmar distinto, le da a "Limpiar firma".
            |
            | No se pisa lo que ya venga (old() tras un rechazo del servidor).
            */
            if (! firmaInput.value && lienzo.dataset.firmaRegistrada) {
                firmaInput.value = lienzo.dataset.firmaRegistrada;
            }

            function ajustarLienzo() {
                // Redimensionar limpia el trazo, así que se conserva y se
                // vuelve a pintar: cambiar de paso no debe borrar la firma.
                const previo = firmaInput.value;
                const rect = lienzo.getBoundingClientRect();

                if (!rect.width) return;

                lienzo.width = rect.width;
                lienzo.height = rect.height;
                ctx.lineWidth = 2;
                ctx.lineCap = 'round';
                ctx.strokeStyle = '#1a1a1a';

                if (previo) {
                    const img = new Image();
                    img.onload = () => ctx.drawImage(img, 0, 0, lienzo.width, lienzo.height);
                    img.src = previo;
                }
            }

            ajustarLienzo();
            window.addEventListener('resize', ajustarLienzo);
            // El paso de la firma arranca oculto: el lienzo mide 0 hasta que se abre.
            form.addEventListener('paso:cambio', function (e) {
                if (e.detail?.paso === 'firma') ajustarLienzo();
            });

            const guardar = () => { firmaInput.value = lienzo.toDataURL('image/png'); };
            const punto = (e) => {
                const r = lienzo.getBoundingClientRect();
                const t = e.touches ? e.touches[0] : e;
                return [t.clientX - r.left, t.clientY - r.top];
            };

            let firmando = false;

            const empezar = (e) => {
                if (e.touches) e.preventDefault();
                firmando = true;
                ctx.beginPath();
                ctx.moveTo(...punto(e));
            };

            const mover = (e) => {
                if (!firmando) return;
                if (e.touches) e.preventDefault();
                ctx.lineTo(...punto(e));
                ctx.stroke();
            };

            const terminar = () => { if (firmando) { firmando = false; guardar(); } };

            lienzo.addEventListener('mousedown', empezar);
            lienzo.addEventListener('mousemove', mover);
            lienzo.addEventListener('mouseup', terminar);
            lienzo.addEventListener('mouseout', terminar);
            lienzo.addEventListener('touchstart', empezar, { passive: false });
            lienzo.addEventListener('touchmove', mover, { passive: false });
            lienzo.addEventListener('touchend', terminar);

            /* Se puede quitar la firma registrada para firmar a mano, y
               volver a ponerla si fue sin querer. */
            const avisoFirma = form.querySelector('[data-firma-aviso]');
            const usarFirma = form.querySelector('[data-usar-firma]');

            function alternarAvisos(cargada) {
                if (avisoFirma) avisoFirma.style.display = cargada ? '' : 'none';
                if (usarFirma) usarFirma.style.display = cargada ? 'none' : '';
            }

            if (limpiarFirma) {
                limpiarFirma.addEventListener('click', function (e) {
                    e.preventDefault();
                    ctx.clearRect(0, 0, lienzo.width, lienzo.height);
                    firmaInput.value = '';
                    alternarAvisos(false);
                });
            }

            if (usarFirma) {
                usarFirma.addEventListener('click', function (e) {
                    e.preventDefault();
                    firmaInput.value = lienzo.dataset.firmaRegistrada || '';
                    ajustarLienzo();
                    alternarAvisos(true);
                });
            }
        }

        /* ===================== Antes de enviar =====================
           Lo que falte (fotos, firma...) lo revisa y lo dice la validación
           por paso; aquí solo se cuida no enviar a media subida. */
        form.addEventListener('submit', function (e) {
            if (subiendoVideos > 0) {
                e.preventDefault();
                window.showToast?.('Espera a que terminen de subirse los videos.', 'err');
            }
        });

        /* ==========================================================
           Si el modelo ya está registrado, se rellena lo que se sabe.
        ========================================================== */
        const modeloSelect = document.getElementById('equipment_model_id');
        const aviso = document.getElementById('modeloExistenteAviso');
        const precioInput = document.getElementById('precio');
        const descripcionInput = document.getElementById('descripcion');
        const imagenActualWrap = document.getElementById('imagen-actual-wrap');
        const imagenActual = document.getElementById('imagen-actual');
        const buscarPorModeloUrl = @json(route('inventory.productos.buscarPorModelo'));

        /* ==========================================================
           Precio de venta

           Es el precio en el que se vende el equipo, no lo que costó: el
           sistema no guarda costos. De aquí lo toman las cotizaciones.

           Si el modelo ya lo tiene, no se vuelve a preguntar: se muestra
           el que hay. Para quien no puede ver precios el campo ni
           siquiera existe en el HTML, así que todo esto no aplica.
        ========================================================== */
        const campoPrecio = form.querySelector('[data-campo-precio]');
        const cajaPrecio = form.querySelector('[data-precio-fijo]');
        const valorPrecio = form.querySelector('[data-precio-valor]');
        const notaPrecio = form.querySelector('[data-precio-nota]');
        const btnCambiarPrecio = form.querySelector('[data-precio-cambiar]');

        function pedirPrecio(nota) {
            if (!campoPrecio) return;
            campoPrecio.style.display = '';
            if (cajaPrecio) cajaPrecio.style.display = 'none';
            if (precioInput) precioInput.disabled = false;
            if (notaPrecio) notaPrecio.textContent = nota || '';
        }

        function mostrarPrecio(data) {
            if (!campoPrecio) return;

            if (!data.tiene_precio) {
                if (precioInput) precioInput.value = '';
                pedirPrecio('Este modelo todavía no tiene precio de venta. Ponlo aquí y queda para las siguientes entradas y cotizaciones.');
                return;
            }

            // Ya hay precio: se enseña y el campo se retira del formulario
            // para que no lo pise sin querer.
            campoPrecio.style.display = 'none';
            if (precioInput) { precioInput.value = ''; precioInput.disabled = true; }
            if (cajaPrecio) cajaPrecio.style.display = '';
            if (valorPrecio) valorPrecio.textContent = data.precio_texto ?? '';
        }

        if (btnCambiarPrecio) {
            btnCambiarPrecio.addEventListener('click', function () {
                pedirPrecio('Vas a cambiar el precio de venta del modelo, no solo el de esta entrada. Las cotizaciones nuevas lo tomarán de aquí.');
                if (precioInput) precioInput.focus();
            });
        }

        if (modeloSelect) {
            modeloSelect.addEventListener('change', function () {
                aviso.style.display = 'none';
                imagenActualWrap.style.display = 'none';
                sugeridoBase = null;
                mostrarSugerida();

                if (!modeloSelect.value) return;

                fetch(buscarPorModeloUrl + '?equipment_model_id=' + encodeURIComponent(modeloSelect.value), {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                })
                    .then(r => r.json())
                    .then(data => {
                        if (!data.existe) return;

                        mostrarPrecio(data);

                        if (descripcionInput && !descripcionInput.value) descripcionInput.value = data.descripcion ?? '';

                        let mensaje = 'Este modelo ya está registrado (stock actual: ' + data.stock_actual
                            + '). Lo que llegue se agrega a esa misma fila. Se completó la descripción.';

                        if (data.ve_precio) {
                            mensaje += data.tiene_precio
                                ? ' Ya tiene precio, no hace falta capturarlo.'
                                : ' Todavía no tiene precio: captúralo abajo.';
                        }

                        sugeridoBase = data.no_serie_sugerido || null;
                        mostrarSugerida();

                        if (sugeridoBase) {
                            mensaje += ' La última serie registrada sigue con ' + sugeridoBase
                                + '; si estas piezas siguen ese consecutivo, usa el botón «Seguir consecutivo» en el paso de piezas.';
                        }

                        // La referencia es del modelo: se completa si no se escribió otra.
                        const referenciaInput = document.getElementById('referencia');
                        if (referenciaInput && !referenciaInput.value && data.referencia) {
                            referenciaInput.value = data.referencia;
                            mensaje += ' Se completó la referencia.';
                        }

                        if (data.imagen) {
                            imagenActual.src = data.imagen;
                            imagenActualWrap.style.display = 'block';
                            mensaje += ' Ya tiene foto de catálogo; sube otra solo si quieres cambiarla.';
                        } else {
                            mensaje += ' Todavía no tiene foto de catálogo, considera subir una.';
                        }

                        aviso.textContent = mensaje;
                        aviso.style.display = 'block';
                    })
                    .catch(() => {});
            });
        }

        /* ==========================================================
           Generar series propias

           Mucho equipo llega sin serial de fábrica. La serie se arma con
           el catálogo elegido más un consecutivo, y el servidor es quien
           lo calcula para que no choque con las que ya existen.
        ========================================================== */
        const btnGenerar = form.querySelector('[data-generar-series]');
        const notaGenerar = form.querySelector('[data-generar-nota]');

        function prefijoALaVista() {
            // El tipo ya no forma parte de la serie (GeneradorDeSeries).
            const nombres = ['subtype_id', 'brand_id', 'equipment_model_id']
                .map(id => {
                    const s = document.getElementById(id);
                    return s?.value ? s.options[s.selectedIndex]?.text : null;
                })
                .filter(Boolean);

            return nombres.length ? nombres.join(' · ') : null;
        }

        function pintarNotaGenerar() {
            if (!notaGenerar) return;

            const visto = prefijoALaVista();

            notaGenerar.textContent = visto
                ? 'con ' + visto + ' y un consecutivo'
                : 'sin subtipo, marca ni modelo quedan como EQ-0001, EQ-0002…';
        }

        ['equipment_type_id', 'subtype_id', 'brand_id', 'equipment_model_id'].forEach(function (id) {
            document.getElementById(id)?.addEventListener('change', pintarNotaGenerar);
        });

        if (btnGenerar) {
            pintarNotaGenerar();

            btnGenerar.addEventListener('click', async function () {
                const tipo = document.getElementById('equipment_type_id');

                if (!tipo?.value) {
                    window.showToast?.('Elige primero el tipo de equipo, en el paso anterior.', 'err');
                    form.dispatchEvent(new CustomEvent('paso:ir', { detail: { paso: 'equipo' } }));
                    return;
                }

                // Solo se llenan las piezas sin serie: la que ya tiene la suya
                // la conserva. Si todas tienen, se pregunta antes de pisarlas.
                let inputsSerie = seriesDeLasPiezas().filter(i => !i.value.trim());

                if (!inputsSerie.length) {
                    if (!(await window.confirmModal({ message: 'Todas las piezas ya tienen serie. ¿Reemplazarlas por series generadas?' }))) {
                        return;
                    }

                    inputsSerie = seriesDeLasPiezas();
                }

                const cuantas = Math.max(1, inputsSerie.length);

                btnGenerar.disabled = true;
                const textoPrevio = btnGenerar.textContent;
                btnGenerar.textContent = 'Generando…';

                try {
                    const r = await fetch(@json(route('inventory.productos.generarSeries')), {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrf,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({
                            equipment_type_id: tipo.value || null,
                            subtype_id: document.getElementById('subtype_id')?.value || null,
                            brand_id: document.getElementById('brand_id')?.value || null,
                            equipment_model_id: document.getElementById('equipment_model_id')?.value || null,
                            cantidad: cuantas,
                        }),
                    });

                    const data = await r.json();

                    if (!r.ok || !data.series) {
                        throw new Error(data.message || 'No se pudieron generar.');
                    }

                    inputsSerie.forEach(function (input, i) {
                        if (data.series[i]) input.value = data.series[i];
                    });

                    if (notaGenerar) {
                        notaGenerar.textContent = `se generaron ${data.series.length} con el prefijo ${data.prefijo}; puedes cambiar la de cualquier pieza`;
                    }
                } catch (e) {
                    window.showToast?.(e.message || 'No se pudieron generar las series. Intenta de nuevo.', 'err');
                } finally {
                    btnGenerar.disabled = false;
                    btnGenerar.textContent = textoPrevio;
                }
            });
        }

        /* ===================== Resumen del último paso ===================== */
        function esUsado() {
            return form.querySelector('[data-condicion]:checked')?.value === 'usado';
        }

        function actualizarResumen() {
            const usado = esUsado();
            const n = parseInt(cantidadInput?.value || '1', 10) || 1;

            // Las fotos y los videos se cuentan de todas las piezas juntas,
            // pero lo que importa es que ninguna se haya quedado sin foto.
            const bloques = Array.from(contenedorPiezas?.querySelectorAll('.pieza-card') || []);
            let fotos = 0;
            let sinFoto = 0;
            let videos = 0;

            bloques.forEach(function (card) {
                const cuantas = card.querySelector('input[type=file][multiple]')?.files.length || 0;
                fotos += cuantas;
                if (cuantas === 0) sinFoto++;
                if (card.querySelector('[data-video-path]')?.value) videos++;
            });

            const poner = (llave, texto) => {
                const el = form.querySelector(`[data-res-${llave}]`);
                if (el) el.textContent = texto;
            };

            poner('condicion', usado ? 'Usado' : 'Nuevo');
            poner('cantidad', n === 1 ? '1 pieza' : n + ' piezas');
            poner('estado', usado ? 'En revisión' : 'Disponible');
            poner('evidencia', sinFoto > 0
                ? `Faltan las fotos de ${sinFoto} pieza(s)`
                : `${fotos} foto(s)` + (videos ? ` y ${videos} video(s)` : ', sin video'));
        }

        window.actualizarResumenEntrada = actualizarResumen;

        /* ===================== Arranque ===================== */
        window.pintarPiezas();
        actualizarResumen();
    });
    </script>
@endpush
