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
    .galeria-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 16px;
    }
    .galeria-card {
        flex: 1 1 240px;
        max-width: 320px;
        min-height: 150px;
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: 18px;
        padding: 18px;
        display: flex;
        flex-direction: column;
        gap: 10px;
        transition: transform .15s, box-shadow .15s, border-color .15s;
        text-decoration: none;
        color: inherit;
    }
    .galeria-card:hover {
        transform: translateY(-3px);
        border-color: rgba(10,132,255,.35);
        box-shadow: 0 10px 28px rgba(0,0,0,.22);
    }
    .galeria-meta {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }
    .galeria-tag {
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
    .galeria-status {
        display: inline-flex;
        width: fit-content;
        padding: 4px 9px;
        border-radius: 999px;
        background: rgba(10,132,255,.12);
        color: var(--primary);
        font-size: 10px;
        font-weight: 800;
        letter-spacing: 0.05em;
        text-transform: uppercase;
    }
    .galeria-title {
        font-size: 15px;
        font-weight: 800;
        line-height: 1.3;
    }
    .galeria-copy {
        color: var(--muted);
        font-size: 13px;
        line-height: 1.45;
        margin: 0;
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    .galeria-link {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        margin-top: auto;
        color: var(--primary);
        font-size: 13px;
        font-weight: 700;
    }
    .galeria-link span { color: var(--muted); font-weight: 600; }
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
        <h2 class="catalogo-title" style="font-size: 20px;">Galería de piezas</h2>
        <p class="catalogo-sub">Toca una tarjeta para ver el flyer en Canva / Drive.</p>

        @if($flyers->isEmpty())
            <div class="galeria-empty">
                Aún no hay flyers cargados. Cuando marques tareas como hechas con enlace de Canva o Drive, aparecerán aquí.
            </div>
        @else
            <div class="galeria-grid">
                @foreach($flyers as $flyer)
                    <a href="{{ route('marketing.biblioteca_catalogo.descargar_flyer', $flyer) }}" class="galeria-card" title="Descargar flyer">
                        <div class="galeria-meta">
                            @if($flyer->category)
                                <span class="galeria-tag">{{ $flyer->category }}</span>
                            @endif
                            <span class="galeria-status">Hecho</span>
                        </div>
                        <div class="galeria-title">{{ $flyer->title }}</div>
                        @if($flyer->description)
                            <p class="galeria-copy">{{ Str::limit($flyer->description, 140) }}</p>
                        @endif
                        <div class="galeria-link">Descargar / abrir <span>→</span></div>
                    </a>
                @endforeach
            </div>
        @endif
    </div>

    <div class="catalogo-foot">
        <div>grupomedibuy · Portal de Marketing</div>
        <div>grupomedibuy.com · WhatsApp 55 1867 / 722 448 5191</div>
    </div>
</div>
@endsection
