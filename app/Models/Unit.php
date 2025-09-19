<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Unit extends Model
{
    protected $fillable = [
        'parent_id',
        'unit_code',
        'unit_name',
        'conversion_factor',
        'notes',
    ];

    protected $casts = [
        'conversion_factor' => 'float',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'parent_id');
    }
}
