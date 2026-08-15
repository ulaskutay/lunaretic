<?php

namespace App\Etic\Storefront;

use Illuminate\Http\Request;

class CatalogFilters
{
    public function __construct(
        public ?string $search = null,
        public string $sort = 'newest',
        public ?int $color = null,
        public ?int $size = null,
        public ?int $brand = null,
        public ?int $minPrice = null,
        public ?int $maxPrice = null,
        public bool $inStock = false,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            search: $request->string('q')->toString() ?: null,
            sort: in_array($request->string('sort')->toString(), ['newest', 'name', 'price_asc', 'price_desc'], true)
                ? $request->string('sort')->toString()
                : 'newest',
            color: $request->integer('renk') ?: null,
            size: $request->integer('beden') ?: null,
            brand: $request->integer('marka') ?: null,
            minPrice: self::toMinor($request->input('min')),
            maxPrice: self::toMinor($request->input('max')),
            inStock: $request->boolean('stok'),
        );
    }

    public function toQuery(): array
    {
        return array_filter([
            'q' => $this->search,
            'sort' => $this->sort !== 'newest' ? $this->sort : null,
            'renk' => $this->color,
            'beden' => $this->size,
            'marka' => $this->brand,
            'min' => $this->minPrice !== null ? $this->minPrice / 100 : null,
            'max' => $this->maxPrice !== null ? $this->maxPrice / 100 : null,
            'stok' => $this->inStock ? 1 : null,
        ], fn (mixed $value) => $value !== null && $value !== '');
    }

    private static function toMinor(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) round(((float) $value) * 100);
    }
}
