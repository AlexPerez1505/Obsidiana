@props(['class' => '', 'style' => ''])

<div class="card {{ $class }}" style="{{ $style }}">
    {{ $slot }}
</div>
