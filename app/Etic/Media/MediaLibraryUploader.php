<?php

namespace App\Etic\Media;

use App\Etic\Support\StoreContext;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaLibraryUploader
{
    /**
     * @param  list<mixed>  $files
     * @return list<array{path: string, name: string}>
     */
    public function persistUploads(array $files): array
    {
        $stored = [];

        foreach ($files as $file) {
            $pending = $this->persistOne($file);

            if ($pending) {
                $stored[] = $pending;
            }
        }

        if ($stored === []) {
            throw new \InvalidArgumentException('Ürün görseli yüklenemedi.');
        }

        return $stored;
    }

    /**
     * @param  list<mixed>  $files
     */
    public function addMany(HasMedia $owner, array $files, string $collection, bool $markFirstAsPrimary): Media
    {
        $files = array_values(array_filter($files, fn (mixed $file): bool => $file !== null && $file !== ''));

        if ($files === []) {
            throw new \InvalidArgumentException('Ürün görseli yüklenemedi.');
        }

        $isEmpty = $owner->getMedia($collection)->isEmpty();
        $created = null;

        foreach ($files as $index => $file) {
            $created = $this->addOne(
                $owner,
                $file,
                $collection,
                ($isEmpty || $markFirstAsPrimary) && $index === 0,
            );
        }

        return $created;
    }

    public function addOne(HasMedia $owner, mixed $file, string $collection, bool $primary): Media
    {
        $originalName = null;

        if (is_array($file)) {
            $originalName = is_string($file['name'] ?? null) ? $file['name'] : null;
            $file = $file['path'] ?? null;
        }

        if ($file instanceof TemporaryUploadedFile) {
            $adder = $owner->addMedia($file)
                ->usingFileName($file->getClientOriginalName() ?: 'image.png');
            $label = pathinfo((string) $file->getClientOriginalName(), PATHINFO_FILENAME);
        } elseif (is_string($file) && is_file($file)) {
            $adder = $owner->addMedia($file)
                ->usingFileName($originalName ?: basename($file));
            $label = pathinfo($originalName ?: $file, PATHINFO_FILENAME);
        } elseif (is_string($file) && $file !== '') {
            $adder = $owner->addMediaFromDisk($file, config('livewire.temporary_file_upload.disk') ?: 'local');
            $label = pathinfo($originalName ?: $file, PATHINFO_FILENAME);
        } elseif (is_object($file) && method_exists($file, 'get')) {
            $original = method_exists($file, 'getClientOriginalName')
                ? ($file->getClientOriginalName() ?: 'image.png')
                : 'image.png';
            $adder = $owner->addMediaFromString((string) $file->get())
                ->usingFileName($original);
            $label = pathinfo($original, PATHINFO_FILENAME);
        } else {
            throw new \InvalidArgumentException('Ürün görseli yüklenemedi.');
        }

        return $adder
            ->withCustomProperties([
                'name' => $label,
                'primary' => $primary,
                'store_handle' => app(StoreContext::class)->handle() ?: 'platform',
            ])
            ->toMediaCollection($collection);
    }

    /**
     * @return array{path: string, name: string}|null
     */
    private function persistOne(mixed $file): ?array
    {
        if ($file instanceof TemporaryUploadedFile) {
            $name = $file->getClientOriginalName() ?: 'image.jpg';
            $path = $file->storeAs('imports/media', $this->storedName($name), 'local');

            return is_string($path) && $path !== '' ? ['path' => $path, 'name' => $name] : null;
        }

        if (is_string($file) && is_file($file)) {
            $name = basename($file);
            $path = 'imports/media/'.$this->storedName($name);
            Storage::disk('local')->put($path, (string) file_get_contents($file));

            return ['path' => $path, 'name' => $name];
        }

        if (is_string($file) && $file !== '') {
            $disk = (string) (config('livewire.temporary_file_upload.disk') ?: 'local');

            if (! Storage::disk($disk)->exists($file)) {
                return null;
            }

            $name = basename($file);
            $path = 'imports/media/'.$this->storedName($name);
            Storage::disk('local')->put($path, Storage::disk($disk)->get($file));

            return ['path' => $path, 'name' => $name];
        }

        return null;
    }

    private function storedName(string $name): string
    {
        $extension = strtolower((string) pathinfo($name, PATHINFO_EXTENSION)) ?: 'jpg';

        return Str::uuid()->toString().'.'.$extension;
    }
}
