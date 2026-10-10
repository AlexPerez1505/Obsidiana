@extends('layouts.dashboard')

@section('title', 'API e integraciones')
@section('page-title', 'API e integraciones')
@section('page-sub', 'Llaves para otros sistemas y sesiones abiertas desde apps')

@push('head')
    <style>
        /* minmax(0,1fr): sin esto una tabla ancha estira la rejilla y la página se desborda en el celular. */
        .ap-grid { display:grid; grid-template-columns:minmax(0, 1fr); gap:16px; }
        .ap-mono { font-family:ui-monospace, Consolas, monospace; font-size:12.5px; }
        .ap-copiar { display:flex; gap:8px; margin-top:12px; }
        .ap-copiar input { flex:1; min-width:0; font-family:ui-monospace, Consolas, monospace; font-size:12.5px; }
        .ap-nueva { border-color:var(--primary); }

        .ap-uso { display:grid; grid-template-columns:repeat(auto-fit, minmax(260px, 1fr)); gap:14px; margin-top:12px; }
        .ap-uso .dato { padding:12px 14px; border:1px solid var(--border); border-radius:10px; background:var(--surface-2); }
        .ap-uso .dato span { display:block; color:var(--muted); font-size:12px; margin-bottom:4px; }
        .ap-uso pre { margin:0; white-space:pre-wrap; word-break:break-all; }

        .ap-tabla { overflow-x:auto; margin:12px -4px 0; }
        .ap-tabla td { vertical-align:top; }
        .ap-tabla .s { color:var(--muted); font-size:12.5px; margin-top:2px; }
        .ap-vacio { color:var(--muted); font-size:13.5px; margin:12px 0 0; }

        .ap-modal { width:min(620px, calc(100vw - 32px)); max-height:calc(100vh - 48px); padding:24px;
                    border:1px solid var(--border); border-radius:16px; background:var(--surface); color:var(--text); }
        .ap-modal::backdrop { background:rgba(15,23,42,.45); }
        .ap-modal-pie { display:flex; justify-content:flex-end; gap:10px; margin-top:18px; }
        .ap-acceso { display:flex; gap:10px; flex-wrap:wrap; }
        .ap-acceso label { flex:1; min-width:200px; display:flex; gap:10px; align-items:flex-start; padding:11px 12px;
                           border:1px solid var(--border); border-radius:10px; cursor:pointer; font-size:13.5px; }
        .ap-acceso label:has(input:checked) { border-color:var(--primary); background:var(--primary-soft); }
        .ap-acceso input { margin-top:2px; accent-color:var(--primary); }
        .ap-acceso small { display:block; color:var(--muted); font-size:12px; margin-top:2px; }

        .ap-permisos { display:grid; grid-template-columns:repeat(auto-fit, minmax(240px, 1fr)); gap:10px; margin-top:10px; }
        .ap-grupo { border:1px solid var(--border); border-radius:10px; padding:8px 10px; }
        .ap-grupo h4 { margin:2px 0 6px; font-size:13px; font-weight:600; }
        .ap-grupo label { display:flex; gap:8px; align-items:flex-start; padding:4px 0; font-size:12.5px; line-height:1.35; cursor:pointer; }
        .ap-grupo input { margin-top:2px; flex:none; accent-color:var(--primary); }
    </style>
@endpush

@section('content')
    @if (session('status'))
        <x-ui.alert type="ok">{{ session('status') }}</x-ui.alert>
    @endif

    @if ($errors->any())
        <x-ui.alert type="err">{{ $errors->first() }}</x-ui.alert>
    @endif

    <div class="ap-grid">
        @if ($nueva = session('llave_nueva'))
            <x-ui.card class="ap-nueva">
                <x-ui.section-title style="margin:0 0 4px;">Llave «{{ $nueva['nombre'] }}»</x-ui.section-title>
                <p class="campo-nota" style="margin:0;">
                    Cópiala y guárdala en el otro sistema. Por seguridad no se vuelve a mostrar:
                    si se pierde, revócala y crea otra.
                </p>
                <div class="ap-copiar">
                    <input id="llaveNueva" type="text" readonly value="{{ $nueva['texto'] }}" aria-label="Llave nueva">
                    <button type="button" class="btn" data-copiar="#llaveNueva">Copiar</button>
                </div>
            </x-ui.card>
        @endif

        <div class="content-actions" style="margin:0;">
            <a href="{{ url('/docs/api') }}" class="btn btn--ghost" target="_blank" rel="noopener">
                <x-ui.icon name="file" size="15" /> Documentación
            </a>
            <button type="button" class="btn" data-abrir-llave>
                <x-ui.icon name="plus" size="15" /> Nueva llave
            </button>
        </div>

        <x-ui.card>
            <x-ui.section-title style="margin:0 0 4px;">Cómo se conecta un sistema</x-ui.section-title>
            <p class="campo-nota" style="margin:0;">
                Cada petición lleva la llave en la cabecera <span class="ap-mono">Authorization</span>.
                La llave puede lo mismo que el usuario a cuyo nombre está, recortado a los permisos que se le marcaron.
                Las apps no usan llaves: sus usuarios entran con correo y contraseña en
                <span class="ap-mono">POST /auth/login</span>.
            </p>
            <div class="ap-uso">
                <div class="dato">
                    <span>Dirección base</span>
                    <pre class="ap-mono">{{ $urlBase }}</pre>
                </div>
                <div class="dato">
                    <span>Ejemplo</span>
                    <pre class="ap-mono">curl {{ $urlBase }}/auth/yo \
  -H "Authorization: Bearer &lt;llave&gt;" \
  -H "Accept: application/json"</pre>
                </div>
            </div>
        </x-ui.card>

        <x-ui.card>
            <x-ui.section-title style="margin:0 0 4px;">Llaves de integración</x-ui.section-title>
            <p class="campo-nota" style="margin:0;">Para otros sistemas: Jureto, la página web, n8n, un servidor propio.</p>

            @if ($llaves->isEmpty())
                <p class="ap-vacio">Todavía no hay llaves. Crea una con «Nueva llave».</p>
            @else
                <div class="ap-tabla">
                    <table>
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>A nombre de</th>
                                <th>Acceso</th>
                                <th>Último uso</th>
                                <th>Vence</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($llaves as $llave)
                                @php $permisos = $llave->abilities ?? []; @endphp
                                <tr>
                                    <td>
                                        <b>{{ $llave->name }}</b>
                                        <div class="s">
                                            Creada {{ $llave->created_at->format('d/m/Y') }}
                                            @if ($llave->creadoPor && $llave->creadoPor->id !== $llave->tokenable_id)
                                                por {{ $llave->creadoPor->name }}
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        {{ $llave->tokenable?->name ?? 'Usuario eliminado' }}
                                        <div class="s">{{ $llave->tokenable?->email }}</div>
                                    </td>
                                    <td>
                                        @if ($llave->accesoTotal())
                                            <x-ui.badge variant="info">Total</x-ui.badge>
                                        @else
                                            <span title="{{ collect($permisos)->map(fn ($p) => \App\Support\CatalogoPermisos::etiqueta($p))->implode("\n") }}">
                                                <x-ui.badge>{{ count($permisos) }} permiso(s)</x-ui.badge>
                                            </span>
                                        @endif
                                        @if ($llave->ip_permitidas)
                                            <div class="s">Solo desde {{ implode(', ', $llave->ip_permitidas) }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($llave->last_used_at)
                                            {{ $llave->last_used_at->diffForHumans() }}
                                            <div class="s">{{ $llave->last_used_ip }}</div>
                                        @else
                                            <span class="s">Sin usar</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($llave->expires_at)
                                            @if ($llave->vencido())
                                                <x-ui.badge variant="danger">Vencida</x-ui.badge>
                                            @else
                                                {{ $llave->expires_at->format('d/m/Y') }}
                                            @endif
                                        @else
                                            <span class="s">No vence</span>
                                        @endif
                                    </td>
                                    <td style="text-align:right;">
                                        <form method="POST" action="{{ route('configuracion.api.destroy', $llave) }}" style="margin:0;"
                                              data-confirm="El sistema que usa «{{ $llave->name }}» dejará de poder entrar."
                                              data-confirm-title="¿Revocar la llave?" data-confirm-ok="Revocar" data-confirm-danger>
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn-icono" title="Revocar llave" aria-label="Revocar llave">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/></svg>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-ui.card>

        <x-ui.card>
            <x-ui.section-title style="margin:0 0 4px;">Sesiones de apps</x-ui.section-title>
            <p class="campo-nota" style="margin:0;">
                Dispositivos donde alguien inició sesión por la API. Se cierran solas tras
                {{ config('api.dias_token_app') }} días sin usarse.
            </p>

            @if ($sesiones->isEmpty())
                <p class="ap-vacio">Nadie ha iniciado sesión desde una app.</p>
            @else
                <div class="ap-tabla">
                    <table>
                        <thead>
                            <tr>
                                <th>Usuario</th>
                                <th>Dispositivo</th>
                                <th>Último uso</th>
                                <th>Vence</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($sesiones as $sesion)
                                <tr>
                                    <td>
                                        {{ $sesion->tokenable?->name ?? 'Usuario eliminado' }}
                                        <div class="s">{{ $sesion->tokenable?->email }}</div>
                                    </td>
                                    <td>
                                        {{ $sesion->name }}
                                        <div class="s">Desde {{ $sesion->created_at->format('d/m/Y') }}</div>
                                    </td>
                                    <td>
                                        {{ $sesion->last_used_at?->diffForHumans() ?? 'Sin usar' }}
                                        <div class="s">{{ $sesion->last_used_ip }}</div>
                                    </td>
                                    <td>{{ $sesion->expires_at?->format('d/m/Y') ?? '—' }}</td>
                                    <td style="text-align:right;">
                                        <form method="POST" action="{{ route('configuracion.api.destroy', $sesion) }}" style="margin:0;"
                                              data-confirm="Se cerrará la sesión de «{{ $sesion->name }}»; tendrá que volver a entrar."
                                              data-confirm-title="¿Cerrar esta sesión?" data-confirm-ok="Cerrar sesión">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn-icono" title="Cerrar sesión" aria-label="Cerrar sesión">
                                                <x-ui.icon name="x" />
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-ui.card>
    </div>

    {{-- ===================== Nueva llave ===================== --}}
    <dialog id="modalLlave" class="ap-modal">
        <form method="POST" action="{{ route('configuracion.api.store') }}">
            @csrf

            <x-ui.section-title style="margin:0 0 4px;">Nueva llave de integración</x-ui.section-title>
            <p class="campo-nota" style="margin:0 0 14px;">
                Al guardarla verás la llave completa una sola vez.
            </p>

            <x-ui.form-group label="Nombre *" name="name" placeholder="Ej. Jureto, página web, n8n" :required="true" />

            @if ($usuarios->count() > 1)
                <x-ui.form-group label="A nombre de *" for="user_id">
                    <select id="user_id" name="user_id">
                        @foreach ($usuarios as $u)
                            <option value="{{ $u->id }}" @selected((int) old('user_id', auth()->id()) === $u->id)>{{ $u->name }} · {{ $u->email }}</option>
                        @endforeach
                    </select>
                    <small class="campo-nota">La llave nunca puede más que este usuario. Si lo desactivan, la llave deja de servir.</small>
                </x-ui.form-group>
            @endif

            <x-ui.form-group label="Acceso" for="acceso">
                <div class="ap-acceso">
                    <label>
                        <input type="radio" name="acceso" value="limitado" @checked(old('acceso', 'limitado') === 'limitado')>
                        <span>Solo ciertos permisos<small>Lo recomendado: lo mínimo que necesita el otro sistema.</small></span>
                    </label>
                    <label>
                        <input type="radio" name="acceso" value="total" @checked(old('acceso') === 'total')>
                        <span>Acceso total<small>Todo lo que puede el usuario, incluido lo de administrador.</small></span>
                    </label>
                </div>
            </x-ui.form-group>

            <div id="apPermisos" class="ap-permisos">
                @foreach ($grupos as $grupo)
                    <div class="ap-grupo">
                        <h4>{{ $grupo['titulo'] }}</h4>
                        @foreach ($grupo['permisos'] as $llaveP => $texto)
                            <label>
                                <input type="checkbox" name="permisos[]" value="{{ $llaveP }}" @checked(in_array($llaveP, old('permisos', []), true))>
                                <span>{{ $texto }}</span>
                            </label>
                        @endforeach
                    </div>
                @endforeach
            </div>

            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:0 14px; margin-top:14px;">
                <x-ui.form-group label="Vence" for="expires_at">
                    <input id="expires_at" type="date" name="expires_at" value="{{ old('expires_at') }}" min="{{ now()->addDay()->toDateString() }}">
                    <small class="campo-nota">Vacío = no vence.</small>
                </x-ui.form-group>

                <x-ui.form-group label="Solo desde estas IP" for="ips">
                    <input id="ips" type="text" name="ips" value="{{ old('ips') }}" placeholder="Ej. 200.1.2.3, 10.0.0.5">
                    <small class="campo-nota">Vacío = desde cualquier IP.</small>
                </x-ui.form-group>
            </div>

            <div class="ap-modal-pie">
                <button type="button" class="btn btn--ghost" data-cerrar-llave>Cancelar</button>
                <button type="submit" class="btn">Crear llave</button>
            </div>
        </form>
    </dialog>

    @push('scripts')
        <script>
        (function () {
            const modal = document.getElementById('modalLlave');
            const permisos = document.getElementById('apPermisos');

            // Los permisos solo aplican al acceso limitado.
            function alternarPermisos() {
                const limitado = modal.querySelector('input[name="acceso"]:checked')?.value !== 'total';
                permisos.style.display = limitado ? '' : 'none';
            }

            modal.addEventListener('change', function (e) {
                if (e.target.name === 'acceso') alternarPermisos();
            });
            alternarPermisos();

            document.addEventListener('click', function (e) {
                if (e.target.closest('[data-abrir-llave]')) { modal.showModal(); return; }
                if (e.target === modal || e.target.closest('[data-cerrar-llave]')) { modal.close(); return; }

                const boton = e.target.closest('[data-copiar]');
                if (!boton) return;

                const campo = document.querySelector(boton.getAttribute('data-copiar'));
                const avisar = () => window.showToast && window.showToast('Llave copiada');

                // Fuera de https el portapapeles moderno no existe; se cae al método viejo.
                if (navigator.clipboard && window.isSecureContext) {
                    navigator.clipboard.writeText(campo.value).then(avisar);
                } else {
                    campo.select();
                    document.execCommand('copy');
                    avisar();
                }
            }, true);

            @if ($errors->hasAny(['name', 'user_id', 'acceso', 'permisos', 'expires_at', 'ips']))
                modal.showModal();
            @endif
        })();
        </script>
    @endpush
@endsection
