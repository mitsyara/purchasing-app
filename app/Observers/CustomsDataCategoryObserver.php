<?php

namespace App\Observers;

use App\Models\CustomsDataCategory;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class CustomsDataCategoryObserver
{
    /**
     * Handle the CustomsDataCategory "created" event.
     */
    public function saved(CustomsDataCategory $customsDataCategory): void
    {
        if ($this->hasRealChanges($customsDataCategory)) {
            $this->reCache();
        }
    }

    /**
     * Handle the CustomsDataCategory "deleted" event.
     */
    public function deleted(CustomsDataCategory $customsDataCategory): void
    {
        $this->reCache();
    }

    // Helper methods

    protected function hasRealChanges(Model $model): bool
    {
        // Lấy danh sách field đã thay đổi, loại bỏ 'updated_at'
        $changed = collect($model->getChanges())->keys()->diff(['updated_at']);
        return $changed->isNotEmpty();
    }

    protected function reCache(): void
    {
        // all categories
        Cache::forget('customs_data_categories.all');
        Cache::rememberForever('customs_data_categories.all', function (): Collection {
            return \App\Models\CustomsDataCategory::all(['id', 'name', 'keywords']);
        });
        // keywords
        Cache::forget('customs_data_categories.keywords');
        Cache::rememberForever('customs_data_categories.keywords', function (): Collection {
            return CustomsDataCategory::all()
                ->flatMap(fn(CustomsDataCategory $category) => $category->keywordList)
                ->unique()->values();
        });

        // hash
        // Cache::forget('categories_hash');
        // Cache::rememberForever('categories_hash', function (): string {
        //     return CustomsDataCategory::currentKeywordsHash();
        // });
    }

}
