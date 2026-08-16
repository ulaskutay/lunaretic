<?php

namespace App\Etic\Catalog\Filament;

use App\Etic\Catalog\DuplicateProduct;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Lunar\Admin\Filament\Resources\ProductResource;

class DuplicateProductAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'duplicate';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('etic.filament.catalog.duplicate.label'));
        $this->modalHeading(__('etic.filament.catalog.duplicate.heading'));
        $this->modalDescription(__('etic.filament.catalog.duplicate.description'));
        $this->modalSubmitActionLabel(__('etic.filament.catalog.duplicate.confirm'));
        $this->successNotificationTitle(__('etic.filament.catalog.duplicate.success'));
        $this->color('gray');
        $this->icon(Heroicon::DocumentDuplicate);
        $this->requiresConfirmation();
        $this->hidden(fn (?Model $record): bool => ! $record || $record->trashed());
        $this->action(function (Model $record): void {
            $copy = app(DuplicateProduct::class)->handle($record);

            $this->redirect(ProductResource::getUrl('edit', [
                'record' => $copy,
            ]));
        });
    }
}
