<x-storefront-layout>
    <h1 class="mb-6 text-2xl font-semibold">Sepet</h1>
    @if($cart->lines->isEmpty())
        <p>Sepetiniz boş.</p>
    @else
        <div class="grid gap-8 lg:grid-cols-[1fr_20rem]">
            <div class="space-y-4">
                @foreach($cart->lines as $line)
                    <div class="flex items-center justify-between rounded-2xl bg-white p-4">
                        <div class="flex items-center gap-3">
                            <div class="h-16 w-16 overflow-hidden rounded-xl bg-neutral-100">
                                <x-theme::product-image :model="$line->purchasable" conversion="small" :alt="$line->purchasable?->sku" />
                            </div>
                            <div>
                                <p class="font-medium">{{ $line->purchasable?->sku }}</p>
                                <p class="text-sm text-neutral-500">{{ $line->total?->formatted() }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <form method="post" action="{{ route('cart.update') }}">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="line_id" value="{{ $line->id }}">
                                <input type="number" name="quantity" value="{{ $line->quantity }}" min="0" class="w-16 rounded border px-2 py-1">
                                <button class="text-sm underline">Güncelle</button>
                            </form>
                            <form method="post" action="{{ route('cart.remove') }}">
                                @csrf
                                @method('DELETE')
                                <input type="hidden" name="line_id" value="{{ $line->id }}">
                                <button class="text-sm text-red-600">Sil</button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
            <aside class="space-y-4">
                @include('theme::partials.coupon-form', ['cart' => $cart])
                <div class="rounded-2xl bg-white p-4">
                    @include('theme::partials.cart-totals', ['cart' => $cart])
                </div>
                <a href="{{ route('checkout.show') }}" class="etic-btn w-full">Ödemeye geç</a>
            </aside>
        </div>
    @endif
</x-storefront-layout>
