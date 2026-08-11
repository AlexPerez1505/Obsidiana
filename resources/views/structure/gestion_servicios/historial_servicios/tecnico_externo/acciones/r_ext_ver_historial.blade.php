<div class="step-panel" data-step="4" id="step-externo">
    <div class="resumen-grid">
        <!-- Acción Requerida -->
    <div class="resumen-card">
        <h3 class="resumen-title">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
            Acción Requerida
        </h3>

        <div class="resumen-alert">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
            <span>Registro protegido. Requiere captura vía formulario QR para asegurar identidad y firmas.</span>
        </div>

        @if(!($modo_ver ?? false))
        <div class="resumen-actions">
            <button type="button" class="resumen-btn resumen-btn--primary" id="btn-generar-qr" onclick="generarQrSinGuardar()">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M7 7h4v4H7z"/><path d="M13 7h4v4h-4z"/><path d="M7 13h4v4H7z"/><path d="M13 13h4v4h-4z"/></svg>
                Generar QR
            </button>
            <button type="button" class="resumen-btn resumen-btn--ghost" onclick="window.printQr()">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                Imprimir
            </button>
            @if($showSave ?? false)
            <button type="button" class="resumen-btn resumen-btn--primary" style="background:linear-gradient(135deg, #22C55E, #16A34A); color:#fff;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                Guardar
            </button>
            @endif
        </div>
        @endif

        <div id="resumen-qr-preview" style="display:none; margin-top:14px; text-align:center;">
            <p class="muted" style="font-size:13px; margin:0 0 8px;">QR generado:</p>
            <img id="resumen-qr-image" src="" alt="Código QR" style="max-width:100%; border-radius:8px; border:1px solid var(--border);">
            <p id="resumen-qr-token" style="font-family:monospace; font-size:12px; color:var(--muted); margin:8px 0 0;"></p>
            <div style="display:flex; gap:10px; justify-content:center; margin-top:10px; flex-wrap:wrap;">
                <a id="resumen-qr-download" href="#" download="qr.png" class="btn" style="display:inline-flex;">Descargar QR</a>
                <a id="resumen-qr-link" href="#" target="_blank" class="btn btn--ghost" style="display:inline-flex;">Abrir enlace</a>
            </div>
            <div id="resumen-order-links" style="display:none; gap:10px; justify-content:center; margin-top:10px; flex-wrap:wrap;">
                <a id="resumen-show-link" href="#" class="btn" style="display:inline-flex;">Ver orden</a>
                <a id="resumen-approvals-link" href="#" class="btn btn--ghost" style="display:inline-flex;">Aprobaciones</a>
            </div>
        </div>

        <ul class="resumen-list">
            <li>Aplica exclusivamente a mantenimientos externos.</li>
            <li>Genera un acceso controlado mediante token temporal.</li>
            <li>Sincroniza automáticamente el movimiento de salida foránea.</li>
        </ul>
    </div>

    <!-- Ficha Técnica del Servicio -->
    <div class="resumen-card">
        <h3 class="resumen-title">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
            Ficha Técnica del Servicio
        </h3>

        <div class="resumen-detail">
            <span class="resumen-label">IDENTIFICACIÓN</span>
            <span class="resumen-value" id="resumen-identificacion">endoscopia <span class="resumen-sep">|</span> adaptador_usb</span>
        </div>
        <div class="resumen-detail">
            <span class="resumen-label">NO. DE SERIE</span>
            <span class="resumen-value" id="resumen-serie">gtvgvegr</span>
        </div>
        <div class="resumen-detail">
            <span class="resumen-label">MARCA / MODELO</span>
            <span class="resumen-value" id="resumen-marca">dffrtgrtg ggagr</span>
        </div>
        <div class="resumen-detail">
            <span class="resumen-label">MÉDICO / TITULAR</span>
            <span class="resumen-value" id="resumen-cliente">gtvg</span>
        </div>
        <div class="resumen-detail">
            <span class="resumen-label">RESPONSABLE</span>
            <span class="resumen-value" id="resumen-responsable" style="font-weight:700;">Ing. José Alex Esquivel Perez</span>
        </div>
        <div class="resumen-detail">
            <span class="resumen-label">VALIDACIÓN OS</span>
            <span class="resumen-value resumen-pending">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                Pendiente
            </span>
        </div>
    </div>

    <!-- Ruta de Trabajo -->
    @include('structure.gestion_servicios.historial_servicios.tecnico_externo.tec_externo_interaciones.flujo_tec_ext.ruta_trajo', ['modo_ver' => $modo_ver ?? false])

    <!-- Auditoría de Movimientos -->
    <div class="resumen-card">
        <h3 class="resumen-title resumen-title--between">
            <span style="display:inline-flex; align-items:center; gap:8px;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                Auditoría de Movimientos
            </span>
            <span class="resumen-count">0 Eventos</span>
        </h3>

        <div class="resumen-empty">
            <svg width="42" height="42" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M4 14.899A7 7 0 1 1 15.71 8h1.79a4.5 4.5 0 0 1 2.5 8.242"/><path d="M12 12v9"/><path d="M8 17l4-4 4 4"/><path d="M8 12l4-4 4 4"/></svg>
            <p>Aún no se ha iniciado la bitácora de eventos para esta orden.</p>
        </div>
    </div>
</div>
</div>


<script>
    window.updateResumenExterno = function() {
        const tipo = document.getElementById('tipo_equipo')?.value || '—';
        const subtipo = document.getElementById('subtipo')?.value || '—';
        const marca = document.getElementById('marca')?.value || '—';
        const modelo = document.getElementById('modelo')?.value || '—';
        const serie = document.querySelector('input[name="serie"]')?.value || '—';
        const cliente = document.getElementById('tech-client-name')?.textContent?.trim() || '—';

        const selectedTechRow = document.querySelector('#ext-tech-list .tech-row.active');
        let responsable = '—';
        if (selectedTechRow) {
            responsable = selectedTechRow.querySelector('div > div div:first-child')?.textContent?.trim() || '—';
        }

        document.getElementById('resumen-identificacion').textContent = tipo + ' | ' + subtipo;
        document.getElementById('resumen-marca').textContent = marca + ' ' + modelo;
        document.getElementById('resumen-serie').textContent = serie;
        document.getElementById('resumen-cliente').textContent = cliente;
        document.getElementById('resumen-responsable').textContent = responsable;
    };

    document.querySelectorAll('#tipo_equipo, #subtipo, #marca, #modelo, input[name="serie"]').forEach(el => {
        el?.addEventListener('input', window.updateResumenExterno);
    });

    window.guardarServicio = function() {
        const form = document.getElementById('orden-form');
        const btnPrimary = document.getElementById('btn-primary');
        
        if (!form) {
            alert('No se encontró el formulario.');
            return;
        }

        // Deshabilitar el botón para evitar múltiples clicks
        if (btnPrimary) {
            btnPrimary.disabled = true;
            btnPrimary.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg> Guardando...';
        }

        const formData = new FormData(form);

        fetch(form.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
            },
        })
        .then(r => {
            if (!r.ok) throw new Error('Error ' + r.status);
            return r.text().then(text => {
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('Respuesta del servidor no es JSON:', text.substring(0, 500));
                    throw new Error('El servidor no devolvió JSON. Verifica la consola.');
                }
            });
        })
        .then(data => {
            // Guardar los datos del servicio en localStorage
            localStorage.setItem('current_service_qr', JSON.stringify({
                id: data.id,
                service_number: data.service_number,
                qr_token: data.qr_token,
                qr_url: data.qr_url,
                show_url: data.show_url,
                approvals_url: data.approvals_url,
                menu_url: data.menu_url,
            }));
            
            // Habilitar el botón de generar QR
            const btnGenerar = document.getElementById('btn-generar-qr');
            if (btnGenerar) {
                btnGenerar.disabled = false;
            }
            
            // Mostrar mensaje de éxito
            alert('✓ Servicio ' + data.service_number + ' guardado exitosamente.\n\nAhora puedes generar el QR haciendo click en "Generar QR".');
            
            // Redirigir al menú de historial después de 1.5 segundos
            setTimeout(() => {
                if (data.menu_url) {
                    window.location.href = data.menu_url;
                }
            }, 1500);
        })
        .catch(err => {
            alert('Error al guardar servicio: ' + err.message);
            console.error(err);
            
            // Re-habilitar el botón en caso de error
            if (btnPrimary) {
                btnPrimary.disabled = false;
                btnPrimary.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg> Guardar nuevo servicio';
            }
        });
    };

    window.generarQrSinGuardar = function() {
        const form = document.getElementById('orden-form');
        if (!form) {
            alert('No se encontró el formulario.');
            return;
        }

        // Verificar si ya existe un QR generado en esta sesión
        const savedQrData = localStorage.getItem('current_service_qr');
        if (savedQrData) {
            const data = JSON.parse(savedQrData);
            mostrarQr(data);
            return;
        }

        const formData = new FormData(form);

        fetch(form.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
            },
        })
        .then(r => {
            if (!r.ok) throw new Error('Error ' + r.status);
            return r.text().then(text => {
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('Respuesta del servidor no es JSON:', text.substring(0, 500));
                    throw new Error('El servidor no devolvió JSON. Verifica la consola.');
                }
            });
        })
        .then(data => {
            // Guardar el QR en localStorage para que sea único durante esta sesión
            localStorage.setItem('current_service_qr', JSON.stringify({
                id: data.id,
                service_number: data.service_number,
                qr_token: data.qr_token,
                qr_url: data.qr_url,
                show_url: data.show_url,
                approvals_url: data.approvals_url,
                menu_url: data.menu_url,
            }));
            mostrarQr(data);
        })
        .catch(err => {
            alert('Error al generar QR: ' + err.message);
            console.error(err);
        });
    };

    function mostrarQr(data) {
        const qrPreview = document.getElementById('resumen-qr-preview');
        const qrImage = document.getElementById('resumen-qr-image');
        const qrToken = document.getElementById('resumen-qr-token');
        const qrLink = document.getElementById('resumen-qr-link');
        const showLink = document.getElementById('resumen-show-link');
        const approvalsLink = document.getElementById('resumen-approvals-link');
        const orderLinks = document.getElementById('resumen-order-links');

        if (qrPreview && qrImage && data.qr_url && data.qr_token) {
            const imageUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=' + encodeURIComponent(data.qr_url);
            qrImage.src = imageUrl;
            if (qrToken) qrToken.textContent = data.qr_token;
            if (qrLink) {
                qrLink.href = data.qr_url;
                qrLink.textContent = 'Abrir enlace';
            }
            const qrDownload = document.getElementById('resumen-qr-download');
            if (qrDownload) {
                qrDownload.href = imageUrl;
                qrDownload.download = 'qr-' + data.service_number + '.png';
            }
            qrPreview.style.display = 'block';
            window.markRutaQrCompletado();
            qrPreview.scrollIntoView({ behavior: 'smooth' });

            const btnGenerar = document.getElementById('btn-generar-qr');
            if (btnGenerar) {
                btnGenerar.disabled = true;
                btnGenerar.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M7 7h4v4H7z"/><path d="M13 7h4v4h-4z"/><path d="M7 13h4v4H7z"/><path d="M13 13h4v4h-4z"/></svg> QR generado';
            }

            if (showLink) {
                showLink.href = data.show_url;
            }
            if (approvalsLink) {
                approvalsLink.href = data.approvals_url ?? '#';
            }
            if (orderLinks) {
                orderLinks.style.display = 'flex';
            }
        }
    }

    window.printQr = function() {
        const img = document.getElementById('resumen-qr-image');
        const identificacion = document.getElementById('resumen-identificacion')?.textContent?.trim() || '';
        const serie = document.getElementById('resumen-serie')?.textContent?.trim() || '';
        const responsable = document.getElementById('resumen-responsable')?.textContent?.trim() || '';
        if (!img || !img.src) {
            alert('Primero guarda y genera el QR.');
            return;
        }
        const w = window.open('', '_blank', 'width=420,height=540');
        if (!w) {
            alert('El navegador bloqueó la ventana de impresión.');
            return;
        }
        w.document.write(`
            <html>
                <head>
                    <title>Imprimir QR</title>
                    <style>
                        body { margin: 0; padding: 20px; font-family: Arial, sans-serif; background: #fff; color: #000; }
                        .print-box { max-width: 360px; margin: 0 auto; text-align: center; }
                        .print-qr img { width: 220px; height: 220px; }
                        .print-data { margin-top: 18px; text-align: left; font-size: 14px; }
                        .print-data p { margin: 6px 0; }
                        .print-label { color: #666; font-size: 12px; }
                        .print-value { font-weight: 700; }
                        @media print { body { margin: 0; } }
                    </style>
                </head>
                <body>
                    <div class="print-box">
                        <div class="print-qr">
                            <img src="${img.src}" alt="Código QR" onload="setTimeout(function(){ window.print(); }, 200);">
                        </div>
                        <div class="print-data">
                            <p><span class="print-label">Identificación:</span><br><span class="print-value">${identificacion}</span></p>
                            <p><span class="print-label">No. de serie:</span><br><span class="print-value">${serie}</span></p>
                            <p><span class="print-label">Responsable:</span><br><span class="print-value">${responsable}</span></p>
                        </div>
                    </div>
                </body>
            </html>
        `);
        w.document.close();
    };

    window.setRutaStepState = function(step, status) {
        step.classList.remove('resumen-step--done', 'resumen-step--active', 'resumen-step--pending', 'resumen-step--rechazado');
        const icon = step.querySelector('.resumen-step-icon');
        const statusEl = step.querySelector('.resumen-step-status');

        let stateClass = 'resumen-step--pending';
        let color = 'var(--muted)';
        let text = 'PENDIENTE';
        let svg = '<circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/>';

        if (status === 'completado') {
            stateClass = 'resumen-step--done';
            color = 'var(--green)';
            text = 'COMPLETADO';
            svg = '<path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>';
        } else if (status === 'activo') {
            stateClass = 'resumen-step--active';
            color = 'var(--primary)';
            text = 'EN PROCESO';
            svg = '<rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>';
        }

        step.classList.add(stateClass);
        if (icon) {
            icon.classList.remove('resumen-step-icon--active');
            if (status === 'activo') icon.classList.add('resumen-step-icon--active');
            icon.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">' + svg + '</svg>';
        }
        if (statusEl) {
            statusEl.style.color = color;
            statusEl.textContent = text;
        }
    };

    window.markRutaQrCompletado = function() {
        const steps = document.querySelectorAll('#ruta-pasos .resumen-step');
        for (let i = 0; i < steps.length; i++) {
            const nameEl = steps[i].querySelector('.resumen-step-name');
            if (nameEl && /Generaci[óo]n de QR/i.test(nameEl.textContent)) {
                window.setRutaStepState(steps[i], 'completado');
                if (steps[i + 1]) window.setRutaStepState(steps[i + 1], 'activo');
                return;
            }
        }
    };
</script>
