<?php

namespace App\Etic\Catalog\Filament;

use Filament\Actions\CreateAction;
use Illuminate\Database\Eloquent\Model;
use Lunar\Admin\Filament\Resources\ProductResource\Pages\ListProducts;
use Lunar\Admin\Support\Extending\ListPageExtension;

class ListProductsExtension extends ListPageExtension
{
    public function headerActions(array $actions): array
    {
        foreach ($actions as $action) {
            if (! $action instanceof CreateAction || $action->getName() !== 'create') {
                continue;
            }

            $action->using(fn (array $data, string $model): Model => $this->createPublished($data, $model));
        }

        return $actions;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createPublished(array $data, string $model): Model
    {
        $product = ListProducts::createRecord($data, $model);
        $product->forceFill(['status' => 'published'])->save();

        return $product->refresh();
    }
}
