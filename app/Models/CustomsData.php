<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

#[ObservedBy([\App\Observers\CustomsDataObserver::class])]
class CustomsData extends Model
{
    protected $connection = 'mysql_customs_data';
    
    protected $fillable = [
        'customs_data_category_id',
        'import_date',
        'importer',
        'product',
        'unit',
        'qty',
        'price',
        'export_country',
        'exporter',
        'incoterm',
        'hscode',

        'category_keywords_hash',
    ];

    protected $casts = [
        'import_date' => 'date',
        'qty' => 'decimal:3',
        'price' => 'decimal:3',
        'total' => 'decimal:3',
    ];

    /**
     * Relationship
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(CustomsDataCategory::class, 'customs_data_category_id');
    }

    /**
     * Normalize fields
     */
    protected function unit(): Attribute
    {
        return Attribute::make(
            get: fn($value) => mb_strtoupper($value),
            set: fn($value) => mb_strtoupper($value),
        );
    }

    protected function incoterm(): Attribute
    {
        return Attribute::make(
            get: fn($value) => mb_strtoupper($value),
            set: fn($value) => mb_strtoupper($value),
        );
    }

    protected function hscode(): Attribute
    {
        return Attribute::make(
            get: fn($value) => mb_strtoupper($value),
            set: fn($value) => mb_strtoupper($value),
        );
    }

    /**
     * Dự đoán category dựa theo tên sản phẩm
     */
    public function guessCategoryByName(?string $currentKeywordsHash = null): bool
    {
        // Nếu đã xử lý với đúng hash → bỏ qua
        if ($this->category_keywords_hash === $currentKeywordsHash) {
            return false;
        }

        $productLower = Str::lower($this->product);

        $categories = Cache::rememberForever(
            'customs_data_categories.all',
            fn() => CustomsDataCategory::all(['id', 'name', 'keywords'])
        );

        $bestCategory = null;
        $maxMatchCount = 0;

        foreach ($categories as $category) {
            $matchCount = $category->keywordList->filter(
                fn($kw) =>
                str_contains($productLower, $kw)
            )->count();

            if (
                $matchCount > $maxMatchCount ||
                ($matchCount === $maxMatchCount && $bestCategory && $category->id < $bestCategory->id)
            ) {
                $maxMatchCount = $matchCount;
                $bestCategory = $category;
            }
        }

        if ($bestCategory) {
            return $this->updateQuietly([
                'customs_data_category_id' => $bestCategory->id,
                'category_keywords_hash' => $currentKeywordsHash
            ]);
        }

        // Không match nhưng vẫn ghi hash để không chạy lại
        $this->updateQuietly(['category_keywords_hash' => $currentKeywordsHash]);

        return false;
    }
}
