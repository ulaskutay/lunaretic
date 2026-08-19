<?php

namespace App\Etic\Store\Models;

use App\Etic\Support\StoreContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomDomain extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_VERIFYING = 'verifying';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_FAILED = 'failed';

    protected $table = 'etic_custom_domains';

    protected $fillable = [
        'store_id',
        'hostname',
        'status',
        'verification_token',
        'verified_at',
        'ssl_status',
    ];

    protected function casts(): array
    {
        return [
            'verified_at' => 'datetime',
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function txtRecord(): string
    {
        return 'etic-verify='.$this->verification_token;
    }

    public function txtLookupHost(): string
    {
        return '_etic-verify.'.$this->hostname;
    }

    public function dnsName(): string
    {
        $parts = explode('.', $this->hostname);

        if (count($parts) <= 2) {
            return '@';
        }

        return (string) $parts[0];
    }

    public function usesApex(): bool
    {
        return $this->dnsName() === '@';
    }

    /**
     * @return list<array{type: string, name: string, value: string}>
     */
    public function dnsRecords(string $cnameTarget): array
    {
        return [
            [
                'type' => 'CNAME',
                'name' => $this->dnsName(),
                'value' => $cnameTarget,
            ],
            [
                'type' => 'TXT',
                'name' => '_etic-verify',
                'value' => $this->txtRecord(),
            ],
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $domain): void {
            $domain->hostname = Store::normalizeHost($domain->hostname);
        });

        static::addGlobalScope('store', function ($query): void {
            $context = app(StoreContext::class);

            if ($context->isolationBypassed()) {
                return;
            }

            $storeId = $context->store()?->id;

            if ($storeId) {
                $query->where('store_id', $storeId);
            }
        });
    }
}
