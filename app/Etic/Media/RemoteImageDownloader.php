<?php

namespace App\Etic\Media;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Spatie\MediaLibrary\Downloaders\Downloader;
use Spatie\MediaLibrary\MediaCollections\Exceptions\UnreachableUrl;

class RemoteImageDownloader implements Downloader
{
    public function getTempFile(string $url): string
    {
        $temporaryFile = tempnam(sys_get_temp_dir(), 'etic-image-');

        if ($temporaryFile === false) {
            throw UnreachableUrl::create($url);
        }

        try {
            $response = Http::timeout(45)
                ->connectTimeout(15)
                ->retry(2, 400)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                    'Accept' => '*/*',
                    'Referer' => 'https://www.trendyol.com/',
                ])
                ->get($url);
        } catch (ConnectionException) {
            @unlink($temporaryFile);

            throw UnreachableUrl::create($url);
        }

        if (! $response->successful() || $response->body() === '') {
            @unlink($temporaryFile);

            throw UnreachableUrl::create($url);
        }

        file_put_contents($temporaryFile, $response->body());

        $mime = (string) (mime_content_type($temporaryFile) ?: $response->header('Content-Type'));

        if (! str_starts_with($mime, 'image/')) {
            @unlink($temporaryFile);

            throw UnreachableUrl::create($url);
        }

        $extension = match (true) {
            str_contains($mime, 'png') => 'png',
            str_contains($mime, 'webp') => 'webp',
            str_contains($mime, 'gif') => 'gif',
            str_contains($mime, 'avif') => 'avif',
            default => 'jpg',
        };

        $named = $temporaryFile.'.'.$extension;

        rename($temporaryFile, $named);

        return $named;
    }
}
