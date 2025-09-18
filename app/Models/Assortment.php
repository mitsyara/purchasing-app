<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Assortment extends Model
{
    protected $fillable = [
        'assortment_code',
        'assortment_name',
        'assortment_description',
        'category_id',
        'assortment_notes',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function assortmentProducts(): HasMany
    {
        return $this->hasMany(AssortmentProduct::class, 'assortment_id');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(
            Product::class,
            'assortment_product',
            'assortment_id',
            'product_id'
        );
    }
}
