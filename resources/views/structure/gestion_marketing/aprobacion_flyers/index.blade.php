@extends('layouts.dashboard')

@section('title', 'Aprobación de flyers · Marketing')
@section('page-title', 'Aprobación de flyers')
@section('page-sub', 'Revisión y autorización de piezas gráficas antes de publicar')

@section('content')
    <style>
        :root {
            --fly-bg: #070c17;
            --fly-surface: #0f1a30;
            --fly-surface-2: #111c36;
            --fly-surface-3: #162444;
            --fly-border: rgba(90, 140, 230, 0.18);
            --fly-text: #e8eef8;
            --fly-muted: #93a4bd;
            --fly-primary: #0a84ff;
            --fly-amber: #f59e0b;
            --fly-blue: #3b82f6;
            --fly-violet: #8b5cf6;
            --fly-emerald: #10b981;
            --fly-rose: #f43f5e;
            --fly-cyan: #06b6d4;
        }

        [data-theme="light"] .fly-approval {
            --fly-bg: #f6f7f9;
            --fly-surface: #ffffff;
            --fly-surface-2: #f7f8fa;
            --fly-surface-3: #eef2f7;
            --fly-border: #e2e8f0;
            --fly-text: #1e293b;
            --fly-muted: #64748b;
        }

        .fly-approval {
            color: var(--fly-text);
            font-size: 14px;
            padding-bottom: 40px;
        }

        .fly-breadcrumb {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 12px;
            color: var(--fly-muted);
            margin-bottom: 22px;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            font-weight: 700;
        }

        .fly-breadcrumb a {
            color: var(--fly-muted);
            text-decoration: none;
            transition: color 0.15s ease;
        }

        .fly-breadcrumb a:hover { color: var(--fly-primary); }

        .fly-breadcrumb svg {
            width: 14px;
            height: 14px;
            color: var(--fly-muted);
            opacity: 0.7;
        }

        /* Regla de oro */
        .fly-rule {
            display: flex;
            align-items: flex-start;
            gap: 16px;
            background: linear-gradient(135deg, rgba(10, 132, 255, 0.10), rgba(124, 58, 237, 0.08));
            border: 1px solid var(--fly-border);
            border-left: 4px solid var(--fly-primary);
            border-radius: 16px;
            padding: 18px 22px;
            margin-bottom: 30px;
            box-shadow: 0 8px 26px rgba(0, 0, 0, 0.18);
        }

        .fly-rule__icon {
            flex: 0 0 44px;
            width: 44px;
            height: 44px;
            border-radius: 12px;
            background: rgba(10, 132, 255, 0.14);
            color: var(--fly-primary);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .fly-rule__icon svg { width: 22px; height: 22px; }

        .fly-rule__title {
            font-size: 13px;
            font-weight: 800;
            margin: 0 0 4px;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            color: var(--fly-primary);
        }

        .fly-rule__text {
            margin: 0;
            color: var(--fly-muted);
            line-height: 1.6;
            font-size: 14px;
        }

        .fly-rule__text strong {
            color: var(--fly-emerald);
            font-weight: 700;
        }

        /* Header */
        .fly-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 26px;
            flex-wrap: wrap;
        }

        .fly-header__main { flex: 1; min-width: 280px; }

        .fly-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 12px;
            border-radius: 20px;
            background: rgba(10, 132, 255, 0.10);
            color: var(--fly-primary);
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.07em;
            text-transform: uppercase;
            margin-bottom: 12px;
        }

        .fly-pill::before {
            content: '';
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: var(--fly-primary);
        }

        .fly-title {
            font-size: 34px;
            font-weight: 800;
            margin: 0 0 8px;
            letter-spacing: -0.02em;
        }

        .fly-subtitle {
            color: var(--fly-muted);
            font-size: 15px;
            line-height: 1.6;
            margin: 0;
            max-width: 680px;
        }

        .fly-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 22px;
            border-radius: 14px;
            background: linear-gradient(135deg, #0a84ff, #7c3aed);
            color: #fff;
            font-weight: 700;
            font-size: 14px;
            text-decoration: none;
            border: none;
            cursor: pointer;
            box-shadow: 0 8px 24px rgba(10, 132, 255, 0.35);
            transition: transform 0.15s ease, box-shadow 0.15s ease;
            white-space: nowrap;
        }

        .fly-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 32px rgba(10, 132, 255, 0.45);
        }

        .fly-btn svg { width: 18px; height: 18px; }

        /* Stats */
        .fly-stats {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 16px;
            margin-bottom: 30px;
        }

        @media (max-width: 1024px) { .fly-stats { grid-template-columns: repeat(3, 1fr); } }
        @media (max-width: 640px) { .fly-stats { grid-template-columns: repeat(2, 1fr); } }

        .fly-stat {
            background: linear-gradient(145deg, rgba(15, 26, 48, 0.95), rgba(17, 28, 54, 0.90));
            border: 1px solid var(--fly-border);
            border-radius: 18px;
            padding: 20px;
            position: relative;
            overflow: hidden;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.16);
        }

        .fly-stat:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 28px rgba(0, 0, 0, 0.26);
        }

        .fly-stat::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--stat-color, var(--fly-primary)), rgba(255, 255, 255, 0.20));
            box-shadow: 0 0 16px var(--stat-color, var(--fly-primary));
        }

        .fly-stat[data-color="amber"] { --stat-color: var(--fly-amber); }
        .fly-stat[data-color="blue"] { --stat-color: var(--fly-blue); }
        .fly-stat[data-color="rose"] { --stat-color: var(--fly-rose); }
        .fly-stat[data-color="emerald"] { --stat-color: var(--fly-emerald); }
        .fly-stat[data-color="cyan"] { --stat-color: var(--fly-cyan); }

        .fly-stat__value {
            font-size: 30px;
            font-weight: 800;
            margin: 0;
        }

        .fly-stat__label {
            font-size: 12px;
            color: var(--fly-muted);
            margin-top: 4px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-weight: 700;
        }

        /* Kanban */
        .fly-kanban {
            display: flex;
            gap: 18px;
            overflow-x: auto;
            padding-bottom: 12px;
            margin-bottom: 44px;
            scroll-snap-type: x mandatory;
        }

        .fly-kanban::-webkit-scrollbar { height: 8px; }
        .fly-kanban::-webkit-scrollbar-track { background: transparent; }
        .fly-kanban::-webkit-scrollbar-thumb {
            background: var(--fly-border);
            border-radius: 4px;
        }

        .fly-column {
            flex: 0 0 290px;
            min-width: 290px;
            scroll-snap-align: start;
            background: linear-gradient(145deg, rgba(15, 26, 48, 0.92), rgba(17, 28, 54, 0.88));
            border: 1px solid var(--fly-border);
            border-radius: 20px;
            padding: 18px;
            display: flex;
            flex-direction: column;
            gap: 14px;
            box-shadow: 0 8px 26px rgba(0, 0, 0, 0.18);
        }

        .fly-column__head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-bottom: 12px;
            border-bottom: 1px solid var(--fly-border);
        }

        .fly-column__title {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            font-weight: 800;
            margin: 0;
        }

        .fly-column__dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            box-shadow: 0 0 8px currentColor;
        }

        .fly-column__count {
            font-size: 12px;
            font-weight: 800;
            padding: 3px 10px;
            border-radius: 20px;
            background: var(--fly-surface-3);
            color: var(--fly-muted);
        }

        .fly-column__cards {
            display: flex;
            flex-direction: column;
            gap: 12px;
            min-height: 120px;
        }

        .fly-card {
            background: var(--fly-surface-2);
            border: 1px solid var(--fly-border);
            border-radius: 16px;
            padding: 14px;
            transition: transform 0.15s ease, box-shadow 0.15s ease;
            cursor: pointer;
        }

        .fly-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 26px rgba(0, 0, 0, 0.22);
        }

        .fly-card__thumb {
            width: 100%;
            aspect-ratio: 4 / 3;
            border-radius: 12px;
            background: linear-gradient(135deg, rgba(10, 132, 255, 0.15), rgba(124, 58, 237, 0.15));
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--fly-muted);
            margin-bottom: 12px;
            overflow: hidden;
        }

        .fly-card__thumb svg { width: 42px; height: 42px; opacity: 0.5; }

        .fly-card__title {
            font-size: 14px;
            font-weight: 700;
            margin: 0 0 4px;
        }

        .fly-card__meta {
            font-size: 12px;
            color: var(--fly-muted);
            margin: 0 0 10px;
        }

        .fly-card__actions {
            display: flex;
            gap: 8px;
        }

        .fly-card__btn {
            flex: 1;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 4px;
            padding: 8px 10px;
            border-radius: 10px;
            border: 1px solid var(--fly-border);
            background: var(--fly-surface-3);
            color: var(--fly-text);
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            transition: background 0.15s ease;
        }

        .fly-card__btn:hover { background: var(--fly-surface); }

        .fly-card__btn--primary {
            background: linear-gradient(135deg, #0a84ff, #7c3aed);
            border-color: transparent;
            color: #fff;
        }

        .fly-card__btn--primary:hover { opacity: 0.92; }

        .fly-empty {
            text-align: center;
            padding: 28px 12px;
            color: var(--fly-muted);
            font-size: 13px;
            border: 1px dashed var(--fly-border);
            border-radius: 14px;
        }

        /* Secciones */
        .fly-section {
            margin-bottom: 46px;
        }

        .fly-section__label {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 12px;
            border-radius: 20px;
            background: rgba(10, 132, 255, 0.10);
            color: var(--fly-primary);
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.07em;
            text-transform: uppercase;
            margin-bottom: 14px;
        }

        .fly-section__title {
            font-size: 24px;
            font-weight: 800;
            margin: 0 0 20px;
            letter-spacing: -0.01em;
        }

        /* Checklist */
        .fly-checklist {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 14px;
        }

        @media (max-width: 1024px) { .fly-checklist { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 640px) { .fly-checklist { grid-template-columns: 1fr; } }

        .fly-check {
            display: flex;
            align-items: flex-start;
            gap: 14px;
            background: linear-gradient(145deg, rgba(15, 26, 48, 0.95), rgba(17, 28, 54, 0.90));
            border: 1px solid var(--fly-border);
            border-radius: 16px;
            padding: 16px;
            cursor: pointer;
            transition: transform 0.15s ease, box-shadow 0.15s ease, border-color 0.15s ease;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.14);
        }

        .fly-check:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 26px rgba(0, 0, 0, 0.22);
        }

        .fly-check__box {
            flex: 0 0 24px;
            width: 24px;
            height: 24px;
            border-radius: 8px;
            border: 2px solid var(--fly-border);
            background: var(--fly-surface-2);
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.15s ease;
            margin-top: 2px;
        }

        .fly-check__box svg {
            width: 14px;
            height: 14px;
            color: #fff;
            opacity: 0;
            transform: scale(0.6);
            transition: all 0.15s ease;
        }

        .fly-check.checked .fly-check__box {
            background: var(--fly-emerald);
            border-color: var(--fly-emerald);
        }

        .fly-check.checked .fly-check__box svg {
            opacity: 1;
            transform: scale(1);
        }

        .fly-check__text {
            margin: 0;
            font-size: 14px;
            line-height: 1.45;
            font-weight: 600;
            transition: color 0.15s ease;
        }

        .fly-check.checked .fly-check__text {
            color: var(--fly-muted);
            text-decoration: line-through;
        }

        /* Flow */
        .fly-flow {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 14px;
            position: relative;
        }

        @media (max-width: 1100px) { .fly-flow { grid-template-columns: repeat(3, 1fr); } }
        @media (max-width: 640px) { .fly-flow { grid-template-columns: 1fr; } }

        .fly-step {
            background: linear-gradient(145deg, rgba(15, 26, 48, 0.95), rgba(17, 28, 54, 0.90));
            border: 1px solid var(--fly-border);
            border-radius: 18px;
            padding: 18px;
            position: relative;
            transition: transform 0.15s ease, box-shadow 0.15s ease;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.14);
        }

        .fly-step:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 26px rgba(0, 0, 0, 0.22);
        }

        .fly-step__num {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: linear-gradient(135deg, #0a84ff, #7c3aed);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            font-weight: 800;
            margin-bottom: 14px;
            box-shadow: 0 6px 16px rgba(10, 132, 255, 0.35);
        }

        .fly-step__title {
            font-size: 15px;
            font-weight: 800;
            margin: 0 0 6px;
        }

        .fly-step__desc {
            color: var(--fly-muted);
            margin: 0;
            font-size: 13px;
            line-height: 1.5;
        }
    </style>

    <div class="fly-approval">
        <div class="fly-breadcrumb">
            <a href="{{ route('dashboard') }}">Inicio</a>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
            <span>Marketing</span>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
            <span>Aprobación de flyers</span>
        </div>

        <div class="fly-rule">
            <div class="fly-rule__icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
            </div>
            <div>
                <div class="fly-rule__title">Regla de oro</div>
                <p class="fly-rule__text">
                    Ningún flyer se publica hasta que un revisor autorizado lo marque como <strong>Autorizado</strong> en el tablero.
                </p>
            </div>
        </div>

        <div class="fly-header">
            <div class="fly-header__main">
                <div class="fly-pill">Tablero</div>
                <h1 class="fly-title">Aprobación de flyers</h1>
                <p class="fly-subtitle">
                    Estado actual de las piezas en proceso. Flujo: Diseño sube → revisor valida specs y copy → autoriza o pide cambios → se publica.
                </p>
            </div>
            <button class="fly-btn" onclick="alert('Aquí se abrirá el formulario para subir un nuevo flyer.')" type="button">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Subir flyer
            </button>
        </div>

        <div class="fly-stats">
            <div class="fly-stat" data-color="amber">
                <div class="fly-stat__value">2</div>
                <div class="fly-stat__label">Pendientes</div>
            </div>
            <div class="fly-stat" data-color="blue">
                <div class="fly-stat__value">1</div>
                <div class="fly-stat__label">En revisión</div>
            </div>
            <div class="fly-stat" data-color="rose">
                <div class="fly-stat__value">0</div>
                <div class="fly-stat__label">Cambios solicitados</div>
            </div>
            <div class="fly-stat" data-color="emerald">
                <div class="fly-stat__value">0</div>
                <div class="fly-stat__label">Autorizados</div>
            </div>
            <div class="fly-stat" data-color="cyan">
                <div class="fly-stat__value">0</div>
                <div class="fly-stat__label">Publicados</div>
            </div>
        </div>

        <div class="fly-kanban">
            <div class="fly-column">
                <div class="fly-column__head">
                    <div class="fly-column__title">
                        <span class="fly-column__dot" style="color: var(--fly-amber); background: var(--fly-amber);"></span>
                        Pendiente
                    </div>
                    <span class="fly-column__count">2</span>
                </div>
                <div class="fly-column__cards">
                    <div class="fly-card">
                        <div class="fly-card__thumb">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                        </div>
                        <div class="fly-card__title">Promo marzo - insulina</div>
                        <p class="fly-card__meta">Diseño · hace 2 horas</p>
                        <div class="fly-card__actions">
                            <button class="fly-card__btn" type="button">Ver</button>
                            <button class="fly-card__btn fly-card__btn--primary" type="button">Revisar</button>
                        </div>
                    </div>
                    <div class="fly-card">
                        <div class="fly-card__thumb">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                        </div>
                        <div class="fly-card__title">Lanzamiento nuevo equipo</div>
                        <p class="fly-card__meta">Diseño · ayer</p>
                        <div class="fly-card__actions">
                            <button class="fly-card__btn" type="button">Ver</button>
                            <button class="fly-card__btn fly-card__btn--primary" type="button">Revisar</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="fly-column">
                <div class="fly-column__head">
                    <div class="fly-column__title">
                        <span class="fly-column__dot" style="color: var(--fly-blue); background: var(--fly-blue);"></span>
                        En revisión
                    </div>
                    <span class="fly-column__count">1</span>
                </div>
                <div class="fly-column__cards">
                    <div class="fly-card">
                        <div class="fly-card__thumb">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                        </div>
                        <div class="fly-card__title">Carrusel capacitación</div>
                        <p class="fly-card__meta">Revisor: Jennifer · hoy</p>
                        <div class="fly-card__actions">
                            <button class="fly-card__btn" type="button">Ver</button>
                            <button class="fly-card__btn fly-card__btn--primary" type="button">Decidir</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="fly-column">
                <div class="fly-column__head">
                    <div class="fly-column__title">
                        <span class="fly-column__dot" style="color: var(--fly-rose); background: var(--fly-rose);"></span>
                        Cambios solicitados
                    </div>
                    <span class="fly-column__count">0</span>
                </div>
                <div class="fly-column__cards">
                    <div class="fly-empty">Sin piezas en esta etapa.</div>
                </div>
            </div>

            <div class="fly-column">
                <div class="fly-column__head">
                    <div class="fly-column__title">
                        <span class="fly-column__dot" style="color: var(--fly-emerald); background: var(--fly-emerald);"></span>
                        Autorizado
                    </div>
                    <span class="fly-column__count">0</span>
                </div>
                <div class="fly-column__cards">
                    <div class="fly-empty">Sin piezas autorizadas aún.</div>
                </div>
            </div>

            <div class="fly-column">
                <div class="fly-column__head">
                    <div class="fly-column__title">
                        <span class="fly-column__dot" style="color: var(--fly-cyan); background: var(--fly-cyan);"></span>
                        Publicado
                    </div>
                    <span class="fly-column__count">0</span>
                </div>
                <div class="fly-column__cards">
                    <div class="fly-empty">Sin piezas publicadas aún.</div>
                </div>
            </div>
        </div>

        <div class="fly-section">
            <div class="fly-section__label">Antes de autorizar</div>
            <h2 class="fly-section__title">Checklist de revisión</h2>

            <div class="fly-checklist">
                <div class="fly-check" onclick="this.classList.toggle('checked')">
                    <div class="fly-check__box">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                    </div>
                    <p class="fly-check__text">Nombre y modelo del equipo correctos</p>
                </div>
                <div class="fly-check" onclick="this.classList.toggle('checked')">
                    <div class="fly-check__box">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                    </div>
                    <p class="fly-check__text">Especificaciones verificables contra el fabricante</p>
                </div>
                <div class="fly-check" onclick="this.classList.toggle('checked')">
                    <div class="fly-check__box">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                    </div>
                    <p class="fly-check__text">Marca / logotipo correctos</p>
                </div>
                <div class="fly-check" onclick="this.classList.toggle('checked')">
                    <div class="fly-check__box">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                    </div>
                    <p class="fly-check__text">Precio correcto (o sin precio, según política)</p>
                </div>
                <div class="fly-check" onclick="this.classList.toggle('checked')">
                    <div class="fly-check__box">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                    </div>
                    <p class="fly-check__text">Ortografía y redacción del copy revisadas</p>
                </div>
                <div class="fly-check" onclick="this.classList.toggle('checked')">
                    <div class="fly-check__box">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                    </div>
                    <p class="fly-check__text">Datos de contacto correctos (tel, web, redes)</p>
                </div>
                <div class="fly-check" onclick="this.classList.toggle('checked')">
                    <div class="fly-check__box">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                    </div>
                    <p class="fly-check__text">Sin claims médicos o regulatorios indebidos</p>
                </div>
                <div class="fly-check" onclick="this.classList.toggle('checked')">
                    <div class="fly-check__box">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                    </div>
                    <p class="fly-check__text">Tamaño/formato correcto para la red destino</p>
                </div>
                <div class="fly-check" onclick="this.classList.toggle('checked')">
                    <div class="fly-check__box">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                    </div>
                    <p class="fly-check__text">Imagen nítida, sin artefactos ni texto cortado</p>
                </div>
            </div>
        </div>

        <div class="fly-section">
            <div class="fly-section__label">Cómo funciona</div>
            <h2 class="fly-section__title">Flujo de aprobación</h2>

            <div class="fly-flow">
                <div class="fly-step">
                    <div class="fly-step__num">1</div>
                    <div class="fly-step__title">Diseño sube</div>
                    <p class="fly-step__desc">Crea tarjeta con imagen, copy y red. Estado: Pendiente.</p>
                </div>
                <div class="fly-step">
                    <div class="fly-step__num">2</div>
                    <div class="fly-step__title">Avisa</div>
                    <p class="fly-step__desc">Pega el link en el grupo de WhatsApp de aprobación.</p>
                </div>
                <div class="fly-step">
                    <div class="fly-step__num">3</div>
                    <div class="fly-step__title">En revisión</div>
                    <p class="fly-step__desc">El revisor aplica el checklist completo.</p>
                </div>
                <div class="fly-step">
                    <div class="fly-step__num">4</div>
                    <div class="fly-step__title">Decide</div>
                    <p class="fly-step__desc">Autorizado con nombre y fecha, o Cambios solicitados.</p>
                </div>
                <div class="fly-step">
                    <div class="fly-step__num">5</div>
                    <div class="fly-step__title">Corrige</div>
                    <p class="fly-step__desc">Si hay cambios, regresa a Diseño y se repite.</p>
                </div>
                <div class="fly-step">
                    <div class="fly-step__num">6</div>
                    <div class="fly-step__title">Publicado</div>
                    <p class="fly-step__desc">Una vez en redes, se marca como Publicado.</p>
                </div>
            </div>
        </div>
    </div>
@endsection
