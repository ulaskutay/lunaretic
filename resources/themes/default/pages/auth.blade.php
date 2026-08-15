<x-storefront-layout>
    <h1 class="mb-6 text-2xl font-semibold">{{ $mode === 'login' ? 'Giriş' : 'Kayıt' }}</h1>
    @if($mode === 'login')
        <form method="post" action="{{ route('login') }}" class="max-w-sm space-y-3">
            @csrf
            <input type="email" name="email" class="w-full rounded border px-3 py-2" placeholder="E-posta" required>
            <input type="password" name="password" class="w-full rounded border px-3 py-2" placeholder="Şifre" required>
            <button class="rounded-full bg-neutral-900 px-6 py-2 text-white">Giriş yap</button>
        </form>
        <p class="mt-4 text-sm"><a href="{{ route('register') }}">Hesap oluştur</a></p>
    @else
        <form method="post" action="{{ route('register') }}" class="max-w-sm space-y-3">
            @csrf
            <input name="name" class="w-full rounded border px-3 py-2" placeholder="Ad soyad" required>
            <input type="email" name="email" class="w-full rounded border px-3 py-2" placeholder="E-posta" required>
            <input type="password" name="password" class="w-full rounded border px-3 py-2" placeholder="Şifre" required>
            <input type="password" name="password_confirmation" class="w-full rounded border px-3 py-2" placeholder="Şifre tekrar" required>
            <button class="rounded-full bg-neutral-900 px-6 py-2 text-white">Kayıt ol</button>
        </form>
    @endif
</x-storefront-layout>
