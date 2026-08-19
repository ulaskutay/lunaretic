<?php

namespace App\Etic\Catalog\Spreadsheet;

use App\Etic\Catalog\AssignProductAvailability;
use App\Etic\Catalog\Models\Brand;
use App\Etic\Catalog\Models\CollectionGroup;
use App\Etic\Catalog\Models\ProductOption;
use App\Etic\Catalog\Models\ProductOptionValue;
use App\Etic\Catalog\Models\ProductType;
use App\Etic\Catalog\Models\TaxClass;
use App\Etic\Media\Jobs\AttachRemoteProductImagesJob;
use App\Etic\Support\StoreContext;
use App\Etic\Support\TaxClassResolver;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Lunar\FieldTypes\TranslatedText;
use Lunar\Models\Collection;
use Lunar\Models\Currency;
use Lunar\Models\Language;
use Lunar\Models\Product;
use Lunar\Models\ProductVariant;
use Lunar\Models\Url;
use Throwable;

class ProductSpreadsheetImporter
{
    /**
     * @var array<string, Brand>
     */
    private array $brands = [];

    /**
     * @var array<string, Collection>
     */
    private array $collections = [];

    /**
     * @var array<string, ProductOptionValue>
     */
    private array $optionValues = [];

    /**
     * @var array<string, Product>
     */
    private array $products = [];

    /**
     * @var array<int, true>
     */
    private array $touchedProducts = [];

    public function __construct(
        private TrendyolWorkbook $workbook,
        private StoreContext $store,
        private TaxClassResolver $taxClasses,
    ) {}

    /**
     * @return array{created_products: int, updated_products: int, created_variants: int, updated_variants: int, queued_images: int, errors: list<string>}
     */
    public function import(string $path): array
    {
        set_time_limit(0);

        $rows = $this->workbook->parse($path);
        $result = [
            'created_products' => 0,
            'updated_products' => 0,
            'created_variants' => 0,
            'updated_variants' => 0,
            'queued_images' => 0,
            'errors' => [],
        ];
        $pendingImages = [];

        foreach ($rows as $row) {
            try {
                $this->assertRow($row);
                $outcome = DB::transaction(fn (): array => $this->importRow($row));
                $result[$outcome['product']]++;
                $result[$outcome['variant']]++;

                if ($row['images'] !== []) {
                    $pendingImages[$outcome['product_id']] = array_values(array_unique(array_merge(
                        $pendingImages[$outcome['product_id']] ?? [],
                        $row['images'],
                    )));
                }
            } catch (Throwable $exception) {
                $result['errors'][] = 'Satır '.$row['row'].': '.$exception->getMessage();
            }
        }

        foreach ($pendingImages as $productId => $urls) {
            AttachRemoteProductImagesJob::dispatch((int) $productId, $urls);
            $result['queued_images'] += count($urls);
        }

        return $result;
    }

    /**
     * @param  array{row: int, sku: string, model_code: string, color: string, size: string, brand: ?string, category: ?string, name: string, description: ?string, price: ?string, stock: ?string, vat: ?string, images: list<string>, partner_id: ?string}  $row
     * @return array{product: string, variant: string, product_id: int}
     */
    private function importRow(array $row): array
    {
        $color = $this->optionValue('color', 'Renk', $row['color']);
        $size = $this->optionValue('size', 'Beden', $row['size']);
        $existing = ProductVariant::query()
            ->withTrashed()
            ->with('product')
            ->where('sku', $row['sku'])
            ->orderBy('deleted_at')
            ->first();
        $createdVariant = $existing === null || $existing->trashed();
        $this->restoreIfTrashed($existing);

        $product = $existing?->product ?? $this->productFor($row, $color);
        $createdProduct = ! isset($this->touchedProducts[$product->id])
            && ($product->wasRecentlyCreated || $product->trashed());
        $this->restoreIfTrashed($product);
        $this->touchedProducts[$product->id] = true;
        $this->fillProduct($product, $row);

        $variant = $existing ?? new ProductVariant;
        $this->fillVariant($variant, $product, $row, $color, $size);

        return [
            'product' => $createdProduct ? 'created_products' : 'updated_products',
            'variant' => $createdVariant ? 'created_variants' : 'updated_variants',
            'product_id' => $product->id,
        ];
    }

    /**
     * @param  array{row: int, sku: string, model_code: string, color: string, size: string, name: string}  $row
     */
    private function assertRow(array $row): void
    {
        if ($row['sku'] === '') {
            throw new \InvalidArgumentException('Barkod boş olamaz.');
        }

        if ($row['model_code'] === '') {
            throw new \InvalidArgumentException('Model kodu boş olamaz.');
        }

        if ($row['color'] === '') {
            throw new \InvalidArgumentException('Ürün rengi boş olamaz.');
        }

        if ($row['size'] === '') {
            throw new \InvalidArgumentException('Beden boş olamaz.');
        }

        if ($row['name'] === '') {
            throw new \InvalidArgumentException('Ürün adı boş olamaz.');
        }
    }

    private function productFor(array $row, ProductOptionValue $color): Product
    {
        $key = $row['model_code'].'|'.mb_strtolower($color->translate('name') ?? $row['color']);

        if (isset($this->products[$key])) {
            return $this->products[$key];
        }

        $product = Product::query()
            ->withTrashed()
            ->where('model_code', $row['model_code'])
            ->whereHas('variants', fn ($variants) => $variants
                ->withTrashed()
                ->whereHas('values', fn ($values) => $values->whereKey($color->id)))
            ->orderBy('deleted_at')
            ->first();

        if (! $product) {
            $product = Product::query()->create([
                'status' => 'published',
                'product_type_id' => $this->productTypeId(),
                'brand_id' => $row['brand'] ? $this->brand($row['brand'])->id : null,
                'model_code' => $row['model_code'],
                'attribute_data' => [
                    'name' => new TranslatedText(collect(['tr' => $row['name']])),
                    'description' => new TranslatedText(collect(['tr' => $this->descriptionHtml($row['description'] ?? '')])),
                ],
            ]);
        }

        return $this->products[$key] = $product;
    }

    private function fillProduct(Product $product, array $row): void
    {
        $attributes = collect($product->attribute_data ?? []);
        $attributes->put('name', new TranslatedText(collect(['tr' => $row['name']])));

        if (filled($row['description'])) {
            $attributes->put('description', new TranslatedText(collect(['tr' => $this->descriptionHtml($row['description'])])));
        }

        $product->fill([
            'status' => 'published',
            'model_code' => $row['model_code'],
            'brand_id' => $row['brand'] ? $this->brand($row['brand'])->id : $product->brand_id,
            'attribute_data' => $attributes,
        ]);
        $product->save();

        $this->assignChannel($product);
        $this->syncOptions($product);
        $this->syncSlug($product, $row['name'], $row['color']);

        if ($row['category']) {
            $collection = $this->collection($row['category']);
            $product->collections()->syncWithoutDetaching([
                $collection->id => ['position' => 1],
            ]);
        }
    }

    private function fillVariant(
        ProductVariant $variant,
        Product $product,
        array $row,
        ProductOptionValue $color,
        ProductOptionValue $size,
    ): void {
        $stock = max(0, (int) round((float) str_replace(',', '.', (string) ($row['stock'] ?? 0))));
        $variant->fill([
            'product_id' => $product->id,
            'tax_class_id' => $this->taxClass($row['vat'] ?? null)->id,
            'sku' => $row['sku'],
            'mpn' => $row['model_code'],
            'stock' => $stock,
            'purchasable' => 'in_stock',
            'shippable' => true,
            'unit_quantity' => 1,
        ]);
        $variant->save();
        $variant->values()->sync([$color->id, $size->id]);
        $this->syncPrice($variant, $row['price'] ?? null);
    }

    private function syncPrice(ProductVariant $variant, ?string $price): void
    {
        if ($price === null || $price === '') {
            return;
        }

        $currency = Currency::getDefault();
        $minor = (int) round(((float) str_replace(',', '.', $price)) * $currency->factor);
        $existing = $variant->prices()->where('currency_id', $currency->id)->first();

        if ($existing) {
            $existing->update(['price' => $minor]);

            return;
        }

        $variant->prices()->create([
            'price' => $minor,
            'currency_id' => $currency->id,
            'min_quantity' => 1,
        ]);
    }

    private function optionValue(string $handle, string $label, string $name): ProductOptionValue
    {
        $key = $handle.':'.mb_strtolower($name);

        if (isset($this->optionValues[$key])) {
            return $this->optionValues[$key];
        }

        $option = ProductOption::query()->firstOrCreate(
            ['handle' => $handle, 'store_id' => app(StoreContext::class)->store()?->id],
            [
                'name' => ['tr' => $label],
                'label' => ['tr' => $label],
                'shared' => true,
            ]
        );

        $match = $option->values()->get()->first(
            fn (ProductOptionValue $value) => mb_strtolower((string) $value->translate('name')) === mb_strtolower($name)
        );

        if (! $match) {
            $match = ProductOptionValue::query()->create([
                'product_option_id' => $option->id,
                'name' => ['tr' => $name],
                'position' => (int) $option->values()->max('position') + 1,
            ]);
            $option->unsetRelation('values');
        }

        return $this->optionValues[$key] = $match;
    }

    private function brand(string $name): Brand
    {
        $key = mb_strtolower($name);

        return $this->brands[$key] ??= Brand::query()->firstOrCreate([
            'name' => $name,
            'store_id' => app(StoreContext::class)->store()?->id,
        ]);
    }

    private function collection(string $name): Collection
    {
        $key = mb_strtolower($name);

        if (isset($this->collections[$key])) {
            return $this->collections[$key];
        }

        $group = CollectionGroup::query()->firstOrCreate(
            ['handle' => 'kategoriler', 'store_id' => app(StoreContext::class)->store()?->id],
            ['name' => 'Kategoriler']
        );

        $collection = Collection::query()
            ->where('collection_group_id', $group->id)
            ->get()
            ->first(fn (Collection $item) => mb_strtolower((string) $item->translateAttribute('name')) === $key);

        if (! $collection) {
            $collection = Collection::query()->create([
                'collection_group_id' => $group->id,
                'type' => 'main',
                'sort' => 'custom',
                'attribute_data' => [
                    'name' => new TranslatedText(collect(['tr' => $name])),
                    'description' => new TranslatedText(collect(['tr' => ''])),
                ],
            ]);
        }

        $this->syncSlug($collection, $name);

        return $this->collections[$key] = $collection;
    }

    private function syncOptions(Product $product): void
    {
        $color = ProductOption::query()->where('handle', 'color')->first();
        $size = ProductOption::query()->where('handle', 'size')->first();
        $sync = [];

        if ($color) {
            $sync[$color->id] = ['position' => 1];
        }

        if ($size) {
            $sync[$size->id] = ['position' => 2];
        }

        if ($sync !== []) {
            $product->productOptions()->syncWithoutDetaching($sync);
        }
    }

    private function assignChannel(Product $product): void
    {
        app(AssignProductAvailability::class)->handle($product);
    }

    private function syncSlug(Product|Collection $model, string $name, ?string $suffix = null): void
    {
        $base = Str::slug(trim($name.($suffix ? ' '.$suffix : ''))) ?: 'urun';
        $language = Language::getDefault();
        $url = $model->urls()->where('language_id', $language->id)->first();
        $slug = $this->uniqueSlug($base, $url?->id);

        if ($url) {
            if ($url->slug !== $slug) {
                $url->update(['slug' => $slug, 'default' => true]);
            }

            return;
        }

        $model->urls()->create([
            'default' => true,
            'language_id' => $language->id,
            'slug' => $slug,
        ]);
    }

    private function uniqueSlug(string $base, ?int $ignoreId = null): string
    {
        $candidate = $base;
        $i = 2;

        while ($this->slugTaken($candidate, $ignoreId)) {
            $candidate = $base.'-'.$i;
            $i++;
        }

        return $candidate;
    }

    private function slugTaken(string $slug, ?int $ignoreId = null): bool
    {
        return Url::query()
            ->where('slug', $slug)
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->exists();
    }

    private function restoreIfTrashed(Product|ProductVariant|null $model): void
    {
        if ($model?->trashed()) {
            $model->restore();
        }
    }

    private function productTypeId(): int
    {
        return (int) (ProductType::query()->where('store_id', app(StoreContext::class)->store()?->id)->value('id')
            ?? ProductType::query()->create([
                'name' => 'Ürün',
                'store_id' => app(StoreContext::class)->store()?->id,
            ])->id);
    }

    private function taxClass(?string $vat): TaxClass
    {
        return $this->taxClasses->resolve($vat);
    }

    private function descriptionHtml(?string $text): string
    {
        $text = trim((string) $text);

        if ($text === '') {
            return '';
        }

        $parts = collect(preg_split('/;\s*/', $text) ?: [])
            ->map(fn (string $part) => trim($part, " \t-"))
            ->filter();

        if ($parts->count() > 1) {
            return '<ul>'.$parts->map(fn (string $part) => '<li>'.e($part).'</li>')->implode('').'</ul>';
        }

        return '<p>'.e($text).'</p>';
    }
}
