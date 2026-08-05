@extends('layouts.dashboard')

@section('title', 'Biblioteca & catálogo')
@section('page-title', 'Biblioteca & catálogo')

@section('content')
<style>
    .catalogo-wrap { max-width: 1200px; margin: 0 auto; }
    .catalogo-section { margin-bottom: 42px; }
    .catalogo-eyebrow {
        display: inline-block;
        padding: 5px 12px;
        border-radius: 999px;
        border: 1px solid var(--border);
        color: var(--green);
        font-size: 10px;
        font-weight: 800;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        margin-bottom: 14px;
    }
    .catalogo-title {
        margin: 0 0 6px;
        font-size: 26px;
        font-weight: 800;
        letter-spacing: -0.02em;
    }
    .catalogo-sub {
        color: var(--muted);
        font-size: 15px;
        margin: 0 0 24px;
    }
    .catalogo-grid {
        display: flex;
        flex-wrap: nowrap;
        gap: 16px;
        overflow-x: auto;
        padding-bottom: 18px;
        scrollbar-width: thin;
        scrollbar-color: rgba(147,164,189,.35) rgba(147,164,189,.10);
    }
    .catalogo-grid::-webkit-scrollbar { height: 8px; }
    .catalogo-grid::-webkit-scrollbar-track {
        background: rgba(147,164,189,.10);
        border-radius: 999px;
    }
    .catalogo-grid::-webkit-scrollbar-thumb {
        background: rgba(147,164,189,.35);
        border-radius: 999px;
    }
    .catalogo-card {
        flex: 0 0 170px;
        min-height: 150px;
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: 18px;
        padding: 18px;
        display: flex;
        flex-direction: column;
        gap: 12px;
        transition: transform .15s, box-shadow .15s, border-color .15s;
        text-decoration: none;
        color: inherit;
    }
    .catalogo-card:hover {
        transform: translateY(-3px);
        border-color: rgba(10,132,255,.35);
        box-shadow: 0 10px 28px rgba(0,0,0,.22);
    }
    .catalogo-tag {
        display: inline-flex;
        width: fit-content;
        padding: 4px 9px;
        border-radius: 999px;
        background: var(--green-soft);
        color: var(--green);
        font-size: 10px;
        font-weight: 800;
        letter-spacing: 0.05em;
        text-transform: uppercase;
    }
    .catalogo-brand {
        font-size: 13px;
        font-weight: 700;
        letter-spacing: 0.04em;
        color: var(--primary);
        text-transform: uppercase;
    }
    .catalogo-link {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        margin-top: auto;
        color: var(--primary);
        font-size: 13px;
        font-weight: 700;
    }
    .catalogo-link span { color: var(--muted); font-weight: 600; }
    .galeria-empty {
        border: 1px dashed var(--border);
        border-radius: 18px;
        padding: 36px 24px;
        text-align: center;
        color: var(--muted);
        font-size: 14px;
    }
    .catalogo-foot {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        margin-top: 60px;
        padding-top: 22px;
        border-top: 1px solid var(--border);
        font-size: 12px;
        color: var(--muted);
    }
</style>

<div class="catalogo-wrap">
    <div class="catalogo-section">
        <div class="catalogo-eyebrow">Catálogo</div>
        <h1 class="catalogo-title">Biblioteca & catálogo</h1>
        <p class="catalogo-sub">Áreas y su equipo destacado.</p>

        <div class="catalogo-grid">
            <a href="https://grupomedibuy.com/flipbook/catalogo-de-endoscopia/" target="_blank" rel="noopener" class="catalogo-card">
                <div class="catalogo-tag">Endoscopía</div>
                <div class="catalogo-brand">Olympus</div>
                <div class="catalogo-link">Ver → <span>Catálogo</span></div>
            </a>

            <a href="https://grupomedibuy.com/" target="_blank" rel="noopener" class="catalogo-card">
                <div class="catalogo-tag">Laparoscopía</div>
                <div class="catalogo-brand">Stryker</div>
                <div class="catalogo-link">Ver → <span>Catálogo</span></div>
            </a>

            <a href="https://grupomedibuy.com/artroscopia/" target="_blank" rel="noopener" class="catalogo-card">
                <div class="catalogo-tag">Artroscopía</div>
                <div class="catalogo-brand">Stryker</div>
                <div class="catalogo-link">Ver → <span>Catálogo</span></div>
            </a>

            <a href="https://grupomedibuy.com/artroscopia/" target="_blank" rel="noopener" class="catalogo-card">
                <div class="catalogo-tag">Urología</div>
                <div class="catalogo-brand">Richard Wolf</div>
                <div class="catalogo-link">Ver → <span>Catálogo</span></div>
            </a>

            <a href="https://grupomedibuy.com/flipbook/catalogo-de-ginecologia/" target="_blank" rel="noopener" class="catalogo-card">
                <div class="catalogo-tag">Ginecología</div>
                <div class="catalogo-brand">Midmark</div>
                <div class="catalogo-link">Ver → <span>Catálogo</span></div>
            </a>

            <a href="https://grupomedibuy.com/otorrinolaringologia/" target="_blank" rel="noopener" class="catalogo-card">
                <div class="catalogo-tag">Otorrinolaringología</div>
                <div class="catalogo-brand">Medtronic</div>
                <div class="catalogo-link">Ver → <span>Catálogo</span></div>
            </a>

            <a href="https://grupomedibuy.com/flipbook/catalogo-de-quirofano/" target="_blank" rel="noopener" class="catalogo-card">
                <div class="catalogo-tag">Quirófano</div>
                <div class="catalogo-brand">Olympus</div>
                <div class="catalogo-link">Ver → <span>Catálogo</span></div>
            </a>

            <a href="https://grupomedibuy.com/hospitalizacion/" target="_blank" rel="noopener" class="catalogo-card">
                <div class="catalogo-tag">Hospitalización</div>
                <div class="catalogo-brand">Stryker</div>
                <div class="catalogo-link">Ver → <span>Catálogo</span></div>
            </a>
        </div>
    </div>

    <div class="catalogo-section">
        <div class="catalogo-eyebrow" style="color: var(--muted); border-color: var(--border);">Contenido en Notion</div>
        <h2 class="catalogo-title" style="font-size: 20px;">Galería de piezas</h2>
        <p class="catalogo-sub">Toca una imagen para ver su copy.</p>

        <div class="galeria-empty">
            Aún no hay imágenes cargadas. Cuando subas flyers a las piezas, aparecerán aquí.
        </div>
    </div>

    <div class="catalogo-foot">
        <div>grupomedibuy · Portal de Marketing</div>
        <div>grupomedibuy.com · WhatsApp 55 1867 / 722 448 5191</div>
    </div>
</div>
@endsection
