<x-storefront-layout seo-title="Sipariş #{{ $order->reference ?? $order->id }} — Hesabım">
    @php
        $user = auth()->user();
        $firstName = explode(' ', trim((string) $user?->name), 2)[0] ?: 'Merhaba';
        $initial = mb_strtoupper(mb_substr(trim((string) $user?->name), 0, 1));
    @endphp

    <section class="etic-account">
        <header class="etic-account__head">
            <p class="etic-account__kicker">Hesabım</p>
            <h1>Merhaba, {{ $firstName }}</h1>
            <p class="etic-account__lead">Sipariş detaylarını görüntülüyorsunuz.</p>
        </header>

        <div class="etic-account__layout">
            <aside class="etic-account__profile">
                <div class="etic-account__avatar" aria-hidden="true">{{ $initial }}</div>
                <div class="etic-account__identity">
                    <p class="etic-account__name">{{ $user?->name }}</p>
                    <p class="etic-account__email">{{ $user?->email }}</p>
                </div>
                <nav class="etic-account__nav" aria-label="Hesap menüsü">
                    <a href="{{ route('account') }}" class="etic-account__nav-item is-active">Siparişlerim</a>
                    <a href="{{ route('catalog') }}" class="etic-account__nav-item">Alışverişe devam et</a>
                </nav>
                <form method="post" action="{{ route('logout') }}" class="etic-account__logout">
                    @csrf
                    <button type="submit">Çıkış yap</button>
                </form>
            </aside>

            <div class="etic-account__main">
                <div class="etic-account__panel etic-account__panel--detail">
                    <div class="etic-account__panel-head">
                        <div>
                            <a href="{{ route('account') }}" class="etic-account__back">← Siparişlerime dön</a>
                            <h2>Sipariş detayı</h2>
                        </div>
                    </div>

                    @include('etic.order-detail', ['order' => $order])
                </div>
            </div>
        </div>
    </section>
</x-storefront-layout>
