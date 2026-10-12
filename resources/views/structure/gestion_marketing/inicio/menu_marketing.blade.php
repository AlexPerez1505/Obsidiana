@extends('layouts.dashboard')
@section('title', 'Inicio · Marketing')

@section('content')
<style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Open+Sans:wght@600;700;800&display=swap');

    .hero-card {
        margin-bottom: 24px;
        background: linear-gradient(135deg, var(--surface) 0%, var(--surface-2) 100%);
    }
    .hero-text {
        max-width: 780px;
    }
    .eyebrow {
        display: inline-block;
        padding: 5px 12px;
        border-radius: 999px;
        background: var(--primary-soft);
        color: var(--primary);
        font-size: 11px;
        font-weight: 800;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        margin-bottom: 14px;
    }
    .hero-text h1 {
        margin: 0;
        font-size: 34px;
        line-height: 1.15;
        letter-spacing: -0.02em;
        font-weight: 800;
    }
    .hero-text p {
        margin: 14px 0 0;
        font-size: 15.5px;
        line-height: 1.6;
        max-width: 680px;
        color: var(--muted);
    }
    .stat-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 16px;
        margin-top: 28px;
    }
    .stat-card {
        padding: 18px 20px;
        display: flex;
        align-items: center;
        gap: 14px;
    }
    .stat-number {
        width: 42px;
        height: 42px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        font-weight: 800;
        flex: 0 0 auto;
    }
    .stat-label {
        font-size: 13px;
        color: var(--muted);
        font-weight: 500;
    }

    .quick-section { margin-bottom: 24px; }
    .section-tag {
        display: inline-block;
        padding: 6px 12px;
        border-radius: 20px;
        background: var(--surface-2);
        color: var(--muted);
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        margin-bottom: 10px;
    }
    .quick-section h2 {
        margin: 0 0 18px;
        font-size: 22px;
        font-weight: 700;
    }
    .quick-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
        gap: 18px;
    }
    .quick-card {
        padding: 20px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
        transition: transform .15s, box-shadow .15s;
        text-decoration: none;
        color: inherit;
    }
    .quick-card:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow);
    }
    .quick-icon {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        background: var(--green);
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 auto;
    }
    .quick-title {
        font-size: 15.5px;
        font-weight: 700;
        color: var(--text);
    }
    .quick-desc {
        font-size: 13px;
        color: var(--muted);
        margin-top: 2px;
    }

    .brand-section { margin-bottom: 24px; }
    .brand-section h2 {
        margin: 0;
        font-size: 22px;
        font-weight: 700;
    }
    .brand-intro {
        margin: 10px 0 22px;
        color: var(--muted);
        font-size: 15px;
        line-height: 1.55;
        max-width: 720px;
    }
    .palette-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 18px;
    }
    .color-card {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: 18px;
        overflow: hidden;
        box-shadow: var(--shadow);
    }
    .swatch {
        position: relative;
        height: 120px;
        display: flex;
        align-items: flex-end;
        padding: 16px;
    }
    .copy-btn {
        font-size: 10px;
        font-weight: 800;
        letter-spacing: 0.04em;
        padding: 7px 12px;
        border-radius: 8px;
        border: none;
        cursor: pointer;
        background: rgba(255,255,255,0.22);
        color: #fff;
        backdrop-filter: blur(4px);
        transition: background .15s;
    }
    .copy-btn:hover { background: rgba(255,255,255,0.35); }
    .color-meta {
        padding: 16px;
        display: flex;
        flex-direction: column;
        gap: 6px;
    }
    .fonts-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 18px;
        margin-top: 22px;
    }
    .font-card {
        display: flex;
        flex-direction: column;
        gap: 14px;
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: 18px;
        padding: 28px 24px 24px;
        color: var(--text);
    }
    .font-name {
        font-size: 34px;
        font-weight: 700;
        line-height: 1.15;
        letter-spacing: -0.03em;
    }
    .font-sample { font-size: 15px; color: var(--text); letter-spacing: .01em; }
    .font-usage {
        font-size: 11px;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: var(--primary);
        font-weight: 700;
    }
    .font-desc {
        font-size: 13px;
        line-height: 1.55;
        color: var(--muted);
    }
    .tone-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 18px;
        margin-top: 22px;
    }
    .tone-card {
        padding: 22px;
        border-radius: 14px;
    }
    .tone-card.do { background: #f0fdf4; border: 1px solid #bbf7d0; }
    .tone-card.dont { background: #fef2f2; border: 1px solid #fecaca; }
    [data-theme="dark"] .tone-card.do { background: rgba(22,163,74,0.10); border-color: rgba(74,222,128,0.25); }
    [data-theme="dark"] .tone-card.dont { background: rgba(220,38,38,0.10); border-color: rgba(248,113,113,0.25); }
    .tone-title {
        margin: 0 0 14px;
        font-size: 16px;
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .tone-card ul { list-style: none; padding: 0; margin: 0; }
    .tone-card li {
        padding-left: 20px;
        position: relative;
        margin-bottom: 10px;
        font-size: 14px;
        line-height: 1.45;
    }
    .tone-card li::before {
        content: "";
        position: absolute;
        left: 0;
        top: 7px;
        width: 7px;
        height: 7px;
        border-radius: 50%;
    }
    .tone-card.do li::before { background: var(--green); }
    .tone-card.dont li::before { background: var(--danger); }

    .legal {
        margin-top: 22px;
        padding: 18px 20px;
        border-radius: 14px;
        background: var(--surface-2);
        border: 1px solid var(--border);
    }
    .legal strong {
        display: block;
        font-size: 15px;
        margin-bottom: 6px;
    }
    .legal p {
        margin: 0;
        font-size: 14px;
        color: var(--muted);
        line-height: 1.5;
    }
    .btn-primary {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 20px;
        border: none;
        border-radius: 12px;
        background: var(--primary);
        color: #fff;
        font-weight: 700;
        font-size: 14px;
        text-decoration: none;
        cursor: pointer;
        transition: background .15s;
    }
    .btn-primary:hover { background: var(--primary-strong); }
</style>

<div class="card hero-card">
    <div class="hero-text">
        <span class="eyebrow">Portal de Marketing · Grupo MediBuy</span>
        <h1>Todo el marketing de MediBuy, <span style="color:var(--green);">en un solo lugar.</span></h1>
        <p>
            Guía de marca, calendario de contenido, aprobación de flyers y la biblioteca de
            productos y recursos. Un panel central para que el equipo trabaje rápido,
            consistente y sin errores antes de publicar.
        </p>
    </div>

    <div class="stat-row">
        <div class="card stat-card">
            <div class="stat-number" style="background:var(--accent-soft); color:var(--accent);">{{ $inicioStats['cambios_solicitados'] }}</div>
            <div class="stat-label">Cambios solicitados</div>
        </div>
        <div class="card stat-card">
            <div class="stat-number" style="background:var(--primary-soft); color:var(--primary);">{{ $inicioStats['en_revision'] }}</div>
            <div class="stat-label">En revisión</div>
        </div>
        <div class="card stat-card">
            <div class="stat-number" style="background:var(--accent-soft); color:var(--accent);">{{ $inicioStats['pendiente_por_tomar'] }}</div>
            <div class="stat-label">Pendiente por tomar</div>
        </div>
        <div class="card stat-card">
            <div class="stat-number" style="background:var(--green-soft); color:var(--green);">{{ $inicioStats['areas_especializadas'] }}</div>
            <div class="stat-label">Áreas especializadas</div>
        </div>
    </div>
</div>

<div class="quick-section">
    <span class="section-tag">Accesos rápidos</span>
    <h2>¿Qué quieres hacer hoy?</h2>

    <div class="quick-grid">
        <a href="{{ route('marketing.aprobacion_flyers.index') }}" class="card quick-card">
            <div style="display:flex; align-items:center; gap:14px;">
                <div class="quick-icon">
                    <x-gravityui-file-text width="22" height="22" />
                </div>
                <div>
                    <div class="quick-title">Revisar flyers pendientes</div>
                    <div class="quick-desc">Sin piezas en revisión por ahora.</div>
                </div>
            </div>
            <x-gravityui-arrow-chevron-right width="18" height="18" style="color:var(--muted);" />
        </a>

        <a href="{{ route('marketing.calendario.index') }}" class="card quick-card">
            <div style="display:flex; align-items:center; gap:14px;">
                <div class="quick-icon">
                    <x-gravityui-calendar width="22" height="22" />
                </div>
                <div>
                    <div class="quick-title">Ver calendario de la semana</div>
                    <div class="quick-desc">Qué se publica y en qué red social.</div>
                </div>
            </div>
            <x-gravityui-arrow-chevron-right width="18" height="18" style="color:var(--muted);" />
        </a>

        <a href="{{ route('marketing.guia_de_marca.index') }}" class="card quick-card">
            <div style="display:flex; align-items:center; gap:14px;">
                <div class="quick-icon">
                    <x-gravityui-globe width="22" height="22" />
                </div>
                <div>
                    <div class="quick-title">Consultar la guía de marca</div>
                    <div class="quick-desc">Colores, tipografías y reglas legales.</div>
                </div>
            </div>
            <x-gravityui-arrow-chevron-right width="18" height="18" style="color:var(--muted);" />
        </a>

        <a href="{{ route('marketing.biblioteca_catalogo.index') }}" class="card quick-card">
            <div style="display:flex; align-items:center; gap:14px;">
                <div class="quick-icon">
                    <x-gravityui-book width="22" height="22" />
                </div>
                <div>
                    <div class="quick-title">Abrir biblioteca y catálogos</div>
                    <div class="quick-desc">Productos por área y recursos descargables.</div>
                </div>
            </div>
            <x-gravityui-arrow-chevron-right width="18" height="18" style="color:var(--muted);" />
        </a>
    </div>
</div>

            

</script>
@endsection
