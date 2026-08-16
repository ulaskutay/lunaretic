<x-storefront-layout>
    @php($filters = $filters ?? new \App\Etic\Storefront\CatalogFilters)
    @php($facets = $facets ?? ['colors' => collect(), 'sizes' => collect(), 'brands' => collect()])
    @php($catalogTitle = $filters->sort === 'best_selling'
        ? __('etic.storefront.filters.best_selling')
        : (isset($currentCollection) ? $currentCollection->translateAttribute('name') : ($search ?? 'Koleksiyon')))

    <div class="etic-catalog-default">
        <div class="mb-5 flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="text-2xl font-semibold">{{ $catalogTitle }}</h1>
                <p class="mt-1 text-sm text-neutral-500">{{ $products->total() }} ürün</p>
            </div>
            <button
                type="button"
                class="etic-catalog-default__toggle md:hidden"
                data-default-catalog-toggle
                aria-expanded="false"
            >
                Filtreler
            </button>
        </div>

        @if(($collections ?? collect())->isNotEmpty())
            <nav class="etic-catalog-default__categories mb-4 md:hidden" aria-label="Ürün kategorileri">
                <a href="{{ route('catalog') }}" @class(['is-active' => ! isset($currentCollection)])>Tümü</a>
                @foreach($collections as $collection)
                    @php($isCurrent = isset($currentCollection) && $currentCollection->is($collection))
                    <a href="{{ route('collection', $collection->defaultUrl?->slug) }}" @class(['is-active' => $isCurrent])>
                        {{ $collection->translateAttribute('name') }}
                    </a>
                @endforeach
            </nav>
        @endif

        <div class="grid gap-6 md:grid-cols-[16rem_1fr] md:gap-8">
            <aside class="etic-catalog-default__filters hidden rounded-2xl bg-white p-4 text-sm md:block" data-default-catalog-filters>
                @if(($collections ?? collect())->isNotEmpty())
                    <div class="mb-4 hidden md:block">
                        <h2 class="mb-2 text-xs font-semibold uppercase tracking-wide text-neutral-500">Kategoriler</h2>
                        <div class="space-y-1">
                            <a href="{{ route('catalog') }}" class="block text-sm {{ ! isset($currentCollection) ? 'font-semibold text-neutral-900' : 'text-neutral-600' }}">Tümü</a>
                            @foreach($collections as $collection)
                                @php($isCurrent = isset($currentCollection) && $currentCollection->is($collection))
                                <a href="{{ route('collection', $collection->defaultUrl?->slug) }}" class="block text-sm {{ $isCurrent ? 'font-semibold text-neutral-900' : 'text-neutral-600' }}">
                                    {{ $collection->translateAttribute('name') }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
                <h2 class="mb-3 font-medium">{{ __('etic.storefront.filters.title') }}</h2>
                <form method="get" class="space-y-3">
                    <input type="hidden" name="q" value="{{ $search ?? $filters->search }}">
                    <label class="block">
                        {{ __('etic.storefront.filters.sort') }}
                        <select name="sort" class="mt-1 w-full rounded border px-2 py-2">
                            <option value="newest" @selected($filters->sort === 'newest')>{{ __('etic.storefront.filters.newest') }}</option>
                            <option value="best_selling" @selected($filters->sort === 'best_selling')>{{ __('etic.storefront.filters.best_selling') }}</option>
                            <option value="price_asc" @selected($filters->sort === 'price_asc')>{{ __('etic.storefront.filters.price_asc') }}</option>
                            <option value="price_desc" @selected($filters->sort === 'price_desc')>{{ __('etic.storefront.filters.price_desc') }}</option>
                        </select>
                    </label>
                    @if($facets['colors']->isNotEmpty())
                        <label class="block">
                            {{ __('etic.storefront.filters.color') }}
                            <select name="renk" class="mt-1 w-full rounded border px-2 py-2">
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
                            <select name="beden" class="mt-1 w-full rounded border px-2 py-2">
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
                            <select name="marka" class="mt-1 w-full rounded border px-2 py-2">
                                <option value="">{{ __('etic.storefront.filters.all') }}</option>
                                @foreach($facets['brands'] as $brand)
                                    <option value="{{ $brand->id }}" @selected($filters->brand === $brand->id)>{{ $brand->name }}</option>
                                @endforeach
                            </select>
                        </label>
                    @endif
                    <div class="grid grid-cols-2 gap-2">
                        <label>{{ __('etic.storefront.filters.min') }}
                            <input type="number" name="min" min="0" step="1" value="{{ $filters->minPrice !== null ? $filters->minPrice / 100 : '' }}" class="mt-1 w-full rounded border px-2 py-2">
                        </label>
                        <label>{{ __('etic.storefront.filters.max') }}
                            <input type="number" name="max" min="0" step="1" value="{{ $filters->maxPrice !== null ? $filters->maxPrice / 100 : '' }}" class="mt-1 w-full rounded border px-2 py-2">
                        </label>
                    </div>
                    <label class="block">
                        {{ __('etic.storefront.filters.availability') }}
                        <select name="stok" class="mt-1 w-full rounded border px-2 py-2">
                            <option value="">{{ __('etic.storefront.filters.all') }}</option>
                            <option value="1" @selected($filters->inStock)>{{ __('etic.storefront.filters.in_stock') }}</option>
                            <option value="yok" @selected($filters->outOfStock)>{{ __('etic.storefront.filters.out_of_stock') }}</option>
                        </select>
                    </label>
                    <button class="etic-btn w-full min-h-11">{{ __('etic.storefront.filters.apply') }}</button>
                    <a href="{{ isset($currentCollection) ? route('collection', $currentCollection->defaultUrl?->slug) : route('catalog') }}" class="block text-center text-neutral-500">{{ __('etic.storefront.filters.clear') }}</a>
                </form>
            </aside>
            <div class="min-w-0">
                <div class="grid grid-cols-2 gap-3 sm:gap-4 md:grid-cols-3">
                    @forelse($products as $product)
                        <x-theme::product-card :product="$product" />
                    @empty
                        <p class="col-span-full text-sm text-muted">{{ __('etic.storefront.filters.empty') }}</p>
                    @endforelse
                </div>
                <div class="mt-8">{{ $products->links() }}</div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('click', (event) => {
            const toggle = event.target.closest('[data-default-catalog-toggle]');
            const panel = document.querySelector('[data-default-catalog-filters]');
            if (!toggle || !panel) return;
            const open = panel.classList.toggle('is-open');
            panel.classList.toggle('hidden', !open);
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            toggle.textContent = open ? 'Filtreleri kapat' : 'Filtreler';
        });
    </script>
</x-storefront-layout>
