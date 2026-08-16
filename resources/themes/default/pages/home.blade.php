<x-storefront-layout :canonical="$canonical ?? null" :schema-json="$schemaJson ?? null">
    <section class="mb-10 grid gap-8 md:grid-cols-2 md:items-center">
        <div>
            <p class="text-xs uppercase tracking-[0.2em] text-neutral-500">Boxer koleksiyonu</p>
            <h1 class="mt-3 text-4xl font-semibold">Rahatlık, sade tasarım.</h1>
            <p class="mt-4 max-w-md text-neutral-600">İlk Etic Commerce mağazası. Renk ve beden varyantlarıyla tek üründen stoklu satış.</p>
            <a href="{{ route('catalog') }}" class="etic-btn mt-6">Alışverişe başla</a>
        </div>
        <div class="aspect-[4/5] overflow-hidden rounded-3xl bg-neutral-100">
            @if(isset($products) && $products->first())
                <x-theme::product-image :model="$products->first()" conversion="large" :alt="$products->first()->translateAttribute('name')" />
            @endif
        </div>
    </section>
    <h2 class="font-heading mb-4 text-xl font-medium">Öne çıkanlar</h2>
    <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
        @foreach($products as $product)
            <x-theme::product-card :product="$product" />
        @endforeach
    </div>
</x-storefront-layout>
