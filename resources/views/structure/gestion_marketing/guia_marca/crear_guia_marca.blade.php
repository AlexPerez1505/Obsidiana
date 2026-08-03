@extends('layouts.dashboard')

@section('title', 'Crear guía de marca')
@section('page-title', 'Crear guía de marca')
@section('page-sub', 'Define la paleta y tipografía de la marca')

@section('content')
<style>
    .guia-title { margin:0; font-size:32px; font-weight:800; letter-spacing:-0.02em; }
    .guia-sub { margin:10px 0 0; color:var(--muted); line-height:1.55; max-width:720px; }
    .section-title { margin:0 0 18px; font-size:18px; font-weight:700; }
    .section-header { display:flex; align-items:center; gap:10px; margin-bottom:18px; flex-wrap:wrap; }
    .palette-grid, .fonts-grid { display:grid; gap:18px; }
    .palette-grid { grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); }
    .fonts-grid { grid-template-columns:repeat(auto-fit,minmax(280px,1fr)); }
    .color-card { background:var(--surface); border:1px solid var(--border); border-radius:18px; overflow:hidden; box-shadow:var(--shadow); position:relative; }
    .swatch { position:relative; height:120px; display:flex; align-items:flex-end; padding:16px; transition:background 0.15s; }
    .remove-color, .remove-font { position:absolute; top:10px; right:10px; width:28px; height:28px; border-radius:50%; border:none; background:rgba(255,255,255,0.85); color:#b91c1c; cursor:pointer; display:flex; align-items:center; justify-content:center; font-size:18px; line-height:1; box-shadow:0 2px 6px rgba(0,0,0,.1); transition:background 0.15s; }
    .remove-color:hover, .remove-font:hover { background:#fff; }
    .font-card { display:flex; flex-direction:column; gap:12px; background:var(--surface-2); border:1px solid var(--border); border-radius:14px; padding:46px 20px 20px; position:relative; }
    .edit-field { width:100%; background:var(--surface-2); border:1px solid var(--border); border-radius:10px; padding:8px 12px; color:var(--text); font-family:inherit; font-size:14px; outline:none; transition:border 0.15s, background 0.15s; }
    .edit-field:focus { border-color:var(--primary); background:var(--surface); }
    .font-title { font-size:26px; font-weight:800; letter-spacing:-0.02em; background:transparent; border:none; border-bottom:1px dashed var(--border); border-radius:0; padding:0 0 6px; }
    .font-title:focus { background:transparent; border-color:var(--primary); }
    .font-sample { font-size:15px; }
    .font-usage { font-size:11px; letter-spacing:0.08em; text-transform:uppercase; color:var(--muted); font-weight:700; }
    .font-desc { font-size:13px; line-height:1.5; resize:vertical; min-height:54px; }
    .btn-primary { display:inline-flex; align-items:center; gap:8px; padding:12px 24px; border:none; border-radius:12px; background:var(--primary); color:#fff; font-weight:700; font-size:15px; cursor:pointer; transition:background 0.15s; }
    .btn-primary:hover { background:var(--primary-strong); }
    .btn-secondary { display:inline-flex; align-items:center; gap:8px; padding:10px 18px; border:1px solid var(--border); border-radius:10px; background:var(--surface-2); color:var(--text); font-weight:700; font-size:14px; cursor:pointer; transition:background 0.15s; }
    .btn-secondary:hover { background:var(--surface); }
    .add-btn { margin-top:18px; }
    .save-bar { display:flex; justify-content:flex-end; gap:12px; align-items:center; flex-wrap:wrap; }
</style>

<form method="POST" action="{{ route('marketing.guia.update') }}">
    @csrf

    <div class="card" style="margin-bottom:22px;">
        <h1 class="guia-title">Crear guía de marca</h1>
        <p class="guia-sub">
            Configura la paleta de colores y las fuentes base de la marca. Puedes agregar tantos colores como necesites.
        </p>
    </div>

    <div class="card" style="margin-bottom:22px;">
        <div class="section-header">
            <h2 class="section-title" style="margin:0;">Paleta de color</h2>
            <span style="font-size:12px; color:var(--muted);">(usa el botón para generar más colores)</span>
        </div>
        <div class="palette-grid" id="palette-grid">
            @foreach (old('colors', $brandGuide->colors ?? [['name' => '', 'hex' => '']]) as $i => $color)
                <div class="color-card" data-index="{{ $i }}">
                    <button type="button" class="remove-color" title="Eliminar color" onclick="removeColor(this)">&times;</button>
                    <div class="swatch" style="background-color:{{ $color['hex'] ?: '#000000' }};"></div>
                    <div class="color-meta" style="padding:16px; display:flex; flex-direction:column; gap:10px;">
                        <input type="text" name="colors[{{ $i }}][name]" value="{{ $color['name'] }}" class="edit-field" placeholder="Nombre del color" required>
                        <input type="text" name="colors[{{ $i }}][hex]" value="{{ $color['hex'] }}" class="edit-field hex-input" placeholder="#000000" required oninput="updateSwatch(this)">
                    </div>
                </div>
            @endforeach
        </div>
        <button type="button" class="btn-secondary add-btn" id="add-color">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Agregar color
        </button>
    </div>

    <div class="card" style="margin-bottom:22px;">
        <h2 class="section-title">Tipografía</h2>
        <div class="fonts-grid" id="fonts-grid">
            @foreach (old('fonts', $brandGuide->fonts ?? []) as $i => $font)
                <div class="font-card" data-index="{{ $i }}">
                    <button type="button" class="remove-font" title="Eliminar fuente" onclick="removeFont(this)">&times;</button>
                    <input type="text" name="fonts[{{ $i }}][name]" value="{{ $font['name'] ?? '' }}" class="edit-field font-title" placeholder="Nombre de la fuente">
                    <input type="text" name="fonts[{{ $i }}][sample]" value="{{ $font['sample'] ?? '' }}" class="edit-field font-sample" placeholder="Aa Bb Cc · 0123456789">
                    <input type="text" name="fonts[{{ $i }}][usage]" value="{{ $font['usage'] ?? '' }}" class="edit-field font-usage" placeholder="USO · TIPO">
                    <textarea name="fonts[{{ $i }}][description]" class="edit-field font-desc" rows="2" placeholder="Descripción de uso">{{ $font['description'] ?? '' }}</textarea>
                </div>
            @endforeach
        </div>
        <button type="button" class="btn-secondary add-btn" id="add-font">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Agregar fuente
        </button>
    </div>

    <div class="card save-bar">
        <a href="{{ route('marketing.guia.index') }}" class="btn-secondary" style="text-decoration:none;">Volver a la guía</a>
        <button type="submit" class="btn-primary">Guardar guía de marca</button>
    </div>
</form>

<script>
    (function () {
        var palette = document.getElementById('palette-grid');

        function reindex() {
            var cards = palette.querySelectorAll('.color-card');
            cards.forEach(function (card, i) {
                card.dataset.index = i;
                card.querySelectorAll('input').forEach(function (input) {
                    var parts = input.name.replace(/^colors\[\d+\]/, 'colors[' + i + ']');
                    input.name = parts;
                });
            });
        }

        function updateSwatch(input) {
            var card = input.closest('.color-card');
            var swatch = card.querySelector('.swatch');
            swatch.style.backgroundColor = input.value.trim() || '#000000';
        }

        window.removeColor = function (btn) {
            var card = btn.closest('.color-card');
            card.remove();
            reindex();
            if (palette.querySelectorAll('.color-card').length === 0) {
                document.getElementById('add-color').click();
            }
        };

        document.getElementById('add-color').addEventListener('click', function () {
            var index = palette.querySelectorAll('.color-card').length;
            var div = document.createElement('div');
            div.className = 'color-card';
            div.dataset.index = index;
            div.innerHTML =
                '<button type="button" class="remove-color" title="Eliminar color" onclick="removeColor(this)">&times;</button>' +
                '<div class="swatch" style="background-color:#000000;"></div>' +
                '<div class="color-meta" style="padding:16px; display:flex; flex-direction:column; gap:10px;">' +
                    '<input type="text" name="colors[' + index + '][name]" class="edit-field" placeholder="Nombre del color" required>' +
                    '<input type="text" name="colors[' + index + '][hex]" class="edit-field hex-input" placeholder="#000000" required oninput="updateSwatch(this)">' +
                '</div>';
            palette.appendChild(div);
        });

        window.updateSwatch = function (input) {
            var card = input.closest('.color-card');
            var swatch = card.querySelector('.swatch');
            swatch.style.backgroundColor = input.value.trim() || '#000000';
        };

        var fontsGrid = document.getElementById('fonts-grid');

        function reindexFonts() {
            var cards = fontsGrid.querySelectorAll('.font-card');
            cards.forEach(function (card, i) {
                card.dataset.index = i;
                card.querySelectorAll('input, textarea').forEach(function (input) {
                    input.name = input.name.replace(/^fonts\[\d+\]/, 'fonts[' + i + ']');
                });
            });
        }

        window.removeFont = function (btn) {
            var card = btn.closest('.font-card');
            card.remove();
            reindexFonts();
        };

        document.getElementById('add-font').addEventListener('click', function () {
            var index = fontsGrid.querySelectorAll('.font-card').length;
            var div = document.createElement('div');
            div.className = 'font-card';
            div.dataset.index = index;
            div.innerHTML =
                '<button type="button" class="remove-font" title="Eliminar fuente" onclick="removeFont(this)">&times;</button>' +
                '<input type="text" name="fonts[' + index + '][name]" class="edit-field font-title" placeholder="Nombre de la fuente">' +
                '<input type="text" name="fonts[' + index + '][sample]" class="edit-field font-sample" placeholder="Aa Bb Cc · 0123456789">' +
                '<input type="text" name="fonts[' + index + '][usage]" class="edit-field font-usage" placeholder="USO · TIPO">' +
                '<textarea name="fonts[' + index + '][description]" class="edit-field font-desc" rows="2" placeholder="Descripción de uso"></textarea>';
            fontsGrid.appendChild(div);
        });

    })();
</script>
@endsection
