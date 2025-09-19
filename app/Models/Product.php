<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Product extends Model
{
    protected $fillable = [
        'product_code',
        'product_name',

        'is_active',
        'is_fav',

        'mfg_id',
        'category_id',
        'packing_id',

        'product_alert_qty',
        'product_life_cycle',
        'product_certificates',
        'notes',

        'product_full_name',
        'product_unit_label',
    ];

    // Manufacturer
    public function mfg(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'mfg_id');
    }

    // Category
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    // Packing
    public function packing(): BelongsTo
    {
        return $this->belongsTo(Packing::class, 'packing_id');
    }

    // Product Assortments
    public function productAssortments(): HasMany
    {
        return $this->hasMany(AssortmentProduct::class, 'product_id');
    }
    public function assortments(): BelongsToMany
    {
        return $this->belongsToMany(
            Assortment::class,
            'assortment_product',
            'product_id',
            'assortment_id'
        );
    }

    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    // Strong Traders
    public function strongTraders(): BelongsToMany
    {
        return $this->belongsToMany(
            Contact::class,
            'product_trader',
            'product_id',
            'contact_id'
        );
    }
    // Product strong Traders
    public function productStrongTraders(): HasMany
    {
        return $this->hasMany(ProductTrader::class, 'product_id');
    }

    // Helper methods
    public function setProductCode(int|string|null $category_id = null): static
    {
        $category = Category::find($category_id) ?? $this->category;
        if ($category) {
            $code = $category?->getCategoryIndex();
            $this->updateQuietly(['product_code' => $code]);
            $category->incrementIndex();
        }
        return $this;
    }

    public function setFullName(): static
    {
        $shortName = $this->mfg?->contact_short_name ?? $this->mfg->contact_code ?? 'N/A';
        $packingName = $this->packing?->packing_name ?? 'N/A';
        $this->updateQuietly([
            'product_full_name' => $this->product_name . ' [' . $shortName . ']'
            . ' ; ' . $packingName
        ]);
        return $this;
    }

    public function setUnitLabel(): static
    {
        $this->updateQuietly([
            'product_unit_label' => $this->packing?->unit?->unit_code
        ]);
        return $this;
    }

}
