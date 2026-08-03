        <!-- Paso 2: Equipo -->
        <div class="step-panel" data-step="2">
            <div style="display:flex; align-items:center; justify-content:space-between; gap:14px; flex-wrap:wrap; margin-bottom:22px;">
                <div style="display:flex; align-items:center; gap:12px;">
                    <div class="client-avatar">JD</div>
                    <div>
                        <div class="client-name" id="sel-client-name">DR. Jhone Doe</div>
                        <div class="client-info">Cliente seleccionado</div>
                    </div>
                </div>
                <div style="display:flex; align-items:center; gap:12px; color:var(--muted); font-size:14px;">
                    Registrado por: <strong style="color:var(--text);">ING. ALEX ESQUIVEL</strong>
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                </div>
            </div>

            <h3 style="display:flex; align-items:center; gap:10px; font-size:18px; margin:0 0 8px;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" color="var(--primary)"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
                Datos del equipo
            </h3>
            <p class="muted" style="margin:0 0 18px; font-size:13px;">Ingresa la informacion del equipo que recibira el servicio tecnico</p>

            <div class="form-grid">
                <div class="form-group">
                    <label>Tipo de equipo</label>
                    <select name="tipo_equipo"><option>Selecciona un tipo</option></select>
                </div>
                <div class="form-group">
                    <label>Subtipo</label>
                    <select name="subtipo"><option>Selecciona un subtipo</option></select>
                </div>
                <div class="form-group">
                    <label>Marca</label>
                    <input type="text" name="marca" placeholder="Ej. Olympus">
                </div>
                <div class="form-group">
                    <label>Modelo</label>
                    <input type="text" name="modelo" placeholder="Ej. C-90">
                </div>
                <div class="form-group">
                    <label>Numero de serie</label>
                    <input type="text" name="serie" placeholder="Ej. SN-893-832">
                </div>
                <div class="form-group">
                    <label>Ano</label>
                    <input type="text" name="anio" placeholder="Ej. 2026">
                </div>
                <div class="form-group">
                    <label>Fecha de adquisicion</label>
                    <div class="date-row">
                        <input type="text" name="adq_dd" placeholder="DD">
                        <input type="text" name="adq_mm" placeholder="MM">
                        <input type="text" name="adq_yyyy" placeholder="YYYY">
                    </div>
                </div>
                <div class="form-group" style="grid-column:1/-1;">
                    <label>Observaciones</label>
                    <textarea name="observaciones" rows="3" placeholder="Anotaciones sobre el estado del equipo"></textarea>
                </div>
            </div>

            <div class="form-group" style="margin-top:18px;">
                <label>Evidencia del equipo</label>
                <div class="upload-grid">
                    <label class="upload-card">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                        <div style="font-size:13px; margin-top:8px;">Imagen 1</div>
                        <div style="font-size:12px;">Toca para subir</div>
                        <input type="file" name="evidencia_1" accept="image/*" style="display:none;">
                    </label>
                    <label class="upload-card">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                        <div style="font-size:13px; margin-top:8px;">Imagen 2</div>
                        <div style="font-size:12px;">Toca para subir</div>
                        <input type="file" name="evidencia_2" accept="image/*" style="display:none;">
                    </label>
                    <label class="upload-card">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                        <div style="font-size:13px; margin-top:8px;">Imagen 3</div>
                        <div style="font-size:12px;">Toca para subir</div>
                        <input type="file" name="evidencia_3" accept="image/*" style="display:none;">
                    </label>
                    <label class="upload-card">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="23 7 23 17 7 17 7 7 23 7"/><rect x="1" y="3" width="4" height="18" rx="1"/><polyline points="5 7 7 7 7 17 5 17"/></svg>
                        <div style="font-size:13px; margin-top:8px;">Video</div>
                        <div style="font-size:12px;">Toca para subir</div>
                        <input type="file" name="evidencia_video" accept="video/*" style="display:none;">
                    </label>
                </div>
                <p style="font-size:12px; color:var(--muted); margin-top:8px;">Formatos permitidos: JPG, PNG, MP4. Tamano maximo: 10MB por archivo</p>
            </div>

            <div class="form-group" style="margin-top:18px;">
                <label>Firma Digital</label>
                <canvas class="signature-box" id="signature-pad"></canvas>
                <a href="#" style="font-size:13px; color:var(--primary);" onclick="clearSignature(); return false;">Limpiar firma</a>
            </div>
        </div>

@push('scripts')
<script>
    // Firma basica (canvas vacio)
    const canvas = document.getElementById('signature-pad');
    const ctx = canvas.getContext('2d');
    function resizeCanvas() {
        const rect = canvas.getBoundingClientRect();
        canvas.width = rect.width;
        canvas.height = rect.height;
    }
    resizeCanvas();
    window.addEventListener('resize', resizeCanvas);

    let drawing = false;
    canvas.addEventListener('mousedown', e => { drawing = true; ctx.beginPath(); ctx.moveTo(e.offsetX, e.offsetY); });
    canvas.addEventListener('mousemove', e => { if (drawing) { ctx.lineTo(e.offsetX, e.offsetY); ctx.stroke(); } });
    canvas.addEventListener('mouseup', () => drawing = false);
    canvas.addEventListener('mouseout', () => drawing = false);
    canvas.addEventListener('touchstart', e => { e.preventDefault(); drawing = true; const t = e.touches[0]; const r = canvas.getBoundingClientRect(); ctx.beginPath(); ctx.moveTo(t.clientX - r.left, t.clientY - r.top); });
    canvas.addEventListener('touchmove', e => { e.preventDefault(); if (drawing) { const t = e.touches[0]; const r = canvas.getBoundingClientRect(); ctx.lineTo(t.clientX - r.left, t.clientY - r.top); ctx.stroke(); } });
    canvas.addEventListener('touchend', () => drawing = false);

    function clearSignature() {
        const rect = canvas.getBoundingClientRect();
        ctx.clearRect(0, 0, rect.width, rect.height);
    }
    window.clearSignature = clearSignature;
</script>
@endpush
