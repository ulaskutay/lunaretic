<?php

namespace App\Console\Commands;

use App\Etic\Media\StoreMediaPathGenerator;
use App\Etic\Support\StoreContext;
use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Support\Defer\DeferredCallbackCollection;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\Conversions\FileManipulator;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class RegenerateMediaConversions extends Command
{
    use ConfirmableTrait;

    protected $signature = 'etic:media-regenerate
                            {--store= : Yalniz bu magaza handle}
                            {--ids= : Virgulle media id listesi}
                            {--force : Onay isteme}';

    protected $description = 'Mevcut görsellerin vitrin conversionlarini WebP olarak yeniden üretir.';

    public function handle(FileManipulator $fileManipulator, StoreMediaPathGenerator $paths): int
    {
        if (! $this->confirmToProceed()) {
            return self::SUCCESS;
        }

        $ids = $this->mediaIds();
        $store = trim((string) $this->option('store'));
        $pruned = 0;
        $regenerated = 0;
        $skipped = 0;
        $errors = [];

        app(StoreContext::class)->withoutIsolation(function () use (
            $fileManipulator,
            $paths,
            $ids,
            $store,
            &$pruned,
            &$regenerated,
            &$skipped,
            &$errors,
        ): void {
            $query = Media::query()->orderBy('id');

            if ($ids !== []) {
                $query->whereIn('id', $ids);
            }

            $mediaFiles = $query->cursor();
            $bar = $this->output->createProgressBar(max(1, $query->count()));
            $bar->start();

            foreach ($mediaFiles as $media) {
                if ($store !== '' && $paths->storeHandle($media) !== $store) {
                    $skipped++;
                    $bar->advance();

                    continue;
                }

                try {
                    $fileManipulator->createDerivedFiles($media);
                    app(DeferredCallbackCollection::class)->invoke();
                    $pruned += $this->pruneStaleConversions($media, $paths);
                    $regenerated++;
                } catch (\Throwable $exception) {
                    $errors[$media->getKey()] = $exception->getMessage();
                }

                $bar->advance();
            }

            $bar->finish();
        });

        $this->newLine(2);
        $this->info("Yeniden üretildi: {$regenerated}");

        if ($skipped > 0) {
            $this->comment("Atlandı (başka mağaza): {$skipped}");
        }

        if ($pruned > 0) {
            $this->comment("Eski conversion silindi: {$pruned}");
        }

        foreach ($errors as $mediaId => $message) {
            $this->warn("Media id {$mediaId}: {$message}");
        }

        return $errors === [] ? self::SUCCESS : self::FAILURE;
    }

    /**
     * @return list<int>
     */
    private function mediaIds(): array
    {
        $raw = trim((string) $this->option('ids'));

        if ($raw === '') {
            return [];
        }

        return array_values(array_filter(array_map(
            fn (string $id): int => (int) trim($id),
            explode(',', $raw),
        )));
    }

    private function pruneStaleConversions(Media $media, StoreMediaPathGenerator $paths): int
    {
        $directory = trim($paths->getPathForConversions($media), '/');
        $disk = Storage::disk($media->disk);
        $keep = [];

        foreach ($media->getMediaConversionNames() as $name) {
            $keep[] = basename($media->getPathRelativeToRoot($name));
        }

        if ($keep === [] || ! $disk->exists($directory)) {
            return 0;
        }

        $deleted = 0;

        foreach ($disk->files($directory) as $file) {
            if (in_array(basename($file), $keep, true)) {
                continue;
            }

            $disk->delete($file);
            $deleted++;
        }

        return $deleted;
    }
}
