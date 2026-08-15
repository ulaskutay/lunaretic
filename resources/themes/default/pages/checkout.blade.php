<x-storefront-layout>
    <h1 class="mb-6 text-2xl font-semibold">Ödeme</h1>
    <div class="grid gap-8 md:grid-cols-2">
        <form method="post" action="{{ route('checkout.place') }}" class="space-y-3">
            @csrf
            <div class="grid grid-cols-2 gap-3">
                <input name="first_name" placeholder="Ad" class="rounded border px-3 py-2" required>
                <input name="last_name" placeholder="Soyad" class="rounded border px-3 py-2" required>
            </div>
            <input type="email" name="email" placeholder="E-posta" class="w-full rounded border px-3 py-2" required>
            <input name="phone" placeholder="Telefon" class="w-full rounded border px-3 py-2" required>
            <input name="line_one" placeholder="Adres" class="w-full rounded border px-3 py-2" required>
            <div class="grid grid-cols-2 gap-3">
                <input name="city" placeholder="İl" class="rounded border px-3 py-2" required>
                <input name="state" placeholder="İlçe" class="rounded border px-3 py-2">
            </div>
            <input name="postcode" placeholder="Posta kodu" class="w-full rounded border px-3 py-2">
            <textarea name="notes" placeholder="Sipariş notu" class="w-full rounded border px-3 py-2"></textarea>
            <label class="block text-sm">Kargo
                <select name="shipping" class="mt-1 w-full rounded border px-3 py-2">
                    @foreach($shippingOptions as $option)
                        <option value="{{ $option->getIdentifier() }}">{{ $option->getName() }} — {{ $option->getPrice()->formatted() }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block text-sm">Ödeme
                <select name="payment" class="mt-1 w-full rounded border px-3 py-2">
                    <option value="cash-in-hand">Kapıda / havale (offline)</option>
                    <option value="iyzico">iyzico</option>
                </select>
            </label>
            <input type="hidden" name="payment_token" value="test-token">
            <button class="rounded-full bg-neutral-900 px-6 py-3 text-white">Siparişi tamamla</button>
        </form>
        <aside class="rounded-2xl bg-white p-4">
            <h2 class="font-medium">Özet</h2>
            <p class="mt-4">Toplam: {{ $cart->total?->formatted() }}</p>
        </aside>
    </div>
</x-storefront-layout>
