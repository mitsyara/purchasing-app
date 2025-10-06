<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class AssortmentProduct extends Pivot
{
    use \App\Traits\HasLoggedActivity;
    public $incrementing = true;
    
    protected $fillable = [
        'assortment_id',
        'product_id',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function assortment(): BelongsTo
    {
        return $this->belongsTo(Assortment::class, 'assortment_id');
    }
}
