@extends('structure.gestion_servicios.layout')

@section('title', 'Nuevo servicio')

@section('service_content')
<style>
#wizard-actions { display:flex; align-items:center; gap:10px; }
.breadcrumb { display:flex; align-items:center; gap:10px; font-size:14px; color:var(--muted); }
.breadcrumb a { color:var(--muted); text-decoration:none; }
.breadcrumb a:hover { color:var(--primary); }
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

<div class="card condition-screen" id="condition-screen">
    <div class="wizard-header">
        <div class="wizard-icon">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
        </div>
        <div>
            <h1 class="section-title" style="font-size:24px; margin:0;">Tipo de servicio</h1>
            <p class="muted" style="margin:4px 0 0;">Selecciona el tipo de mantenimiento a registrar</p>
        </div>
    </div>

    <div class="condition-card condition-card--externo" data-condition="externo">
        <div class="check"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg></div>
        <div class="info">
            <strong>Mantenimiento externo</strong>
            <span>El equipo se atiende fuera de las instalaciones del cliente.</span>
        </div>
    </div>

    <div class="condition-card condition-card--interno" data-condition="interno">
        <div class="check"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg></div>
        <div class="info">
            <strong>Mantenimiento interno</strong>
            <span>El tecnico asiste en las instalaciones del cliente.</span>
        </div>
    </div>

    <div style="display:flex; gap:10px; margin-top:8px;">
        <button type="button" class="btn btn--ghost" onclick="history.back()" style="flex:1; display:inline-flex; align-items:center; justify-content:center; gap:8px;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            Cancelar
        </button>
        <button type="button" class="btn" id="btn-start" style="flex:1; display:inline-flex; align-items:center; justify-content:center; gap:8px;">
            Continuar
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
        </button>
    </div>
</div>

<div class="card hidden" id="wizard-card" style="position:relative;">
    <div class="wizard-actions" id="wizard-actions" style="position:absolute; top:18px; right:18px; z-index:10;">
        <button type="button" class="btn btn--ghost" id="btn-secondary" style="display:inline-flex; align-items:center; gap:8px;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            Cancelar
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
            <h1 class="section-title" style="font-size:24px; margin:0;" id="wizard-title">Nuevo servicio</h1>
            <p class="muted" style="margin:4px 0 0;" id="wizard-subtitle">Crea un nuevo servicio</p>
        </div>
    </div>

    <div class="stepper">
        <div class="step active" data-step="1"><span class="dot">1</span> Cliente</div>
        <div class="line"></div>
        <div class="step" data-step="2"><span class="dot">2</span> Equipo</div>
        <div class="line"></div>
        <div class="step" data-step="3"><span class="dot">3</span> Tecnico</div>
        <div class="line"></div>
        <div class="step" data-step="4"><span class="dot">4</span> Cotizacion</div>
        <div class="line"></div>
        <div class="step" data-step="5"><span class="dot">5</span> Resumen</div>
    </div>

    <form id="orden-form" method="POST" action="{{ isset($invitation) ? route('public.nueva_orden.store', $invitation) : route('gestion.servicios.historial.nueva_orden.store') }}" autocomplete="off">
        @csrf
        @if(isset($invitation))
            <input type="hidden" name="invitation_token" value="{{ $invitation->token }}">
        @endif
        <input type="hidden" name="mantenimiento_externo" id="mantenimiento_externo" value="0">
        <input type="hidden" name="mantenimiento_interno" id="mantenimiento_interno" value="0">

        @include('structure.gestion_servicios.historial_servicios.registro_servicio.c1_registro_serv', ['customers' => $customers])
        @include('structure.gestion_servicios.historial_servicios.registro_servicio.c2_resgistro_serv')
        @include('structure.gestion_servicios.historial_servicios.registro_servicio.c3_tecnico_Int', ['internalTechnicians' => $internalTechnicians])
        @include('structure.gestion_servicios.historial_servicios.registro_servicio.c3_tecnico_ext', ['externalTechnicians' => $externalTechnicians])
        <div class="step-panel" data-step="4">
            @include('structure.gestion_servicios.historial_servicios.acciones_mn_hit_ser.u_menu_historial')
        </div>
        @include('structure.gestion_servicios.historial_servicios.acciones_mn_hit_ser.r_ext_menu_historial')
    </form>

    @include('structure.gestion_servicios.historial_servicios.registro_servicio.tec_externo.c_tec_externo')
</div>
@endsection

@push('scripts')
<script>
    let currentStep = 1;
    const FORM_STORAGE_KEY = 'nueva_orden_draft';

    const conditionScreen = document.getElementById('condition-screen');
    const wizardCard = document.getElementById('wizard-card');
    const conditionCards = document.querySelectorAll('.condition-card');
    const btnStart = document.getElementById('btn-start');
    const inputExterno = document.getElementById('mantenimiento_externo');
    const inputInterno = document.getElementById('mantenimiento_interno');

    function updateConditionSelection() {
        const externo = document.querySelector('.condition-card[data-condition="externo"]').classList.contains('selected');
        const interno = document.querySelector('.condition-card[data-condition="interno"]').classList.contains('selected');
        inputExterno.value = externo ? 1 : 0;
        inputInterno.value = interno ? 1 : 0;
    }

    conditionCards.forEach(card => {
        card.addEventListener('click', () => {
            conditionCards.forEach(c => c.classList.remove('selected'));
            card.classList.add('selected');
            updateConditionSelection();
        });
    });

    btnStart.addEventListener('click', () => {
        if (!parseInt(inputExterno.value) && !parseInt(inputInterno.value)) {
            alert('Selecciona al menos un tipo de mantenimiento.');
            return;
        }
        conditionScreen.classList.add('hidden');
        wizardCard.classList.remove('hidden');
        updateStep();
    });

    const wizardTitle = document.getElementById('wizard-title');
    const wizardSubtitle = document.getElementById('wizard-subtitle');
    const wizardIcon = document.getElementById('wizard-icon');
    const btnPrimary = document.getElementById('btn-primary');
    const btnSecondary = document.getElementById('btn-secondary');
    const form = document.getElementById('orden-form');

    function saveFormState() {
        const state = { currentStep, externo: inputExterno.value, interno: inputInterno.value };
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
        inputExterno.value = state.externo || '0';
        inputInterno.value = state.interno || '0';
        if (inputExterno.value === '1') document.querySelector('.condition-card[data-condition="externo"]')?.classList.add('selected');
        if (inputInterno.value === '1') document.querySelector('.condition-card[data-condition="interno"]')?.classList.add('selected');

        form.querySelectorAll('input:not([type="file"]):not([type="password"]):not([name="_token"]):not([type="checkbox"]):not([type="radio"]), select, textarea').forEach(el => {
            const key = el.name || el.id;
            if (key && state.hasOwnProperty(key)) el.value = state[key] || '';
        });
        form.querySelectorAll('input[type="checkbox"], input[type="radio"]').forEach(el => {
            if (el.name && state.hasOwnProperty(el.name)) el.checked = !!state[el.name];
        });

        if (inputExterno.value === '1' || inputInterno.value === '1') {
            conditionScreen.classList.add('hidden');
            wizardCard.classList.remove('hidden');
            updateStep();
        }
    }

    function updateStep() {
        document.querySelectorAll('.step-panel').forEach(p => p.classList.remove('active'));
        if (currentStep === 3) {
            const isExterno = parseInt(inputExterno.value);
            const isInterno = parseInt(inputInterno.value);
            if (isExterno) {
                document.getElementById('step-panel-externo')?.classList.add('active');
            } else if (isInterno) {
                document.getElementById('step-panel-interno')?.classList.add('active');
            }
        } else {
            document.querySelector(`.step-panel[data-step="${currentStep}"]`)?.classList.add('active');
        }
        document.querySelectorAll('.step').forEach(s => {
            const step = parseInt(s.dataset.step);
            s.classList.remove('active','done');
            if (step === currentStep) s.classList.add('active');
            else if (step < currentStep) s.classList.add('done');
        });

        if (currentStep === 1) {
            wizardTitle.textContent = 'Nuevo servicio';
            wizardSubtitle.textContent = 'Crea un nuevo servicio ';
            wizardIcon.innerHTML = '<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>';
            btnSecondary.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg> Cancelar';
            btnPrimary.innerHTML = 'Siguiente: Equipo <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>';
            btnPrimary.type = 'button';
        } else if (currentStep === 2) {
            wizardTitle.textContent = 'Registro';
            wizardSubtitle.textContent = 'Completa la informacion del equipo para continuar';
            wizardIcon.innerHTML = '<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>';
            btnSecondary.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5"/><path d="M12 19l-7-7 7-7"/></svg> Regresar';
            btnPrimary.innerHTML = 'Siguiente: Tecnico <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>';
            btnPrimary.type = 'button';
        } else if (currentStep === 3) {
            wizardTitle.textContent = 'Final tecnico';
            wizardSubtitle.textContent = 'Asigna un especialista al servicio programmado';
            wizardIcon.innerHTML = '<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>';
            btnSecondary.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5"/><path d="M12 19l-7-7 7-7"/></svg> Regresar';
            btnPrimary.innerHTML = 'Siguiente: Cotizacion <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>';
            btnPrimary.type = 'button';
        } else if (currentStep === 4) {
            wizardTitle.textContent = 'Cotizacion';
            wizardSubtitle.textContent = 'Agrega los conceptos de la cotizacion';
            wizardIcon.innerHTML = '<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>';
            btnSecondary.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5"/><path d="M12 19l-7-7 7-7"/></svg> Regresar';
            btnPrimary.innerHTML = 'Siguiente: Resumen <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>';
            btnPrimary.type = 'button';
        } else {
            wizardTitle.textContent = 'Resumen de Orden';
            wizardSubtitle.textContent = 'Revisa la informacion antes de guardar';
            wizardIcon.innerHTML = '<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>';
            btnSecondary.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5"/><path d="M12 19l-7-7 7-7"/></svg> Regresar';
            btnPrimary.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg> Guardar Orden';
            btnPrimary.type = 'submit';
        }
        saveFormState();
    }

    btnPrimary.addEventListener('click', () => {
        if (currentStep < 5) {
            currentStep++;
            updateStep();
        }
    });
    function resetWizard() {
        currentStep = 1;
        inputExterno.value = '0';
        inputInterno.value = '0';
        conditionCards.forEach(c => c.classList.remove('selected'));
        form.reset();
        localStorage.removeItem(FORM_STORAGE_KEY);
        wizardCard.classList.add('hidden');
        conditionScreen.classList.remove('hidden');
    }

    btnSecondary.addEventListener('click', () => {
        if (currentStep > 1) {
            currentStep--;
            updateStep();
        } else {
            resetWizard();
        }
    });

    form.addEventListener('input', saveFormState);
    form.addEventListener('change', saveFormState);

    // Forzar inicio en seleccion de tipo de servicio si no es invitacion publica
    @if(!isset($invitation))
        localStorage.removeItem(FORM_STORAGE_KEY);
    @endif

    restoreFormState();

</script>
@endpush
