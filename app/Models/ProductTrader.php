<?php

namespace App\Models;

use App\Traits\HasComments;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class ProductTrader extends Pivot
{
    use \App\Traits\HasLoggedActivity;
    use HasComments;
    public $incrementing = true;
    public $timestamps = false;

    protected $fillable = [
        'product_id',
        'contact_id',
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
