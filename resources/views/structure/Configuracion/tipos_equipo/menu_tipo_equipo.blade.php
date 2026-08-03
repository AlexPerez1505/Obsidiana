@extends('structure.Configuracion.layout')

@section('title', 'Tipos de Equipo')

@section('configuracion_content')
    <div class="catalog-card" style="margin-bottom:22px;">
        <div style="display:flex; align-items:center; justify-content:space-between; gap:18px; flex-wrap:wrap;">
            <div>
                <h2 style="margin:0; font-size:24px; font-weight:700; color:#fff;">Tipos de Equipo</h2>
                <p style="margin:4px 0 0; color:rgba(255,255,255,0.55); font-size:14px;">Catálogo de tipos de equipo disponibles</p>
            </div>
            <a href="{{ route('configuracion.tipos_equipo.create') }}" style="background:linear-gradient(135deg, #00A8FF, #7C3AED); color:#fff; border:1px solid rgba(255,255,255,0.15); padding:10px 18px; border-radius:12px; font-size:14px; font-weight:600; text-decoration:none; display:inline-flex; align-items:center; gap:8px; box-shadow:0 0 12px rgba(59,130,246,0.35), 0 0 30px rgba(124,58,237,0.2); transition:all 0.2s ease;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Agregar tipo de equipo
            </a>
        </div>
    </div>

    <div class="stats-grid" style="display:grid; grid-template-columns:repeat(4, 1fr); gap:18px; margin-bottom:22px;">
        <div class="stat-card">
            <div class="stat-icon" style="background:rgba(0,168,255,0.12); color:#00A8FF;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="4" width="16" height="16" rx="2"/><path d="M9 9h6v6H9z"/></svg>
            </div>
            <div class="stat-info">
                <div class="stat-number">{{ number_format($totalTypes) }}</div>
                <div class="stat-label">Tipos registrados</div>
                <div class="stat-sublabel">Total activos</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background:rgba(124,58,237,0.12); color:#A855F7;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18"/><path d="M3 12h18"/><path d="M3 18h18"/></svg>
            </div>
            <div class="stat-info">
                <div class="stat-number">{{ number_format($totalSubtypes) }}</div>
                <div class="stat-label">Subtipos registrados</div>
                <div class="stat-sublabel">Total activos</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background:rgba(34,197,94,0.12); color:#22C55E;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
            </div>
            <div class="stat-info">
                <div class="stat-number">{{ number_format($totalBrands) }}</div>
                <div class="stat-label">Marcas registradas</div>
                <div class="stat-sublabel">Total activos</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background:rgba(249,115,22,0.12); color:#F97316;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
            </div>
            <div class="stat-info">
                <div class="stat-number">{{ number_format($totalModels) }}</div>
                <div class="stat-label">Modelos registrados</div>
                <div class="stat-sublabel">Total activos</div>
            </div>
        </div>
    </div>

    <div class="catalog-card">
        <div class="toolbar" style="display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap; margin-bottom:18px;">
            <div class="search-box" style="flex:1; min-width:260px;">
                <svg class="search-icon" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
                <input type="text" placeholder="Buscar tipo de equipo..." readonly>
            </div>
            <div style="display:flex; align-items:center; gap:12px;">
                <button type="button" class="toolbar-btn" disabled>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                    Exportar
                </button>
            </div>
        </div>

        <div class="table-wrap">
            <table class="equipment-table">
                <thead>
                    <tr>
                        <th>TIPO DE EQUIPO</th>
                        <th>SUBTIPOS</th>
                        <th>MARCAS</th>
                        <th>MODELOS</th>
                        <th>ACCIONES</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($equipmentTypes as $type)
                        <tr>
                            <td>
                                <div class="type-cell">
                                    <div class="type-icon">
                                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="4" width="16" height="16" rx="2"/><path d="M9 9h6v6H9z"/></svg>
                                    </div>
                                    <div>
                                        <div class="type-name">{{ $type->name }}</div>
                                        <div class="type-desc">{{ $type->description ?? 'Sin descripción' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="name-list">
                                    @forelse ($type->subtypes_names as $name)
                                        <span class="name-pill">{{ $name }}</span>
                                    @empty
                                        <span class="name-empty">—</span>
                                    @endforelse
                                </div>
                            </td>
                            <td>
                                <div class="name-list">
                                    @forelse ($type->brands_names as $name)
                                        <span class="name-pill">{{ $name }}</span>
                                    @empty
                                        <span class="name-empty">—</span>
                                    @endforelse
                                </div>
                            </td>
                            <td>
                                <div class="name-list">
                                    @forelse ($type->models_names as $name)
                                        <span class="name-pill">{{ $name }}</span>
                                    @empty
                                        <span class="name-empty">—</span>
                                    @endforelse
                                </div>
                            </td>
                            <td>
                                <div class="actions">
                                    <button type="button" class="action-btn action-btn--edit" title="Editar" disabled>
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                    </button>
                                    <button type="button" class="action-btn action-btn--delete" title="Eliminar" disabled>
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="empty-cell">No hay tipos de equipo registrados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($equipmentTypes->hasPages())
            <div style="margin-top:18px; display:flex; justify-content:flex-end;">
                {{ $equipmentTypes->links('vendor.pagination.default') }}
            </div>
        @endif
    </div>

    @push('scripts')
    <style>
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 18px; }
        @media (max-width: 900px) { .stats-grid { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 560px) { .stats-grid { grid-template-columns: 1fr; } }
        .stat-card {
            display: flex;
            align-items: center;
            gap: 14px;
            background: rgba(8,18,40,0.82);
            border: 1px solid rgba(0,168,255,0.55);
            border-radius: 14px;
            padding: 16px;
            box-shadow: 0 8px 28px rgba(0,0,0,0.35), 0 0 14px rgba(0,168,255,0.2), inset 0 1px 0 rgba(255,255,255,0.04);
        }
        .stat-icon {
            width: 46px; height: 46px;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
        }
        .stat-info { flex: 1; }
        .stat-number { font-size: 22px; font-weight: 800; color: #fff; }
        .stat-label { font-size: 13px; color: #fff; font-weight: 600; }
        .stat-sublabel { font-size: 12px; color: rgba(255,255,255,0.5); }

        .toolbar { display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap; }
        .search-box { position: relative; }
        .search-box input {
            width: 100%;
            padding: 10px 14px 10px 38px;
            border: 1px solid rgba(0,168,255,0.45);
            border-radius: 11px;
            background: rgba(8,18,40,0.55);
            color: #fff;
            font-size: 14px;
            outline: none;
            cursor: not-allowed;
        }
        .search-box .search-icon {
            position: absolute; left: 13px; top: 50%; transform: translateY(-50%);
            color: #00A8FF; pointer-events: none;
        }
        .toolbar-btn {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 9px 14px;
            border: 1px solid rgba(0,168,255,0.45);
            border-radius: 11px;
            background: rgba(8,18,40,0.55);
            color: #fff;
            font-size: 13px;
            font-weight: 600;
            cursor: not-allowed;
            opacity: 0.7;
        }

        .table-wrap { overflow-x: auto; border-radius: 14px; }
        .equipment-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            font-size: 14px;
            min-width: 760px;
        }
        .equipment-table thead th {
            text-align: center;
            padding: 13px 14px;
            color: rgba(255,255,255,0.75);
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .04em;
            border-bottom: 1px solid rgba(0,168,255,0.35);
            white-space: nowrap;
            background: rgba(8,18,40,0.96);
        }
        .equipment-table thead th:first-child { text-align: left; }
        .equipment-table tbody td {
            padding: 14px;
            border-bottom: 1px solid rgba(0,168,255,0.18);
            color: #fff;
            vertical-align: middle;
            text-align: center;
        }
        .equipment-table tbody tr { transition: background .16s ease; }
        .equipment-table tbody tr:hover { background: rgba(0,168,255,0.08); }
        .equipment-table tbody td:first-child { text-align: left; }

        .type-cell { display: flex; align-items: center; gap: 12px; }
        .type-icon {
            width: 42px; height: 42px;
            border-radius: 12px;
            background: rgba(0,168,255,0.12);
            color: #00A8FF;
            display: flex; align-items: center; justify-content: center;
            flex: 0 0 42px;
        }
        .type-name { font-weight: 700; color: #fff; }
        .type-desc { font-size: 12px; color: rgba(255,255,255,0.55); margin-top: 2px; }

        .name-list {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 5px;
            max-height: 80px;
            overflow-y: auto;
        }
        .name-pill {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 10px;
            font-size: 12px;
            font-weight: 600;
            white-space: nowrap;
            background: rgba(0,168,255,0.15);
            color: #00A8FF;
            border: 1px solid rgba(0,168,255,0.35);
        }
        .name-empty { color: rgba(255,255,255,0.4); font-size: 13px; }

        .actions { display: flex; align-items: center; justify-content: center; gap: 8px; }
        .action-btn {
            width: 34px; height: 34px;
            border-radius: 10px;
            border: 1px solid rgba(0,168,255,0.35);
            background: rgba(8,18,40,0.55);
            color: rgba(255,255,255,0.7);
            display: inline-flex; align-items: center; justify-content: center;
            cursor: not-allowed;
            opacity: 0.6;
            transition: all .16s ease;
        }
        .action-btn--edit:hover { color: #00A8FF; border-color: #00A8FF; }
        .action-btn--delete:hover { color: #ef4444; border-color: #ef4444; }

        .empty-cell {
            text-align: center;
            color: rgba(255,255,255,0.55);
            padding: 28px 14px;
        }

        :root[data-theme="light"] .stat-card { background: linear-gradient(145deg, rgba(15,23,42,0.04), rgba(15,23,42,0.08)); border-color: rgba(15,23,42,0.14); }
        :root[data-theme="light"] .stat-number { color: var(--text); }
        :root[data-theme="light"] .stat-label { color: var(--text); }
        :root[data-theme="light"] .stat-sublabel { color: var(--muted); }
        :root[data-theme="light"] .search-box input { background: #fff; color: var(--text); border-color: rgba(15,23,42,0.18); }
        :root[data-theme="light"] .toolbar-btn { background: #fff; color: var(--text); border-color: rgba(15,23,42,0.18); }
        :root[data-theme="light"] .equipment-table thead th { background: rgba(15,23,42,0.04); color: var(--text); border-color: rgba(15,23,42,0.14); }
        :root[data-theme="light"] .equipment-table tbody td { color: var(--text); border-color: rgba(15,23,42,0.08); }
        :root[data-theme="light"] .equipment-table tbody tr:hover { background: rgba(0,122,255,0.08); }
        :root[data-theme="light"] .type-name { color: var(--text); }
        :root[data-theme="light"] .type-desc { color: var(--muted); }
        :root[data-theme="light"] .action-btn { background: rgba(15,23,42,0.04); border-color: rgba(15,23,42,0.14); color: var(--text); }
        :root[data-theme="light"] .counter--blue { background: rgba(0,122,255,0.1); color: var(--primary); border-color: rgba(0,122,255,0.25); }
        :root[data-theme="light"] .name-pill { background: rgba(0,122,255,0.1); color: var(--primary); border-color: rgba(0,122,255,0.25); }
        :root[data-theme="light"] .name-empty { color: var(--muted); }
    </style>
    @endpush
@endsection
