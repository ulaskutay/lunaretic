<x-storefront-layout :canonical="$canonical ?? null" :schema-json="$schemaJson ?? null" :seo-title="$product->translateAttribute('name')">
    <div class="grid gap-10 md:grid-cols-2">
        <div class="space-y-3">
            @php($gallery = \App\Etic\Media\ProductImage::gallery($product))
            <div class="aspect-square overflow-hidden rounded-3xl bg-neutral-100">
                <x-theme::product-image :model="$product" conversion="large" :alt="$product->translateAttribute('name')" data-etic-cart-source />
            </div>
            @if($gallery->count() > 1)
                <div class="grid grid-cols-4 gap-2">
                    @foreach($gallery as $media)
                        <div class="aspect-square overflow-hidden rounded-xl bg-neutral-100">
                            <img
                                src="{{ $media->hasGeneratedConversion('small') ? $media->getUrl('small') : $media->getUrl() }}"
                                alt=""
                                class="block h-full w-full object-contain object-center"
                                style="object-fit: contain; object-position: center;"
                            >
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
        <div>
            <h1 class="text-3xl font-semibold">{{ $product->translateAttribute('name') }}</h1>
            @unless($product->variants->contains(fn ($item) => $item->canBeFulfilledAtQuantity(1)))
                <p class="mt-2 text-xs font-semibold uppercase tracking-[0.14em] text-neutral-500">{{ __('etic.storefront.product.out_of_stock') }}</p>
            @endunless
            <div class="prose mt-4 text-neutral-600">{!! $product->translateAttribute('description') !!}</div>
            @if(($colorVariants ?? collect())->count() > 1)
                <div class="mt-6">
                    <p class="text-xs font-semibold uppercase tracking-wide text-neutral-500">{{ __('etic.storefront.filters.color') }}</p>
                    <div class="mt-2 flex flex-wrap gap-2 rounded-xl border border-neutral-200 bg-white p-3">
                        @foreach($colorVariants as $item)
                            <a
                                href="{{ route('product', $item['slug']) }}"
                                class="block rounded-lg border p-1 transition hover:border-neutral-900 {{ $item['active'] ? 'border-neutral-900 ring-1 ring-neutral-900' : 'border-neutral-200' }}"
                                title="{{ $item['color'] ?: $item['name'] }}"
                            >
                                <img
                                    src="{{ $item['image'] }}"
                                    alt="{{ $item['color'] ?: $item['name'] }}"
                                    class="h-14 w-14 rounded object-cover"
                                >
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
            @php($price = $variant?->prices->first())
            @if($price)
                <p class="mt-6 text-2xl font-medium">{{ $price->priceIncTax()->formatted() }}</p>
                @if((int) ($price->compare_price?->value ?? 0) > 0)
                    <p class="text-sm text-neutral-500 line-through">{{ $price->comparePriceIncTax()->formatted() }}</p>
                @endif
                <p class="mt-1 text-xs text-neutral-500">{{ __('etic.storefront.totals.tax_included') }}</p>
            @endif
            <form
                method="post"
                action="{{ route('cart.add') }}"
                class="mt-6 space-y-3"
                data-etic-add-to-cart
                data-product-name="{{ $product->translateAttribute('name') }}"
                data-product-image="{{ \App\Etic\Media\ProductImage::url($product, 'large') }}"
                data-cart-url="{{ route('cart.show') }}"
            >
                @csrf
                @if($product->variants->count() > 1)
                    <label class="block text-sm">{{ __('etic.storefront.product.variant') }}
                        <select name="variant_id" class="mt-1 w-full rounded border px-3 py-2">
                            @foreach($product->variants as $item)
                                <option value="{{ $item->id }}">
                                    {{ $item->values->map(fn ($value) => $value->translate('name'))->filter()->implode(' / ') ?: $item->sku }}
                                    — stok {{ $item->stock }}
                                </option>
                            @endforeach
                        </select>
                    </label>
                @else
                    <input type="hidden" name="variant_id" value="{{ $variant?->id }}">
                @endif
                <label class="block text-sm">Adet
                    <input type="number" name="quantity" value="1" min="1" class="mt-1 w-24 rounded border px-3 py-2">
                </label>
                <button class="etic-btn">Sepete ekle</button>
            </form>
        </div>
    </div>
</x-storefront-layout>
