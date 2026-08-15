<?php

namespace App\Etic\CMS\Filament\Resources\BlogPostResource\Pages;

use App\Etic\CMS\Filament\Resources\BlogPostResource;
use Filament\Resources\Pages\EditRecord;

class EditBlogPost extends EditRecord
{
    protected static string $resource = BlogPostResource::class;
}
