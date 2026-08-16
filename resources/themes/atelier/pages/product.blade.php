<x-storefront-layout :canonical="$canonical ?? null" :schema-json="$schemaJson ?? null" :seo-title="$product->translateAttribute('name')">
    @php
        $gallery = \App\Etic\Media\ProductImage::gallery($product);
        $galleryUrls = \App\Etic\Media\ProductImage::galleryUrls($product);
        $collection = $product->collections->first();
        $price = $variant?->prices->first();
        $selectedValues = $variant?->values->keyBy(fn ($value) => $value->option?->handle) ?? collect();
        $optionLabels = [
            'color' => __('etic.storefront.filters.color'),
            'size' => __('etic.storefront.filters.size'),
        ];
    @endphp
    <article class="etic-pdp" data-etic-pdp>
        <script type="application/json" data-pdp-variants>@json($variantsPayload ?? [])</script>
        <nav class="etic-pdp__crumbs" aria-label="Sayfa yolu">
            <a href="{{ route('home') }}">{{ __('etic.storefront.product.home') }}</a>
            <span>/</span>
            <a href="{{ route('catalog') }}">{{ __('etic.storefront.product.collections') }}</a>
            @if($collection?->defaultUrl?->slug)
                <span>/</span>
                <a href="{{ route('collection', $collection->defaultUrl->slug) }}">{{ $collection->translateAttribute('name') }}</a>
            @endif
        </nav>
        <div class="etic-pdp__media">
            <div class="etic-pdp__gallery" data-pdp-gallery>
                <script type="application/json" data-pdp-gallery-images>@json($galleryUrls)</script>
                <div class="etic-pdp__stage" data-pdp-stage>
                    <button type="button" class="etic-pdp__inspect" data-pdp-open-lightbox aria-label="Görseli incele">
                        <x-theme::product-image :model="$product" conversion="large" :priority="true" :alt="$product->translateAttribute('name')" data-pdp-image data-etic-cart-source />
                    </button>
                    @if($gallery->count() > 1)
                        <button type="button" class="etic-pdp__nav is-prev" data-pdp-prev aria-label="Önceki görsel">‹</button>
                        <button type="button" class="etic-pdp__nav is-next" data-pdp-next aria-label="Sonraki görsel">›</button>
                    @endif
                    <span class="etic-pdp__inspect-hint">İncele</span>
                </div>
                @if($gallery->count() > 1)
                    <div class="etic-pdp__thumbs">
                        @foreach($gallery as $index => $media)
                            <button
                                type="button"
                                class="{{ $index === 0 ? 'is-active' : '' }}"
                                data-pdp-thumb
                                data-index="{{ $index }}"
                                aria-label="{{ $product->translateAttribute('name') }} {{ $index + 1 }}"
                            >
                                <img
                                    src="{{ $media->hasGeneratedConversion('small') ? $media->getUrl('small') : $media->getUrl() }}"
                                    alt=""
                                >
                            </button>
                        @endforeach
                    </div>
                @endif
                <div class="etic-pdp-lightbox" data-pdp-lightbox hidden role="dialog" aria-modal="true" aria-label="{{ $product->translateAttribute('name') }}">
                    <button type="button" class="etic-pdp-lightbox__close" data-pdp-lightbox-close aria-label="Kapat">×</button>
                    @if($gallery->count() > 1)
                        <button type="button" class="etic-pdp-lightbox__nav is-prev" data-pdp-prev aria-label="Önceki görsel">‹</button>
                    @endif
                    <div class="etic-pdp-lightbox__frame" data-pdp-lightbox-frame>
                        <img src="{{ $galleryUrls->first() }}" alt="{{ $product->translateAttribute('name') }}" data-pdp-lightbox-image>
                    </div>
                    @if($gallery->count() > 1)
                        <button type="button" class="etic-pdp-lightbox__nav is-next" data-pdp-next aria-label="Sonraki görsel">›</button>
                    @endif
                    <p class="etic-pdp-lightbox__meta">
                        @if($gallery->count() > 1)
                            <strong data-pdp-lightbox-count>1 / {{ $gallery->count() }}</strong>
                        @endif
                        <span data-pdp-lightbox-hint>Yakınlaştırmak için tıklayın</span>
                    </p>
                </div>
            </div>
        </div>
        <div class="etic-pdp__info">
            @if($product->brand?->name)
                <p class="etic-pdp__brand">{{ $product->brand->name }}</p>
            @endif
            <h1 class="etic-pdp__title">{{ $product->translateAttribute('name') }}</h1>
            @php($hasOptionPicker = ($options ?? collect())->isNotEmpty())
            @php($needsVariantSelect = ! $hasOptionPicker && $product->variants->count() > 1)
            <form
                method="post"
                action="{{ route('cart.add') }}"
                class="etic-pdp__form"
                data-etic-add-to-cart
                data-product-name="{{ $product->translateAttribute('name') }}"
                data-product-image="{{ $galleryUrls->first() }}"
                data-product-price="{{ $price?->priceIncTax()->formatted() }}"
                data-cart-url="{{ route('cart.show') }}"
            >
                @csrf
                @unless($needsVariantSelect)
                    <input type="hidden" name="variant_id" value="{{ $variant?->id }}" data-pdp-variant>
                @endunless
                <input type="hidden" name="quantity" value="1">
                <div class="etic-pdp__price" data-pdp-price @unless($price) hidden @endunless>
                    <strong data-pdp-price-current>{{ $price?->priceIncTax()->formatted() }}</strong>
                    @php($hasComparePrice = (int) ($price?->compare_price?->value ?? 0) > 0)
                    <s data-pdp-price-compare @unless($hasComparePrice) hidden @endunless>{{ $hasComparePrice ? $price->comparePriceIncTax()->formatted() : '' }}</s>
                    <span>{{ __('etic.storefront.totals.tax_included') }}</span>
                </div>
                @error('cart')
                    <p class="etic-pdp__error">{{ $message }}</p>
                @enderror
                @if(($colorVariants ?? collect())->count() > 1)
                    <fieldset class="etic-pdp__option">
                        <legend class="etic-pdp__option-label">{{ __('etic.storefront.filters.color') }}</legend>
                        <div class="etic-pdp__swatches">
                            @foreach($colorVariants as $item)
                                <a
                                    href="{{ route('product', $item['slug']) }}"
                                    class="{{ $item['active'] ? 'is-active' : '' }}"
                                    title="{{ $item['color'] ?: $item['name'] }}"
                                >
                                    <img src="{{ $item['image'] }}" alt="{{ $item['color'] ?: $item['name'] }}">
                                </a>
                            @endforeach
                        </div>
                    </fieldset>
                @endif
                @if($hasOptionPicker)
                    @foreach($options as $handle => $values)
                        <fieldset class="etic-pdp__option">
                            <legend class="etic-pdp__option-label">{{ $optionLabels[$handle] ?? $handle }}</legend>
                            @if($handle === 'size')
                                @include('theme::partials.size-chart')
                            @endif
                            <div class="{{ $handle === 'size' ? 'etic-pdp__sizes' : 'etic-pdp__choices' }}">
                                @foreach($values as $value)
                                    <button
                                        type="button"
                                        data-pdp-option="{{ $handle }}"
                                        data-value-id="{{ $value['id'] }}"
                                        class="{{ (int) $selectedValues->get($handle)?->id === (int) $value['id'] ? 'is-active' : '' }}"
                                    >{{ $value['name'] }}</button>
                                @endforeach
                            </div>
                        </fieldset>
                    @endforeach
                @elseif($needsVariantSelect)
                    <label class="etic-pdp__fallback">
                        {{ __('etic.storefront.product.variant') }}
                        <select name="variant_id">
                            @foreach($product->variants as $item)
                                <option value="{{ $item->id }}" @selected($item->id === $variant?->id)>
                                    {{ $item->values->map(fn ($value) => $value->translate('name'))->filter()->implode(' / ') ?: $item->sku }}
                                </option>
                            @endforeach
                        </select>
                    </label>
                @endif
                <ul class="etic-pdp__meta">
                    <li>
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <circle cx="12" cy="12" r="9" fill="none" stroke="currentColor" stroke-width="1.4" />
                            <path d="M3.6 9.5h16.8M3.6 14.5h16.8M12 3c2.6 3.2 3.9 6.4 3.9 9s-1.3 5.8-3.9 9c-2.6-3.2-3.9-6.4-3.9-9s1.3-5.8 3.9-9Z" fill="none" stroke="currentColor" stroke-width="1.4" />
                        </svg>
                        {{ __('etic.storefront.product.free_shipping') }}
                    </li>
                    <li
                        data-pdp-stock
                        data-in="{{ __('etic.storefront.product.in_stock') }}"
                        data-out="{{ __('etic.storefront.product.out_of_stock') }}"
                    >
                        <span class="{{ $variant?->canBeFulfilledAtQuantity(1) ? 'is-ready' : 'is-empty' }}"></span>
                        <span data-pdp-stock-label>{{ $variant?->canBeFulfilledAtQuantity(1) ? __('etic.storefront.product.in_stock') : __('etic.storefront.product.out_of_stock') }}</span>
                    </li>
                </ul>
                <div class="etic-pdp__actions">
                    <button type="submit" name="intent" value="cart" class="etic-pdp__cart" data-pdp-cart>
                        {{ __('etic.storefront.product.add_to_cart') }}
                    </button>
                    <button type="submit" name="intent" value="buy" class="etic-pdp__buy" data-pdp-buy>
                        {{ __('etic.storefront.product.buy_now') }}
                    </button>
                </div>
            </form>
            @if($product->translateAttribute('description'))
                <div class="etic-pdp__copy">{!! $product->translateAttribute('description') !!}</div>
            @endif
            <div class="etic-pdp__accordion">
                <details>
                    <summary>{{ __('etic.storefront.product.shipping') }}</summary>
                    <div class="etic-pdp__accordion-body">
                        @if(filled($shippingPage?->content))
                            {!! $shippingPage->content !!}
                        @else
                            <p>{{ __('etic.storefront.product.shipping_fallback') }}</p>
                        @endif
                        <a href="{{ route('page', 'kargo') }}">{{ __('etic.storefront.product.shipping_link') }}</a>
                    </div>
                </details>
                <details>
                    <summary>{{ __('etic.storefront.product.ask') }}</summary>
                    <form class="etic-pdp__ask" data-etic-pdp-question data-product="{{ $product->translateAttribute('name') }}">
                        <p>{{ __('etic.storefront.product.ask_intro') }}</p>
                        <label>
                            {{ __('etic.storefront.product.ask_name') }}
                            <input name="name" type="text" required autocomplete="name">
                        </label>
                        <label>
                            {{ __('etic.storefront.product.ask_email') }}
                            <input name="email" type="email" required autocomplete="email">
                        </label>
                        <label>
                            {{ __('etic.storefront.product.ask_message') }}
                            <textarea name="message" rows="4" required>{{ __('etic.storefront.product.ask_prefix', ['product' => $product->translateAttribute('name')]) }}</textarea>
                        </label>
                        <button type="submit">{{ __('etic.storefront.product.ask_send') }}</button>
                        <p class="etic-pdp__ask-status" data-pdp-question-status aria-live="polite"></p>
                    </form>
                </details>
            </div>
        </div>
        @include('theme::partials.size-chart', ['mode' => 'dialog'])
    </article>
</x-storefront-layout>
