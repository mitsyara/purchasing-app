<?php

namespace App\Filament\Clusters\Settings\Pages;

use App\Filament\Clusters\Settings\SettingsCluster;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

use Filament\Forms\Components as F;
use Filament\Notifications\Notification;

class ManageUserGuide extends Page
{
    use \BezhanSalleh\FilamentShield\Traits\HasPageShield;

    protected string $view = 'filament.clusters.settings.pages.manage-user-guide';

    protected static ?string $cluster = SettingsCluster::class;
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;
    protected static ?int $navigationSort = 31;

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

    protected string $docsPath = 'docs/user-guide';
    public array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            F\Select::make('select_file')
                ->label(__('Select File'))
                ->options(function () {
                    $dir = base_path($this->docsPath);
                    if (!is_dir($dir)) return [];
                    $files = glob($dir . '/*.md');
                    return collect($files)
                        ->mapWithKeys(fn($file) => [basename($file) => basename($file)])
                        ->toArray();
                })
                ->placeholder(__('Select a file to load'))
                ->searchable()
                ->live()
                ->afterStateUpdated(function (callable $set, ?string $state) {
                    if ($state) {
                        $filePath = base_path($this->docsPath . '/' . $state);
                        $content = file_exists($filePath) ? file_get_contents($filePath) : '';
                        $set('file_name', $state);
                        $set('content', $content);
                    } else {
                        // Không chọn file → tạo mới
                        $set('file_name', null);
                        $set('content', null);
                    }
                }),

            F\TextInput::make('file_name')
                ->label(__('File Name'))
                ->required()
                ->afterStateUpdated(function (callable $set, ?string $state) {
                    if ($state) {
                        $fileName = str($state)->kebab();
                        // Thêm .md nếu chưa có
                        if (!str($fileName)->endsWith('.md')) {
                            $fileName .= '.md';
                        }
                        $set('file_name', $fileName);
                    }
                })
                ->live(onBlur: true),

            F\MarkdownEditor::make('content')
                ->label(__('Content'))
                ->columnSpanFull()
                ->required()
                ->reactive(),

            Action::make('save')
                ->label(__('Save User Guide'))
                ->button()
                ->color('success')
                ->requiresConfirmation()
                ->action(fn() => $this->submit()),
        ])
            ->columns()
            ->statePath('data');
    }

    public function submit(): void
    {
        $data = $this->form->getState();

        if ($data['file_name']) {
        }

        $dir = base_path($this->docsPath);
        if (!is_dir($dir)) mkdir($dir, 0755, true);

        $oldFile = $data['select_file'] ?? null; // file cũ nếu có chọn
        $newFileName = $data['file_name'];
        $newFilePath = $dir . '/' . $newFileName;

        $backupPath = null;

        try {
            // Nếu đang ghi đè file cũ, tạo backup
            if ($oldFile && file_exists($dir . '/' . $oldFile)) {
                $oldFilePath = $dir . '/' . $oldFile;

                // Nếu đổi tên file cũ, rename trước
                if ($oldFile !== $newFileName) {
                    rename($oldFilePath, $newFilePath);
                }

                // Tạo backup trước khi ghi
                if (file_exists($newFilePath)) {
                    $backupPath = $newFilePath . '.bak';
                    copy($newFilePath, $backupPath);
                }
            }

            // Ghi file
            file_put_contents($newFilePath, $data['content']);

            // Nếu ghi thành công → xóa backup
            if ($backupPath && file_exists($backupPath)) {
                unlink($backupPath);
            }

            Notification::make('saved')
                ->title(__('User Guide saved successfully'))
                ->success()
                ->send();
        } catch (\Throwable $e) {
            // Ghi lỗi → rollback
            if ($backupPath && file_exists($backupPath)) {
                copy($backupPath, $newFilePath);
                unlink($backupPath);
            }

            Notification::make('danger')
                ->title(__('Failed to save File'))
                ->danger()
                ->send();
            return;
        }

        // Reset form
        $this->reset('data');
    }

    // Helpers

    protected function getNextUserGuideIndex(): string
    {
        $dir = base_path($this->docsPath);
        $files = is_dir($dir) ? glob($dir . '/*.md') : [];
        $max = 0;

        foreach ($files as $file) {
            $basename = basename($file); // ví dụ "01-intro.md"
            if (preg_match('/^(\d+)-/', $basename, $matches)) {
                $number = (int) $matches[1];
                if ($number > $max) $max = $number;
            }
        }

        return sprintf('%02d', $max + 1);
    }
}
