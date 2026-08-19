<?php

namespace App\Etic\Support;

use App\Etic\Store\Models\Store;

class DnsResolver
{
    /**
     * @return list<string>
     */
    public function cname(string $host): array
    {
        return $this->records($host, DNS_CNAME, 'target');
    }

    /**
     * @return list<string>
     */
    public function txt(string $host): array
    {
        $records = @dns_get_record($host, DNS_TXT) ?: [];

        return collect($records)
            ->flatMap(function (array $record): array {
                if (isset($record['txt'])) {
                    return [(string) $record['txt']];
                }

                if (isset($record['entries']) && is_array($record['entries'])) {
                    return array_map('strval', $record['entries']);
                }

                return [];
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @return list<string>
     */
    private function records(string $host, int $type, string $key): array
    {
        $records = @dns_get_record($host, $type) ?: [];

        return collect($records)
            ->map(fn (array $record) => Store::normalizeHost((string) ($record[$key] ?? '')))
            ->filter()
            ->values()
            ->all();
    }
}
