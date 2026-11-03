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
            let cola = Promise.resolve();

            function vistaPrevia(url) {
                const ext = extension(new URL(url, location.href).pathname);
                if (!['heic', 'heif'].includes(ext)) return Promise.resolve(url);
                if (vistas.has(url)) return vistas.get(url);

                const tarea = cola.then(async function () {
                    const respuesta = await fetch(url);
                    if (!respuesta.ok) throw new Error('No se pudo cargar la foto original.');
                    const archivo = new File([await respuesta.blob()], 'foto.' + ext, { type: 'image/' + ext });
                    const compatible = await preparar(archivo);
                    if (compatible === archivo) throw new Error('No se pudo preparar la vista previa HEIC.');
                    return URL.createObjectURL(compatible);
                });
                cola = tarea.catch(() => {});
                vistas.set(url, tarea);
                tarea.catch(() => { vistas.delete(url); });
                return tarea;
            }

            window.addEventListener('pagehide', function (evento) {
                if (evento.persisted) return;
                vistas.forEach(tarea => tarea.then(url => URL.revokeObjectURL(url)).catch(() => {}));
                vistas.clear();
            });

            function cargarMiniaturas(raiz) {
                const fotos = raiz.matches?.('[data-foto-heic]') ? [raiz] : Array.from(raiz.querySelectorAll?.('[data-foto-heic]') || []);
                fotos.forEach(function (img) {
                    if (img.dataset.preparando) return;
                    img.dataset.preparando = '1';
                    const estado = img.parentElement.querySelector('[data-foto-estado]');
                    vistaPrevia(img.dataset.fotoHeic).then(function (url) {
                        img.src = url;
                        img.style.display = 'block';
                        if (estado) estado.style.display = 'none';
                    }).catch(function () {
                        if (estado) estado.textContent = 'HEIC · Abrir evidencia';
                    });
                });
            }

            cargarMiniaturas(document);
            new MutationObserver(cambios => cambios.forEach(cambio => cambio.addedNodes.forEach(nodo => {
                if (nodo.nodeType === 1) cargarMiniaturas(nodo);
            }))).observe(document.body, { childList: true, subtree: true });

            return { PEDAZO, ACEPTA, urlDisco, extension, seVe, preparar, subir, vistaPrevia };
        })();
        </script>
    @endpush
@endonce
