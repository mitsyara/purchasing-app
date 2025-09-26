<?php

namespace App\Models;

use App\Traits\HasCustomRecursiveQueryBuilder;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[ObservedBy([\App\Observers\CategoryObserver::class])]
class Category extends Model
{
    use HasCustomRecursiveQueryBuilder;

    protected $fillable = [
        'category_code',
        'category_name',
        'vat_id',
        'is_gmp_required',
        'parent_id',
        'category_keywords',
        'notes',
        'category_index',
    ];

    protected $casts = [
        'is_gmp_required' => 'boolean',
    ];

    public function vat(): BelongsTo
    {
        return $this->belongsTo(Vat::class, 'vat_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'category_id');
    }

    public function assortments(): HasMany
    {
        return $this->hasMany(Assortment::class, 'category_id');
    }

    // Models attributes
    public function keywords(): array
    {
        $keywords = $this->category_keywords ? explode(',', $this->category_keywords) : [];
        return array_merge($keywords, [$this->category_code, $this->category_name]);
    }

    // Helper methods
    public function getCategoryIndex(): string
    {
        $new_index = $this->category_index + 1;
        return $this->category_code . '-' . str_pad($new_index, 3, '0', STR_PAD_LEFT);
    }

    public function incrementIndex(): bool
    {
        $this->category_index += 1;
        return $this->saveQuietly();
    }
}
