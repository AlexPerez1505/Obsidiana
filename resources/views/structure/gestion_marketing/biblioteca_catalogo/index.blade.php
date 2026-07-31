@extends('layouts.dashboard')

@section('title', 'Biblioteca & catálogo · Marketing')
@section('page-title', 'Biblioteca & catálogo')
@section('page-sub', 'Áreas y su equipo destacado')

@php
    $areas = [
        ['name' => 'Artroscopía', 'brand' => 'Stryker'],
        ['name' => 'Urología', 'brand' => 'Richard Wolf'],
        ['name' => 'Ginecología', 'brand' => 'Midmark'],
        ['name' => 'Otorrinolaringología', 'brand' => 'Medtronic'],
        ['name' => 'Quirófano', 'brand' => 'Olympus'],
        ['name' => 'Hospitalización', 'brand' => 'Stryker'],
    ];
@endphp

@section('content')
    <style>
        :root {
            --bc-bg: #070c17;
            --bc-surface: #0f1a30;
            --bc-surface-2: #111c36;
            --bc-surface-3: #162444;
            --bc-border: rgba(90, 140, 230, 0.18);
            --bc-text: #e8eef8;
            --bc-muted: #93a4bd;
            --bc-primary: #0a84ff;
            --bc-emerald: #10b981;
            --bc-violet: #8b5cf6;
        }

        [data-theme="light"] .bib-catalogo {
            --bc-bg: #f6f7f9;
            --bc-surface: #ffffff;
            --bc-surface-2: #f7f8fa;
            --bc-surface-3: #eef2f7;
            --bc-border: #e2e8f0;
            --bc-text: #1e293b;
            --bc-muted: #64748b;
        }

        .bib-catalogo { color: var(--bc-text); font-size: 14px; }

        .bc-breadcrumb { display: flex; align-items: center; gap: 8px; font-size: 12px; color: var(--bc-muted); margin-bottom: 22px; text-transform: uppercase; letter-spacing: 0.06em; font-weight: 700; }
        .bc-breadcrumb a { color: var(--bc-muted); text-decoration: none; transition: color 0.15s ease; }
        .bc-breadcrumb a:hover { color: var(--bc-primary); }
        .bc-breadcrumb svg { width: 14px; height: 14px; opacity: 0.7; }

        .bc-pill { display: inline-flex; align-items: center; gap: 6px; padding: 5px 12px; border-radius: 20px; background: rgba(16, 185, 129, 0.10); color: var(--bc-emerald); border: 1px solid var(--bc-border); font-size: 11px; font-weight: 800; letter-spacing: 0.07em; text-transform: uppercase; margin-bottom: 12px; }

        .bc-title { font-size: 34px; font-weight: 800; margin: 0 0 8px; letter-spacing: -0.02em; }
        .bc-subtitle { color: var(--bc-muted); font-size: 15px; line-height: 1.6; margin: 0; max-width: 680px; }

        .bc-header { margin-bottom: 30px; }

        /* Carrusel */
        .bc-carousel-wrap { position: relative; margin-bottom: 44px; }
        .bc-carousel { display: flex; gap: 16px; overflow-x: auto; scroll-behavior: smooth; padding-bottom: 8px; scroll-snap-type: x mandatory; }
        .bc-carousel::-webkit-scrollbar { height: 8px; }
        .bc-carousel::-webkit-scrollbar-track { background: transparent; }
        .bc-carousel::-webkit-scrollbar-thumb { background: var(--bc-border); border-radius: 4px; }

        .bc-area {
            flex: 0 0 220px;
            scroll-snap-align: start;
            background: linear-gradient(145deg, rgba(15, 26, 48, 0.95), rgba(17, 28, 54, 0.90));
            border: 1px solid var(--bc-border);
            border-radius: 18px;
            padding: 20px;
            display: flex;
            flex-direction: column;
            gap: 10px;
            transition: transform 0.15s ease, box-shadow 0.15s ease;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.16);
        }

        .bc-area:hover { transform: translateY(-3px); box-shadow: 0 12px 28px rgba(0, 0, 0, 0.24); }

        .bc-area__tag {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #fff;
            background: var(--bc-emerald);
            width: fit-content;
        }

        .bc-area__title { font-size: 17px; font-weight: 800; margin: 0; }
        .bc-area__brand { font-size: 12px; color: var(--bc-muted); margin: 0; }

        .bc-area__link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-top: auto;
            padding: 9px 14px;
            border-radius: 10px;
            background: rgba(10, 132, 255, 0.10);
            color: var(--bc-primary);
            font-size: 13px;
            font-weight: 700;
            text-decoration: none;
            transition: background 0.15s ease;
        }

        .bc-area__link:hover { background: rgba(10, 132, 255, 0.18); }
        .bc-area__link svg { width: 14px; height: 14px; }

        .bc-arrow {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: 36px;
            height: 36px;
            border-radius: 50%;
            border: 1px solid var(--bc-border);
            background: var(--bc-surface);
            color: var(--bc-text);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            z-index: 10;
            transition: background 0.15s ease;
        }

        .bc-arrow:hover { background: var(--bc-surface-2); }
        .bc-arrow.left { left: -18px; }
        .bc-arrow.right { right: -18px; }
        .bc-arrow svg { width: 18px; height: 18px; }

        @media (max-width: 900px) {
            .bc-arrow { display: none; }
        }

        /* Galería */
        .bc-gallery {}

        .bc-gallery__label {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 12px;
            border-radius: 20px;
            background: rgba(139, 92, 246, 0.10);
            color: var(--bc-violet);
            border: 1px solid var(--bc-border);
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.07em;
            text-transform: uppercase;
            margin-bottom: 14px;
        }

        .bc-gallery__title { font-size: 24px; font-weight: 800; margin: 0 0 10px; }
        .bc-gallery__sub { color: var(--bc-muted); margin: 0 0 20px; font-size: 14px; }

        .bc-gallery__empty {
            border: 1px dashed var(--bc-border);
            border-radius: 16px;
            padding: 40px 24px;
            text-align: center;
            color: var(--bc-muted);
            font-size: 14px;
        }

        .bc-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid var(--bc-border);
            font-size: 12px;
            color: var(--bc-muted);
        }
    </style>

    <div class="bib-catalogo">
        <div class="bc-breadcrumb">
            <a href="{{ route('dashboard') }}">Inicio</a>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
            <span>Marketing</span>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
            <span>Biblioteca & catálogo</span>
        </div>

        <div class="bc-header">
            <div class="bc-pill">Catálogo</div>
            <h1 class="bc-title">Biblioteca & catálogo</h1>
            <p class="bc-subtitle">Áreas y su equipo destacado.</p>
        </div>

        <div class="bc-carousel-wrap">
            <button class="bc-arrow left" type="button" onclick="scrollCarousel(-1)" aria-label="Anterior">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
            </button>

            <div class="bc-carousel" id="bcCarousel">
                @foreach ($areas as $area)
                    <div class="bc-area">
                        <span class="bc-area__tag">{{ $area['name'] }}</span>
                        <h3 class="bc-area__title">{{ $area['brand'] }}</h3>
                        <p class="bc-area__brand">{{ $area['brand'] }}</p>
                        <a class="bc-area__link" href="https://grupomedibuy.com/flipbook/catalogo-de-artroscopia/" target="_blank" rel="noopener">
                            Ver
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
                            Catálogo
                        </a>
                    </div>
                @endforeach
            </div>

            <button class="bc-arrow right" type="button" onclick="scrollCarousel(1)" aria-label="Siguiente">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
            </button>
        </div>

        <div class="bc-gallery">
            <div class="bc-gallery__label">Contenido en motion</div>
            <h2 class="bc-gallery__title">Galería de piezas</h2>
            <p class="bc-gallery__sub">Toca una imagen para ver su copy.</p>

            <div class="bc-gallery__empty">
                Aún no hay imágenes cargadas. Cuando subas flyers o las piezas, aparecerán aquí.
            </div>
        </div>

        <div class="bc-footer">
            <span>Grupo MediBuy · Portal de Marketing</span>
            <span>grupomedibuy.com · WhatsApp 55 1667 / 722 448 5181</span>
        </div>
    </div>

    <script>
        function scrollCarousel(direction) {
            const c = document.getElementById('bcCarousel');
            c.scrollBy({ left: direction * 260, behavior: 'smooth' });
        }
    </script>
@endsection
