<?php

namespace App\Etic\Integrations\Marketing;

use App\Etic\Media\ProductImage;
use App\Etic\Storefront\StorefrontPaths;
use App\Etic\Support\StoreContext;
use Lunar\Models\Product;
use Lunar\Models\ProductVariant;

class GoogleMerchantFeed
{
    public function __construct(private StoreContext $store) {}

    public function xml(): string
    {
        $base = $this->store->primaryUrl();
        $currency = $this->store->currency()->code;

        $items = '';

        Product::query()
            ->channel($this->store->channel())
            ->where('status', 'published')
            ->with(['variants.prices', 'variants.values', 'brand', 'defaultUrl', 'media', 'thumbnail'])
            ->each(function (Product $product) use (&$items, $base, $currency) {
                $slug = $product->defaultUrl?->slug;
                $link = $slug ? $base.StorefrontPaths::product($slug) : $base;
                $title = $product->translateAttribute('name') ?: 'Ürün';
                $description = trim(html_entity_decode(strip_tags((string) $product->translateAttribute('description')))) ?: $title;
                $image = ProductImage::url($product, 'large');
                $brand = $product->brand?->name ?: $this->store->name();

                foreach ($product->variants as $variant) {
                    $items .= $this->item($variant, $title, $description, $link, $image, $brand, $currency, $product->id);
                }
            });

        $title = e($this->store->name().' ürün akışı');

        return '<?xml version="1.0" encoding="UTF-8"?>'
            .'<rss version="2.0" xmlns:g="http://base.google.com/ns/1.0">'
            .'<channel>'
            .'<title>'.$title.'</title>'
            .'<link>'.e($base).'</link>'
            .'<description>'.$title.'</description>'
            .$items
            .'</channel></rss>';
    }

    private function item(
        ProductVariant $variant,
        string $title,
        string $description,
        string $link,
        ?string $image,
        string $brand,
        string $currency,
        int $groupId,
    ): string {
        $price = $variant->prices->first();
        $amount = number_format(((int) ($price?->price?->value ?? 0)) / 100, 2, '.', '');
        $inStock = $variant->canBeFulfilledAtQuantity(1);
        $option = $variant->values->map(fn ($value) => $value->translate('name'))->filter()->implode(' / ');
        $itemTitle = $option ? $title.' - '.$option : $title;

        $xml = '<item>';
        $xml .= '<g:id>'.e($variant->sku).'</g:id>';
        $xml .= '<g:title>'.e($itemTitle).'</g:title>';
        $xml .= '<g:description>'.e($description).'</g:description>';
        $xml .= '<g:link>'.e($link).'</g:link>';
        if ($image) {
            $xml .= '<g:image_link>'.e($image).'</g:image_link>';
        }
        $xml .= '<g:availability>'.($inStock ? 'in stock' : 'out of stock').'</g:availability>';
        $xml .= '<g:price>'.$amount.' '.e($currency).'</g:price>';
        $xml .= '<g:brand>'.e($brand).'</g:brand>';
        $xml .= '<g:condition>new</g:condition>';
        $xml .= '<g:mpn>'.e($variant->sku).'</g:mpn>';
        $xml .= '<g:identifier_exists>no</g:identifier_exists>';
        $xml .= '<g:item_group_id>'.e((string) $groupId).'</g:item_group_id>';
        $xml .= '</item>';

        return $xml;
    }
}
