<?php

namespace App\Etic\Catalog\Filament;

use Lunar\Admin\Support\Extending\EditPageExtension;

class EditProductExtension extends EditPageExtension
{
    public function headerActions(array $actions): array
    {
        return [
            DuplicateProductAction::make(),
            ...$actions,
        ];
    }
}
