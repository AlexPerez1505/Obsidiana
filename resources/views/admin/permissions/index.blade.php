@extends('layouts.dashboard')
@section('title', 'Permisos')
@section('page-title', 'Permisos')
@section('page-sub', 'Gestiona los permisos del sistema')

@section('content')
    <x-ui.card style="margin-bottom:18px;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; flex-wrap:wrap; gap:10px;">
            <x-ui.section-title style="margin:0;">Permisos disponibles</x-ui.section-title>
            <a href="{{ route('admin.permissions.create') }}" class="badge badge--ok" style="text-decoration:none;">+ Nuevo permiso</a>
        </div>

        @if ($permissions->isEmpty())
            <p class="muted">No hay permisos creados todavía.</p>
        @else
            <div style="overflow-x:auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Identificador</th>
                            <th>Etiqueta</th>
                            <th>Descripción</th>
                            <th>Usuarios</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($permissions as $p)
                            <tr>
                                <td><code>{{ $p->name }}</code></td>
                                <td>{{ $p->label }}</td>
                                <td class="muted">{{ $p->description ?? '-' }}</td>
                                <td>{{ $p->users_count }}</td>
                                <td>
                                    <a class="link" href="{{ route('admin.permissions.edit', $p) }}">Editar</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-ui.card>
@endsection
