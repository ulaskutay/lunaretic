<x-storefront-layout>
    @php($filters = $filters ?? new \App\Etic\Storefront\CatalogFilters)
    @php($facets = $facets ?? ['colors' => collect(), 'sizes' => collect(), 'brands' => collect()])
    @php($catalogTitle = $filters->sort === 'best_selling'
        ? __('etic.storefront.filters.best_selling')
        : (isset($currentCollection) ? $currentCollection->translateAttribute('name') : ($search ?? 'Koleksiyon')))

    <section class="etic-catalog" data-etic-catalog data-grid-columns="3">
        <header class="etic-catalog__header">
            <p>Atelier seçkisi</p>
            <h1>{{ $catalogTitle }}</h1>
            <span>{{ $products->total() }} ürün</span>
        </header>

        <div class="etic-catalog__controls">
            <button type="button" class="etic-catalog__control" data-catalog-filter-toggle aria-expanded="true">
                <span data-catalog-filter-label>Filtreleri gizle</span>
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 6h16M7 12h10m-7 6h4"/></svg>
            </button>
            <label class="etic-catalog__sort">
                <span>Sıralama</span>
                <select form="atelier-catalog-filters" onchange="this.form.elements.sort.value=this.value; this.form.submit()">
                    <option value="newest" @selected($filters->sort === 'newest')>{{ __('etic.storefront.filters.newest') }}</option>
                    <option value="best_selling" @selected($filters->sort === 'best_selling')>{{ __('etic.storefront.filters.best_selling') }}</option>
                    <option value="price_asc" @selected($filters->sort === 'price_asc')>{{ __('etic.storefront.filters.price_asc') }}</option>
                    <option value="price_desc" @selected($filters->sort === 'price_desc')>{{ __('etic.storefront.filters.price_desc') }}</option>
                </select>
            </label>
            <div class="etic-catalog__grid-options" aria-label="Ürün grid görünümü">
                @foreach([2, 3, 4] as $columns)
                    <button
                        type="button"
                        class="{{ $columns === 3 ? 'is-active' : '' }}"
                        data-catalog-grid="{{ $columns }}"
                        aria-label="{{ $columns }} sütunlu görünüm"
                        aria-pressed="{{ $columns === 3 ? 'true' : 'false' }}"
                    >
                        @for($dot = 0; $dot < $columns; $dot++)<i></i>@endfor
                    </button>
                @endforeach
            </div>
        </div>

        <div class="etic-catalog__layout">
        <aside class="etic-catalog__filters">
            @if($collections->isNotEmpty())
                <nav class="etic-catalog__collections" aria-label="Ürün kategorileri">
                    <h2>Ürün kategorileri</h2>
                    @foreach($collections as $collection)
                        @php($isCurrent = isset($currentCollection) && $currentCollection->is($collection))
                        <a href="{{ route('collection', $collection->defaultUrl?->slug) }}" @class(['is-active' => $isCurrent])>
                            <span>{{ $collection->translateAttribute('name') }}</span>
                            <i aria-hidden="true"></i>
                        </a>
                    @endforeach
                </nav>
            @endif

            <h2>Filtreler</h2>
            <form method="get" id="atelier-catalog-filters">
                <input type="hidden" name="q" value="{{ $search ?? $filters->search }}">
                <input type="hidden" name="sort" value="{{ $filters->sort }}">
                @if($facets['sizes']->isNotEmpty())
                    <label>
                        {{ __('etic.storefront.filters.size') }}
                        <select name="beden">
                            <option value="">{{ __('etic.storefront.filters.all') }}</option>
                            @foreach($facets['sizes'] as $size)
                                <option value="{{ $size->id }}" @selected($filters->size === $size->id)>{{ $size->translate('name') }}</option>
                            @endforeach
                        </select>
                    </label>
                @endif
                @if($facets['brands']->isNotEmpty())
                    <label>
                        {{ __('etic.storefront.filters.brand') }}
                        <select name="marka">
                            <option value="">{{ __('etic.storefront.filters.all') }}</option>
                            @foreach($facets['brands'] as $brand)
                                <option value="{{ $brand->id }}" @selected($filters->brand === $brand->id)>{{ $brand->name }}</option>
                            @endforeach
                        </select>
                    </label>
                @endif
                <div class="etic-catalog__price">
                    <label>{{ __('etic.storefront.filters.min') }}
                        <input type="number" name="min" min="0" step="1" value="{{ $filters->minPrice !== null ? $filters->minPrice / 100 : '' }}">
                    </label>
                    <label>{{ __('etic.storefront.filters.max') }}
                        <input type="number" name="max" min="0" step="1" value="{{ $filters->maxPrice !== null ? $filters->maxPrice / 100 : '' }}">
                    </label>
                </div>
                <label>
                    {{ __('etic.storefront.filters.availability') }}
                    <select name="stok">
                        <option value="">{{ __('etic.storefront.filters.all') }}</option>
                        <option value="1" @selected($filters->inStock)>{{ __('etic.storefront.filters.in_stock') }}</option>
                        <option value="yok" @selected($filters->outOfStock)>{{ __('etic.storefront.filters.out_of_stock') }}</option>
                    </select>
                </label>
                <button class="etic-catalog__apply">{{ __('etic.storefront.filters.apply') }}</button>
                <a href="{{ isset($currentCollection) ? route('collection', $currentCollection->defaultUrl?->slug) : route('catalog') }}" class="etic-catalog__clear">{{ __('etic.storefront.filters.clear') }}</a>
            </form>
        </aside>

        <div class="etic-catalog__results">
            <p class="etic-catalog__result-count">{{ $products->firstItem() ?? 0 }}–{{ $products->lastItem() ?? 0 }} / {{ $products->total() }}</p>
            <div class="etic-product-grid">
                @forelse($products as $product)
                    <x-theme::product-card :product="$product" />
                @empty
                    <p class="col-span-full text-sm text-muted">{{ __('etic.storefront.filters.empty') }}</p>
                @endforelse
            </div>
            <div class="etic-catalog__pagination">{{ $products->links() }}</div>
        </div>
        </div>
    </section>
</x-storefront-layout>
