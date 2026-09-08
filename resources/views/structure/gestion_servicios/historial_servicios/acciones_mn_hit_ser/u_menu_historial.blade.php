<div class="refacciones-section" style="display:grid; grid-template-columns:1fr; gap:18px;">
    <div>
        <h3 style="display:flex; align-items:center; gap:10px; font-size:18px; margin:0 0 8px;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" color="var(--primary)"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                Refacciones
            </h3>
            <p class="muted" style="margin:0 0 18px; font-size:13px;">Escribe las refacciones, asigna cantidad y precio unitario. Se calculará el total automáticamente.</p>

            <div class="refaccion-form-inline" style="display:grid; grid-template-columns:1fr 110px 140px auto; gap:10px; align-items:end; margin-bottom:16px;">
                <div class="form-group" style="margin:0;">
                    <label>Refacción</label>
                    <input type="text" id="refaccion-nombre" placeholder="Nombre de la refacción" class="qinput">
                </div>
                <div class="form-group" style="margin:0;">
                    <label>Cantidad</label>
                    <input type="number" id="refaccion-cantidad" placeholder="1" min="1" value="1" class="qinput">
                </div>
                <div class="form-group" style="margin:0;">
                    <label>Precio unitario</label>
                    <input type="number" id="refaccion-precio" placeholder="0.00" min="0" step="0.01" class="qinput">
                </div>
                <button type="button" id="btn-agregar-refaccion" class="btn" style="height:42px;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    Agregar
                </button>
            </div>

            <div class="responsive-table-wrap" style="border:1px solid var(--border); border-radius:12px; overflow:hidden;">
                <table class="refacciones-table" style="width:100%; border-collapse:collapse; font-size:13px;">
                    <thead style="background:var(--surface-2);">
                        <tr>
                            <th style="text-align:left; padding:10px 12px; font-weight:700;">Refacción</th>
                            <th style="text-align:center; padding:10px 12px; font-weight:700; width:100px;">Cantidad</th>
                            <th style="text-align:right; padding:10px 12px; font-weight:700; width:140px;">Precio unitario</th>
                            <th style="text-align:right; padding:10px 12px; font-weight:700; width:140px;">Subtotal</th>
                            <th style="text-align:center; padding:10px 12px; font-weight:700; width:60px;"></th>
                        </tr>
                    </thead>
                    <tbody id="refacciones-body">
                        <tr class="refacciones-empty"><td colspan="5" style="text-align:center; padding:28px 12px; color:var(--muted);">Aún no has agregado refacciones.</td></tr>
                    </tbody>
                </table>
            </div>

            <div style="display:flex; justify-content:flex-end; align-items:center; gap:12px; margin-top:16px; padding:14px 16px; border:1px solid var(--border); border-radius:12px; background:var(--surface-2);">
                <span style="font-size:14px; color:var(--muted); font-weight:600;">Total refacciones</span>
                <span id="refacciones-total" style="font-size:22px; font-weight:800; color:var(--primary);">$0.00</span>
            </div>
    </div>
</div>

<script>
    (function () {
        const nombreInput = document.getElementById('refaccion-nombre');
        const cantidadInput = document.getElementById('refaccion-cantidad');
        const precioInput = document.getElementById('refaccion-precio');
        const btnAgregar = document.getElementById('btn-agregar-refaccion');
        const tbody = document.getElementById('refacciones-body');
        const totalEl = document.getElementById('refacciones-total');
        let refaccionesCount = 0;

        function formatMoney(value) {
            return '$' + parseFloat(value || 0).toFixed(2);
        }

        function updateTotal() {
            let total = 0;
            tbody.querySelectorAll('tr.refaccion-row').forEach(row => {
                const cantidad = parseFloat(row.querySelector('.ref-cantidad')?.value || 0);
                const precio = parseFloat(row.querySelector('.ref-precio')?.value || 0);
                const subtotal = cantidad * precio;
                row.querySelector('.ref-subtotal').textContent = formatMoney(subtotal);
                total += subtotal;
            });
            totalEl.textContent = formatMoney(total);
            updateResumenRefacciones();
        }

        function updateEmptyState() {
            const empty = tbody.querySelector('.refacciones-empty');
            const rows = tbody.querySelectorAll('tr.refaccion-row');
            if (rows.length === 0) {
                if (!empty) {
                    tbody.innerHTML = '<tr class="refacciones-empty"><td colspan="5" style="text-align:center; padding:28px 12px; color:var(--muted);">Aún no has agregado refacciones.</td></tr>';
                } else {
                    empty.style.display = '';
                }
            } else if (empty) {
                empty.remove();
            }
        }

        function addRefaccion(nombre = '', cantidad = 1, precio = 0) {
            if (!nombre.trim()) {
                alert('Escribe el nombre de la refacción.');
                return;
            }
            if (cantidad < 1 || precio < 0) {
                alert('Cantidad y precio no válidos.');
                return;
            }

            const index = refaccionesCount++;
            const subtotal = cantidad * precio;
            const row = document.createElement('tr');
            row.className = 'refaccion-row';
            row.style.borderBottom = '1px solid var(--border)';
            row.innerHTML = `
                <td style="padding:8px 12px;">
                    <input type="text" name="refacciones[${index}][nombre]" value="${nombre.replace(/"/g, '&quot;')}" class="qinput ref-nombre" style="width:100%; border:none; background:transparent; padding:4px 0;" required>
                </td>
                <td style="padding:8px 12px; text-align:center;">
                    <input type="number" name="refacciones[${index}][cantidad]" value="${cantidad}" min="1" class="qinput ref-cantidad" style="width:80px; text-align:center;" required>
                </td>
                <td style="padding:8px 12px; text-align:right;">
                    <input type="number" name="refacciones[${index}][precio_unitario]" value="${precio}" min="0" step="0.01" class="qinput ref-precio" style="width:120px; text-align:right;" required>
                </td>
                <td style="padding:8px 12px; text-align:right; font-weight:700;" class="ref-subtotal">${formatMoney(subtotal)}</td>
                <td style="padding:8px 12px; text-align:center;">
                    <button type="button" class="btn btn--ghost btn-eliminar-refaccion" style="padding:4px 8px;" title="Eliminar">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                    </button>
                </td>
            `;

            updateEmptyState();
            tbody.appendChild(row);

            row.querySelector('.ref-cantidad').addEventListener('input', updateTotal);
            row.querySelector('.ref-precio').addEventListener('input', updateTotal);
            row.querySelector('.btn-eliminar-refaccion').addEventListener('click', () => {
                row.remove();
                reindexRefacciones();
                updateTotal();
                updateEmptyState();
            });

            updateTotal();
        }

        function reindexRefacciones() {
            let i = 0;
            tbody.querySelectorAll('tr.refaccion-row').forEach(row => {
                row.querySelector('.ref-nombre').name = `refacciones[${i}][nombre]`;
                row.querySelector('.ref-cantidad').name = `refacciones[${i}][cantidad]`;
                row.querySelector('.ref-precio').name = `refacciones[${i}][precio_unitario]`;
                i++;
            });
            refaccionesCount = i;
        }

        btnAgregar?.addEventListener('click', () => {
            addRefaccion(nombreInput.value, parseInt(cantidadInput.value || 1), parseFloat(precioInput.value || 0));
            nombreInput.value = '';
            cantidadInput.value = 1;
            precioInput.value = '';
            nombreInput.focus();
        });

        nombreInput?.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                btnAgregar.click();
            }
        });

        function updateResumenRefacciones() {
            const list = document.getElementById('resumen-refacciones-list');
            const total = document.getElementById('resumen-refacciones-total');
            if (!list || !total) return;

            const rows = tbody.querySelectorAll('tr.refaccion-row');
            if (rows.length === 0) {
                list.innerHTML = '<p style="color:var(--muted); font-size:13px; margin:0;">No se agregaron refacciones.</p>';
                total.textContent = '$0.00';
                return;
            }

            let html = '<ul style="margin:0; padding-left:18px; font-size:13px; color:var(--text);">';
            let sum = 0;
            rows.forEach(row => {
                const nombre = row.querySelector('.ref-nombre')?.value || '';
                const cantidad = parseFloat(row.querySelector('.ref-cantidad')?.value || 0);
                const precio = parseFloat(row.querySelector('.ref-precio')?.value || 0);
                const subtotal = cantidad * precio;
                sum += subtotal;
                html += `<li style="margin-bottom:6px;">${nombre} x${cantidad} — ${formatMoney(subtotal)}</li>`;
            });
            html += '</ul>';
            list.innerHTML = html;
            total.textContent = formatMoney(sum);
        }

        window.updateResumenRefacciones = updateResumenRefacciones;
        window.getRefaccionesTotal = function () {
            let total = 0;
            tbody.querySelectorAll('tr.refaccion-row').forEach(row => {
                const cantidad = parseFloat(row.querySelector('.ref-cantidad')?.value || 0);
                const precio = parseFloat(row.querySelector('.ref-precio')?.value || 0);
                total += cantidad * precio;
            });
            return total;
        };
    })();
</script>
