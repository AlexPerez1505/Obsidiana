@extends('structure.gestion_servicios.layout')

@section('title', 'Seleccionar tipo de servicio')

@section('service_content')
<style>
.wizard-header { display:flex; align-items:center; gap:14px; margin-bottom:22px; }
.wizard-icon { width:52px; height:52px; border-radius:14px; background:var(--primary-soft); color:var(--primary); display:flex; align-items:center; justify-content:center; }
.condition-card { 
    display:flex; 
    align-items:center; 
    gap:14px; 
    padding:20px; 
    border:2px solid var(--border); 
    border-radius:14px; 
    margin-bottom:16px; 
    background:var(--surface); 
    cursor:pointer; 
    transition:border-color .16s ease, background .16s ease;
}
.condition-card:hover { border-color:var(--primary); }
.condition-card.selected { border-color:var(--primary); background:var(--primary-soft); }
.condition-card .check { 
    border:2px solid var(--border); 
    border-radius:6px; 
    width:24px; 
    height:24px; 
    display:flex; 
    align-items:center; 
    justify-content:center; 
    flex-shrink:0;
}
.condition-card.selected .check { 
    background:var(--primary); 
    border-color:var(--primary); 
    color:#fff; 
}
.condition-card .info { flex:1; }
.condition-card .info strong { display:block; font-size:16px; margin-bottom:4px; }
.condition-card .info span { display:block; font-size:14px; color:var(--muted); }
.button-group { display:flex; gap:10px; margin-top:24px; }
.button-group button { flex:1; display:inline-flex; align-items:center; justify-content:center; gap:8px; }
</style>

<div class="card">
    <div class="wizard-header">
        <div class="wizard-icon">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M12 20h9"/>
                <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/>
            </svg>
        </div>
        <div>
            <h1 class="section-title" style="font-size:24px; margin:0;">Tipo de servicio</h1>
            <p class="muted" style="margin:4px 0 0;">Selecciona el tipo de mantenimiento a registrar</p>
        </div>
    </div>

    <div class="condition-card condition-card--externo" data-condition="externo">
        <div class="check">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                <polyline points="20 6 9 17 4 12"/>
            </svg>
        </div>
        <div class="info">
            <strong>Mantenimiento externo</strong>
            <span>El equipo se atiende fuera de las instalaciones del cliente.</span>
        </div>
    </div>

    <div class="condition-card condition-card--interno" data-condition="interno">
        <div class="check">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                <polyline points="20 6 9 17 4 12"/>
            </svg>
        </div>
        <div class="info">
            <strong>Mantenimiento interno</strong>
            <span>El tecnico asiste en las instalaciones del cliente.</span>
        </div>
    </div>

    <div class="button-group">
        <button type="button" class="btn btn--ghost" onclick="window.location.href = '{{ route('gestion.servicios.historial') }}'" style="display:inline-flex; align-items:center; justify-content:center; gap:8px;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="18" y1="6" x2="6" y2="18"/>
                <line x1="6" y1="6" x2="18" y2="18"/>
            </svg>
            Cancelar
        </button>
        <button type="button" class="btn" id="btn-continue" style="display:inline-flex; align-items:center; justify-content:center; gap:8px;">
            Continuar
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="9 18 15 12 9 6"/>
            </svg>
        </button>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const conditionCards = document.querySelectorAll('.condition-card');
    const btnContinue = document.getElementById('btn-continue');
    let selectedCondition = null;

    conditionCards.forEach(card => {
        card.addEventListener('click', () => {
            conditionCards.forEach(c => c.classList.remove('selected'));
            card.classList.add('selected');
            selectedCondition = card.dataset.condition;
        });
    });

    btnContinue.addEventListener('click', () => {
        if (!selectedCondition) {
            alert('Selecciona un tipo de mantenimiento para continuar.');
            return;
        }

        if (selectedCondition === 'externo') {
            // Limpiar datos anteriores para comenzar un nuevo servicio desde cero
            localStorage.removeItem('nueva_orden_draft_externo');
            localStorage.removeItem('saved_service_qr');
            localStorage.removeItem('current_service_qr');
            window.location.href = '{{ route("gestion.servicios.historial.nueva_orden.externo") }}';
        } else if (selectedCondition === 'interno') {
            alert('Mantenimiento interno - Próximamente disponible');
        }
    });
</script>
@endpush
