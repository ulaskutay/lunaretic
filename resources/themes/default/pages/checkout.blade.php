<x-storefront-layout>
    @php
        $shippingFree = $shippingOptions->contains(fn ($option) => (int) $option->getPrice()->value === 0);
        $selectedPayment = old('payment', 'iyzico');
    @endphp

    <section class="etic-checkout" data-etic-checkout>
        <div class="etic-checkout__top etic-checkout__top--compact">
            <p class="etic-checkout__secure">
                <svg viewBox="0 0 20 20" aria-hidden="true">
                    <rect x="4.5" y="9" width="11" height="7.5" rx="1.4" fill="none" stroke="currentColor" stroke-width="1.4" />
                    <path d="M7 9V7.2A3 3 0 0 1 10 4.2 3 3 0 0 1 13 7.2V9" fill="none" stroke="currentColor" stroke-width="1.4" />
                </svg>
                Güvenli ödeme
            </p>
        </div>

        <form method="post" action="{{ route('checkout.place') }}" class="etic-checkout__layout" data-checkout-form>
            @csrf
            <input type="hidden" name="payment" value="{{ $selectedPayment }}" data-payment-input>
            <input type="hidden" name="payment_token" value="{{ old('payment_token', 'test-token') }}">

            <div class="etic-checkout__main">
                <section class="etic-checkout__card">
                    <h2 class="etic-checkout__card-title">
                        <svg viewBox="0 0 20 20" aria-hidden="true">
                            <path d="M10 17.2s-5.2-3.4-5.2-7.4a3.2 3.2 0 1 1 5.1-2.6A3.2 3.2 0 1 1 15.2 9.8c0 4-5.2 7.4-5.2 7.4Z" fill="none" stroke="currentColor" stroke-width="1.35" />
                        </svg>
                        Teslimat adresi
                    </h2>
                    <div class="etic-checkout__fields">
                        <div class="etic-checkout__row">
                            <label class="etic-checkout__field">
                                <span class="etic-checkout__label">Ad</span>
                                <input name="first_name" value="{{ old('first_name') }}" required class="etic-checkout__input">
                            </label>
                            <label class="etic-checkout__field">
                                <span class="etic-checkout__label">Soyad</span>
                                <input name="last_name" value="{{ old('last_name') }}" required class="etic-checkout__input">
                            </label>
                        </div>
                        <label class="etic-checkout__field">
                            <span class="etic-checkout__label">E-posta</span>
                            <input type="email" name="email" value="{{ old('email') }}" required class="etic-checkout__input">
                        </label>
                        <label class="etic-checkout__field">
                            <span class="etic-checkout__label">Telefon</span>
                            <input name="phone" value="{{ old('phone') }}" required class="etic-checkout__input">
                        </label>
                        <label class="etic-checkout__field">
                            <span class="etic-checkout__label">Adres</span>
                            <input name="line_one" value="{{ old('line_one') }}" required class="etic-checkout__input">
                        </label>
                        <div class="etic-checkout__row">
                            <label class="etic-checkout__field">
                                <span class="etic-checkout__label">İl</span>
                                <input name="city" value="{{ old('city') }}" required class="etic-checkout__input">
                            </label>
                            <label class="etic-checkout__field">
                                <span class="etic-checkout__label">İlçe</span>
                                <input name="state" value="{{ old('state') }}" class="etic-checkout__input">
                            </label>
                        </div>
                        <label class="etic-checkout__field">
                            <span class="etic-checkout__label">Posta kodu</span>
                            <input name="postcode" value="{{ old('postcode') }}" class="etic-checkout__input">
                        </label>
                        <label class="etic-checkout__field">
                            <span class="etic-checkout__label">Sipariş notu</span>
                            <textarea name="notes" rows="3" class="etic-checkout__input etic-checkout__textarea">{{ old('notes') }}</textarea>
                        </label>
                        @if($shippingOptions->isNotEmpty())
                            <fieldset class="etic-checkout__shipping">
                                <legend class="etic-checkout__label">Kargo</legend>
                                @foreach($shippingOptions as $option)
                                    <label class="etic-checkout__shipping-option">
                                        <span class="etic-checkout__shipping-copy">
                                            <input type="radio" name="shipping" value="{{ $option->getIdentifier() }}" @checked($loop->first || old('shipping') === $option->getIdentifier())>
                                            {{ $option->getName() }}
                                        </span>
                                        <span @class(['etic-checkout__shipping-free' => (int) $option->getPrice()->value === 0])>
                                            {{ (int) $option->getPrice()->value === 0 ? 'Ücretsiz' : $option->getPrice()->formatted() }}
                                        </span>
                                    </label>
                                @endforeach
                            </fieldset>
                        @endif
                    </div>
                </section>

                <section class="etic-checkout__card">
                    <h2 class="etic-checkout__card-title">
                        <svg viewBox="0 0 20 20" aria-hidden="true">
                            <path d="M4 6.5h12v9H4z" fill="none" stroke="currentColor" stroke-width="1.35" />
                            <path d="M7 4.5h6v2H7z" fill="none" stroke="currentColor" stroke-width="1.35" />
                            <path d="M7 11h6M7 13.5h4" fill="none" stroke="currentColor" stroke-width="1.35" stroke-linecap="round" />
                        </svg>
                        Fatura bilgileri
                    </h2>
                    @include('etic.checkout-billing')
                </section>

                <section class="etic-checkout__card">
                    <h2 class="etic-checkout__card-title">Ödeme yöntemi</h2>
                    <div class="etic-checkout__pay-tabs" role="tablist" aria-label="Ödeme yöntemi">
                        <button type="button" class="etic-checkout__pay-tab" data-pay-tab="iyzico" data-pay-driver="iyzico" role="tab" aria-selected="false">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="2.5" y="5.5" width="19" height="13" rx="2" fill="none" stroke="currentColor" stroke-width="1.5" /><path d="M2.5 10h19" fill="none" stroke="currentColor" stroke-width="1.5" /></svg>
                            <span>Kredi kartı</span>
                        </button>
                        <button type="button" class="etic-checkout__pay-tab" data-pay-tab="havale" data-pay-driver="cash-in-hand" role="tab" aria-selected="false">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 10h18v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-8Z" fill="none" stroke="currentColor" stroke-width="1.5" /><path d="M7 10V7a2 2 0 0 1 2-2h6a2 2 0 0 1 2 2v3" fill="none" stroke="currentColor" stroke-width="1.5" /></svg>
                            <span>Havale / EFT</span>
                        </button>
                        <button type="button" class="etic-checkout__pay-tab" data-pay-tab="kapida" data-pay-driver="cash-in-hand" role="tab" aria-selected="false">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 7h11v10H3zM14 10h4l3 3v4h-7V10Z" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round" /><circle cx="7.5" cy="18" r="1.5" fill="currentColor" /><circle cx="17.5" cy="18" r="1.5" fill="currentColor" /></svg>
                            <span>Kapıda ödeme</span>
                        </button>
                    </div>

                    <div class="etic-checkout__pay-panel" data-pay-panel="iyzico" role="tabpanel">
                        <label class="etic-checkout__field">
                            <span class="etic-checkout__label">Kart üzerindeki isim</span>
                            <input name="card_name" autocomplete="cc-name" class="etic-checkout__input">
                        </label>
                        <label class="etic-checkout__field">
                            <span class="etic-checkout__label">Kart numarası</span>
                            <input name="card_number" inputmode="numeric" autocomplete="cc-number" placeholder="•••• •••• •••• ••••" class="etic-checkout__input">
                        </label>
                        <div class="etic-checkout__row">
                            <label class="etic-checkout__field">
                                <span class="etic-checkout__label">Son kullanma (AA/YY)</span>
                                <input name="card_expiry" autocomplete="cc-exp" placeholder="AA/YY" class="etic-checkout__input">
                            </label>
                            <label class="etic-checkout__field">
                                <span class="etic-checkout__label">CVC</span>
                                <input name="card_cvc" inputmode="numeric" autocomplete="cc-csc" class="etic-checkout__input">
                            </label>
                        </div>
                        <label class="etic-checkout__checkbox">
                            <input type="checkbox">
                            Sonraki alışverişlerim için bilgilerimi güvenle kaydet.
                        </label>
                    </div>
                    <div class="etic-checkout__pay-note" data-pay-panel="havale" hidden>
                        Siparişi tamamladıktan sonra havale/EFT bilgileri e-posta ile iletilir. Ödeme onaylanınca kargoya verilir.
                    </div>
                    <div class="etic-checkout__pay-note" data-pay-panel="kapida" hidden>
                        Teslimatta nakit veya kapıda ödeme seçeneği sunulur. Tutar kargo görevlisine ödenir.
                    </div>
                </section>
            </div>

            <aside class="etic-checkout__aside">
                <div class="etic-checkout__summary">
                    <h2>Sipariş özeti</h2>
                    <ul class="etic-checkout__items">
                        @foreach($cart->lines as $line)
                            @php
                                $product = $line->purchasable?->product;
                                $name = $product?->translateAttribute('name') ?: $line->purchasable?->sku;
                                $thumb = \App\Etic\Media\ProductImage::url($line->purchasable, 'small');
                            @endphp
                            <li class="etic-checkout__item">
                                <div class="etic-checkout__item-thumb">
                                    @if($thumb)
                                        <img src="{{ $thumb }}" alt="" width="56" height="56" loading="lazy" decoding="async">
                                    @endif
                                </div>
                                <div class="etic-checkout__item-copy">
                                    <p class="etic-checkout__item-name">{{ $name }}</p>
                                    <p class="etic-checkout__item-qty">Adet: {{ $line->quantity }}</p>
                                </div>
                                <span class="etic-checkout__item-price">{{ $line->total?->formatted() }}</span>
                            </li>
                        @endforeach
                    </ul>
                    <dl class="etic-checkout__totals">
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
                            <dd @class(['is-free' => $shippingFree])>{{ $shippingFree ? 'Ücretsiz' : ($cart->shippingTotal?->formatted() ?: 'Seçime göre') }}</dd>
                        </div>
                        <div class="is-total">
                            <dt>Genel toplam</dt>
                            <dd>{{ $cart->total?->formatted() }}</dd>
                        </div>
                    </dl>
                    @if((int) ($cart->taxTotal?->value ?? 0) > 0)
                        <p class="etic-checkout__tax">{{ __('etic.storefront.totals.tax_included_amount', ['amount' => $cart->taxTotal->formatted()]) }}</p>
                    @endif
                    @include('theme::partials.coupon-form', ['cart' => $cart, 'embedded' => true])
                    <button type="submit" class="etic-checkout__cta">
                        <svg viewBox="0 0 20 20" aria-hidden="true">
                            <rect x="4.5" y="9" width="11" height="7.5" rx="1.4" fill="none" stroke="currentColor" stroke-width="1.4" />
                            <path d="M7 9V7.2A3 3 0 0 1 10 4.2 3 3 0 0 1 13 7.2V9" fill="none" stroke="currentColor" stroke-width="1.4" />
                        </svg>
                        Siparişi tamamla
                    </button>
                    <p class="etic-checkout__ssl">256-bit SSL şifreleme ile korunmaktadır.</p>
                </div>
            </aside>
        </form>
    </section>

    <script>
        (() => {
            const root = document.querySelector('[data-etic-checkout]');
            if (!root) return;

            const input = root.querySelector('[data-payment-input]');
            const tabs = [...root.querySelectorAll('[data-pay-tab]')];
            const panels = [...root.querySelectorAll('[data-pay-panel]')];

            function select(tab) {
                const name = tab.dataset.payTab;
                input.value = tab.dataset.payDriver;

                tabs.forEach((item) => {
                    const active = item === tab;
                    item.classList.toggle('is-active', active);
                    item.setAttribute('aria-selected', active ? 'true' : 'false');
                });

                panels.forEach((panel) => {
                    panel.hidden = panel.dataset.payPanel !== name;
                });
            }

            tabs.forEach((tab) => tab.addEventListener('click', () => select(tab)));
            select(tabs.find((tab) => tab.dataset.payDriver === input.value) || tabs[0]);

            const billingSame = root.querySelector('[data-billing-same]');
            const billingPanel = root.querySelector('[data-billing-panel]');
            const corporateToggle = root.querySelector('[data-billing-corporate]');
            const corporatePanel = root.querySelector('[data-corporate-panel]');
            const billingFields = [...root.querySelectorAll('[data-billing-field]')];
            const corporateFields = [...root.querySelectorAll('[data-corporate-field]')];

            function syncBillingFields() {
                const same = billingSame?.checked ?? true;
                billingPanel.hidden = same;
                billingFields.forEach((field) => {
                    field.disabled = same;
                    field.required = !same;
                });
            }

            function syncCorporateFields() {
                const corporate = corporateToggle?.checked ?? false;
                corporatePanel.hidden = !corporate;
                corporateFields.forEach((field) => {
                    field.disabled = !corporate;
                    field.required = corporate;
                });
            }

            billingSame?.addEventListener('change', syncBillingFields);
            corporateToggle?.addEventListener('change', syncCorporateFields);
            syncBillingFields();
            syncCorporateFields();
        })();
    </script>
</x-storefront-layout>
