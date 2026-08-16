<x-storefront-layout>
    @php
        $lineCount = (int) $cart->lines->sum('quantity');
        $subtotal = (int) ($cart->subTotal?->value ?? 0);
        $rates = app(\App\Etic\Integrations\Shipping\ShippingRates::class)->all();
        $threshold = null;
        foreach ($rates as $index => $rate) {
            if ((int) ($rate['price'] ?? 0) !== 0) {
                continue;
            }

            $previous = $rates[$index - 1] ?? null;
            $threshold = is_array($previous) ? ($previous['max_subtotal'] ?? null) : 0;
            break;
        }
        $shippingFree = $threshold === null || $subtotal > (int) $threshold;
    @endphp

    <section class="etic-cart">
        <ol class="etic-cart__steps" aria-label="Ödeme adımları">
            <li class="is-active"><span>1</span> Sepet</li>
            <li><span>2</span> Ödeme</li>
        </ol>

        @if($cart->lines->isEmpty())
            <div class="etic-cart__empty">
                <h1>Sepetiniz boş</h1>
                <p>Koleksiyondan bir ürün eklediğinizde burada görünecek.</p>
                <a href="{{ route('catalog') }}" class="etic-cart__cta">Alışverişe devam et</a>
            </div>
        @else
            <div class="etic-cart__layout">
                <div class="etic-cart__list">
                    <header class="etic-cart__list-head">
                        <h1>Sepetiniz ({{ $lineCount }} ürün)</h1>
                    </header>
                    <div class="etic-cart__lines">
                        @foreach($cart->lines as $line)
                            @php
                                $product = $line->purchasable?->product;
                                $name = $product?->translateAttribute('name') ?: $line->purchasable?->sku;
                                $slug = $product?->defaultUrl?->slug;
                                $meta = $line->purchasable?->values
                                    ? $line->purchasable->values->map(fn ($value) => $value->translate('name'))->filter()->implode(' / ')
                                    : null;
                                $unit = $line->unitPriceInclTax ?? $line->unitPrice;
                                $thumb = \App\Etic\Media\ProductImage::url($line->purchasable, 'small');
                            @endphp
                            <article class="etic-cart__line">
                                <div class="etic-cart__thumb">
                                    @if($slug)
                                        <a href="{{ route('product', $slug) }}" aria-label="{{ $name }}">
                                            @if($thumb)
                                                <img src="{{ $thumb }}" alt="{{ $name }}" width="84" height="84" loading="lazy" decoding="async">
                                            @else
                                                <span aria-hidden="true"></span>
                                            @endif
                                        </a>
                                    @elseif($thumb)
                                        <img src="{{ $thumb }}" alt="{{ $name }}" width="84" height="84" loading="lazy" decoding="async">
                                    @else
                                        <span aria-hidden="true"></span>
                                    @endif
                                </div>
                                <div class="etic-cart__line-body">
                                    <div class="etic-cart__line-copy">
                                        @if($slug)
                                            <a href="{{ route('product', $slug) }}"><h2 class="etic-cart__name">{{ $name }}</h2></a>
                                        @else
                                            <h2 class="etic-cart__name">{{ $name }}</h2>
                                        @endif
                                        @if($meta)
                                            <p class="etic-cart__meta">{{ $meta }}</p>
                                        @endif
                                        @if($unit)
                                            <p class="etic-cart__unit">{{ $unit->formatted() }}</p>
                                        @endif
                                    </div>
                                    <div class="etic-cart__line-tools">
                                        <form method="post" action="{{ route('cart.update') }}" class="etic-cart__qty" aria-label="Adet">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="line_id" value="{{ $line->id }}">
                                            <button type="submit" name="quantity" value="{{ max(1, $line->quantity - 1) }}" @disabled($line->quantity <= 1) aria-label="Azalt">
                                                <svg viewBox="0 0 16 16" aria-hidden="true"><path d="M3 8h10" fill="none" stroke="currentColor" stroke-width="1.4" /></svg>
                                            </button>
                                            <span>{{ $line->quantity }}</span>
                                            <button type="submit" name="quantity" value="{{ $line->quantity + 1 }}" aria-label="Artır">
                                                <svg viewBox="0 0 16 16" aria-hidden="true"><path d="M8 3v10M3 8h10" fill="none" stroke="currentColor" stroke-width="1.4" /></svg>
                                            </button>
                                        </form>
                                        <form method="post" action="{{ route('cart.remove') }}">
                                            @csrf
                                            @method('DELETE')
                                            <input type="hidden" name="line_id" value="{{ $line->id }}">
                                            <button type="submit" class="etic-cart__remove" aria-label="Ürünü kaldır">
                                                <svg viewBox="0 0 20 20" aria-hidden="true">
                                                    <path d="M4.5 6.2h11M8.2 6.2V4.8A1.3 1.3 0 0 1 9.5 3.5h1a1.3 1.3 0 0 1 1.3 1.3v1.4M7.2 8.1l.4 7.1M12.8 8.1l-.4 7.1M6.1 6.2l.5 9.2a1.4 1.4 0 0 0 1.4 1.3h4a1.4 1.4 0 0 0 1.4-1.3l.5-9.2" fill="none" stroke="currentColor" stroke-width="1.35" stroke-linecap="round" stroke-linejoin="round" />
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>
                    <a href="{{ route('catalog') }}" class="etic-cart__continue">← Alışverişe devam et</a>
                </div>
                <aside class="etic-cart__aside">
                    <div class="etic-cart__summary">
                        <h2>Sipariş özeti</h2>
                        <dl class="etic-cart__totals">
                            <div>
                                <dt>{{ __('etic.storefront.totals.subtotal') }}</dt>
                                <dd>{{ $cart->subTotal?->formatted() }}</dd>
                            </div>
                            @if((int) ($cart->discountTotal?->value ?? 0) > 0)
                                <div class="is-discount">
                                    <dt>{{ __('etic.storefront.totals.discount') }}@if($cart->coupon_code) ({{ $cart->coupon_code }})@endif</dt>
                                    <dd>− {{ $cart->discountTotal?->formatted() }}</dd>
                                </div>
                            @endif
                            <div>
                                <dt>{{ __('etic.storefront.totals.shipping') }}</dt>
                                <dd @class(['is-free' => $shippingFree])>{{ $shippingFree ? 'Ücretsiz' : 'Ödeme adımında' }}</dd>
                            </div>
                            <div class="is-total">
                                <dt>Genel toplam</dt>
                                <dd>{{ $cart->total?->formatted() }}</dd>
                            </div>
                        </dl>
                        @include('theme::partials.coupon-form', ['cart' => $cart, 'embedded' => true])
                        <a href="{{ route('checkout.show') }}" class="etic-cart__cta">
                            <svg viewBox="0 0 20 20" aria-hidden="true">
                                <rect x="4.5" y="9" width="11" height="7.5" rx="1.4" fill="none" stroke="currentColor" stroke-width="1.4" />
                                <path d="M7 9V7.2A3 3 0 0 1 10 4.2 3 3 0 0 1 13 7.2V9" fill="none" stroke="currentColor" stroke-width="1.4" />
                            </svg>
                            Güvenli ödemeye geç
                        </a>
                        <p class="etic-cart__ssl">256-bit SSL şifreleme ile güvende</p>
                    </div>
                </aside>
            </div>
            <div class="etic-cart__dock">
                <div>
                    <span>{{ __('etic.storefront.totals.total') }}</span>
                    <strong>{{ $cart->total?->formatted() }}</strong>
                </div>
                <a href="{{ route('checkout.show') }}">Ödemeye geç</a>
            </div>
        @endif
    </section>
</x-storefront-layout>
