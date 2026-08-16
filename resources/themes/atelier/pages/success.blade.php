<x-storefront-layout>
    <h1 class="text-2xl font-semibold">Siparişiniz alındı</h1>
    <p class="mt-4">Sipariş no: {{ $order->reference ?? $order->id }}</p>
    <p class="mt-2 text-sm text-neutral-600">Durum: {{ $order->status_label }}</p>
</x-storefront-layout>
