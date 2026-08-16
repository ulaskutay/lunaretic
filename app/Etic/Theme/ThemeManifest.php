<?php

namespace App\Etic\Theme;

class ThemeManifest
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        public readonly string $handle,
        public readonly string $path,
        public readonly array $data,
    ) {}

    public static function fromDirectory(string $path): ?self
    {
        $file = $path.'/theme.json';

        if (! is_file($file)) {
            return null;
        }

        $decoded = json_decode((string) file_get_contents($file), true);

        if (! is_array($decoded)) {
            return null;
        }

        $handle = basename($path);

        return new self($handle, $path, $decoded);
    }

    public function name(): string
    {
        return (string) ($this->data['name'] ?? $this->handle);
    }

    public function title(): string
    {
        return (string) ($this->data['title'] ?? $this->name());
    }

    public function description(): string
    {
        return (string) ($this->data['description'] ?? '');
    }

    /**
     * @return list<string>
     */
    public function previewPalette(): array
    {
        $defaults = $this->defaults();

        return array_values(array_filter([
            (string) ($defaults['color_background'] ?? ''),
            (string) ($defaults['color_surface'] ?? ''),
            (string) ($defaults['color_text'] ?? ''),
            (string) ($defaults['color_primary'] ?? ''),
            (string) ($defaults['color_accent'] ?? ''),
        ]));
    }

    /**
     * @return array<string, mixed>
     */
    public function toPickerArray(): array
    {
        return [
            'handle' => $this->handle,
            'name' => $this->name(),
            'title' => $this->title(),
            'description' => $this->description(),
            'author' => (string) ($this->data['author'] ?? ''),
            'version' => $this->version(),
            'palette' => $this->previewPalette(),
        ];
    }

    public function version(): string
    {
        return (string) ($this->data['version'] ?? '1.0.0');
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function settingGroups(): array
    {
        $groups = $this->data['settings'] ?? [];

        return is_array($groups) ? array_values($groups) : [];
    }

    /**
     * @return array<string, mixed>
     */
    public function defaults(): array
    {
        $defaults = [];

        foreach ($this->settingGroups() as $group) {
            $enabledKey = $group['enabled_key'] ?? null;

            if (is_string($enabledKey) && $enabledKey !== '') {
                $defaults[$enabledKey] = $group['enabled_default'] ?? true;
            }

            foreach ($group['fields'] ?? [] as $field) {
                if (! is_array($field) || ! isset($field['key'])) {
                    continue;
                }

                $defaults[(string) $field['key']] = $field['default'] ?? null;
            }
        }

        return $defaults;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function field(string $key): ?array
    {
        foreach ($this->settingGroups() as $group) {
            foreach ($group['fields'] ?? [] as $field) {
                if (is_array($field) && ($field['key'] ?? null) === $key) {
                    return $field;
                }
            }
        }

        return null;
    }

    public function cssPath(): ?string
    {
        $relative = $this->data['assets']['css'] ?? 'css/theme.css';
        $full = $this->path.'/'.$relative;

        return is_file($full) ? 'resources/themes/'.$this->handle.'/'.$relative : null;
    }

    public function jsPath(): ?string
    {
        $relative = $this->data['assets']['js'] ?? 'js/theme.js';
        $full = $this->path.'/'.$relative;

        return is_file($full) ? 'resources/themes/'.$this->handle.'/'.$relative : null;
    }
}
