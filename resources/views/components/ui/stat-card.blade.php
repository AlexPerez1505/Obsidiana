@props(['value', 'label', 'color' => 'blue', 'icon', 'valueClass' => '', 'valueStyle' => ''])

<div class="card stat">
    <div class="stat-ico {{ $color }}">
        {!! $icon !!}
    </div>
    <div>
        <div class="stat-num {{ $valueClass }}" style="{{ $valueStyle }}">{{ $value }}</div>
        <div class="stat-lbl">{{ $label }}</div>
    </div>
</div>
