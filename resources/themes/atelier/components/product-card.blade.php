@props([
    'product',
])

@php
    $name = $product->translateAttribute('name');
    $href = route('product', $product->defaultUrl?->slug ?? $product->id);
    $variant = $product->variants->first();
    $price = $variant?->prices->first();
    $brand = $product->brand?->name;
    $inStock = $product->variants->contains(fn ($item) => $item->canBeFulfilledAtQuantity(1));
    $colorVariants = $product->relationLoaded('colorVariantProducts') ? $product->colorVariantProducts : collect();
@endphp

<article {{ $attributes->class('etic-product') }}>
    <a href="{{ $href }}" class="etic-product__link">
        <div class="etic-product__media">
            <x-theme::product-image
                :model="$product"
                conversion="large"
                :alt="$name"
            />
            @unless($inStock)
                <span class="etic-product__badge">{{ __('etic.storefront.product.out_of_stock') }}</span>
            @endunless
        </div>
        <h2 class="etic-product__name">{{ $name }}</h2>
        @if(filled($brand))
            <p class="etic-product__brand">{{ $brand }}</p>
        @endif
        @if($price)
            <p class="etic-product__price">{{ $price->priceIncTax()->formatted() }}</p>
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
