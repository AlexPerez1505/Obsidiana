{{--
    Subida de fotos por pedazos, compartida por la entrada y los procesos.

    Cada foto se sube en cuanto se elige, en pedazos de 4 MB: así no hay
    límite de tamaño y se acepta cualquier formato de imagen. Las HEIC del
    iPhone se convierten a JPG aquí mismo para que se vean en cualquier
    pantalla; lo demás se sube tal como está. El servidor regresa la ruta de
    la foto ya armada y eso es lo único que viaja en el formulario.

    Uso: ObsFotos.subir(archivo, urlDelChunk, pct => ...) → { foto_path, url }
--}}
@once
    @push('scripts')
        <script>
        window.ObsFotos = (function () {
            const PEDAZO = 4 * 1024 * 1024;
            const SE_VEN = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif', 'bmp', 'jfif'];
            const ACEPTA = 'image/*,.heic,.heif,.tif,.tiff,.dng,.cr2,.cr3,.nef,.arw,.orf,.rw2,.raf,.srw,.pef,.avif,.jxl,.jfif,.webp,.bmp,.psd';
            const csrf = document.querySelector('meta[name="csrf-token"]')?.content || @json(csrf_token());
            const urlDisco = @json(rtrim(\Illuminate\Support\Facades\Storage::disk(config('filesystems.fotos_disk', 'public'))->url(''), '/') . '/');

            function extension(nombre) {
                return (String(nombre).split('.').pop() || '').toLowerCase().replace(/[^a-z0-9]/g, '').slice(0, 5) || 'jpg';
            }

            function seVe(nombre) {
                return SE_VEN.includes(extension(nombre));
            }

            let convertidorHeic = null;
            function cargarConvertidorHeic() {
                convertidorHeic ??= new Promise(function (ok, falla) {
                    const s = document.createElement('script');
                    s.src = 'https://cdn.jsdelivr.net/npm/heic-to@1.6.6/dist/iife/heic-to.js';
                    s.onload = () => ok(window.HeicTo);
                    s.onerror = () => { convertidorHeic = null; falla(new Error('No se pudo cargar el convertidor de HEIC.')); };
                    document.head.appendChild(s);
                });
                return convertidorHeic;
            }

            /* HEIC/HEIF → JPG. Si no se puede convertir, se sube el original. */
            async function preparar(archivo) {
                if (!['heic', 'heif'].includes(extension(archivo.name)) && !/image\/hei[cf]/.test(archivo.type)) return archivo;

                try {
                    const convertir = await cargarConvertidorHeic();
                    const blob = await convertir({ blob: archivo, type: 'image/jpeg', quality: 0.92 });
                    const unico = Array.isArray(blob) ? blob[0] : blob;
                    return new File([unico], archivo.name.replace(/\.[^.]+$/, '') + '.jpg', { type: 'image/jpeg' });
                } catch (e) {
                    return archivo;
                }
            }

            async function subir(archivo, url, alAvanzar) {
                const ext = extension(archivo.name);
                const id = (crypto.randomUUID
                    ? crypto.randomUUID()
                    : (Date.now() + '-' + Math.random().toString(36).slice(2))).replace(/[^a-zA-Z0-9-]/g, '');
                const total = Math.max(1, Math.ceil(archivo.size / PEDAZO));

                for (let i = 0; i < total; i++) {
                    const datos = new FormData();
                    datos.append('chunk', archivo.slice(i * PEDAZO, (i + 1) * PEDAZO), 'chunk');
                    datos.append('upload_id', id);
                    datos.append('index', i);
                    datos.append('total', total);
                    datos.append('extension', ext);

                    const r = await fetch(url, {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                        body: datos,
                    });
                    const json = await r.json().catch(() => ({}));

                    if (!r.ok) throw new Error(json.message || 'No se pudo subir la foto.');

                    if (alAvanzar) alAvanzar(Math.round(((i + 1) / total) * 100));
                    if (json.status === 'listo') return json;
                }

                throw new Error('No se pudo subir la foto.');
            }

            const vistas = new Map();
            const contenidos = new Map();
            let cola = Promise.resolve();
            let cacheHeic;

            function abrirCacheHeic() {
                cacheHeic ??= window.caches
                    ? window.caches.open('obs-heic-v1').catch(() => null)
                    : Promise.resolve(null);
                return cacheHeic;
            }

            async function convertirVista(blob, ext) {
                const hash = window.crypto?.subtle
                    ? Array.from(new Uint8Array(await crypto.subtle.digest('SHA-256', await blob.arrayBuffer())), n => n.toString(16).padStart(2, '0')).join('')
                    : null;
                if (hash && contenidos.has(hash)) return contenidos.get(hash);
                const conversion = cola.then(async function () {
                    const archivo = new File([blob], 'foto.' + ext, { type: 'image/' + ext });
                    const compatible = await preparar(archivo);
                    if (compatible === archivo) throw new Error('No se pudo preparar la vista previa HEIC.');
                    return compatible;
                });
                cola = conversion.catch(() => {});
                if (hash) {
                    contenidos.set(hash, conversion);
                    conversion.catch(() => contenidos.delete(hash));
                }
                return conversion;
            }

            function vistaPrevia(url) {
                const direccion = new URL(url, location.href);
                const ext = extension(direccion.pathname);
                if (!['heic', 'heif'].includes(ext)) return Promise.resolve(url);
                direccion.hash = '';
                const clave = direccion.href;
                if (vistas.has(clave)) return vistas.get(clave);

                const tarea = (async function () {
                    const cache = direccion.origin === location.origin ? await abrirCacheHeic() : null;
                    if (cache) {
                        try {
                            const guardada = await cache.match(clave);
                            if (guardada && Date.now() - Number(guardada.headers.get('X-Preview-Fecha')) < 7 * 86400000) {
                                return URL.createObjectURL(await guardada.blob());
                            }
                        } catch (e) {}
                    }
                    const respuesta = await fetch(url);
                    if (!respuesta.ok) throw new Error('No se pudo cargar la foto original.');
                    const compatible = await convertirVista(await respuesta.blob(), ext);
                    if (cache && compatible.size <= 8 * 1024 * 1024) {
                        try {
                            await cache.put(clave, new Response(compatible, { headers: {
                                'Content-Type': 'image/jpeg', 'X-Preview-Fecha': String(Date.now()),
                            } }));
                            const claves = await cache.keys();
                            await Promise.all(claves.slice(0, Math.max(0, claves.length - 24)).map(antigua => cache.delete(antigua)));
                        } catch (e) {}
                    }
                    return URL.createObjectURL(compatible);
                })();
                vistas.set(clave, tarea);
                tarea.catch(() => { vistas.delete(clave); });
                return tarea;
            }

            window.addEventListener('pagehide', function (evento) {
                if (evento.persisted) return;
                vistas.forEach(tarea => tarea.then(url => URL.revokeObjectURL(url)).catch(() => {}));
                vistas.clear();
            });

            const miniaturas = new WeakMap();

            function cargarMiniaturas(raiz) {
                const fotos = raiz.matches?.('img') ? [raiz] : Array.from(raiz.querySelectorAll?.('img') || []);
                fotos.forEach(function (img) {
                    const origen = img.getAttribute('src') || img.dataset.fotoHeic;
                    if (!origen) return;
                    let ext;
                    try { ext = extension(new URL(origen, location.href).pathname); } catch (e) { return; }
                    const anterior = miniaturas.get(img);
                    if (!['heic', 'heif'].includes(ext)) {
                        if (anterior && !origen.startsWith('blob:')) {
                            anterior.estado.style.display = 'none';
                            img.style.display = anterior.display;
                            miniaturas.delete(img);
                            delete img.dataset.fotoHeic;
                        }
                        return;
                    }
                    if (anterior?.origen === origen) return;
                    const estado = anterior?.estado
                        || (img.nextElementSibling?.matches('[data-foto-estado]') ? img.nextElementSibling : document.createElement('span'));
                    estado.dataset.fotoEstado = '';
                    estado.textContent = 'Preparando foto…';
                    estado.style.cssText = 'display:block; padding:6px; font-size:11px;';
                    if (!estado.parentElement) img.after(estado);
                    const actual = { origen, estado, display: anterior?.display ?? (img.style.display === 'none' ? 'block' : img.style.display) };
                    miniaturas.set(img, actual);
                    img.dataset.fotoHeic = origen;
                    img.removeAttribute('src');
                    img.style.display = 'none';
                    vistaPrevia(origen).then(function (url) {
                        if (miniaturas.get(img) !== actual || img.getAttribute('src')) return;
                        img.src = url;
                        img.style.display = actual.display;
                        estado.style.display = 'none';
                    }).catch(function () {
                        if (miniaturas.get(img) === actual) estado.textContent = 'HEIC · Vista previa no disponible';
                    });
                });
            }

            cargarMiniaturas(document);
            new MutationObserver(cambios => cambios.forEach(cambio => {
                if (cambio.type === 'attributes') cargarMiniaturas(cambio.target);
                cambio.addedNodes.forEach(nodo => {
                    if (nodo.nodeType === 1) cargarMiniaturas(nodo);
                });
            })).observe(document.body, { childList: true, subtree: true, attributes: true, attributeFilter: ['src'] });

            return { PEDAZO, ACEPTA, urlDisco, extension, seVe, preparar, subir, vistaPrevia };
        })();
        </script>
    @endpush
@endonce
