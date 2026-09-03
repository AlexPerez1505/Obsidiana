{{-- Modales de la pantalla de cobranza. --}}

{{-- ===================== Registrar un pago ===================== --}}
<dialog class="cb-modal" id="modalCobro">
    <form method="POST" action="{{ route('commercial.ventas.cobros.store', $venta) }}"
          enctype="multipart/form-data" class="cb-modal-box">
        @csrf

        <div class="cb-modal-head">
            <h3>Registrar pago</h3>
            <button type="button" class="cb-x" data-cerrar aria-label="Cerrar">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
            </button>
        </div>

        <div class="cb-modal-body">
            <label for="cbParcialidad">Aplicar a</label>
            <select id="cbParcialidad" name="venta_pago_id">
                <option value="">Sin parcialidad (abono suelto)</option>
                @foreach ($venta->pagos as $p)
                    <option value="{{ $p->id }}" @disabled($p->saldo() <= 0)>
                        {{ $p->nombre }} — saldo ${{ number_format($p->saldo(), 2) }}
                    </option>
                @endforeach
            </select>

            <div class="cb-2">
                <div>
                    <label for="cbFecha" class="cb-mt">Fecha del pago</label>
                    <input type="date" id="cbFecha" name="fecha" value="{{ old('fecha', now()->format('Y-m-d')) }}" required>
                </div>
                <div>
                    <label for="cbMonto" class="cb-mt">Monto</label>
                    <input type="number" id="cbMonto" name="monto" step="0.01" min="0.01"
                           max="{{ $venta->saldo() }}" value="{{ old('monto') }}" required>
                    <p class="cb-nota">Saldo disponible ${{ number_format($venta->saldo(), 2) }}</p>
                </div>
            </div>

            <div class="cb-2">
                <div>
                    <label for="cbMetodo" class="cb-mt">Método</label>
                    <select id="cbMetodo" name="metodo" required>
                        @foreach ($metodos as $valor => $etiqueta)
                            <option value="{{ $valor }}" @selected(old('metodo') === $valor)>{{ $etiqueta }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="cbRef" class="cb-mt">Referencia</label>
                    <input type="text" id="cbRef" name="referencia" value="{{ old('referencia') }}"
                           placeholder="Folio bancario, últimos 4 dígitos…">
                </div>
            </div>

            <label for="cbNota" class="cb-mt">Nota</label>
            <input type="text" id="cbNota" name="nota" value="{{ old('nota') }}" placeholder="Opcional">

            <label for="cbEvidencias" class="cb-mt">Evidencia del pago</label>
            <label class="cb-drop" for="cbEvidencias" data-drop>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="m17 8-5-5-5 5"/><path d="M12 3v12"/></svg>
                <span data-nombre>Comprobante, captura o ficha de depósito</span>
                <small>Imagen o PDF · hasta 5 archivos</small>
            </label>
            <input type="file" id="cbEvidencias" name="evidencias[]" multiple
                   accept="image/*,.pdf" class="cb-file">
        </div>

        <div class="cb-modal-foot">
            <button type="button" class="erp-btn ghost sm" data-cerrar>Cancelar</button>
            <button type="submit" class="erp-btn sm">Registrar pago</button>
        </div>
    </form>
</dialog>

{{-- ===================== Recorrer fechas ===================== --}}
<dialog class="cb-modal" id="modalRecorrer">
    <form method="POST" action="{{ route('commercial.ventas.cobros.recorrer', $venta) }}" class="cb-modal-box">
        @csrf

        <div class="cb-modal-head">
            <h3>Recorrer fechas</h3>
            <button type="button" class="cb-x" data-cerrar aria-label="Cerrar">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
            </button>
        </div>

        <div class="cb-modal-body">
            <p class="cb-explica">
                Se moverá el calendario para que el primer pago pendiente caiga en la fecha que elijas,
                conservando los días entre una parcialidad y la siguiente.
                <b>Las parcialidades que ya tienen pagos no se mueven.</b>
            </p>

            <label for="cbNueva" class="cb-mt">Nueva fecha del primer pago pendiente</label>
            <input type="date" id="cbNueva" name="nueva_fecha" value="{{ now()->format('Y-m-d') }}" required>
        </div>

        <div class="cb-modal-foot">
            <button type="button" class="erp-btn ghost sm" data-cerrar>Cancelar</button>
            <button type="submit" class="erp-btn sm">Recorrer</button>
        </div>
    </form>
</dialog>

{{-- ===================== Agregar parcialidad ===================== --}}
<dialog class="cb-modal" id="modalParcialidad">
    <form method="POST" action="{{ route('commercial.ventas.cobros.parcialidad.agregar', $venta) }}" class="cb-modal-box">
        @csrf

        <div class="cb-modal-head">
            <h3>Agregar parcialidad</h3>
            <button type="button" class="cb-x" data-cerrar aria-label="Cerrar">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
            </button>
        </div>

        <div class="cb-modal-body">
            <label for="cbPNombre">Nombre</label>
            <input type="text" id="cbPNombre" name="nombre" placeholder="Ej. Pago 4" required>

            <div class="cb-2">
                <div>
                    <label for="cbPFecha" class="cb-mt">Fecha</label>
                    <input type="date" id="cbPFecha" name="fecha" value="{{ now()->addMonth()->format('Y-m-d') }}" required>
                </div>
                <div>
                    <label for="cbPMonto" class="cb-mt">Monto</label>
                    <input type="number" id="cbPMonto" name="monto" step="0.01" min="0.01" required>
                </div>
            </div>
        </div>

        <div class="cb-modal-foot">
            <button type="button" class="erp-btn ghost sm" data-cerrar>Cancelar</button>
            <button type="submit" class="erp-btn sm">Agregar</button>
        </div>
    </form>
</dialog>

{{-- ===================== Editar parcialidad ===================== --}}
<dialog class="cb-modal" id="modalEditarParcialidad">
    <form method="POST" class="cb-modal-box" data-form>
        @csrf @method('PUT')

        <div class="cb-modal-head">
            <h3>Editar <span data-titulo>parcialidad</span></h3>
            <button type="button" class="cb-x" data-cerrar aria-label="Cerrar">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
            </button>
        </div>

        <div class="cb-modal-body">
            <div class="cb-2">
                <div>
                    <label for="cbEFecha">Fecha</label>
                    <input type="date" id="cbEFecha" name="fecha" required>
                </div>
                <div>
                    <label for="cbEMonto">Monto</label>
                    <input type="number" id="cbEMonto" name="monto" step="0.01" min="0" required>
                    <p class="cb-nota" data-piso hidden></p>
                </div>
            </div>
        </div>

        <div class="cb-modal-foot">
            <button type="button" class="erp-btn ghost sm" data-cerrar>Cancelar</button>
            <button type="submit" class="erp-btn sm">Guardar</button>
        </div>
    </form>
</dialog>

<script>
(function () {
    function abrir(el) {
        if (typeof el.showModal === 'function') { el.showModal(); } else { el.setAttribute('open', ''); }
    }

    document.querySelectorAll('.cb-modal').forEach(function (m) {
        m.querySelectorAll('[data-cerrar]').forEach(function (b) {
            b.addEventListener('click', function () { m.close(); });
        });
        m.addEventListener('click', function (e) { if (e.target === m) m.close(); });
    });

    // Botones que abren un modal por id
    document.querySelectorAll('[data-abrir]').forEach(function (b) {
        b.addEventListener('click', function () {
            var m = document.getElementById(b.dataset.abrir);
            if (m) abrir(m);
        });
    });

    // Registrar pago: puede venir de una parcialidad concreta
    var mCobro = document.getElementById('modalCobro');

    document.querySelectorAll('[data-cobrar]').forEach(function (b) {
        b.addEventListener('click', function () {
            if (b.dataset.parcialidad) {
                document.getElementById('cbParcialidad').value = b.dataset.parcialidad;
            }
            if (b.dataset.monto) {
                document.getElementById('cbMonto').value = b.dataset.monto;
            }
            abrir(mCobro);
        });
    });

    // Editar parcialidad
    var mEditar = document.getElementById('modalEditarParcialidad');

    document.querySelectorAll('[data-editar-parcialidad]').forEach(function (b) {
        b.addEventListener('click', function () {
            var form = mEditar.querySelector('[data-form]');
            form.action = b.dataset.url;

            mEditar.querySelector('[data-titulo]').textContent = b.dataset.nombre;
            document.getElementById('cbEFecha').value = b.dataset.fecha || '';
            document.getElementById('cbEMonto').value = b.dataset.monto || '';

            // Si ya tiene dinero encima, se avisa cuál es el piso.
            var cobrado = parseFloat(b.dataset.cobrado || '0');
            var piso = mEditar.querySelector('[data-piso]');

            if (cobrado > 0) {
                piso.textContent = 'Ya tiene $' + cobrado.toFixed(2) + ' cobrados: no puede quedar por debajo.';
                piso.hidden = false;
                document.getElementById('cbEMonto').min = cobrado;
            } else {
                piso.hidden = true;
                document.getElementById('cbEMonto').min = 0;
            }

            abrir(mEditar);
        });
    });

    // Evidencias: nombre del archivo y arrastrar
    var input = document.getElementById('cbEvidencias');
    var zona = document.querySelector('[data-drop]');

    if (input && zona) {
        var etiqueta = zona.querySelector('[data-nombre]');

        function mostrar(archivos) {
            if (!archivos || !archivos.length) return;
            etiqueta.textContent = archivos.length === 1
                ? archivos[0].name
                : archivos.length + ' archivos elegidos';
        }

        input.addEventListener('change', function () { mostrar(input.files); });

        ['dragenter', 'dragover'].forEach(function (ev) {
            zona.addEventListener(ev, function (e) { e.preventDefault(); zona.classList.add('is-encima'); });
        });
        ['dragleave', 'drop'].forEach(function (ev) {
            zona.addEventListener(ev, function (e) { e.preventDefault(); zona.classList.remove('is-encima'); });
        });
        zona.addEventListener('drop', function (e) {
            if (!e.dataTransfer.files.length) return;
            input.files = e.dataTransfer.files;
            mostrar(input.files);
        });
    }

    // Confirmación antes de cancelar un cobro
    document.querySelectorAll('[data-confirmar]').forEach(function (f) {
        f.addEventListener('submit', function (e) {
            if (! window.confirm(f.dataset.confirmar + '\n\n¿Continuar?')) e.preventDefault();
        });
    });
})();
</script>
