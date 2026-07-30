@props(['type' => 'default', 'style' => ''])

<div class="alert {{ $type !== 'default' ? 'alert--'.$type : '' }}" style="{{ $style }}">
    {{ $slot }}
</div>
