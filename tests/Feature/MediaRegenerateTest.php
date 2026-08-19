<?php

use App\Etic\Media\ProductImage;
use App\Etic\Support\CommerceBootstrap;
use App\Etic\Support\StoreContext;
use Lunar\Models\Product;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

beforeEach(function () {
    app(CommerceBootstrap::class)->catalog();
});

it('regenerates product image conversions as webp and removes stale jpeg files', function () {
    $this->withoutDefer();

    $product = Product::query()->first();
    $path = sys_get_temp_dir().'/etic-boxer-'.uniqid().'.png';
    $image = imagecreatetruecolor(80, 80);
    imagefilledrectangle($image, 0, 0, 79, 79, imagecolorallocate($image, 18, 18, 18));
    imagepng($image, $path);

    $media = $product->addMedia($path)
        ->usingFileName('boxer.png')
        ->withCustomProperties(['name' => 'Boxer', 'primary' => true])
        ->toMediaCollection(config('lunar.media.collection'));

    $stale = dirname($media->getPath('large')).'/boxer-large.jpg';
    file_put_contents($stale, 'stale');

    expect(is_file($stale))->toBeTrue()
        ->and(ProductImage::url($product->fresh(), 'large'))->toContain('.webp');

    secondStore();
    app(StoreContext::class)->bindByHandle('second');

    $this->artisan('etic:media-regenerate', ['--force' => true])
        ->assertSuccessful();

    $media = $media->fresh();

    expect($media->hasGeneratedConversion('large'))->toBeTrue()
        ->and(is_file($stale))->toBeFalse()
        ->and(ProductImage::url($product->fresh(), 'large'))->toContain('.webp')
        ->and($media->getPathRelativeToRoot())->toContain('stores/omnipanel/')
        ->and(str_ends_with(strtolower($media->file_name), '.png'))->toBeTrue();

    @unlink($path);
});

it('limits regeneration to a store handle', function () {
    $this->withoutDefer();

    $product = Product::query()->first();
    $path = sys_get_temp_dir().'/etic-boxer-'.uniqid().'.png';
    $image = imagecreatetruecolor(40, 40);
    imagefilledrectangle($image, 0, 0, 39, 39, imagecolorallocate($image, 10, 10, 10));
    imagepng($image, $path);

    $product->addMedia($path)
        ->usingFileName('boxer.png')
        ->toMediaCollection(config('lunar.media.collection'));

    $this->artisan('etic:media-regenerate', [
        '--force' => true,
        '--store' => 'missing-store',
    ])->assertSuccessful();

    expect(Media::query()->count())->toBe(1);

    @unlink($path);
});
