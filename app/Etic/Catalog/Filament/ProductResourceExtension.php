<?php

namespace App\Etic\Catalog\Filament;

use App\Etic\Catalog\Filament\Pages\ManageProductIdentifiers;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\PageRegistration;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Lunar\Admin\Filament\Resources\ProductResource\Pages\ManageProductIdentifiers as LunarManageProductIdentifiers;
use Lunar\Admin\Support\Extending\ResourceExtension;

class ProductResourceExtension extends ResourceExtension
{
    public function extendForm(Schema $schema): Schema
    {
        return $schema->components([
            ...$schema->getComponents(withHidden: true),
            Section::make(__('etic.filament.catalog.model_code.section'))
                ->schema([
                    TextInput::make('model_code')
                        ->label(__('etic.filament.catalog.model_code.label'))
                        ->helperText(__('etic.filament.catalog.model_code.help'))
                        ->maxLength(100),
                ]),
        ]);
    }

    public function extendTable(Table $table): Table
    {
        return $table
            ->deferLoading(false)
            ->modifyQueryUsing(fn (Builder $query) => $query->with([
                'brand',
                'productType',
                'variants',
                'media',
            ]))
            ->pushColumns([
                TextColumn::make('model_code')
                    ->label(__('etic.filament.catalog.model_code.label'))
                    ->searchable()
                    ->toggleable(),
            ])
            ->pushRecordActions([
                DuplicateProductAction::make(),
            ]);
    }

    /**
     * @param  array<string, PageRegistration>  $pages
     * @return array<string, PageRegistration>
     */
    public function extendPages(array $pages): array
    {
        $pages['identifiers'] = ManageProductIdentifiers::route('/{record}/identifiers');

        return $pages;
    }

    /**
     * @param  array<int, class-string>  $pages
     * @return array<int, class-string>
     */
    public function extendSubNavigation(array $pages): array
    {
        return array_map(
            fn (string $page) => $page === LunarManageProductIdentifiers::class
                ? ManageProductIdentifiers::class
                : $page,
            $pages
        );
    }
}
