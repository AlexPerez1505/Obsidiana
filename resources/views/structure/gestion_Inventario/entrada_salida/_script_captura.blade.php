@push('scripts')
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('form-entrada');
        if (!form) return;

        const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';

        // Se declaran arriba porque el resumen las lee, y el resumen corre
        // desde las zonas de archivo, que se arman antes que el resto.
        const cantidadInput = document.getElementById('cantidad');
        const videoPathInput = document.getElementById('video-path-input');

        /* ==========================================================
           Zona para soltar archivos

           Antes era un <input type="file"> pelón: no se veía lo que ya
           habías elegido y no se podía quitar una foto sin volver a
           elegirlas todas.
        ========================================================== */
        function zonaDeArchivos(nombre, { max = null, alQuitar = null, alElegir = null } = {}) {
            const zona = form.querySelector(`[data-soltar="${nombre}"]`);
            if (!zona) return null;

            const input = zona.querySelector('input[type=file]');
            const minis = form.querySelector(`[data-miniaturas="${nombre}"]`);
            const cuenta = zona.querySelector(`[data-cuenta-${nombre}]`);
            const textoBase = cuenta ? cuenta.textContent : '';
            let primeraPintada = true;

            function pintar() {
                minis.innerHTML = '';

                Array.from(input.files || []).forEach(function (archivo, i) {
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

                const n = input.files ? input.files.length : 0;
                zona.classList.toggle('lleno', n > 0);

                if (cuenta) {
                    cuenta.textContent = n === 0
                        ? textoBase
                        : (n === 1 ? '1 archivo elegido' : n + ' archivos elegidos')
                          + (max ? ' de ' + max : '') + ' · toca para cambiar';
                }

                if (alElegir && !primeraPintada) alElegir(n);
            }

            // Un FileList no se puede editar: se arma uno nuevo sin el que
            // se quitó y se le asigna al input.
            function quitarArchivo(indice) {
                const dt = new DataTransfer();
                Array.from(input.files).forEach((a, i) => { if (i !== indice) dt.items.add(a); });
                input.files = dt.files;
                pintar();
                if (alQuitar) alQuitar(input.files.length);
            }

            function recibir(lista) {
                const dt = new DataTransfer();
                Array.from(lista).slice(0, max || lista.length).forEach(a => dt.items.add(a));
                input.files = dt.files;
                input.dispatchEvent(new Event('change', { bubbles: true }));
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
                if (e.dataTransfer?.files?.length) recibir(e.dataTransfer.files);
            });

            input.addEventListener('change', pintar);
            pintar();
            primeraPintada = false;

            return { input, pintar, zona };
        }

        /* ===================== Fotos de evidencia ===================== */
        const errorFotos = document.getElementById('evidencias-error');

        const fotos = zonaDeArchivos('fotos', {
            max: 3,
            alElegir: function (n) {
                if (errorFotos) errorFotos.style.display = n > 3 ? 'block' : 'none';
                actualizarResumen();
            },
            alQuitar: actualizarResumen,
        });

        if (fotos) {
            fotos.input.addEventListener('change', function () {
                // El navegador permite elegir más de 3 desde el diálogo:
                // se recorta aquí para no llegar al servidor con un error.
                if (fotos.input.files.length > 3) {
                    const dt = new DataTransfer();
                    Array.from(fotos.input.files).slice(0, 3).forEach(a => dt.items.add(a));
                    fotos.input.files = dt.files;
                    if (errorFotos) errorFotos.style.display = 'block';
                    fotos.pintar();
                }
            });
        }

        /* ==========================================================
           Video: se sube en pedazos de 4MB para no mandar el archivo
           completo de golpe. El servidor regresa la ruta ya ensamblada y
           eso es lo único que viaja en el submit.
        ========================================================== */
        const videoInput = document.getElementById('evidencia_video');
        const videoProgresoWrap = document.getElementById('video-progreso-wrap');
        const videoProgresoBarra = document.getElementById('video-progreso-barra');
        const videoProgresoTexto = document.getElementById('video-progreso-texto');
        const videoError = document.getElementById('video-error');
        const CHUNK_SIZE = 4 * 1024 * 1024;
        const EXTENSIONES_VALIDAS = ['mp4', 'mov', 'm4v', 'webm'];
        let videoSubiendo = false;

        const video = zonaDeArchivos('video', { max: 1, alElegir: actualizarResumen });

        function bloquearEnvio(bloquear) {
            form.querySelectorAll('button[type="submit"]').forEach(b => { b.disabled = bloquear; });
        }

        async function subirVideoPorChunks(file) {
            videoError.style.display = 'none';
            videoPathInput.value = '';

            const extension = (file.name.split('.').pop() || '').toLowerCase();

            if (!EXTENSIONES_VALIDAS.includes(extension)) {
                videoError.textContent = 'Formato de video no permitido. Usa MP4, MOV o WEBM.';
                videoError.style.display = 'block';
                videoInput.value = '';
                if (video) video.pintar();
                return;
            }

            const uploadId = (crypto.randomUUID
                ? crypto.randomUUID()
                : (Date.now() + '-' + Math.random().toString(36).slice(2))).replace(/[^a-zA-Z0-9-]/g, '');

            const total = Math.max(1, Math.ceil(file.size / CHUNK_SIZE));

            videoSubiendo = true;
            bloquearEnvio(true);
            videoProgresoWrap.style.display = 'block';

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
                    videoProgresoBarra.style.width = porcentaje + '%';
                    videoProgresoTexto.textContent = 'Subiendo video... ' + porcentaje + '%';

                    if (json.status === 'listo') {
                        videoPathInput.value = json.video_path;
                        videoProgresoTexto.textContent = 'Video subido correctamente.';
                    }
                }
            } catch (err) {
                videoError.textContent = err.message || 'No se pudo subir el video. Vuelve a intentarlo.';
                videoError.style.display = 'block';
                videoPathInput.value = '';
                videoProgresoWrap.style.display = 'none';
            } finally {
                videoSubiendo = false;
                bloquearEnvio(false);
                actualizarResumen();
            }
        }

        if (videoInput) {
            videoInput.addEventListener('change', function () {
                if (videoInput.files && videoInput.files[0]) {
                    subirVideoPorChunks(videoInput.files[0]);
                }
            });
        }

        /* ===================== Firma ===================== */
        const lienzo = document.getElementById('signature-pad');
        const firmaInput = document.getElementById('firma-input');
        const limpiarFirma = document.getElementById('limpiar-firma');

        if (lienzo && firmaInput) {
            const ctx = lienzo.getContext('2d');

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

            if (limpiarFirma) {
                limpiarFirma.addEventListener('click', function (e) {
                    e.preventDefault();
                    ctx.clearRect(0, 0, lienzo.width, lienzo.height);
                    firmaInput.value = '';
                });
            }
        }

        /* ===================== Antes de enviar ===================== */
        form.addEventListener('submit', function (e) {
            if (videoSubiendo) {
                e.preventDefault();
                alert('Espera a que termine de subirse el video.');
                return;
            }

            if (!videoPathInput.value) {
                e.preventDefault();
                alert('Sube el video de verificación antes de registrar la entrada.');
                form.dispatchEvent(new CustomEvent('paso:ir', { detail: { paso: 'evidencia' } }));
                return;
            }

            if (firmaInput && !firmaInput.value) {
                e.preventDefault();
                alert('Firma en el recuadro antes de registrar la entrada.');
                form.dispatchEvent(new CustomEvent('paso:ir', { detail: { paso: 'firma' } }));
            }
        });

        /* ==========================================================
           Renglones de captura una por una
        ========================================================== */
        const unidadesRows = document.getElementById('unidades-rows');
        const notaUnidades = form.querySelector('[data-nota-unidades]');
        let sugeridoBase = null;

        function incrementarSerial(base, delta) {
            const m = /^(.*?)(\d+)$/.exec(base || '');
            if (!m) return '';
            return m[1] + String(parseInt(m[2], 10) + delta).padStart(m[2].length, '0');
        }

        function esUsado() {
            return form.querySelector('[data-condicion]:checked')?.value === 'usado';
        }

        window.pintarUnidades = function () {
            if (!unidadesRows) return;

            const cantidad = Math.max(0, parseInt(cantidadInput?.value || '0', 10) || 0);
            const fotoObligatoria = esUsado();

            if (notaUnidades) {
                notaUnidades.innerHTML = fotoObligatoria
                    ? 'Un renglón por pieza. En equipo usado <b>la foto de cada pieza es obligatoria</b>; el número de serie es opcional.'
                    : 'Un renglón por pieza. Serie y foto son opcionales: aun sin capturarlas, cada pieza recibe su etiqueta con QR.';
            }

            // Se vuelve a pintar solo si cambió el número de renglones, para
            // no borrar lo que ya se capturó al cambiar de paso.
            if (cantidad === unidadesRows.querySelectorAll('.unidad-row').length) {
                unidadesRows.querySelectorAll('input[type=file]').forEach(i => { i.required = fotoObligatoria; });
                return;
            }

            unidadesRows.innerHTML = '';

            for (let i = 0; i < cantidad; i++) {
                const row = document.createElement('div');
                row.className = 'unidad-row';
                const sugerido = sugeridoBase ? incrementarSerial(sugeridoBase, i) : '';

                row.innerHTML = `
                    <span class="unidad-num">#${i + 1}</span>
                    <input type="text" name="unidades[${i}][no_serie]" placeholder="No. de serie (opcional)" value="${sugerido}">
                    <div>
                        <input type="file" name="unidades[${i}][foto]" accept="image/*" data-preview="foto-preview-${i}" ${fotoObligatoria ? 'required' : ''}>
                        <img id="foto-preview-${i}" class="unidad-foto-preview" alt="Vista previa">
                    </div>
                `;

                unidadesRows.appendChild(row);
            }

            unidadesRows.querySelectorAll('input[type=file]').forEach(function (input) {
                input.addEventListener('change', function () {
                    const preview = document.getElementById(input.dataset.preview);
                    if (!preview || !input.files || !input.files[0]) return;
                    preview.src = URL.createObjectURL(input.files[0]);
                    preview.style.display = 'block';
                });
            });
        };

        /* ==========================================================
           Si el modelo ya está registrado, se rellena lo que se sabe.
        ========================================================== */
        const modeloSelect = document.getElementById('equipment_model_id');
        const aviso = document.getElementById('modeloExistenteAviso');
        const precioInput = document.getElementById('precio');
        const descripcionInput = document.getElementById('descripcion');
        const seriesTextoInput = document.getElementById('series_texto');
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

                        if (seriesTextoInput && !seriesTextoInput.value && data.no_serie_sugerido) {
                            seriesTextoInput.value = data.no_serie_sugerido;
                            mensaje += ' La serie se sugirió como ' + data.no_serie_sugerido + ' (consecutivo del último registrado).';
                        }

                        if (data.es_serializado) {
                            const modoUnidades = form.querySelector('[data-modo][value="unidades"]');
                            if (modoUnidades && !form.querySelector('[data-modo]:checked')?.value.match(/series|unidades/)) {
                                modoUnidades.checked = true;
                                modoUnidades.dispatchEvent(new Event('change', { bubbles: true }));
                            }
                            mensaje += ' Este modelo ya se maneja pieza por pieza.';
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
            const nombres = ['equipment_type_id', 'subtype_id', 'brand_id', 'equipment_model_id']
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
                ? 'Se armarán con: ' + visto + '.'
                : 'Elige al menos el tipo de equipo en el paso anterior.';
        }

        ['equipment_type_id', 'subtype_id', 'brand_id', 'equipment_model_id'].forEach(function (id) {
            document.getElementById(id)?.addEventListener('change', pintarNotaGenerar);
        });

        if (btnGenerar) {
            pintarNotaGenerar();

            btnGenerar.addEventListener('click', async function () {
                const tipo = document.getElementById('equipment_type_id');

                if (!tipo?.value) {
                    alert('Elige primero el tipo de equipo, en el paso anterior.');
                    form.dispatchEvent(new CustomEvent('paso:ir', { detail: { paso: 'equipo' } }));
                    return;
                }

                const cuantas = Math.max(1, parseInt(cantidadInput?.value || '1', 10) || 1);

                // Lo capturado no se pisa sin avisar.
                if (seriesTextoInput?.value.trim() && !confirm('Ya hay series capturadas. ¿Reemplazarlas por las generadas?')) {
                    return;
                }

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

                    seriesTextoInput.value = data.series.join('\n');

                    if (notaGenerar) {
                        notaGenerar.textContent = `Se generaron ${data.series.length} con el prefijo ${data.prefijo}.`;
                    }
                } catch (e) {
                    alert(e.message || 'No se pudieron generar las series. Intenta de nuevo.');
                } finally {
                    btnGenerar.disabled = false;
                    btnGenerar.textContent = textoPrevio;
                }
            });
        }

        /* ===================== Resumen del último paso ===================== */
        function actualizarResumen() {
            const usado = esUsado();
            const n = parseInt(cantidadInput?.value || '1', 10) || 1;
            const nFotos = fotos?.input.files.length || 0;

            const poner = (llave, texto) => {
                const el = form.querySelector(`[data-res-${llave}]`);
                if (el) el.textContent = texto;
            };

            poner('condicion', usado ? 'Usado' : 'Nuevo');
            poner('cantidad', n === 1 ? '1 pieza' : n + ' piezas');
            poner('estado', usado ? 'En revisión' : 'Disponible');
            poner('evidencia', (nFotos === 1 ? '1 foto' : nFotos + ' fotos')
                + (videoPathInput?.value ? ' y video' : ', falta video'));
        }

        window.actualizarResumenEntrada = actualizarResumen;
        actualizarResumen();
    });
    </script>
@endpush
