<?php

namespace App\Etic\Storefront\Http\Api;

use App\Etic\Catalog\Models\Brand;
use App\Etic\CMS\Models\BlogCategory;
use App\Etic\CMS\Models\BlogPost;
use App\Etic\CMS\Models\Page;
use App\Etic\Integrations\Marketing\TrackingDispatcher;
use App\Etic\SEO\CanonicalUrl;
use App\Etic\SEO\SchemaBuilder;
use App\Etic\Storefront\CatalogFilters;
use App\Etic\Storefront\CatalogQuery;
use App\Etic\Storefront\StorefrontPaths;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StoreApiController
{
    public function __construct(private StorefrontPresenter $present) {}

    public function bootstrap(): JsonResponse
    {
        return response()->json(['data' => $this->present->bootstrap()]);
    }

    public function products(Request $request, CatalogQuery $catalog, TrackingDispatcher $tracking): JsonResponse
    {
        $filters = CatalogFilters::fromRequest($request);
        $collection = filled($request->string('collection')->toString())
            ? $catalog->collectionBySlug($request->string('collection')->toString())
            : null;

        if ($filters->search) {
            $tracking->record('search', ['search_term' => $filters->search]);
        } else {
            $tracking->record('view_category', ['item_list_id' => $collection?->defaultUrl?->slug ?? 'catalog']);
        }

        $paginator = $catalog->publishedProducts($filters, $collection);

        return response()->json([
            ...$this->present->paginated($paginator, fn ($product) => $this->present->productCard($product)),
            'facets' => $this->facets($catalog),
            'collections' => $catalog->collections()->map(fn ($item) => $this->present->collectionCard($item))->values()->all(),
            'collection' => $collection ? $this->present->collectionCard($collection) : null,
            'events' => $this->present->trackingEvents(),
        ]);
    }

    public function product(string $slug, CatalogQuery $catalog, TrackingDispatcher $tracking, SchemaBuilder $schema, CanonicalUrl $canonical): JsonResponse
    {
        $product = $catalog->productBySlug($slug);
        $colorVariants = $catalog->colorVariantProducts($product);
        $variant = $product->variants->first();
        $price = $variant?->prices->first();
        $tracking->record('view_item', [
            'item_id' => $variant?->sku,
            'item_name' => $product->translateAttribute('name'),
            'value' => TrackingDispatcher::fromMinor((int) ($price?->price?->value ?? 0)),
            'currency' => $price?->currency?->code ?? 'TRY',
        ]);

        $url = $canonical->forPath(StorefrontPaths::product($slug));
        $data = $this->present->productDetail($product, $colorVariants);

        return response()->json([
            'data' => $data,
            'schema' => $schema->product(
                $product->translateAttribute('name') ?? '',
                $url,
                (int) ($price?->price?->value ?? 0),
                $price?->currency?->code ?? 'TRY',
                (bool) $variant?->canBeFulfilledAtQuantity(1),
                $data['image'] ?? null,
            ),
            'events' => $this->present->trackingEvents(),
        ]);
    }

    public function categories(CatalogQuery $catalog): JsonResponse
    {
        return response()->json([
            'data' => $catalog->collections()->map(fn ($collection) => $this->present->collectionCard($collection))->values()->all(),
        ]);
    }

    public function brands(): JsonResponse
    {
        return response()->json([
            'data' => Brand::query()->orderBy('name')->get()->map(fn ($brand) => $this->present->brand($brand))->values()->all(),
        ]);
    }

    public function pages(): JsonResponse
    {
        return response()->json([
            'data' => Page::query()->forStore()->where('is_published', true)->get(['id', 'title', 'slug']),
        ]);
    }

    public function page(string $slug): JsonResponse
    {
        $page = Page::query()->forStore()->where('slug', $slug)->where('is_published', true)->with('seo')->firstOrFail();

        return response()->json(['data' => $this->present->page($page)]);
    }

    public function blogIndex(Request $request): JsonResponse
    {
        $categorySlug = $request->string('kategori')->toString() ?: null;
        $posts = BlogPost::query()
            ->forStore()
            ->published()
            ->with(['category', 'seo'])
            ->when($categorySlug, fn ($query) => $query->whereHas('category', fn ($category) => $category->where('slug', $categorySlug)))
            ->latest('published_at')
            ->paginate(10)
            ->withQueryString();

        return response()->json([
            ...$this->present->paginated($posts, fn (BlogPost $post) => $this->present->blogPost($post)),
            'categories' => BlogCategory::query()->forStore()->orderBy('name')->get(['id', 'name', 'slug']),
            'category' => $categorySlug,
        ]);
    }

    public function blogShow(string $slug, CanonicalUrl $canonical, SchemaBuilder $schema): JsonResponse
    {
        $post = BlogPost::query()->forStore()->published()->with(['category', 'tags', 'seo'])->where('slug', $slug)->firstOrFail();

        return response()->json([
            'data' => $this->present->blogPost($post, true),
            'related' => BlogPost::query()
                ->forStore()
                ->published()
                ->with('category')
                ->whereKeyNot($post->id)
                ->when($post->blog_category_id, fn ($query) => $query->where('blog_category_id', $post->blog_category_id))
                ->latest('published_at')
                ->limit(3)
                ->get()
                ->map(fn (BlogPost $item) => $this->present->blogPost($item))
                ->values()
                ->all(),
            'schema' => $schema->article($post->title, $canonical->forPath('blog/'.$slug), $post->published_at?->toIso8601String()),
        ]);
    }

    private function facets(CatalogQuery $catalog): array
    {
        $facets = $catalog->facets();

        return [
            'colors' => $facets['colors']->map(fn ($value) => $this->present->optionValue($value))->values()->all(),
            'sizes' => $facets['sizes']->map(fn ($value) => $this->present->optionValue($value))->values()->all(),
            'brands' => $facets['brands']->map(fn ($brand) => $this->present->brand($brand))->values()->all(),
        ];
    }
}
