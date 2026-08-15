<?php

namespace App\Etic\Media;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Lunar\Admin\Support\Extending\RelationManagerExtension;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaRelationManagerExtension extends RelationManagerExtension
{
    public function extendForm(Schema $schema): Schema
    {
        foreach ($schema->getComponents(withHidden: true) as $component) {
            if ($component instanceof TextInput && $component->getName() === 'custom_properties.name') {
                $component->hidden();

                continue;
            }

            if ($component instanceof Toggle && $component->getName() === 'custom_properties.primary') {
                $component
                    ->label(__('etic.filament.media.primary'))
                    ->helperText(__('etic.filament.media.primary_help'))
                    ->default(false);

                continue;
            }

            if (! $component instanceof FileUpload) {
                continue;
            }

            $maxUploadKb = max(1024, (int) config('etic.media.max_upload_kb', 51200));

            $component
                ->label(__('etic.filament.media.files'))
                ->image()
                ->multiple()
                ->reorderable()
                ->appendFiles()
                ->panelLayout('grid')
                ->imagePreviewHeight('140')
                ->maxFiles(24)
                ->maxParallelUploads(3)
                ->maxSize($maxUploadKb)
                ->acceptedFileTypes([
                    'image/jpeg',
                    'image/jpg',
                    'image/pjpeg',
                    'image/png',
                    'image/x-png',
                    'image/webp',
                    'image/gif',
                    'image/avif',
                ])
                ->mimeTypeMap([
                    'jpg' => 'image/jpeg',
                    'jpeg' => 'image/jpeg',
                    'png' => 'image/png',
                    'webp' => 'image/webp',
                    'gif' => 'image/gif',
                    'avif' => 'image/avif',
                ])
                ->imageEditor()
                ->imageEditorEmptyFillColor('transparent')
                ->openable()
                ->helperText(__('etic.filament.media.helper', [
                    'max_mb' => number_format($maxUploadKb / 1024, 0),
                ]));
        }

        return $schema;
    }

    public function extendTable(Table $table): Table
    {
        $name = $table->getColumn('custom_properties.name');

        if ($name instanceof TextColumn) {
            $name->hidden();
        }

        $image = $table->getColumn('image');

        if ($image instanceof ImageColumn) {
            $image->state(function (Media $record): string {
                if ($record->hasGeneratedConversion('small')) {
                    return $record->getUrl('small');
                }

                return $record->getUrl();
            });
        }

        $manager = $this->caller;

        foreach ($table->getHeaderActions() as $action) {
            if ($action->getName() !== 'create' || ! is_object($manager) || ! method_exists($manager, 'getOwnerRecord')) {
                continue;
            }

            $action
                ->label(__('etic.filament.media.add'))
                ->modalHeading(__('etic.filament.media.add'))
                ->modalSubmitActionLabel(__('etic.filament.media.upload'))
                ->modalWidth(Width::FourExtraLarge)
                ->using(function (array $data) use ($manager): Media {
                    $files = $data['media'] ?? [];

                    if (! is_array($files)) {
                        $files = [$files];
                    }

                    return app(MediaLibraryUploader::class)->addMany(
                        $manager->getOwnerRecord(),
                        $files,
                        $manager->mediaCollection ?? config('lunar.media.collection', 'images'),
                        (bool) ($data['custom_properties']['primary'] ?? false),
                    );
                });
        }

        return $table;
    }
}
