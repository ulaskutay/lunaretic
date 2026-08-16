@props([
    'model',
    'conversion' => 'large',
    'alt' => '',
    'priority' => false,
])

@php
    $src = \App\Etic\Media\ProductImage::url($model, $conversion);
@endphp

@if($src)
    <img
        src="{{ $src }}"
        alt="{{ $alt }}"
        loading="{{ $priority ? 'eager' : 'lazy' }}"
        @if($priority) fetchpriority="high" @endif
        decoding="async"
        {{ $attributes->except('class')->class('block h-full w-full object-cover object-center') }}
    >
@else
    <div {{ $attributes->except('class')->class('h-full w-full bg-canvas') }}></div>
@endif
