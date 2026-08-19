<?php

namespace App\Etic\Store;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class CloudflareCustomHostnames
{
    public function configured(): bool
    {
        return $this->token() !== '' && $this->zoneId() !== '';
    }

    public function register(string $hostname): void
    {
        if (! $this->configured() || $hostname === '') {
            return;
        }

        if ($this->findId($hostname)) {
            return;
        }

        $response = Http::withToken($this->token())
            ->acceptJson()
            ->timeout(30)
            ->post($this->url('/custom_hostnames'), [
                'hostname' => $hostname,
                'ssl' => [
                    'method' => 'http',
                    'type' => 'dv',
                ],
            ]);

        $payload = $response->json();

        if ($this->alreadyExists($payload)) {
            return;
        }

        if (! $response->successful() || ! ($payload['success'] ?? false)) {
            throw new RuntimeException($this->errorMessage($payload, 'Cloudflare özel alan adı kaydedilemedi.'));
        }
    }

    public function unregister(string $hostname): void
    {
        if (! $this->configured() || $hostname === '') {
            return;
        }

        $id = $this->findId($hostname);

        if (! $id) {
            return;
        }

        $response = Http::withToken($this->token())
            ->acceptJson()
            ->timeout(30)
            ->delete($this->url('/custom_hostnames/'.$id));

        if ($response->status() === 404) {
            return;
        }

        $payload = $response->json();

        if (! $response->successful() || ! ($payload['success'] ?? false)) {
            throw new RuntimeException($this->errorMessage($payload, 'Cloudflare özel alan adı silinemedi.'));
        }
    }

    private function findId(string $hostname): ?string
    {
        $response = Http::withToken($this->token())
            ->acceptJson()
            ->timeout(30)
            ->get($this->url('/custom_hostnames'), [
                'hostname' => $hostname,
            ]);

        $payload = $response->json();

        if (! $response->successful() || ! ($payload['success'] ?? false)) {
            return null;
        }

        $id = $payload['result'][0]['id'] ?? null;

        return is_string($id) && $id !== '' ? $id : null;
    }

    /**
     * @param  array<string, mixed>|null  $payload
     */
    private function alreadyExists(?array $payload): bool
    {
        foreach ($payload['errors'] ?? [] as $error) {
            $code = (int) ($error['code'] ?? 0);
            $message = strtolower((string) ($error['message'] ?? ''));

            if ($code === 1406 || str_contains($message, 'already exists')) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>|null  $payload
     */
    private function errorMessage(?array $payload, string $fallback): string
    {
        $message = $payload['errors'][0]['message'] ?? null;

        return is_string($message) && $message !== '' ? $message : $fallback;
    }

    private function url(string $path): string
    {
        return 'https://api.cloudflare.com/client/v4/zones/'.$this->zoneId().$path;
    }

    private function token(): string
    {
        return (string) config('etic.tenancy.cloudflare.api_token');
    }

    private function zoneId(): string
    {
        return (string) config('etic.tenancy.cloudflare.zone_id');
    }
}
