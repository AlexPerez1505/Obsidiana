@extends('layouts.dashboard')

@section('title', 'Detalle del equipo')
@section('page-title', 'Detalle del equipo')
@section('page-sub', 'Gestion de Inventario > Equipos > Detalle')

@push('head')
<style>
    .equipment-detail { display: grid; gap: 18px; }
    .equipment-detail__bar {
        display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap;
    }
    .equipment-detail__bar p { margin: 0; color: var(--muted); font-weight: 600; }

    .equipment-detail-card {
        display: grid;
        grid-template-columns: 300px 1fr;
        gap: 32px;
        padding: 28px;
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: 20px;
        box-shadow: var(--shadow);
        align-items: start;
    }

    .equipment-detail-side { display: grid; gap: 20px; }
    .equipment-detail-side > div {
        background: var(--surface-2);
        border: 1px solid var(--border);
        border-radius: 14px;
        padding: 18px;
    }
    .equipment-detail-side h4 {
        margin: 0 0 14px;
        font-size: 0.9rem;
        font-weight: 800;
        color: var(--muted);
    }
    .equipment-detail-image {
        min-height: 220px;
        display: grid;
        place-items: center;
        overflow: hidden;
    }
    .equipment-detail-image img {
        max-width: 100%;
        max-height: 240px;
        object-fit: contain;
        display: block;
    }
    .equipment-detail-image .no-img { color: #6b7280; }
    .equipment-detail-signature img {
        width: 100%;
        max-width: 260px;
        border-radius: 8px;
        border: 1px solid var(--border);
        background: #fff;
    }

    .equipment-detail-main { min-width: 0; }
    .equipment-detail-main h3 {
        margin: 0 0 22px;
        font-size: 1.4rem;
        font-weight: 900;
        color: var(--text);
    }

    .equipment-detail-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 14px;
    }
    .equipment-detail-item {
        display: flex;
        flex-direction: column;
        gap: 6px;
        padding: 14px 16px;
        background: var(--surface-2);
        border: 1px solid var(--border);
        border-radius: 12px;
    }
    .equipment-detail-item.full { grid-column: 1 / -1; }
    .equipment-detail-item dt {
        color: var(--muted);
        font-size: 0.75rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }
    .equipment-detail-item dd {
        color: var(--text);
        font-size: 0.95rem;
        font-weight: 700;
        margin: 0;
        overflow-wrap: anywhere;
    }

    .state-pill {
        display: inline-flex;
        align-items: center;
        padding: 5px 12px;
        border-radius: 999px;
        font-size: 0.82rem;
        font-weight: 800;
        border: 1px solid var(--border);
    }
    .state-pill.green { color: #16a329; border-color: #22c943; background: #f7fff8; }
    .state-pill.blue { color: #1689ff; border-color: #1689ff; background: #f5fbff; }
    .state-pill.red { color: #ff3131; border-color: #ff4b4b; background: #fff8f8; }

    @media (max-width: 980px) {
        .equipment-detail-card { grid-template-columns: 1fr; padding: 22px; }
        .equipment-detail-side { grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 680px) {
        .equipment-detail-side { grid-template-columns: 1fr; }
        .equipment-detail-grid { grid-template-columns: 1fr; }
    }
</style>
@endpush

@section('content')
    <section class="equipment-detail">
        <div class="equipment-detail__bar">
            <p>Consulta el detalle del equipo registrado.</p>
            <div style="display:flex; gap:10px;">
                <a href="{{ route('inventory.equipos.index') }}" class="btn btn--ghost" style="text-decoration:none;">Volver</a>
            </div>
        </div>

        <article class="equipment-detail-card">
            <aside class="equipment-detail-side">
                <div class="equipment-detail-image" aria-label="Imagen de {{ $equipo->tipo_equipo }}">
                    <h4>Imagen del equipo</h4>
                    @if($equipo->imagen_path)
                        <img src="{{ asset('storage/' . $equipo->imagen_path) }}" alt="{{ $equipo->tipo_equipo }}">
                    @else
                        <svg class="no-img" width="90" height="90" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.3"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>
                    @endif
                </div>

                @if($equipo->firma_path)
                    <div class="equipment-detail-signature">
                        <h4>Firma del responsable</h4>
                        <img src="{{ asset('storage/' . $equipo->firma_path) }}" alt="Firma">
                    </div>
                @endif
            </aside>

            <div class="equipment-detail-main">
                <h3>{{ $equipo->tipo_equipo }}</h3>

                <dl class="equipment-detail-grid">
                    @php
                        $tone = match($equipo->estado) {
                            'Mantenimiento' => 'blue',
                            'Inactivo', 'Dañado', 'Baja' => 'red',
                            default => 'green',
                        };
                    @endphp

                    <div class="equipment-detail-item"><dt>Tipo de equipo</dt><dd>{{ $equipo->tipo_equipo ?: '—' }}</dd></div>
                    <div class="equipment-detail-item"><dt>Subtipo</dt><dd>{{ $equipo->subtipo ?: '—' }}</dd></div>
                    <div class="equipment-detail-item"><dt>Marca</dt><dd>{{ $equipo->marca ?: '—' }}</dd></div>
                    <div class="equipment-detail-item"><dt>Modelo</dt><dd>{{ $equipo->modelo ?: '—' }}</dd></div>
                    <div class="equipment-detail-item"><dt>Número de serie</dt><dd>{{ $equipo->no_serie ?: '—' }}</dd></div>
                    <div class="equipment-detail-item"><dt>Número de serie base</dt><dd>{{ $equipo->no_serie_base ?: '—' }}</dd></div>
                    <div class="equipment-detail-item"><dt>Estado</dt>
                        <dd><span class="state-pill {{ $tone }}">{{ $equipo->estado ?: 'Activo' }}</span></dd>
                    </div>
                    <div class="equipment-detail-item"><dt>Fecha de adquisición</dt><dd>{{ $equipo->fecha_adquisicion ? $equipo->fecha_adquisicion->format('d/m/Y') : '—' }}</dd></div>
                    <div class="equipment-detail-item"><dt>Registrado por</dt><dd>{{ $equipo->registradoPor?->name ?? '—' }}</dd></div>
                    <div class="equipment-detail-item"><dt>Registrado el</dt><dd>{{ $equipo->created_at?->format('d/m/Y H:i') }}</dd></div>

                    @if($equipo->descripcion)
                        <div class="equipment-detail-item full"><dt>Descripción</dt><dd>{{ $equipo->descripcion }}</dd></div>
                    @endif
                </dl>
            </div>
        </article>
    </section>
@endsection
