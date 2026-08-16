<?php

namespace App\Etic\Media\Jobs;

use App\Etic\Media\RemoteProductImages;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Lunar\Models\Product;

class AttachRemoteProductImagesJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 180;

    public int $tries = 3;

    /**
     * @param  list<string>  $urls
     */
    public function __construct(
        public int $productId,
        public array $urls,
    ) {}

    /**
     * @return list<int>
     */
    public function backoff(): array
    {
        return [20, 60];
    }

    public function handle(RemoteProductImages $images): void
    {
        $product = Product::query()->find($this->productId);

        if (! $product) {
            return;
        }

        $images->attach($product, $this->urls);
    }
}
