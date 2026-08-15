<x-storefront-layout :canonical="$canonical ?? null" :schema-json="$schemaJson ?? null" :seo-title="$product->translateAttribute('name')">
    <div class="grid gap-10 md:grid-cols-2">
        <div class="space-y-3">
            @php($gallery = \App\Etic\Media\ProductImage::gallery($product))
            <div class="aspect-square overflow-hidden rounded-3xl bg-neutral-100">
                <x-theme::product-image :model="$product" conversion="large" :alt="$product->translateAttribute('name')" />
            </div>
            @if($gallery->count() > 1)
                <div class="grid grid-cols-4 gap-2">
                    @foreach($gallery as $media)
                        <div class="aspect-square overflow-hidden rounded-xl bg-neutral-100">
                            <img
                                src="{{ $media->hasGeneratedConversion('small') ? $media->getUrl('small') : $media->getUrl() }}"
                                alt=""
                                class="block h-full w-full object-contain object-center"
                                style="object-fit: contain; object-position: center;"
                            >
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
        <div>
            <h1 class="text-3xl font-semibold">{{ $product->translateAttribute('name') }}</h1>
            <div class="prose mt-4 text-neutral-600">{!! $product->translateAttribute('description') !!}</div>
            @php($price = $variant?->prices->first())
            @if($price)
                <p class="mt-6 text-2xl font-medium">{{ $price->price->formatted() }}</p>
                @if($price->compare_price)
                    <p class="text-sm text-neutral-500 line-through">{{ $price->compare_price->formatted() }}</p>
                @endif
            @endif
            <form method="post" action="{{ route('cart.add') }}" class="mt-6 space-y-3">
                @csrf
                <label class="block text-sm">Varyant
                    <select name="variant_id" class="mt-1 w-full rounded border px-3 py-2">
                        @foreach($product->variants as $item)
                            <option value="{{ $item->id }}">{{ $item->sku }} — stok {{ $item->stock }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="block text-sm">Adet
                    <input type="number" name="quantity" value="1" min="1" class="mt-1 w-24 rounded border px-3 py-2">
                </label>
                <button class="rounded-full bg-neutral-900 px-6 py-3 text-sm text-white">Sepete ekle</button>
            </form>
        </div>
    </div>
</x-storefront-layout>
