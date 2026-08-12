<div id="newTechnicianModal" class="ns-modal-overlay ns-hidden" onclick="if(event.target === this) closeNewTechnicianModal()">
    <div class="ns-modal">
        <div class="ns-modal-header">
            <div>
                <div class="ns-modal-title">Agregar tecnico externo</div>
                <p class="ns-modal-subtitle">Completa los datos del nuevo tecnico externo.</p>
            </div>
            <button type="button" class="ns-modal-close" onclick="closeNewTechnicianModal()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="20" height="20"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <div class="ns-modal-body">
            <div class="ns-field full-width">
                <label>Nombre <span style="color:#ef4444">*</span></label>
                <input type="text" name="nombre" id="newNombre" placeholder="Nombre del técnico" oninput="enableSaveIfNewValid()" disabled>
            </div>
            <div class="ns-field">
                <label>Apellidos</label>
                <input type="text" name="apellidos" id="newApellidos" placeholder="Apellidos" disabled>
            </div>
            <div class="ns-field">
                <label>Teléfono</label>
                <input type="text" name="telefono" id="newTelefono" placeholder="Teléfono" disabled>
            </div>
            <div class="ns-field">
                <label>Correo</label>
                <input type="email" name="correo" id="newCorreo" placeholder="Correo electrónico" disabled>
            </div>
            <div class="ns-field">
                <label>Especialidad</label>
                <input type="text" name="especialidad" id="newEspecialidad" placeholder="Especialidad" disabled>
            </div>
            <div class="ns-field">
                <label>Domicilio</label>
                <input type="text" name="domicilio" id="newDomicilio" placeholder="Domicilio" disabled>
            </div>
            <div class="ns-field">
                <label>Empresa</label>
                <input type="text" name="empresa" id="newEmpresa" placeholder="Empresa" disabled>
            </div>
            <div class="ns-field full-width">
                <label>Foto (opcional)</label>
                <div class="ns-file-upload" id="photoUploadArea">
                    <input type="file" name="photo" id="newPhoto" accept="image/*" onchange="previewPhoto(this)" disabled>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                    <p id="photoUploadText">Haz clic aqui para subir una foto</p>
                    <small>JPEG, PNG o WebP</small>
                    <img id="photoPreview" class="ns-file-preview ns-hidden" alt="Vista previa">
                </div>
            </div>
        </div>
        <div class="ns-modal-footer">
            <button type="button" class="ns-btn ns-btn--ghost" onclick="closeNewTechnicianModal()">Cancelar</button>
            <button type="button" class="ns-btn ns-btn--primary" id="modalSaveBtn" onclick="submitNewTechnician()" disabled>Guardar tecnico externo</button>
        </div>
    </div>
</div>
