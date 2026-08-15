<?php

namespace App\Etic\Storefront\Http\Api;

use App\Etic\CMS\Models\Page;
use App\Etic\Storefront\CartManager;
use App\Etic\Storefront\CatalogQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Lunar\Models\Brand;
use Lunar\Models\Collection;

class StoreApiController
{
    public function products(Request $request, CatalogQuery $catalog): JsonResponse
    {
        $paginator = $catalog->publishedProducts($request->string('q')->toString() ?: null);

        return response()->json([
            'data' => $paginator->through(fn ($product) => [
                'id' => $product->id,
                'name' => $product->translateAttribute('name'),
                'slug' => $product->defaultUrl?->slug,
                'status' => $product->status,
                'image' => \App\Etic\Media\ProductImage::url($product, 'medium'),
            ]),
        ]);
    }

    public function categories(): JsonResponse
    {
        return response()->json([
            'data' => Collection::query()->get()->map(fn ($collection) => [
                'id' => $collection->id,
                'name' => $collection->translateAttribute('name'),
                'slug' => $collection->defaultUrl?->slug,
            ]),
        ]);
    }

    public function brands(): JsonResponse
    {
        return response()->json([
            'data' => Brand::query()->get(['id', 'name']),
        ]);
    }

    public function pages(): JsonResponse
    {
        return response()->json([
            'data' => Page::query()->where('is_published', true)->get(['id', 'title', 'slug']),
        ]);
    }

    public function cart(CartManager $carts): JsonResponse
    {
        $cart = $carts->current()->calculate();

        return response()->json([
            'data' => [
                'id' => $cart->id,
                'lines' => $cart->lines->map(fn ($line) => [
                    'id' => $line->id,
                    'sku' => $line->purchasable?->sku,
                    'quantity' => $line->quantity,
                    'total' => $line->total?->value,
                ]),
                'total' => $cart->total?->value,
            ],
        ]);
    }
}
