<?php

namespace App\Filament\Tables\Columns;

use Livewire\Component;
use Closure;

use Filament\Tables\Columns\Column;
use Illuminate\Support\Str;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Facades\Blade;

class LinkColumn extends Column
{
    protected string $view = 'filament.tables.columns.link-column';

    protected bool | Closure $isClickDisabled = true;

    protected bool $isMultiple = false;

    protected ?string $titleColumnName = null;

    protected ?Closure $nameCallback = null;

    protected ?Closure $typeCallback = null;

    protected ?int $textLimit = null;

    public function multiple(bool $condition = true): static
    {
        $this->isMultiple = $condition;
        return $this;
    }

    public function phone(): static
    {
        $this->typeCallback = fn() => 'phone';
        return $this;
    }

    public function email(): static
    {
        $this->typeCallback = fn() => 'email';
        return $this;
    }

    public function titleColumn(string $column): static
    {
        $this->titleColumnName = $column;
        return $this;
    }

    public function nameUsing(Closure $callback): static
    {
        $this->nameCallback = $callback;
        return $this;
    }

    public function typeUsing(Closure $callback): static
    {
        $this->typeCallback = $callback;
        return $this;
    }

    public function titleLimit(?int $textLimit = 16): static
    {
        $this->textLimit = $textLimit;
        return $this;
    }

    public function getState(): mixed
    {
        $state = parent::getState();

        if (blank($state)) {
            return new HtmlString('-');
        }

        /** @var \Illuminate\Database\Eloquent\Model|null $record */
        $record = $this->getRecord();

        // Multiple
        if ($this->isMultiple && is_array($state)) {
            $type = $this->evaluate($this->typeCallback);

            // Nếu có titleColumn (array), dùng key-value
            $titleMap = $this->titleColumnName && $record
                ? $record->{$this->titleColumnName} ?? []
                : $this->evaluate($this->nameCallback);

            return new HtmlString(
                collect($state)->map(function ($link) use ($titleMap, $type) {
                    $title = is_array($titleMap) ? $titleMap[$link] ?? $link : $link;
                    return $this->buildLink($link, $title, $type);
                })->implode('<br>')
            );
        }

        // Single
        $type = $this->evaluate($this->typeCallback);
        $title = null;

        if ($record && $this->titleColumnName) {
            $title = $record->{$this->titleColumnName};
        } else {
            $title = $this->evaluate($this->nameCallback);
        }

        return $this->buildLink($state, $title, $type);
    }

    protected function buildLink(?string $link, ?string $title = null, ?string $type = null): HtmlString
    {
        if (blank($link)) {
            return new HtmlString('-');
        }

        $title = $title ?? $link;

        if ($type === 'phone') {
            $icon = 'heroicon-s-phone-arrow-up-right';
            $linkHref = 'tel:' . $link;
        } elseif ($type === 'email') {
            $icon = 'heroicon-s-envelope';
            $linkHref = 'mailto:' . $link;
        } else {
            $icon = 'heroicon-s-arrow-top-right-on-square';

            if (str_starts_with($link, 'attachments/')) {
                $linkHref = url('storage/' . $link);
            } elseif (!filter_var($link, FILTER_VALIDATE_URL)) {
                $linkHref = 'https://' . ltrim($link, '/');
            } else {
                $linkHref = $link;
            }
        }

        $mainLinkHtml = Blade::render('filament::components.link', [
            'color' => 'info',
            'tooltip' => $this->textLimit ? $title : null,
            'href' => $linkHref,
            'target' => '_blank',
            'slot' => $this->textLimit ? Str::limit($title, 16) : $title,
            'icon' => $icon,
        ]);

        return new HtmlString($mainLinkHtml);
    }
}
