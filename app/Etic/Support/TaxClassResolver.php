<?php

namespace App\Etic\Support;

use App\Etic\Catalog\Models\TaxClass;
use Lunar\Models\TaxRate;
use Lunar\Models\TaxRateAmount;
use Lunar\Models\TaxZone;
use RuntimeException;

class TaxClassResolver
{
    public function resolve(?string $rate = null): TaxClass
    {
        $percentage = $this->normalizePercentage($rate);

        if ($percentage === null) {
            $percentage = $this->defaultPercentage();
        }

        return $this->forPercentage($percentage);
    }

    public function forPercentage(int $percentage): TaxClass
    {
        $taxZone = $this->defaultZone();
        $taxClass = $this->taxClassFor($percentage);

        $this->ensureAmount($taxZone, $taxClass, $percentage);

        return $taxClass;
    }

    public function defaultPercentage(): int
    {
        return (int) config('etic.tax.default_rate', 10);
    }

    private function normalizePercentage(?string $rate): ?int
    {
        if ($rate === null || trim($rate) === '') {
            return null;
        }

        $value = (int) round((float) str_replace(',', '.', trim($rate)));

        return $value > 0 ? $value : null;
    }

    private function defaultZone(): TaxZone
    {
        return TaxZone::query()->where('default', true)->first()
            ?? throw new RuntimeException('Varsayılan vergi bölgesi bulunamadı.');
    }

    private function taxClassFor(int $percentage): TaxClass
    {
        if ($percentage === $this->defaultPercentage()) {
            return TaxClass::query()->firstOrCreate(
                ['name' => 'KDV', 'store_id' => app(StoreContext::class)->store()?->id],
                ['default' => true],
            );
        }

        return TaxClass::query()->firstOrCreate(
            ['name' => 'KDV %'.$percentage, 'store_id' => app(StoreContext::class)->store()?->id],
            ['default' => false],
        );
    }

    private function ensureAmount(TaxZone $taxZone, TaxClass $taxClass, int $percentage): void
    {
        $taxRate = TaxRate::query()->firstOrCreate(
            ['name' => 'KDV %'.$percentage, 'tax_zone_id' => $taxZone->id],
            ['priority' => 1],
        );

        TaxRateAmount::query()->updateOrCreate(
            [
                'tax_rate_id' => $taxRate->id,
                'tax_class_id' => $taxClass->id,
            ],
            ['percentage' => $percentage],
        );
    }
}
