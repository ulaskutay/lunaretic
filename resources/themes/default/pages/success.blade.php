<x-storefront-layout
    seo-title="Siparişiniz alındı — {{ $eticStore->name() }}"
    robots="noindex,nofollow"
>
    @php
        $reference = $order->reference ?? $order->id;
        $shipping = $order->shippingAddress;
        $billing = $order->billingAddress;
        $lines = $order->productLines->filter(fn ($line) => filled($line->purchasable_id));
        $shippingFree = (int) ($order->shipping_total?->value ?? 0) === 0;
        $email = $shipping?->contact_email;
        $billingDiffers = $billing && $shipping && (
            $billing->line_one !== $shipping->line_one
            || $billing->city !== $shipping->city
            || $billing->company_name
            || $billing->tax_identifier
        );
        $taxOffice = data_get($billing?->meta, 'tax_office');

        $statusMessage = match ((string) $order->status) {
            \App\Etic\Orders\OrderStatusScenario::PAYMENT_OFFLINE => 'Siparişiniz kaydedildi. Kapıda ödeme veya havale talimatları e-posta ile iletilecektir.',
            \App\Etic\Orders\OrderStatusScenario::AWAITING_PAYMENT => 'Ödeme onayı bekleniyor. Onaylandığında siparişiniz hazırlanmaya başlanacaktır.',
            \App\Etic\Orders\OrderStatusScenario::PAYMENT_RECEIVED => 'Ödemeniz alındı. Siparişiniz kısa süre içinde hazırlanmaya başlanacaktır.',
            default => 'Sipariş detayları e-posta ile paylaşılacaktır. Hesabınızdan sipariş durumunu takip edebilirsiniz.',
        };
    @endphp

    <section class="etic-success">
        <header class="etic-success__hero">
            <div class="etic-success__icon" aria-hidden="true">
                <svg viewBox="0 0 48 48">
                    <circle cx="24" cy="24" r="22" fill="none" stroke="currentColor" stroke-width="2" />
                    <path d="M15 24.5 21 30.5 33 18" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </div>
            <p class="etic-success__kicker">Teşekkür ederiz</p>
            <h1 class="etic-success__title">Siparişiniz alındı</h1>
            <p class="etic-success__lead">
                Sipariş numaranız <strong>#{{ $reference }}</strong>
                @if($email)
                    · Onay e-postası <strong>{{ $email }}</strong> adresine gönderilecektir.
                @endif
            </p>
            <span class="etic-success__status">{{ $order->status_label }}</span>
        </header>

        <div class="etic-success__layout">
            <div class="etic-success__main">
                <section class="etic-success__card">
                    <h2 class="etic-success__card-title">Sonraki adımlar</h2>
                    <p class="etic-success__card-copy">{{ $statusMessage }}</p>
                    <ol class="etic-success__timeline">
                        <li class="is-done">
                            <span class="etic-success__timeline-dot" aria-hidden="true"></span>
                            <div>
                                <p class="etic-success__timeline-title">Sipariş onaylandı</p>
                                <p class="etic-success__timeline-copy">{{ $order->created_at?->translatedFormat('d F Y, H:i') }}</p>
                            </div>
                        </li>
                        <li>
                            <span class="etic-success__timeline-dot" aria-hidden="true"></span>
                            <div>
                                <p class="etic-success__timeline-title">Hazırlanıyor</p>
                                <p class="etic-success__timeline-copy">Ürünleriniz paketlenmeye başlandığında bilgilendirileceksiniz.</p>
                            </div>
                        </li>
                        <li>
                            <span class="etic-success__timeline-dot" aria-hidden="true"></span>
                            <div>
                                <p class="etic-success__timeline-title">Kargoya verildi</p>
                                <p class="etic-success__timeline-copy">Takip bilgisi e-posta ile paylaşılır.</p>
                            </div>
                        </li>
                    </ol>
                </section>

                @if($shipping)
                    <section class="etic-success__card">
                        <h2 class="etic-success__card-title">Teslimat adresi</h2>
                        <address class="etic-success__address">
                            <p class="etic-success__address-name">{{ trim($shipping->first_name.' '.$shipping->last_name) }}</p>
                            <p>{{ $shipping->line_one }}</p>
                            @if($shipping->line_two)
                                <p>{{ $shipping->line_two }}</p>
                            @endif
                            <p>{{ trim(collect([$shipping->state, $shipping->city, $shipping->postcode])->filter()->implode(', ')) }}</p>
                            @if($shipping->contact_phone)
                                <p class="etic-success__address-meta">{{ $shipping->contact_phone }}</p>
                            @endif
                        </address>
                    </section>
                @endif

                @if($billingDiffers && $billing)
                    <section class="etic-success__card">
                        <h2 class="etic-success__card-title">Fatura bilgileri</h2>
                        <address class="etic-success__address">
                            @if($billing->company_name)
                                <p class="etic-success__address-name">{{ $billing->company_name }}</p>
                            @endif
                            @if($taxOffice || $billing->tax_identifier)
                                <p class="etic-success__address-meta">
                                    @if($taxOffice){{ $taxOffice }}@endif
                                    @if($taxOffice && $billing->tax_identifier) · @endif
                                    @if($billing->tax_identifier){{ $billing->tax_identifier }}@endif
                                </p>
                            @endif
                            <p class="etic-success__address-name">{{ trim($billing->first_name.' '.$billing->last_name) }}</p>
                            <p>{{ $billing->line_one }}</p>
                            @if($billing->line_two)
                                <p>{{ $billing->line_two }}</p>
                            @endif
                            <p>{{ trim(collect([$billing->state, $billing->city, $billing->postcode])->filter()->implode(', ')) }}</p>
                        </address>
                    </section>
                @endif
            </div>

            <aside class="etic-success__aside">
                <div class="etic-success__summary">
                    <h2 class="etic-success__summary-title">Sipariş özeti</h2>
                    <ul class="etic-success__items">
                        @foreach($lines as $line)
                            @php
                                $thumb = \App\Etic\Media\ProductImage::url($line->purchasable, 'small');
                            @endphp
                            <li class="etic-success__item">
                                <div class="etic-success__item-thumb">
                                    @if($thumb)
                                        <img src="{{ $thumb }}" alt="" width="56" height="56" loading="lazy" decoding="async">
                                    @endif
                                </div>
                                <div class="etic-success__item-copy">
                                    <p class="etic-success__item-name">{{ $line->description }}</p>
                                    <p class="etic-success__item-qty">Adet: {{ $line->quantity }}</p>
                                </div>
                                <span class="etic-success__item-price">{{ $line->total?->formatted() }}</span>
                            </li>
                        @endforeach
                    </ul>
                    <dl class="etic-success__totals">
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
                        <p class="etic-success__tax">{{ __('etic.storefront.totals.tax_included_amount', ['amount' => $order->tax_total->formatted()]) }}</p>
                    @endif
                    <div class="etic-success__actions">
                        <a href="{{ route('catalog') }}" class="etic-success__cta">Alışverişe devam et</a>
                        @auth
                            <a href="{{ route('account') }}" class="etic-success__cta etic-success__cta--ghost">Hesabım</a>
                        @endauth
                    </div>
                </div>
            </aside>
        </div>
    </section>
</x-storefront-layout>
