<?php

namespace App\Etic\Theme;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;

class ThemeRegistry
{
    /**
     * @return Collection<string, ThemeManifest>
     */
    public function all(): Collection
    {
        $root = resource_path('themes');

        if (! is_dir($root)) {
            return collect();
        }

        return collect(File::directories($root))
            ->map(fn (string $path) => ThemeManifest::fromDirectory($path))
            ->filter()
            ->keyBy(fn (ThemeManifest $theme) => $theme->handle);
    }

    public function get(string $handle): ?ThemeManifest
    {
        return $this->all()->get($handle);
    }

    public function getOrDefault(string $handle): ThemeManifest
    {
        return $this->get($handle)
            ?? $this->get('default')
            ?? ThemeManifest::fromDirectory(resource_path('themes/default'))
            ?? new ThemeManifest('default', resource_path('themes/default'), ['name' => 'Default']);
    }

    /**
     * @return array<string, string>
     */
    public function options(): array
    {
        return $this->all()
            ->mapWithKeys(fn (ThemeManifest $theme) => [$theme->handle => $theme->title()])
            ->all();
    }
}
