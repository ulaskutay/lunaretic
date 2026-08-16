<x-storefront-layout>
    <h1 class="mb-6 text-2xl font-semibold">Hesabım</h1>
    <form method="post" action="{{ route('logout') }}">@csrf<button class="text-sm underline">Çıkış</button></form>
    <h2 class="mt-8 font-medium">Siparişler</h2>
    <ul class="mt-3 space-y-2">
        @forelse($orders as $order)
            <li class="rounded bg-white p-3">#{{ $order->reference ?? $order->id }} — {{ $order->status_label }}</li>
        @empty
            <li>Henüz sipariş yok.</li>
        @endforelse
    </ul>
</x-storefront-layout>
