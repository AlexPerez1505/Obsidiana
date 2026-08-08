@extends('layouts.dashboard')

@section('title')
    @yield('title', 'Gestión de Servicios')
@endsection

@section('content')
    <style>
        /* ===== Estilos globales del módulo Gestión de Servicios =====
           Estética: dark glass tecnológico (glassmorphism oscuro y sutil)
           - Superficie verde oscuro semitransparente
           - Borde luminoso verde
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
            border: 1px solid rgba(34, 197, 94, 0.55);
            border-radius: 18px;
            padding: 22px;
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            box-shadow:
                0 12px 40px rgba(0, 0, 0, 0.45),
                0 0 18px rgba(34, 197, 94, 0.35),
                0 0 0 1px rgba(0, 0, 0, 0.3),
                inset 0 1px 0 rgba(255, 255, 255, 0.04);
        }
        .catalog-card.service-section { margin-top: 0; }
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
            border: 1px solid rgba(34, 197, 94, 0.55);
            border-radius: 10px;
            background: rgba(8, 18, 40, 0.45);
            color: #22C55E;
            cursor: pointer;
            box-shadow: 0 0 10px rgba(34, 197, 94, 0.25);
            transition: background .16s ease, border-color .16s ease, box-shadow .16s ease, transform .16s ease;
        }
        .catalog-search-btn:hover {
            background: rgba(34, 197, 94, 0.14);
            border-color: #22C55E;
            box-shadow: 0 0 16px rgba(34, 197, 94, 0.45);
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
            color: #22C55E;
            pointer-events: none;
            filter: drop-shadow(0 0 4px rgba(34, 197, 94, 0.4));
        }
        .catalog-search .search-icon svg { width: 17px; height: 17px; }
        .catalog-search input {
            width: 100%;
            padding: 11px 14px 11px 40px;
            border: 1px solid rgba(34, 197, 94, 0.45);
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
            border-color: #22C55E;
            box-shadow: 0 0 12px rgba(34, 197, 94, 0.35);
        }
        .catalog-search-noresult {
            padding: 22px 12px;
            color: rgba(255, 255, 255, 0.55);
            text-align: center;
            font-size: 14px;
            display: none;
        }
        .category-list {
            border-top: 1px solid rgba(34, 197, 94, 0.35);
            margin-top: 18px;
            max-height: 460px;
            overflow-y: auto;
        }
        .category-item {
            display: flex;
            align-items: center;
            gap: 13px;
            min-height: 70px;
            padding: 10px 2px;
            border-bottom: 1px solid rgba(34, 197, 94, 0.35);
            transition: background .16s ease, border-color .16s ease;
        }
        .category-item:hover {
            background: rgba(34, 197, 94, 0.08);
            border-bottom-color: rgba(34, 197, 94, 0.7);
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
            color: var(--text);
            font-size: 15px;
            font-weight: 700;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .category-meta {
            color: var(--muted);
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
            border: 1px dashed rgba(34, 197, 94, 0.75);
            border-radius: 10px;
            color: #22C55E;
            font-size: 14px;
            font-weight: 700;
            text-decoration: none;
            background: rgba(8, 18, 40, 0.45);
            transition: background .16s ease, border-color .16s ease, box-shadow .16s ease;
        }
        .catalog-create:hover {
            background: rgba(34, 197, 94, 0.14);
            border-color: #22C55E;
            box-shadow: 0 0 16px rgba(34, 197, 94, 0.45);
        }
        .catalog-empty {
            padding: 28px 12px 18px;
            color: rgba(255, 255, 255, 0.55);
            text-align: center;
            font-size: 14px;
        }

        /* ===== Tabla de servicios ===== */
        .service-section { margin-top: 28px; }
        .service-table-wrap { width: 100%; overflow-x: auto; overflow-y: auto; max-height: 440px; border-radius: 14px; }
        .service-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            font-size: 14px;
            min-width: 760px;
        }
        .service-table thead th {
            text-align: center;
            padding: 13px 14px;
            color: rgba(255, 255, 255, 0.75);
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .04em;
            border-bottom: 1px solid rgba(34, 197, 94, 0.35);
            white-space: nowrap;
            position: sticky;
            top: 0;
            background: rgba(8, 18, 40, 0.96);
            z-index: 1;
        }
        .service-table tbody td {
            padding: 12px 14px;
            border-bottom: 1px solid rgba(34, 197, 94, 0.18);
            color: #fff;
            vertical-align: middle;
            text-align: center;
        }
        .service-table tbody tr { transition: background .16s ease; }
        .service-table tbody tr:hover { background: rgba(34, 197, 94, 0.08); }
        .service-table tbody tr:last-child td { border-bottom: none; }
        .service-table th:last-child,
        .service-table td:last-child { text-align: right; }
        .service-thumb {
            width: 46px;
            height: 46px;
            border-radius: 10px;
            object-fit: cover;
            border: 1px solid rgba(34, 197, 94, 0.35);
            box-shadow: 0 0 8px rgba(34, 197, 94, 0.2);
        }
        .service-thumb-placeholder {
            width: 46px;
            height: 46px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(34, 197, 94, 0.10);
            border: 1px solid rgba(34, 197, 94, 0.35);
            color: #22C55E;
            margin: 0 auto;
        }
        .service-thumb-placeholder svg { width: 22px; height: 22px; }
        .service-thumb-file {
            width: 46px; height: 46px; border-radius: 10px;
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            background: rgba(34, 197, 94, 0.10); border: 1px solid rgba(34, 197, 94, 0.35);
            gap: 1px; cursor: help; color: #22C55E; margin: 0 auto;
        }
        .service-thumb-file svg { width: 20px; height: 20px; }
        .service-thumb-file .file-ext { font-size: 8px; font-weight: 700; color: #22C55E; letter-spacing: .5px; }
        .file-count-badge {
            display: inline-block; margin-left: 4px; padding: 1px 6px;
            border-radius: 10px; font-size: 10px; font-weight: 700;
            background: rgba(34, 197, 94, 0.18); color: #22C55E; border: 1px solid rgba(34, 197, 94, 0.35);
            vertical-align: middle;
        }
        /* Menú de tres puntos */
        .service-menu { position: relative; display: inline-block; }
        .service-menu-trigger {
            width: 34px; height: 34px; border-radius: 8px; border: 1px solid rgba(34, 197, 94, 0.3);
            background: rgba(34, 197, 94, 0.06); color: var(--muted); cursor: pointer;
            display: flex; align-items: center; justify-content: center; transition: all .15s ease;
        }
        .service-menu-trigger svg { width: 18px; height: 18px; }
        .service-menu-trigger:hover { background: rgba(34, 197, 94, 0.16); color: var(--text); border-color: #22C55E; }
        .service-menu-trigger[aria-expanded="true"] { background: rgba(34, 197, 94, 0.2); color: var(--text); border-color: #22C55E; }
        .service-menu-dropdown {
            position: absolute; right: 0; top: calc(100% + 4px); z-index: 50;
            min-width: 140px; border-radius: 10px; overflow: hidden;
            background: #0b1a35; border: 1px solid rgba(34, 197, 94, 0.35);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.5); padding: 4px;
            display: none; flex-direction: column; gap: 2px;
        }
        .service-menu.open .service-menu-dropdown { display: flex; }
        .category-list .service-menu .service-menu-dropdown,
        .table-wrap .service-menu .service-menu-dropdown,
        .equipment-table .service-menu .service-menu-dropdown {
            position: fixed;
            top: var(--menu-top, 0);
            right: var(--menu-right, auto);
            left: auto;
            bottom: auto;
        }
        .service-menu-item {
            display: flex; align-items: center; gap: 10px; width: 100%; padding: 8px 10px;
            border: none; border-radius: 7px; background: transparent; color: var(--text);
            font-size: 13px; cursor: pointer; text-align: left; transition: all .12s ease;
        }
        .service-menu-item svg { width: 16px; height: 16px; flex: 0 0 auto; }
        .service-menu-item:hover { background: rgba(34, 197, 94, 0.14); color: var(--text); }
        .service-menu-item.danger { color: var(--danger); }
        .service-menu-item.danger:hover { background: rgba(239, 68, 68, 0.16); color: var(--danger); }
        .service-name { font-weight: 700; color: #fff; }
        .service-category { color: rgba(255, 255, 255, 0.7); font-size: 13px; }
        .service-dates { color: rgba(255, 255, 255, 0.7); font-size: 13px; white-space: nowrap; }
        .service-place { color: rgba(255, 255, 255, 0.7); font-size: 13px; }
        .service-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            white-space: nowrap;
        }
        .service-badge::before {
            content: "";
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: currentColor;
            box-shadow: 0 0 6px currentColor;
        }
        .service-badge.active   { color: #4ade80; background: rgba(74, 222, 128, 0.14); border: 1px solid rgba(74, 222, 128, 0.4); }
        .service-badge.upcoming { color: #22C55E; background: rgba(34, 197, 94, 0.14); border: 1px solid rgba(34, 197, 94, 0.4); }
        .service-badge.finished { color: rgba(255, 255, 255, 0.55); background: rgba(255, 255, 255, 0.06); border: 1px solid rgba(255, 255, 255, 0.18); }
        .service-actions { display: flex; align-items: center; gap: 8px; white-space: nowrap; }
        .service-action-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border-radius: 8px;
            border: 1px solid rgba(34, 197, 94, 0.35);
            background: rgba(8, 18, 40, 0.45);
            color: #22C55E;
            cursor: pointer;
            transition: background .16s ease, border-color .16s ease, box-shadow .16s ease;
        }
        .service-action-btn:hover {
            background: rgba(34, 197, 94, 0.14);
            border-color: #22C55E;
            box-shadow: 0 0 10px rgba(34, 197, 94, 0.35);
        }
        .service-action-btn svg { width: 16px; height: 16px; }
        .service-action-btn.danger { color: #ff4a4a; border-color: rgba(255, 74, 74, 0.35); }
        .service-action-btn.danger:hover { background: rgba(255, 74, 74, 0.14); border-color: #ff4a4a; box-shadow: 0 0 10px rgba(255, 74, 74, 0.35); }

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
        :root[data-theme="light"] .category-name { color: #0f172a; }
        :root[data-theme="light"] .category-meta { color: #334155; }
        :root[data-theme="light"] .category-arrow { color: #334155; }
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

        /* ===== Modo claro: tabla de servicios ===== */
        :root[data-theme="light"] .service-table thead th {
            color: var(--muted);
            border-bottom-color: rgba(15, 23, 42, 0.14);
            background: rgba(255, 255, 255, 0.96);
        }
        :root[data-theme="light"] .service-table tbody td {
            color: var(--text);
            border-bottom-color: rgba(15, 23, 42, 0.08);
        }
        :root[data-theme="light"] .service-table tbody tr:hover { background: rgba(15, 23, 42, 0.04); }
        :root[data-theme="light"] .service-thumb { border-color: rgba(15, 23, 42, 0.14); box-shadow: none; }
        :root[data-theme="light"] .service-thumb-placeholder {
            background: rgba(0, 122, 255, 0.08);
            border-color: rgba(0, 122, 255, 0.25);
            color: var(--primary);
        }
        :root[data-theme="light"] .service-thumb-file {
            background: rgba(0, 122, 255, 0.08);
            border-color: rgba(0, 122, 255, 0.25);
            color: var(--primary);
        }
        :root[data-theme="light"] .service-thumb-file .file-ext { color: var(--primary); }
        :root[data-theme="light"] .file-count-badge {
            background: rgba(0, 122, 255, 0.12); color: var(--primary); border-color: rgba(0, 122, 255, 0.25);
        }
        :root[data-theme="light"] .service-menu-trigger {
            background: rgba(15, 23, 42, 0.04); color: #334155; border-color: rgba(15, 23, 42, 0.14);
        }
        :root[data-theme="light"] .service-menu-trigger:hover { background: rgba(0, 122, 255, 0.1); color: #0f172a; border-color: var(--primary); }
        :root[data-theme="light"] .service-menu-dropdown {
            background: #fff; border-color: rgba(15, 23, 42, 0.14); box-shadow: 0 8px 24px rgba(15, 23, 42, 0.18);
        }
        :root[data-theme="light"] .service-menu-item { color: #0f172a; }
        :root[data-theme="light"] .service-menu-item:hover { background: rgba(15, 23, 42, 0.06); }
        :root[data-theme="light"] .service-menu-item.danger { color: var(--danger); }
        :root[data-theme="light"] .service-menu-item.danger:hover { background: var(--danger-soft); }
        :root[data-theme="light"] .service-name { color: var(--text); }
        :root[data-theme="light"] .service-category { color: var(--muted); }
        :root[data-theme="light"] .service-dates { color: var(--muted); }
        :root[data-theme="light"] .service-place { color: var(--muted); }
        :root[data-theme="light"] .service-badge.finished {
            color: var(--muted);
            background: rgba(15, 23, 42, 0.06);
            border-color: rgba(15, 23, 42, 0.14);
        }
        :root[data-theme="light"] .service-action-btn {
            background: rgba(15, 23, 42, 0.04);
            border-color: rgba(0, 122, 255, 0.25);
            color: var(--primary);
            box-shadow: none;
        }
        :root[data-theme="light"] .service-action-btn:hover {
            background: var(--primary-soft);
            border-color: var(--primary);
            box-shadow: 0 0 8px rgba(0, 122, 255, 0.18);
        }
        :root[data-theme="light"] .service-action-btn.danger { color: var(--danger); border-color: rgba(255, 74, 74, 0.25); }
        :root[data-theme="light"] .service-action-btn.danger:hover { background: var(--danger-soft); border-color: var(--danger); box-shadow: none; }

        @media (max-width: 1024px) {
            .catalog-grid { grid-template-columns: 1fr; }
        }
        @media (max-width: 640px) {
            .catalog-card { margin: 0; padding: 16px; }
            .catalog-header { flex-wrap: wrap; gap: 12px; }
            .catalog-header h2 { font-size: 20px; }
            .catalog-header-actions { width: 100%; justify-content: flex-end; }
            .category-list { max-height: 360px; }
            .service-table-wrap { max-height: 320px; }
            .service-table { min-width: 620px; }
            .service-table th, .service-table td { padding: 10px 8px; font-size: 12px; }
            .service-thumb { width: 38px; height: 38px; }
        }
        /* ===== Estilos de Tipos de Equipo ===== */
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 18px; }
        @media (max-width: 900px) { .stats-grid { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 560px) { .stats-grid { grid-template-columns: 1fr; } }
        .stat-card {
            display: flex;
            align-items: center;
            gap: 14px;
            background: rgba(8,18,40,0.82);
            border: 1px solid rgba(34,197,94,0.55);
            border-radius: 14px;
            padding: 16px;
            box-shadow: 0 8px 28px rgba(0,0,0,0.35), 0 0 14px rgba(34,197,94,0.2), inset 0 1px 0 rgba(255,255,255,0.04);
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
            border: 1px solid rgba(34,197,94,0.45);
            border-radius: 11px;
            background: rgba(8,18,40,0.55);
            color: #fff;
            font-size: 14px;
            outline: none;
            cursor: text;
        }
        .search-box .search-icon {
            position: absolute; left: 13px; top: 50%; transform: translateY(-50%);
            color: #22C55E; pointer-events: none;
        }
        .toolbar-btn {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 9px 14px;
            border: 1px solid rgba(34,197,94,0.45);
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
            border-bottom: 1px solid rgba(34,197,94,0.35);
            white-space: nowrap;
            background: rgba(8,18,40,0.96);
        }
        .equipment-table thead th:first-child { text-align: left; }
        .equipment-table tbody td {
            padding: 14px;
            border-bottom: 1px solid rgba(34,197,94,0.18);
            color: #fff;
            vertical-align: middle;
            text-align: center;
        }
        .equipment-table tbody tr { transition: background .16s ease; }
        .equipment-table tbody tr:hover { background: rgba(34,197,94,0.08); }
        .equipment-table tbody td:first-child { text-align: left; }

        .type-cell { display: flex; align-items: center; gap: 12px; }
        .type-icon {
            width: 42px; height: 42px;
            border-radius: 12px;
            background: rgba(34,197,94,0.12);
            color: #22C55E;
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
            background: rgba(34,197,94,0.15);
            color: #22C55E;
            border: 1px solid rgba(34,197,94,0.35);
        }
        .name-empty { color: rgba(255,255,255,0.4); font-size: 13px; }

        .actions { display: flex; align-items: center; justify-content: center; gap: 8px; }
        .action-btn {
            width: 34px; height: 34px;
            border-radius: 10px;
            border: 1px solid rgba(34,197,94,0.35);
            background: rgba(8,18,40,0.55);
            color: rgba(255,255,255,0.7);
            display: inline-flex; align-items: center; justify-content: center;
            cursor: not-allowed;
            opacity: 0.6;
            transition: all .16s ease;
        }
        .action-btn--edit:hover { color: #22C55E; border-color: #22C55E; }
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

        .page-title { color: #fff; }
        .page-subtitle { color: rgba(255,255,255,0.55); }
        :root[data-theme="light"] .page-title { color: var(--text); }
        :root[data-theme="light"] .page-subtitle { color: var(--muted); }
        :root[data-theme="light"] .search-box .search-icon { color: var(--primary); }
        :root[data-theme="light"] .type-icon { background: rgba(0,122,255,0.1); color: var(--primary); }
        :root[data-theme="light"] .empty-cell { color: var(--muted); }

        /* ===== Tarjetas de condición (tipo de mantenimiento) ===== */
        .condition-screen { display:flex; flex-direction:column; gap:18px; max-width:620px; margin:0 auto; }
        .condition-card {
            border:1px solid rgba(34,197,94,0.55);
            border-radius:14px;
            padding:18px;
            background:rgba(8,18,40,0.82);
            cursor:pointer;
            display:flex;
            align-items:center;
            gap:14px;
            transition:all .18s ease;
            box-shadow:0 8px 28px rgba(0,0,0,0.35), 0 0 14px rgba(34,197,94,0.2), inset 0 1px 0 rgba(255,255,255,0.04);
            backdrop-filter:blur(14px);
            -webkit-backdrop-filter:blur(14px);
        }
        .condition-card:hover { border-color:#22C55E; background:rgba(34,197,94,0.12); box-shadow:0 8px 32px rgba(0,0,0,0.45), 0 0 22px rgba(34,197,94,0.45), inset 0 1px 0 rgba(255,255,255,0.06); }
        .condition-card.selected { border-color:#22C55E; background:rgba(34,197,94,0.16); box-shadow:0 8px 32px rgba(0,0,0,0.45), 0 0 24px rgba(34,197,94,0.5), inset 0 0 0 1px rgba(34,197,94,0.6); }
        .condition-card .check { width:22px; height:22px; border:2px solid rgba(34,197,94,0.55); border-radius:6px; display:flex; align-items:center; justify-content:center; color:transparent; transition:all .18s ease; background:rgba(8,18,40,0.55); }
        .condition-card.selected .check { background:#22C55E; border-color:#22C55E; color:#fff; box-shadow:0 0 10px rgba(34,197,94,0.65); }
        .condition-card .info strong { font-size:15px; display:block; margin-bottom:2px; color:#fff; transition:color .18s ease; }
        .condition-card .info span { font-size:13px; color:rgba(255,255,255,0.55); transition:color .18s ease; }
        .condition-card--externo { border-color:rgba(245,158,11,0.55); box-shadow:0 8px 28px rgba(0,0,0,0.35), 0 0 14px rgba(245,158,11,0.2), inset 0 1px 0 rgba(255,255,255,0.04); }
        .condition-card--externo:hover { border-color:#f59e0b; background:rgba(245,158,11,0.14); box-shadow:0 8px 32px rgba(0,0,0,0.45), 0 0 22px rgba(245,158,11,0.45), inset 0 1px 0 rgba(255,255,255,0.06); }
        .condition-card--externo.selected { border-color:#f59e0b; background:rgba(245,158,11,0.22); box-shadow:0 8px 32px rgba(0,0,0,0.45), 0 0 26px rgba(245,158,11,0.55), inset 0 0 0 1px rgba(245,158,11,0.65); }
        .condition-card--externo .check { border-color:rgba(245,158,11,0.55); }
        .condition-card--externo.selected .check { background:#f59e0b; border-color:#f59e0b; color:#fff; box-shadow:0 0 12px rgba(245,158,11,0.75); }
        .condition-card--externo.selected .info strong { color:#f59e0b; text-shadow:0 0 12px rgba(245,158,11,0.55); }
        .condition-card--externo.selected .info span { color:rgba(245,158,11,0.85); }
        .condition-card--interno { border-color:rgba(168,85,247,0.55); box-shadow:0 8px 28px rgba(0,0,0,0.35), 0 0 14px rgba(168,85,247,0.2), inset 0 1px 0 rgba(255,255,255,0.04); }
        .condition-card--interno:hover { border-color:#a855f7; background:rgba(168,85,247,0.14); box-shadow:0 8px 32px rgba(0,0,0,0.45), 0 0 22px rgba(168,85,247,0.45), inset 0 1px 0 rgba(255,255,255,0.06); }
        .condition-card--interno.selected { border-color:#a855f7; background:rgba(168,85,247,0.22); box-shadow:0 8px 32px rgba(0,0,0,0.45), 0 0 26px rgba(168,85,247,0.55), inset 0 0 0 1px rgba(168,85,247,0.65); }
        .condition-card--interno .check { border-color:rgba(168,85,247,0.55); }
        .condition-card--interno.selected .check { background:#a855f7; border-color:#a855f7; color:#fff; box-shadow:0 0 12px rgba(168,85,247,0.75); }
        .condition-card--interno.selected .info strong { color:#a855f7; text-shadow:0 0 12px rgba(168,85,247,0.55); }
        .condition-card--interno.selected .info span { color:rgba(168,85,247,0.85); }
        :root[data-theme="light"] .condition-card { background:rgba(15,23,42,0.06); border-color:rgba(15,23,42,0.14); box-shadow:0 8px 28px rgba(15,23,42,0.08); }
        :root[data-theme="light"] .condition-card:hover { background:rgba(15,23,42,0.1); }
        :root[data-theme="light"] .condition-card.selected { background:rgba(15,23,42,0.14); }
        :root[data-theme="light"] .condition-card .info strong { color:var(--text); }
        :root[data-theme="light"] .condition-card .info span { color:var(--muted); }
        :root[data-theme="light"] .condition-card--externo { border-color:rgba(245,158,11,0.55); }
        :root[data-theme="light"] .condition-card--externo:hover { background:rgba(245,158,11,0.1); }
        :root[data-theme="light"] .condition-card--externo.selected { background:rgba(245,158,11,0.16); box-shadow:0 8px 28px rgba(245,158,11,0.18); }
        :root[data-theme="light"] .condition-card--externo.selected .info strong { color:#b45309; text-shadow:none; }
        :root[data-theme="light"] .condition-card--interno { border-color:rgba(168,85,247,0.55); }
        :root[data-theme="light"] .condition-card--interno:hover { background:rgba(168,85,247,0.1); }
        :root[data-theme="light"] .condition-card--interno.selected { background:rgba(168,85,247,0.16); box-shadow:0 8px 28px rgba(168,85,247,0.18); }
        :root[data-theme="light"] .condition-card--interno.selected .info strong { color:#7e22ce; text-shadow:none; }
    </style>

    @yield('service_content')
@endsection
