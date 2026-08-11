@props([
    'name',
    'doc',
])

<div class="hr-file-chip">
    <label class="hr-file-btn">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="M17 8l-5-5-5 5"/><path d="M12 3v12"/></svg>
        <span class="hr-file-name">Subir documento</span>
        <input type="file" name="{{ $name }}" accept=".pdf,.jpg,.jpeg,.png" class="hr-file-input">
    </label>
    <a href="#" class="hr-file-link" data-doc="{{ $doc }}" target="_blank" style="display:none;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
        Ver actual
    </a>
</div>
