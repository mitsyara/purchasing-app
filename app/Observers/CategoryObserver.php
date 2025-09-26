<?php

namespace App\Observers;

use App\Models\Category;

class CategoryObserver
{
    /**
     * Handle the Category "saved" event.
     */
    public function saved(Category $category): void
    {
        if ($category->parent && $category->wasChanged('parent_id')) {
            $category->updateQuietly([
                'vat_id' => $category->parent->vat_id,
                'is_gmp_required' => $category->parent->is_gmp_required,
            ]);
        }

        $category->descendants()->update([
            'vat_id' => $category->vat_id,
            'is_gmp_required' => $category->is_gmp_required,
        ]);
    }

    /**
     * Handle the Category "deleted" event.
     */
    public function deleted(Category $category): void
    {
        //
    }

    /**
     * Handle the Category "restored" event.
     */
    public function restored(Category $category): void
    {
        //
    }

    /**
     * Handle the Category "force deleted" event.
     */
    public function forceDeleted(Category $category): void
    {
        //
    }
}
