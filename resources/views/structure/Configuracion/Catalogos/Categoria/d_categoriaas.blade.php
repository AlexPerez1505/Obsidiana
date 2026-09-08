@extends('structure.Configuracion.layout')

@section('title', 'Eliminar Categoría')
@section('page-title', 'Eliminar Categoría')

@section('configuracion_content')
    <form method="POST" action="{{ route('configuracion.categorias.destroy', $category) }}" class="cat-form">
        @csrf
        @method('DELETE')

        <x-ui.card>
            <x-ui.section-title style="margin:0 0 4px;">Confirmar eliminación</x-ui.section-title>
            <p class="muted" style="margin:0; font-size:13.5px;">
                Vas a eliminar la siguiente categoría del catálogo.
            </p>

            <div class="danger-box">
                <div class="cat-del">
                    <span class="cat-del-ico">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M10 11v6M14 11v6"/></svg>
                    </span>
                    <div>
                        <div class="cat-del-name">{{ $category->nombre }}</div>
                        <div class="cat-del-note">Esta acción no se puede deshacer.</div>
                    </div>
                </div>
            </div>
        </x-ui.card>

        <div class="page-foot">
            <a href="{{ route('configuracion.catalogos.index') }}" class="btn btn--ghost">Cancelar</a>
            <button type="submit" class="btn btn--danger">Eliminar categoría</button>
        </div>
    </form>

    <style>
        .cat-form { max-width:560px; }
        .cat-del { display:flex; align-items:center; gap:14px; }
        .cat-del-ico { flex:0 0 auto; display:flex; align-items:center; justify-content:center;
                       width:40px; height:40px; border-radius:11px;
                       background:var(--surface); color:var(--danger); }
        .cat-del-ico svg { width:18px; height:18px; }
        .cat-del-name { font-size:15px; font-weight:600; overflow-wrap:anywhere; }
        .cat-del-note { margin-top:2px; color:var(--muted); font-size:13px; }
    </style>
@endsection
