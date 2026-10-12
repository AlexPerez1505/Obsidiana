@props([
    'name',
    'doc',
])

{{--
    Subir un documento y, si ya hay uno, verlo.

    Los estilos viajan con el componente: antes vivían en el <style> de la
    pantalla de usuarios, así que usarlo en cualquier otra salía sin formato.
--}}

@once
    @push('head')
        <style>
            .hr-file-chip { display:flex; align-items:center; gap:10px; margin:2px 0 8px; flex-wrap:wrap; }

            .hr-file-btn { display:inline-flex; align-items:center; gap:6px; padding:6px 12px;
                           border-radius:8px; border:1px dashed var(--border); font-size:12px;
                           color:var(--muted); cursor:pointer; transition:border-color .15s, color .15s; }
            .hr-file-btn:hover { border-color:var(--primary); color:var(--primary); }
            .hr-file-btn svg { width:14px; height:14px; flex-shrink:0; }
            .hr-file-btn input[type="file"] { position:absolute; opacity:0; width:0; height:0; }

            .hr-file-name { max-width:140px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }

            .hr-file-link { display:inline-flex; align-items:center; gap:4px; font-size:12px;
                            color:var(--primary); text-decoration:none; }
            .hr-file-link svg { width:13px; height:13px; }
            .hr-file-link:hover { text-decoration:underline; }
        </style>
    @endpush
@endonce

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
