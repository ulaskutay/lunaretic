@php
    $heroImage = theme()->heroImageUrl();
    if (! $heroImage && isset($products) && $products->first()) {
        $heroImage = \App\Etic\Media\ProductImage::url($products->first(), 'large');
    }
    $title = (string) theme_setting('hero_title', 'Rahatlık, sade tasarım');
    $titleLines = preg_split("/\r\n|\n|\r/", $title) ?: [$title];
    $kicker = theme_setting('hero_kicker', 'Yeni koleksiyon');
    $ctaPrimary = theme_setting('hero_cta_primary', 'Ürünleri gör');
    $ctaPrimaryUrl = theme_setting('hero_cta_primary_url', route('catalog'));
    $ctaSecondary = theme_setting('hero_cta_secondary', 'Keşfet');
    $ctaSecondaryUrl = theme_setting('hero_cta_secondary_url', route('page', 'hakkimizda'));
    $featuredTitle = theme_setting('featured_title', 'Yeni gelenler — Koleksiyon');
    $featuredProducts = $products->take(4);
    $featuredColumns = max(1, min(4, $featuredProducts->count()));
    $editorialProducts = $products->take(4);
    $editorialKicker = theme_setting('editorial_kicker', 'Sezon seçkisi');
    $editorialTitle = theme_setting('editorial_title', 'Unutulmaz bir gece');
    $editorialCta = theme_setting('editorial_cta', 'Koleksiyonu keşfet');
    $editorialCtaUrl = theme_setting('editorial_cta_url', route('catalog'));
    $editorialImage = theme()->editorialImageUrl();

    if (! $editorialImage && $editorialProducts->last()) {
        $editorialImage = \App\Etic\Media\ProductImage::url($editorialProducts->last(), 'large');
    }

    $secondaryEditorialKicker = theme_setting('editorial_secondary_kicker', 'Yeni sezon');
    $secondaryEditorialTitle = theme_setting('editorial_secondary_title', 'Özgür ruhlar için');
    $secondaryEditorialCta = theme_setting('editorial_secondary_cta', 'Seçkiyi keşfet');
    $secondaryEditorialCtaUrl = theme_setting('editorial_secondary_cta_url', route('catalog'));
    $secondaryEditorialImage = theme()->secondaryEditorialImageUrl();

    if (! $secondaryEditorialImage && $editorialProducts->last()) {
        $secondaryEditorialImage = \App\Etic\Media\ProductImage::url($editorialProducts->last(), 'large');
    }

    $bestSellersTitle = theme_setting('best_sellers_title', 'Çok satanlar');
    $bestSellersCta = theme_setting('best_sellers_cta', 'Tümünü gör');
    $bestSellersUrl = route('catalog', ['sort' => 'best_selling']);
    $bestSellerProducts = ($bestSellerProducts ?? $products)->take(8);
    $bestSellerColumns = max(1, min(4, $bestSellerProducts->count()));
    $leftBannerImage = theme()->leftBannerImageUrl() ?: $editorialImage;
    $rightBannerImage = theme()->rightBannerImageUrl() ?: $secondaryEditorialImage;
    $leftBannerTitle = theme_setting('banner_left_title', 'Zarif konfor');
    $leftBannerSubtitle = theme_setting('banner_left_subtitle');
    $leftBannerCta = theme_setting('banner_left_cta', 'Keşfet');
    $leftBannerUrl = theme_setting('banner_left_url', route('catalog'));
    $rightBannerTitle = theme_setting('banner_right_title', 'Sezonun dokusu');
    $rightBannerSubtitle = theme_setting('banner_right_subtitle', 'Yeni sezon seçkisi');
    $rightBannerCta = theme_setting('banner_right_cta', 'Şimdi keşfet');
    $rightBannerUrl = theme_setting('banner_right_url', route('catalog'));
    $shopLookKicker = theme_setting('shop_look_kicker', 'Stili keşfet');
    $shopLookTitle = theme_setting('shop_look_title', 'Görünümü tamamla');
    $shopLookImage = theme()->shopLookImageUrl() ?: $leftBannerImage ?: $editorialImage;
    $shopLookItems = collect($shopLookItems ?? []);
    $countdownTitle = theme_setting('countdown_title', 'Sezon indirimi');
    $countdownDescription = theme_setting('countdown_description', 'Seçili ürünlerde sınırlı süreli fırsatları keşfedin.');
    $countdownEndsAt = theme()->countdownEndsAt();
@endphp

<x-storefront-layout :canonical="$canonical ?? null" :schema-json="$schemaJson ?? null">
    @if(theme_enabled('hero_enabled'))
    <section class="etic-hero">
        @if($heroImage)
            <img class="etic-hero__image" src="{{ $heroImage }}" alt="" role="presentation">
        @endif
        <div class="etic-hero__shade"></div>
        <div class="etic-hero__content">
            @if(filled($kicker))
                <p class="etic-hero__kicker">{{ $kicker }}</p>
            @endif
            <h1 class="etic-hero__title">
                @foreach($titleLines as $line)
                    <span>{{ $line }}</span>
                @endforeach
            </h1>
            <div class="etic-hero__actions">
                @if(filled($ctaPrimary))
                    <a href="{{ $ctaPrimaryUrl }}" class="etic-hero__cta">{{ $ctaPrimary }}</a>
                @endif
                @if(filled($ctaSecondary))
                    <a href="{{ $ctaSecondaryUrl }}" class="etic-hero__link">{{ $ctaSecondary }}</a>
                @endif
            </div>
        </div>
    </section>
    @endif

    @if(theme_enabled('countdown_enabled') && $countdownEndsAt)
        <section
            class="etic-countdown"
            data-etic-countdown
            data-countdown-end="{{ $countdownEndsAt }}"
            aria-labelledby="atelier-countdown-title"
        >
            <div class="etic-countdown__copy">
                @if(filled($countdownTitle))
                    <h2 id="atelier-countdown-title">{{ $countdownTitle }}</h2>
                @endif
                @if(filled($countdownDescription))
                    <p>{{ $countdownDescription }}</p>
                @endif
            </div>

            <div class="etic-countdown__timer" role="timer" aria-label="Kampanya için kalan süre">
                <div class="etic-countdown__unit">
                    <strong data-countdown-days>00</strong>
                    <span>Gün</span>
                </div>
                <div class="etic-countdown__unit">
                    <strong data-countdown-hours>00</strong>
                    <span>Saat</span>
                </div>
                <div class="etic-countdown__unit">
                    <strong data-countdown-minutes>00</strong>
                    <span>Dakika</span>
                </div>
                <div class="etic-countdown__unit">
                    <strong data-countdown-seconds>00</strong>
                    <span>Saniye</span>
                </div>
            </div>
        </section>
    @endif

    @if(theme_enabled('featured_enabled') && $featuredProducts->isNotEmpty())
        <section class="etic-featured" aria-labelledby="atelier-featured-title">
            <div class="etic-featured__inner">
                @if(filled($featuredTitle))
                    <div class="etic-featured__heading">
                        <span aria-hidden="true"></span>
                        <h2 id="atelier-featured-title" class="etic-featured__title">{{ $featuredTitle }}</h2>
                        <span aria-hidden="true"></span>
                    </div>
                @endif
                <div
                    class="etic-featured__grid"
                    style="--etic-featured-columns: {{ $featuredColumns }}"
                >
                @foreach($featuredProducts as $product)
                    <x-theme::product-card :product="$product" />
                @endforeach
                </div>
            </div>
        </section>
    @endif

    @if(theme_enabled('editorial_enabled') && $editorialProducts->isNotEmpty())
        <section class="etic-editorial" aria-labelledby="atelier-editorial-title">
            <div class="etic-editorial__products">
                @foreach($editorialProducts as $product)
                    <x-theme::product-card class="etic-editorial__product" :product="$product" />
                @endforeach
            </div>

            <div class="etic-editorial__campaign" data-etic-parallax>
                @if($editorialImage)
                    <img
                        class="etic-editorial__image"
                        src="{{ $editorialImage }}"
                        alt=""
                        loading="lazy"
                        decoding="async"
                    >
                @endif
                <div class="etic-editorial__shade"></div>
                <div class="etic-editorial__content">
                    @if(filled($editorialKicker))
                        <p class="etic-editorial__kicker">{{ $editorialKicker }}</p>
                    @endif
                    @if(filled($editorialTitle))
                        <h2 id="atelier-editorial-title" class="etic-editorial__title">{{ $editorialTitle }}</h2>
                    @endif
                    @if(filled($editorialCta))
                        <a class="etic-editorial__cta" href="{{ $editorialCtaUrl }}">{{ $editorialCta }}</a>
                    @endif
                </div>
            </div>
        </section>
    @endif

    @if(theme_enabled('editorial_secondary_enabled') && $editorialProducts->isNotEmpty())
        <section class="etic-editorial etic-editorial--reverse" aria-labelledby="atelier-editorial-secondary-title">
            <div class="etic-editorial__campaign" data-etic-parallax>
                @if($secondaryEditorialImage)
                    <img
                        class="etic-editorial__image"
                        src="{{ $secondaryEditorialImage }}"
                        alt=""
                        loading="lazy"
                        decoding="async"
                    >
                @endif
                <div class="etic-editorial__shade"></div>
                <div class="etic-editorial__content">
                    @if(filled($secondaryEditorialKicker))
                        <p class="etic-editorial__kicker">{{ $secondaryEditorialKicker }}</p>
                    @endif
                    @if(filled($secondaryEditorialTitle))
                        <h2 id="atelier-editorial-secondary-title" class="etic-editorial__title">{{ $secondaryEditorialTitle }}</h2>
                    @endif
                    @if(filled($secondaryEditorialCta))
                        <a class="etic-editorial__cta" href="{{ $secondaryEditorialCtaUrl }}">{{ $secondaryEditorialCta }}</a>
                    @endif
                </div>
            </div>

            <div class="etic-editorial__products">
                @foreach($editorialProducts as $product)
                    <x-theme::product-card class="etic-editorial__product" :product="$product" />
                @endforeach
            </div>
        </section>
    @endif

    @if(theme_enabled('best_sellers_enabled') && $bestSellerProducts->isNotEmpty())
        <section class="etic-best-sellers" aria-labelledby="atelier-best-sellers-title">
            @if(filled($bestSellersTitle))
                <div class="etic-best-sellers__heading">
                    <span aria-hidden="true"></span>
                    <h2 id="atelier-best-sellers-title">{{ $bestSellersTitle }}</h2>
                    <span aria-hidden="true"></span>
                </div>
            @endif

            <div
                class="etic-best-sellers__grid"
                style="--etic-best-seller-columns: {{ $bestSellerColumns }}"
            >
                @foreach($bestSellerProducts as $product)
                    <x-theme::product-card class="etic-best-sellers__product" :product="$product" />
                @endforeach
            </div>

            @if(filled($bestSellersCta))
                <div class="etic-best-sellers__footer">
                    <a href="{{ $bestSellersUrl }}">{{ $bestSellersCta }}</a>
                </div>
            @endif
        </section>
    @endif

    @if(theme_enabled('shop_look_enabled') && $shopLookImage && $shopLookItems->isNotEmpty())
        <section class="etic-shop-look" data-etic-shop-look aria-labelledby="atelier-shop-look-title">
            <div class="etic-shop-look__heading">
                @if(filled($shopLookKicker))
                    <p>{{ $shopLookKicker }}</p>
                @endif
                <h2 id="atelier-shop-look-title">{{ $shopLookTitle }}</h2>
            </div>

            <div class="etic-shop-look__stage">
                <div class="etic-shop-look__visual">
                    <img src="{{ $shopLookImage }}" alt="" loading="lazy" decoding="async">
                    @foreach($shopLookItems as $index => $item)
                        <button
                            type="button"
                            class="etic-shop-look__hotspot {{ $index === 0 ? 'is-active' : '' }}"
                            style="--hotspot-x: {{ $item['x'] }}%; --hotspot-y: {{ $item['y'] }}%"
                            data-shop-look-trigger="{{ $index }}"
                            aria-controls="atelier-shop-look-product-{{ $index }}"
                            aria-expanded="{{ $index === 0 ? 'true' : 'false' }}"
                            aria-label="{{ $item['product']->translateAttribute('name') }} ürününü göster"
                        >
                            <span>{{ $index + 1 }}</span>
                        </button>
                    @endforeach
                </div>

                <div class="etic-shop-look__products" aria-live="polite">
                    <p class="etic-shop-look__counter">
                        <span data-shop-look-current>01</span>
                        <span aria-hidden="true">/</span>
                        <span>{{ str_pad((string) $shopLookItems->count(), 2, '0', STR_PAD_LEFT) }}</span>
                    </p>
                    @foreach($shopLookItems as $index => $item)
                        <div
                            id="atelier-shop-look-product-{{ $index }}"
                            class="etic-shop-look__product"
                            data-shop-look-product="{{ $index }}"
                            @if($index !== 0) hidden @endif
                        >
                            <x-theme::product-card :product="$item['product']" />
                        </div>
                    @endforeach
                    <div class="etic-shop-look__mobile-nav" aria-label="Görünüm ürünleri">
                        @foreach($shopLookItems as $index => $item)
                            <button
                                type="button"
                                class="{{ $index === 0 ? 'is-active' : '' }}"
                                data-shop-look-trigger="{{ $index }}"
                                aria-label="{{ $index + 1 }}. ürünü göster"
                            >{{ $index + 1 }}</button>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>
    @endif

    @if(theme_enabled('banners_enabled') && ($leftBannerImage || $rightBannerImage))
        <section class="etic-dual-banners" aria-label="Koleksiyon seçkileri">
            <article class="etic-dual-banner">
                @if($leftBannerImage)
                    <img class="etic-dual-banner__image" src="{{ $leftBannerImage }}" alt="" loading="lazy" decoding="async">
                @endif
                <div class="etic-dual-banner__shade"></div>
                <div class="etic-dual-banner__content">
                    @if(filled($leftBannerTitle))
                        <h2 class="etic-dual-banner__title">{{ $leftBannerTitle }}</h2>
                    @endif
                    @if(filled($leftBannerSubtitle))
                        <p class="etic-dual-banner__subtitle">{{ $leftBannerSubtitle }}</p>
                    @endif
                    @if(filled($leftBannerCta))
                        <a class="etic-dual-banner__cta" href="{{ $leftBannerUrl }}">{{ $leftBannerCta }}</a>
                    @endif
                </div>
            </article>

            <article class="etic-dual-banner etic-dual-banner--centered">
                @if($rightBannerImage)
                    <img class="etic-dual-banner__image" src="{{ $rightBannerImage }}" alt="" loading="lazy" decoding="async">
                @endif
                <div class="etic-dual-banner__shade"></div>
                <div class="etic-dual-banner__content">
                    @if(filled($rightBannerTitle))
                        <h2 class="etic-dual-banner__title">{{ $rightBannerTitle }}</h2>
                    @endif
                    @if(filled($rightBannerSubtitle))
                        <p class="etic-dual-banner__subtitle">{{ $rightBannerSubtitle }}</p>
                    @endif
                    @if(filled($rightBannerCta))
                        <a class="etic-dual-banner__cta" href="{{ $rightBannerUrl }}">{{ $rightBannerCta }}</a>
                    @endif
                </div>
            </article>
        </section>
    @endif

    @if(theme_enabled('newsletter_enabled'))
        <x-theme::newsletter-benefits />
    @endif

</x-storefront-layout>
