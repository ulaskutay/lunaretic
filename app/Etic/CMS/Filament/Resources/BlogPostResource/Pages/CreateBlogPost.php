<?php

namespace App\Etic\CMS\Filament\Resources\BlogPostResource\Pages;

use App\Etic\CMS\Filament\Resources\BlogPostResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBlogPost extends CreateRecord
{
    protected static string $resource = BlogPostResource::class;
}
