{{--
    El teléfono del cliente va a 10 dígitos exactos: mientras se escribe se
    quitan espacios, guiones y letras, se dice cuántos faltan, y no deja
    enviar el formulario hasta que sean 10. El servidor valida lo mismo.
--}}
<p class="campo-nota" id="telefonoAyuda" style="margin:-6px 0 10px; font-size:12.5px; color:var(--muted);">10 dígitos, sin lada internacional.</p>
<script>
(function () {
    const input = document.getElementById('telefono');
    const ayuda = document.getElementById('telefonoAyuda');
    if (!input || !ayuda) return;

    const grupo = input.closest('.form-group');
    if (grupo && grupo.nextSibling !== ayuda) grupo.after(ayuda);

    const digitos = () => input.value.replace(/\D+/g, '');

    function pintar() {
        const n = digitos().length;
        const ok = n === 10;
        ayuda.textContent = n === 0
            ? '10 dígitos, sin lada internacional.'
            : (ok ? '✓ 10 dígitos' : (n < 10 ? 'Faltan ' + (10 - n) + ' dígito(s).' : 'Sobran ' + (n - 10) + ' dígito(s): deben ser 10.'));
        ayuda.style.color = n === 0 ? 'var(--muted)' : (ok ? 'var(--green, #15803d)' : 'var(--danger, #dc2626)');
        input.setAttribute('aria-invalid', n > 0 && !ok ? 'true' : 'false');
    }

    input.addEventListener('input', function () {
        const limpio = digitos().slice(0, 10);
        if (input.value !== limpio) input.value = limpio;
        pintar();
    });

    input.form?.addEventListener('submit', function (e) {
        if (digitos().length === 10) return;
        e.preventDefault();
        e.stopImmediatePropagation();
        pintar();
        input.focus();
        window.showToast?.('El teléfono debe tener exactamente 10 dígitos.', 'warn');
    }, true);

    pintar();
})();
</script>
