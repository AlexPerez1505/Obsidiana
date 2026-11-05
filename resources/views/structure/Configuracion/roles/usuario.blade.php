@extends('layouts.dashboard')

@section('title', 'Permisos de '.$usuario->name)
@section('page-title', $usuario->name)
@section('page-sub', 'Ajustes individuales dentro del rol '.$role->label)

@push('head')
    <style>
        .pu-grupos { display:grid; grid-template-columns:repeat(auto-fit, minmax(300px, 1fr)); gap:14px; }
        .pu-grupo { border:1px solid var(--border); border-radius:12px; background:var(--surface); overflow:hidden; }
        .pu-grupo-cab { padding:13px 15px; background:var(--surface-2); border-bottom:1px solid var(--border); }
        .pu-grupo-cab h3 { margin:0; font-size:14px; font-weight:600; }
        .pu-lista { padding:6px 8px; }
        .pu-item { display:flex; align-items:center; gap:10px; padding:7px 9px; border-radius:8px; font-size:13px; }
        .pu-item input { width:16px; height:16px; accent-color:var(--primary); cursor:pointer; flex:none; }
        .pu-item .txt { flex:1; min-width:0; line-height:1.35; }
        .pu-item .clave { display:block; font-family:ui-monospace,Consolas,monospace; font-size:10.5px; color:var(--muted); }
        .pu-tag { flex:none; font-size:11px; padding:2px 8px; border-radius:999px; background:var(--surface-2);
                  border:1px solid var(--border); color:var(--muted); }
        .pu-item.dar { background:rgba(74,222,128,.07); }
        .pu-item.no { background:rgba(248,113,113,.07); }
        @media (max-width:640px) { .pu-grupos { grid-template-columns:1fr; } }
    </style>
@endpush

@section('content')
    @if (session('status'))
        <x-ui.alert type="ok">{{ session('status') }}</x-ui.alert>
    @endif

    <x-ui.card style="margin-bottom:18px;">
        <p class="campo-nota" style="margin:0;">
            El rol <b>{{ $role->label }}</b> da lo marcado como «del rol». Aquí se ajusta solo
            <b>esta cuenta</b>: <i>Otorgar</i> le da un permiso que el rol no tiene y
            <i>Quitar</i> le bloquea uno que el rol sí tiene. No cambia a los demás del rol.
        </p>
    </x-ui.card>

    <form method="POST" action="{{ route('configuracion.roles.usuario.update', [$role, $usuario]) }}" data-live="off">
        @csrf
        @method('PUT')

        <div class="pu-grupos">
            @foreach ($grupos as $grupo)
                <div class="pu-grupo">
                    <div class="pu-grupo-cab"><h3>{{ $grupo['titulo'] }}</h3></div>
                    <div class="pu-lista">
                        @foreach ($grupo['permisos'] as $clave => $nombre)
                            @php
                                $esDelRol = in_array($clave, $delRol, true);
                                $dar = in_array($clave, $otorgados, true);
                                $no = in_array($clave, $quitados, true);
                            @endphp
                            @if ($esDelRol)
                                <label class="pu-item {{ $no ? 'no' : '' }}">
                                    <input type="checkbox" name="quitar[]" value="{{ $clave }}" @checked($no)>
                                    <span class="txt">{{ $nombre }}<span class="clave">{{ $clave }}</span></span>
                                    <span class="pu-tag">del rol · quitarle</span>
                                </label>
                            @else
                                <label class="pu-item {{ $dar ? 'dar' : '' }}">
                                    <input type="checkbox" name="otorgar[]" value="{{ $clave }}" @checked($dar)>
                                    <span class="txt">{{ $nombre }}<span class="clave">{{ $clave }}</span></span>
                                    <span class="pu-tag">otorgarle</span>
                                </label>
                            @endif
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>

        <div style="display:flex; gap:10px; margin-top:16px;">
            <button type="submit" class="btn">Guardar ajustes de {{ $usuario->name }}</button>
            <a href="{{ route('configuracion.roles.index') }}" class="btn btn--ghost">Volver a roles</a>
        </div>
    </form>
@endsection
