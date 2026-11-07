{{--
    Autocorrector ortográfico en español de México, en todas las pantallas.

    Al salir de un campo de texto libre (notas, descripciones, nombres,
    direcciones, motivos…), lo escrito se revisa con la IA y se corrige solo:
    acentos, ñ, b/v, s/c/z, h, letras cambiadas. Aparece una notita junto al
    campo con lo que cambió y un botón "Deshacer".

    No se revisan: búsquedas, correos, contraseñas, números, teléfonos,
    series, códigos, folios, RFC, marcas/modelos ni los campos con lista de
    sugerencias del catálogo. Para excluir un campo o una zona:
    data-no-ortografia.

    Si se guarda el formulario mientras una revisión va en camino, se espera
    (máximo unos segundos) a que termine, para que se guarde ya corregido.
--}}
@once
<style>
    .orto-nota { position:fixed; z-index:2147483000; display:flex; align-items:center; gap:8px; max-width:min(420px, calc(100vw - 24px));
                 padding:6px 8px 6px 10px; border-radius:10px; font-size:12.5px; line-height:1.35;
                 background:var(--surface, #fff); color:var(--text, #0f172a); border:1px solid var(--border, #e2e8f0);
                 box-shadow:0 6px 22px rgba(15,23,42,.18); opacity:0; transform:translateY(-4px); transition:opacity .15s, transform .15s; }
    .orto-nota.is-on { opacity:1; transform:none; }
    .orto-nota b { color:var(--green, #15803d); font-weight:700; }
    .orto-nota s { color:var(--muted, #64748b); }
    .orto-nota button { flex:0 0 auto; border:0; border-radius:7px; padding:4px 8px; cursor:pointer; font:inherit; font-weight:600;
                        background:var(--surface-2, #f1f5f9); color:var(--primary, #2563eb); }
    @media (prefers-reduced-motion: reduce) { .orto-nota { transition:none; } }
</style>
<script>
(function () {
    const URL_ORTO = @json(route('ortografia.corregir'));
    const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content || '';
    const TIPOS = ['text', ''];
    // Campos que no son texto libre: no se tocan.
    const NO_NOMBRE = /(e-?mail|correo|pass|contrase|token|url|link|sitio|web|username|usuario|login|api|key|llave|slug|path|archivo|buscar|busca|search|filtro|^q$|serie|serial|c[oó]digo|codigo|folio|rfc|curp|clabe|cuenta|modelo|marca|sku|placa|cup[oó]n|nip|pin|postal|^cp$|tel[eé]fono|telefono|celular|whats|monto|precio|cantidad|importe|referencia)/i;

    const pendientes = new Map();   // campo -> promesa
    let nota = null, notaTimer = null;

    function aplica(el) {
        if (!el || !el.matches) return false;
        if (el.tagName === 'INPUT') {
            if (!TIPOS.includes((el.getAttribute('type') || '').toLowerCase())) return false;
            if (el.hasAttribute('list')) return false;          // valores del catálogo
        } else if (el.tagName !== 'TEXTAREA') {
            return false;
        }
        if (el.readOnly || el.disabled || el.closest('[data-no-ortografia]')) return false;
        const nombre = ((el.name || '') + ' ' + (el.id || '')).trim();
        if (nombre && NO_NOMBRE.test(nombre)) return false;
        if (/(email|username|url|password|one-time-code)/i.test(el.getAttribute('autocomplete') || '')) return false;
        if (/^(email|url|numeric|decimal|tel)$/i.test(el.getAttribute('inputmode') || '')) return false;
        return true;
    }

    function revisar(el) {
        if (!aplica(el)) return;
        const texto = el.value;
        if (!texto || texto.length > 2000 || !/\p{L}{3,}/u.test(texto)) return;
        if (el._ortoVisto === texto) return;                    // ya se revisó tal cual
        el._ortoVisto = texto;

        const ctrl = new AbortController();
        const corte = setTimeout(() => ctrl.abort(), 9000);
        const p = fetch(URL_ORTO, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf() },
            body: JSON.stringify({ texto }),
            signal: ctrl.signal,
            credentials: 'same-origin',
        })
            .then(r => r.ok ? r.json() : null)
            .then(d => {
                // Solo si el campo sigue diciendo lo mismo (no lo cambiaron mientras tanto).
                if (!d || !d.texto || d.texto === texto || el.value !== texto) return;
                poner(el, d.texto);
                avisar(el, texto, d.cambios || []);
            })
            .catch(() => {})
            .finally(() => { clearTimeout(corte); pendientes.delete(el); });
        pendientes.set(el, p);
    }

    function poner(el, valor) {
        el.value = valor;
        el._ortoVisto = valor;
        // Para las pantallas que llevan su propio estado (cotizador, filas nuevas…).
        el.dispatchEvent(new Event('input', { bubbles: true }));
        el.dispatchEvent(new Event('change', { bubbles: true }));
    }

    const esc = t => String(t).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

    function avisar(el, original, cambios) {
        quitarNota();
        const r = el.getBoundingClientRect();
        if (!r.width) return;
        nota = document.createElement('div');
        nota.className = 'orto-nota';
        nota.setAttribute('role', 'status');
        const lista = cambios.slice(0, 4).map(c => '<s>' + esc(c.antes) + '</s> → <b>' + esc(c.despues) + '</b>').join(', ');
        nota.innerHTML = '<span>Ortografía corregida' + (lista ? ': ' + lista + (cambios.length > 4 ? '…' : '') : '') + '</span>';
        const deshacer = document.createElement('button');
        deshacer.type = 'button';
        deshacer.textContent = 'Deshacer';
        deshacer.addEventListener('mousedown', e => e.preventDefault());
        deshacer.addEventListener('click', () => { poner(el, original); quitarNota(); });
        nota.appendChild(deshacer);
        // Dentro de una ventana (<dialog>) la nota va en la ventana, si no quedaría detrás.
        (el.closest('dialog[open]') || document.body).appendChild(nota);
        const alto = nota.offsetHeight || 30;
        const arriba = r.bottom + 6 + alto > window.innerHeight ? r.top - alto - 6 : r.bottom + 6;
        nota.style.top = Math.max(6, arriba) + 'px';
        nota.style.left = Math.max(8, Math.min(r.left, window.innerWidth - nota.offsetWidth - 8)) + 'px';
        requestAnimationFrame(() => nota && nota.classList.add('is-on'));
        notaTimer = setTimeout(quitarNota, 7000);
    }

    function quitarNota() {
        clearTimeout(notaTimer);
        if (nota) { nota.remove(); nota = null; }
    }

    // Al salir del campo (el de mayúsculas ya lo convirtió en su fase de captura).
    document.addEventListener('focusout', e => revisar(e.target));
    window.addEventListener('scroll', quitarNota, { passive: true });

    // Guardar mientras una revisión va en camino: se espera a que termine
    // (máximo 4 s) y se vuelve a mandar el formulario ya corregido.
    document.addEventListener('submit', function (e) {
        const form = e.target;
        if (!(form instanceof HTMLFormElement) || form._ortoListo) { if (form) form._ortoListo = false; return; }
        const enEste = [...pendientes.entries()].filter(([el]) => form.contains(el) || el.form === form).map(([, p]) => p);
        if (!enEste.length) return;
        e.preventDefault();
        e.stopImmediatePropagation();
        const quien = e.submitter;
        Promise.race([Promise.allSettled(enEste), new Promise(r => setTimeout(r, 4000))]).then(() => {
            form._ortoListo = true;
            if (form.requestSubmit) { quien && quien.form === form ? form.requestSubmit(quien) : form.requestSubmit(); }
            else { form.submit(); }
        });
    }, true);
})();
</script>
@endonce
