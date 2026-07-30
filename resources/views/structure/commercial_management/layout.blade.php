@extends('layouts.dashboard')

@section('title')
    @yield('title', 'Módulo Comercial')
@endsection

@section('content')
    <style>
        .dashboard-card {
            background: linear-gradient(145deg, #0A1228, #0D1730);
            border: 1px solid rgba(80, 120, 255, 0.22);
            border-radius: 18px;
            padding: 22px;
            box-shadow:
                0 12px 40px rgba(0, 0, 0, 0.45),
                0 0 0 1px rgba(0, 0, 0, 0.3),
                inset 0 1px 0 rgba(255, 255, 255, 0.04);
            backdrop-filter: blur(14px);
        }

        .header-title {
            margin: 0;
            font-size: 24px;
            font-weight: 700;
            color: #fff;
        }

        .header-subtitle {
            margin: 4px 0 0;
            color: rgba(255, 255, 255, 0.55);
            font-size: 14px;
        }

        .header-icon {
            width: 54px;
            height: 54px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #7c3aed, #3b82f6, #06b6d4);
            box-shadow:
                0 0 20px rgba(124, 58, 237, 0.75),
                0 0 40px rgba(59, 130, 246, 0.45),
                0 0 60px rgba(6, 182, 212, 0.2),
                inset 0 0 14px rgba(255, 255, 255, 0.25);
        }

        .total-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(8, 18, 40, 0.82);
            border: 1px solid rgba(80, 120, 255, 0.22);
            color: #fff;
            font-size: 12px;
            font-weight: 500;
            padding: 4px 10px;
            border-radius: 20px;
            margin-left: 12px;
        }

        .btn-glass {
            background: rgba(8, 18, 40, 0.45);
            border: 1px solid rgba(80, 130, 220, 0.22);
            color: #fff;
            padding: 10px 16px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s ease;
        }

        .btn-glass:hover {
            background: rgba(8, 18, 40, 0.7);
            border-color: #3B82F6;
            box-shadow: 0 0 12px rgba(59, 130, 246, 0.25);
        }

        .btn-gradient {
            background: linear-gradient(135deg, #00A8FF, #7C3AED);
            color: #fff;
            border: 1px solid rgba(255, 255, 255, 0.15);
            padding: 10px 18px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow:
                0 0 12px rgba(59, 130, 246, 0.35),
                0 0 30px rgba(124, 58, 237, 0.2);
            transition: all 0.2s ease;
        }

        .btn-gradient:hover {
            transform: translateY(-1px);
            box-shadow:
                0 0 18px rgba(59, 130, 246, 0.55),
                0 0 40px rgba(124, 58, 237, 0.35);
        }

        .filter-input, .filter-select {
            background: linear-gradient(145deg, #071024, #0B142B);
            border: 1px solid rgba(80, 130, 220, 0.22);
            border-radius: 12px;
            color: #fff;
            padding: 12px 14px;
            font-size: 14px;
            outline: none;
        }

        .filter-input:focus, .filter-select:focus {
            border-color: #B026FF;
            box-shadow:
                0 0 8px rgba(176, 38, 255, 0.65),
                0 0 24px rgba(176, 38, 255, 0.25);
        }

        .filter-input::placeholder {
            color: rgba(255, 255, 255, 0.4);
        }

        .filter-select {
            appearance: none;
            cursor: pointer;
        }

        .search-box {
            position: relative;
            border-radius: 12px;
            padding: 1px;
            background: linear-gradient(135deg, rgba(0, 168, 255, 0.45), rgba(124, 58, 237, 0.45));
            box-shadow: 0 0 10px rgba(0, 168, 255, 0.08);
            transition: all 0.2s ease;
        }

        .search-box:focus-within {
            background: linear-gradient(135deg, rgba(0, 168, 255, 0.8), rgba(124, 58, 237, 0.8));
            box-shadow:
                0 0 12px rgba(0, 168, 255, 0.3),
                0 0 28px rgba(124, 58, 237, 0.2);
        }

        .search-box .search-icon {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #00A8FF;
            pointer-events: none;
            filter: drop-shadow(0 0 4px rgba(0, 168, 255, 0.4));
        }

        .search-input {
            width: 100%;
            padding: 12px 42px 12px 14px;
            border: none;
            border-radius: 11px;
            background: linear-gradient(145deg, #071024, #0B142B);
            color: #fff;
            font-size: 14px;
            outline: none;
        }

        .search-input::placeholder {
            color: rgba(255, 255, 255, 0.45);
        }

        .filter-select-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .filter-select-wrapper .filter-select {
            width: 100%;
            padding-left: 38px;
            padding-right: 34px;
        }

        .filter-select-wrapper .select-left-icon {
            position: absolute;
            left: 12px;
            color: #00A8FF;
            pointer-events: none;
            filter: drop-shadow(0 0 3px rgba(0, 168, 255, 0.35));
        }

        .filter-select-wrapper .select-chevron {
            position: absolute;
            right: 10px;
            color: rgba(255, 255, 255, 0.55);
            pointer-events: none;
        }

        .stat-card {
            border-radius: 18px;
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 16px;
            color: #fff;
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(80, 120, 255, 0.22);
            background: rgba(8, 18, 40, 0.55);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.35);
            backdrop-filter: blur(14px);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            inset: 0;
            background: radial-gradient(circle at 80% 20%, rgba(255, 255, 255, 0.05), transparent 45%);
        }

        .stat-card.cyan   { background: linear-gradient(145deg, rgba(0, 229, 255, 0.08), rgba(8, 18, 40, 0.55)); border: 1px solid rgba(0, 229, 255, 0.55); box-shadow: 0 0 30px rgba(0, 229, 255, 0.28); }
        .stat-card.green  { background: linear-gradient(145deg, rgba(16, 185, 129, 0.08), rgba(8, 18, 40, 0.55)); border: 1px solid rgba(16, 185, 129, 0.55); box-shadow: 0 0 30px rgba(16, 185, 129, 0.28); }
        .stat-card.violet { background: linear-gradient(145deg, rgba(124, 58, 237, 0.1), rgba(8, 18, 40, 0.55)); border: 1px solid rgba(124, 58, 237, 0.65); box-shadow: 0 0 30px rgba(124, 58, 237, 0.33); }
        .stat-card.magenta{ background: linear-gradient(145deg, rgba(225, 0, 255, 0.09), rgba(8, 18, 40, 0.55)); border: 1px solid rgba(225, 0, 255, 0.6); box-shadow: 0 0 30px rgba(225, 0, 255, 0.3); }
        .stat-card.amber  { background: linear-gradient(145deg, rgba(249, 115, 22, 0.08), rgba(8, 18, 40, 0.55)); border: 1px solid rgba(249, 115, 22, 0.55); box-shadow: 0 0 30px rgba(249, 115, 22, 0.28); }

        .stat-card.cyan   .stat-icon { background: rgba(0, 229, 255, 0.2); color: #00E5FF; box-shadow: 0 0 14px rgba(0, 229, 255, 0.35); }
        .stat-card.green  .stat-icon { background: rgba(16, 185, 129, 0.2); color: #34d399; box-shadow: 0 0 14px rgba(16, 185, 129, 0.35); }
        .stat-card.violet .stat-icon { background: rgba(124, 58, 237, 0.2); color: #a78bfa; box-shadow: 0 0 14px rgba(124, 58, 237, 0.4); }
        .stat-card.magenta .stat-icon { background: rgba(225, 0, 255, 0.2); color: #e879f9; box-shadow: 0 0 14px rgba(225, 0, 255, 0.38); }
        .stat-card.amber  .stat-icon { background: rgba(249, 115, 22, 0.2); color: #fbbf24; box-shadow: 0 0 14px rgba(249, 115, 22, 0.35); }

        .stat-icon {
            width: 52px;
            height: 52px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            position: relative;
            z-index: 1;
        }

        .stat-value {
            font-size: 26px;
            font-weight: 700;
            line-height: 1;
        }

        .stat-label {
            font-size: 13px;
            color: rgba(255, 255, 255, 0.65);
            margin-top: 4px;
        }

        .stat-sublabel {
            font-size: 12px;
            margin-top: 3px;
        }

        .stat-card.cyan   .stat-sublabel { color: #67e8f9; }
        .stat-card.green  .stat-sublabel { color: #6ee7b7; }
        .stat-card.violet .stat-sublabel { color: #c4b5fd; }
        .stat-card.magenta .stat-sublabel { color: #f0abfc; }
        .stat-card.amber  .stat-sublabel { color: #fcd34d; }

        .customers-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
            color: #fff;
        }

        .customers-table th {
            padding: 14px 12px;
            font-weight: 600;
            color: #67e8f9;
            border-bottom: 1px solid rgba(0, 229, 255, 0.45);
            box-shadow:
                0 1px 0 rgba(255, 255, 255, 0.05),
                0 2px 14px rgba(0, 229, 255, 0.12);
            text-align: left;
            cursor: pointer;
            user-select: none;
        }

        .customers-table td {
            padding: 14px 12px;
            border-bottom: 1px solid rgba(80, 120, 255, 0.1);
            vertical-align: middle;
        }

        .customers-table tr:hover td {
            background: rgba(255, 255, 255, 0.02);
        }

        .customer-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            font-weight: 700;
            color: #fff;
            margin-right: 10px;
            text-transform: uppercase;
            border: 2px solid rgba(255, 255, 255, 0.35);
            box-shadow: 0 0 0 1px rgba(255, 255, 255, 0.1), 0 0 12px currentColor;
        }

        .customer-name {
            color: #fff;
            font-weight: 500;
        }

        .customer-meta {
            color: rgba(255, 255, 255, 0.55);
            font-size: 12px;
            margin-top: 2px;
        }

        .contact-icon {
            color: #fff;
            margin-right: 6px;
            vertical-align: middle;
        }

        .promotion-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            background: rgba(16, 185, 129, 0.12);
            border: 1px solid rgba(16, 185, 129, 0.35);
            color: #34d399;
        }

        .promotion-badge.inactive {
            background: rgba(239, 68, 68, 0.12);
            border: 1px solid rgba(239, 68, 68, 0.35);
            color: #f87171;
        }

        .action-btn {
            width: 34px;
            height: 34px;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid rgba(80, 130, 220, 0.22);
            background: rgba(8, 18, 40, 0.4);
            color: rgba(255, 255, 255, 0.8);
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .action-btn:hover {
            background: rgba(8, 18, 40, 0.7);
            border-color: #3B82F6;
            color: #fff;
            box-shadow: 0 0 12px rgba(59, 130, 246, 0.25);
        }

        .action-btn.view:hover {
            background: rgba(124, 58, 237, 0.15);
            border-color: #7c3aed;
            color: #a78bfa;
            box-shadow: 0 0 12px rgba(124, 58, 237, 0.3);
        }

        .pagination {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .pagination a, .pagination span {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            font-size: 14px;
            color: rgba(255, 255, 255, 0.7);
            background: linear-gradient(145deg, #0A1228, #0D1730);
            border: 1px solid rgba(80, 130, 220, 0.22);
            transition: all 0.2s ease;
        }

        .pagination a:hover, .pagination span.current {
            background: linear-gradient(135deg, #00A8FF, #7C3AED);
            color: #fff;
            border-color: rgba(255, 255, 255, 0.25);
            box-shadow: 0 0 12px rgba(59, 130, 246, 0.35);
        }

        .page-size-select {
            background: linear-gradient(145deg, #071024, #0B142B);
            border: 1px solid rgba(80, 130, 220, 0.22);
            border-radius: 10px;
            color: #fff;
            padding: 8px 12px;
            font-size: 13px;
        }
    /* Tema claro: usa el atributo data-theme que ya maneja el dashboard */
    html[data-theme="light"] .dashboard-card {
        background: linear-gradient(145deg, #ffffff, #f1f5f9);
        border: 1px solid rgba(0, 0, 0, 0.08);
        box-shadow: 0 12px 40px rgba(0, 0, 0, 0.08);
        color: #111827;
    }

    html[data-theme="light"] .header-title { color: #111827; }
    html[data-theme="light"] .header-subtitle { color: #6b7280; }

    html[data-theme="light"] .total-badge {
        background: #f8fafc;
        border: 1px solid rgba(0, 0, 0, 0.1);
        color: #111827;
    }

    html[data-theme="light"] .btn-glass {
        background: rgba(0, 0, 0, 0.04);
        border-color: rgba(0, 0, 0, 0.12);
        color: #111827;
    }
    html[data-theme="light"] .btn-glass:hover {
        background: rgba(0, 0, 0, 0.08);
        border-color: #3B82F6;
        box-shadow: 0 0 12px rgba(59, 130, 246, 0.15);
    }

    html[data-theme="light"] .btn-gradient {
        color: #fff;
        box-shadow: 0 0 12px rgba(59, 130, 246, 0.25), 0 0 30px rgba(124, 58, 237, 0.12);
    }

    html[data-theme="light"] .search-box { background: linear-gradient(135deg, rgba(0, 168, 255, 0.25), rgba(124, 58, 237, 0.25)); }
    html[data-theme="light"] .search-input {
        background: #fff;
        color: #111827;
    }
    html[data-theme="light"] .search-input::placeholder { color: #9ca3af; }

    html[data-theme="light"] .filter-input,
    html[data-theme="light"] .filter-select,
    html[data-theme="light"] .page-size-select {
        background: #fff;
        border-color: rgba(80, 130, 220, 0.25);
        color: #111827;
    }
    html[data-theme="light"] .filter-input::placeholder { color: #9ca3af; }
    html[data-theme="light"] .filter-input:focus,
    html[data-theme="light"] .filter-select:focus,
    html[data-theme="light"] .page-size-select:focus { border-color: #B026FF; }

    html[data-theme="light"] .filter-select-wrapper .select-left-icon { color: #00A8FF; }
    html[data-theme="light"] .filter-select-wrapper .select-chevron { color: #6b7280; }

    html[data-theme="light"] .stat-card {
        background: linear-gradient(145deg, rgba(255, 255, 255, 0.9), rgba(241, 245, 249, 0.95));
        border-color: rgba(80, 120, 255, 0.2);
    }
    html[data-theme="light"] .stat-card.cyan   { background: linear-gradient(145deg, rgba(0, 229, 255, 0.08), rgba(255, 255, 255, 0.9)); border-color: rgba(0, 229, 255, 0.45); }
    html[data-theme="light"] .stat-card.green  { background: linear-gradient(145deg, rgba(16, 185, 129, 0.08), rgba(255, 255, 255, 0.9)); border-color: rgba(16, 185, 129, 0.45); }
    html[data-theme="light"] .stat-card.violet { background: linear-gradient(145deg, rgba(124, 58, 237, 0.1), rgba(255, 255, 255, 0.9)); border-color: rgba(124, 58, 237, 0.55); }
    html[data-theme="light"] .stat-card.magenta{ background: linear-gradient(145deg, rgba(225, 0, 255, 0.08), rgba(255, 255, 255, 0.9)); border-color: rgba(225, 0, 255, 0.5); }
    html[data-theme="light"] .stat-card.amber  { background: linear-gradient(145deg, rgba(249, 115, 22, 0.08), rgba(255, 255, 255, 0.9)); border-color: rgba(249, 115, 22, 0.45); }
    html[data-theme="light"] .stat-label,
    html[data-theme="light"] .stat-value { color: #111827; }

    html[data-theme="light"] .customers-table { color: #111827; }
    html[data-theme="light"] .customers-table th {
        color: #0e7490;
        border-bottom-color: rgba(6, 182, 212, 0.35);
        box-shadow: 0 1px 0 rgba(0, 0, 0, 0.04), 0 2px 14px rgba(6, 182, 212, 0.08);
    }
    html[data-theme="light"] .customers-table td { border-bottom-color: rgba(0, 0, 0, 0.06); }
    html[data-theme="light"] .customers-table tr:hover td { background: rgba(0, 0, 0, 0.02); }

    html[data-theme="light"] .customer-name { color: #111827; }
    html[data-theme="light"] .customer-meta { color: #6b7280; }
    html[data-theme="light"] .customer-avatar { border-color: rgba(0, 0, 0, 0.12); }
    html[data-theme="light"] .contact-icon { color: #6b7280; }

    html[data-theme="light"] .promotion-badge { color: #047857; background: rgba(16, 185, 129, 0.1); border-color: rgba(16, 185, 129, 0.35); }
    html[data-theme="light"] .promotion-badge.inactive { color: #b91c1c; background: rgba(239, 68, 68, 0.1); border-color: rgba(239, 68, 68, 0.35); }

    html[data-theme="light"] .action-btn {
        background: rgba(0, 0, 0, 0.04);
        border-color: rgba(0, 0, 0, 0.12);
        color: #4b5563;
    }
    html[data-theme="light"] .action-btn:hover { color: #111827; }

    html[data-theme="light"] .pagination a,
    html[data-theme="light"] .pagination span {
        background: linear-gradient(145deg, #ffffff, #f1f5f9);
        border-color: rgba(80, 130, 220, 0.25);
        color: #4b5563;
    }
    </style>

    @yield('commercial_content')
@endsection
