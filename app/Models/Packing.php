<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Packing extends Model
{
    protected $fillable = [
        'packing_name',
        'unit_conversion_value',
        'unit_id',
        'packing_notes',
    ];

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

}
