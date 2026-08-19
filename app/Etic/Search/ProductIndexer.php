<?php

namespace App\Etic\Search;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use App\Etic\Storefront\StorefrontPaths;
use Lunar\Facades\AttributeManifest;
use Lunar\FieldTypes\TranslatedText;
use Lunar\Models\Product as LunarProduct;
use Lunar\Search\ProductIndexer as LunarProductIndexer;

class ProductIndexer extends LunarProductIndexer
{
    public function getFilterableFields(): array
    {
        return [
            ...parent::getFilterableFields(),
            'channel_ids',
            'brand_id',
        ];
    }

    public function shouldBeSearchable(Model $model): bool
    {
        return $model->status === 'published';
    }

    public function makeAllSearchableUsing(Builder $query): Builder
    {
        return parent::makeAllSearchableUsing($query)->with(['channels', 'defaultUrl']);
    }

    public function toSearchableArray(Model $model): array
    {
        $data = array_merge([
            'id' => (string) $model->id,
            'status' => $model->status,
            'product_type' => $model->productType?->name,
            'brand' => $model->brand?->name,
            'created_at' => (int) $model->created_at->timestamp,
        ], $this->mapSearchableAttributes($model));

        if ($thumbnail = $model->thumbnail) {
            $data['thumbnail'] = $thumbnail->getUrl('small');
        }

        $data['skus'] = $model->variants->pluck('sku')->toArray();

        $slug = $model->defaultUrl?->slug;
        $data['url'] = filled($slug) ? StorefrontPaths::product((string) $slug) : null;

        $data['channel_ids'] = $model->channels
            ->pluck('id')
            ->map(fn (mixed $id): string => (string) $id)
            ->values()
            ->all();

        if ($model->brand_id) {
            $data['brand_id'] = (string) $model->brand_id;
        }

        return $data;
    }

    protected function mapSearchableAttributes(Model $model): array
    {
        $data = $this->attributesForType($model, $model->getMorphClass());

        if ($data === []) {
            $data = $this->attributesForType($model, LunarProduct::class);
        }

        $name = $model->translateAttribute('name');

        if (filled($name)) {
            $data['name'] = (string) $name;
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    private function attributesForType(Model $model, string $attributeType): array
    {
        $attributes = AttributeManifest::getSearchableAttributes($attributeType);
        $attributeData = $model->attribute_data;

        if ($attributes->isEmpty() || ! $attributeData) {
            return [];
        }

        $data = [];

        foreach ($attributes as $attribute) {
            $attributeValue = $attributeData->get($attribute->handle);

            if ($attributeValue instanceof TranslatedText) {
                foreach ($attributeValue->getValue() as $locale => $text) {
                    $data[$attribute->handle.'_'.$locale] = $text?->getValue();
                }

                continue;
            }

            $data[$attribute->handle] = $model->attr($attribute->handle);
        }

        return $data;
    }
}
