<?php

namespace App\Etic\Catalog\Filament\Pages;

use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Lunar\Admin\Filament\Resources\ProductResource\Pages\ManageProductIdentifiers as LunarManageProductIdentifiers;
use Lunar\Admin\Filament\Resources\ProductVariantResource;

class ManageProductIdentifiers extends LunarManageProductIdentifiers
{
    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->schema([
                ProductVariantResource::getSkuFormComponent()->live(),
                ProductVariantResource::getGtinFormComponent(),
                ProductVariantResource::getMpnFormComponent(),
                ProductVariantResource::getEanFormComponent(),
            ])->columns(1),
        ])->statePath('');
    }
}
