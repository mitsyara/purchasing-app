<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

#[ObservedBy([\App\Observers\CustomsDataCategoryObserver::class])]
class CustomsDataCategory extends Model
{
    protected $connection = 'mysql_customs_data';

    protected $fillable = [
        'name',
        'keywords',
        'description',
        'current_index',
        'count',
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

    /**
     * Recalculate count of related customsDatas for all categories
     * and update cache for each category.
     */
    public static function recalculateAggregates(): void
    {
        // Count Category's customsDatas
        $counts = \App\Models\CustomsData::query()
            ->select('customs_data_category_id', DB::raw('count(*) as total'))
            ->groupBy('customs_data_category_id')
            ->pluck('total', 'customs_data_category_id'); // [customs_data_category_id => total]

        // Update DB & cache with 1 transaction
        DB::transaction(function () use ($counts) {
            foreach ($counts as $categoryId => $total) {
                DB::table('customs_data_categories')
                    ->where('id', $categoryId)
                    ->update(['count' => $total]);

                // Update cache
                Cache::put("customs_data_category:{$categoryId}", $total, now()->addDay());
            }
        });

        // set count = 0 for categories with no related customsDatas
        $allCategoryIds = self::pluck('id');
        $missingIds = $allCategoryIds->diff($counts->keys());
        foreach ($missingIds as $id) {
            DB::table('customs_data_categories')
                ->where('id', $id)
                ->update(['count' => 0]);
            Cache::put("customs_data_category:{$id}", 0, now()->addDay());
        }
    }

}
