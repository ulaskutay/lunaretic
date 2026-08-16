<x-storefront-layout>
    @php($filters = $filters ?? new \App\Etic\Storefront\CatalogFilters)
    @php($facets = $facets ?? ['colors' => collect(), 'sizes' => collect(), 'brands' => collect()])
    <div class="grid gap-8 md:grid-cols-[16rem_1fr]">
        <aside class="rounded-2xl bg-white p-4 text-sm">
            <h2 class="mb-3 font-medium">{{ __('etic.storefront.filters.title') }}</h2>
            <form method="get" class="space-y-3">
                <input type="hidden" name="q" value="{{ $search ?? $filters->search }}">
                <label class="block">
                    {{ __('etic.storefront.filters.sort') }}
                    <select name="sort" class="mt-1 w-full rounded border px-2 py-1">
                        <option value="newest" @selected($filters->sort === 'newest')>{{ __('etic.storefront.filters.newest') }}</option>
                        <option value="best_selling" @selected($filters->sort === 'best_selling')>{{ __('etic.storefront.filters.best_selling') }}</option>
                        <option value="price_asc" @selected($filters->sort === 'price_asc')>{{ __('etic.storefront.filters.price_asc') }}</option>
                        <option value="price_desc" @selected($filters->sort === 'price_desc')>{{ __('etic.storefront.filters.price_desc') }}</option>
                    </select>
                </label>
                @if($facets['colors']->isNotEmpty())
                    <label class="block">
                        {{ __('etic.storefront.filters.color') }}
                        <select name="renk" class="mt-1 w-full rounded border px-2 py-1">
                            <option value="">{{ __('etic.storefront.filters.all') }}</option>
                            @foreach($facets['colors'] as $color)
                                <option value="{{ $color->id }}" @selected($filters->color === $color->id)>{{ $color->translate('name') }}</option>
                            @endforeach
                        </select>
                    </label>
                @endif
                @if($facets['sizes']->isNotEmpty())
                    <label class="block">
                        {{ __('etic.storefront.filters.size') }}
                        <select name="beden" class="mt-1 w-full rounded border px-2 py-1">
                            <option value="">{{ __('etic.storefront.filters.all') }}</option>
                            @foreach($facets['sizes'] as $size)
                                <option value="{{ $size->id }}" @selected($filters->size === $size->id)>{{ $size->translate('name') }}</option>
                            @endforeach
                        </select>
                    </label>
                @endif
                @if($facets['brands']->isNotEmpty())
                    <label class="block">
                        {{ __('etic.storefront.filters.brand') }}
                        <select name="marka" class="mt-1 w-full rounded border px-2 py-1">
                            <option value="">{{ __('etic.storefront.filters.all') }}</option>
                            @foreach($facets['brands'] as $brand)
                                <option value="{{ $brand->id }}" @selected($filters->brand === $brand->id)>{{ $brand->name }}</option>
                            @endforeach
                        </select>
                    </label>
                @endif
                <div class="grid grid-cols-2 gap-2">
                    <label>{{ __('etic.storefront.filters.min') }}
                        <input type="number" name="min" min="0" step="1" value="{{ $filters->minPrice !== null ? $filters->minPrice / 100 : '' }}" class="mt-1 w-full rounded border px-2 py-1">
                    </label>
                    <label>{{ __('etic.storefront.filters.max') }}
                        <input type="number" name="max" min="0" step="1" value="{{ $filters->maxPrice !== null ? $filters->maxPrice / 100 : '' }}" class="mt-1 w-full rounded border px-2 py-1">
                    </label>
                </div>
                <label class="block">
                    {{ __('etic.storefront.filters.availability') }}
                    <select name="stok" class="mt-1 w-full rounded border px-2 py-1">
                        <option value="">{{ __('etic.storefront.filters.all') }}</option>
                        <option value="1" @selected($filters->inStock)>{{ __('etic.storefront.filters.in_stock') }}</option>
                        <option value="yok" @selected($filters->outOfStock)>{{ __('etic.storefront.filters.out_of_stock') }}</option>
                    </select>
                </label>
                <button class="etic-btn w-full">{{ __('etic.storefront.filters.apply') }}</button>
                <a href="{{ isset($currentCollection) ? route('collection', $currentCollection->defaultUrl?->slug) : route('catalog') }}" class="block text-center text-neutral-500">{{ __('etic.storefront.filters.clear') }}</a>
            </form>
        </aside>
        <div>
            <div class="mb-6">
                <h1 class="text-2xl font-semibold">
                    {{ $filters->sort === 'best_selling'
                        ? __('etic.storefront.filters.best_selling')
                        : (isset($currentCollection) ? $currentCollection->translateAttribute('name') : ($search ?? 'Koleksiyon')) }}
                </h1>
            </div>
            <div class="grid grid-cols-2 gap-4 md:grid-cols-3">
                @forelse($products as $product)
                    <x-theme::product-card :product="$product" />
                @empty
                    <p class="col-span-full text-sm text-muted">{{ __('etic.storefront.filters.empty') }}</p>
                @endforelse
            </div>
            <div class="mt-8">{{ $products->links() }}</div>
        </div>
    </div>
</x-storefront-layout>
