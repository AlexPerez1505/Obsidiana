@push('scripts')
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('form-entrada');
        const lista = form?.querySelector('[data-chk-lista]');
        if (!lista) return;

        /*
        | El checklist ya no es fijo: lo arma la IA para el equipo elegido
        | (tipo, subtipo, marca y modelo) y se le pueden sumar puntos a mano.
        | Cada punto viaja con su texto, su grupo y el proceso al que manda,
        | así el servidor guarda lo que de verdad se preguntó.
        */
        const GENERAL = @json($checklist);
        const PREVIO = @json(old('checklist') ?: null);
        const URL_IA = @json(route('inventory.movimientos.checklistIa'));
        const PROCESOS = @json(\App\Models\PiezaProceso::PROCESOS);

        const origen = form.querySelector('[data-chk-origen-texto]');
        const btnRegenerar = form.querySelector('[data-chk-regenerar]');
        const nuevoTexto = form.querySelector('[data-chk-nuevo-texto]');
        const nuevoManda = form.querySelector('[data-chk-nuevo-manda]');
        const btnAgregar = form.querySelector('[data-chk-agregar]');

        // [{ llave, texto, manda, grupo, manual }]
        let puntos = [];

        function desdeGrupos(grupos) {
            const salida = [];
            Object.values(grupos || {}).forEach(function (g) {
                Object.entries(g.puntos || {}).forEach(function ([llave, p]) {
                    salida.push({ llave, texto: p.texto, manda: p.manda || '', grupo: g.titulo, manual: false });
                });
            });
            return salida;
        }

        function esUsado() {
            return form.querySelector('[data-condicion]:checked')?.value === 'usado';
        }

        function escapar(texto) {
            // También comillas: el texto va dentro de value="..." de los ocultos.
            return String(texto ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;')
                .replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
        }

        /* Lo ya contestado se conserva al volver a pintar (por llave). */
        function respuestasActuales() {
            const r = {};
            lista.querySelectorAll('[data-chk-punto]').forEach(function (div) {
                r[div.dataset.llave] = {
                    r: div.querySelector('input[type=radio]:checked')?.value || null,
                    nota: div.querySelector('.nota')?.value || '',
                };
            });
            return r;
        }

        function pintar(previas = respuestasActuales()) {
            const grupos = {};
            puntos.forEach(p => (grupos[p.grupo || 'Otros'] ??= []).push(p));

            lista.innerHTML = '';

            Object.entries(grupos).forEach(function ([titulo, items]) {
                const g = document.createElement('div');
                g.className = 'chk-grupo';
                g.innerHTML = `<h4>${escapar(titulo)}</h4>`;

                items.forEach(function (p) {
                    const previa = previas[p.llave] || {};
                    const n = `checklist[${p.llave}]`;
                    const div = document.createElement('div');

                    div.className = 'chk-punto' + (p.manual ? ' manual' : '');
                    div.dataset.chkPunto = '';
                    div.dataset.llave = p.llave;
                    // data-manda: a qué proceso va la pieza si sale en "No";
                    // de ahí se propone sola la ruta de procesos.
                    if (p.manda) div.dataset.manda = p.manda;

                    div.innerHTML = `
                        <span class="txt">${escapar(p.texto)}${p.manual ? ' <button type="button" class="chk-quitar" title="Quitar este punto" aria-label="Quitar este punto">×</button>' : ''}</span>
                        <span class="tri">
                            ${[['si', 'Sí'], ['no', 'No'], ['na', 'N/A']].map(([v, t]) => `
                                <label><input type="radio" name="${n}[r]" value="${v}" ${previa.r === v ? 'checked' : ''}><span>${t}</span></label>
                            `).join('')}
                        </span>
                        <input type="text" class="nota" name="${n}[nota]" maxlength="300"
                               placeholder="¿Qué tiene? (ej. rayón en la tapa derecha)" value="${escapar(previa.nota || '')}">
                        <input type="hidden" name="${n}[texto]" value="${escapar(p.texto)}">
                        <input type="hidden" name="${n}[manda]" value="${escapar(p.manda || '')}">
                        <input type="hidden" name="${n}[grupo]" value="${escapar(p.grupo || '')}">
                    `;

                    // El paso entero se apaga cuando el equipo es nuevo.
                    div.querySelectorAll('input').forEach(c => { c.disabled = !esUsado(); });

                    div.querySelector('.chk-quitar')?.addEventListener('click', function () {
                        puntos = puntos.filter(x => x.llave !== p.llave);
                        pintar();
                    });

                    g.appendChild(div);
                });

                lista.appendChild(g);
            });

            const total = form.querySelector('[data-chk-total]');
            if (total) total.textContent = puntos.length;

            form.dispatchEvent(new CustomEvent('checklist:cambio'));
        }

        /* ===================== Puntos a mano ===================== */
        function agregar() {
            const texto = (nuevoTexto.value || '').trim();
            if (!texto) { nuevoTexto.focus(); return; }

            let llave = 'manual_' + texto.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '')
                .replace(/[^a-z0-9]+/g, '_').replace(/^_|_$/g, '').slice(0, 45);
            if (llave === 'manual_') llave = 'manual_punto';
            for (let i = 2; puntos.some(p => p.llave === llave); i++) llave = llave.replace(/_\d+$/, '') + '_' + i;

            puntos.push({ llave, texto, manda: nuevoManda.value || '', grupo: 'Agregados a mano', manual: true });
            nuevoTexto.value = '';
            pintar();
        }

        btnAgregar?.addEventListener('click', agregar);
        nuevoTexto?.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') { e.preventDefault(); e.stopPropagation(); agregar(); }
        });

        /* ===================== Checklist de la IA ===================== */
        const selects = ['equipment_type_id', 'subtype_id', 'brand_id', 'equipment_model_id']
            .map(id => document.getElementById(id));

        function equipoElegido() {
            const [tipo, subtipo, marca, modelo] = selects.map(s => s?.value || '');
            return { equipment_type_id: tipo, subtype_id: subtipo, brand_id: marca, equipment_model_id: modelo };
        }

        function nombreDelEquipo() {
            return selects.map(s => s?.selectedOptions?.[0]?.value ? s.selectedOptions[0].textContent.trim() : '')
                .filter(Boolean).join(' · ');
        }

        let ultimaConsulta = '';
        let pedido = 0;

        async function pedirChecklist(forzar = false) {
            const equipo = equipoElegido();

            if (!equipo.equipment_type_id) {
                origen.textContent = 'Elige el tipo, subtipo, marca y modelo en el paso de Equipo para armar el checklist de ese equipo.';
                btnRegenerar.hidden = true;
                return;
            }

            const consulta = new URLSearchParams(Object.entries(equipo).filter(([, v]) => v)).toString();
            if (!forzar && consulta === ultimaConsulta) return;
            ultimaConsulta = consulta;

            const este = ++pedido;
            origen.textContent = 'Armando el checklist para ' + nombreDelEquipo() + '...';
            btnRegenerar.hidden = true;
            lista.classList.add('cargando');

            try {
                const r = await fetch(URL_IA + '?' + consulta, { headers: { 'Accept': 'application/json' } });
                const data = await r.json();
                if (este !== pedido) return; // ya se eligió otro equipo

                const manuales = puntos.filter(p => p.manual);
                puntos = desdeGrupos(data.grupos).concat(manuales);
                pintar();

                origen.textContent = data.ia
                    ? 'Checklist armado por IA para ' + nombreDelEquipo() + '. Agrega abajo lo que falte.'
                    : (data.aviso || 'Se muestra el checklist general.');
            } catch (e) {
                if (este !== pedido) return;
                origen.textContent = 'No se pudo armar el checklist de este equipo. Se queda el que ya estaba.';
            } finally {
                if (este === pedido) {
                    lista.classList.remove('cargando');
                    btnRegenerar.hidden = false;
                }
            }
        }

        // Los selects se llenan en cadena: se espera a que se asienten.
        let espera = null;
        function programar() {
            clearTimeout(espera);
            espera = setTimeout(() => { if (esUsado()) pedirChecklist(); }, 500);
        }

        selects.forEach(s => s?.addEventListener('change', programar));
        form.querySelectorAll('[data-condicion]').forEach(r => r.addEventListener('change', function () {
            lista.querySelectorAll('input').forEach(c => { c.disabled = !esUsado(); });
            programar();
        }));
        btnRegenerar?.addEventListener('click', () => pedirChecklist(true));

        /* ===================== Arranque ===================== */
        if (PREVIO) {
            // Regresó del servidor con error: se rearma tal como estaba,
            // con sus respuestas, sin volver a preguntarle a la IA.
            puntos = Object.entries(PREVIO).map(([llave, p]) => ({
                llave,
                texto: p.texto || llave,
                manda: p.manda || '',
                grupo: p.grupo || 'Checklist',
                manual: llave.startsWith('manual_'),
            }));
            const previas = {};
            Object.entries(PREVIO).forEach(([llave, p]) => { previas[llave] = { r: p.r || null, nota: p.nota || '' }; });
            pintar(previas);
            ultimaConsulta = new URLSearchParams(Object.entries(equipoElegido()).filter(([, v]) => v)).toString();
            origen.textContent = 'Se conservó el checklist que ya habías contestado.';
            btnRegenerar.hidden = false;
        } else {
            puntos = desdeGrupos(GENERAL);
            pintar({});
            if (esUsado()) programar();
        }
    });
    </script>
@endpush
