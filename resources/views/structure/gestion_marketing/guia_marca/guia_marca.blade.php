@extends('layouts.dashboard')

@section('title', 'Guía de marca')
@section('page-title', 'Guía de marca')
@section('page-sub', 'La base visual de todo lo que sale de MediBuy')

@section('content')
<style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Open+Sans:wght@600;700;800&display=swap');
    .guia-title { margin:0; font-size:32px; font-weight:800; letter-spacing:-0.02em; }
    .guia-sub { margin:10px 0 0; color:var(--muted); line-height:1.55; max-width:720px; }
    .section-title { margin:0 0 18px; font-size:18px; font-weight:700; }
    .palette-grid, .fonts-grid, .tone-grid { display:grid; gap:18px; }
    .palette-grid { grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); }
    .fonts-grid, .tone-grid { grid-template-columns:repeat(auto-fit,minmax(280px,1fr)); }
    .color-card { background:var(--surface); border:1px solid var(--border); border-radius:18px; overflow:hidden; box-shadow:var(--shadow); position:relative; }
    .remove-color, .remove-font { position:absolute; top:10px; right:10px; width:28px; height:28px; border-radius:50%; border:none; background:rgba(255,255,255,0.85); color:#b91c1c; cursor:pointer; display:flex; align-items:center; justify-content:center; font-size:18px; line-height:1; box-shadow:0 2px 6px rgba(0,0,0,.1); transition:background 0.15s; z-index:2; }
    .remove-color:hover, .remove-font:hover { background:#fff; }
    .swatch { position:relative; height:120px; display:flex; align-items:flex-end; padding:16px; transition:background 0.15s; }
    .copy-btn { font-size:10px; font-weight:800; letter-spacing:0.04em; padding:7px 12px; border-radius:8px; border:none; cursor:pointer; background:rgba(255,255,255,0.22); color:#fff; backdrop-filter:blur(4px); transition:background 0.15s; }
    .copy-btn:hover { background:rgba(255,255,255,0.35); }
    .color-meta { padding:16px; display:flex; flex-direction:column; gap:10px; }
    .edit-field { width:100%; background:var(--surface-2); border:1px solid var(--border); border-radius:10px; padding:8px 12px; color:var(--text); font-family:inherit; font-size:14px; outline:none; transition:border 0.15s, background 0.15s; }
    .edit-field:focus { border-color:var(--primary); background:var(--surface); }
    .font-card { display:flex; flex-direction:column; gap:16px; background:var(--surface); border:1px solid var(--border); border-radius:18px; padding:28px 24px 24px; position:relative; color:var(--text); }
    [data-theme="dark"] .font-card { background:#0d1630; border-color:rgba(90,140,230,.14); color:#e8eef8; }
    .font-name { font-size:34px; font-weight:700; line-height:1.15; letter-spacing:-0.03em; color:var(--text); }
    [data-theme="dark"] .font-name { color:#e8eef8; }
    .font-sample { font-size:15px; color:var(--text); letter-spacing:.01em; }
    [data-theme="dark"] .font-sample { color:#e8eef8; }
    .font-usage { font-size:11px; letter-spacing:0.08em; text-transform:uppercase; color:var(--primary); font-weight:700; }
    [data-theme="dark"] .font-usage { color:#0a84ff; }
    .font-desc { font-size:13px; line-height:1.55; color:var(--muted); }
    [data-theme="dark"] .font-desc { color:#93a4bd; }
    .tone-card { padding:22px; border-radius:14px; }
    .tone-card.do { background:#f0fdf4; border:1px solid #bbf7d0; }
    .tone-card.dont { background:#fef2f2; border:1px solid #fecaca; }
    [data-theme="dark"] .tone-card.do { background:rgba(22,163,74,0.10); border-color:rgba(74,222,128,0.25); }
    [data-theme="dark"] .tone-card.dont { background:rgba(220,38,38,0.10); border-color:rgba(248,113,113,0.25); }
    .tone-title { margin:0 0 14px; font-size:16px; font-weight:700; display:flex; align-items:center; gap:8px; }
    .tone-card ul { list-style:none; padding:0; margin:0; }
    .tone-card li { padding-left:20px; position:relative; margin-bottom:10px; font-size:14px; line-height:1.45; }
    .tone-card li::before { content:""; position:absolute; left:0; top:7px; width:7px; height:7px; border-radius:50%; }
    .tone-card.do li::before { background:var(--green); }
    .tone-card.dont li::before { background:var(--danger); }
    .save-bar { display:flex; justify-content:flex-end; gap:12px; align-items:center; flex-wrap:wrap; }
    .btn-primary { display:inline-flex; align-items:center; gap:8px; padding:12px 24px; border:none; border-radius:12px; background:var(--primary); color:#fff; font-weight:700; font-size:15px; cursor:pointer; transition:background 0.15s; }
    .btn-primary:hover { background:var(--primary-strong); }
</style>

<form method="POST" action="{{ route('marketing.guia_de_marca.update') }}">
    @csrf

    <div class="card" style="margin-bottom:22px;">
        <h1 class="guia-title">Guía de marca</h1>
        <p class="guia-sub">
            La base visual de todo lo que sale de MediBuy. Usa siempre estos colores, tipografías y
            reglas para que cada pieza se vea como parte de la misma marca.
        </p>
    </div>

    <div class="card" style="margin-bottom:22px;">
        <div style="display:flex; align-items:center; justify-content:space-between; gap:10px; margin-bottom:18px; flex-wrap:wrap;">
            <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                <h2 class="section-title" style="margin:0;">Paleta de color</h2>
                <span style="font-size:12px; color:var(--muted);">(clic para copiar el HEX)</span>
            </div>
            <a href="{{ route('marketing.guia_de_marca.create') }}" class="btn-primary" style="padding:9px 16px; font-size:14px; text-decoration:none;">Crear más colores</a>
        </div>
        <div class="palette-grid" id="palette-grid">
            @foreach ($brandGuide->colors as $i => $color)
                <div class="color-card" data-index="{{ $i }}">
                    <button type="button" class="remove-color" title="Eliminar color" onclick="removeColor(this)">&times;</button>
                    <div class="swatch" style="background-color:{{ $color['hex'] }};">
                        <button type="button" class="copy-btn" data-copy="{{ $color['hex'] }}">COPIAR</button>
                    </div>
                    <div class="color-meta">
                        <input type="text" name="colors[{{ $i }}][name]" value="{{ old("colors.$i.name", $color['name'] ?? '') }}" class="edit-field" placeholder="Nombre del color" required>
                        <input type="text" name="colors[{{ $i }}][hex]" value="{{ old("colors.$i.hex", $color['hex'] ?? '') }}" class="edit-field hex-input" placeholder="#000000" required oninput="updateSwatch(this)">
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="card" style="margin-bottom:22px;">
        <h2 class="section-title">Tipografía</h2>
        <div class="fonts-grid" id="fonts-grid">
            <div class="font-card" data-index="0">
                <div class="font-name" style="font-family:'Open Sans',sans-serif;">Open Sans</div>
                <div class="font-sample" style="font-family:'Open Sans',sans-serif;">Aa Bb Cc · 0123456789</div>
                <div class="font-usage">DISPLAY · TÍTULOS</div>
                <div class="font-desc">Para encabezados, nombres de equipo y números grandes. Pesos 600-800.</div>
            </div>
            <div class="font-card" data-index="1">
                <div class="font-name" style="font-family:'Inter',sans-serif;">Inter</div>
                <div class="font-sample" style="font-family:'Inter',sans-serif;">Aa Bb Cc · 0123456789</div>
                <div class="font-usage">TEXTO · CUERPO</div>
                <div class="font-desc">Para descripciones, copys largos y datos técnicos. Pesos 400-600.</div>
            </div>
        </div>
    </div>

    <div class="card" style="margin-bottom:22px;">
        <h2 class="section-title">Tono de voz & uso correcto</h2>
        <div class="tone-grid">
            <div class="tone-card do">
                <div class="tone-title" style="color:var(--green);">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="20" height="20"><polyline points="20 6 9 17 4 12"/></svg>
                    Sí hacer
                </div>
                <ul>
                    <li>Tono profesional, claro y cercano; cero relleno.</li>
                    <li>Specs verificados contra la fuente del fabricante.</li>
                    <li>Mostrar marca y modelo correctos del equipo.</li>
                    <li>CTA claro a WhatsApp o catálogo.</li>
                    <li>Imágenes nítidas, sin texto cortado.</li>
                </ul>
            </div>
            <div class="tone-card dont">
                <div class="tone-title" style="color:var(--danger);">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="20" height="20"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    No hacer
                </div>
                <ul>
                    <li>Prometer resultados clínicos o curativos.</li>
                    <li>Inventar datos técnicos sin verificar.</li>
                    <li>Publicar precios sin autorización.</li>
                    <li>Mezclar colores fuera de la paleta.</li>
                    <li>Usar fotos pixeladas o con marca de agua.</li>
                </ul>
            </div>
        </div>
    </div>

    <div class="card save-bar">
        <button type="submit" class="btn-primary">Guardar cambios</button>
    </div>
</form>

<script>
    (function () {
        function copyToClipboard(text) {
            if (navigator.clipboard && window.isSecureContext) {
                return navigator.clipboard.writeText(text);
            }
            var ta = document.createElement('textarea');
            ta.value = text;
            document.body.appendChild(ta);
            ta.select();
            document.execCommand('copy');
            document.body.removeChild(ta);
            return Promise.resolve();
        }

        document.querySelectorAll('.copy-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var input = this.closest('.color-card').querySelector('.hex-input');
                var hex = input ? input.value : this.dataset.copy;
                copyToClipboard(hex).then(function () {
                    if (window.showToast) window.showToast('HEX copiado: ' + hex, 'ok');
                });
            });
        });

        window.updateSwatch = function (input) {
            var card = input.closest('.color-card');
            var swatch = card.querySelector('.swatch');
            var hex = input.value.trim();
            swatch.style.backgroundColor = hex;

            var r = 0, g = 0, b = 0;
            if (hex.length === 4) {
                r = parseInt(hex[1] + hex[1], 16);
                g = parseInt(hex[2] + hex[2], 16);
                b = parseInt(hex[3] + hex[3], 16);
            } else if (hex.length === 7) {
                r = parseInt(hex.substr(1, 2), 16);
                g = parseInt(hex.substr(3, 2), 16);
                b = parseInt(hex.substr(5, 2), 16);
            }
            var luminance = (0.299 * r + 0.587 * g + 0.114 * b) / 255;
            var btn = swatch.querySelector('.copy-btn');
            if (btn) {
                if (luminance > 0.6) {
                    btn.style.color = '#111827';
                    btn.style.background = 'rgba(0,0,0,0.12)';
                } else {
                    btn.style.color = '#fff';
                    btn.style.background = 'rgba(255,255,255,0.22)';
                }
            }
        };

        document.querySelectorAll('.hex-input').forEach(function (input) { window.updateSwatch(input); });

        var palette = document.getElementById('palette-grid');

        function reindexColors() {
            var cards = palette.querySelectorAll('.color-card');
            cards.forEach(function (card, i) {
                card.dataset.index = i;
                card.querySelectorAll('input').forEach(function (input) {
                    input.name = input.name.replace(/^colors\[\d+\]/, 'colors[' + i + ']');
                });
            });
        }

        window.removeColor = function (btn) {
            var card = btn.closest('.color-card');
            card.remove();
            reindexColors();
        };
    })();
</script>
@endsection
