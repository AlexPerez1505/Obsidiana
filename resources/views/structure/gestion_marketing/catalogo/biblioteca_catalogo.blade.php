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
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 16px;
    }
    .galeria-card {
        width: 100%;
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
    .galeria-image-wrap {
        width: 100%;
        aspect-ratio: 4 / 3;
        background: var(--surface-2);
        border-radius: 12px;
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .galeria-image {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
        cursor: zoom-in;
    }
    .galeria-actions {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 8px;
        margin-top: auto;
    }
    .galeria-promo, .galeria-download {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 8px 12px;
        border-radius: 8px;
        border: none;
        font-size: 12px;
        font-weight: 700;
        cursor: pointer;
        text-decoration: none;
    }
    .galeria-promo { background: var(--primary); color: #fff; }
    .galeria-promo:hover { background: var(--primary-strong); }
    .galeria-download { background: var(--surface-2); color: var(--text); border: 1px solid var(--border); }
    .galeria-download:hover { background: var(--surface); }
    .lightbox {
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,.72);
        display: none;
        align-items: center;
        justify-content: center;
        padding: 40px;
        z-index: 1000;
    }
    .lightbox.is-open { display: flex; }
    .lightbox-card {
        position: relative;
        background: #fff;
        border-radius: 18px;
        padding: 12px;
        max-width: 90vw;
        max-height: 90vh;
        display: flex;
        flex-direction: column;
        align-items: center;
        box-shadow: 0 20px 60px rgba(0,0,0,.45);
    }
    .lightbox-card img {
        max-width: 80vw;
        max-height: 76vh;
        border-radius: 12px;
        object-fit: contain;
    }
    .lightbox-close {
        position: absolute;
        top: -18px;
        right: -18px;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: #fff;
        border: none;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 28px;
        line-height: 1;
        color: #333;
        cursor: pointer;
        box-shadow: 0 4px 12px rgba(0,0,0,.25);
    }
    .lightbox-close:hover { background: #f2f2f2; }
    .lightbox-counter {
        position: absolute;
        bottom: -44px;
        left: 50%;
        transform: translateX(-50%);
        background: rgba(0,0,0,.6);
        color: #fff;
        padding: 7px 16px;
        border-radius: 999px;
        font-size: 13px;
        font-weight: 700;
    }
    .lightbox-nav {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        width: 44px;
        height: 44px;
        border-radius: 50%;
        background: rgba(0,0,0,.6);
        color: #fff;
        border: none;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 26px;
        line-height: 1;
        cursor: pointer;
        transition: background .15s;
    }
    .lightbox-nav:hover { background: rgba(0,0,0,.85); }
    .lightbox-prev { left: -22px; }
    .lightbox-next { right: -22px; }
    .galeria-image-empty {
        color: var(--muted);
        font-size: 13px;
        font-weight: 600;
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
        <h2 class="catalogo-title" style="font-size: 20px;">Galería de piezas</h2>
        <p class="catalogo-sub">Toca una tarjeta para ver la imagen.</p>

        @if($flyers->isEmpty())
            <div class="galeria-empty">
                Aún no hay piezas aprobadas con imagen. Cuando una tarea con imagen se marque como aprobada, aparecerá aquí.
            </div>
        @else
            <div class="galeria-grid">
                @php $imageIndex = 0; @endphp
                @foreach($flyers as $flyer)
                    <div class="galeria-card">
                        <div class="galeria-image-wrap">
                            @if($flyer->project_image)
                                <img src="{{ asset('storage/' . $flyer->project_image) }}" alt="{{ $flyer->title }}" class="galeria-image" onclick="openLightbox({{ $imageIndex }})">
                                @php $imageIndex++; @endphp
                            @else
                                <div class="galeria-image-empty">Sin imagen</div>
                            @endif
                        </div>
                        <div class="galeria-title">{{ $flyer->title }}</div>
                        <div class="galeria-actions">
                            <button type="button" class="galeria-promo" onclick="alert('El apartado de promociones aún no está creado.')">Mandar a promociones</button>
                            <a href="{{ route('marketing.biblioteca_catalogo.descargar_flyer', $flyer) }}" class="galeria-download" target="_blank" rel="noopener">Descargar</a>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <div class="catalogo-foot">
        <div>grupomedibuy · Portal de Marketing</div>
        <div>grupomedibuy.com · WhatsApp 55 1867 / 722 448 5191</div>
    </div>
</div>

@php
    $lightboxImages = $flyers->filter(fn($f) => !empty($f->project_image))->values()->map(fn($f) => asset('storage/' . $f->project_image));
@endphp

<div class="lightbox" id="lightbox">
    <div class="lightbox-card" onclick="event.stopPropagation()">
        <button type="button" class="lightbox-close" onclick="closeLightbox()">&times;</button>
        <img id="lightboxImage" src="" alt="Imagen ampliada">
        <div class="lightbox-counter" id="lightboxCounter">1 / 1</div>
        <button type="button" class="lightbox-nav lightbox-prev" onclick="navLightbox(-1)">&#8249;</button>
        <button type="button" class="lightbox-nav lightbox-next" onclick="navLightbox(1)">&#8250;</button>
    </div>
</div>

<script>
    const lightboxImages = @json($lightboxImages);
    let currentImage = 0;

    function openLightbox(index) {
        currentImage = index;
        updateLightbox();
        document.getElementById('lightbox').classList.add('is-open');
    }

    function updateLightbox() {
        const img = lightboxImages[currentImage] || '';
        document.getElementById('lightboxImage').src = img;
        document.getElementById('lightboxCounter').textContent = (currentImage + 1) + ' / ' + lightboxImages.length;
    }

    function closeLightbox() {
        document.getElementById('lightbox').classList.remove('is-open');
    }

    function navLightbox(dir) {
        if (lightboxImages.length <= 1) return;
        currentImage = (currentImage + dir + lightboxImages.length) % lightboxImages.length;
        updateLightbox();
    }

    document.getElementById('lightbox').addEventListener('click', function(e) {
        if (e.target.id === 'lightbox') closeLightbox();
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeLightbox();
        if (e.key === 'ArrowLeft') navLightbox(-1);
        if (e.key === 'ArrowRight') navLightbox(1);
    });
</script>
@endsection
