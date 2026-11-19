{{--
    Todo lo que se escribe se guarda en MAYÚSCULAS, con corrector ortográfico.

    Aplica a los campos de texto libre (input de texto/búsqueda y textarea)
    de cualquier pantalla, también los que dibuja el JavaScript después:
      - mientras se escribe, el texto SE VE en mayúsculas (CSS) pero el valor
        sigue como se tecleó, para que el corrector del navegador pueda
        subrayar las palabras mal escritas;
      - al salir del campo y al guardar el formulario, el valor SE CONVIERTE
        a mayúsculas, que es lo que llega al servidor;
      - en el celular el teclado sale en mayúsculas y con autocorrección.

    No se tocan: correos, contraseñas, URLs, usuarios, tokens/llaves, ni
    fecha/número/teléfono, ni selects, casillas o campos ocultos (ahí viajan
    valores internos como rutas de fotos, firmas o claves de catálogo).
    Para excluir un campo o una zona: data-no-mayus.
--}}
@once
<style>
    .obs-mayus { text-transform:uppercase; }
    .obs-mayus::placeholder { text-transform:none; }
</style>
<script>
(function () {
    const NO_NOMBRE = /(e-?mail|correo|pass|contrase|token|url|link|sitio|web|username|usuario|login|api|key|llave|slug|path|archivo)/i;
    const NO_AUTOCOMPLETE = /(email|username|url|password|one-time-code)/i;
    const TIPOS = ['text', 'search', ''];

    function aplica(el) {
        if (!el || !el.matches) return false;
        if (el.tagName === 'TEXTAREA') {
            // sigue abajo con las exclusiones
        } else if (el.tagName === 'INPUT') {
            if (!TIPOS.includes((el.getAttribute('type') || '').toLowerCase())) return false;
        } else {
            return false;
        }
        if (el.readOnly || el.closest('[data-no-mayus]')) return false;
        const nombre = (el.name || '') + ' ' + (el.id || '');
        if (NO_NOMBRE.test(nombre)) return false;
        if (NO_AUTOCOMPLETE.test(el.getAttribute('autocomplete') || '')) return false;
        if (/^(email|url)$/i.test(el.getAttribute('inputmode') || '')) return false;
        return true;
    }

    function equipar(el) {
        if (!aplica(el) || el.dataset.mayusListo) return;
        el.dataset.mayusListo = '1';
        el.classList.add('obs-mayus');
        el.setAttribute('spellcheck', 'true');
        el.setAttribute('autocorrect', 'on');
        el.setAttribute('autocapitalize', 'characters');
        if (!el.getAttribute('lang')) el.setAttribute('lang', 'es-MX');
    }

    function convertir(el) {
        if (!aplica(el) || !el.value) return;
        const mayus = el.value.toLocaleUpperCase('es-MX');
        if (mayus === el.value) return;
        // Se conserva la posición del cursor si el campo sigue activo.
        const ini = el.selectionStart, fin = el.selectionEnd;
        el.value = mayus;
        if (document.activeElement === el && ini !== null) {
            try { el.setSelectionRange(ini, fin); } catch (e) {}
        }
    }

    function equiparTodo(raiz) {
        (raiz || document).querySelectorAll('input, textarea').forEach(equipar);
    }

    // Campos que aparecen después (modales, filas nuevas, tiempo real).
    document.addEventListener('focusin', e => equipar(e.target));
    document.addEventListener('obs:vivo', () => equiparTodo());

    // Al salir del campo ya queda en mayúsculas.
    document.addEventListener('change', e => convertir(e.target), true);
    document.addEventListener('focusout', e => convertir(e.target), true);

    // Antes de mandar cualquier formulario (va en captura: corre antes que
    // los scripts de cada pantalla que arman su FormData en el submit).
    document.addEventListener('submit', function (e) {
        e.target.querySelectorAll?.('input, textarea').forEach(convertir);
    }, true);

    // Guardados por fetch que leen el formulario sin mandarlo: se convierte
    // todo lo escrito justo al picar un botón.
    document.addEventListener('pointerdown', function (e) {
        const boton = e.target.closest('button, [type=submit], [role=button]');
        const form = boton?.closest('form') || boton?.form;
        if (form) form.querySelectorAll('input, textarea').forEach(convertir);
    }, true);

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => equiparTodo());
    } else {
        equiparTodo();
    }
})();
</script>
@endonce
