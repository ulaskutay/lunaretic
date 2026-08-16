@php
    $logoText = theme()->logoText();
    $logo = theme()->logoUrl();
    $announcement = theme_setting('announcement');
    $headerMenu = theme()->menu('header');
    $links = $headerMenu?->items ?? collect();
    $overlay = theme_setting('header_style', 'overlay') !== 'stacked';
    $isHome = request()->routeIs('home');
@endphp

@if(filled($announcement))
    <div class="etic-announcement">{{ $announcement }}</div>
@endif

<header
    class="etic-header {{ $overlay ? 'etic-header--overlay' : 'etic-header--solid' }} {{ $isHome ? 'etic-header--home' : '' }} {{ filled($announcement) ? 'etic-header--has-announcement' : '' }}"
    data-etic-header
>
    <div class="etic-header__bg" aria-hidden="true"></div>
    <div class="etic-header__bar">
        <a href="{{ route('home') }}" class="etic-header__logo">
            @if($logo)
                <img src="{{ $logo }}" alt="{{ $logoText }}">
            @else
                {{ $logoText }}
            @endif
        </a>

        <nav class="etic-header__nav" data-etic-nav aria-label="Ana menü">
            @forelse($links as $item)
                @if($item->children->isNotEmpty())
                    <div class="etic-header__item" data-mega-trigger="{{ $item->id }}">
                        <a href="{{ $item->url }}">{{ $item->label }}</a>
                        <div class="etic-header__mobile-mega">
                            @include('etic.mega-menu-panel', ['item' => $item])
                        </div>
                    </div>
                @else
                    <a href="{{ $item->url }}">{{ $item->label }}</a>
                @endif
            @empty
                <a href="{{ route('catalog') }}">Ürünler</a>
                <a href="{{ route('blog.index') }}">Blog</a>
                <a href="{{ route('page', 'hakkimizda') }}">Hakkımızda</a>
            @endforelse
        </nav>

        <div class="etic-header__tools">
            @auth
                <a href="{{ route('account') }}" class="etic-icon-btn" aria-label="Hesabım">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="3.25" fill="none" stroke="currentColor" stroke-width="1.4"/><path d="M5.5 19.2c.8-3.3 3.4-5.2 6.5-5.2s5.7 1.9 6.5 5.2" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg>
                </a>
            @else
                <a href="{{ route('login') }}" class="etic-icon-btn" aria-label="Giriş">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="3.25" fill="none" stroke="currentColor" stroke-width="1.4"/><path d="M5.5 19.2c.8-3.3 3.4-5.2 6.5-5.2s5.7 1.9 6.5 5.2" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg>
                </a>
            @endauth

            <button type="button" class="etic-icon-btn" data-etic-search-toggle aria-label="Ara" aria-expanded="false">
                <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="6.25" fill="none" stroke="currentColor" stroke-width="1.4"/><path d="M16 16.5 20 20.5" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg>
            </button>

            @livewire('etic.mini-cart')

            <button type="button" class="etic-icon-btn etic-header__burger" data-etic-nav-toggle aria-label="Menü" aria-expanded="false">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 8h14M5 12h14M5 16h14" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg>
            </button>
        </div>
    </div>

    <div class="etic-header__mega-layer" data-etic-mega-layer>
        @foreach($links as $item)
            @if($item->children->isNotEmpty())
                <div class="etic-header__mega-panel" data-mega-panel="{{ $item->id }}" hidden>
                    @include('etic.mega-menu-panel', ['item' => $item])
                </div>
            @endif
        @endforeach
    </div>

    <div class="etic-search-layer" data-etic-search data-suggestions-url="{{ route('search.suggestions') }}" hidden>
        <button type="button" class="etic-search-layer__backdrop" data-etic-search-toggle aria-label="Aramayı kapat"></button>
        <div class="etic-search-layer__panel">
            <form action="{{ route('search') }}" method="get" class="etic-search__form" data-etic-search-form>
                <label class="etic-search__label" for="etic-search-input">Ürün ara</label>
                <input id="etic-search-input" type="search" name="q" placeholder="Ne aramıştınız?" value="{{ request('q') }}" autocomplete="off" enterkeyhint="search" data-etic-search-input>
                <button type="submit" class="etic-search__submit">Ara</button>
                <button type="button" class="etic-search__close" data-etic-search-toggle aria-label="Kapat">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg>
                </button>
            </form>
            <div class="etic-search-suggestions" data-etic-search-suggestions hidden>
                <p class="etic-search-suggestions__label">Öneriler</p>
                <ul class="etic-search-suggestions__list" data-etic-search-suggestions-list></ul>
            </div>
        </div>
    </div>
</header>
