@php
    $footerMenu = theme()->menu('footer');
    $links = $footerMenu?->items ?? collect();
    $logoText = theme()->logoText();
    $logo = theme()->logoUrl();
    $social = [
        'Instagram' => theme_setting('social_instagram'),
        'TikTok' => theme_setting('social_tiktok'),
        'Facebook' => theme_setting('social_facebook'),
    ];
    $whatsapp = theme_setting('social_whatsapp');
    $footerText = theme_setting('footer_text');
    $footerImage = theme()->footerImageUrl()
        ?: theme()->rightBannerImageUrl()
        ?: theme()->secondaryEditorialImageUrl();
    $newsletterKicker = theme_setting('newsletter_kicker', 'Haftalık bültenimiz');
    $newsletterTitle = theme_setting('newsletter_title', 'Detayları kaçırma');
    $newsletterDescription = theme_setting('newsletter_description', 'Yeni koleksiyonlar, özel teklifler ve ilham veren seçkiler e-posta kutunda.');
    $newsletterPlaceholder = theme_setting('newsletter_placeholder', 'e-posta adresiniz');
@endphp

<footer class="etic-footer">
    <div class="etic-footer__inner">
        <div class="etic-footer__main">
            <a href="{{ route('home') }}" class="etic-footer__logo">
                @if($logo)
                    <img src="{{ $logo }}" alt="{{ $logoText }}">
                @else
                    {{ $logoText }}
                @endif
            </a>

            <div class="etic-footer__columns">
                <div class="etic-footer__column">
                    <p class="etic-footer__label">Koleksiyonlar</p>
                    <a href="{{ route('catalog') }}">Tüm ürünler</a>
                    <a href="{{ route('catalog', ['sort' => 'best_selling']) }}">Çok satanlar</a>
                    <a href="{{ route('catalog', ['sort' => 'newest']) }}">Yeni gelenler</a>
                </div>

                <div class="etic-footer__column">
                    <p class="etic-footer__label">Yardımcı bağlantılar</p>
                    @forelse($links as $item)
                        <a href="{{ $item->url }}">{{ $item->label }}</a>
                        @foreach($item->children as $child)
                            <a href="{{ $child->url }}">{{ $child->label }}</a>
                        @endforeach
                    @empty
                        <a href="{{ route('page', 'hakkimizda') }}">Hakkımızda</a>
                        <a href="{{ route('page', 'gizlilik') }}">Gizlilik</a>
                        <a href="{{ route('page', 'iade') }}">İade</a>
                        <a href="{{ route('page', 'kullanim-kosullari') }}">Koşullar</a>
                    @endforelse
                </div>

                <div class="etic-footer__column etic-footer__about">
                    <p class="etic-footer__label">Hakkımızda</p>
                    <p>{{ $footerText ?: 'Zamansız tasarımlar, özenli detaylar ve günlük yaşama eşlik eden seçkiler.' }}</p>
                </div>
            </div>

            <div class="etic-footer__social-row">
                <p>Bizi takip edin.</p>
                <div class="etic-footer__socials">
                    @foreach($social as $label => $url)
                        @if(filled($url))
                            <a href="{{ $url }}" rel="noopener noreferrer" target="_blank">{{ $label }}</a>
                        @endif
                    @endforeach
                    @if(filled($whatsapp))
                        <a href="https://wa.me/{{ preg_replace('/\D+/', '', $whatsapp) }}" rel="noopener noreferrer" target="_blank">WhatsApp</a>
                    @endif
                </div>
            </div>

            <p class="etic-footer__copyright">&copy; {{ date('Y') }} {{ $logoText }}</p>
        </div>

        <aside class="etic-footer__aside">
            @if($footerImage && ! request()->routeIs('cart.show', 'checkout.show'))
                <div class="etic-footer__media">
                    <img src="{{ $footerImage }}" alt="" loading="lazy" decoding="async">
                </div>
            @endif

            @if(theme_enabled('newsletter_enabled'))
            <div class="etic-footer__newsletter">
                @if(filled($newsletterKicker))
                    <p class="etic-footer__label">{{ $newsletterKicker }}</p>
                @endif
                @if(filled($newsletterTitle))
                    <h2>{{ $newsletterTitle }}</h2>
                @endif
                @if(filled($newsletterDescription))
                    <p>{{ $newsletterDescription }}</p>
                @endif
                <form class="etic-footer__form" data-etic-newsletter>
                    <label class="sr-only" for="atelier-newsletter-email">E-posta adresi</label>
                    <input id="atelier-newsletter-email" type="email" name="email" required placeholder="{{ $newsletterPlaceholder }}">
                    <button type="submit" aria-label="Bültene katıl">&rarr;</button>
                </form>
                <p class="etic-footer__form-status" data-newsletter-status aria-live="polite"></p>
            </div>
            @endif
        </aside>
    </div>
</footer>
