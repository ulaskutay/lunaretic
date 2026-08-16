@php
    $reference = $order->reference ?? $order->id;
    $shipping = $order->shippingAddress;
    $billing = $order->billingAddress;
    $lines = $order->productLines->filter(fn ($line) => filled($line->purchasable_id));
    $shippingFree = (int) ($order->shipping_total?->value ?? 0) === 0;
    $billingDiffers = $billing && $shipping && (
        $billing->line_one !== $shipping->line_one
        || $billing->city !== $shipping->city
        || $billing->company_name
        || $billing->tax_identifier
    );
    $taxOffice = data_get($billing?->meta, 'tax_office');
    $tracking = \App\Etic\Integrations\Shipping\ShipmentTracking::fromMeta((array) $order->meta);
@endphp

<div class="etic-order-detail">
    <header class="etic-order-detail__head">
        <div>
            <p class="etic-order-detail__ref">#{{ $reference }}</p>
            <p class="etic-order-detail__date">{{ $order->created_at?->translatedFormat('d F Y, H:i') }}</p>
        </div>
        <span class="etic-order-detail__status">{{ $order->status_label }}</span>
    </header>

    <div class="etic-order-detail__layout">
        <div class="etic-order-detail__main">
            <section class="etic-order-detail__card">
                <h3 class="etic-order-detail__title">Ürünler</h3>
                <ul class="etic-order-detail__items">
                    @foreach($lines as $line)
                        @php
                            $thumb = \App\Etic\Media\ProductImage::url($line->purchasable, 'small');
                        @endphp
                        <li class="etic-order-detail__item">
                            <div class="etic-order-detail__thumb">
                                @if($thumb)
                                    <img src="{{ $thumb }}" alt="" width="56" height="56" loading="lazy" decoding="async">
                                @endif
                            </div>
                            <div class="etic-order-detail__copy">
                                <p class="etic-order-detail__name">{{ $line->description }}</p>
                                <p class="etic-order-detail__qty">Adet: {{ $line->quantity }}</p>
                            </div>
                            <span class="etic-order-detail__price">{{ $line->total?->formatted() }}</span>
                        </li>
                    @endforeach
                </ul>
            </section>

            @if($shipping)
                <section class="etic-order-detail__card">
                    <h3 class="etic-order-detail__title">Teslimat adresi</h3>
                    <address class="etic-order-detail__address">
                        <p class="etic-order-detail__address-name">{{ trim($shipping->first_name.' '.$shipping->last_name) }}</p>
                        <p>{{ $shipping->line_one }}</p>
                        @if($shipping->line_two)
                            <p>{{ $shipping->line_two }}</p>
                        @endif
                        <p>{{ trim(collect([$shipping->state, $shipping->city, $shipping->postcode])->filter()->implode(', ')) }}</p>
                        @if($shipping->contact_phone)
                            <p class="etic-order-detail__address-meta">{{ $shipping->contact_phone }}</p>
                        @endif
                    </address>
                </section>
            @endif

            @if($billingDiffers && $billing)
                <section class="etic-order-detail__card">
                    <h3 class="etic-order-detail__title">Fatura bilgileri</h3>
                    <address class="etic-order-detail__address">
                        @if($billing->company_name)
                            <p class="etic-order-detail__address-name">{{ $billing->company_name }}</p>
                        @endif
                        @if($taxOffice || $billing->tax_identifier)
                            <p class="etic-order-detail__address-meta">
                                @if($taxOffice){{ $taxOffice }}@endif
                                @if($taxOffice && $billing->tax_identifier) · @endif
                                @if($billing->tax_identifier){{ $billing->tax_identifier }}@endif
                            </p>
                        @endif
                        <p class="etic-order-detail__address-name">{{ trim($billing->first_name.' '.$billing->last_name) }}</p>
                        <p>{{ $billing->line_one }}</p>
                        @if($billing->line_two)
                            <p>{{ $billing->line_two }}</p>
                        @endif
                        <p>{{ trim(collect([$billing->state, $billing->city, $billing->postcode])->filter()->implode(', ')) }}</p>
                    </address>
                </section>
            @endif

            @if($tracking)
                <section class="etic-order-detail__card">
                    <h3 class="etic-order-detail__title">Kargo takibi</h3>
                    <p class="etic-order-detail__address-meta">
                        Takip no: <strong>{{ $tracking['tracking_number'] }}</strong>
                        @if($tracking['status'])
                            <br>{{ $tracking['status'] }}
                        @endif
                    </p>
                    @if($tracking['tracking_url'])
                        <p><a href="{{ $tracking['tracking_url'] }}" target="_blank" rel="noopener">{{ $tracking['carrier_label'] }}’da takip et</a></p>
                    @endif
                </section>
            @endif
        </div>

        <aside class="etic-order-detail__aside">
            <section class="etic-order-detail__card">
                <h3 class="etic-order-detail__title">Özet</h3>
                <dl class="etic-order-detail__totals">
                    <div>
                        <dt>{{ __('etic.storefront.totals.subtotal') }}</dt>
                        <dd>{{ $order->sub_total?->formatted() }}</dd>
                    </div>
                    @if((int) ($order->discount_total?->value ?? 0) > 0)
                        <div class="is-discount">
                            <dt>{{ __('etic.storefront.totals.discount') }}</dt>
                            <dd>− {{ $order->discount_total?->formatted() }}</dd>
                        </div>
                    @endif
                    <div>
                        <dt>{{ __('etic.storefront.totals.shipping') }}</dt>
                        <dd @class(['is-free' => $shippingFree])>{{ $shippingFree ? 'Ücretsiz' : $order->shipping_total?->formatted() }}</dd>
                    </div>
                    <div class="is-total">
                        <dt>{{ __('etic.storefront.totals.total') }}</dt>
                        <dd>{{ $order->total?->formatted() }}</dd>
                    </div>
                </dl>
                @if((int) ($order->tax_total?->value ?? 0) > 0)
                    <p class="etic-order-detail__tax">{{ __('etic.storefront.totals.tax_included_amount', ['amount' => $order->tax_total->formatted()]) }}</p>
                @endif
            </section>
        </aside>
    </div>
</div>
