<?php

namespace App\Etic\Media;

use Illuminate\Support\Facades\Log;
use Lunar\Models\Product;
use Throwable;

class RemoteProductImages
{
    /**
     * @param  list<string>  $urls
     */
    public function attach(Product $product, array $urls): int
    {
        $collection = (string) config('lunar.media.collection', 'images');
        $product->unsetRelation('media');

        if ($urls === [] || $product->getMedia($collection)->isNotEmpty()) {
            return 0;
        }

        $skipped = 0;

        foreach (array_values($urls) as $index => $url) {
            $url = $this->normalize($url);

            if ($url === null) {
                $skipped++;

                continue;
            }

            try {
                $extension = strtolower((string) pathinfo((string) parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));
                $extension = in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif', 'avif'], true) ? $extension : 'jpg';

                $product->addMediaFromUrl($url)
                    ->usingFileName('gorsel-'.($index + 1).'.'.$extension)
                    ->withCustomProperties([
                        'name' => 'Görsel '.($index + 1),
                        'primary' => $index === 0,
                    ])
                    ->toMediaCollection($collection);
            } catch (Throwable $exception) {
                Log::warning('Toplu ürün görseli indirilemedi.', [
                    'product_id' => $product->id,
                    'url' => $url,
                    'message' => $exception->getMessage(),
                ]);
                $skipped++;
            }
        }

        return $skipped;
    }

    public function normalize(string $url): ?string
    {
        $url = trim($url, " \t\n\r\0\x0B\"'");

        if (preg_match('/https?:\/\/[^\s"\'<>]+/i', $url, $match) !== 1) {
            return null;
        }

        $url = rtrim($match[0], '.,);');

        return filter_var($url, FILTER_VALIDATE_URL) ?: null;
    }
}
