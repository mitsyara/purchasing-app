<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class ProductCustomer extends Pivot
{
    protected $table = 'product_customer';
    public $timestamps = false;
    public $incrementing = true;

    protected $fillable = [
        'product_id',
        'contact_id'
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'contact_id');
    }
}
