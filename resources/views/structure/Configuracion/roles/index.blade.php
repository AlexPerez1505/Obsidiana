@extends('layouts.dashboard')

@section('title', 'Roles y permisos')
@section('page-title', 'Roles y permisos')
@section('page-sub', 'Qué puede hacer cada quien dentro del sistema')

@push('head')
    <style>
        .rl-lista { display:grid; grid-template-columns:repeat(auto-fit, minmax(320px, 1fr)); gap:16px; }

        .rl-cab { display:flex; align-items:flex-start; gap:12px; }
        .rl-cab h3 { flex:1; margin:0; font-size:16px; font-weight:600; }
        .rl-clave { font-family:ui-monospace, Consolas, monospace; font-size:11.5px; color:var(--muted); }

        .rl-barra { height:6px; border-radius:3px; background:var(--surface-2); overflow:hidden; margin-top:14px; }
        .rl-barra > span { display:block; height:100%; background:var(--primary); }
        .rl-barra.todo > span { background:var(--green); }

        .rl-cuenta { display:flex; justify-content:space-between; gap:10px; margin-top:7px;
                     color:var(--muted); font-size:12.5px; }

        .rl-modulos { display:flex; flex-wrap:wrap; gap:6px; margin-top:14px; }
        .rl-mod { padding:3px 9px; border-radius:999px; background:var(--surface-2);
                  border:1px solid var(--border); font-size:11.5px; }
        .rl-mod.parcial { color:var(--muted); }
        .rl-mod.lleno { background:var(--primary-soft); border-color:transparent; color:var(--primary); font-weight:600; }

        .rl-pie { display:flex; align-items:center; gap:10px; margin-top:16px;
                  padding-top:13px; border-top:1px solid var(--border); }
        .rl-pie .usuarios { flex:1; color:var(--muted); font-size:12.5px; }

        .rl-admin { padding:11px 13px; border-radius:9px; background:var(--green-soft);
                    color:var(--green); font-size:13px; line-height:1.5; margin-top:14px; }
    </style>
@endpush

@section('content')
    @if (session('status'))
        <x-ui.alert type="ok">{{ session('status') }}</x-ui.alert>
    @endif

    @error('rol')<x-ui.alert type="err">{{ $message }}</x-ui.alert>@enderror

    <div class="content-actions">
        <button type="button" class="btn" data-abrir-rol>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="15" height="15"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Nuevo rol
        </button>
    </div>

    <div class="rl-lista">
        @foreach ($roles as $role)
            @php
                $esAdmin = $role->name === 'admin';
                $tiene = $role->permissions->pluck('name');
                $n = $esAdmin ? $totalPermisos : $tiene->count();
                $pct = $totalPermisos ? round($n / $totalPermisos * 100) : 0;
            @endphp

            <x-ui.card>
                <div class="rl-cab">
                    <div style="flex:1; min-width:0;">
                        <h3>{{ $role->label }}</h3>
                        <span class="rl-clave">{{ $role->name }}</span>
                        @unless ($role->is_active)
                            <span class="badge badge--danger" style="margin-left:6px;">Inactivo</span>
                        @endunless
                    </div>
                </div>

                @if ($role->description)
                    <p class="campo-nota" style="margin:8px 0 0;">{{ $role->description }}</p>
                @endif

                @if ($esAdmin)
                    <p class="rl-admin">
                        <b>Puede todo, siempre.</b> No se le configuran permisos ni se puede eliminar:
                        es la cuenta que garantiza que alguien siempre pueda entrar a arreglar las demás.
                    </p>
                @else
                    <div class="rl-barra {{ $n === $totalPermisos ? 'todo' : '' }}">
                        <span style="width:{{ $pct }}%"></span>
                    </div>
                    <div class="rl-cuenta">
                        <span><b>{{ $n }}</b> de {{ $totalPermisos }} permisos</span>
                        <span>{{ $pct }}%</span>
                    </div>

                    {{-- De un vistazo: a qué módulos entra este rol. --}}
                    <div class="rl-modulos">
                        @foreach ($grupos as $clave => $grupo)
                            @php
                                $delGrupo = collect(array_keys($grupo['permisos']));
                                $cuantos = $delGrupo->intersect($tiene)->count();
                            @endphp

                            @if ($cuantos)
                                <span class="rl-mod {{ $cuantos === $delGrupo->count() ? 'lleno' : 'parcial' }}">
                                    {{ $grupo['titulo'] }}
                                    @if ($cuantos < $delGrupo->count()) ({{ $cuantos }}/{{ $delGrupo->count() }}) @endif
                                </span>
                            @endif
                        @endforeach

                        @if ($tiene->isEmpty())
                            <span class="rl-mod parcial">Sin permisos todavía</span>
                        @endif
                    </div>
                @endif

                <div class="rl-pie">
                    <span class="usuarios">
                        {{ $role->users->count() }} usuario(s)
                        @if ($role->users->isNotEmpty())
                            · {{ $role->users->take(2)->pluck('name')->implode(', ') }}{{ $role->users->count() > 2 ? '…' : '' }}
                        @endif
                    </span>

                    @unless ($esAdmin)
                        <a href="{{ route('configuracion.roles.edit', $role) }}" class="btn btn--ghost">Configurar</a>

                        <form method="POST" action="{{ route('configuracion.roles.destroy', $role) }}"
                              onsubmit="return confirm('¿Eliminar el rol {{ $role->label }}?');" style="margin:0;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn-icono" title="Eliminar rol" aria-label="Eliminar rol">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/></svg>
                            </button>
                        </form>
                    @endunless
                </div>
            </x-ui.card>
        @endforeach
    </div>

    <x-ui.card style="margin-top:18px;">
        <x-ui.section-title style="margin:0 0 6px;">Cómo se asigna</x-ui.section-title>
        <p class="campo-nota" style="margin:0;">
            Aquí defines qué puede hacer cada rol. A quién le toca cada rol se elige en
            <a href="{{ route('admin.users.index') }}" class="link">Panel de usuarios</a>.
            Un usuario puede tener más de un rol: se queda con la suma de sus permisos.
        </p>
    </x-ui.card>

    {{-- ===================== Nuevo rol ===================== --}}
    <dialog id="modalRol" class="rl-modal">
        <form method="POST" action="{{ route('configuracion.roles.store') }}">
            @csrf

            <x-ui.section-title style="margin:0 0 4px;">Nuevo rol</x-ui.section-title>
            <p class="campo-nota" style="margin:0 0 14px;">
                Después eliges qué puede hacer.
            </p>

            <x-ui.form-group label="Nombre visible *" name="label" placeholder="Ej. Jefe de almacén" :required="true" />

            <x-ui.form-group label="Clave *" name="name" for="name">
                <input id="name" type="text" name="name" value="{{ old('name') }}" placeholder="jefe_almacen"
                       pattern="[a-z][a-z0-9_]*" required
                       style="font-family:ui-monospace, Consolas, monospace;">
                <small class="campo-nota">Minúsculas, sin espacios ni acentos. No se puede cambiar después.</small>
            </x-ui.form-group>

            <x-ui.form-group label="Para qué es" for="description">
                <textarea id="description" name="description" rows="2" maxlength="255"
                          placeholder="Ej. Recibe equipo y lo mueve por procesos">{{ old('description') }}</textarea>
            </x-ui.form-group>

            <div class="rl-modal-pie">
                <button type="button" class="btn btn--ghost" data-cerrar-rol>Cancelar</button>
                <button type="submit" class="btn">Crear rol</button>
            </div>
        </form>
    </dialog>

    <style>
        .rl-modal { width:min(440px, calc(100vw - 32px)); padding:24px; border:1px solid var(--border);
                    border-radius:16px; background:var(--surface); color:var(--text); }
        .rl-modal::backdrop { background:rgba(15,23,42,.45); }
        .rl-modal-pie { display:flex; justify-content:flex-end; gap:10px; margin-top:18px; }
    </style>

    @push('scripts')
        <script>
        (function () {
            // En captura: si esto se envuelve en DOMContentLoaded puede
            // llegar tarde, y así tampoco lo frena ningún stopPropagation.
            document.addEventListener('click', function (e) {
                const modal = document.getElementById('modalRol');
                if (!modal) return;

                if (e.target.closest('[data-abrir-rol]')) { modal.showModal(); return; }
                if (e.target === modal || e.target.closest('[data-cerrar-rol]')) modal.close();
            }, true);

            @if ($errors->has('name') || $errors->has('label'))
                document.getElementById('modalRol')?.showModal();
            @endif
        })();
        </script>
    @endpush
@endsection
