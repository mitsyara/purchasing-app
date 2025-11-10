<?php

namespace App\Filament\Clusters\Settings\Pages;

use App\Filament\Clusters\Settings\SettingsCluster;
use App\Filament\BasePage as Page;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Forms\Components\CodeEditor\Enums\Language as CodeLanguage;
use LaravelLang\NativeLocaleNames\LocaleNames;

use Filament\Forms\Components as F;
use Filament\Schemas\Components as S;
use Filament\Actions;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Support\Htmlable;

class Language extends Page implements HasSchemas, HasActions
{
    use \BezhanSalleh\FilamentShield\Traits\HasPageShield;

    use InteractsWithSchemas, InteractsWithActions;

    protected string $view = 'filament.clusters.settings.pages.language';

    protected static ?string $cluster = SettingsCluster::class;
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedLanguage;
    protected static ?int $navigationSort = 30;
    public function getTitle(): string | Htmlable
    {
        $title = static::$title ?? (string) str(class_basename(static::class))
            ->kebab()
            ->replace('-', ' ')
            ->ucwords();
        return __($title);
    }
    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return __('General Settings');
    }

    /***************************************************************************************/
    public ?array $data = [];
    public ?array $available_langs = [];
    public ?string $selected_lang = null;

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                S\Flex::make([
                    F\Select::make('language')
                        ->label('Select a Language')
                        ->inlineLabel()
                        ->options(function () {
                            $locale = app()->getLocale();
                            $all_langs = $this->getLangFiles();
                            $collator = new \Collator($locale);
                            uasort($all_langs, fn($a, $b) => $collator->compare($a, $b));
                            return $all_langs;
                        })
                        ->afterStateUpdated(function ($state, $set) {
                            $this->selected_lang = $state;
                            $translation = $this->loadTranslation($this->selected_lang);
                            $set('translation', $translation);
                        })
                        ->live()
                        ->required(),

                    Actions\Action::make('delete')
                        ->color('danger')
                        ->icon(Heroicon::OutlinedTrash)
                        ->requiresConfirmation()
                        ->disabled(fn($get) => !$get('language'))
                        ->action(fn($get) => $this->deleteLanguage($get('language'))),
                ])
                    ->columns(),

                S\Group::make([
                    F\CodeEditor::make('translation')
                        ->language(CodeLanguage::Json),

                    S\Flex::make([
                        Actions\Action::make('save')
                            ->color('info')
                            ->action(fn() => $this->saveTranslation())
                            ->keyBindings(['command+shift+s', 'ctrl+shift+s']),

                        Actions\Action::make('cancel')
                            ->color('gray'),
                    ]),
                ])
                    ->hiddenJs(<<<'JS'
                        $get('language') == '' 
                        || $get('language') == null
                        || $get('language') == 0
                        JS)
                    ->columnSpanFull(),

            ])
            ->statePath('data');
    }

    public function create(): Actions\Action
    {
        return Actions\Action::make('create')
            ->label('Add Language')
            ->color('primary')
            ->modal()
            ->modalWidth(\Filament\Support\Enums\Width::Medium)
            ->schema([
                F\Select::make('lang_code')
                    ->label(__("Select a Language"))
                    ->options(function () {
                        $locale = app()->getLocale();
                        $all_langs = LocaleNames::get($locale);
                        $translated_langs = $this->getLangFiles();
                        $all_langs = array_diff_key($all_langs, $translated_langs);
                        $collator = new \Collator($locale);
                        uasort($all_langs, fn($a, $b) => $collator->compare($a, $b));
                        return $all_langs;
                    })
                    ->required(),
            ])
            ->action(fn($data) => $this->addLanguage($data['lang_code']));
    }
    // Add new language
    public function addLanguage(?string $lang_code): void
    {
        if (!$lang_code) {
            Notification::make('error')
                ->danger()
                ->title(__('Language code is Invalid!'))
                ->send();
        }
        $file_path = base_path('lang') . '/' . $lang_code . '.json';
        // Ensure language file doesn't exists (lang folder)
        if (!file_exists($file_path)) {
            // Check if language code is valid
            \Illuminate\Support\Facades\Artisan::call('lang:add ' . $lang_code);
            Notification::make('added')
                ->success()
                ->title(__('New Language file Created!'))
                ->send();
            $this->js('window.location.reload()');
            return;
        }

        Notification::make('error')
            ->danger()
            ->title(__('Language already exists!'))
            ->send();
    }

    // Save translation
    // public function saveTranslation(): void
    // {
    //     $file_name = $this->selected_lang;
    //     $translation = $this->form->getState()['translation'];

    //     if (!filled($translation)) {
    //         Notification::make('empty_warning')
    //             ->warning()
    //             ->title(__('Nothing to Save!'))
    //             ->send();
    //         return;
    //     }

    //     $file_path = base_path('lang') . '//' . $file_name . '.json';
    //     $file_backup = base_path('lang') . '//' . $file_name . '.bak';

    //     // make a backup file
    //     if (copy($file_path, $file_backup)) {
    //         // save content to file
    //         if (!file_put_contents($file_path, $translation)) {
    //             unlink($file_path);
    //             copy($file_backup, $file_path);
    //             Notification::make('error_write_file')
    //                 ->danger()
    //                 ->title(__('File\'s Save Error!'))
    //                 ->send();
    //             return;
    //         };
    //         Notification::make('saved')
    //             ->success()
    //             ->title(__('Translation Saved!'))
    //             ->send();
    //         $this->js('window.location.reload()');
    //         return;
    //     }

    //     Notification::make('error_backup')
    //         ->danger()
    //         ->title(__('File Backup Error!'))
    //         ->send();
    // }

    public function saveTranslation(): void
    {
        $file_name = $this->selected_lang;
        $translation = $this->form->getState()['translation'];

        if (!filled($translation)) {
            Notification::make('empty_warning')
                ->warning()
                ->title(__('Nothing to Save!'))
                ->send();
            return;
        }

        // format JSON đẹp đẽ
        $decoded = json_decode($translation, true); // decode ra mảng associative
        if (json_last_error() !== JSON_ERROR_NONE) {
            Notification::make('error_json')
                ->danger()
                ->title(__('Invalid JSON!'))
                ->send();
            return;
        }
        $formatted = json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        $file_path = base_path('lang') . '//' . $file_name . '.json';
        $file_backup = base_path('lang') . '//' . $file_name . '.bak';

        // make a backup file
        if (copy($file_path, $file_backup)) {
            // save content to file
            if (!file_put_contents($file_path, $formatted)) {
                unlink($file_path);
                copy($file_backup, $file_path);
                Notification::make('error_write_file')
                    ->danger()
                    ->title(__('File\'s Save Error!'))
                    ->send();
                return;
            };
            Notification::make('saved')
                ->success()
                ->title(__('Translation Saved!'))
                ->send();
            $this->js('window.location.reload()');
            return;
        }

        Notification::make('error_backup')
            ->danger()
            ->title(__('File Backup Error!'))
            ->send();
    }


    // Delete Language file
    public function deleteLanguage(?string $lang_code): void
    {
        $file_path = base_path('lang') . '/' . $lang_code . '.json';
        $folder_path = base_path('lang') . '/' . $lang_code;
        // Ensure language file exists (lang folder)
        if (file_exists($file_path)) {
            // Perform deletion
            \Illuminate\Support\Facades\File::delete($file_path);
            \Illuminate\Support\Facades\File::deleteDirectory($folder_path);
            Notification::make('deleted_language')
                ->success()
                ->title(__('Language Deleted Successfully!'))
                ->body(__('Language deleted:') . ' ' . $lang_code)
                ->send();
            $this->js('window.location.reload()');
            return;
        }
        Notification::make('lang_not_found')
            ->danger()
            ->title(__('Language not Found!'))
            ->send();
    }

    /**
     * JSON helper methods
     */
    // Get JSON file list
    public function getLangFiles(): array
    {
        $fileNames = [];
        foreach (\Illuminate\Support\Facades\File::files(base_path('lang')) as $file) {
            $fileNames[pathinfo($file)['filename']]
                = LocaleNames::get(app()->getLocale())[pathinfo($file)['filename']];
        }
        return $fileNames;
    }
    public function loadTranslation(?string $key = null): null|string|array
    {
        if (!$key) {
            return null;
        }
        $path = base_path('lang');
        $data = file_get_contents($path . '/' . $key . '.json');
        return $data;
    }
}
