@props([
    'name',
    'doc',
])

<div class="hr-file-chip">
    <label class="hr-file-btn">
        <x-gravityui-arrow-up-from-line />
        <span class="hr-file-name">Subir documento</span>
        <input type="file" name="{{ $name }}" accept=".pdf,.jpg,.jpeg,.png" class="hr-file-input">
    </label>
    <a href="#" class="hr-file-link" data-doc="{{ $doc }}" target="_blank" style="display:none;">
        <x-gravityui-eye />
        Ver actual
    </a>
</div>
