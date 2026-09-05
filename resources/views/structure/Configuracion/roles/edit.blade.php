@extends('layouts.dashboard')

@section('title', 'Permisos de '.$role->label)
@section('page-title', $role->label)
@section('page-sub', 'Marca lo que puede hacer este rol')

@push('head')
    <style>
        .pm-datos { display:grid; grid-template-columns:repeat(auto-fit, minmax(240px, 1fr)); gap:0 18px; }

        .pm-resumen { position:sticky; top:12px; z-index:5; display:flex; align-items:center; gap:12px;
                      flex-wrap:wrap; padding:12px 16px; margin:18px 0; border-radius:12px;
                      background:var(--surface); border:1px solid var(--border);
                      box-shadow:0 6px 18px rgba(15,23,42,.06); }
        .pm-resumen b { font-size:15px; }
        .pm-resumen .sep { flex:1; }

        .pm-grupos { display:grid; grid-template-columns:repeat(auto-fit, minmax(300px, 1fr)); gap:14px; }

        .pm-grupo { border:1px solid var(--border); border-radius:12px; background:var(--surface); overflow:hidden; }
        .pm-grupo.activo { border-color:var(--primary); }

        .pm-grupo-cab { display:flex; align-items:flex-start; gap:10px; padding:13px 15px;
                        background:var(--surface-2); border-bottom:1px solid var(--border); }
        .pm-grupo-cab .txt { flex:1; min-width:0; }
        .pm-grupo-cab h3 { margin:0; font-size:14px; font-weight:600; }
        .pm-grupo-cab p { margin:2px 0 0; font-size:12px; color:var(--muted); }

        .pm-todos { display:inline-flex; align-items:center; gap:6px; font-size:11.5px; color:var(--muted);
                    cursor:pointer; white-space:nowrap; user-select:none; }

        .pm-lista { padding:6px 8px; }

        .pm-item { display:flex; align-items:flex-start; gap:10px; padding:8px 9px;
                   border-radius:8px; cursor:pointer; }
        .pm-item:hover { background:var(--surface-2); }
        .pm-item input { margin-top:2px; flex:none; width:16px; height:16px; accent-color:var(--primary); cursor:pointer; }
        .pm-item .txt { flex:1; min-width:0; font-size:13px; line-height:1.35; }
        .pm-item .clave { display:block; margin-top:2px; font-family:ui-monospace, Consolas, monospace;
                          font-size:10.5px; color:var(--muted); }

        .pm-aviso { padding:11px 13px; border-radius:9px; background:#fff7ed;
                    color:#b45309; font-size:12.5px; line-height:1.5; margin:6px 9px 10px; }

        .pm-usuarios { display:flex; flex-wrap:wrap; gap:6px; margin-top:8px; }
        .pm-usuario { padding:3px 9px; border-radius:999px; background:var(--surface-2);
                      border:1px solid var(--border); font-size:12px; }

        @media (max-width: 640px) {
            .pm-resumen { position:static; }
            .pm-grupos { grid-template-columns:1fr; }
        }
    </style>
@endpush

@section('content')
    @if (session('status'))
        <x-ui.alert type="ok">{{ session('status') }}</x-ui.alert>
    @endif

    <form method="POST" action="{{ route('configuracion.roles.update', $role) }}" id="formPermisos">
        @csrf
        @method('PUT')

        {{-- ===================== Identidad del rol ===================== --}}
        <x-ui.card>
            <x-ui.section-title style="margin:0 0 14px;">El rol</x-ui.section-title>

            <div class="pm-datos">
                <x-ui.form-group label="Nombre visible *" name="label" :value="old('label', $role->label)" :required="true" />

                <x-ui.form-group label="Clave interna" for="clave_fija">
                    <input id="clave_fija" type="text" value="{{ $role->name }}" disabled
                           style="font-family:ui-monospace, Consolas, monospace;">
                    <small class="campo-nota">No se cambia: hay código que la usa por nombre.</small>
                </x-ui.form-group>
            </div>

            <x-ui.form-group label="Para qué es" for="description">
                <textarea id="description" name="description" rows="2" maxlength="255"
                          placeholder="Ej. Recibe equipo y lo mueve por procesos">{{ old('description', $role->description) }}</textarea>
            </x-ui.form-group>

            <label class="pm-item" style="padding-left:0;">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $role->is_active))>
                <span class="txt">
                    Rol activo
                    <span class="clave">Si se desactiva, deja de otorgar permisos a quien lo tenga.</span>
                </span>
            </label>

            @if ($role->users->isNotEmpty())
                <p class="campo-nota" style="margin:14px 0 0;">
                    Lo tienen {{ $role->users->count() }} usuario(s). Lo que marques aquí les aplica a todos:
                </p>
                <div class="pm-usuarios">
                    @foreach ($role->users as $u)
                        <span class="pm-usuario">{{ $u->name }}</span>
                    @endforeach
                </div>
            @endif
        </x-ui.card>

        {{-- ===================== Contador pegado ===================== --}}
        @php
            $totalPermisos = collect($grupos)->sum(fn ($g) => count($g['permisos']));
        @endphp

        <div class="pm-resumen">
            <b><span id="pmCuenta">0</span> de {{ $totalPermisos }}</b>
            <span style="color:var(--muted); font-size:13px;">permisos marcados</span>
            <span class="sep"></span>
            <button type="button" class="btn btn--ghost" data-pm-todo="1">Marcar todo</button>
            <button type="button" class="btn btn--ghost" data-pm-todo="0">Quitar todo</button>
        </div>

        {{-- ===================== Los permisos ===================== --}}
        <div class="pm-grupos">
            @foreach ($grupos as $clave => $grupo)
                <div class="pm-grupo" data-grupo="{{ $clave }}">
                    <div class="pm-grupo-cab">
                        <div class="txt">
                            <h3>{{ $grupo['titulo'] }}</h3>
                            <p>{{ $grupo['descripcion'] }}</p>
                        </div>
                        <label class="pm-todos">
                            <input type="checkbox" data-grupo-todo style="width:15px; height:15px; accent-color:var(--primary);">
                            Todo
                        </label>
                    </div>

                    @if ($clave === 'precios')
                        <p class="pm-aviso">
                            Sin <b>precios.ver</b>, este rol trabaja el inventario pero no ve cuánto vale cada equipo.
                        </p>
                    @endif

                    @if ($clave === 'administracion')
                        <p class="pm-aviso">
                            <b>Crear roles</b> deja a este rol repartir permisos, incluidos los suyos.
                            Dáselo solo a quien administre el sistema.
                        </p>
                    @endif

                    <div class="pm-lista">
                        @foreach ($grupo['permisos'] as $llave => $texto)
                            <label class="pm-item">
                                <input type="checkbox" name="permisos[]" value="{{ $llave }}"
                                       @checked(in_array($llave, old('permisos', $concedidos), true))>
                                <span class="txt">
                                    {{ $texto }}
                                    <span class="clave">{{ $llave }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>

        <div class="page-foot">
            <a href="{{ route('configuracion.roles.index') }}" class="btn btn--ghost">Cancelar</a>
            <button type="submit" class="btn">Guardar permisos</button>
        </div>
    </form>

    @push('scripts')
    <script>
    (function () {
        const form = document.getElementById('formPermisos');
        if (!form) return;

        const cajas = () => Array.from(form.querySelectorAll('input[name="permisos[]"]'));
        const cuenta = document.getElementById('pmCuenta');

        // El "Todo" de cada grupo refleja el estado del grupo, no al reves:
        // si se marcan uno por uno hasta completarlo, tiene que verse marcado.
        function repintar() {
            form.querySelectorAll('.pm-grupo').forEach(function (grupo) {
                const suyas = Array.from(grupo.querySelectorAll('input[name="permisos[]"]'));
                const marcadas = suyas.filter(function (c) { return c.checked; }).length;
                const todo = grupo.querySelector('[data-grupo-todo]');

                todo.checked = marcadas === suyas.length && suyas.length > 0;
                todo.indeterminate = marcadas > 0 && marcadas < suyas.length;
                grupo.classList.toggle('activo', marcadas > 0);
            });

            cuenta.textContent = cajas().filter(function (c) { return c.checked; }).length;
        }

        form.addEventListener('change', function (e) {
            if (e.target.matches('[data-grupo-todo]')) {
                const grupo = e.target.closest('.pm-grupo');
                grupo.querySelectorAll('input[name="permisos[]"]').forEach(function (c) {
                    c.checked = e.target.checked;
                });
            }

            repintar();
        });

        form.addEventListener('click', function (e) {
            const boton = e.target.closest('[data-pm-todo]');
            if (!boton) return;

            const valor = boton.dataset.pmTodo === '1';
            cajas().forEach(function (c) { c.checked = valor; });
            repintar();
        });

        repintar();
    })();
    </script>
    @endpush
@endsection
