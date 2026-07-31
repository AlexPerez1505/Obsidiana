<div class="gm-paleta" id="gmPaleta">
    <div class="gm-paleta__grid">
        @foreach ([
            ['id' => 'azul', 'name' => 'Azul MediBuy', 'hex' => '#1859A6'],
            ['id' => 'verde', 'name' => 'Verde MediBuy', 'hex' => '#357C23'],
            ['id' => 'gris', 'name' => 'Gris neutro', 'hex' => '#A3A3A3'],
        ] as $color)
            <div class="gm-color" data-color-id="{{ $color['id'] }}">
                <div class="gm-color__swatch" style="background: {{ $color['hex'] }};">
                    <label class="gm-color__picker">
                        <input type="color" value="{{ $color['hex'] }}" oninput="updateColor(this)">
                        <span>Editar</span>
                    </label>
                </div>
                <div class="gm-color__info">
                    <div class="gm-color__name">{{ $color['name'] }}</div>
                    <input type="text" class="gm-color__hex-input" value="{{ $color['hex'] }}" oninput="updateHex(this)">
                </div>
            </div>
        @endforeach
    </div>
</div>
