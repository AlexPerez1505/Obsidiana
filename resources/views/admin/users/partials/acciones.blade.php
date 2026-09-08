{{--
    Menú de tres puntos de un usuario.

    Vive aparte porque el mismo bloque se pinta dos veces (en la fila de la
    tabla y en el pie de la tarjeta): repetirlo era la vía segura para que
    una acción nueva quedara solo en una de las dos vistas.

    Recibe: $fila (el mapa que arma admin/users/index).
--}}

@php $u = $fila['modelo']; @endphp

<div class="row-menu" data-row-menu>
    <button type="button" class="row-menu-btn" data-row-menu-toggle
            aria-haspopup="true" aria-expanded="false"
            aria-label="Acciones de {{ $u->name }}">
        <x-gravityui-ellipsis-vertical />
    </button>

    <div class="row-menu-pop" data-row-menu-pop role="menu" hidden>
        <a href="{{ route('admin.users.show', $u) }}" role="menuitem">
            <x-gravityui-eye />
            Ver detalle
        </a>

        {{-- Abre el mismo modal, ya con este usuario seleccionado. --}}
        <button type="button" role="menuitem" data-abrir-rh data-usuario="{{ $u->id }}">
            <x-gravityui-pencil />
            Editar datos y roles
        </button>

        {{--
            Aquí iba un enlace a la pantalla vieja de permisos por usuario
            (admin.users.permissions). No se enlaza: su formulario guarda un
            "nivel" por permiso en una columna que no existe en la base. Lo
            que decide qué puede hacer alguien son sus roles, y esos se
            asignan en el modal de arriba.
        --}}

        @if ($u->isPending())
            <form method="POST" action="{{ route('admin.users.approve', $u) }}">
                @csrf
                <button type="submit" role="menuitem" class="ok">
                    <x-gravityui-check />
                    Aprobar acceso
                </button>
            </form>
        @endif

        @if ($u->isBanned())
            <form method="POST" action="{{ route('admin.users.unban', $u) }}">
                @csrf
                <button type="submit" role="menuitem" class="ok">
                    <x-gravityui-arrow-rotate-left />
                    Reactivar
                </button>
            </form>
        @elseif (! $u->is_admin)
            <form method="POST" action="{{ route('admin.users.ban', $u) }}"
                  onsubmit="return confirm('¿Quitarle el acceso a {{ $u->name }}?');">
                @csrf
                <button type="submit" role="menuitem" class="danger">
                    <x-gravityui-ban />
                    Quitar acceso
                </button>
            </form>
        @endif
    </div>
</div>
