<x-storefront-layout>
    @php
        $user = auth()->user();
        $firstName = explode(' ', trim((string) $user?->name), 2)[0] ?: 'Merhaba';
        $initial = mb_strtoupper(mb_substr(trim((string) $user?->name), 0, 1));
    @endphp

    <section class="etic-account">
        <header class="etic-account__head">
            <p class="etic-account__kicker">Hesabım</p>
            <h1>Merhaba, {{ $firstName }}</h1>
            <p class="etic-account__lead">Siparişlerinizi görüntüleyin ve hesap bilgilerinizi yönetin.</p>
        </header>

        <div class="etic-account__layout">
            <aside class="etic-account__profile">
                <div class="etic-account__avatar" aria-hidden="true">{{ $initial }}</div>
                <div class="etic-account__identity">
                    <p class="etic-account__name">{{ $user?->name }}</p>
                    <p class="etic-account__email">{{ $user?->email }}</p>
                </div>
                <nav class="etic-account__nav" aria-label="Hesap menüsü">
                    <span class="etic-account__nav-item is-active">Siparişlerim</span>
                    <a href="{{ route('catalog') }}" class="etic-account__nav-item">Alışverişe devam et</a>
                </nav>
                <form method="post" action="{{ route('logout') }}" class="etic-account__logout">
                    @csrf
                    <button type="submit">Çıkış yap</button>
                </form>
            </aside>

            <div class="etic-account__main">
                <div class="etic-account__panel">
                    <div class="etic-account__panel-head">
                        <h2>Siparişlerim</h2>
                        <p>{{ $orders->count() }} kayıt</p>
                    </div>

                    @if($orders->isEmpty())
                        <div class="etic-account__empty">
                            <p>Henüz bir siparişiniz yok.</p>
                            <a href="{{ route('catalog') }}" class="etic-account__cta">Koleksiyonu keşfet</a>
                        </div>
                    @else
                        <ul class="etic-account__orders">
                            @foreach($orders as $order)
                                <li>
                                    <a href="{{ route('account.order', $order) }}" class="etic-account__order">
                                        <div class="etic-account__order-copy">
                                            <p class="etic-account__order-ref">#{{ $order->reference ?? $order->id }}</p>
                                            <p class="etic-account__order-date">
                                                {{ $order->created_at?->translatedFormat('d F Y') }}
                                            </p>
                                        </div>
                                        <span class="etic-account__order-status">{{ $order->status_label }}</span>
                                        <p class="etic-account__order-total">{{ $order->total?->formatted() }}</p>
                                        <span class="etic-account__order-action" aria-hidden="true">Detay →</span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </div>
    </section>
</x-storefront-layout>
