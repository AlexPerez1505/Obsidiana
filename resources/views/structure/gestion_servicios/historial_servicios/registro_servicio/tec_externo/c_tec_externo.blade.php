<div class="modal-overlay hidden" id="add-ext-modal">
    <div class="modal-card card">
        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:14px;">
            <h3 style="margin:0; font-size:17px;">Agregar tecnico externo</h3>
            <button type="button" class="btn btn--ghost" id="btn-close-ext-modal" aria-label="Cerrar">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <form id="add-ext-tech-form" method="POST" action="{{ route('gestion.servicios.historial.external_technicians.store') }}" enctype="multipart/form-data">
            @csrf
            <p class="muted" style="font-size:13px; margin:0 0 12px;">Completa los datos del nuevo tecnico externo.</p>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
                <div class="form-group">
                    <label>Nombre <span style="color:var(--danger)">*</span></label>
                    <input type="text" name="name" required>
                </div>
                <div class="form-group">
                    <label>Telefono <span class="muted" style="font-size:12px;">(opcional)</span></label>
                    <input type="tel" name="phone" inputmode="tel" pattern="[0-9+\s\-()]{7,30}" title="Ingresa un telefono valido (7 a 30 caracteres)">
                </div>
                <div class="form-group">
                    <label>Email <span class="muted" style="font-size:12px;">(opcional)</span></label>
                    <input type="email" name="email" placeholder="ejemplo@correo.com">
                </div>
                <div class="form-group">
                    <label>Empresa <span class="muted" style="font-size:12px;">(opcional)</span></label>
                    <input type="text" name="company">
                </div>
                <div class="form-group">
                    <label>Especialidad</label>
                    <input type="text" name="specialty">
                </div>
                <div class="form-group">
                    <label>Ubicacion <span class="muted" style="font-size:12px;">(opcional)</span></label>
                    <input type="text" name="location">
                </div>
            </div>
            <div class="form-group" style="margin-top:12px;">
                <label>Notas <span class="muted" style="font-size:12px;">(opcional)</span></label>
                <textarea name="address" rows="2" placeholder="Notas adicionales..."></textarea>
            </div>
            <div class="form-group" style="margin-top:12px;">
                <label for="ext-photo-input">Foto <span class="muted" style="font-size:12px;">(opcional)</span></label>
                <input type="file" id="ext-photo-input" name="photo" accept="image/*" style="display:none;">
                <label for="ext-photo-input" class="photo-upload">
                    <img id="ext-photo-preview" src="" alt="Vista previa" style="display:none;">
                    <span id="ext-photo-placeholder" class="muted">Haz clic aqui para subir una foto</span>
                </label>
            </div>
            <div style="display:flex; gap:10px; margin-top:14px;">
                <button type="button" class="btn btn--ghost" id="btn-cancel-add-ext">Cancelar</button>
                <button type="submit" class="btn">Guardar tecnico externo</button>
            </div>
        </form>
    </div>
</div>

<style>
    #add-ext-modal.modal-overlay {
        position: fixed;
        inset: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 1000;
        background: rgba(0, 0, 0, 0.65);
        backdrop-filter: blur(6px);
        -webkit-backdrop-filter: blur(6px);
        padding: 22px;
    }
    #add-ext-modal .modal-card {
        width: 100%;
        max-width: 560px;
        max-height: 90vh;
        overflow-y: auto;
    }
    .photo-upload {
        display: flex;
        align-items: center;
        justify-content: center;
        flex-direction: column;
        gap: 8px;
        min-height: 120px;
        border: 2px dashed rgba(255, 255, 255, 0.18);
        border-radius: 10px;
        padding: 18px;
        cursor: pointer;
        text-align: center;
        transition: .16s;
    }
    .photo-upload:hover {
        border-color: var(--accent);
        background: rgba(255, 255, 255, 0.03);
    }
    .photo-upload img {
        max-width: 100%;
        max-height: 120px;
        border-radius: 8px;
        object-fit: cover;
    }
    :root[data-theme="light"] .photo-upload {
        border-color: rgba(15, 23, 42, 0.18);
    }
    :root[data-theme="light"] .photo-upload:hover {
        background: rgba(15, 23, 42, 0.04);
    }
    :root[data-theme="light"] #add-ext-modal.modal-overlay { background: rgba(15, 23, 42, 0.35); }
</style>

@push('scripts')
<script>
    const extModal = document.getElementById('add-ext-modal');
    const photoInput = document.getElementById('ext-photo-input');
    const photoPreview = document.getElementById('ext-photo-preview');
    const photoPlaceholder = document.getElementById('ext-photo-placeholder');

    function openExtModal() {
        if (extModal) extModal.classList.remove('hidden');
    }

    function closeExtModal() {
        if (extModal) {
            extModal.classList.add('hidden');
            const form = document.getElementById('add-ext-tech-form');
            if (form) form.reset();
            if (photoPreview) {
                URL.revokeObjectURL(photoPreview.src);
                photoPreview.src = '';
                photoPreview.style.display = 'none';
            }
            if (photoPlaceholder) photoPlaceholder.style.display = 'block';
            if (photoInput) photoInput.value = '';
        }
        if (window.extTechnicians && window.extTechnicians.length && typeof window.selectExtTech === 'function') {
            window.selectExtTech(0);
        }
    }

    if (photoInput) {
        photoInput.addEventListener('change', () => {
            const file = photoInput.files[0];
            if (file) {
                photoPreview.src = URL.createObjectURL(file);
                photoPreview.style.display = 'block';
                photoPlaceholder.style.display = 'none';
            } else {
                photoPreview.style.display = 'none';
                photoPlaceholder.style.display = 'block';
            }
        });
    }

    const btnShowAddExt = document.getElementById('btn-show-add-ext');
    const btnCancelAddExt = document.getElementById('btn-cancel-add-ext');
    const btnCloseExtModal = document.getElementById('btn-close-ext-modal');

    if (btnShowAddExt) {
        btnShowAddExt.addEventListener('click', openExtModal);
    }

    if (btnCancelAddExt) {
        btnCancelAddExt.addEventListener('click', closeExtModal);
    }

    if (btnCloseExtModal) {
        btnCloseExtModal.addEventListener('click', closeExtModal);
    }

    if (extModal) {
        extModal.addEventListener('click', (e) => {
            if (e.target === extModal) closeExtModal();
        });
    }

    const extForm = document.getElementById('add-ext-tech-form');
    if (extForm) {
        extForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            e.stopPropagation();
            const formData = new FormData(extForm);
            const token = extForm.querySelector('input[name="_token"]')?.value;
            try {
                const response = await fetch(extForm.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': token,
                        'Accept': 'application/json'
                    },
                    credentials: 'same-origin'
                });
                if (!response.ok) {
                    const errorData = await response.json().catch(() => ({}));
                    const message = errorData.message
                        || (errorData.errors ? Object.values(errorData.errors).flat().join('\n') : null)
                        || `Error ${response.status}`;
                    throw new Error(message);
                }
                const tech = await response.json();
                closeExtModal();
                if (typeof window.addExtTechnician === 'function') {
                    window.addExtTechnician(tech);
                } else {
                    location.reload();
                }
            } catch (err) {
                alert('No se pudo guardar el tecnico: ' + err.message);
            }
        });
    }
</script>
@endpush
