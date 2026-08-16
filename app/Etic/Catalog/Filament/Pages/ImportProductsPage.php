<?php

namespace App\Etic\Catalog\Filament\Pages;

use App\Etic\Catalog\Jobs\ImportProductsJob;
use App\Etic\Catalog\Spreadsheet\TrendyolWorkbook;
use App\Etic\Support\StoreContext;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

/**
 * @property-read Schema $form
 */
class ImportProductsPage extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowUpTray;

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'urun-yukle';

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public static function getNavigationGroup(): ?string
    {
        return __('lunarpanel::global.sections.catalog');
    }

    public static function getNavigationLabel(): string
    {
        return __('etic.filament.catalog.import.plural');
    }

    public function getTitle(): string
    {
        return __('etic.filament.catalog.import.plural');
    }

    public function mount(): void
    {
        $this->form->fill();
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('etic.filament.catalog.import.section'))
                ->description(__('etic.filament.catalog.import.help'))
                ->schema([
                    FileUpload::make('file')
                        ->label(__('etic.filament.catalog.import.file'))
                        ->acceptedFileTypes([
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'application/vnd.ms-excel',
                        ])
                        ->storeFiles(false)
                        ->required(),
                ]),
        ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('import')
                ->footer([
                    Actions::make([
                        Action::make('template')
                            ->label(__('etic.filament.catalog.import.template'))
                            ->color('gray')
                            ->action('downloadTemplate'),
                        Action::make('import')
                            ->label(__('etic.filament.catalog.import.submit'))
                            ->submit('import')
                            ->keyBindings(['mod+s']),
                    ]),
                ]),
        ]);
    }

    public function import(): void
    {
        $file = $this->form->getState()['file'] ?? null;

        ImportProductsJob::dispatch(
            $this->storeUpload($file),
            auth('staff')->id(),
            app(StoreContext::class)->handle(),
        );

        $this->form->fill();

        Notification::make()
            ->title(__('etic.filament.catalog.import.queued'))
            ->body(__('etic.filament.catalog.import.queued_body'))
            ->success()
            ->send();
    }

    public function downloadTemplate()
    {
        $path = app(TrendyolWorkbook::class)->write([]);

        return response()->download($path, 'etic-urun-sablonu.xlsx')->deleteFileAfterSend();
    }

    private function storeUpload(mixed $file): string
    {
        if (is_array($file)) {
            $file = $file[0] ?? null;
        }

        if ($file instanceof TemporaryUploadedFile) {
            $path = $file->storeAs('imports', Str::uuid()->toString().'.xlsx', 'local');

            if (is_string($path) && $path !== '') {
                return $path;
            }
        }

        throw new \InvalidArgumentException(__('etic.filament.catalog.import.missing_file'));
    }
}
