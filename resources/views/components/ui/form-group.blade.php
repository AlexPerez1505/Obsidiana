@props([
    'label',
    'name' => null,
    'for' => null,
    'type' => 'text',
    'value' => '',
    'required' => false,
    'autofocus' => false,
    'placeholder' => '',
    'inputClass' => '',
    'inputmode' => null,
    'maxlength' => null,
    'pattern' => null,
    'autocomplete' => null,
])

@php
$fieldName = $name ?? $for;
@endphp

@if ($slot->isEmpty())
    <label for="{{ $fieldName }}">{{ $label }}</label>
    <input id="{{ $fieldName }}"
           type="{{ $type }}"
           name="{{ $fieldName }}"
           value="{{ old($fieldName, $value) }}"
           {{ $required ? 'required' : '' }}
           {{ $autofocus ? 'autofocus' : '' }}
           @if ($placeholder) placeholder="{{ $placeholder }}" @endif
           class="{{ $inputClass }}"
           @if ($inputmode) inputmode="{{ $inputmode }}" @endif
           @if ($maxlength) maxlength="{{ $maxlength }}" @endif
           @if ($pattern) pattern="{{ $pattern }}" @endif
           @if ($autocomplete) autocomplete="{{ $autocomplete }}" @endif>
@else
    <label for="{{ $for }}">{{ $label }}</label>
    {{ $slot }}
@endif

@error($fieldName)
    @if ($fieldName)
        <p class="err">{{ $message }}</p>
    @endif
@enderror
