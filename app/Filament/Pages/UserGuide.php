<?php

namespace App\Filament\Pages;

use App\Filament\BasePage as Page;
use Filament\Panel;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\Table\TableExtension;
use League\CommonMark\MarkdownConverter;

class UserGuide extends Page
{
    protected string $view = 'filament.pages.user-guide';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedQuestionMarkCircle;

    protected static bool $shouldRegisterNavigation = false;

    public ?string $pin = '01-intro';
    public array $docs = [];
    public ?string $content = null;

    // Cho phép đồng bộ query string (?pin=xxx)
    protected $queryString = ['pin'];

    public function mount(): void
    {
        $path = base_path('docs/user-guide');

        $this->docs = collect(File::files($path))
            ->map(function ($f) {
                $slug = $f->getFilenameWithoutExtension();
                $clean = preg_replace('/^\d+-/', '', $slug);
                return [
                    'name' => Str::title(str_replace('-', ' ', $clean)),
                    'slug' => $slug,
                ];
            })
            ->values()
            ->toArray();

        $this->loadContent();
    }

    // 👇 Livewire sẽ gọi hàm này khi $pin thay đổi
    public function updatedPin(): void
    {
        $this->loadContent();
    }

    private function loadContent(): void
    {
        if (!$this->pin) {
            $this->content = null;
            return;
        }

        $file = base_path("docs/user-guide/{$this->pin}.md");

        if (!File::exists($file)) {
            $this->content = '<p class="text-red-500">❌ File not found.</p>';
            return;
        }

        // 👇 Bật TableExtension
        $environment = new Environment();
        $environment->addExtension(new CommonMarkCoreExtension());
        $environment->addExtension(new TableExtension());

        $converter = new MarkdownConverter($environment);

        $this->content = $converter->convert(File::get($file))->getContent();
    }

    public static function getSlug(?Panel $panel = null): string
    {
        return 'user-guide'; // chỉ 1 route: /admin/user-guide
    }
}
