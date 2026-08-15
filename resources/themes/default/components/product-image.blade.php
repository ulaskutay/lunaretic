@props([
    'model',
    'conversion' => 'large',
    'alt' => '',
])

@php($src = \App\Etic\Media\ProductImage::url($model, $conversion))

@if($src)
    <img
        src="{{ $src }}"
        alt="{{ $alt }}"
        loading="lazy"
        decoding="async"
        style="object-fit: contain; object-position: center;"
        {{ $attributes->except('class')->class('block h-full w-full object-contain object-center') }}
    >
@else
    <div {{ $attributes->except('class')->class('h-full w-full bg-neutral-100') }}></div>
@endif
