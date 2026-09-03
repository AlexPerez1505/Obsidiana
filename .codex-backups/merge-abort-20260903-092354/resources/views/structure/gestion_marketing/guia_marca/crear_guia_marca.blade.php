@extends('layouts.dashboard')

@section('title', 'Editar guía de marca')
@section('page-title', 'Editar guía de marca')

@push('head')
    <style>
        .guia-editor { max-width: 900px; }
        .guia-editor h2 { font-size: 20px; font-weight: 700; margin: 0 0 16px; }
        .guia-editor .section { background: var(--surface); border: 1px solid var(--border); border-radius: 18px; padding: 24px; margin-bottom: 24px; }
        .guia-editor .row { display: flex; gap: 12px; align-items: center; margin-bottom: 10px; }
        .guia-editor .row input { flex: 1 1 0; padding: 11px 12px; border: 1px solid var(--border); border-radius: 10px; background: var(--surface-2); color: var(--text); font-size: 14px; }
        .guia-editor .row input:focus { border-color: var(--primary); outline: none; }
        .guia-editor .row input[name*="[hex]"] { max-width: 140px; text-transform: uppercase; }
        .guia-editor .btn-add {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 10px 16px; border-radius: 10px; border: 0;
            background: var(--green); color: #fff; font-size: 14px; font-weight: 600;
            cursor: pointer; margin-top: 6px;
        }
        .guia-editor .btn-add:hover { filter: brightness(1.05); }
        .guia-editor .btn-delete {
            width: 34px; height: 34px; border-radius: 8px; border: 0;
            background: var(--danger-soft); color: var(--danger);
            font-size: 20px; font-weight: 700; cursor: pointer;
            display: flex; align-items: center; justify-content: center;
        }
        .guia-editor .actions { display: flex; gap: 12px; margin-top: 10px; }
        .guia-editor .btn-save {
            padding: 12px 22px; border: 0; border-radius: 10px;
            background: var(--primary); color: #fff; font-size: 15px; font-weight: 600; cursor: pointer;
        }
        .guia-editor .btn-cancel {
            padding: 12px 22px; border-radius: 10px;
            background: transparent; color: var(--primary); border: 1px solid var(--border);
            font-size: 15px; font-weight: 600; text-decoration: none;
        }
        .guia-editor .empty { color: var(--muted); font-size: 14px; }
    </style>
@endpush

@section('content')
    <div class="guia-editor">
        <form class="guia-form" action="{{ route('marketing.guia_de_marca.update') }}" method="POST">
            @csrf
            <div class="section">
                <h2>Paleta de colores</h2>
                <div id="colors-list">
                    @forelse ($brandGuide->colors ?? [] as $idx => $color)
                        <div class="row" data-color-row>
                            <input type="text" name="colors[{{ $idx }}][name]" value="{{ $color['name'] }}" placeholder="Nombre del color" required>
                            <input type="text" name="colors[{{ $idx }}][hex]" value="{{ $color['hex'] }}" placeholder="#000000" maxlength="7" required>
                            <button type="button" class="btn-delete" data-delete>&times;</button>
                        </div>
                    @empty
                        <p class="empty" id="no-colors">No hay colores. Agrega uno con el botón de abajo.</p>
                    @endforelse
                </div>
                <template id="color-template">
                    <div class="row" data-color-row>
                        <input type="text" name="colors[__IDX__][name]" placeholder="Nombre del color" required>
                        <input type="text" name="colors[__IDX__][hex]" placeholder="#000000" maxlength="7" required>
                        <button type="button" class="btn-delete" data-delete>&times;</button>
                    </div>
                </template>
                <button type="button" class="btn-add" id="add-color">+ Agregar color</button>
                <div class="actions">
                    <button type="submit" class="btn-save">Guardar colores</button>
                    <a href="{{ route('marketing.guia_de_marca.index') }}" class="btn-cancel">Cancelar</a>
                </div>
            </div>
        </form>

        <form class="guia-form" action="{{ route('marketing.guia_de_marca.update') }}" method="POST">
            @csrf
            <div class="section">
                <h2>Tipografías</h2>
                <div id="fonts-list">
                    @forelse ($brandGuide->fonts ?? [] as $idx => $font)
                        <div class="row" data-font-row>
                            <input type="text" name="fonts[{{ $idx }}][name]" value="{{ $font['name'] }}" placeholder="Nombre de la tipografía" required>
                            <input type="text" name="fonts[{{ $idx }}][sample]" value="{{ $font['sample'] }}" placeholder="Muestra (Aa Bb Cc...)" required>
                            <input type="text" name="fonts[{{ $idx }}][usage]" value="{{ $font['usage'] }}" placeholder="Uso" required>
                            <input type="text" name="fonts[{{ $idx }}][description]" value="{{ $font['description'] }}" placeholder="Descripción" required>
                            <button type="button" class="btn-delete" data-delete>&times;</button>
                        </div>
                    @empty
                        <p class="empty" id="no-fonts">No hay tipografías. Agrega una con el botón de abajo.</p>
                    @endforelse
                </div>
                <template id="font-template">
                    <div class="row" data-font-row>
                        <input type="text" name="fonts[__IDX__][name]" placeholder="Nombre de la tipografía" required>
                        <input type="text" name="fonts[__IDX__][sample]" placeholder="Muestra (Aa Bb Cc...)" required>
                        <input type="text" name="fonts[__IDX__][usage]" placeholder="Uso" required>
                        <input type="text" name="fonts[__IDX__][description]" placeholder="Descripción" required>
                        <button type="button" class="btn-delete" data-delete>&times;</button>
                    </div>
                </template>
                <button type="button" class="btn-add" id="add-font">+ Agregar tipografía</button>
                <div class="actions">
                    <button type="submit" class="btn-save">Guardar tipografías</button>
                    <a href="{{ route('marketing.guia_de_marca.index') }}" class="btn-cancel">Cancelar</a>
                </div>
            </div>
        </form>
    </div>

    <script>
        (function () {
            let colorIndex = document.querySelectorAll('[data-color-row]').length;
            let fontIndex = document.querySelectorAll('[data-font-row]').length;

            function addFromTemplate(templateId, listId, indexVar, emptyId, rowAttr) {
                const template = document.getElementById(templateId).content.cloneNode(true);
                const wrapper = template.querySelector('.row');
                wrapper.querySelectorAll('input, button').forEach(function (el) {
                    if (el.name) el.name = el.name.replace('__IDX__', indexVar);
                    if (el.dataset.delete === '') el.dataset.delete = '';
                });
                wrapper.querySelectorAll('input').forEach(function (el) { el.value = ''; });
                document.getElementById(listId).appendChild(wrapper);
                const empty = document.getElementById(emptyId);
                if (empty) empty.remove();
            }

            document.getElementById('add-color').addEventListener('click', function () {
                addFromTemplate('color-template', 'colors-list', colorIndex++, 'no-colors');
            });

            document.getElementById('add-font').addEventListener('click', function () {
                addFromTemplate('font-template', 'fonts-list', fontIndex++, 'no-fonts');
            });

            document.addEventListener('click', function (e) {
                if (e.target.matches('[data-delete]')) {
                    e.target.closest('[data-color-row], [data-font-row]').remove();
                }
            });
        })();
    </script>
@endsection