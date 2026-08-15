<x-storefront-layout>
    <div class="mb-6 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <h1 class="text-2xl font-semibold">{{ isset($currentCollection) ? $currentCollection->translateAttribute('name') : ($search ?? 'Koleksiyon') }}</h1>
        <form class="flex gap-2 text-sm">
            <input type="hidden" name="q" value="{{ $search ?? request('q') }}">
            <select name="sort" class="rounded border px-2 py-1" onchange="this.form.submit()">
                <option value="newest">Yeni</option>
                <option value="name">İsim</option>
            </select>
        </form>
    </div>
    <div class="grid grid-cols-2 gap-4 md:grid-cols-3">
        @foreach($products as $product)
            <a href="{{ route('product', $product->defaultUrl?->slug ?? $product->id) }}" class="rounded-2xl bg-white p-3">
                <div class="mb-3 aspect-square overflow-hidden rounded-xl bg-neutral-100">
                    <x-theme::product-image :model="$product" conversion="medium" :alt="$product->translateAttribute('name')" />
                </div>
                <h2 class="text-sm font-medium">{{ $product->translateAttribute('name') }}</h2>
            </a>
        @endforeach
    </div>
    <div class="mt-8">{{ $products->links() }}</div>
</x-storefront-layout>
