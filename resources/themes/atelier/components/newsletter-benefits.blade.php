@php
    $title = theme_setting('newsletter_title', 'Detayları kaçırma');
    $description = theme_setting('newsletter_description', 'Yeni koleksiyonlar, özel teklifler ve ilham veren seçkiler e-posta kutunda.');
    $placeholder = theme_setting('newsletter_placeholder', 'e-posta adresiniz');
    $cta = theme_setting('newsletter_cta', 'Katıl');
    $benefits = [
        [
            'title' => theme_setting('benefit_returns_title', 'Kolay iade'),
            'description' => theme_setting('benefit_returns_description', 'Siparişinizi 30 gün içinde kolayca iade edin.'),
            'icon' => 'returns',
        ],
        [
            'title' => theme_setting('benefit_shipping_title', 'Hızlı gönderim'),
            'description' => theme_setting('benefit_shipping_description', 'Siparişiniz özenle hazırlanır ve hızla kargoya verilir.'),
            'icon' => 'shipping',
        ],
        [
            'title' => theme_setting('benefit_support_title', 'Müşteri desteği'),
            'description' => theme_setting('benefit_support_description', 'Sorularınız için ekibimiz her zaman yanınızda.'),
            'icon' => 'support',
        ],
    ];
@endphp

<section class="etic-newsletter-benefits" aria-labelledby="atelier-newsletter-title">
    <div class="etic-newsletter-benefits__intro">
        <h2 id="atelier-newsletter-title">{{ $title }}</h2>
        <p>{{ $description }}</p>
        <form class="etic-newsletter-benefits__form" data-etic-newsletter>
            <label class="sr-only" for="atelier-benefits-email">E-posta adresi</label>
            <input id="atelier-benefits-email" type="email" name="email" required placeholder="{{ $placeholder }}">
            <button type="submit">{{ $cta }}</button>
        </form>
        <p class="etic-newsletter-benefits__status" data-newsletter-status aria-live="polite"></p>
    </div>

    <div class="etic-newsletter-benefits__grid">
        @foreach($benefits as $benefit)
            <article class="etic-newsletter-benefits__item">
                <div class="etic-newsletter-benefits__icon" aria-hidden="true">
                    @if($benefit['icon'] === 'returns')
                        <svg viewBox="0 0 32 32"><path d="M8 10h15l3 5v10H8zM8 15h18M12 10V7h8v3M5 20a6 6 0 1 0 2-4.5M5 14v6h6" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    @elseif($benefit['icon'] === 'shipping')
                        <svg viewBox="0 0 32 32"><path d="M4 8h15v13H4zM19 13h5l4 5v3h-9zM9 25a3 3 0 1 0 0-6 3 3 0 0 0 0 6ZM23 25a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    @else
                        <svg viewBox="0 0 32 32"><path d="M6 17v-2a10 10 0 0 1 20 0v2M6 17v6h4v-8H8a2 2 0 0 0-2 2ZM26 17v6h-4v-8h2a2 2 0 0 1 2 2ZM22 24c-1 2-3 3-6 3" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    @endif
                </div>
                <div>
                    <h3>{{ $benefit['title'] }}</h3>
                    <p>{{ $benefit['description'] }}</p>
                </div>
            </article>
        @endforeach
    </div>
</section>
