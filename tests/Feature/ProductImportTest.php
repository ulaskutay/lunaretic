<?php

use App\Etic\Catalog\Filament\Pages\ImportProductsPage;
use App\Etic\Catalog\Jobs\ImportProductsJob;
use App\Etic\Catalog\Spreadsheet\ProductSpreadsheetImporter;
use App\Etic\Catalog\Spreadsheet\TrendyolWorkbook;
use App\Etic\Media\Jobs\AttachRemoteProductImagesJob;
use App\Etic\Support\CommerceBootstrap;
use App\Etic\Support\StaffNotifier;
use App\Etic\Support\StoreContext;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Lunar\Admin\Models\Staff;
use Lunar\Models\Product;
use Lunar\Models\ProductVariant;

beforeEach(function () {
    app(CommerceBootstrap::class)->catalog();
});

it('imports trendyol rows as color products grouped by model code', function () {
    $path = app(TrendyolWorkbook::class)->write([
        [
            'sku' => 'ASK-TNK-MS01BJ-36',
            'model_code' => 'ASK-TNK-MS-01',
            'color' => 'Bej',
            'size' => '36-38',
            'brand' => 'Asya Karen',
            'category' => 'Tesettür Tunik',
            'name' => 'Müslin Tunik',
            'description' => '- Rahat kesim.; - Müslin kumaş.',
            'price' => '1290',
            'stock' => '2',
            'vat' => '10',
        ],
        [
            'sku' => 'ASK-TNK-MS01BJ-40',
            'model_code' => 'ASK-TNK-MS-01',
            'color' => 'Bej',
            'size' => '40-42',
            'brand' => 'Asya Karen',
            'category' => 'Tesettür Tunik',
            'name' => 'Müslin Tunik',
            'price' => '1290',
            'stock' => '4',
        ],
        [
            'sku' => 'ASK-TNK-MS01SYH-36',
            'model_code' => 'ASK-TNK-MS-01',
            'color' => 'Siyah',
            'size' => '36-38',
            'brand' => 'Asya Karen',
            'category' => 'Tesettür Tunik',
            'name' => 'Müslin Tunik',
            'price' => '1290',
            'stock' => '1',
        ],
        [
            'sku' => 'ASK-TNK-MS01SYH-40',
            'model_code' => 'ASK-TNK-MS-01',
            'color' => 'Siyah',
            'size' => '40-42',
            'brand' => 'Asya Karen',
            'category' => 'Tesettür Tunik',
            'name' => 'Müslin Tunik',
            'price' => '1290',
            'stock' => '3',
        ],
    ]);

    $result = app(ProductSpreadsheetImporter::class)->import($path);

    expect($result['errors'])->toBe([])
        ->and($result['created_products'])->toBe(2)
        ->and($result['created_variants'])->toBe(4);

    $products = Product::query()->where('model_code', 'ASK-TNK-MS-01')->with(['variants.values.option', 'collections', 'brand'])->get();

    expect($products)->toHaveCount(2)
        ->and($products->every(fn (Product $product) => $product->status === 'published'))->toBeTrue()
        ->and($products->pluck('brand.name')->unique()->all())->toBe(['Asya Karen']);

    $beige = $products->first(function (Product $product) {
        return $product->variants->contains(
            fn (ProductVariant $variant) => $variant->values->contains(
                fn ($value) => $value->option?->handle === 'color' && $value->translate('name') === 'Bej'
            )
        );
    });
    $black = $products->first(function (Product $product) {
        return $product->variants->contains(
            fn (ProductVariant $variant) => $variant->values->contains(
                fn ($value) => $value->option?->handle === 'color' && $value->translate('name') === 'Siyah'
            )
        );
    });

    expect($beige)->not->toBeNull()
        ->and($black)->not->toBeNull()
        ->and($beige->variants)->toHaveCount(2)
        ->and($black->variants)->toHaveCount(2)
        ->and($beige->variants->pluck('sku')->sort()->values()->all())->toBe(['ASK-TNK-MS01BJ-36', 'ASK-TNK-MS01BJ-40'])
        ->and((int) $beige->variants->firstWhere('sku', 'ASK-TNK-MS01BJ-36')->prices->first()->price->value)->toBe(129000)
        ->and($beige->collections->pluck('id'))->not->toBeEmpty();

    $card = collect($this->getJson('/api/v1/products')->assertOk()->json('data'))
        ->firstWhere('id', $beige->id);

    expect($card['color_variants'])->toHaveCount(2)
        ->and(collect($card['color_variants'])->pluck('id')->sort()->values()->all())
        ->toBe(collect([$beige->id, $black->id])->sort()->values()->all());
});

it('recreates products that were deleted before the same excel is imported again', function () {
    $workbook = app(TrendyolWorkbook::class);
    $path = $workbook->write([[
        'sku' => 'ASK-TNK-DEL-36',
        'model_code' => 'ASK-TNK-DEL',
        'color' => 'Bej',
        'size' => '36-38',
        'brand' => 'Asya Karen',
        'name' => 'Silinen Tunik',
        'price' => '1290',
        'stock' => '2',
    ]]);

    app(ProductSpreadsheetImporter::class)->import($path);

    $product = Product::query()->where('model_code', 'ASK-TNK-DEL')->firstOrFail();
    $product->delete();

    expect(Product::query()->where('model_code', 'ASK-TNK-DEL')->exists())->toBeFalse()
        ->and(Product::withTrashed()->where('model_code', 'ASK-TNK-DEL')->exists())->toBeTrue();

    $result = app(ProductSpreadsheetImporter::class)->import($path);
    $restored = Product::query()->where('model_code', 'ASK-TNK-DEL')->with('variants')->first();

    expect($result['errors'])->toBe([])
        ->and($result['created_products'])->toBe(1)
        ->and($result['updated_variants'])->toBe(1)
        ->and($restored)->not->toBeNull()
        ->and($restored->trashed())->toBeFalse()
        ->and($restored->status)->toBe('published')
        ->and($restored->id)->toBe($product->id)
        ->and($restored->variants)->toHaveCount(1)
        ->and($restored->variants->first()->sku)->toBe('ASK-TNK-DEL-36')
        ->and($restored->variants->first()->trashed())->toBeFalse();
});

it('applies the vat rate from excel when calculating cart tax', function () {
    $path = app(TrendyolWorkbook::class)->write([[
        'sku' => 'ASK-TNK-VAT20-36',
        'model_code' => 'ASK-TNK-VAT20',
        'color' => 'Yeşil',
        'size' => '36-38',
        'brand' => 'Asya Karen',
        'name' => 'KDV Test Tunik',
        'price' => '1490',
        'stock' => '2',
        'vat' => '20',
    ]]);

    app(ProductSpreadsheetImporter::class)->import($path);

    $variant = ProductVariant::query()->where('sku', 'ASK-TNK-VAT20-36')->with('taxClass.taxRateAmounts')->firstOrFail();
    $rate = $variant->taxClass->taxRateAmounts->first();

    expect((float) $rate->percentage)->toBe(20.0);

    $cart = \Lunar\Models\Cart::create([
        'currency_id' => $variant->prices->first()->currency_id,
        'channel_id' => app(\App\Etic\Support\StoreContext::class)->channel()->id,
    ]);
    $cart->add($variant, 1);
    $cart = $cart->calculate();

    expect((int) $cart->total->value)->toBe(149000)
        ->and((int) $cart->taxTotal->value)->toBe(24833);
});

it('updates stock and price for an existing barcode', function () {
    $workbook = app(TrendyolWorkbook::class);
    $first = $workbook->write([[
        'sku' => 'ASK-TNK-001YSL-36',
        'model_code' => 'ASK-TNK-001',
        'color' => 'Yeşil',
        'size' => '36-38',
        'brand' => 'Asya Karen',
        'name' => 'Kemerli Tunik',
        'price' => '1490',
        'stock' => '2',
    ]]);

    app(ProductSpreadsheetImporter::class)->import($first);

    $second = $workbook->write([[
        'sku' => 'ASK-TNK-001YSL-36',
        'model_code' => 'ASK-TNK-001',
        'color' => 'Yeşil',
        'size' => '36-38',
        'brand' => 'Asya Karen',
        'name' => 'Kemerli Regular Tunik',
        'price' => '1590',
        'stock' => '8',
    ]]);

    $result = app(ProductSpreadsheetImporter::class)->import($second);
    $variant = ProductVariant::query()->where('sku', 'ASK-TNK-001YSL-36')->first();

    expect($result['errors'])->toBe([])
        ->and($result['created_products'])->toBe(0)
        ->and($result['updated_variants'])->toBe(1)
        ->and($variant)->not->toBeNull()
        ->and($variant->stock)->toBe(8)
        ->and((int) $variant->prices->first()->price->value)->toBe(159000)
        ->and($variant->product->translateAttribute('name'))->toBe('Kemerli Regular Tunik');
});

it('attaches product images from excel urls', function () {
    Storage::fake('public');
    Http::fake([
        'https://cdn.example.test/*' => Http::response(testJpeg(), 200, ['Content-Type' => 'image/jpeg']),
    ]);

    $path = app(TrendyolWorkbook::class)->write([[
        'sku' => 'ASK-IMG-01BJ-36',
        'model_code' => 'ASK-IMG-01',
        'color' => 'Bej',
        'size' => '36-38',
        'brand' => 'Asya Karen',
        'name' => 'Görselli Tunik',
        'price' => '1290',
        'stock' => '2',
        'images' => [
            'https://cdn.example.test/one.jpg',
            'https://cdn.example.test/two.jpg',
        ],
    ]]);

    $result = app(ProductSpreadsheetImporter::class)->import($path);
    $product = Product::query()->where('model_code', 'ASK-IMG-01')->first();
    $media = $product->getMedia((string) config('lunar.media.collection', 'images'));

    expect($result['errors'])->toBe([])
        ->and($result['queued_images'])->toBe(2)
        ->and($media)->toHaveCount(2)
        ->and((bool) $media->first()->getCustomProperty('primary'))->toBeTrue();
});

it('still imports the product when image urls cannot be downloaded', function () {
    Http::fake([
        'https://cdn.example.test/*' => Http::response('forbidden', 403),
    ]);

    $path = app(TrendyolWorkbook::class)->write([[
        'sku' => 'ASK-IMG-02SYH-36',
        'model_code' => 'ASK-IMG-02',
        'color' => 'Siyah',
        'size' => '36-38',
        'brand' => 'Asya Karen',
        'name' => 'Görselsiz Tunik',
        'price' => '1290',
        'stock' => '1',
        'images' => ['https://cdn.example.test/missing.jpg'],
    ]]);

    $result = app(ProductSpreadsheetImporter::class)->import($path);
    $product = Product::query()->where('model_code', 'ASK-IMG-02')->first();

    expect($result['errors'])->toBe([])
        ->and($result['created_products'])->toBe(1)
        ->and($result['queued_images'])->toBe(1)
        ->and($product)->not->toBeNull()
        ->and($product->getMedia((string) config('lunar.media.collection', 'images')))->toHaveCount(0);
});

it('dispatches image downloads as queued jobs', function () {
    Queue::fake();

    $path = app(TrendyolWorkbook::class)->write([[
        'sku' => 'ASK-IMG-03BJ-36',
        'model_code' => 'ASK-IMG-03',
        'color' => 'Bej',
        'size' => '36-38',
        'brand' => 'Asya Karen',
        'name' => 'Kuyruk Görsel',
        'price' => '1290',
        'stock' => '1',
        'images' => ['https://cdn.example.test/one.jpg'],
    ]]);

    $result = app(ProductSpreadsheetImporter::class)->import($path);

    Queue::assertPushed(AttachRemoteProductImagesJob::class, function (AttachRemoteProductImagesJob $job): bool {
        return $job->urls === ['https://cdn.example.test/one.jpg'];
    });

    expect($result['queued_images'])->toBe(1)
        ->and(Product::query()->where('model_code', 'ASK-IMG-03')->first()?->getMedia((string) config('lunar.media.collection', 'images')))
        ->toHaveCount(0);
});

it('queues the spreadsheet from the admin page without importing inline', function () {
    Queue::fake();
    app(CommerceBootstrap::class)->admin();
    $staff = Staff::query()->firstOrFail();

    $source = app(TrendyolWorkbook::class)->write([[
        'sku' => 'ASK-Q-01BJ-36',
        'model_code' => 'ASK-Q-01',
        'color' => 'Bej',
        'size' => '36-38',
        'brand' => 'Asya Karen',
        'name' => 'Kuyruk Tunik',
        'price' => '1290',
        'stock' => '2',
    ]]);

    Livewire::actingAs($staff, 'staff')
        ->test(ImportProductsPage::class)
        ->fillForm([
            'file' => UploadedFile::fake()->createWithContent(
                'urunler.xlsx',
                (string) file_get_contents($source),
            ),
        ])
        ->call('import')
        ->assertNotified(__('etic.filament.catalog.import.queued'));

    Queue::assertPushed(ImportProductsJob::class);
    expect(Product::query()->where('model_code', 'ASK-Q-01')->exists())->toBeFalse();
});

it('notifies staff when a queued import finishes', function () {
    app(CommerceBootstrap::class)->admin();
    $staff = Staff::query()->firstOrFail();
    $source = app(TrendyolWorkbook::class)->write([[
        'sku' => 'ASK-JOB-01BJ-36',
        'model_code' => 'ASK-JOB-01',
        'color' => 'Bej',
        'size' => '36-38',
        'brand' => 'Asya Karen',
        'name' => 'İş Tunik',
        'price' => '1290',
        'stock' => '2',
    ]]);
    $stored = 'imports/job-'.uniqid('', true).'.xlsx';
    Storage::disk('local')->put($stored, (string) file_get_contents($source));

    (new ImportProductsJob($stored, $staff->id, 'boxers'))->handle(
        app(ProductSpreadsheetImporter::class),
        app(StoreContext::class),
        app(StaffNotifier::class),
    );

    expect(ProductVariant::query()->where('sku', 'ASK-JOB-01BJ-36')->exists())->toBeTrue()
        ->and(Storage::disk('local')->exists($stored))->toBeFalse()
        ->and($staff->fresh()->notifications)->toHaveCount(1);
});

function testJpeg(): string
{
    $path = sys_get_temp_dir().'/etic-import-'.uniqid('', true).'.jpg';
    $image = imagecreatetruecolor(16, 16);
    imagefilledrectangle($image, 0, 0, 15, 15, imagecolorallocate($image, 30, 30, 30));
    imagejpeg($image, $path, 80);
    imagedestroy($image);
    $bytes = (string) file_get_contents($path);
    @unlink($path);

    return $bytes;
}
