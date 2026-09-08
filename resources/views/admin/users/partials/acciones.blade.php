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
        <svg viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="5" r="1.7"/><circle cx="12" cy="12" r="1.7"/><circle cx="12" cy="19" r="1.7"/></svg>
    </button>

    <div class="row-menu-pop" data-row-menu-pop role="menu" hidden>
        <a href="{{ route('admin.users.show', $u) }}" role="menuitem">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
            Ver detalle
        </a>

        {{-- Abre el mismo modal, ya con este usuario seleccionado. --}}
        <button type="button" role="menuitem" data-abrir-rh data-usuario="{{ $u->id }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
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
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M20 6L9 17l-5-5"/></svg>
                    Aprobar acceso
                </button>
            </form>
        @endif

        @if ($u->isBanned())
            <form method="POST" action="{{ route('admin.users.unban', $u) }}">
                @csrf
                <button type="submit" role="menuitem" class="ok">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>
                    Reactivar
                </button>
            </form>
        @elseif (! $u->is_admin)
            <form method="POST" action="{{ route('admin.users.ban', $u) }}"
                  onsubmit="return confirm('¿Quitarle el acceso a {{ $u->name }}?');">
                @csrf
                <button type="submit" role="menuitem" class="danger">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="10"/><path d="M4.93 4.93l14.14 14.14"/></svg>
                    Quitar acceso
                </button>
            </form>
        @endif
    </div>
</div>
