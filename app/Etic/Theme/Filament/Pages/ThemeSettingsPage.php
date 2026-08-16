<?php

namespace App\Etic\Theme\Filament\Pages;

use App\Etic\Store\Models\Store;
use App\Etic\Support\StoreContext;
use App\Etic\Theme\ThemeManifest;
use App\Etic\Theme\ThemeRegistry;
use App\Etic\Theme\ThemeSettings;
use Filament\Actions\Action;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Lunar\Models\Product;

/**
 * @property-read Schema $form
 */
class ThemeSettingsPage extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedSwatch;

    protected static ?int $navigationSort = 7;

    protected static ?string $slug = 'tema-ayarlari';

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public static function getNavigationGroup(): ?string
    {
        return __('lunarpanel::global.sections.settings');
    }

    public static function getNavigationLabel(): string
    {
        return __('etic.filament.theme.plural');
    }

    public function getTitle(): string
    {
        return __('etic.filament.theme.plural');
    }

    public function mount(): void
    {
        $this->fillFromStore(app(StoreContext::class)->handle());
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('store_handle')
                ->label(__('etic.filament.stores.label'))
                ->options(fn () => Store::query()->orderBy('name')->pluck('name', 'handle'))
                ->visible(fn () => Store::query()->count() > 1)
                ->live()
                ->afterStateUpdated(fn (?string $state) => $this->fillFromStore($state))
                ->dehydrated(false),
            Hidden::make('theme'),
            View::make('filament.themes.picker')
                ->viewData(fn (): array => [
                    'active' => $this->selectedThemeHandle(),
                    'storefrontUrl' => app(StoreContext::class)->primaryUrl() ?: url('/'),
                    'themes' => app(ThemeRegistry::class)->all()
                        ->map(fn (ThemeManifest $theme): array => [
                            ...$theme->toPickerArray(),
                            'preview_url' => $this->themePreviewUrl($theme->handle),
                        ])
                        ->values()
                        ->all(),
                ]),
            Section::make(fn (): string => __('etic.filament.theme.customize', [
                'theme' => app(ThemeRegistry::class)->getOrDefault($this->selectedThemeHandle())->title(),
            ]))
                ->description(__('etic.filament.theme.customize_help'))
                ->extraAttributes(['id' => 'etic-theme-customize', 'class' => 'etic-theme-customize'])
                ->schema([
                    Tabs::make('theme-settings')
                        ->contained(false)
                        ->persistTabInQueryString('sekme')
                        ->tabs(fn (): array => $this->settingTabs()),
                ])
                ->footer([
                    Actions::make([
                        Action::make('save')
                            ->label(__('etic.filament.theme.save'))
                            ->submit('save')
                            ->keyBindings(['mod+s']),
                    ]),
                ]),
        ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save'),
        ]);
    }

    public function save(): void
    {
        $this->bindSelectedStore($this->data['store_handle'] ?? null);
        $this->persistTheme((string) ($this->data['theme'] ?? ''));
        $state = $this->form->getState();
        app(ThemeSettings::class)->save($state);

        Notification::make()
            ->title(__('etic.filament.theme.saved'))
            ->success()
            ->send();
    }

    public function publishTheme(string $handle): void
    {
        if (! app(ThemeRegistry::class)->get($handle) || $handle === $this->selectedThemeHandle()) {
            return;
        }

        $this->bindSelectedStore($this->data['store_handle'] ?? null);
        $this->persistTheme($handle);
        $this->fillFromStore($this->data['store_handle'] ?? null);

        Notification::make()
            ->title(__('etic.filament.theme.changed', [
                'name' => app(ThemeRegistry::class)->getOrDefault($handle)->title(),
            ]))
            ->success()
            ->send();
    }

    public function resetTheme(?string $handle = null): void
    {
        $this->bindSelectedStore($this->data['store_handle'] ?? null);
        $handle = filled($handle) ? $handle : $this->selectedThemeHandle();

        if (! app(ThemeRegistry::class)->get($handle)) {
            return;
        }

        app(ThemeSettings::class)->clear($handle);

        if ($handle === $this->selectedThemeHandle()) {
            $this->fillFromStore($this->data['store_handle'] ?? null);
        }

        Notification::make()
            ->title(__('etic.filament.theme.reset_done'))
            ->success()
            ->send();
    }

    /**
     * @return list<Tab>
     */
    private function settingTabs(): array
    {
        $categories = [
            'brand' => [
                'label' => __('etic.filament.theme.tabs.brand'),
                'icon' => Heroicon::OutlinedBuildingStorefront,
                'columns' => 1,
                'sections' => [],
            ],
            'home' => [
                'label' => __('etic.filament.theme.tabs.home'),
                'icon' => Heroicon::OutlinedHome,
                'columns' => ['default' => 1, 'xl' => 2],
                'sections' => [],
            ],
            'appearance' => [
                'label' => __('etic.filament.theme.tabs.appearance'),
                'icon' => Heroicon::OutlinedSwatch,
                'columns' => 1,
                'sections' => [],
            ],
            'footer' => [
                'label' => __('etic.filament.theme.tabs.footer'),
                'icon' => Heroicon::OutlinedBars3BottomLeft,
                'columns' => 1,
                'sections' => [],
            ],
        ];

        foreach (app(ThemeRegistry::class)->getOrDefault($this->selectedThemeHandle())->settingGroups() as $group) {
            $label = (string) ($group['label'] ?? 'Tema');
            $fields = [];
            $hasWideField = false;
            $enabledKey = is_string($group['enabled_key'] ?? null) ? (string) $group['enabled_key'] : null;

            if ($enabledKey) {
                $fields[] = $this->sectionToggle($enabledKey);
            }

            foreach ($group['fields'] ?? [] as $field) {
                if (! is_array($field) || ! isset($field['key'])) {
                    continue;
                }

                $type = (string) ($field['type'] ?? 'text');

                if (in_array($type, ['image', 'textarea'], true)) {
                    $hasWideField = true;
                }

                $component = $this->fieldComponent($field);

                if ($component) {
                    $fields[] = $component;
                }
            }

            if ($fields === []) {
                continue;
            }

            $category = $this->settingCategory($label);
            $section = Section::make($label)
                ->schema($fields)
                ->columns($hasWideField ? 1 : 2)
                ->compact();

            if ($category === 'home') {
                $section
                    ->icon($this->homeSectionIcon($label))
                    ->extraAttributes(['class' => 'etic-theme-home-card'])
                    ->collapsible()
                    ->collapsed();
            }

            $categories[$category]['sections'][] = $section;
        }

        return collect($categories)
            ->filter(fn (array $category): bool => $category['sections'] !== [])
            ->map(fn (array $category): Tab => Tab::make($category['label'])
                ->icon($category['icon'])
                ->columns($category['columns'])
                ->schema($category['sections']))
            ->values()
            ->all();
    }

    private function homeSectionIcon(string $label): Heroicon
    {
        $normalized = mb_strtolower($label);

        return match (true) {
            str_contains($normalized, 'kahraman'), str_contains($normalized, 'hero') => Heroicon::OutlinedPhoto,
            str_contains($normalized, 'öne çıkan') => Heroicon::OutlinedStar,
            str_contains($normalized, 'editoryal') => Heroicon::OutlinedNewspaper,
            str_contains($normalized, 'çok satan') => Heroicon::OutlinedChartBar,
            str_contains($normalized, 'banner') => Heroicon::OutlinedRectangleGroup,
            str_contains($normalized, 'look') => Heroicon::OutlinedEye,
            str_contains($normalized, 'sayım') => Heroicon::OutlinedClock,
            default => Heroicon::OutlinedSquares2X2,
        };
    }

    private function settingCategory(string $label): string
    {
        $normalized = mb_strtolower($label);

        if (str_contains($normalized, 'marka')) {
            return 'brand';
        }

        if (str_contains($normalized, 'renk') || str_contains($normalized, 'tipografi')) {
            return 'appearance';
        }

        if (str_contains($normalized, 'alt bilgi') || str_contains($normalized, 'footer')) {
            return 'footer';
        }

        return 'home';
    }

    /**
     * @param  array<string, mixed>  $field
     */
    private function fieldComponent(array $field): mixed
    {
        $key = (string) $field['key'];
        $label = (string) ($field['label'] ?? $key);
        $help = $field['help'] ?? null;
        $type = (string) ($field['type'] ?? 'text');
        $options = $field['options'] ?? [];
        $placeholder = $field['placeholder'] ?? null;

        $component = match (true) {
            $type === 'toggle' => Toggle::make($key)->onColor('success')->live()->columnSpanFull(),
            $type === 'color' => ColorPicker::make($key)->hex()->live(onBlur: true),
            $type === 'textarea' => Textarea::make($key)->rows(3)->live(onBlur: true)->columnSpanFull(),
            $type === 'url' => TextInput::make($key)->url()->live(onBlur: true),
            $type === 'datetime' => DateTimePicker::make($key)->seconds(false)->native(false)->live(),
            $type === 'number' => TextInput::make($key)
                ->numeric()
                ->minValue((int) ($field['min'] ?? 0))
                ->maxValue((int) ($field['max'] ?? 100))
                ->live(onBlur: true),
            $type === 'product' => Select::make($key)
                ->options(fn (): array => $this->productOptions())
                ->searchable()
                ->preload()
                ->native(false)
                ->live(),
            $type === 'image' => $this->imageUploadComponent($key),
            $type === 'select' && in_array($key, ['radius', 'container', 'header_style'], true) => ToggleButtons::make($key)
                ->options($options)
                ->inline()
                ->grouped()
                ->live()
                ->columnSpanFull(),
            $type === 'select' => Select::make($key)->options($options)->native(false)->live(),
            default => TextInput::make($key)->maxLength(191)->live(onBlur: true),
        };

        $component->label($label);

        if (filled($help)) {
            $component->helperText((string) $help);
        }

        if (filled($placeholder) && method_exists($component, 'placeholder')) {
            $component->placeholder((string) $placeholder);
        }

        return $component;
    }

    private function sectionToggle(string $key): Toggle
    {
        return Toggle::make($key)
            ->label(__('etic.filament.theme.show_section'))
            ->helperText(__('etic.filament.theme.show_section_help'))
            ->onColor('success')
            ->live()
            ->columnSpanFull();
    }

    private function imageUploadComponent(string $key): FileUpload
    {
        $component = FileUpload::make($key)
            ->image()
            ->disk('public')
            ->directory('theme')
            ->visibility('public')
            ->previewable()
            ->maxParallelUploads(1)
            ->maxSize(10240)
            ->placeholder(__('etic.filament.theme.dropzone'))
            ->getUploadedFileUsing(function (FileUpload $component, string $file, string|array|null $storedFileNames): ?array {
                $uploadedFile = $component->getUploadedFile($file, $storedFileNames);

                if ($uploadedFile === null) {
                    return null;
                }

                $uploadedFile['url'] = request()->getSchemeAndHttpHost().'/storage/'.ltrim($file, '/');

                return $uploadedFile;
            })
            ->columnSpanFull();

        if ($key === 'logo') {
            return $component
                ->panelLayout('integrated')
                ->panelAspectRatio('3:1')
                ->imagePreviewHeight('220')
                ->loadingIndicatorPosition('left')
                ->uploadProgressIndicatorPosition('left')
                ->removeUploadedFileButtonPosition('right');
        }

        return $component
            ->panelLayout('compact')
            ->imagePreviewHeight('120');
    }

    /**
     * @return array<int, string>
     */
    private function productOptions(): array
    {
        return Product::query()
            ->channel(app(StoreContext::class)->channel())
            ->where('status', 'published')
            ->orderByDesc('id')
            ->get()
            ->mapWithKeys(fn (Product $product): array => [
                $product->id => (string) ($product->translateAttribute('name') ?: '#'.$product->id),
            ])
            ->all();
    }

    private function themePreviewUrl(string $handle): string
    {
        return rtrim(url('/'), '/')
            .'/?'.http_build_query(['theme_preview' => $handle]);
    }

    private function fillFromStore(?string $handle): void
    {
        $handle ??= app(StoreContext::class)->handle();
        $this->bindSelectedStore($handle);

        $this->form->fill([
            'store_handle' => $handle,
            'theme' => app(StoreContext::class)->theme(),
            ...app(ThemeSettings::class)->resolved(),
        ]);
    }

    private function persistTheme(string $theme): void
    {
        if ($theme === '' || ! app(ThemeRegistry::class)->get($theme)) {
            return;
        }

        $store = app(StoreContext::class)->store();

        if (! $store || $store->theme === $theme) {
            return;
        }

        $store->forceFill(['theme' => $theme])->save();
        app(StoreContext::class)->bind($store->fresh());
    }

    private function selectedThemeHandle(): string
    {
        $fromForm = $this->data['theme'] ?? null;

        if (is_string($fromForm) && app(ThemeRegistry::class)->get($fromForm)) {
            return $fromForm;
        }

        return app(StoreContext::class)->theme();
    }

    private function bindSelectedStore(?string $handle): void
    {
        if (filled($handle)) {
            app(StoreContext::class)->bindByHandle($handle);
        }
    }
}
