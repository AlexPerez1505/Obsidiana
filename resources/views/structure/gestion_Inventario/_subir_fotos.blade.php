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
            const ACEPTA = 'image/*,.heic,.heif,.tif,.tiff,.dng,.cr2,.cr3,.nef,.arw,.orf,.rw2,.raf';
            const csrf = document.querySelector('meta[name="csrf-token"]')?.content || @json(csrf_token());
            const urlDisco = @json(rtrim(\Illuminate\Support\Facades\Storage::disk(config('filesystems.fotos_disk', 'public'))->url(''), '/') . '/');

            function extension(nombre) {
                return (String(nombre).split('.').pop() || '').toLowerCase().replace(/[^a-z0-9]/g, '').slice(0, 5) || 'jpg';
            }

            function seVe(nombre) {
                return SE_VEN.includes(extension(nombre));
            }

            let heic2any = null;
            function cargarHeic2any() {
                heic2any ??= new Promise(function (ok, falla) {
                    const s = document.createElement('script');
                    s.src = 'https://cdn.jsdelivr.net/npm/heic2any@0.0.4/dist/heic2any.min.js';
                    s.onload = () => ok(window.heic2any);
                    s.onerror = () => { heic2any = null; falla(new Error('No se pudo cargar el convertidor de HEIC.')); };
                    document.head.appendChild(s);
                });
                return heic2any;
            }

            /* HEIC/HEIF → JPG. Si no se puede convertir, se sube el original. */
            async function preparar(archivo) {
                if (!['heic', 'heif'].includes(extension(archivo.name)) && !/image\/hei[cf]/.test(archivo.type)) return archivo;

                try {
                    const convertir = await cargarHeic2any();
                    const blob = await convertir({ blob: archivo, toType: 'image/jpeg', quality: 0.92 });
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

            return { PEDAZO, ACEPTA, urlDisco, extension, seVe, preparar, subir };
        })();
        </script>
    @endpush
@endonce
