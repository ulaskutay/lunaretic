<?php

namespace App\Etic\Media;

use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaLibraryUploader
{
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
        if ($file instanceof TemporaryUploadedFile) {
            $adder = $owner->addMedia($file)
                ->usingFileName($file->getClientOriginalName() ?: 'image.png');
            $label = pathinfo((string) $file->getClientOriginalName(), PATHINFO_FILENAME);
        } elseif (is_string($file) && is_file($file)) {
            $adder = $owner->addMedia($file)
                ->usingFileName(basename($file));
            $label = pathinfo($file, PATHINFO_FILENAME);
        } elseif (is_string($file) && $file !== '') {
            $adder = $owner->addMediaFromDisk($file, config('livewire.temporary_file_upload.disk') ?: 'local');
            $label = pathinfo($file, PATHINFO_FILENAME);
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
            ])
            ->toMediaCollection($collection);
    }
}
