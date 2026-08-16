<?php

namespace App\Etic\Media\Jobs;

use App\Etic\Media\MediaLibraryUploader;
use App\Etic\Support\StaffNotifier;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Lunar\Admin\Events\ModelMediaUpdated;
use Spatie\MediaLibrary\HasMedia;
use Throwable;

class AttachUploadedMediaJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 300;

    public int $tries = 2;

    /**
     * @param  list<array{path: string, name: string}>  $files
     */
    public function __construct(
        public string $ownerType,
        public int $ownerId,
        public array $files,
        public string $collection,
        public bool $markFirstAsPrimary,
        public ?int $staffId,
    ) {}

    public function handle(MediaLibraryUploader $uploader, StaffNotifier $notifier): void
    {
        try {
            $owner = $this->owner();

            if (! $owner instanceof HasMedia) {
                return;
            }

            $files = $this->absoluteFiles();

            if ($files === []) {
                throw new \RuntimeException(__('etic.filament.media.failed_body'));
            }

            $uploader->addMany(
                $owner,
                $files,
                $this->collection,
                $this->markFirstAsPrimary,
            );

            ModelMediaUpdated::dispatch($owner);

            $notifier->send(
                $this->staffId,
                __('etic.filament.media.done'),
                __('etic.filament.media.done_body', ['count' => count($this->files)]),
            );
        } finally {
            $this->forgetPending();
        }
    }

    public function failed(?Throwable $exception): void
    {
        app(StaffNotifier::class)->send(
            $this->staffId,
            __('etic.filament.media.failed'),
            $exception?->getMessage() ?: __('etic.filament.media.failed_body'),
            success: false,
        );

        $this->forgetPending();
    }

    private function owner(): ?Model
    {
        if (! class_exists($this->ownerType)) {
            return null;
        }

        return $this->ownerType::query()->find($this->ownerId);
    }

    /**
     * @return list<array{path: string, name: string}>
     */
    private function absoluteFiles(): array
    {
        return array_values(array_filter(array_map(function (array $file): ?array {
            $absolute = Storage::disk('local')->path($file['path']);

            if (! is_file($absolute)) {
                return null;
            }

            return [
                'path' => $absolute,
                'name' => $file['name'],
            ];
        }, $this->files)));
    }

    private function forgetPending(): void
    {
        foreach ($this->files as $file) {
            Storage::disk('local')->delete($file['path']);
        }
    }
}
