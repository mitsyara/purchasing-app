<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\Pivot;

class ProductTrader extends Pivot
{
    public $incrementing = true;
    public $timestamps = false;

    protected $fillable = [
        'product_id',
        'contact_id',
        'commentable_id',
        'commentable_type',
    ];

    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'contact_id');
    }
}
