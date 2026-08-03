@extends('layouts.dashboard')

@section('title', 'Nueva Orden')

@section('content')
<style>
.wizard-top { display:flex; align-items:center; justify-content:space-between; gap:18px; flex-wrap:wrap; margin-bottom:20px; }
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
</style>

<div class="wizard-top">
    <div class="breadcrumb">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
        <span style="color:var(--text); font-weight:600;">Ordenes de Servicio</span>
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
        <span>Nueva Orden</span>
    </div>
    <div class="wizard-actions">
        <button type="button" class="btn btn--ghost" id="btn-secondary" style="display:inline-flex; align-items:center; gap:8px;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            Cancelar
        </button>
        <button type="button" class="btn" id="btn-primary" style="display:inline-flex; align-items:center; gap:8px;">
            Siguiente: Equipo
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
        </button>
    </div>
</div>

<div class="card" id="wizard-card">
    <div class="wizard-header">
        <div class="wizard-icon" id="wizard-icon">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
        </div>
        <div>
            <h1 class="section-title" style="font-size:24px; margin:0;" id="wizard-title">Nueva Orden</h1>
            <p class="muted" style="margin:4px 0 0;" id="wizard-subtitle">Crea una nueva orden de servicio en dos sencillos pasos</p>
        </div>
    </div>

    <div class="stepper">
        <div class="step active" data-step="1"><span class="dot">1</span> Cliente</div>
        <div class="line"></div>
        <div class="step" data-step="2"><span class="dot">2</span> Equipo</div>
        <div class="line"></div>
        <div class="step" data-step="3"><span class="dot">3</span> Tecnico</div>
    </div>

    <form id="orden-form" method="POST" action="#" autocomplete="off">
        @csrf

        @include('structure.gestion_servicios.historial_servicios.nueva_orden.c1_nueva_or', ['customers' => $customers])
        @include('structure.gestion_servicios.historial_servicios.nueva_orden.c2_nueva_or')
        @include('structure.gestion_servicios.historial_servicios.nueva_orden.c3_nueva_or')
    </form>
</div>
@endsection

@push('scripts')
<script>
    let currentStep = 1;

    const wizardTitle = document.getElementById('wizard-title');
    const wizardSubtitle = document.getElementById('wizard-subtitle');
    const wizardIcon = document.getElementById('wizard-icon');
    const btnPrimary = document.getElementById('btn-primary');
    const btnSecondary = document.getElementById('btn-secondary');
    const form = document.getElementById('orden-form');

    function updateStep() {
        document.querySelectorAll('.step-panel').forEach(p => p.classList.remove('active'));
        document.querySelector(`.step-panel[data-step="${currentStep}"]`).classList.add('active');
        document.querySelectorAll('.step').forEach(s => {
            const step = parseInt(s.dataset.step);
            s.classList.remove('active','done');
            if (step === currentStep) s.classList.add('active');
            else if (step < currentStep) s.classList.add('done');
        });

        if (currentStep === 1) {
            wizardTitle.textContent = 'Nueva Orden';
            wizardSubtitle.textContent = 'Crea una nueva orden de servicio en dos sencillos pasos';
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
        } else {
            wizardTitle.textContent = 'Final tecnico';
            wizardSubtitle.textContent = 'Asigna un especialista al servicio programmado';
            wizardIcon.innerHTML = '<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>';
            btnSecondary.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5"/><path d="M12 19l-7-7 7-7"/></svg> Regresar';
            btnPrimary.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg> Guardar Orden';
            btnPrimary.type = 'submit';
        }
    }

    btnPrimary.addEventListener('click', () => {
        if (currentStep < 3) {
            currentStep++;
            updateStep();
        }
    });
    btnSecondary.addEventListener('click', () => {
        if (currentStep > 1) {
            currentStep--;
            updateStep();
        } else {
            history.back();
        }
    });
    form.addEventListener('submit', (e) => {
        e.preventDefault();
        alert('Orden guardada (modo de prueba). Aqui conectaras el POST al backend.');
    });

</script>
@endpush
