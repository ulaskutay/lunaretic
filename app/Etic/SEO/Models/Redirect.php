<?php

namespace App\Etic\SEO\Models;

use App\Etic\Support\Concerns\BelongsToChannel;
use Illuminate\Database\Eloquent\Model;

class Redirect extends Model
{
    use BelongsToChannel;

    protected $table = 'etic_redirects';

    protected $fillable = [
        'from_path',
        'to_url',
        'status_code',
        'is_active',
        'channel_id',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'status_code' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Redirect $redirect): void {
            $redirect->from_path = self::normalizePath($redirect->from_path);
            $redirect->to_url = self::normalizeTarget($redirect->to_url);
            $redirect->status_code = in_array((int) $redirect->status_code, [301, 302], true)
                ? (int) $redirect->status_code
                : 301;
        });
    }

    public static function normalizePath(?string $path): string
    {
        $parsed = parse_url((string) $path, PHP_URL_PATH);
        $value = is_string($parsed) && $parsed !== '' ? $parsed : (string) $path;
        $value = '/'.ltrim($value, '/');

        if ($value !== '/') {
            $value = rtrim($value, '/');
        }

        return $value === '' ? '/' : $value;
    }

    public static function normalizeTarget(?string $url): string
    {
        $url = trim((string) $url);

        if ($url === '') {
            return '/';
        }

        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return $url;
        }

        return self::normalizePath($url);
    }
}
