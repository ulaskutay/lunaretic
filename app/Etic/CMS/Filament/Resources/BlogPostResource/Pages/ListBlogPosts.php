<?php

namespace App\Etic\CMS\Filament\Resources\BlogPostResource\Pages;

use App\Etic\CMS\Filament\Resources\BlogPostResource;
use Filament\Resources\Pages\ListRecords;

class ListBlogPosts extends ListRecords
{
    protected static string $resource = BlogPostResource::class;
}
