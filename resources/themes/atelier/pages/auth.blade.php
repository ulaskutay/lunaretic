<x-storefront-layout>
    @php
        $isLogin = $mode === 'login';
        $storeName = $eticStore->name();
    @endphp

    <section class="etic-auth">
        <div class="etic-auth__shell">
            <aside class="etic-auth__hero" aria-hidden="true">
                <p class="etic-auth__kicker">{{ $storeName }}</p>
                <h1 class="etic-auth__title">
                    {{ $isLogin ? 'Tekrar hoş geldiniz' : 'Yeni bir hesap oluşturun' }}
                </h1>
                <p class="etic-auth__lead">
                    {{ $isLogin
                        ? 'Siparişlerinizi takip edin, adreslerinizi kaydedin ve koleksiyonları daha hızlı keşfedin.'
                        : 'Birkaç adımda hesabınızı açın; sipariş geçmişiniz ve tercihleriniz tek yerde toplansın.' }}
                </p>
                <ul class="etic-auth__perks">
                    <li>Sipariş geçmişi ve durum takibi</li>
                    <li>Hızlı ödeme için kayıtlı bilgiler</li>
                    <li>Yeni koleksiyonlardan ilk siz haberdar olun</li>
                </ul>
            </aside>

            <div class="etic-auth__card">
                <div class="etic-auth__card-head">
                    <h2 class="etic-auth__card-title">{{ $isLogin ? 'Giriş yap' : 'Kayıt ol' }}</h2>
                    <p class="etic-auth__card-copy">
                        {{ $isLogin ? 'Hesabınıza erişmek için bilgilerinizi girin.' : 'Alışverişe başlamak için bilgilerinizi tamamlayın.' }}
                    </p>
                </div>

                @if($isLogin)
                    <form method="post" action="{{ route('login') }}" class="etic-auth__form">
                        @csrf
                        <label class="etic-auth__field">
                            <span class="etic-auth__label">E-posta</span>
                            <input type="email" name="email" value="{{ old('email') }}" class="etic-auth__input" autocomplete="email" required>
                        </label>
                        <label class="etic-auth__field">
                            <span class="etic-auth__label">Şifre</span>
                            <input type="password" name="password" class="etic-auth__input" autocomplete="current-password" required>
                        </label>
                        <button type="submit" class="etic-auth__submit">Giriş yap</button>
                    </form>
                    <p class="etic-auth__switch">
                        Henüz hesabınız yok mu?
                        <a href="{{ route('register') }}">Kayıt olun</a>
                    </p>
                @else
                    <form method="post" action="{{ route('register') }}" class="etic-auth__form">
                        @csrf
                        <label class="etic-auth__field">
                            <span class="etic-auth__label">Ad soyad</span>
                            <input name="name" value="{{ old('name') }}" class="etic-auth__input" autocomplete="name" required>
                        </label>
                        <label class="etic-auth__field">
                            <span class="etic-auth__label">E-posta</span>
                            <input type="email" name="email" value="{{ old('email') }}" class="etic-auth__input" autocomplete="email" required>
                        </label>
                        <label class="etic-auth__field">
                            <span class="etic-auth__label">Şifre</span>
                            <input type="password" name="password" class="etic-auth__input" autocomplete="new-password" required>
                        </label>
                        <label class="etic-auth__field">
                            <span class="etic-auth__label">Şifre tekrar</span>
                            <input type="password" name="password_confirmation" class="etic-auth__input" autocomplete="new-password" required>
                        </label>
                        <button type="submit" class="etic-auth__submit">Hesap oluştur</button>
                    </form>
                    <p class="etic-auth__switch">
                        Zaten hesabınız var mı?
                        <a href="{{ route('login') }}">Giriş yapın</a>
                    </p>
                @endif
            </div>
        </div>
    </section>
</x-storefront-layout>
