@extends('layouts.dashboard')

@section('title', 'Guía de marca · Marketing')
@section('page-title', 'Guía de marca')
@section('page-sub', 'La base visual de todo lo que sale de MediBuy')

@section('content')
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700;800&family=Inter:wght@400;500;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --gm-bg: #070c17;
            --gm-surface: #0f1a30;
            --gm-surface-2: #111c36;
            --gm-surface-3: #162444;
            --gm-border: rgba(90, 140, 230, 0.18);
            --gm-text: #e8eef8;
            --gm-muted: #93a4bd;
            --gm-primary: #0a84ff;
            --gm-emerald: #10b981;
            --gm-rose: #f43f5e;
            --gm-violet: #8b5cf6;
        }

        [data-theme="light"] .guia-de-marca {
            --gm-bg: #f6f7f9;
            --gm-surface: #ffffff;
            --gm-surface-2: #f7f8fa;
            --gm-surface-3: #eef2f7;
            --gm-border: #e2e8f0;
            --gm-text: #1e293b;
            --gm-muted: #64748b;
        }

        .guia-de-marca { color: var(--gm-text); font-size: 14px; }

        .gm-breadcrumb { display: flex; align-items: center; gap: 8px; font-size: 12px; color: var(--gm-muted); margin-bottom: 22px; text-transform: uppercase; letter-spacing: 0.06em; font-weight: 700; }
        .gm-breadcrumb a { color: var(--gm-muted); text-decoration: none; transition: color 0.15s ease; }
        .gm-breadcrumb a:hover { color: var(--gm-primary); }
        .gm-breadcrumb svg { width: 14px; height: 14px; opacity: 0.7; }

        .gm-header { margin-bottom: 34px; }
        .gm-title { font-size: 34px; font-weight: 800; margin: 0 0 10px; letter-spacing: -0.02em; }
        .gm-subtitle { color: var(--gm-muted); font-size: 15px; line-height: 1.6; margin: 0; max-width: 720px; }

        .gm-section { margin-bottom: 44px; }
        .gm-section__head { display: flex; align-items: center; gap: 12px; margin-bottom: 16px; }
        .gm-section__num {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: var(--gm-primary);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 800;
            flex: 0 0 28px;
        }
        .gm-section__title { font-size: 22px; font-weight: 800; margin: 0; }
        .gm-section__sub { color: var(--gm-muted); font-size: 13px; margin: 4px 0 18px; padding-left: 40px; }

        /* Paleta */
        .gm-paleta__grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; }
        @media (max-width: 900px) { .gm-paleta__grid { grid-template-columns: 1fr; } }

        .gm-color {
            background: var(--gm-surface);
            border: 1px solid var(--gm-border);
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.16);
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        }
        .gm-color:hover { transform: translateY(-3px); box-shadow: 0 12px 28px rgba(0, 0, 0, 0.24); }

        .gm-color__swatch {
            height: 130px;
            padding: 16px;
            display: flex;
            align-items: flex-end;
            position: relative;
        }

        .gm-color__picker {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 12px;
            border-radius: 20px;
            background: rgba(0, 0, 0, 0.35);
            color: #fff;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.04em;
            cursor: pointer;
            transition: background 0.15s ease;
        }

        .gm-color__picker:hover { background: rgba(0, 0, 0, 0.55); }

        .gm-color__picker input[type="color"] {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
        }

        .gm-color__info { padding: 16px; }
        .gm-color__name { font-weight: 800; font-size: 15px; margin-bottom: 8px; }

        .gm-color__hex-input {
            width: 100%;
            padding: 9px 12px;
            border: 1px solid var(--gm-border);
            border-radius: 10px;
            background: var(--gm-surface-2);
            color: var(--gm-text);
            font-family: 'Inter', monospace;
            font-size: 13px;
            font-weight: 600;
            outline: none;
        }

        .gm-color__hex-input:focus { border-color: var(--gm-primary); }

        /* Tipografía */
        .gm-tipo__grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; }
        @media (max-width: 900px) { .gm-tipo__grid { grid-template-columns: 1fr; } }

        .gm-tipo {
            background: var(--gm-surface);
            border: 1px solid var(--gm-border);
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.16);
        }
        .gm-tipo__sample { font-size: 34px; font-weight: 800; margin-bottom: 8px; }
        .gm-tipo__chars { color: var(--gm-muted); font-size: 14px; margin-bottom: 14px; }
        .gm-tipo__tag {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            background: rgba(10, 132, 255, 0.12);
            color: var(--gm-primary);
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 0.04em;
            margin-bottom: 10px;
        }
        .gm-tipo__tag--alt { background: rgba(16, 185, 129, 0.12); color: var(--gm-emerald); }
        .gm-tipo__desc { color: var(--gm-muted); font-size: 13px; line-height: 1.5; margin: 0; }

        /* Tono */
        .gm-tono__grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; }
        @media (max-width: 900px) { .gm-tono__grid { grid-template-columns: 1fr; } }

        .gm-tono__col {
            background: var(--gm-surface);
            border: 1px solid var(--gm-border);
            border-radius: 16px;
            padding: 22px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.16);
        }
        .gm-tono__col--yes { border-top: 4px solid var(--gm-emerald); }
        .gm-tono__col--no { border-top: 4px solid var(--gm-rose); }

        .gm-tono__heading {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 16px;
            font-weight: 800;
            margin: 0 0 16px;
        }
        .gm-tono__heading svg { width: 18px; height: 18px; }
        .gm-tono__col--yes .gm-tono__heading { color: var(--gm-emerald); }
        .gm-tono__col--no .gm-tono__heading { color: var(--gm-rose); }

        .gm-tono__col ul { margin: 0; padding-left: 18px; color: var(--gm-muted); font-size: 14px; line-height: 1.8; }
        .gm-tono__col li { margin-bottom: 10px; }

        /* Legal */
        .gm-legal {
            display: flex;
            align-items: flex-start;
            gap: 16px;
            background: var(--gm-surface);
            border: 1px solid var(--gm-border);
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.16);
        }
        .gm-legal__icon {
            flex: 0 0 44px;
            width: 44px;
            height: 44px;
            border-radius: 12px;
            background: rgba(139, 92, 246, 0.12);
            color: var(--gm-violet);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .gm-legal__icon svg { width: 22px; height: 22px; }
        .gm-legal__title { font-size: 16px; font-weight: 800; margin: 0 0 6px; }
        .gm-legal__text { color: var(--gm-muted); margin: 0; font-size: 14px; line-height: 1.6; }
        .gm-legal__text strong { color: var(--gm-text); }

        .gm-toast {
            position: fixed;
            bottom: 24px;
            right: 24px;
            padding: 12px 18px;
            border-radius: 12px;
            background: var(--gm-emerald);
            color: #fff;
            font-size: 13px;
            font-weight: 700;
            opacity: 0;
            transform: translateY(10px);
            transition: opacity 0.2s ease, transform 0.2s ease;
            z-index: 200;
        }
        .gm-toast.show { opacity: 1; transform: translateY(0); }
    </style>

    <div class="guia-de-marca">
        <div class="gm-breadcrumb">
            <a href="{{ route('dashboard') }}">Inicio</a>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
            <span>Marketing</span>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
            <span>Guía de marca</span>
        </div>

        <div class="gm-header">
            <h1 class="gm-title">Guía de marca</h1>
            <p class="gm-subtitle">
                La base visual de todo lo que sale de MediBuy. Usa siempre estos colores, tipografías y reglas para que cada pieza se vea como parte de la misma marca.
            </p>
        </div>

        <section class="gm-section" id="paleta">
            <div class="gm-section__head">
                <span class="gm-section__num">1</span>
                <h2 class="gm-section__title">Paleta de color</h2>
            </div>
            <p class="gm-section__sub">Usa el selector o edita el HEX para ajustar los colores de la paleta.</p>
            @include('structure.gestion_marketing.guia_de_marca._paleta')
        </section>

        <section class="gm-section" id="tipografia">
            <div class="gm-section__head">
                <span class="gm-section__num">2</span>
                <h2 class="gm-section__title">Tipografía</h2>
            </div>
            <p class="gm-section__sub">Dos familias tipográficas para mantener consistencia.</p>
            @include('structure.gestion_marketing.guia_de_marca._tipografia')
        </section>

        <section class="gm-section" id="tono">
            <div class="gm-section__head">
                <span class="gm-section__num">3</span>
                <h2 class="gm-section__title">Tono de voz y uso correcto</h2>
            </div>
            <p class="gm-section__sub">Reglas que toda pieza debe cumplir.</p>
            @include('structure.gestion_marketing.guia_de_marca._tono')
        </section>

        <section class="gm-section" id="legal">
            <div class="gm-section__head">
                <span class="gm-section__num">4</span>
                <h2 class="gm-section__title">Leyenda legal obligatoria</h2>
            </div>
            <p class="gm-section__sub">Texto que debe aparecer en todo material de equipo médico.</p>
            @include('structure.gestion_marketing.guia_de_marca._legal')
        </section>
    </div>

    <div class="gm-toast" id="gmToast">HEX copiado</div>

    <script>
        function updateColor(input) {
            const hex = input.value.toUpperCase();
            const card = input.closest('.gm-color');
            card.querySelector('.gm-color__swatch').style.background = hex;
            card.querySelector('.gm-color__hex-input').value = hex;
        }

        function updateHex(input) {
            let hex = input.value.trim();
            if (!/^#[0-9A-F]{6}$/i.test(hex)) return;
            hex = hex.toUpperCase();
            const card = input.closest('.gm-color');
            card.querySelector('.gm-color__swatch').style.background = hex;
            card.querySelector('input[type="color"]').value = hex;
            input.value = hex;
        }
    </script>
@endsection
