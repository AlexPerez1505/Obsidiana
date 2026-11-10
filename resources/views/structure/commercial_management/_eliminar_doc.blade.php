{{--
    Eliminar una cotización o una venta. Las primeras 72 horas desde que se
    creó basta con confirmar; después se pide el NIP (o la contraseña si el
    usuario no tiene NIP). El servidor lo vuelve a revisar (EliminarConNip).

    Botón que lo abre:
      <button type="button" data-eliminar-doc data-url="…" data-folio="COT-…"
              data-que="la cotización" data-nip="1|0" data-horas="N">
--}}
@php $conPin = (bool) auth()->user()?->approval_pin_hash; @endphp
<dialog id="dlgEliminarDoc" class="el-dlg" aria-labelledby="elDocT">
    <form method="POST" id="elDocForm">
        @csrf
        @method('DELETE')
        <div class="el-h" id="elDocT">Eliminar</div>
        <div class="el-b">
            <p id="elDocMsg" style="margin:0 0 12px;"></p>
            <div id="elDocNip" hidden>
                <label for="elDocNipIn" style="display:block; font-size:13px; font-weight:600; margin-bottom:6px;">
                    {{ $conPin ? 'NIP de aprobación' : 'Tu contraseña' }}
                </label>
                <input type="password" name="nip" id="elDocNipIn" autocomplete="off" inputmode="{{ $conPin ? 'numeric' : 'text' }}"
                       style="width:100%; padding:10px 12px; border:1px solid var(--border); border-radius:8px; background:var(--surface); color:var(--text); font-size:15px;">
                <p style="margin:6px 0 0; font-size:12.5px; color:var(--muted);">Ya pasaron más de {{ \App\Support\EliminarConNip::HORAS }} horas desde que se creó.</p>
            </div>
            <p id="elDocSinNip" hidden style="margin:0; font-size:12.5px; color:var(--muted);"></p>
        </div>
        <div class="el-f">
            <button type="button" class="erp-btn ghost" data-el-cerrar>Cancelar</button>
            <button type="submit" class="erp-btn danger">Eliminar</button>
        </div>
    </form>
</dialog>
<style>
    dialog.el-dlg { border:0; border-radius:12px; padding:0; width:min(420px, calc(100vw - 32px)); background:var(--surface); color:var(--text); box-shadow:0 20px 60px rgba(0,0,0,.25); }
    dialog.el-dlg::backdrop { background:rgba(15,23,42,.45); }
    .el-h { padding:14px 18px; border-bottom:1px solid var(--border); font-weight:650; font-size:15px; }
    .el-b { padding:16px 18px; font-size:14px; }
    .el-f { padding:12px 18px; border-top:1px solid var(--border); display:flex; justify-content:flex-end; gap:8px; }
</style>
<script>
(function () {
    const dlg = document.getElementById('dlgEliminarDoc');
    if (!dlg || dlg.dataset.listo) return;
    dlg.dataset.listo = '1';
    const form = document.getElementById('elDocForm'), nip = document.getElementById('elDocNipIn');

    document.addEventListener('click', function (e) {
        const b = e.target.closest('[data-eliminar-doc]');
        if (!b) return;
        e.preventDefault();
        const pide = b.dataset.nip === '1';
        form.action = b.dataset.url;
        document.getElementById('elDocT').textContent = 'Eliminar ' + (b.dataset.folio || '');
        document.getElementById('elDocMsg').textContent = '¿Eliminar ' + (b.dataset.que || 'el documento') + ' ' + (b.dataset.folio || '') + '? No se puede deshacer.';
        document.getElementById('elDocNip').hidden = !pide;
        nip.required = pide; nip.value = '';
        const sin = document.getElementById('elDocSinNip');
        sin.hidden = pide;
        sin.textContent = pide ? '' : 'Sin NIP por ' + b.dataset.horas + ' hora(s) más; después se pedirá.';
        dlg.showModal();
        (pide ? nip : form.querySelector('[type=submit]')).focus();
    });
    dlg.querySelector('[data-el-cerrar]').addEventListener('click', () => dlg.close());
    dlg.addEventListener('click', e => { if (e.target === dlg) dlg.close(); });
})();
</script>
