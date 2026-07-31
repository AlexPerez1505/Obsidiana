@extends('layouts.dashboard')

@section('title')
    @yield('title', 'Configuración')
@endsection

@section('content')
    <style>
        /* ===== Estilos globales del módulo Configuración =====
           Estética: dark glass tecnológico (glassmorphism oscuro y sutil)
           - Superficie azul marino semitransparente
           - Borde luminoso azul
           - Sombra difusa + blur sutil
        */
        .catalog-grid {
            display: grid;
            grid-template-columns: 30% 70%;
            gap: 22px;
            align-items: start;
        }
        .catalog-card {
            max-width: none;
            margin: 0;
            overflow: hidden;
            background: rgba(8, 18, 40, 0.82);
            border: 1px solid rgba(0, 168, 255, 0.55);
            border-radius: 18px;
            padding: 22px;
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            box-shadow:
                0 12px 40px rgba(0, 0, 0, 0.45),
                0 0 18px rgba(0, 168, 255, 0.35),
                0 0 0 1px rgba(0, 0, 0, 0.3),
                inset 0 1px 0 rgba(255, 255, 255, 0.04);
        }
        .catalog-card.congress-section { margin-top: 0; }
        .catalog-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 10px;
        }
        .catalog-header h2 {
            margin: 0;
            color: #fff;
        }
        .catalog-header-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .catalog-count {
            color: rgba(255, 255, 255, 0.55);
            font-size: 13px;
            font-weight: 600;
        }
        .catalog-search-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 38px;
            height: 38px;
            flex: 0 0 auto;
            border: 1px solid rgba(0, 168, 255, 0.55);
            border-radius: 10px;
            background: rgba(8, 18, 40, 0.45);
            color: #00A8FF;
            cursor: pointer;
            box-shadow: 0 0 10px rgba(0, 168, 255, 0.25);
            transition: background .16s ease, border-color .16s ease, box-shadow .16s ease, transform .16s ease;
        }
        .catalog-search-btn:hover {
            background: rgba(0, 168, 255, 0.14);
            border-color: #00A8FF;
            box-shadow: 0 0 16px rgba(0, 168, 255, 0.45);
            transform: scale(1.06);
        }
        .catalog-search-btn svg { width: 19px; height: 19px; }
        .catalog-search-wrap {
            max-height: 0;
            overflow: hidden;
            opacity: 0;
            transition: max-height .26s ease, opacity .2s ease, margin .2s ease;
        }
        .catalog-search-wrap.open {
            max-height: 70px;
            opacity: 1;
            margin-top: 14px;
        }
        .catalog-search {
            position: relative;
            display: flex;
            align-items: center;
        }
        .catalog-search .search-icon {
            position: absolute;
            left: 13px;
            color: #00A8FF;
            pointer-events: none;
            filter: drop-shadow(0 0 4px rgba(0, 168, 255, 0.4));
        }
        .catalog-search .search-icon svg { width: 17px; height: 17px; }
        .catalog-search input {
            width: 100%;
            padding: 11px 14px 11px 40px;
            border: 1px solid rgba(0, 168, 255, 0.45);
            border-radius: 11px;
            background: rgba(8, 18, 40, 0.55);
            color: #fff;
            font-size: 14px;
            font-family: inherit;
            outline: none;
            transition: border-color .16s ease, box-shadow .16s ease;
        }
        .catalog-search input::placeholder { color: rgba(255, 255, 255, 0.4); }
        .catalog-search input:focus {
            border-color: #00A8FF;
            box-shadow: 0 0 12px rgba(0, 168, 255, 0.35);
        }
        .catalog-search-noresult {
            padding: 22px 12px;
            color: rgba(255, 255, 255, 0.55);
            text-align: center;
            font-size: 14px;
            display: none;
        }
        .category-list {
            border-top: 1px solid rgba(0, 168, 255, 0.35);
            margin-top: 18px;
        }
        .category-item {
            display: flex;
            align-items: center;
            gap: 13px;
            min-height: 70px;
            padding: 10px 2px;
            border-bottom: 1px solid rgba(0, 168, 255, 0.35);
            transition: background .16s ease, border-color .16s ease;
        }
        .category-item:hover {
            background: rgba(0, 168, 255, 0.08);
            border-bottom-color: rgba(0, 168, 255, 0.7);
        }
        .category-icon {
            width: 42px;
            height: 42px;
            flex: 0 0 42px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
        }
        .category-icon svg { width: 22px; height: 22px; }
        .category-info { min-width: 0; flex: 1; }
        .category-name {
            display: block;
            overflow: hidden;
            color: #fff;
            font-size: 15px;
            font-weight: 700;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .category-meta {
            color: rgba(255, 255, 255, 0.55);
            font-size: 12px;
            margin-top: 3px;
        }
        .category-arrow {
            color: rgba(255, 255, 255, 0.45);
            flex: 0 0 auto;
        }
        .category-arrow svg { width: 18px; height: 18px; }
        .catalog-create {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            margin-top: 18px;
            padding: 11px 14px;
            border: 1px dashed rgba(0, 168, 255, 0.75);
            border-radius: 10px;
            color: #00A8FF;
            font-size: 14px;
            font-weight: 700;
            text-decoration: none;
            background: rgba(8, 18, 40, 0.45);
            transition: background .16s ease, border-color .16s ease, box-shadow .16s ease;
        }
        .catalog-create:hover {
            background: rgba(0, 168, 255, 0.14);
            border-color: #00A8FF;
            box-shadow: 0 0 16px rgba(0, 168, 255, 0.45);
        }
        .catalog-empty {
            padding: 28px 12px 18px;
            color: rgba(255, 255, 255, 0.55);
            text-align: center;
            font-size: 14px;
        }

        /* ===== Tabla de congresos ===== */
        .congress-section { margin-top: 28px; }
        .congress-table-wrap { width: 100%; overflow-x: auto; border-radius: 14px; }
        .congress-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            font-size: 14px;
            min-width: 760px;
        }
        .congress-table thead th {
            text-align: left;
            padding: 13px 14px;
            color: rgba(255, 255, 255, 0.75);
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .04em;
            border-bottom: 1px solid rgba(0, 168, 255, 0.35);
            white-space: nowrap;
        }
        .congress-table tbody td {
            padding: 12px 14px;
            border-bottom: 1px solid rgba(0, 168, 255, 0.18);
            color: #fff;
            vertical-align: middle;
        }
        .congress-table tbody tr { transition: background .16s ease; }
        .congress-table tbody tr:hover { background: rgba(0, 168, 255, 0.08); }
        .congress-table tbody tr:last-child td { border-bottom: none; }
        .congress-thumb {
            width: 46px;
            height: 46px;
            border-radius: 10px;
            object-fit: cover;
            border: 1px solid rgba(0, 168, 255, 0.35);
            box-shadow: 0 0 8px rgba(0, 168, 255, 0.2);
        }
        .congress-thumb-placeholder {
            width: 46px;
            height: 46px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(0, 168, 255, 0.10);
            border: 1px solid rgba(0, 168, 255, 0.35);
            color: #00A8FF;
        }
        .congress-thumb-placeholder svg { width: 22px; height: 22px; }
        .congress-name { font-weight: 700; color: #fff; }
        .congress-dates { color: rgba(255, 255, 255, 0.7); font-size: 13px; white-space: nowrap; }
        .congress-place { color: rgba(255, 255, 255, 0.7); font-size: 13px; }
        .congress-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            white-space: nowrap;
        }
        .congress-badge::before {
            content: "";
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: currentColor;
            box-shadow: 0 0 6px currentColor;
        }
        .congress-badge.active   { color: #4ade80; background: rgba(74, 222, 128, 0.14); border: 1px solid rgba(74, 222, 128, 0.4); }
        .congress-badge.upcoming { color: #00A8FF; background: rgba(0, 168, 255, 0.14); border: 1px solid rgba(0, 168, 255, 0.4); }
        .congress-badge.finished { color: rgba(255, 255, 255, 0.55); background: rgba(255, 255, 255, 0.06); border: 1px solid rgba(255, 255, 255, 0.18); }
        .congress-actions { display: flex; align-items: center; gap: 8px; white-space: nowrap; }
        .congress-action-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border-radius: 8px;
            border: 1px solid rgba(0, 168, 255, 0.35);
            background: rgba(8, 18, 40, 0.45);
            color: #00A8FF;
            cursor: pointer;
            transition: background .16s ease, border-color .16s ease, box-shadow .16s ease;
        }
        .congress-action-btn:hover {
            background: rgba(0, 168, 255, 0.14);
            border-color: #00A8FF;
            box-shadow: 0 0 10px rgba(0, 168, 255, 0.35);
        }
        .congress-action-btn svg { width: 16px; height: 16px; }
        .congress-action-btn.danger { color: #ff4a4a; border-color: rgba(255, 74, 74, 0.35); }
        .congress-action-btn.danger:hover { background: rgba(255, 74, 74, 0.14); border-color: #ff4a4a; box-shadow: 0 0 10px rgba(255, 74, 74, 0.35); }

        /* ===== Modo claro: glass oscuro suave, bordes discretos ===== */
        :root[data-theme="light"] .catalog-card {
            background: rgba(15, 23, 42, 0.06);
            border: 1px solid rgba(15, 23, 42, 0.14);
            box-shadow: 0 8px 28px rgba(15, 23, 42, 0.08);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }
        :root[data-theme="light"] .catalog-header h2 { color: var(--text); }
        :root[data-theme="light"] .catalog-count { color: var(--muted); }
        :root[data-theme="light"] .category-list { border-top-color: rgba(15, 23, 42, 0.10); }
        :root[data-theme="light"] .category-item { border-bottom-color: rgba(15, 23, 42, 0.10); }
        :root[data-theme="light"] .category-item:hover {
            background: rgba(15, 23, 42, 0.04);
            border-bottom-color: rgba(15, 23, 42, 0.18);
        }
        :root[data-theme="light"] .category-name { color: var(--text); }
        :root[data-theme="light"] .category-meta { color: var(--muted); }
        :root[data-theme="light"] .category-arrow { color: var(--muted); }
        :root[data-theme="light"] .catalog-empty { color: var(--muted); }
        :root[data-theme="light"] .catalog-create {
            background: rgba(15, 23, 42, 0.04);
            border: 1px dashed rgba(0, 122, 255, 0.45);
            color: var(--primary);
        }
        :root[data-theme="light"] .catalog-create:hover {
            background: var(--primary-soft);
            border-color: var(--primary);
            box-shadow: 0 0 10px rgba(0, 122, 255, 0.18);
        }
        :root[data-theme="light"] .catalog-search-btn {
            background: rgba(15, 23, 42, 0.04);
            border: 1px solid rgba(0, 122, 255, 0.35);
            color: var(--primary);
            box-shadow: none;
        }
        :root[data-theme="light"] .catalog-search-btn:hover {
            background: var(--primary-soft);
            border-color: var(--primary);
            box-shadow: 0 0 10px rgba(0, 122, 255, 0.18);
        }
        :root[data-theme="light"] .catalog-search .search-icon { color: var(--primary); filter: none; }
        :root[data-theme="light"] .catalog-search input {
            background: rgba(15, 23, 42, 0.04);
            border: 1px solid rgba(15, 23, 42, 0.14);
            color: var(--text);
        }
        :root[data-theme="light"] .catalog-search input::placeholder { color: var(--muted); }
        :root[data-theme="light"] .catalog-search input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 10px rgba(0, 122, 255, 0.18);
        }
        :root[data-theme="light"] .catalog-search-noresult { color: var(--muted); }

        /* ===== Modo claro: tabla de congresos ===== */
        :root[data-theme="light"] .congress-table thead th {
            color: var(--muted);
            border-bottom-color: rgba(15, 23, 42, 0.14);
        }
        :root[data-theme="light"] .congress-table tbody td {
            color: var(--text);
            border-bottom-color: rgba(15, 23, 42, 0.08);
        }
        :root[data-theme="light"] .congress-table tbody tr:hover { background: rgba(15, 23, 42, 0.04); }
        :root[data-theme="light"] .congress-thumb { border-color: rgba(15, 23, 42, 0.14); box-shadow: none; }
        :root[data-theme="light"] .congress-thumb-placeholder {
            background: rgba(0, 122, 255, 0.08);
            border-color: rgba(0, 122, 255, 0.25);
            color: var(--primary);
        }
        :root[data-theme="light"] .congress-name { color: var(--text); }
        :root[data-theme="light"] .congress-dates { color: var(--muted); }
        :root[data-theme="light"] .congress-place { color: var(--muted); }
        :root[data-theme="light"] .congress-badge.finished {
            color: var(--muted);
            background: rgba(15, 23, 42, 0.06);
            border-color: rgba(15, 23, 42, 0.14);
        }
        :root[data-theme="light"] .congress-action-btn {
            background: rgba(15, 23, 42, 0.04);
            border-color: rgba(0, 122, 255, 0.25);
            color: var(--primary);
            box-shadow: none;
        }
        :root[data-theme="light"] .congress-action-btn:hover {
            background: var(--primary-soft);
            border-color: var(--primary);
            box-shadow: 0 0 8px rgba(0, 122, 255, 0.18);
        }
        :root[data-theme="light"] .congress-action-btn.danger { color: var(--danger); border-color: rgba(255, 74, 74, 0.25); }
        :root[data-theme="light"] .congress-action-btn.danger:hover { background: var(--danger-soft); border-color: var(--danger); box-shadow: none; }

        @media (max-width: 1024px) {
            .catalog-grid { grid-template-columns: 1fr; }
        }
        @media (max-width: 640px) {
            .catalog-card { margin: 0; }
        }
    </style>

    @yield('configuracion_content')
@endsection
