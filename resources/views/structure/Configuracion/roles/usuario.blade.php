@extends('layouts.dashboard')

@section('title', 'Permisos de '.$usuario->name)
@section('page-title', $usuario->name)
@section('page-sub', 'Ajustes individuales dentro del rol '.$role->label)

@push('head')
    <style>
        .pu-grupos { display:grid; grid-template-columns:repeat(auto-fit, minmax(360px, 1fr)); gap:14px; }
        .pu-grupo { border:1px solid var(--border); border-radius:12px; background:var(--surface); overflow:hidden; }
        .pu-grupo-cab { display:flex; align-items:center; justify-content:space-between; gap:12px; padding:13px 15px; background:var(--surface-2); border-bottom:1px solid var(--border); }
        .pu-grupo-cab h3 { margin:0; font-size:14px; font-weight:600; }
        .pu-grupo-cab p { margin:3px 0 0; color:var(--muted); font-size:11px; }
        .pu-todo { display:flex; align-items:center; gap:6px; flex:none; font-size:12px; color:var(--muted); cursor:pointer; }
        .pu-todo input { width:16px; height:16px; accent-color:var(--primary); cursor:pointer; }
        .pu-lista { padding:6px 8px; }
        .pu-item { display:flex; align-items:center; gap:10px; padding:7px 9px; border-radius:8px; font-size:13px; }
        .pu-item input { width:16px; height:16px; accent-color:var(--primary); cursor:pointer; flex:none; }
        .pu-item .txt { flex:1; min-width:0; line-height:1.35; }
        .pu-item .clave { display:block; font-family:ui-monospace,Consolas,monospace; font-size:10.5px; color:var(--muted); }
        .pu-tag { flex:none; font-size:11px; padding:2px 8px; border-radius:999px; background:var(--surface-2);
                  border:1px solid var(--border); color:var(--muted); }
        .pu-item.activo { background:rgba(74,222,128,.07); }
        .pu-leyenda { display:grid; grid-template-columns:repeat(3, minmax(0, 1fr)); gap:10px; margin-top:14px; }
        .pu-leyenda div { padding:10px 12px; border:1px solid var(--border); border-radius:9px; font-size:12px; color:var(--muted); }
        .pu-leyenda b { display:block; margin-bottom:3px; color:var(--text); font-size:12px; }
        @media (max-width:800px) { .pu-leyenda { grid-template-columns:1fr; } }
        @media (max-width:640px) { .pu-grupos { grid-template-columns:1fr; } }
    </style>
@endpush

@section('content')
    @if (session('status'))
        <x-ui.alert type="ok">{{ session('status') }}</x-ui.alert>
    @endif

    <x-ui.card style="margin-bottom:18px;">
        <p class="campo-nota" style="margin:0;">
            Selecciona los permisos efectivos de <b>esta cuenta</b>. Los permisos que vienen del rol
            se conservan automáticamente; al desmarcarlos se bloquean solo para esta cuenta.
            Los permisos adicionales marcados se otorgan solo a esta cuenta.
        </p>
        <div class="pu-leyenda">
            <div><b>Marcado</b>La cuenta tiene acceso efectivo a ese permiso.</div>
            <div><b>Desmarcado</b>La cuenta no tiene acceso, aunque el rol lo tenga.</div>
            <div><b>Todo</b>Activa o desactiva todos los permisos del módulo.</div>
        </div>
    </x-ui.card>

    <form method="POST" action="{{ route('configuracion.roles.usuario.update', [$role, $usuario]) }}" data-live="off">
        @csrf
        @method('PUT')

        <div class="pu-grupos">
            @foreach ($grupos as $grupo)
                <div class="pu-grupo" data-permission-group>
                    <div class="pu-grupo-cab">
                        <div>
                            <h3>{{ $grupo['titulo'] }}</h3>
                            <p>{{ $grupo['descripcion'] }}</p>
                        </div>
                        <label class="pu-todo"><input type="checkbox" data-group-toggle> Todo</label>
                    </div>
                    <div class="pu-lista">
                        @foreach ($grupo['permisos'] as $clave => $nombre)
                            @php
                                $esDelRol = in_array($clave, $delRol, true);
                                $estaBloqueado = in_array($clave, $quitados, true);
                                $activo = in_array($clave, $activos, true);
                            @endphp
                            <label class="pu-item {{ $activo ? 'activo' : '' }}" data-inherited="{{ $esDelRol ? '1' : '0' }}">
                                <input type="checkbox" name="permisos[]" value="{{ $clave }}" data-permission @checked($activo)>
                                <span class="txt">{{ $nombre }}<span class="clave">{{ $clave }}</span></span>
                                <span class="pu-tag">{{ $activo ? ($esDelRol && ! $estaBloqueado ? 'activo · del rol' : 'activo · individual') : 'sin acceso' }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>

        <div style="display:flex; gap:10px; margin-top:16px;">
            <button type="submit" class="btn">Guardar ajustes de {{ $usuario->name }}</button>
            <button type="submit" class="btn btn--ghost" form="restablecer-permisos" onclick="return confirm('¿Restaurar todos los permisos heredados del rol y eliminar los ajustes individuales de esta cuenta?')">Restaurar herencia del rol</button>
            <a href="{{ route('configuracion.roles.index') }}" class="btn btn--ghost">Volver a roles</a>
        </div>
    </form>

    <form id="restablecer-permisos" method="POST" action="{{ route('configuracion.roles.usuario.reset', [$role, $usuario]) }}" data-live="off">
        @csrf
        @method('DELETE')
    </form>
@endsection

@push('scripts')
    <script>
        document.querySelectorAll('[data-permission-group]').forEach(function (grupo) {
            const toggle = grupo.querySelector('[data-group-toggle]');
            const permisos = Array.from(grupo.querySelectorAll('[data-permission]'));
            const actualizarGrupo = function () {
                const activos = permisos.filter(function (permiso) { return permiso.checked; }).length;
                toggle.checked = activos === permisos.length;
                toggle.indeterminate = activos > 0 && activos < permisos.length;
            };
            const actualizarPermiso = function (permiso) {
                const item = permiso.closest('.pu-item');
                const etiqueta = item.querySelector('.pu-tag');
                item.classList.toggle('activo', permiso.checked);
                etiqueta.textContent = permiso.checked
                    ? (item.dataset.inherited === '1' ? 'activo · del rol' : 'activo · individual')
                    : 'sin acceso';
            };

            toggle.addEventListener('change', function () {
                permisos.forEach(function (permiso) {
                    permiso.checked = toggle.checked;
                    actualizarPermiso(permiso);
                });
            });
            permisos.forEach(function (permiso) {
                permiso.addEventListener('change', function () {
                    actualizarPermiso(permiso);
                    actualizarGrupo();
                });
            });
            actualizarGrupo();
        });
    </script>
@endpush
