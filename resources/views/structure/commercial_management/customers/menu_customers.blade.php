@extends('layouts.dashboard')
@section('title', 'Clientes')

@section('content')
    <div class="card" style="margin-bottom:18px;">
        <div style="display:flex; align-items:center; justify-content:space-between; gap:18px; flex-wrap:wrap;">
            <div style="display:flex; align-items:center; gap:14px;">
                <svg viewBox="0 0 24 24" fill="currentColor" style="width:48px;height:48px;color:var(--primary);">
                    <circle cx="9" cy="8" r="4"/>
                    <circle cx="17" cy="8" r="3" opacity="0.75"/>
                    <path d="M2 20c0-3.5 3-5.5 7-5.5s7 2 7 5.5"/>
                    <path d="M13 14.5c3.5 0 6 2 6 5.5" opacity="0.75"/>
                </svg>
                <div>
                    <h2 class="section-title" style="margin:0; font-size:22px;">Clientes</h2>
                    <p class="muted" style="margin:2px 0 0;">Administra y consulta información de tus clientes</p>
                </div>
            </div>
            <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                <button type="button" class="btn btn--ghost" style="display:inline-flex; align-items:center; gap:6px; color:var(--text);">
                    Más acciones
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><polyline points="6 9 12 15 18 9"/></svg>
                </button>
                <a href="{{ route('commercial.clientes.create') }}" class="btn" style="display:inline-flex; align-items:center; gap:6px; text-decoration:none;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    Registrar cliente
                </a>
            </div>
        </div>
    </div>

    {{-- Filtros --}}
    <div class="card" style="margin-bottom:18px;">
        <div style="display:flex; align-items:center; gap:12px; flex-wrap:wrap;">
            <input type="text" placeholder="Buscar cliente por nombre, empresa o contacto..." style="flex:1; min-width:240px; padding:10px 12px; border:1px solid var(--border); border-radius:9px; font-size:14px; background:var(--surface); color:var(--text);" />
            <select style="padding:10px 12px; border:1px solid var(--border); border-radius:9px; font-size:14px; background:var(--surface); color:var(--text);">
                <option>Todos los asesores</option>
            </select>
            <select style="padding:10px 12px; border:1px solid var(--border); border-radius:9px; font-size:14px; background:var(--surface); color:var(--text);">
                <option>Con promoción</option>
                <option>Sí</option>
                <option>No</option>
            </select>
        </div>
    </div>

    {{-- Tabla de clientes --}}
    <div class="card" style="overflow-x:auto;">
        <table style="width:100%; border-collapse:collapse; font-size:14px;">
            <thead>
                <tr style="border-bottom:1px solid var(--border); text-align:left;">
                    <th style="padding:12px; font-weight:600;">Cliente</th>
                    <th style="padding:12px; font-weight:600;">Teléfono</th>
                    <th style="padding:12px; font-weight:600;">Correo</th>
                    <th style="padding:12px; font-weight:600;">Asesor</th>
                    <th style="padding:12px; font-weight:600;">Promoción</th>
                    <th style="padding:12px; font-weight:600;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($customers as $customer)
                    <tr style="border-bottom:1px solid var(--border);">
                        <td style="padding:12px;">{{ $customer->nombre }} {{ $customer->apellido }}</td>
                        <td style="padding:12px;">{{ $customer->telefono }}</td>
                        <td style="padding:12px;">{{ $customer->correo ?? '-' }}</td>
                        <td style="padding:12px;">{{ $customer->seller?->name ?? 'Sin asesor' }}</td>
                        <td style="padding:12px;">{{ $customer->receives_promotion ? 'Sí' : 'No' }}</td>
                        <td style="padding:12px;">
                            <a href="#" class="btn btn--ghost" style="text-decoration:none;">Ver</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="padding:24px; text-align:center; color:var(--muted);">
                            No hay clientes registrados.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
