@extends('layouts.dashboard')
@section('title', 'Editar perfil')
@section('page-title', 'Editar perfil')
@section('page-sub', 'Actualiza tu información personal')

@section('content')
    <div class="grid" style="grid-template-columns:repeat(auto-fit,minmax(320px,1fr)); align-items:start;">
        {{-- Datos personales --}}
        <x-ui.card>
            <x-ui.section-title>Datos personales</x-ui.section-title>
            <form method="POST" action="{{ route('profile.update') }}">
                @csrf
                @method('PATCH')

                <x-ui.form-group
                    label="Nombre"
                    name="name"
                    type="text"
                    :value="old('name', $user->name)"
                    :required="true"
                />

                <x-ui.form-group
                    label="Correo electrónico"
                    name="email"
                    type="email"
                    :value="old('email', $user->email)"
                    :required="true"
                />

                <p class="muted" style="margin-top:6px; font-size:13px;">
                    Si cambias el correo, tendrás que verificarlo de nuevo con un código.
                </p>

                <x-ui.button style="margin-top:16px;">Guardar cambios</x-ui.button>
            </form>
        </x-ui.card>

        {{-- Cambiar contraseña --}}
        <x-ui.card>
            <x-ui.section-title>Cambiar contraseña</x-ui.section-title>
            <form method="POST" action="{{ route('profile.password') }}">
                @csrf
                @method('PUT')

                <x-ui.form-group
                    label="Contraseña actual"
                    name="current_password"
                    type="password"
                    :required="true"
                />

                <x-ui.form-group
                    label="Nueva contraseña"
                    name="password"
                    type="password"
                    :required="true"
                />

                <x-ui.form-group
                    label="Confirmar nueva contraseña"
                    name="password_confirmation"
                    type="password"
                    :required="true"
                />

                <x-ui.button style="margin-top:16px;">Actualizar contraseña</x-ui.button>
            </form>
        </x-ui.card>

        {{--
            Firma registrada: se traza una sola vez aquí y de ahí la cargan
            solas las pantallas que piden firma (entradas de inventario,
            salidas de almacén), en vez de volver a dibujarla con el mouse.
        --}}
        <x-ui.card>
            <x-ui.section-title>Mi firma</x-ui.section-title>
            <p class="muted" style="margin:0 0 14px; font-size:13.5px;">
                Trázala una vez y se va a cargar sola cada que tengas que firmar una entrada
                o una salida de almacén. Siempre puedes limpiarla y firmar a mano en el momento.
            </p>

            @if ($user->tieneFirma())
                <div class="firma-actual">
                    <div>
                        <span class="k">Firma registrada</span>
                        <img src="{{ $user->firmaUrl() }}" alt="Tu firma registrada">
                    </div>
                    <form method="POST" action="{{ route('profile.firma.destroy') }}"
                          data-confirm="¿Quitar tu firma registrada? Tendrás que firmar a mano cada vez.">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn--ghost" style="color:var(--danger);">Quitar</button>
                    </form>
                </div>
            @endif

            <form method="POST" action="{{ route('profile.firma.store') }}">
                @csrf

                <p class="muted" style="margin:0 0 8px; font-size:12.5px;">
                    {{ $user->tieneFirma() ? 'Traza una nueva para reemplazarla:' : 'Firma con el mouse o el dedo:' }}
                </p>

                <canvas id="firma-perfil" class="firma-lienzo"></canvas>
                <input type="hidden" name="firma" id="firma-perfil-input">

                @error('firma')<p class="err">{{ $message }}</p>@enderror

                <div style="display:flex; align-items:center; gap:12px; margin-top:12px;">
                    <x-ui.button>Guardar mi firma</x-ui.button>
                    <a href="#" id="firma-perfil-limpiar" class="link" style="font-size:13px;">Limpiar</a>
                </div>
            </form>
        </x-ui.card>
    </div>

    @push('head')
        <style>
            /* Se traza sobre blanco aunque el sistema esté en oscuro: la firma
               es tal cual se va a imprimir en las hojas. */
            .firma-lienzo { width:100%; max-width:420px; height:150px; background:#fff;
                            border:1px dashed var(--border); border-radius:10px;
                            cursor:crosshair; touch-action:none; display:block; }
            .firma-actual { display:flex; align-items:flex-end; gap:14px; flex-wrap:wrap;
                            margin-bottom:18px; padding-bottom:18px; border-bottom:1px solid var(--border); }
            .firma-actual .k { display:block; color:var(--muted); font-size:12px; margin-bottom:6px; }
            .firma-actual img { max-width:260px; border:1px solid var(--border); border-radius:10px; background:#fff; }
        </style>
    @endpush

    @push('scripts')
        <script>
        document.addEventListener('DOMContentLoaded', function () {
            const lienzo = document.getElementById('firma-perfil');
            const campo = document.getElementById('firma-perfil-input');
            const limpiar = document.getElementById('firma-perfil-limpiar');

            if (! lienzo || ! campo) return;

            const ctx = lienzo.getContext('2d');

            // El lienzo mide en píxeles reales, no en CSS: sin esto la firma
            // sale estirada y desalineada del cursor.
            function ajustar() {
                const previo = campo.value;
                const rect = lienzo.getBoundingClientRect();

                if (! rect.width) return;

                lienzo.width = rect.width;
                lienzo.height = rect.height;
                ctx.lineWidth = 2;
                ctx.lineCap = 'round';
                ctx.strokeStyle = '#1a1a1a';

                if (previo) {
                    const img = new Image();
                    img.onload = () => ctx.drawImage(img, 0, 0, lienzo.width, lienzo.height);
                    img.src = previo;
                }
            }

            ajustar();
            window.addEventListener('resize', ajustar);

            const punto = (e) => {
                const r = lienzo.getBoundingClientRect();
                const t = e.touches ? e.touches[0] : e;
                return [t.clientX - r.left, t.clientY - r.top];
            };

            let firmando = false;

            const empezar = (e) => {
                if (e.touches) e.preventDefault();
                firmando = true;
                ctx.beginPath();
                ctx.moveTo(...punto(e));
            };

            const mover = (e) => {
                if (! firmando) return;
                if (e.touches) e.preventDefault();
                ctx.lineTo(...punto(e));
                ctx.stroke();
            };

            const terminar = () => {
                if (! firmando) return;
                firmando = false;
                campo.value = lienzo.toDataURL('image/png');
            };

            lienzo.addEventListener('mousedown', empezar);
            lienzo.addEventListener('mousemove', mover);
            lienzo.addEventListener('mouseup', terminar);
            lienzo.addEventListener('mouseout', terminar);
            lienzo.addEventListener('touchstart', empezar, { passive: false });
            lienzo.addEventListener('touchmove', mover, { passive: false });
            lienzo.addEventListener('touchend', terminar);

            if (limpiar) {
                limpiar.addEventListener('click', function (e) {
                    e.preventDefault();
                    ctx.clearRect(0, 0, lienzo.width, lienzo.height);
                    campo.value = '';
                });
            }
        });
        </script>
    @endpush
@endsection
