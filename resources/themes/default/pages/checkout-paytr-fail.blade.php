<x-storefront-layout>
    <section class="etic-checkout etic-checkout--fail">
        <div class="etic-checkout__card" style="max-width: 36rem; margin: 2rem auto; text-align: center;">
            <h1>Ödeme tamamlanamadı</h1>
            <p>Sipariş numaranız: <strong>{{ $order->reference }}</strong></p>
            <p>Ödeme işlemi başarısız oldu veya iptal edildi. Tekrar deneyebilir veya farklı bir ödeme yöntemi seçebilirsiniz.</p>
            <p>
                <a href="{{ route('checkout.show') }}" class="etic-checkout__cta">Ödemeye dön</a>
            </p>
        </div>
    </section>
</x-storefront-layout>
