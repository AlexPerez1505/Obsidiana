@extends('structure.gestion_servicios.layout')

@section('title', 'Nuevo servicio externo')

@section('service_content')
<style>
#wizard-actions { display:flex; align-items:center; gap:10px; }
.wizard-actions { display:flex; align-items:center; gap:10px; }
.wizard-header { display:flex; align-items:center; gap:14px; margin-bottom:22px; }
.wizard-icon { width:52px; height:52px; border-radius:14px; background:var(--primary-soft); color:var(--primary); display:flex; align-items:center; justify-content:center; }
.stepper { display:flex; align-items:center; gap:16px; margin-bottom:28px; }
.step { display:flex; align-items:center; gap:8px; color:var(--muted); font-size:14px; font-weight:700; }
.step .dot { width:28px; height:28px; border-radius:50%; border:2px solid var(--border); display:flex; align-items:center; justify-content:center; font-size:13px; background:var(--surface); }
.step.active { color:var(--primary); }
.step.active .dot { background:var(--primary); color:#fff; border-color:var(--primary); }
.step.done { color:var(--green); }
.step.done .dot { background:var(--green); color:#fff; border-color:var(--green); }
.stepper .line { flex:1; height:2px; background:var(--border); border-radius:2px; max-width:70px; }
.step-panel { display:none; }
.step-panel.active { display:block; }
.client-tabs { display:flex; gap:12px; margin-bottom:18px; border-bottom:1px solid var(--border); padding-bottom:12px; }
.tab-btn { background:transparent; border:none; font-size:14px; font-weight:700; color:var(--muted); padding:8px 16px; cursor:pointer; border-bottom:2px solid transparent; display:inline-flex; align-items:center; gap:8px; }
.tab-btn.active { color:var(--primary); border-bottom-color:var(--primary); }
.search-box { position:relative; }
.search-box input { width:100%; padding:12px 12px 12px 40px; }
.search-box svg { position:absolute; left:12px; top:50%; transform:translateY(-50%); color:var(--muted); }
.client-card { display:flex; align-items:center; justify-content:space-between; gap:14px; padding:16px; border:1px solid var(--border); border-radius:14px; margin-bottom:10px; background:var(--surface); }
.client-card.selected { border-color:var(--primary); background:var(--primary-soft); }
.client-avatar { width:44px; height:44px; border-radius:50%; background:var(--primary); color:#fff; display:flex; align-items:center; justify-content:center; font-size:15px; font-weight:800; }
.client-meta { flex:1; }
.client-name { font-weight:700; font-size:15px; }
.client-info { font-size:13px; color:var(--muted); display:flex; align-items:center; gap:12px; margin-top:4px; }
.form-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:18px; margin-top:18px; }
.form-group label { font-size:13px; font-weight:700; margin-bottom:6px; display:block; }
.form-group input, .form-group select, .form-group textarea { width:100%; padding:11px 12px; border:1px solid var(--border); border-radius:9px; background:var(--surface); color:var(--text); font-size:14px; }
.form-group input:focus, .form-group select:focus, .form-group textarea:focus { border-color:var(--primary); outline:none; }
.form-group input::placeholder, .form-group textarea::placeholder { color:#aaa; }
.date-row { display:grid; grid-template-columns:repeat(3,1fr); gap:8px; }
.upload-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(130px,1fr)); gap:12px; margin-top:8px; }
.upload-card { border:1px dashed var(--border); border-radius:12px; padding:14px; text-align:center; cursor:pointer; color:var(--muted); background:var(--surface); }
.upload-card:hover { border-color:var(--primary); color:var(--primary); }
.technician-list { display:flex; flex-direction:column; gap:10px; }
.tech-row { display:flex; align-items:center; justify-content:space-between; gap:14px; padding:14px; border:1px solid var(--border); border-radius:14px; cursor:pointer; }
.tech-row.active { border-color:var(--primary); background:var(--primary-soft); }
.tech-avatar { width:40px; height:40px; border-radius:50%; background:var(--primary); color:#fff; display:flex; align-items:center; justify-content:center; font-weight:800; }
.badge { padding:3px 10px; border-radius:999px; font-size:12px; font-weight:700; }
.badge.ok { background:var(--green-soft); color:var(--green); }
.badge.warn { background:var(--accent-soft); color:var(--accent); }
.badge.danger { background:var(--danger-soft); color:var(--danger); }
.summary-pill { display:inline-flex; align-items:center; gap:8px; padding:8px 14px; border:1px solid var(--border); border-radius:999px; font-size:13px; }
.signature-box { border:1px dashed var(--border); border-radius:12px; width:100%; height:120px; }
.hidden { display:none !important; }
</style>

<div class="card" id="wizard-card" style="position:relative;">
    <div class="wizard-actions" id="wizard-actions" style="position:absolute; top:18px; right:18px; z-index:10;">
        <button type="button" class="btn btn--ghost" id="btn-cancel-wizard" style="display:inline-flex; align-items:center; gap:8px;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            Cancelar e iniciar
        </button>
        <button type="button" class="btn btn--ghost" id="btn-secondary" style="display:inline-flex; align-items:center; gap:8px;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5"/><path d="M12 19l-7-7 7-7"/></svg>
            Regresar
        </button>
        <button type="button" class="btn" id="btn-primary" style="display:inline-flex; align-items:center; gap:8px;">
            Siguiente: Equipo
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
        </button>
    </div>
    
    <div class="wizard-header">
        <div class="wizard-icon" id="wizard-icon">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
        </div>
        <div>
            <h1 class="section-title" style="font-size:24px; margin:0;" id="wizard-title">Nuevo servicio externo</h1>
            <p class="muted" style="margin:4px 0 0;" id="wizard-subtitle">Crea un nuevo servicio de mantenimiento externo</p>
        </div>
    </div>

    <div class="stepper">
        <div class="step active" data-step="1"><span class="dot">1</span> Cliente</div>
        <div class="line"></div>
        <div class="step" data-step="2"><span class="dot">2</span> Equipo</div>
        <div class="line"></div>
        <div class="step" data-step="3"><span class="dot">3</span> Tecnico</div>
    </div>

    <form id="orden-form" method="POST" action="{{ isset($invitation) ? route('public.nueva_orden.store', $invitation) : route('gestion.servicios.historial.nueva_orden.store') }}" autocomplete="off" enctype="multipart/form-data">
        @csrf
        @if(isset($invitation))
            <input type="hidden" name="invitation_token" value="{{ $invitation->token }}">
        @endif
        <input type="hidden" name="mantenimiento_externo" id="mantenimiento_externo" value="1">

        @include('structure.gestion_servicios.historial_servicios.tecnico_externo.registro_servicio_externo.ct1_registro_serv', ['customers' => $customers])
        @include('structure.gestion_servicios.historial_servicios.tecnico_externo.registro_servicio_externo.ct2_resgistro_serv')
        @include('structure.gestion_servicios.historial_servicios.tecnico_externo.registro_servicio_externo.ct3_tecnico_ext', ['externalTechnicians' => $externalTechnicians])
        @include('structure.gestion_servicios.historial_servicios.tecnico_externo.acciones.r_ext_ver_historial')
    </form>

    @include('structure.gestion_servicios.historial_servicios.tecnico_externo.registro_servicio_externo.tec_externo.c_tec_externo')
</div>
@endsection

@push('scripts')
<script>
    let currentStep = 1;
    let isSaving = false;
    const FORM_STORAGE_KEY = 'nueva_orden_draft_externo';

    const wizardTitle = document.getElementById('wizard-title');
    const wizardSubtitle = document.getElementById('wizard-subtitle');
    const wizardIcon = document.getElementById('wizard-icon');
    const btnPrimary = document.getElementById('btn-primary');
    const btnSecondary = document.getElementById('btn-secondary');
    const btnCancelWizard = document.getElementById('btn-cancel-wizard');
    const form = document.getElementById('orden-form');

    function saveFormState() {
        const state = { currentStep };
        form.querySelectorAll('input:not([type="file"]):not([type="password"]):not([name="_token"]):not([type="checkbox"]):not([type="radio"]), select, textarea').forEach(el => {
            if (el.name || el.id) state[el.name || el.id] = el.value;
        });
        form.querySelectorAll('input[type="checkbox"], input[type="radio"]').forEach(el => {
            if (el.name) state[el.name] = el.checked;
        });
        localStorage.setItem(FORM_STORAGE_KEY, JSON.stringify(state));
    }

    function restoreFormState() {
        const saved = localStorage.getItem(FORM_STORAGE_KEY);
        if (!saved) return;
        const state = JSON.parse(saved);
        if (state.currentStep) currentStep = parseInt(state.currentStep);

        form.querySelectorAll('input:not([type="file"]):not([type="password"]):not([name="_token"]):not([type="checkbox"]):not([type="radio"]), select, textarea').forEach(el => {
            const key = el.name || el.id;
            if (key && state.hasOwnProperty(key)) el.value = state[key] || '';
        });
        form.querySelectorAll('input[type="checkbox"], input[type="radio"]').forEach(el => {
            if (el.name && state.hasOwnProperty(el.name)) el.checked = !!state[el.name];
        });

        const savedQr = localStorage.getItem('saved_service_qr');
        if (savedQr) {
            const data = JSON.parse(savedQr);
            if (!data.show_url) {
                localStorage.removeItem('saved_service_qr');
                return;
            }
            const qrPreview = document.getElementById('resumen-qr-preview');
            const qrImage = document.getElementById('resumen-qr-image');
            const qrToken = document.getElementById('resumen-qr-token');
            const qrLink = document.getElementById('resumen-qr-link');
            const qrDownload = document.getElementById('resumen-qr-download');
            if (qrPreview && qrImage) {
                qrImage.src = data.qr_image_url;
                if (qrToken) qrToken.textContent = data.qr_token;
                if (qrLink) qrLink.href = data.qr_url;
                if (qrDownload) {
                    qrDownload.href = data.qr_image_url;
                    qrDownload.download = 'qr-' + data.service_number + '.png';
                }
                const showLink = document.getElementById('resumen-show-link');
                const approvalsLink = document.getElementById('resumen-approvals-link');
                const orderLinks = document.getElementById('resumen-order-links');
                if (showLink) showLink.href = data.show_url ?? '#';
                if (approvalsLink) approvalsLink.href = data.approvals_url ?? '#';
                if (orderLinks) orderLinks.style.display = 'flex';
                qrPreview.style.display = 'block';
                window.markRutaQrCompletado();
            }
            const resumenBtn = document.querySelector('#step-externo .resumen-btn--primary');
            if (resumenBtn) {
                resumenBtn.disabled = true;
                resumenBtn.innerHTML = 'QR generado';
            }
            if (btnPrimary && data.show_url) {
                btnPrimary.type = 'button';
                btnPrimary.removeAttribute('form');
                btnPrimary.disabled = true;
            }
            isSaving = true;
        }

        updateStep();
    }

    function updateStep() {
        document.querySelectorAll('.step-panel').forEach(p => p.classList.remove('active'));
        document.querySelector(`.step-panel[data-step="${currentStep}"]`)?.classList.add('active');
        
        document.querySelectorAll('.step').forEach(s => {
            const step = parseInt(s.dataset.step);
            s.classList.remove('active','done');
            if (step === currentStep) s.classList.add('active');
            else if (step < currentStep) s.classList.add('done');
        });

        if (currentStep === 1) {
            wizardTitle.textContent = 'Nuevo servicio externo';
            wizardSubtitle.textContent = 'Selecciona el cliente para el servicio';
            wizardIcon.innerHTML = '<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>';
            btnSecondary.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg> Cancelar';
            btnPrimary.innerHTML = 'Siguiente: Equipo <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>';
            btnPrimary.type = 'button';
            btnPrimary.removeAttribute('form');
        } else if (currentStep === 2) {
            wizardTitle.textContent = 'Registro del equipo';
            wizardSubtitle.textContent = 'Completa la información del equipo para continuar';
            wizardIcon.innerHTML = '<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>';
            btnSecondary.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5"/><path d="M12 19l-7-7 7-7"/></svg> Regresar';
            btnPrimary.innerHTML = 'Siguiente: Tecnico <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>';
            btnPrimary.type = 'button';
            btnPrimary.removeAttribute('form');
        } else if (currentStep === 3) {
            wizardTitle.textContent = 'Asignar técnico';
            wizardSubtitle.textContent = 'Selecciona un técnico especializado para el servicio';
            wizardIcon.innerHTML = '<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>';
            btnSecondary.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5"/><path d="M12 19l-7-7 7-7"/></svg> Regresar';
            btnPrimary.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg> Guardar registro';
            btnPrimary.type = 'button';
            btnPrimary.removeAttribute('form');
        } else {
            wizardTitle.textContent = 'Resumen de Orden';
            wizardSubtitle.textContent = 'Revisa la información antes de guardar';
            wizardIcon.innerHTML = '<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>';
            btnSecondary.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5"/><path d="M12 19l-7-7 7-7"/></svg> Regresar';
            btnPrimary.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg> Guardar nuevo servicio';
            btnPrimary.type = 'button';
            btnPrimary.removeAttribute('form');
        }
        
        btnCancelWizard.style.display = currentStep === 1 ? 'none' : 'inline-flex';
        saveFormState();
    }

    btnPrimary.addEventListener('click', () => {
        if (isSaving) return;
        if (currentStep < 4) {
            currentStep++;
            updateStep();
        } else if (currentStep === 4) {
            isSaving = true;
            btnPrimary.disabled = true;
            window.guardarServicio();
        }
    });

    function resetWizard() {
        currentStep = 1;
        form.reset();
        localStorage.removeItem(FORM_STORAGE_KEY);
        localStorage.removeItem('saved_service_qr');

        const resumenBtn = document.querySelector('#step-externo .resumen-btn--primary');
        if (resumenBtn) {
            resumenBtn.disabled = false;
            resumenBtn.innerHTML = `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M7 7h4v4H7z"/><path d="M13 7h4v4h-4z"/><path d="M7 13h4v4H7z"/><path d="M13 13h4v4h-4z"/></svg> Guardar y generar QR`;
        }
        if (btnPrimary) {
            btnPrimary.disabled = false;
        }

        window.location.href = '{{ route("gestion.servicios.historial.nueva_orden.type") }}';
    }

    btnSecondary.addEventListener('click', () => {
        if (currentStep > 1) {
            currentStep--;
            updateStep();
        } else {
            resetWizard();
        }
    });

    btnCancelWizard.addEventListener('click', () => {
        if (confirm('¿Estás seguro de cancelar el registro? Se perderán los datos ingresados.')) {
            resetWizard();
        }
    });

    form.addEventListener('submit', function(e) {
        if (isSaving) {
            e.preventDefault();
            return;
        }
        if (currentStep === 4) {
            e.preventDefault();
            isSaving = true;
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

                    const resumenBtn = document.querySelector('#step-externo .resumen-btn--primary');
                    if (resumenBtn) {
                        resumenBtn.disabled = true;
                        resumenBtn.innerHTML = 'QR generado';
                    }

                    if (btnPrimary) {
                        btnPrimary.type = 'button';
                        btnPrimary.removeAttribute('form');
                        btnPrimary.disabled = true;
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

                    localStorage.setItem('saved_service_qr', JSON.stringify({
                        id: data.id,
                        service_number: data.service_number,
                        qr_token: data.qr_token,
                        qr_url: data.qr_url,
                        qr_image_url: imageUrl,
                        show_url: data.show_url,
                        approvals_url: data.approvals_url,
                        menu_url: data.menu_url,
                    }));

                    setTimeout(() => {
                        if (data.menu_url) window.location.href = data.menu_url;
                    }, 1500);
                }
            })
            .catch(err => {
                isSaving = false;
                alert('Error al guardar: ' + err.message);
                console.error(err);
            });
        } else {
            form.submit();
        }
    });

    form.addEventListener('input', saveFormState);
    form.addEventListener('change', saveFormState);
    localStorage.removeItem('saved_service_qr');
    restoreFormState();
</script>
@endpush
