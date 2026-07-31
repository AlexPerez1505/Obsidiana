@extends('layouts.dashboard')

@section('title', 'Crear Congreso')

@section('content')
    <div style="max-width:760px; margin:0 auto;">
        <h2 class="page-title" style="margin:0 0 8px;">Nuevo Congreso</h2>
        <p class="page-sub" style="margin:0 0 22px;">Registra un nuevo congreso. El Id se genera automáticamente.</p>

        @if (session('status'))
            <div class="alert alert--ok" style="margin-bottom:16px;">
                {{ session('status') }}
            </div>
        @endif

        <div class="card" style="background:var(--surface); border:1px solid var(--border); border-radius:14px; padding:22px; box-shadow:var(--shadow);">
            <form method="POST" action="{{ route('configuracion.congresos.store') }}" enctype="multipart/form-data">
                @csrf

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                    <x-ui.form-group label="Nombre *" name="name" placeholder="Nombre del congreso" :required="true" />

                    <x-ui.form-group for="category_id" label="Categoría">
                        <select id="category_id" name="category_id" style="width:100%; padding:11px 12px; border:1px solid var(--border); border-radius:9px; font-size:15px; background:var(--surface); color:var(--text);">
                            <option value="" disabled selected>Seleccione una categoría</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" @selected(old('category_id') == $category->id)>{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </x-ui.form-group>

                    <x-ui.form-group for="image" label="Imagen">
                        <input id="image" name="image" type="file" accept="image/*" style="width:100%; padding:9px 12px; border:1px solid var(--border); border-radius:9px; font-size:15px; background:var(--surface); color:var(--text);" />
                    </x-ui.form-group>

                    <x-ui.form-group label="Fecha de inicio *" name="start_date" type="date" :required="true" />
                    <x-ui.form-group label="Fecha de finalización *" name="end_date" type="date" :required="true" />
                    <x-ui.form-group label="Hora de montaje *" name="assembly_time" type="time" :required="true" />
                    <x-ui.form-group label="Hora de desmontaje *" name="disassembly_time" type="time" :required="true" />
                </div>

                <div style="margin-top:16px;">
                    <x-ui.form-group for="download_access" label="Acceso de descarga">
                        <input type="hidden" name="download_access" value="0">
                        <label class="ui-switch">
                            <input type="checkbox" id="download_access" name="download_access" value="1" @checked(old('download_access') == '1' || old('download_access') === true)>
                            <span class="slider"></span>
                        </label>
                    </x-ui.form-group>

                    <x-ui.form-group label="Descarga en texto" name="download_text" placeholder="Descripción o enlace de descarga" />
                </div>

                <div style="display:flex; justify-content:flex-end; margin-top:18px;">
                    <button type="submit" class="btn">Guardar Congreso</button>
                </div>
            </form>
        </div>
    </div>

    <style>
        .ui-switch { position: relative; display: inline-block; width: 50px; height: 26px; }
        .ui-switch input { opacity: 0; width: 0; height: 0; }
        .ui-switch .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; border-radius: 26px; transition: .4s; }
        .ui-switch .slider:before { position: absolute; content: ""; height: 22px; width: 22px; left: 2px; bottom: 2px; background-color: white; border-radius: 50%; transition: .4s; box-shadow: 0 1px 3px rgba(0,0,0,0.3); }
        .ui-switch input:checked + .slider { background-color: var(--green, #22c55e); }
        .ui-switch input:checked + .slider:before { transform: translateX(24px); }
    </style>
@endsection
