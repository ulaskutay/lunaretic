@props([
    'product',
])

@php
    $name = $product->translateAttribute('name');
    $href = route('product', $product->defaultUrl?->slug ?? $product->id);
    $variant = $product->variants->first();
    $price = $variant?->prices->first();
    $inStock = $product->variants->contains(fn ($item) => $item->canBeFulfilledAtQuantity(1));
    $colorVariants = $product->relationLoaded('colorVariantProducts') ? $product->colorVariantProducts : collect();
@endphp

<article {{ $attributes->class('etic-card block p-3 shadow-sm') }}>
    <a href="{{ $href }}" class="block text-inherit no-underline">
        <div class="relative mb-3 aspect-square overflow-hidden rounded-[calc(var(--etic-radius,1rem)-0.25rem)] bg-canvas">
            <x-theme::product-image :model="$product" conversion="large" :alt="$name" />
            @unless($inStock)
                <span class="etic-product__badge">{{ __('etic.storefront.product.out_of_stock') }}</span>
            @endunless
        </div>
        <h2 class="font-heading text-sm font-medium">{{ $name }}</h2>
        @if($price)
            <p class="mt-1 text-sm text-muted">{{ $price->priceIncTax()->formatted() }}</p>
        @endif
    </a>
    @if($colorVariants->count() > 1)
        <div class="etic-product__colors">
            @foreach($colorVariants as $item)
                @php($slug = $item->defaultUrl?->slug)
                @continue(! filled($slug))
                <a
                    href="{{ route('product', $slug) }}"
                    class="etic-product__color{{ $item->is($product) ? ' is-active' : '' }}"
                    title="{{ $item->translateAttribute('name') }}"
                >
                    <x-theme::product-image :model="$item" conversion="small" :alt="$item->translateAttribute('name')" />
                </a>
            @endforeach
        </div>
    @endif
</article>
