<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

#[ObservedBy([\App\Observers\CustomsDataCategoryObserver::class])]
class CustomsDataCategory extends Model
{
    protected $fillable = [
        'name',
        'keywords',
        'description',
        'current_index',
    ];

    public function customsDatas(): HasMany
    {
        return $this->hasMany(CustomsData::class, 'customs_data_category_id');
    }

    // Attributes
    protected function keywords(): Attribute
    {
        return Attribute::set(fn($value) => is_string($value) ? mb_strtolower($value) : null);
    }

    public function keywordList(): Attribute
    {
        return Attribute::get(function () {
            return collect(explode(';', $this->keywords ?? ''))
                ->map(fn($kw) => trim(Str::lower($kw), " \t\n\r\0\x0B.,:;"))
                ->filter()
                ->whenEmpty(fn() => collect([Str::lower($this->name)]));
        });
    }

    // Helper methods
    public function hasKeywordIn(string $text): bool
    {
        $textLower = Str::lower($text);
        return $this->keywordList->contains(fn($kw) => str_contains($textLower, $kw));
    }

    public static function allKeywords(): Collection
    {
        return self::all()
            ->flatMap(fn($category) => $category->keywordList)
            ->unique()
            ->values();
    }

    public static function currentKeywordsHash(): string
    {
        $raw = self::query()
            ->orderBy('id')
            ->get(['name', 'keywords'])
            ->map(fn($cat) => $cat->name . '|' . $cat->keywords)
            ->implode(';');
        return md5($raw);
    }
}
