<?php

namespace App\Etic\CMS\Filament\Resources\BlogCategoryResource\Pages;

use App\Etic\CMS\Filament\Resources\BlogCategoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBlogCategories extends ListRecords
{
    protected static string $resource = BlogCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
