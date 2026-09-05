@extends('structure.gestion_servicios.layout')

@section('title', 'Mantenimiento')

@section('service_content')
    <style>
        .category-link {
            text-decoration: none;
            color: inherit;
        }
        .category-item.active {
            background: rgba(34, 197, 94, .12);
            border-bottom-color: rgba(34, 197, 94, .7);
        }
        .category-item.active .category-name {
            color: #22C55E;
        }
        .tech-initials {
            width: 42px;
            height: 42px;
            flex: 0 0 42px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            background: rgba(34, 197, 94, .13);
            color: #22C55E;
            font-weight: 700;
            font-size: 14px;
        }
    </style>

    <div class="catalog-grid">
        <div class="card catalog-card">
            <div class="catalog-header">
                <h2 class="page-title">Técnicos</h2>
                <span class="catalog-count">{{ $technicians->count() }} {{ $technicians->count() === 1 ? 'opción' : 'opciones' }}</span>
            </div>

            @if ($technicians->isEmpty())
                <div class="catalog-empty">No hay técnicos registrados.</div>
            @else
                <div class="category-list">
                    @foreach ($technicians as $tech)
                        @php
                            $isSelected = $selected && $selected->id === $tech->id && $selected->is_external === $tech->is_external;
                            $link = $tech->is_external
                                ? route('gestion.servicios.mantenimiento.index', ['tipo' => 'externo'])
                                : route('gestion.servicios.mantenimiento.index', ['tecnico' => $tech->id]);
                        @endphp
                        <a href="{{ $link }}" class="category-item category-link {{ $isSelected ? 'active' : '' }}">
                            <div class="tech-initials">{{ $tech->initials }}</div>
                            <div class="category-info">
                                <span class="category-name">{{ $tech->name }}</span>
                                <span class="category-meta">{{ $tech->email }} · {{ $tech->count }} {{ $tech->count === 1 ? $tech->count_label : $tech->count_label . 's' }}</span>
                            </div>
                            <div class="category-arrow">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="9 18 15 12 9 6"/>
                                </svg>
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="card catalog-card service-section">
            @if ($selected)
                <div class="catalog-header">
                    <h2 class="page-title">
                        @if ($selected->is_external)
                            Servicios externos
                        @else
                            Servicios activos de {{ $selected->name }}
                        @endif
                    </h2>
                    <span class="catalog-count">{{ $services->count() }} {{ $services->count() === 1 ? 'servicio' : 'servicios' }}</span>
                </div>

                <div class="service-table-wrap">
                    <table class="service-table">
                        <thead>
                            <tr>
                                <th>OS</th>
                                <th>Cliente</th>
                                <th>Tipo</th>
                                <th>Estatus</th>
                                <th>Paso actual</th>
                                <th>Fecha</th>
                                <th style="text-align:right;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($services as $service)
                                @php
                                    $badgeClass = match ($service->status) {
                                        'en_progreso' => 'active',
                                        'registrado' => 'upcoming',
                                        'entregado' => 'finished',
                                        'cancelado' => 'finished',
                                        default => 'finished',
                                    };
                                @endphp
                                <tr>
                                    <td class="service-name">{{ $service->service_number }}</td>
                                    <td>{{ $service->customer?->nombre }} {{ $service->customer?->apellido }}</td>
                                    <td style="text-transform:capitalize;">{{ $service->service_type }}</td>
                                    <td>
                                        <span class="service-badge {{ $badgeClass }}">{{ $service->status }}</span>
                                    </td>
                                    <td>{{ $service->currentStep?->name ?? '—' }}</td>
                                    <td class="service-dates">{{ $service->created_at?->format('d/m/Y') }}</td>
                                    <td style="text-align:right;">
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="empty-cell">
                                        @if ($selected->is_external)
                                            No hay servicios externos registrados.
                                        @else
                                            No hay servicios activos asignados a este técnico.
                                        @endif
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @else
                <div class="catalog-empty">Selecciona un técnico o mantenimiento externo para ver sus servicios.</div>
            @endif
        </div>
    </div>
@endsection
