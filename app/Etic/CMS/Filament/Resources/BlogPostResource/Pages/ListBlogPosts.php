<?php

namespace App\Etic\CMS\Filament\Resources\BlogPostResource\Pages;

use App\Etic\CMS\Filament\Resources\BlogPostResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBlogPosts extends ListRecords
{
    protected static string $resource = BlogPostResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
