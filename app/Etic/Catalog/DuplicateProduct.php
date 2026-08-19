<?php

namespace App\Etic\Catalog;

use App\Etic\Catalog\AssignProductAvailability;
use App\Etic\Support\StoreContext;
use Illuminate\Support\Collection;
use Lunar\Facades\DB;
use Lunar\FieldTypes\Text;
use Lunar\FieldTypes\TranslatedText;
use Lunar\Models\Contracts\Product as ProductContract;
use Lunar\Models\Product;
use Lunar\Models\ProductVariant;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class DuplicateProduct
{
    public function handle(ProductContract $product): Product
    {
        $product->load([
            'variants.prices',
            'variants.values',
            'variants.images',
            'collections',
            'productOptions',
            'tags',
            'channels',
            'customerGroups',
            'associations',
            'media',
        ]);

        return DB::transaction(function () use ($product): Product {
            $copy = Product::query()->create([
                'status' => 'draft',
                'product_type_id' => $product->product_type_id,
                'brand_id' => $product->brand_id,
                'model_code' => $product->model_code,
                'attribute_data' => $this->copiedAttributes($product),
            ]);

            $this->syncRelations($product, $copy);
            $mediaMap = $this->copyMedia($product, $copy);
            $this->copyVariants($product, $copy, $mediaMap);
            $this->copyAssociations($product, $copy);

            return $copy->refresh();
        });
    }

    /**
     * @return Collection<string, mixed>
     */
    protected function copiedAttributes(ProductContract $product): Collection
    {
        $attributes = collect($product->attribute_data ?? []);
        $name = $attributes->get('name');

        if (! $name instanceof TranslatedText) {
            return $attributes;
        }

        $suffix = ' '.__('etic.filament.catalog.duplicate.name_suffix');

        return $attributes->put('name', new TranslatedText(
            $name->getValue()->map(function ($item) use ($suffix) {
                $value = $item instanceof Text ? $item->getValue() : (string) $item;

                return new Text(trim($value).$suffix);
            })
        ));
    }

    protected function syncRelations(ProductContract $product, Product $copy): void
    {
        $channelId = app(StoreContext::class)->channelId();
        $channels = $product->channels;

        if ($channelId) {
            $channels = $channels->where('id', $channelId);
        }

        $copy->channels()->sync(
            $channels->mapWithKeys(fn ($channel) => [
                $channel->id => [
                    'enabled' => (bool) $channel->pivot->enabled,
                    'starts_at' => $channel->pivot->starts_at,
                    'ends_at' => $channel->pivot->ends_at,
                ],
            ])->all()
        );

        $copy->customerGroups()->sync(
            $product->customerGroups->mapWithKeys(fn ($group) => [
                $group->id => [
                    'enabled' => (bool) $group->pivot->enabled,
                    'visible' => (bool) $group->pivot->visible,
                    'purchasable' => (bool) $group->pivot->purchasable,
                    'starts_at' => $group->pivot->starts_at,
                    'ends_at' => $group->pivot->ends_at,
                ],
            ])->all()
        );

        if ($copy->customerGroups()->wherePivot('purchasable', true)->wherePivot('enabled', true)->doesntExist()) {
            app(AssignProductAvailability::class)->handle($copy);
        }

        $copy->collections()->sync(
            $product->collections->mapWithKeys(fn ($collection) => [
                $collection->id => ['position' => $collection->pivot->position],
            ])->all()
        );

        $copy->productOptions()->sync(
            $product->productOptions->mapWithKeys(fn ($option) => [
                $option->id => ['position' => $option->pivot->position],
            ])->all()
        );

        $copy->tags()->sync($product->tags->modelKeys());
    }

    /**
     * @return array<int, Media>
     */
    protected function copyMedia(ProductContract $product, Product $copy): array
    {
        $map = [];

        foreach ($product->media as $media) {
            try {
                $map[$media->id] = $media->copy($copy, $media->collection_name);
            } catch (\Throwable) {
                continue;
            }
        }

        return $map;
    }

    /**
     * @param  array<int, Media>  $mediaMap
     */
    protected function copyVariants(ProductContract $product, Product $copy, array $mediaMap): void
    {
        foreach ($product->variants as $variant) {
            $replica = $variant->replicate([
                'sku',
                'gtin',
                'ean',
                'mpn',
            ]);
            $replica->product_id = $copy->id;
            $replica->sku = $this->uniqueSku($variant->sku);
            $replica->gtin = null;
            $replica->ean = null;
            $replica->mpn = null;
            $replica->save();

            $replica->values()->sync($variant->values->modelKeys());

            foreach ($variant->prices as $price) {
                $replica->prices()->create([
                    'customer_group_id' => $price->customer_group_id,
                    'currency_id' => $price->currency_id,
                    'price' => $price->price->value,
                    'compare_price' => $price->compare_price?->value,
                    'min_quantity' => $price->min_quantity,
                ]);
            }

            foreach ($variant->images as $image) {
                $copied = $mediaMap[$image->id] ?? null;

                if (! $copied) {
                    continue;
                }

                $replica->images()->attach($copied->id, [
                    'primary' => (bool) $image->pivot->primary,
                    'position' => $image->pivot->position,
                ]);
            }
        }
    }

    protected function copyAssociations(ProductContract $product, Product $copy): void
    {
        foreach ($product->associations as $association) {
            if ((int) $association->product_target_id === (int) $product->id) {
                continue;
            }

            $copy->associations()->create([
                'product_target_id' => $association->product_target_id,
                'type' => $association->type,
            ]);
        }
    }

    protected function uniqueSku(?string $sku): string
    {
        $base = trim((string) $sku) !== '' ? $sku.'-KOPYA' : 'KOPYA';
        $candidate = $base;
        $i = 2;

        while (ProductVariant::query()->where('sku', $candidate)->exists()) {
            $candidate = $base.'-'.$i;
            $i++;
        }

        return mb_substr($candidate, 0, 255);
    }
}
