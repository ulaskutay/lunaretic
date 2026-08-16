<?php

namespace App\Etic\Catalog\Jobs;

use App\Etic\Catalog\Spreadsheet\ProductSpreadsheetImporter;
use App\Etic\Support\StaffNotifier;
use App\Etic\Support\StoreContext;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ImportProductsJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 900;

    public int $tries = 1;

    public function __construct(
        public string $path,
        public ?int $staffId,
        public string $storeHandle,
    ) {}

    public function handle(
        ProductSpreadsheetImporter $importer,
        StoreContext $store,
        StaffNotifier $notifier,
    ): void {
        $store->bindByHandle($this->storeHandle);

        $fullPath = Storage::disk('local')->path($this->path);

        if (! is_file($fullPath)) {
            throw new \RuntimeException(__('etic.filament.catalog.import.missing_file'));
        }

        try {
            $result = $importer->import($fullPath);
        } finally {
            Storage::disk('local')->delete($this->path);
        }

        $errors = $result['errors'];
        $body = __('etic.filament.catalog.import.summary', [
            'products' => $result['created_products'] + $result['updated_products'],
            'created' => $result['created_products'],
            'updated' => $result['updated_variants'],
            'variants' => $result['created_variants'] + $result['updated_variants'],
        ]);

        if ((int) $result['queued_images'] > 0) {
            $body .= ' '.__('etic.filament.catalog.import.images_queued', [
                'count' => $result['queued_images'],
            ]);
        }

        if ($errors !== []) {
            $body .= ' '.implode(' ', array_slice($errors, 0, 5));
        }

        $notifier->send(
            $this->staffId,
            __('etic.filament.catalog.import.done'),
            $body,
            success: $errors === [],
        );
    }

    public function failed(?Throwable $exception): void
    {
        Storage::disk('local')->delete($this->path);

        app(StaffNotifier::class)->send(
            $this->staffId,
            __('etic.filament.catalog.import.failed'),
            $exception?->getMessage() ?: __('etic.filament.catalog.import.failed_body'),
            success: false,
        );
    }
}
